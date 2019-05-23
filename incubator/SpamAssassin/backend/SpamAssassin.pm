=head1 NAME

 Plugin::SpamAssassin

=cut

# i-MSCP SpamAssassin plugin
# Copyright (C) 2015-2019 Laurent Declercq <l.declercq@nuxwin.com>
# Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>
# Copyright (C) 2013-2016 Rene Schuster <mail@reneschuster.de>
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

package Plugin::SpamAssassin;

use strict;
use warnings;
use File::Basename 'basename';
use iMSCP::Boolean;
use iMSCP::Crypt 'randomStr';
use iMSCP::Debug qw/ debug error /;
use iMSCP::Dir;
use iMSCP::Database;
use iMSCP::Execute 'execute';
use iMSCP::File;
use iMSCP::Rights 'setRights';
use iMSCP::Service;
use iMSCP::SystemGroup;
use iMSCP::SystemUser;
use iMSCP::TemplateParser qw/ process replaceBloc /;
use iMSCP::Umask;
use List::MoreUtils 'uniq';
use Servers::cron;
use Servers::mta;
use Servers::sqld;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP SpamAssassin plugin.

=head1 PUBLIC METHODS

=over 4

=item update( $fromVersion )

 Update tasks

 Param $string $fromVersion Version from which plugin is being updated
 Return int 0 on success, other on failure

=cut

sub update
{
    my ( undef, $fromVersion ) = @_;

    $fromVersion = version->parse( $fromVersion );

    return 0 unless $fromVersion < version->parse( '2.1.0' );

    if ( getpwnam( 'debian-spamd' ) ) {
        # sa-compile package post-installation tasks fail if the `debian-spamd'
        # user shell is not a valid login shell
        my $rs = execute(
            [ '/usr/sbin/usermod', '-s', '/bin/sh', 'debian-spamd' ],
            \my $stdout,
            \my $stderr
        );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
    }

    return 0 unless $fromVersion < version->parse( '2.0.0' );

    if ( -f '/etc/spamassassin/00_imscp.pre' ) {
        my $rs = iMSCP::File->new(
            filename => '/etc/spamassassin/00_imscp.pre'
        )->delFile();
        return $rs if $rs;
    }

    0;
}

=item enable( )

 Activation tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
    my ( $self ) = @_;

    unless ( ( defined $::execmode && $::execmode eq 'setup' )
        || !grep ( $_ eq $self->{'action'}, 'install', 'update' )
    ) {
        my $rs = $self->_installDistributionPackages();
        return $rs if $rs;
    }

    my $rs = $self->_createSpamdUser();
    $rs ||= $self->_createSaSqlUser();
    $rs ||= $self->_configureSa( 'configure' );
    $rs ||= $self->_installSaPlugins( 'install' );
    $rs ||= $self->_setupSaPlugins();
    $rs ||= $self->_configureHeinleinRuleset( 'configure' );
    $rs ||= $self->_configureSpamassMilter( 'configure' );
    $rs ||= $self->_configurePostfix( 'configure' );
    return $rs if $rs;

    my $serviceTasksSub = sub {
        local $@;
        eval {
            my $serviceMngr = iMSCP::Service->getInstance();
            for my $service( qw/ spamassassin spamass-milter / ) {
                $serviceMngr->enable( $service );
                $serviceMngr->restart( $service );
            }
        };
        if ( $@ ) {
            error( $@ );
            return 1;
        }
        0;
    };

    if ( defined $::execmode && $::execmode eq 'setup' ) {
        $rs = $self->{'eventManager'}->register(
            'beforeSetupRestartServices',
            sub {
                unshift @{ $_[0] }, [ $serviceTasksSub, 'SpamAssassin' ];
                0;
            }
        );
        return $rs;
    }

    $serviceTasksSub->();
}

=item disable( )

 Deactivation tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
    my ( $self ) = @_;

    local $@;

    my $rs = eval {
        for my $cronjob( qw/
            BayesSaLearn CleanAwlDb CleanBayesDb DiscoverRazor
        / ) {
            my $rs = $self->_unregisterCronjob( $cronjob );
            return $rs if $rs;
        }

        my $rs = $self->_configurePostfix( 'deconfigure' );
        $rs ||= $self->_configureSpamassMilter( 'deconfigure' );
        $rs ||= $self->_configureHeinleinRuleset( 'deconfigure' );
        $rs ||= $self->_installSaPlugins( 'uninstall' );
        $rs ||= $self->_configureSa( 'deconfigure' );
        return $rs if $rs;

        Servers::sqld->factory()->dropUser(
            'sa_user', $::imscpConfig{'DATABASE_USER_HOST'}
        );

        return 0 unless $self->{'action'} eq 'disable';

        my $serviceMngr = iMSCP::Service->getInstance();
        for my $service ( qw/ spamass-milter spamassassin / ) {
            $serviceMngr->stop( $service );
            $serviceMngr->disable( $service );
        }
    };
    if ( $@ ) {
        error( $@ );
        $rs = 1;
    }

    $rs;
}

