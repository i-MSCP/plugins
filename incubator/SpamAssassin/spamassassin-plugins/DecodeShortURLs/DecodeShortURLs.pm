# <@LICENSE>
# Licensed to the Apache Software Foundation (ASF) under one or more
# contributor license agreements.  See the NOTICE file distributed with
# this work for additional information regarding copyright ownership.
# The ASF licenses this file to you under the Apache License, Version 2.0
# (the "License"); you may not use this file except in compliance with
# the License.  You may obtain a copy of the License at:
# 
#     http://www.apache.org/licenses/LICENSE-2.0
# 
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
# </@LICENSE>

# Author:  Steve Freegard <steve.freegard@fsl.com>

=head1 NAME

DecodeShortURLs - Expand shortened URLs

=head1 SYNOPSIS

  loadplugin    Mail::SpamAssassin::Plugin::DecodeShortURLs

  url_shortener bit.ly
  url_shortener go.to
  ...

=head1 DESCRIPTION

This plugin looks for URLs shortened by a list of URL shortening services and
upon finding a matching URL will connect using to the shortening service and
do an HTTP HEAD lookup and retrieve the location header which points to the 
actual shortened URL, it then adds this URL to the list of URIs extracted by 
SpamAssassin which can then be accessed by other plug-ins, such as URIDNSBL.

This plugin also sets the rule HAS_SHORT_URL if any matching short URLs are 
found.

Regular 'uri' rules can be used to detect and score links disabled by the
shortening service for abuse and URL_BITLY_BLOCKED is supplied as an example.
It should be safe to score this rule highly on a match as experience shows
that bit.ly only blocks access to a URL if it has seen consistent abuse and
problem reports.

As of version 0.3 this plug-in will follow 'chained' shorteners e.g.


short URL -> short URL -> short URL -> real URL


If this form of chaining is found, then the rule 'SHORT_URL_CHAINED' will be
fired.  If a loop is detected then 'SHORT_URL_LOOP' will be fired.
This plug-in limits the number of chained shorteners to a maximim of 10 at 
which point it will fire the rule 'SHORT_URL_MAXCHAIN' and go no further.

If a shortener returns a '404 Not Found' result for the short URL then the 
rule 'SHORT_URL_404' will be fired.

If a shortener does not return an HTTP redirect, then a dynamic rule will
be fired: 'SHORT_<SHORTENER>_<CODE>' where <SHORTENER> is the uppercase
name of the shortener with dots converted to underscores.  e.g.:
'SHORT_T_CO_200' This is to handle the case of t.co which now returns an 
HTTP 200 and an abuse page instead of redirecting to an abuse page like
every other shortener does...

=head1 NOTES

This plugin runs the parsed_metadata hook with a priority of -1 so that
it may modify the parsed URI list prior to the URIDNSBL plugin which
runs as priority 0.

Currently the plugin queries a maximum of 10 distinct shortened URLs with
a maximum timeout of 5 seconds per lookup.

=head1 ACKNOWLEDGEMENTS

A lot of this plugin has been hacked together by using other plugins as 
examples.  The author would particularly like to tip his hat to Karsten
Bräckelmann for the _add_uri_detail_list() function that he stole from
GUDO.pm for which this plugin would not be possible due to the SpamAssassin
API making no provision for adding to the base list of extracted URIs and 
the author not knowing enough about Perl to be able to achieve this without 
a good example from someone that does ;-)

=cut

package Mail::SpamAssassin::Plugin::DecodeShortURLs;

my $VERSION = 0.11;

use Mail::SpamAssassin::Plugin;
use strict;
use warnings;

use vars qw(@ISA);
@ISA = qw(Mail::SpamAssassin::Plugin);

use constant HAS_LWP_USERAGENT => eval { local $SIG{'__DIE__'}; require LWP::UserAgent; };
use constant HAS_SQLITE => eval { local $SIG{'__DIE__'}; require DBD::SQLite; };
use Fcntl qw(:flock SEEK_END);
use Sys::Syslog qw(:DEFAULT setlogsock);


