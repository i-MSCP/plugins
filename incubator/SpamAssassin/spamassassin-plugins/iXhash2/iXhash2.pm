
=head1 NAME

Mail::SpamAssassin::Plugin::iXhash2 - compute fuzzy checksums from mail
bodies and compare to known spam ones via DNS

=head1 SYNOPSIS

loadplugin Mail::SpamAssassin::Plugin::iXhash2 iXhash2.pm

 # The actual rule(s)
 ixhashdnsbl   IXHASH_IX  ix.dnsbl.manitu.net.
 body          IXHASH_IX  eval:check_ixhash('IXHASH_IX')
 describe      IXHASH_IX  http://www.ixhash.net/listinfo.html
 tflags        IXHASH_IX  net
 score         IXHASH_IX  1.5

 ixhashdnsbl   IXHASH_GENERIC  generic.ixhash.net.
 body          IXHASH_GENERIC  eval:check_ixhash('IXHASH_GENERIC')
 describe      IXHASH_GENERIC  http://www.ixhash.net/listinfo.html
 tflags        IXHASH_GENERIC  net
 score         IXHASH_GENERIC  1.5

 ixhashdnsbl   IXHASH_SEM  ixhash.spameatingmonkey.net.
 body          IXHASH_SEM  eval:check_ixhash('IXHASH_SEM')
 describe      IXHASH_SEM  http://spameatingmonkey.com/lists.html
 tflags        IXHASH_SEM  net
 score         IXHASH_SEM  1.5

=head1 DESCRIPTION

iXhash2.pm is a plugin for SpamAssassin 3.2.0 and up. It takes the body of a
mail, strips parts from it and then computes a hash value from the rest. 
These values will then be looked up via DNS to see if the hashes have
already been categorized as spam by others.

This plugin is based on parts of the procmail-based project 'NiX Spam',
developed by Bert Ungerer.(un@ix.de) For more information see
http://www.heise.de/ix/nixspam/.  The procmail code producing the hashes
only can be found here: ftp://ftp.ix.de/pub/ix/ix_listings/2004/05/checksums