=item discoverRazor( )

 Create the Razor server list files

 Return int 0 on success, other on failure

=cut

sub discoverRazor
{
    my ( $self ) = @_;

    my $rs = execute(
        [
            '/bin/su',
            '-', $self->{'config'}->{'spamd'}->{'user'},
            '-c', '/usr/bin/razor-admin -discover'
        ],
        \my $stdout,
        \my $stderr
    );
    debug( $stdout ) if length $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    $rs;
}

=item cleanAwlDb( )

 Clean the SpamAssassin (AWL) database

 Return int 0 on success, other on failure

=cut

sub cleanAwlDb
{
    my $dbh = iMSCP::Database->factory();
    my $oldDb;

    local $@;
    eval {
        $oldDb = $dbh->useDatabase( "$::imscpConfig{'DATABASE_NAME'}_spamassassin" );
        $dbh->getRawDb()->do(
            '
                DELETE FROM `awl`
                WHERE (
                    `count` = 1 AND `last_hit` < DATE_SUB( NOW(), INTERVAL 1 WEEK )
                )
                OR (
                    last_hit` < DATE_SUB( NOW(), INTERVAL 1 MONTH )
                )
            '
        );
        $dbh->useDatabase( $oldDb ) if $oldDb;
    };
    if ( $@ ) {
        $dbh->useDatabase( $oldDb ) if $oldDb;
        error( $@ );
        return 1;
    }

    0;
}

=item cleanBayesDb( )

 Expire old tokens from the bayes database

 Return int 0 on success, other on failure

=cut

sub cleanBayesDb
{
    my $rs = execute(
        [ '/usr/bin/sa-learn', '--force-expire' ],
        \my $stdout,
        \my $stderr
    );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    $rs;
}

=item bayesSaLearn( )

 Train SpamAssassin's Bayesian classifier with spam/ham

 Return int 0 on success, other on failure

=cut

sub bayesSaLearn
{
    my $saLearnDir = "$::imscpConfig{'GUI_ROOT_DIR'}/plugins/SpamAssassin/sa-learn";

    for my $dir ( <$saLearnDir/*> ) {
        next unless my ( $username, $learningMode ) = /^\Q$saLearnDir\E\/(.*)__(spam|ham)__.*/;

        my $rs = execute(
            [
                '/usr/bin/sa-learn',
                '--no-sync',
                "--$learningMode",
                '-u', $username,
                $dir
            ],
            \my $stdout,
            \my $stderr
        );
        debug( $stdout ) if length $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
    }

    # Synchronize the database and the journal once per training session
    my $rs = execute(
        [ '/usr/bin/sa-learn', '--sync' ], \my $stdout, \my $stderr
    );
    debug( $stdout ) if length $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    $rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize plugin

 Return Plugin::SpamAssassin, die on failure

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'FORCE_RETVAL'} = 'yes';
    $self->{'_panel_user'} = $::imscpConfig{'SYSTEM_USER_PREFIX'}
        . $::imscpConfig{'SYSTEM_USER_MIN_UID'};
    $self->{'_panel_group'} = getgrgid( ( getpwnam(
        $self->{'_panel_user'} )
    )[3] ) or die( "Couldn't find panel unix user group" );
    $self->{'config'}->{'spamd'}->{'options'} = process(
        {
            SPAMD_USER    => $self->{'config'}->{'spamd'}->{'user'},
            SPAMD_GROUP   => $self->{'config'}->{'spamd'}->{'group'},
            SPAMD_HOMEDIR => $self->{'config'}->{'spamd'}->{'homedir'}
        },
        $self->{'config'}->{'spamd'}->{'options'}
    );

    $self;
}

=item _installDistributionPackages( )
 
 Install required distribution packages

 Return int 0 on success, other on failure

=cut

sub _installDistributionPackages
{
    local $ENV{'DEBIAN_FRONTEND'} = 'noninteractive';

    my $rs = execute( [ 'apt-get', 'update' ], \my $stdout, \my $stderr );
    debug( $stdout ) if length $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs;

    $rs = execute(
        [
            '/usr/bin/apt-get',
            '-o', 'DPkg::Options::=--force-confold',
            '-o', 'DPkg::Options::=--force-confdef',
            '-o', 'DPkg::Options::=--force-confmiss',
            '--assume-yes',
            '--auto-remove',
            '--no-install-recommends',
            '--purge',
            '--quiet',
            'install', 'sa-compile', 'spamassassin', 'spamass-milter',
            'libnet-ident-perl', 'libmail-dkim-perl', 'libmail-spf-perl',
            'libencode-detect-perl', 'pyzor', 'razor', 'spamc',
            'libnet-patricia-perl', 'libgeo-ip-perl'
        ],
        \$stdout,
        \$stderr
    );
    debug( $stdout ) if length $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    $rs;
}

=item _createSpamdUser( )

 Create/Update spamd unix user

 Return int 0 on success, other on failure

=cut

sub _createSpamdUser
{
    my ( $self ) = @_;

    local $@;
    my $rs = eval {
        my $rs = iMSCP::SystemGroup->getInstance()->addSystemGroup(
            $self->{'config'}->{'spamd'}->{'group'}, TRUE
        );
        $rs ||= iMSCP::SystemUser->new( {
            username => $self->{'config'}->{'spamd'}->{'user'},
            group    => $self->{'config'}->{'spamd'}->{'group'},
            system   => TRUE,
            comment  => '',
            home     => $self->{'config'}->{'spamd'}->{'homedir'},
            shell    => '/bin/sh'
        } )->addSystemUser();
        return $rs if $rs;

        iMSCP::Dir->new(
            dirname => $self->{'config'}->{'spamd'}->{'homedir'}
        )->make(
            user  => $self->{'config'}->{'spamd'}->{'user'},
            group => $self->{'config'}->{'spamd'}->{'group'}
        );

        iMSCP::Dir->new(
            dirname => "$self->{'config'}->{'spamd'}->{'homedir'}/sa-update-keys"
        )->make(
            user  => $self->{'config'}->{'spamd'}->{'user'},
            group => $self->{'config'}->{'spamd'}->{'group'},
            mode  => 0700
        );

        my ( $stderr, $stdout );
        execute(
            [
                '/bin/su',
                '-', $self->{'config'}->{'spamd'}->{'user'},
                '-c', "/usr/bin/sa-update --gpghomedir"
                    ." $self->{'config'}->{'spamd'}->{'homedir'}/sa-update-keys"
                    ." --import /usr/share/spamassassin/GPG.KEY"
            ],
            \$stdout, \$stderr,
        ) == 0 or die( $stderr || 'Unknown error' );
        debug( $stdout ) if length $stdout;

        setRights( $self->{'config'}->{'spamd'}->{'homedir'}, {
            user      => $self->{'config'}->{'spamd'}->{'user'},
            group     => $self->{'config'}->{'spamd'}->{'group'},
            recursive => TRUE
        } );
    };
    if ( $@ ) {
        error( $@ );
        $rs = 1;
    }
    $rs;
}

=item _setupPyzor( )

 Setup Pyzor service

 Return int 0 on success, other on failure

=cut

sub _setupPyzor
{
    my ( $self ) = @_;

    my $rs = execute(
        [
            '/bin/su',
            '-', $self->{'config'}->{'spamd'}->{'user'},
            '-c', '/usr/bin/pyzor discover'
        ],
        \my $stdout,
        \my $stderr
    );
    debug( $stdout ) if length $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    $rs;
}

=item _setupRazor( )

 Setup Razor service

 Return int 0 on success, other on failure

=cut

sub _setupRazor
{
    my ( $self ) = @_;

    return 0 if -d "$self->{'config'}->{'spamd'}->{'homedir'}/.razor";

    for my $action ( qw/ create register / ) {
        my $rs = execute(
            [
                '/bin/su',
                '-', $self->{'config'}->{'spamd'}->{'user'},
                '-c', '/usr/bin/razor-admin',
                "-$action"
            ],
            \my $stdout,
            \my $stderr
        );
        debug( $stdout ) if length $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
    }

    0;
}

=item _configureHeinleinRuleset( [ $action = 'deconfigure' ] )

 Configure/Deconfigure Heinlein SpamAssassin ruleset

 Param string $action Action to perform (configure|deconfigure)
 Return int 0 on success, other on failure

=cut

sub _configureHeinleinRuleset
{
    my ( $self, $action ) = @_;
    $action //= 'deconfigure';

    if ( $action eq 'configure'
        && $self->{'config'}->{'spamassassin'}->{'heinlein_support_ruleset'}->{'enabled'}
    ) {
        # Create an hourly cronjob from the original SpamAssassin cronjob
        my $file = iMSCP::File->new(
            filename => '/etc/cron.daily/spamassassin'
        );
        return 1 unless defined( my $fileC = $file->getAsRef());

        my $sleepTimer = $self->{'config'}->{'spamassassin'}->{'heinlein_support_ruleset'}->{'sleep_timer'};
        my $channel = $self->{'config'}->{'spamassassin'}->{'heinlein_support_ruleset'}->{'channel'};

        # Change the sleep timer to 600 seconds on all versions
        ${ $fileC } =~ s/3600/$sleepTimer/g;
        # Change the sa-update channel on Ubuntu Precise
        ${ $fileC } =~ s/^(sa-update)$/$1 --nogpg --channel $channel/m;
        # Change the sa-update channel on Debian Wheezy / Jessie / Stretch and Ubuntu Xenial
        ${ $fileC } =~ s%--gpghomedir /var/lib/spamassassin/sa-update-keys%--nogpg --channel $channel%g;

        $file->{'filename'} = '/etc/cron.hourly/spamassassin_heinlein-support_de';
        my $rs = $file->save();
        return $rs;
    }

    my $rs = execute(
        "/bin/rm -rf /var/lib/spamassassin/*/spamassassin_heinlein-support_de*",
        \my $stdout,
        \my $stderr
    );
    debug( $stdout ) if length $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs || !-f '/etc/cron.hourly/spamassassin_heinlein-support_de';

    iMSCP::File->new(
        filename => '/etc/cron.hourly/spamassassin_heinlein-support_de'
    )->delFile();
}

=item _configureSpamassMilter( [ $action = 'deconfigure' ] )

 Configure/Deconfigure spamass-milter service

 Param string $action Action to perform (configure|deconfigure)
 Return int 0 on success, other on failure

=cut

sub _configureSpamassMilter
{
    my ( $self, $action ) = @_;
    $action //= 'deconfigure';

    my $file = iMSCP::File->new( filename => '/etc/default/spamass-milter' );
    return 1 unless defined( my $fileC = $file->getAsRef());

    if ( $action eq 'configure' ) {
        my $options = $self->{'config'}->{'spamass_milter'}->{'options'};
        my $socketPath = $self->{'config'}->{'spamass_milter'}->{'socket_path'};
        my $socketOwner = $self->{'config'}->{'spamass_milter'}->{'socket_owner'};
        my $socketMode = $self->{'config'}->{'spamass_milter'}->{'socket_mode'};
        my $spamcFlags;

        # Extract SPAMC(1) flags before adding any option for SPAMASS_MILTER(8)
        # Flags will be re-appended later
        $options =~ s/\Q$spamcFlags\E$// if ( $spamcFlags ) = $options =~ /(\s+--\s+.*)$/;
        $options .= " -i $_" for @{ $self->{'config'}->{'spamass_milter'}->{'networks'} };
        $options .= ' -I' if $self->{'config'}->{'spamass_milter'}->{'ignore_auth_sender_msgs'};
        $options .= ' -r ' . $self->{'config'}->{'spamass_milter'}->{'spam_reject_policy'};

        ${ $fileC } =~ s%^OPTIONS=.*%OPTIONS="@{ [ $options . ( $spamcFlags // '' ) ] }"%m;
        ${ $fileC } =~ s/^[#\s]*SOCKET=.*/SOCKET="$socketPath"/m;
        ${ $fileC } =~ s/^[#\s]*SOCKETOWNER=.*/SOCKETOWNER="$socketOwner"/m;
        ${ $fileC } =~ s/^[#\s]*SOCKETMODE=.*/SOCKETMODE="$socketMode"/m;
    } else {
        ${ $fileC } =~ s/^OPTIONS=.*/OPTIONS="-u spamass-milter -i 127.0.0.1"/m;
        ${ $fileC } =~ s%^SOCKET=.*%# SOCKET="/var/spool/postfix/spamass/spamass.sock"%m;
        ${ $fileC } =~ s/^SOCKETOWNER=.*/# SOCKETOWNER="postfix:postfix"/m;
        ${ $fileC } =~ s/^SOCKETMODE=.*/# SOCKETMODE="0660"/m;
    }

    $file->save();
}

=item _configurePostfix( [ $action = 'deconfigure' ] )

 Configure/Deconfigure postfix

 Param string $action Action to be performed (configure|deconfigure)
 Return int 0 on success, other on failure

=cut

sub _configurePostfix
{
    my ( $self, $action ) = @_;
    $action //= 'deconfigure';

    ( my $milterValuePrev = $self->{'config_prev'}->{'spamass_milter'}->{'socket_path'} )
        =~ s%/var/spool/postfix%unix:%;
    my $milterMacros = 'i j {daemon_name} v {if_name} _';

    my $rs = Servers::mta->factory()->postconf( (
        smtpd_milters         => {
            action => 'remove',
            values => [ qr/\Q$milterValuePrev\E/ ]
        },
        non_smtpd_milters     => {
            action => 'remove',
            values => [ qr/\Q$milterValuePrev\E/ ]
        },
        milter_connect_macros => {
            action => 'remove',
            values => [ qr/\Q$milterMacros\E/ ]
        }
    ));
    return $rs if $rs || $action ne 'configure';

    ( my $milterValue = $self->{'config'}->{'spamass_milter'}->{'socket_path'} )
        =~ s%/var/spool/postfix%unix:%;

    Servers::mta->factory()->postconf( (
        milter_default_action => {
            action => 'replace',
            values => [ 'tempfail' ]
        },
        smtpd_milters         => {
            action => 'add',
            values => [ $milterValue ],
            # Make sure that SpamAssassin filtering is processed first
            before => qr/.*/
        },
        non_smtpd_milters     => {
            action => 'add',
            values => [ $milterValue ],
            # Make sure that SpamAssassin filtering is processed first
            before => qr/.*/
        },
        milter_connect_macros => {
            action => 'replace',
            values => [ $milterMacros ]
        }
    ));
}

=item _registerCronjob( $cronjobId )

 Register the given cronjob

 Param string $cronjobId Cronjob unique identifier
 Return int 0 on success, other on failure

=cut

sub _registerCronjob
{
    my ( $self, $cronjobId ) = @_;

    my $cronjobFilePath = "$::imscpConfig{'PLUGINS_DIR'}/SpamAssassin/cronjobs/$cronjobId.pl";

    if ( $cronjobId eq 'bayes_sa_learn' ) {
        my $bayesConfig = $self->{'config'}->{'spamassassin'}->{'Bayes'};

        Servers::cron->factory()->addTask( {
            TASKID  => 'Plugin::SpamAssassin::BayesSaLearn',
            MINUTE  => $bayesConfig->{'cronjob_sa_learn'}->{'minute'},
            HOUR    => $bayesConfig->{'cronjob_sa_learn'}->{'hour'},
            DAY     => $bayesConfig->{'cronjob_sa_learn'}->{'day'},
            MONTH   => $bayesConfig->{'cronjob_sa_learn'}->{'month'},
            DWEEK   => $bayesConfig->{'cronjob_sa_learn'}->{'dweek'},
            COMMAND => "nice -n 15 ionice -c2 -n5 perl $cronjobFilePath >/dev/null 2>&1"
        } );
        return 0;
    }

    if ( $cronjobId eq 'clean_bayes_db' ) {
        my $bayesConfig = $self->{'config'}->{'spamassassin'}->{'Bayes'};

        Servers::cron->factory()->addTask( {
            TASKID  => 'Plugin::SpamAssassin::CleanBayesDb',
            MINUTE  => $bayesConfig->{'cronjob_clean_db'}->{'minute'},
            HOUR    => $bayesConfig->{'cronjob_clean_db'}->{'hour'},
            DAY     => $bayesConfig->{'cronjob_clean_db'}->{'day'},
            MONTH   => $bayesConfig->{'cronjob_clean_db'}->{'month'},
            DWEEK   => $bayesConfig->{'cronjob_clean_db'}->{'dweek'},
            COMMAND => "nice -n 15 ionice -c2 -n5 perl $cronjobFilePath >/dev/null 2>&1"
        } );
        return 0;
    }

    if ( $cronjobId eq 'clean_awl_db' ) {
        my $awlConfig = $self->{'config'}->{'spamassassin'};

        Servers::cron->factory()->addTask( {
            TASKID  => 'Plugin::SpamAssassin::CleanAwlDb',
            MINUTE  => $awlConfig->{'cronjob_clean_db'}->{'minute'},
            HOUR    => $awlConfig->{'cronjob_clean_db'}->{'hour'},
            DAY     => $awlConfig->{'cronjob_clean_db'}->{'day'},
            MONTH   => $awlConfig->{'cronjob_clean_db'}->{'month'},
            DWEEK   => $awlConfig->{'cronjob_clean_db'}->{'dweek'},
            COMMAND => "nice -n 15 ionice -c2 -n5 perl $cronjobFilePath >/dev/null 2>&1"
        } );
        return 0;
    }

    return 0 unless $cronjobId eq 'discover_razor';

    Servers::cron->factory()->addTask( {
        TASKID  => 'Plugin::SpamAssassin::DiscoverRazor',
        MINUTE  => '@weekly',
        COMMAND => "nice -n 15 ionice -c2 -n5 perl $cronjobFilePath >/dev/null 2>&1"
    } );
}

=item _unregisterCronjob( $cronjobId )

 Unregister the given cronjob

 Param string $cronjobId Cronjob unique identifier
 Return int 0 on success, other on failure

=cut

sub _unregisterCronjob
{
    my ( undef, $cronjobId ) = @_;

    Servers::cron->factory()->deleteTask( {
        TASKID => 'Plugin::SpamAssassin::' . $cronjobId
    } );
}

=item _configureSa( [ $action = 'deconfigure ] )

 Configure/Deconfigure SpamAssassin

 Param string $action Action to be performed (configure|deconfigure)
 Return int 0 on success, other on failure

=cut

sub _configureSa
{
    my ( $self, $action ) = @_;
    $action //= 'deconfigure';

    if ( $action eq 'configure' ) {
        my $file = iMSCP::File->new(
            filename => "$::imscpConfig{'PLUGINS_DIR'}/SpamAssassin/config-templates/spamassassin/00_imscp.cf"
        );
        return 1 unless defined( my $fileC = $file->getAsRef());

        my @discardedPrefs;
        # Discard bayes preferences if the SA Bayes plugin is disabled
        push @discardedPrefs, 'use_bayes', 'bayes_auto_learn', 'use_bayes_rules', 'bayes_auto_learn_threshold_nonspam',
            'bayes_auto_learn_threshold_spam', unless $self->{'config'}->{'spamassassin'}->{'Bayes'}->{'enabled'};
        # Discard DCC preference if the SA DCC plugin is disabled
        push @discardedPrefs, 'use_dcc' unless $self->{'config'}->{'spamassassin'}->{'DCC'}->{'enabled'};
        # Discard Pyzor preference if the SA Pyzor plugin is disabled
        push @discardedPrefs, 'use_pyzor' unless $self->{'config'}->{'spamassassin'}->{'Pyzor'}->{'enabled'};
        # Discard Razor2 preference if the SA Razor2 plugin is disabled
        push @discardedPrefs, 'use_razor2' unless $self->{'config'}->{'spamassassin'}->{'Razor2'}->{'enabled'};
        # Discard skip_rbl_checks preference if SA RBL checks are disabled
        push @discardedPrefs, 'skip_rbl_checks' unless $self->{'config'}->{'spamassassin'}->{'rbl_checks'}->{'enabled'};
        # Discard report_safe preference if SPAM messages are always rejected
        push @discardedPrefs, 'report_safe' if $self->{'config'}->{'spamass_milter'}->{'spam_reject_policy'} == -1;
        # Discard TextCat preferences if the SA TextCat plugin is disabled
        push @discardedPrefs, 'ok_languages' unless $self->{'config'}->{'spamassassin'}->{'TextCat'}->{'enabled'};

        ${ $fileC } = process(
            {
                DATABASE_HOST         => $::imscpConfig{'DATABASE_HOST'},
                DATABASE_PORT         => $::imscpConfig{'DATABASE_PORT'},
                SA_DATABASE_NAME      => "$::imscpConfig{'DATABASE_NAME'}_spamassassin",
                SA_DATABASE_USER      => 'sa_user',
                SA_DATABASE_PASSWORD  => $self->{'_sa_db_passwd'},
                DISCARDED_PREFERENCES => @discardedPrefs ? " AND preference NOT IN(@{ [ join ', ', map qq{'$_'}, @discardedPrefs ] })" : ''
            },
            ${ $fileC }
        );

        if ( $self->{'config'}->{'spamassassin'}->{'Bayes'}->{'site_wide'} ) {
            ${ $fileC } =~ s/^[#\s](bayes_(?:auto_expire|sql_override_username))/$1/gm;
        } else {
            ${ $fileC } =~ s/^(bayes_(?:auto_expire|sql_override_username))/#$1/gm;
        }

        local $UMASK = 027;
        $file->{'filename'} = '/etc/spamassassin/00_imscp.cf';

        my $rs = $file->save();
        $rs ||= $file->owner(
            $::imscpConfig{'ROOT_USER'},
            $self->{'config'}->{'spamd'}->{'group'}
        );
        $rs ||= $file->mode( 0640 );
        return $rs if $rs;

        while ( my ( $plg, $data ) = each %{ $self->{'config'}->{'spamassassin'} } ) {
            next unless defined $data->{'config_file'};
            $rs = $self->_enableSaPlugin(
                $plg, $data->{'config_file'},
                $data->{'enabled'} ? 'enable' : 'disable'
            );
            return $rs if $rs;
        }
    } elsif ( -f '/etc/spamassassin/00_imscp.cf' ) {
        my $rs = iMSCP::File->new(
            filename => '/etc/spamassassin/00_imscp.cf'
        )->delFile();
        return $rs if $rs;
    }

    my $file = iMSCP::File->new( filename => '/etc/default/spamassassin' );
    return 1 unless defined( my $fileC = $file->getAsRef());

    if ( $action eq 'configure' ) {
        ${ $fileC } =~ s/^ENABLED=.*/ENABLED=1/gm;
        ${ $fileC } =~ s/^OPTIONS=.*/OPTIONS="$self->{'config'}->{'spamd'}->{'options'}"/gm;
        ${ $fileC } =~ s/^CRON=.*/CRON=1/gm;
    } else {
        ${ $fileC } =~ s/^ENABLED=.*/ENABLED=0/gm;
        ${ $fileC } =~ s/^OPTIONS=.*/OPTIONS="--create-prefs --max-children 5 --helper-home-dir"/gm;
        ${ $fileC } =~ s/^CRON=.*/CRON=0/gm;
    }

    $file->save();
}

=item _enableSaPlugin( $plugin, $conffile, $action )

 Enable or disable the given SpamAssassin plugin
 
 Generally speaking, SpamAssassin plugins are loaded in one file only. We
 support multiple files to handle case where there would be different
 installation between various supported distributions.

 Param string $plugin Plugin name
 Param string $conffile SpamAssassin Target configuration file
 Param string $action Action to perform (enable/disable)
 Return int 0 on success, other on failure

=cut

sub _enableSaPlugin
{
    my ( undef, $plugin, $conffile, $action ) = @_;

    my $file = iMSCP::File->new( filename => $conffile );
    return 1 unless defined( my $fileC = $file->getAsRef());

    if ( $action eq 'disable' ) {
        ${ $fileC } =~ s/^(loadplugin Mail::SpamAssassin::Plugin::$plugin)/#$1/m;
        return $file->save();
    }

    if ( !( ${ $fileC } =~ s/^[#\s]*(loadplugin Mail::SpamAssassin::Plugin::$plugin)/$1/m ) ) {
        # Plugin line not in file. We add it manually
        ${ $fileC } .= <<"EOF";

loadplugin Mail::SpamAssassin::Plugin::${plugin}
EOF
        return $file->save();
    }

    0;
}

=item _setupSaPlugins( )

 Setup SpamAssassin plugins

 Return int 0 on success, other on failure

=cut

sub _setupSaPlugins
{
    my ( $self ) = @_;

    my $c = $self->{'config'}->{'spamassassin'};

    my $rs = $self->_setGlobalSaPref(
        [ 'use_bayes', 'use_bayes_rules', 'bayes_auto_learn' ],
        $c->{'Bayes'}->{'enforced'} ? 1 : 0,
        $c->{'Bayes'}->{'enforced'}
    );

    $rs = $self->_setGlobalSaPref(
        [ 'use_dcc' ],
        $c->{'DCC'}->{'enforced'} ? 1 : 0,
        $c->{'DCC'}->{'enforced'}
    );
    $rs = $self->_setGlobalSaPref(
        [ 'use_pyzor' ],
        $c->{'Pyzor'}->{'enforced'} ? 1 : 0,
        $c->{'Pyzor'}->{'enforced'}
    );
    $rs = $self->_setGlobalSaPref(
        [ 'use_bayes' ],
        $c->{'Razor2'}->{'enforced'} ? 1 : 0,
        $c->{'Razor2'}->{'enforced'}
    );
    $rs = $self->_setGlobalSaPref(
        [ 'skip_rbl_checks' ],
        $c->{'rbl_checks'}->{'enforced'} ? 0 : 1,
        $c->{'rbl_checks'}->{'enforced'}
    );

    if ( $self->{'config'}->{'spamassassin'}->{'Bayes'}->{'site_wide'} ) {
        # If the SA Bayes plugin operates at site-wide, we mus prevent users to
        # act on threshold-based auto-learning discriminator for SpamAssassin's
        # Bayes subsystem.
        $rs ||= $self->_setGlobalSaPref( [ 'bayes_auto_learn_threshold_nonspam' ], '0.1', 1 );
        $rs ||= $self->_setGlobalSaPref( [ 'bayes_auto_learn_threshold_spam' ], '12.0', 1 );
        return $rs if $rs;
    }

    $rs = $c->{'AWL'}->{'enabled'}
        ? $self->_registerCronjob( 'clean_awl_db' )
        : $self->cleanAwlDb();

    if ( !$rs && $c->{'Bayes'}->{'enabled'} && $c->{'Bayes'}->{'site_wide'} ) {
        $rs = $self->_registerCronjob( 'clean_bayes_db' )
    }

    $rs ||= $self->_setupPyzor() if $c->{'Pyzor'}->{'enabled'};
    return $rs if $rs || !$c->{'Razor2'}->{'enabled'};

    $rs = $self->_setupRazor();
    $rs ||= $self->_registerCronjob( 'discover_razor' );
}

=item _configureRoundcubePlugins( )

 Configure Roundcube plugins

 Return int 0 on success, other on failure

=cut

sub _configureRoundcubePlugins
{
    my ( $self ) = @_;

    for ( qw/ markasjunk2 / ) {
        next unless $self->{'config'}->{'roundcube'}->{$_}->{'enabled'};

        my $file = iMSCP::File->new( filename => "$::imscpConfig{'PLUGINS_DIR'}/SpamAssassin/config-templates/$_/config.inc.php" );
        my $fileContent = $file->get();
        unless ( defined $fileContent ) {
            error( sprintf( "Couldn't read %s file", $file->{'filename'} ));
            return 1;
        }

        $fileContent =~ s/\Q{GUI_ROOT_DIR}\E/$::imscpConfig{'GUI_ROOT_DIR'}/g;

        local $UMASK = 227;
        $file->{'filename'} = "$::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/plugins/$_/config.inc.php";

        $file->set( $fileContent );
        my $rs = $file->save();
        $rs ||= $file->owner( $self->{'_panel_user'}, $self->{'_panel_group'} );
        $rs ||= $file->mode( 0440 );
        return $rs if $rs;

        if ( $_ eq 'markasjunk2' ) {
            if ( $self->{'config'}->{'roundcube'}->{'markasjunk2'}->{'enabled'} && $self->{'config'}->{'spamassassin'}->{'Bayes'}->{'enabled'} ) {
                $rs = $self->_registerCronjob( 'bayes_sa_learn' );
                return $rs if $rs;
            } else {
                $rs = $self->bayesSaLearn();
                return $rs if $rs;
            }
        }
    }

    0;
}

=item _setGlobalSaPref( \@preferences, $value [, $enforce = FALSE ] )

 Update the given global SA preferences and enforce them if requested

 Param arrayref \@preference Global List of SA preference names
 Param string $value Global SA preference value
 Param bool $enforce Whether or not the Global SA preference must be enforced
 Return int 0 on success, other on failure

=cut

sub _setGlobalSaPref
{
    my ( undef, $preferences, $value, $enforce ) = @_;

    my $dbh = iMSCP::Database->factory();
    my $dbi = $dbh->getRawDb();
    my $oldDb;

    local $@;
    eval {
        $oldDb = $dbh->useDatabase( "$::imscpConfig{'DATABASE_NAME'}_spamassassin" );

        for my $preference ( @{ $preferences } ) {
            $dbi->do(
                '
                    DELETE FROM `userpref`
                    WHERE `username` <> ?
                    AND `preference` = ?
                ',
                undef,
                '$GLOBAL',
                $preference
            ) if $enforce;
            $dbi->do(
                '
                    UPDATE `userpref`
                    SET `value` = ?
                    WHERE `username` = ?
                    AND `preference` = ?
                ',
                undef,
                $value,
                '$GLOBAL',
                $preference
            );
        }

        $dbh->useDatabase( $oldDb ) if $oldDb;
    };
    if ( $@ ) {
        $dbh->useDatabase( $oldDb ) if $oldDb;
        error( $@ );
        return 1;
    }

    0;
}

=item _createSaSqlUser( )

 Create SpamAssassin SQL user

 Return int 0 on success, other on failure

=cut

sub _createSaSqlUser
{
    my ( $self ) = @_;

    my $dbh = iMSCP::Database->factory();
    my $dbi = $dbh->getRawDb();

    eval {
        $self->{'_sa_db_passwd'} = randomStr( 16, iMSCP::Crypt::ALNUM );
        my $dbName = $::imscpConfig{'DATABASE_NAME'} . '_spamassassin';
        my $qrs = $dbh->doQuery( '1', 'SHOW DATABASES LIKE ?', $dbName );
        %{ $qrs } or die( sprintf(
            "Couldn't find the `%s' SQL database.", $dbName
        ));
        Servers::sqld->factory()->dropUser(
            'sa_user', $::imscpConfig{'DATABASE_USER_HOST'}
        );
        Servers::sqld->factory()->createUser(
            'sa_user',
            $::imscpConfig{'DATABASE_USER_HOST'},
            $self->{'_sa_db_passwd'}
        );
        my $quotedDbName = $dbh->quoteIdentifier( $dbName );
        $dbi->do(
            "GRANT SELECT, INSERT, UPDATE, DELETE ON $quotedDbName.* TO ?\@?",
            undef,
            'sa_user',
            $::imscpConfig{'DATABASE_USER_HOST'}
        );
    };
    if ( $@ ) {
        error( sprintf( "Couldn't drop SpamAssassin SQL user: %s", $@ ));
        return 1;
    }

    0;
}

