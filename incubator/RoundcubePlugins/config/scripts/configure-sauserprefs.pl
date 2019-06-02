#!/usr/bin/env perl

=head1 NAME

 configure-sauserprefs.pl - Configure or deconfigure the Roundcube sauserprefs plugin

=head1 SYNOPSIS

 perl configure-sauserprefs.pl configure|deconfigure

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2019 Laurent Declercq <l.declercq@nuxwin.com>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../../../../engine/PerlLib", "$FindBin::Bin/../../../../../engine/PerlVendor";
use File::Basename 'basename';
use iMSCP::Boolean;
use iMSCP::Bootstrapper;
use iMSCP::Crypt 'decryptRijndaelCBC';
use iMSCP::Database;
use iMSCP::Debug qw/ debug error getMessageByType newDebug setDebug setVerbose /;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::TemplateParser 'process';
use JSON;
use List::Util 'uniq';
use PHP::Var 'export';
use POSIX 'locale_h';
use Servers::po;

=head1 PUBLIC FUNCTIONS

=over 4

=item configure( )

 Configure the plugin

 Return void, die on failure

=cut

sub configure
{
    my $dbh = iMSCP::Database->factory();
    my $dbi = $dbh->getRawDb();

    my %config = @{ $dbi->selectcol_arrayref(
        "SELECT `name`, `value` FROM `config` WHERE `name` LIKE 'ROUNDCUBE_%'",
        { Columns => [ 1, 2 ] }
    ) };

    # We do not want act if the SQL user isn't there...
    return unless %config;

    my $dbName = $::imscpConfig{'DATABASE_NAME'} . '_spamassassin';
    my $dbUser = decryptRijndaelCBC(
        $::imscpDBKey, $::imscpDBiv, $config{'ROUNDCUBE_SQL_USER'}
    );
    my $dbPasswd = decryptRijndaelCBC(
        $::imscpDBKey, $::imscpDBiv, $config{'ROUNDCUBE_SQL_USER_PASSWD'}
    );

    $dbi->do(
        "
            GRANT SELECT, INSERT, UPDATE, DELETE
            ON `@{ [ $dbName =~ s/([%_])/\\$1/gr ] }`.*
            TO ?\@?
        ",
        undef,
        $dbUser,
        $::imscpConfig{'DATABASE_USER_HOST'}
    );

    my $file = iMSCP::File->new(
        filename => "$::imscpConfig{'GUI_ROOT_DIR'}/vendor/imscp/roundcube/roundcubemail/plugins/sauserprefs/config.inc.php"
    );
    defined( my $fileC = $file->getAsRef()) or die( getMessageByType(
        'error', { amount => 1, remove => TRUE }
    ));

    ${ $fileC } = process(
        {
            DB_USER   => $dbUser,
            DB_PASSWD => $dbPasswd,
            DB_HOST   => $::imscpConfig{'DATABASE_HOST'},
            DB_PORT   => $::imscpConfig{'DATABASE_PORT'},
            DB_NAME   => $dbName
        },
        ${ $fileC }
    );

    my $saPluginConfig = decode_json( _getSpamAssassinPluginConfig() );
    my $dontOverride = ( decode_json( _getRoundcubePluginsPluginConfig() ) )
        ->{'plugin_definitions'}->{'sauserprefs'}->{'config'}->{'parameters'}
        ->{'sauserprefs_dont_override'};
    $dontOverride = [] unless ref $dontOverride eq 'ARRAY';

    # If SPAM mail are never tagged, there is no reasons to let's the user change
    # headers and report related settings through Roundcube sauserprefs plugin
    if ( $saPluginConfig->{'spamass_milter'}->{'spam_reject_policy'} == -1 ) {
        push @{ $dontOverride }, '{headers}', '{report}', 'rewrite_header Subject'
    }

    my $saPluginDefs = $saPluginConfig->{'spamassassin'}->{'plugin_definitions'};
    goto WRITE_CONFIG unless ref $saPluginConfig eq 'HASH';
    
    # Hide Bayes settings in Roundcube sauserprefs plugin if the SA
    # Bayes  plugin is disabled or enforced
    if ( !$saPluginDefs->{'Mail::SpamAssassin::Plugin::Bayes'}->{'enabled'}
        || $saPluginDefs->{'Mail::SpamAssassin::Plugin::Bayes'}->{'enforced'}
    ) {
        push @{ $dontOverride }, '{bayes}';
    }

    # If the SA Bayes plugin operates on a site-wide basis, we must
    # prevent users to act on threshold-based auto-learning
    # discriminator for SpamAssassin's Bayes subsystem.
    if ( $saPluginDefs->{'Mail::SpamAssassin::Plugin::Bayes'}->{'site_wide'} ) {
        push @{ $dontOverride },
            'bayes_auto_learn_threshold_nonspam',
            'bayes_auto_learn_threshold_spam';
    }
    
    if ( ( !$saPluginDefs->{'Mail::SpamAssassin::Plugin::DCC'}->{'enabled'}
            || $saPluginDefs->{'Mail::SpamAssassin::Plugin::DCC'}->{'enforced'}
        ) && ( !$saPluginDefs->{'Mail::SpamAssassin::Plugin::Pyzor'}->{'enabled'}
            || $saPluginDefs->{'Mail::SpamAssassin::Plugin::Pyzor'}->{'enforced'}
        ) && ( !$saPluginDefs->{'Mail::SpamAssassin::Plugin::Razor2'}->{'enabled'}
            || $saPluginDefs->{'Mail::SpamAssassin::Plugin::Razor2'}->{'enforced'}
        ) && ( !$saPluginDefs->{'Mail::SpamAssassin::Plugin::URIDNSBL'}->{'enabled'}
            || $saPluginDefs->{'Mail::SpamAssassin::Plugin::URIDNSBL'}->{'enforced'}
        )
    ) {
        # All plugins for which parameters are settable through the Roundcube
        # sauserprefs plugin are disabled or enforced. Thus, there is no
        # reasons to let's user act on them.
        push @{ $dontOverride }, '{tests}';
    } else {
        # Hide DCC setting in Roundcube sauserprefs plugin if the SA plugin is
        # disabled or enforced
        if ( !$saPluginDefs->{'Mail::SpamAssassin::Plugin::DCC'}->{'enabled'}
            || $saPluginDefs->{'Mail::SpamAssassin::Plugin::DCC'}->{'enforced'}
        ) {
            push @{ $dontOverride }, 'use_dcc';
        }

        # Hide Pyzor setting in Roundcube sauserprefs plugin if the SA plugin
        # is disabled or enforced
        if ( !$saPluginDefs->{'Mail::SpamAssassin::Plugin::Pyzor'}->{'enabled'}
            || $saPluginDefs->{'Mail::SpamAssassin::Plugin::Pyzor'}->{'enforced'}
        ) {
            push @{ $dontOverride }, 'use_pyzor';
        }

        # Hide Razor2 setting in Roundcube sauserprefs plugin if the SA plugin
        # is disabled or enforced
        if ( !$saPluginDefs->{'Mail::SpamAssassin::Plugin::Razor2'}->{'enabled'}
            || $saPluginDefs->{'Mail::SpamAssassin::Plugin::Razor2'}->{'enforced'}
        ) {
            push @{ $dontOverride }, 'use_razor2';
        }

        # Hide RBL checks setting in Roundcube sauserprefs plugin if SA RBL
        # checks are disabled or enforced
        if ( !$saPluginDefs->{'Mail::SpamAssassin::Plugin::URIDNSBL'}->{'enabled'}
            || $saPluginDefs->{'Mail::SpamAssassin::Plugin::URIDNSBL'}->{'enforced'}
        ) {
            push @{ $dontOverride }, 'use_rbl_checks';
        }
    }

    # Hide TextCat setting in Roundcube sauserprefs plugin if the SA plugin is
    # disabled or enforced
    if ( !$saPluginConfig->{'spamassassin'}->{'Mail::SpamAssassin::Plugin::TextCat'}->{'enabled'}
        || $saPluginConfig->{'spamassassin'}->{'Mail::SpamAssassin::Plugin::TextCat'}->{'enforced'}
    ) {
        push @{ $dontOverride }, 'ok_languages';
    }

    WRITE_CONFIG:
    ${ $fileC } =~ s/(\$config\s*\[\s*['"]sauserprefs_dont_override['"]\s*\]).*?;/$1 = @{ [
        export(
            [ sort { $a cmp $b } uniq( @{ $dontOverride } ) ],
            purity => TRUE,
            short  => TRUE
        )
    ] }/igs;
    
    $file->save() == 0 or die( getMessageByType(
        'error', { amount => 1, remove => TRUE }
    ));
}

=item deconfigure( )

 Deconfigure the plugin

 Return void, die on failure

=cut

sub deconfigure
{
    my $dbh = iMSCP::Database->factory();
    my $dbi = $dbh->getRawDb();
    my $row = $dbi->selectrow_hashref(
        "SELECT `value` FROM `config` WHERE `name` = 'ROUNDCUBE_SQL_USER'",
    );

    # We do not want act if the SQL user isn't there...
    return unless %{ $row };

    $dbi->do(
        'DELETE FROM `mysql`.`db` WHERE `Db` = ? and `User` = ?',
        undef,
        decryptRijndaelCBC( $::imscpDBKey, $::imscpDBiv, $row->{'value'} ),
        $::imscpConfig{'DATABASE_NAME'} . '_spamassassin'
    );
    $dbi->do( 'FLUSH PRIVILEGES' );
}

=back

=head1 PRIVATE FUNCTIONS

=over 4

=item  getSpamAssassinPluginConfig

 Get SpamAssassin plugin configuration

 Return hashref SpamAssassin plugin configuration

=cut

sub _getSpamAssassinPluginConfig
{
    defined( my $config = iMSCP::Database->factory()->getRawDb()->selectrow_hashref(
        "SELECT plugin_config FROM plugin WHERE plugin_name = 'SpamAssassin'",
    )) or die( 'SpamAssassin plugin data not found in database' );

    $config->{'plugin_config'};
}

=item  _getRoundcubePluginsPluginConfig

 Get RoundcubePlugins plugin configuration

 Return hashref Roundcube plugin configuration

=cut

sub _getRoundcubePluginsPluginConfig
{
    defined( my $config = iMSCP::Database->factory()->getRawDb()->selectrow_hashref(
        "SELECT plugin_config FROM plugin WHERE plugin_name = 'RoundcubePlugins'",
    )) or die( 'RoundcubePlugins plugin data not found in database' );

    $config->{'plugin_config'};
}

=back

=head1 MAIN

=over 4

=cut

eval {
    @{ENV}{qw/ LANG PATH /} = (
        'C.UTF-8',
        '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin'
    );

    setlocale( LC_MESSAGES, 'C.UTF-8' );
    setDebug( iMSCP::Getopt->debug( TRUE ));
    setVerbose( iMSCP::Getopt->verbose( TRUE ));
    newDebug( "@{ [ basename( $0, '.pl' ) ] }.log" );

    iMSCP::Bootstrapper->getInstance()->lock(
        "/var/lock/@{ [ basename( $0, '.pl' ) ] }.lock"
    );
    iMSCP::Bootstrapper->getInstance()->boot( {
        config_readonly => TRUE,
        nolock          => TRUE
    } );

    my $stage = $ARGV[0] // die "Missing 'stage' argument";

    # Do not act if $stage isn't one of 'configure' or 'deconfigure'
    return unless grep ( $stage eq $_, qw/ configure deconfigure / );

    my %dispatch = (
        configure   => \&configure,
        deconfigure => \&deconfigure
    );
    $dispatch{$stage}->();
};
if ( $@ ) {
    error( $@ );
    exit 1;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