sub dbg {
  my $msg = shift;
  return Mail::SpamAssassin::Logger::dbg("DecodeShortURLs: $msg");
}

sub new {
  my $class = shift;
  my $mailsaobject = shift;

  $class = ref($class) || $class;
  my $self = $class->SUPER::new($mailsaobject);
  bless ($self, $class);

  if ($mailsaobject->{local_tests_only} || !HAS_LWP_USERAGENT) {
    $self->{disabled} = 1;
  } else {
    $self->{disabled} = 0;
  }

  unless ($self->{disabled}) {
   $self->{ua} = new LWP::UserAgent;
   $self->{ua}->{max_redirect} = 0;
   $self->{ua}->{timeout} = 5;
   $self->{ua}->env_proxy;
   $self->{logging} = 0;
   $self->{caching} = 0;
   $self->{syslog} = 0;
  }

  $self->set_config($mailsaobject->{conf});
  $self->register_method_priority ('parsed_metadata', -1);
  $self->register_eval_rule('short_url_tests');

  return $self;
}

sub set_config {
  my($self, $conf) = @_;
  my @cmds = ();

  push (@cmds, {
    setting => 'url_shortener',
    default => {},
    code => sub {
      my ($self, $key, $value, $line) = @_;
      if ($value =~ /^$/) {
        return $Mail::SpamAssassin::Conf::MISSING_REQUIRED_VALUE;
      }
      foreach my $domain (split(/\s+/, $value)) {
        $self->{url_shorteners}->{lc $domain} = 1;
      }
    }
  });

=cut

=head1 PRIVILEGED SETTINGS

=over 4

=item url_shortener_log		(default: none)

A path to a log file to be written to.  The file will be created if it does
not already exist and must be writable by the user running spamassassin.

For each short URL found the following will be written to the log file:
[unix_epoch_time] <short url> => <decoded url>

=cut

  push (@cmds, {
    setting => 'url_shortener_log',
    default => '',
    is_priv => 1,
    type => $Mail::SpamAssassin::Conf::CONF_TYPE_STRING
  });

=item url_shortener_cache		(default: none)

The full path to a database file to write cache entries to.  The database will
be created automatically if is does not already exist but the supplied path
and file must be read/writable by the user running spamassassin or spamd.


NOTE: you will need the DBD::SQLite module installed to use this feature.

Example:

url_shortener_cache /tmp/DecodeShortURLs.sq3

=cut


  push (@cmds, {
    setting => 'url_shortener_cache',
    default => '',
    is_priv => 1,
    type => $Mail::SpamAssassin::Conf::CONF_TYPE_STRING
  });

=item url_shortener_cache_ttl		(default: 86400)

The length of time a cache entry will be valid for in seconds.
Default is 86400 (1 day).


NOTE: you will also need to run the following via cron to actually remove the
records from the database:

echo "DELETE FROM short_url_cache WHERE modified < strftime('%s',now) - <ttl>; | sqlite3 /path/to/database"


NOTE: replace <ttl> above with the same value you use for this option

=cut

  push (@cmds, {
    setting => 'url_shortener_cache_ttl',
    is_admin => 1,
    default => 86400,
    type => $Mail::SpamAssassin::Conf::CONF_TYPE_NUMERIC
  });

=item url_shortener_syslog           (default: 0 (off))

If this option is enabled (set to 1), then short URLs and the decoded URLs will be logged to syslog (mail.info).

=cut


  push (@cmds, {
    setting => 'url_shortener_syslog',
    is_admin => 1,
    default => 0,
    type => $Mail::SpamAssassin::Conf::CONF_TYPE_BOOL
  });

  push (@cmds, {
    setting => 'log_target_only',
    is_admin => 1,
    default => 0,
    type => $Mail::SpamAssassin::Conf::CONF_TYPE_BOOL
  });

  $conf->{parser}->register_commands(\@cmds);
}