=item _installSaPlugins( [ $action = 'uninstall' ] )

 Install/Uninstall the given SpamAssassin plugin

 Param string $action Action to perform (install|uninstall)
 Return int 0 on success, other on failure

=cut

sub _installSaPlugins
{
    my ( $self, $action ) = @_;
    $action //= 'uninstall';

    for my $plugin ( qw/ DecodeShortURLs iXhash2 / ) {
        for my $ext ( qw/ cf pm / ) {
            if ( $action eq 'install' && $self->{'config'}->{'spamassassin'}->{$plugin}->{'enabled'} ) {
                my $file = iMSCP::File->new(
                    filename => "$::imscpConfig{'PLUGINS_DIR'}/SpamAssassin/spamassassin-plugins/$plugin/$plugin.$ext"
                );
                my $rs = $file->copyFile( '/etc/spamassassin', { preserve => 'no' } );
                return $rs if $rs;

                $file->{'filename'} = "/etc/spamassassin/$plugin.$ext";
                $rs = $file->owner(
                    $::imscpConfig{'ROOT_USER'}, $::imscpConfig{'ROOT_GROUP'}
                );
                $rs ||= $file->mode( 0644 );
                return $rs if $rs;
                next;
            }

            next unless -f "/etc/spamassassin/$plugin.$ext";
            my $rs = iMSCP::File->new(
                filename => "/etc/spamassassin/$plugin.$ext"
            )->delFile();
            return $rs if $rs;
        }
    }

    0;
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>
 Sascha Bay <info@space2place.de>
 Rene Schuster <mail@reneschuster.de>

=cut

1;
__END__