iXhash2 is an unofficial version based on iXhash version 1.5.5
(http://www.ixhash.net), adding async DNS lookups for performance and
removing unneeded features.  It's fully compatible with the existing
implementation.

To see which DNS zones are currently available see: http://www.ixhash.net

=head1 LICENSE

Licensed to the Apache Software Foundation (ASF) under one or more
contributor license agreements.  See the NOTICE file distributed with
this work for additional information regarding copyright ownership.
The ASF licenses this file to you under the Apache License, Version 2.0
(the "License"); you may not use this file except in compliance with
the License.  You may obtain a copy of the License at:

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.

=head1 AUTHOR

Henrik Krohns <sa@hege.li>

=cut

package Mail::SpamAssassin::Plugin::iXhash2;
my $VERSION = 2.05;

use strict;
use Mail::SpamAssassin::Plugin;
use Digest::MD5 qw(md5_hex);

use vars qw(@ISA);
@ISA = qw(Mail::SpamAssassin::Plugin);

sub dbg { Mail::SpamAssassin::Plugin::dbg ("iXhash2: @_"); }

sub new
{
    my ($class, $mailsa) = @_;

    $class = ref($class) || $class;
    my $self = $class->SUPER::new($mailsa);
    bless ($self, $class);

    if ($mailsa->{local_tests_only}) {
        $self->{ixhash2_disabled} = 1;
        dbg("only local tests enabled, plugin disabled");
    }

    $self->set_config($mailsa->{conf});
    $self->register_eval_rule("check_ixhash");

    return $self;
}

sub set_config
{
    my ($self, $conf) = @_;
    my @cmds;

    push (@cmds, {
        setting => 'ixhashdnsbl',
        is_priv => 1,
        code => sub {
            my ($self, $key, $value, $line) = @_;
            unless (defined $value && $value !~ /^$/) {
                return $Mail::SpamAssassin::Conf::MISSING_REQUIRED_VALUE;
            }
            unless ($value =~ /^(\S+)\s+(\S+?)\.?$/) {
                return $Mail::SpamAssassin::Conf::INVALID_VALUE;
            }
            my $rulename = $1;
            my $zone = lc($2).'.';
            $self->{ixhashdnsbls}->{$rulename} = { zone=>$zone };
        }
    });

    $conf->{parser}->register_commands(\@cmds);
}

sub parsed_metadata
{
    my ($self, $opts) = @_;
    my $pms = $opts->{permsgstatus};
    my $conf = $pms->{main}->{conf};

    return if $self->{ixhash2_disabled};

    unless (keys %{$conf->{ixhashdnsbls}}) {
        dbg("no ixhashdnsbls configured");
        return;
    }

    unless ($pms->is_dns_available()) {
        dbg("DNS not available");
        return;
    }

    $pms->{ixhashdnsbl_active_rules} = {};
    foreach my $rulename (keys %{$pms->{conf}->{ixhashdnsbls}}) {
        next unless $pms->{conf}->is_rule_active('body_evals', $rulename);
        $pms->{ixhashdnsbl_active_rules}->{$rulename} = 1;
    }

    return unless scalar keys %{$pms->{ixhashdnsbl_active_rules}};

    # Calculate hashes
    my $body = $pms->{msg}->get_pristine_body();
    my $hash;
    # Method #1 and #2
    if ($hash = compute1sthash($body)) { $pms->{ixhash_hashes}->{$hash} = 1; }
    if ($hash = compute2ndhash($body)) { $pms->{ixhash_hashes}->{$hash} = 1; }
    # Method #3 only if first two not generated
    if (not defined $pms->{ixhash_hashes} and $hash = compute3rdhash($body)) {
        $pms->{ixhash_hashes}->{$hash} = 1;
    }

    unless (defined $pms->{ixhash_hashes}) {
        dbg("no suitable parts found for hashing");
        return;
    }

    # Create queries
    foreach my $rulename (keys %{$pms->{ixhashdnsbl_active_rules}}) {
        my $zone = $pms->{conf}->{ixhashdnsbls}->{$rulename}->{zone};
        foreach my $hash (keys %{$pms->{ixhash_hashes}}) {
            my $key = "IXHASH:$zone:$hash";
            my $id = $pms->{main}->{resolver}->bgsend($hash.'.'.$zone, 'A', undef, sub {
                my ($pkt, $id, $timestamp) = @_;
                $pms->{async}->set_response_packet($id, $pkt, $key, $timestamp);
                $self->process_dns_result($pms, $pkt, $rulename, $zone, $hash);
            });
            my $ent = { key=>$key, id=>$id, type=>'IXHASH-A', zone=>$zone };
            $pms->{async}->start_lookup($ent);
            $pms->register_async_rule_start($rulename);
        }
    }
}

# Dummy rule, logic is handled elsewhere
sub check_ixhash
{
    return 0;
}

sub process_dns_result
{
    my ($self, $pms, $response, $rulename, $zone, $hash) = @_;

    foreach my $rr ($response->answer) {
        next unless $rr->type eq 'A';
        next unless $rr->address =~ /^127\./;
        dbg("got hit at $zone for $hash ($rr->{address})");
        $pms->got_hit($rulename);
        $pms->register_async_rule_finish($rulename);
    }
}
                                                                                                                                                                
sub compute1sthash
{
    my $body = shift;

    #  Creation of hash # 1 if following conditions are met:
    # - mail contains 20 spaces or tabs or more - changed follwoing a suggestion by Karsten Bräckelmann
    # - mail consists of at least 2 lines
    #  This should generate the most hits (according to Bert Ungerer about 70%)
    #  This also is where you can tweak your plugin if you have problems with short mails FP'ing -
    #  simply raise that barrier here.

    my $spaces = 0;
    my $newlines = 0;
    my $ok;
    while ($body =~ /([ \t\n])/g) {
        if ($1 eq ' ' or $1 eq "\t") { $spaces++; }
        elsif ($1 eq "\n") { $newlines++; }
        if ($spaces >= 20 and $newlines >= 2) { $ok = 1; last; }
    }
    if ($ok) {
        # All space class chars just one time
        # Do this in two steps to avoid Perl segfaults
        # if there are more than x identical chars to be replaced
        # Thanks to Martin Blapp for finding that out and suggesting this workaround concerning spaces only
        # Thanks to Karsten Bräckelmann for pointing out this would also be the case with _any_ characater, not only spaces
        my $body_copy = $body;
        $body_copy =~ s/\r\n/\n/g;
        # Step One
        $body_copy =~ s/([[:space:]]{100})(?:\1+)/$1/g;
        # Step Two
        $body_copy =~ s/([[:space:]])(?:\1+)/$1/g;
        # remove graph class chars and some specials
        $body_copy =~ s/[[:graph:]]+//go;
        # Create actual digest
        my $digest = md5_hex($body_copy);
        dbg("hash method #1: $digest");
        return $digest;
    }

    dbg("hash method #1: not computed, requirements not met");
    return undef;
}

sub compute2ndhash
{
    my $body = shift;

    # Creation of hash # 2 if mail contains at least 3 of the following characters:
    # '[<>()|@*'!?,]' or the combination of ':/'
    # (To match something like "Already seen?  http:/host.domain.tld/")

    if ($body =~ /((([<>\(\)\|@\*'!?,])|(:\/)).*?){3,}/m) {
        my $body_copy = $body;
        # remove redundant stuff
        $body_copy =~ s/[[:cntrl:][:alnum:]%&#;=]+//g;
        # replace '_' with '.'
        $body_copy =~ tr/_/./;
        # replace duplicate chars. This too suffers from a bug in perl
        # so we do it in two steps
        # Step One
        $body_copy =~ s/([[:print:]]{100})(?:\1+)/$1/g;
        # Step Two
        $body_copy =~ s/([[:print:]])(?:\1+)/$1/g;
        # Computing hash...
        my $digest = md5_hex($body_copy);
        dbg("hash method #2: $digest");
        return $digest;
    }

    dbg("hash method #2: not computed, requirements not met");
    return undef;
}

sub compute3rdhash
{
    my $body = shift;

    # Compute hash # 3 if
    # - there are at least 8 non-space characters in the body and
    # - neither hash #1 nor hash #2 have been computed

    if ($body =~ /[\S]{8}/) {
        my $body_copy = $body;
        $body_copy =~ s/[[:cntrl:][:space:]=]+//g;
        # replace duplicate chars. This too suffers from a bug in perl
        # so we do it in two steps
        # Step One
        $body_copy =~ s/([[:print:]]{100})(?:\1+)/$1/g;
        # Step Two
        $body_copy =~ s/([[:graph:]])(?:\1+)/$1/g;
        # Computing actual hash
        my $digest = md5_hex($body_copy);
        dbg("hash method #3: $digest");
        return $digest;
    }

    dbg("hash method #3: not computed, requirements not met");
    return undef;
}

1;