sub parsed_metadata {
  my ($self, $opts) = @_;
  my $pms = $opts->{permsgstatus};
  my $msg = $opts->{msg};

  return if $self->{disabled};

  dbg ('warn: get_uri_detail_list() has been called already')
    if exists $pms->{uri_detail_list};

  # don't keep dereferencing these
  $self->{url_shorteners} = $pms->{main}->{conf}->{url_shorteners};
  ($self->{url_shortener_log}) = ($pms->{main}->{conf}->{url_shortener_log} =~ /^(.*)$/g);
  ($self->{url_shortener_cache}) = ($pms->{main}->{conf}->{url_shortener_cache} =~ /^(.*)$/g);
  $self->{url_shortener_cache_ttl} = $pms->{main}->{conf}->{url_shortener_cache_ttl};
  $self->{url_shortener_syslog} = $pms->{main}->{conf}->{url_shortener_syslog};
  $self->{log_target_only} = $pms->{main}->{conf}->{log_target_only};

  # Sort short URLs into hash to de-dup them
  my %short_urls;
  my $uris = $pms->get_uri_detail_list();
  while (my($uri, $info) = each %{$uris}) {
    next unless ($info->{domains});
    foreach ( keys %{ $info->{domains} } ) {
      if (exists $self->{url_shorteners}->{lc $_}) {
        # NOTE: $info->{domains} appears to contain all the domains parsed 
        # from the single input URI with no way to work out what the base
        # domain is.  So to prevent someone from stuffing the URI with a
        # shortener to force this plug-in to follow a link that *isn't* on
        # the list of shorteners; we enforce that the shortener must be the
        # base URI and that a path must be present.
        if ($uri !~ /^https:\/\/(?:www\.)?$_\/.+$/i) {
          dbg("Discarding URI: $uri");
          next;
        }
        $short_urls{$uri} = 1;
        next;
      }
    }
  }

  # Make sure we have some work to do
  # Before we open any log files etc.
  my $count = scalar keys %short_urls;
  return undef unless $count gt 0;

  # Initialise logging if enabled
  if ($self->{url_shortener_log}) {
    eval { 
      local $SIG{'__DIE__'};
      open($self->{logfh}, '>>'.$self->{url_shortener_log}) or die $!; 
    };
    if ($@) {
      dbg("warn: $@");
    } else {
      $self->{logging} = 1;
    }
  }

  # Initialise syslog if enabled
  if ($self->{url_shortener_syslog}) {
    eval {
      local $SIG{'__DIE__'};
      openlog('DecodeShortURLs','ndelay,pid','mail');
    };
    if ($@) {
      dbg("warn: $@");
    } else {
      $self->{syslog} = 1;
    }
  }

  # Initialise cache if enabled
  if ($self->{url_shortener_cache} && HAS_SQLITE) {
    eval { 
      local $SIG{'__DIE__'};
      $self->{dbh} = DBI->connect_cached("dbi:SQLite:dbname=".$self->{url_shortener_cache},"","", {RaiseError => 1, PrintError => 0, InactiveDestroy => 1}) or die $!; 
    };
    if ($@) {
      dbg("warn: $@");
    } else {
      $self->{caching} = 1;

      # Create database if needed
      eval {
        local $SIG{'__DIE__'};
        $self->{dbh}->do("
          CREATE TABLE IF NOT EXISTS short_url_cache (
            short_url   TEXT PRIMARY KEY NOT NULL,
            decoded_url TEXT NOT NULL,
            hits        INTEGER NOT NULL DEFAULT 1,
            created     INTEGER NOT NULL DEFAULT (strftime('%s','now')),
            modified    INTEGER NOT NULL DEFAULT (strftime('%s','now'))
          )
        ");
        $self->{dbh}->do(" 
          CREATE INDEX IF NOT EXISTS short_url_by_modified 
            ON short_url_cache(short_url, modified)
        ");
        $self->{dbh}->do("
          CREATE INDEX IF NOT EXISTS short_url_modified
            ON short_url_cache(modified)
        ");
      };
      if ($@) {
        dbg("warn: $@");
        $self->{caching} = 0;
      }
    }
  }

  my $max_short_urls = 10;
  foreach my $short_url (keys %short_urls) {
    next if ($max_short_urls le 0);
    my $location = $self->recursive_lookup($short_url, $pms);
    $max_short_urls--;
  }

  # Close log
  eval { 
    local $SIG{'__DIE__'};
    close($self->{logfh}) or die $!; 
  } if $self->{logging};

  # Close syslog
  eval {
    local $SIG{'__DIE__'};
    closelog() or die $!;
  } if $self->{syslog};

  # Don't disconnect cached database handle
  # eval { $self->{dbh}->disconnect() or die $!; } if $self->{caching};
}

sub recursive_lookup {
  my ($self, $short_url, $pms, %been_here) = @_;

  my $count = scalar keys %been_here;
  dbg("Redirection count $count") if $count gt 0;
  if ($count >= 10) {
    dbg("Error: more than 10 shortener redirections");
    # Fire test
    $pms->got_hit('SHORT_URL_MAXCHAIN');
    return undef;
  }

  my $location;

  if ($self->{caching} && ($location = $self->cache_get($short_url))) {
    dbg("Found cached $short_url => $location");
    eval {
      local $SIG{'__DIE__'};
      if ($self->{log_target_only}) {
        $self->log_to_file($location);
      } else {
        $self->log_to_file("$short_url => $location");
      }
    } if $self->{logging};
    syslog('info',"Found cached $short_url => $location") if $self->{syslog};
  } else {
    # Not cached; do lookup
    my $response = $self->{ua}->head($short_url);
    if (!$response->is_redirect) {
      dbg("URL is not redirect: $short_url = ".$response->status_line);
      if ((my ($domain) = ($short_url =~ /^https?:\/\/(\S+)\//))) {
          if (exists $self->{url_shorteners}->{$domain}) {
              $domain =~ s/\./_/g;
              $domain = uc($domain);
              my $h = 'SHORT_' . $domain . '_' . $response->code;
              dbg("hit rule: $h");
              $pms->got_hit($h);
          }
      }
      $pms->got_hit('SHORT_URL_404') if($response->code == '404');
      return undef;
    }
    $location = $response->headers->{location};
    # Bail out if $short_url redirects to itself
    return undef if ($short_url eq $location);
    $self->cache_add($short_url, $location) if $self->{caching};
    dbg("Found $short_url => $location");
    eval {
      local $SIG{'__DIE__'};
      if ($self->{log_target_only}) {
        $self->log_to_file($location);
      } else {
        $self->log_to_file("$short_url => $location");
      }
    } if $self->{logging};
    syslog('info',"Found $short_url => $location") if $self->{syslog};
  }

  # At this point we have a new URL in $response
  $pms->got_hit('HAS_SHORT_URL');
  _add_uri_detail_list($pms, $location);

  # Set chained here otherwise we might mark a disabled page or 
  # redirect back to the same host as chaining incorrectly. 
  $pms->got_hit('SHORT_URL_CHAINED') if ($count gt 0);

  # Check if we are being redirected to a local page
  # Don't recurse in this case...
  if($location !~ /^https?:/) {
    my($host) = ($short_url =~ /^(https?:\/\/\S+)\//);
    $location = "$host/$location";
    dbg("Looks like a local redirection: $short_url => $location");
    _add_uri_detail_list($pms, $location);
    return $location;
  }

  # Check for recursion
  if ((my ($domain) = ($location =~ /^https?:\/\/(\S+)\//))) {
    if (exists $been_here{$location}) {
      # Loop detected
      dbg("Error: loop detected");
      $pms->got_hit('SHORT_URL_LOOP');
      return $location;
    } else {
      if (exists $self->{url_shorteners}->{$domain}) {
        $been_here{$location} = 1;
        # Recurse...
        return $self->recursive_lookup($location, $pms, %been_here);
      }
    }
  }

  # No recursion; just return the final location...
  return $location;
}

sub short_url_tests {
  # Set by parsed_metadata
  return 0;
}

# Beware.  Code copied from PerMsgStatus get_uri_detail_list().
# Stolen from GUDO.pm
sub _add_uri_detail_list {
  my ($pms, $uri) = @_;
  my $info;

  # Cache of text parsed URIs, as previously used by get_uri_detail_list().
  push @{$pms->{parsed_uri_list}}, $uri;

  $info->{types}->{parsed} = 1;

  $info->{cleaned} =
    [Mail::SpamAssassin::Util::uri_list_canonify (undef, $uri)];

  foreach (@{$info->{cleaned}}) {
    my ($dom, $host) = Mail::SpamAssassin::Util::uri_to_domain($_);

    if ($dom && !$info->{domains}->{$dom}) {
      # 3.4 compatibility as per Marc Martinec
      if ($host) {
          $info->{hosts}->{$host} = $dom;
      }
      $info->{domains}->{$dom} = 1;
      $pms->{uri_domain_count}++;
    }
  }

  $pms->{uri_detail_list}->{$uri} = $info;

  # And of course, copied code from PerMsgStatus get_uri_list().  *sigh*
  dbg ('warn: PMS::get_uri_list() appears to have been harvested'),
    push @{$pms->{uri_list}}, @{$info->{cleaned}}
    if exists $pms->{uri_list};
}

sub log_to_file {
  my ($self, $msg) = @_;
  return undef if not $self->{logging};
  my $fh = $self->{logfh};
  eval {
    flock($fh, LOCK_EX) or die $!;
    seek($fh, 0, SEEK_END) or die $!;
    if ($self->{log_target_only}) {
        print $fh "$msg\n";
    } else {
        print $fh '['.time.'] '.$msg."\n";
    }
    flock($fh, LOCK_UN) or die $!;
  };
}

sub cache_add {
  my ($self, $short_url, $decoded_url) = @_;
  return undef if not $self->{caching};

  eval {
    $self->{sth_insert} = $self->{dbh}->prepare_cached("
      INSERT OR IGNORE INTO short_url_cache (short_url, decoded_url)
      VALUES (?,?)
    ");
  };
  if ($@) {
    dbg("warn: $@");
    return undef;
  };

  $self->{sth_insert}->execute($short_url, $decoded_url);
  return undef;
}

sub cache_get {
  my ($self, $key) = @_;
  return undef if not $self->{caching};

  eval {
    $self->{sth_select} = $self->{dbh}->prepare_cached("
      SELECT decoded_url FROM short_url_cache
      WHERE short_url = ? AND modified > (strftime('%s','now') - ?)
    ");
  }; 
  if ($@) { 
   dbg("warn: $@"); 
   return undef;
  }

  eval {
    $self->{sth_update} = $self->{dbh}->prepare_cached("
      UPDATE short_url_cache
      SET modified=strftime('%s','now'), hits=hits+1
      WHERE short_url = ?
    ");
  };
  if ($@) {
   dbg("warn: $@");
   return undef;
  }

  $self->{sth_select}->execute($key, $self->{url_shortener_cache_ttl});
  my $row = $self->{sth_select}->fetchrow_array();
  if($row) {
    # Found cache entry; touch it to prevent expiry
    $self->{sth_update}->execute($key);
    $self->{sth_select}->finish();
    $self->{sth_update}->finish();
    return $row;
  }
 
  $self->{sth_select}->finish();
  $self->{sth_update}->finish(); 
  return undef;
}

1;
