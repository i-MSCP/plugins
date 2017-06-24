=head1 NAME

 Plugin::SpamAssassin

=cut

# i-MSCP SpamAssassin plugin
# Copyright (C) 2015-2017 Laurent Declercq <l.declercq@nuxwin.com>
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
use autouse 'File::Basename' => qw / basename /;
use autouse 'iMSCP::Crypt' => qw/ randomStr /;
use autouse 'iMSCP::Debug' => qw/ debug error /;
use autouse 'iMSCP::Execute' => qw/ execute /;
use autouse 'iMSCP::Rights' => qw/ setRights /;
use autouse 'iMSCP::TemplateParser' => qw/ process replaceBloc /;
use autouse 'List::MoreUtils' => qw/ uniq /;
use Class::Autouse qw/ :nostat
    iMSCP::Database iMSCP::Dir iMSCP::File iMSCP::Service iMSCP::SystemUser Servers::cron Servers::mta
    Servers::sqld /;
use iMSCP::Umask;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP SpamAssassin plugin.

=head1 PUBLIC METHODS

=over 4

=item update( $fromVersion )

 Perform update tasks

 Param $string $fromVersion Version from which plugin is being updated
 Return int 0 on success, other on failure

=cut

sub update
{
    my (undef, $fromVersion) = @_;

    return 0 unless version->parse( $fromVersion ) >= version->parse('2.0.0' )
        && -f '/etc/spamassassin/00_imscp.pre';

    # Remove obsolete file
    iMSCP::File->new( filename => '/etc/spamassassin/00_imscp.pre' )->delFile( );
}

=item enable( )

 Perform enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
    my ($self) = @_;

    unless (defined $main::execmode && $main::execmode eq 'setup'
        || !grep( $_ eq $self->{'action'}, 'install', 'update' )
    ) {
        my $rs = $self->_installDistributionPackages( );
        return $rs if $rs;
    }

    my $rs = $self->_createSaUnixUser( );
    $rs ||= $self->_createSaSqlUser( );
    $rs ||= $self->_configureSa( 'configure' );
    $rs ||= $self->_installSaPlugins( 'install');
    $rs ||= $self->_setupSaPlugins( );
    $rs ||= $self->_configureHeinleinRuleset( 'configure' );
    $rs ||= $self->_configureSpamassMilter( 'configure' );
    $rs ||= $self->_configurePostfix( 'configure' );
    return $rs if $rs;

    my $serviceTasksSub = sub {
        local $@;
        eval {
            my $serviceMngr = iMSCP::Service->getInstance( );
            for(qw/ spamassassin spamass-milter /) {
                $serviceMngr->enable( $_ );
                $serviceMngr->restart( $_ );
            }
        };
        if ($@) {
            error( $@ );
            return 1;
        }
        0;
    };

    if (defined $main::execmode && $main::execmode eq 'setup') {
        $rs = $self->{'eventManager'}->register(
            'beforeSetupRestartServices',
            sub {
                unshift @{$_[0]}, [ $serviceTasksSub, 'SpamAssassin services' ];
                0;
            },
        );
        return $rs if $rs;
    } else {
        $rs = $serviceTasksSub->( );
        return $rs if $rs;
        undef $serviceTasksSub;
    }

    if (grep( lc $_ eq 'roundcube', split ',', $main::imscpConfig{'WEBMAIL_PACKAGES'} )) {
        $rs = $self->_installRoundcubePlugins( 'install' );
        $rs ||= $self->_configureRoundcubePlugins( );
        $rs ||= $self->_enableRoundcubePlugins( 'enable' );
        return $rs if $rs;

        unless (defined $main::execmode && $main::execmode eq 'setup') {
            local $@;
            eval { iMSCP::Service->getInstance( )->reload( 'imscp_panel' ); };
            if ($@) {
                error( $@ );
                return 1;
            }
        }
    }

    0;
}

=item disable( )

 Perform disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
    my ($self) = @_;

    for(qw/ BayesSaLearn CleanAwlDb CleanBayesDb DiscoverRazor /) {
        my $rs = $self->_unregisterCronjob( $_);
        return $rs if $rs;
    }

    if (grep( lc $_ eq 'roundcube', split ',', $main::imscpConfig{'WEBMAIL_PACKAGES'} )) {
        my $rs = $self->_enableRoundcubePlugins( 'disable' );
        $rs ||= $self->_installRoundcubePlugins( 'uninstall' );
        return $rs if $rs;

        unless (defined $main::execmode && $main::execmode eq 'setup') {
            local $@;
            eval { iMSCP::Service->getInstance( )->reload( 'imscp_panel' ); };
            if ($@) {
                error( $@ );
                return 1;
            }
        }
    }

    my $rs = $self->_configurePostfix( 'deconfigure' );
    $rs ||= $self->_configureSpamassMilter( 'deconfigure' );
    $rs ||= $self->_configureHeinleinRuleset( 'deconfigure' );
    $rs ||= $self->_installSaPlugins( 'uninstall' );
    $rs ||= $self->_configureSa( 'deconfigure' );
    $rs ||= $self->_dropSaSqlUser( );
    return $rs if $rs || $self->{'action'} ne 'disable';

    local $@;
    eval {
        my $serviceMngr = iMSCP::Service->getInstance( );
        for(qw/ spamass-milter spamassassin /) {
            $serviceMngr->stop( $_ );
            $serviceMngr->disable( $_ );
        }
    };
    if ($@) {
        error( $@ );
        return 1;
    }

    0;
}

=item discoverRazor( )

 Create the Razor server list files

 Return int 0 on success, other on failure

=cut

sub discoverRazor
{
    my ($self) = @_;

    my $rs = execute(
        "su -l $self->{'_sa_user'} -s /bin/sh -c '/usr/bin/razor-admin -discover'", \ my $stdout, \ my $stderr
    );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    $rs;
}

=item cleanAwlDb( )

 Clean the SpamAssassin (AWL) database

 Return int 0 on success, other on failure

=cut

sub cleanAwlDb
{
    my $qrs = iMSCP::Database->factory( )->doQuery(
        'd',
        "
            DELETE FROM `$main::imscpConfig{'DATABASE_NAME'}_spamassassin`.`awl`
            WHERE (
                count = 1 AND last_update < DATE_SUB(NOW( ), INTERVAL 1 WEEK)
            ) OR (
                last_update < DATE_SUB(NOW( ), INTERVAL 1 MONTH)
            )
        "
    );
    unless (ref $qrs eq 'HASH') {
        error( $qrs );
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
    my $rs = execute( 'sa-learn --force-expire', \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    $rs;
}

=item bayesSaLearn( )

 Train SpamAssassin's Bayesian classifier with spam and ham reported by the users

 Return int 0 on success, other on failure

=cut

sub bayesSaLearn
{
    my $saLearnDir = "$main::imscpConfig{'GUI_ROOT_DIR'}/plugins/SpamAssassin/sa-learn";

    for (<$saLearnDir/*>) {
        my ($username, $learningMode) = /^\Q$saLearnDir\E\/(.*)__(spam|ham)__.*/ or next;
        my $rs = execute( "sa-learn --$learningMode -u $username $_", \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        $rs ||= iMSCP::File->new( filename => $_ )->delFile( ) if -f;
        return $rs if $rs;
    }

    0;
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
    my ($self) = @_;

    $self->{'FORCE_RETVAL'} = 'yes';
    ($self->{'_sa_user'}) = $self->{'config'}->{'spamd'}->{'options'} =~ /-(?:u\s+|username=)(\S*)/ or die(
        "Couldn't parse SpamAssassin username from the spamd `options' configuration parameter"
    );
    $self->{'_sa_group'} = getgrgid ( (getpwnam( $self->{'_sa_user'} ))[3] ) || $self->{'_sa_user'};
    ($self->{'_sa_homedir'}) = $self->{'config'}->{'spamd'}->{'options'} =~ /-(?:H\s+|-helper-home-dir)(\S*)/ or die(
        "Couldn't parse SpamAssassin homedir from the spamd `options' configuration parameter"
    );
    $self->{'_panel_user'} = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'};
    $self->{'_panel_group'} = getgrgid ( (getpwnam( $self->{'_panel_user'} ))[3] ) or die(
        "Couldn't find panel user group"
    );
    $self;
}

=item _installDistributionPackages( )
 
 Install required distribution packages

 Return int 0 on success, other on failure

=cut

sub _installDistributionPackages
{
    $ENV{'DEBIAN_FRONTEND'} = 'noninteractive';

    my $rs = execute( [ 'apt-get', 'update' ], \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( sprintf("Couldn't update APT index: %s", $stderr || 'Unknown error' ) ) if $rs;
    return $rs if $rs;

    $rs = execute(
        [
            'apt-get', '-o', 'DPkg::Options::=--force-confold', '-o', 'DPkg::Options::=--force-confdef',
            '-o', 'DPkg::Options::=--force-confmiss', '--assume-yes', '--auto-remove', '--no-install-recommends',
            '--purge', '--quiet', 'install', 'sa-compile', 'spamassassin', 'spamass-milter', 'libnet-ident-perl',
            'libmail-dkim-perl', 'libmail-spf-perl', 'libencode-detect-perl', 'pyzor', 'razor'
        ],
        \$stdout,
        \$stderr
    );
    debug( $stdout ) if $stdout;
    error( sprintf( "Couldn't install distribution packages: %s", $stderr || 'Unknown error' ) ) if $rs;
    $rs;
}

=item _createSaUnixUser( )

 Create/Update SpamAssassin unix user and its home directory

 Return int 0 on success, other on failure

=cut

sub _createSaUnixUser
{
    my ($self) = @_;

    my $rs = iMSCP::SystemUser->new(
        {
            username => $self->{'_sa_user'},
            group    => $self->{'_sa_group'},
            system   => 1,
            comment  => 'SpamAssassin user',
            home     => $self->{'_sa_homedir'},
            shell    => '/bin/sh'
        }
    )->addSystemUser( );
    return $rs if $rs;

    local $@;
    eval {  iMSCP::Dir->new( dirname => $self->{'_sa_homedir'} )->make( ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    setRights(
        $self->{'_sa_homedir'},
        {
            user      => $self->{'_sa_user'},
            group     => $self->{'_sa_group'},
            recursive => 1
        }
    );
}

=item _setupPyzor( )

 Setup Pyzor service

 Return int 0 on success, other on failure

=cut

sub _setupPyzor
{
    my ($self) = @_;

    my $rs = execute( "su -l $self->{'_sa_user'} -s /bin/sh -c 'pyzor discover'", \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    $rs;
}

=item _setupRazor( )

 Setup Razor service

 Return int 0 on success, other on failure

=cut

sub _setupRazor
{
    my ($self) = @_;

    return 0 if -d "$self->{'_sa_homedir'}/.razor";

    for(qw/ create register /) {
        my $rs = execute( "su -l $self->{'_sa_user'} -s /bin/sh -c 'razor-admin -$_'", \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
    }

    0;
}

=item _configureHeinleinRuleset( [ $action = 'deconfigure ] )

 Configure/Deconfigure Heinlein SpamAssassin ruleset

 Param string $action Action to perform (configure|deconfigure)
 Return int 0 on success, other on failure

=cut

sub _configureHeinleinRuleset
{
    my ($self, $action) = @_;
    $action //= 'deconfigure';

    if ($action eq 'configure' && $self->{'config'}->{'spamassassin'}->{'heinlein_support_ruleset'}->{'enabled'}) {
        # Create an hourly cronjob from the original SpamAssassin cronjob
        my $file = iMSCP::File->new( filename => '/etc/cron.daily/spamassassin' );
        my $fileContent = $file->get( );
        unless (defined $fileContent) {
            error( sprintf( "Couldn't read %s file", $file->{'filename'} ) );
            return 1;
        }

        my $sleepTimer = $self->{'config'}->{'spamassassin'}->{'heinlein_support_ruleset'}->{'sleep_timer'};
        my $channel = $self->{'config'}->{'spamassassin'}->{'heinlein_support_ruleset'}->{'channel'};

        # Change the sleep timer to 600 seconds on all versions
        $fileContent =~ s/3600/$sleepTimer/g;
        # Change the sa-update channel on Ubuntu Precise
        $fileContent =~ s/^(sa-update)$/$1 --nogpg --channel $channel/m;
        # Change the sa-update channel on Debian Wheezy / Jessie / Stretch and Ubuntu Xenial
        $fileContent =~ s%--gpghomedir /var/lib/spamassassin/sa-update-keys%--nogpg --channel $channel%g;

        $file->{'filename'} = '/etc/cron.hourly/spamassassin_heinlein-support_de';
        my $rs = $file->set( $fileContent );
        $rs ||= $file->save( );
        return $rs;
    }

    my $rs = execute( "rm -rf /var/lib/spamassassin/*/spamassassin_heinlein-support_de*", \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs || !-f '/etc/cron.hourly/spamassassin_heinlein-support_de';

    iMSCP::File->new( filename => '/etc/cron.hourly/spamassassin_heinlein-support_de' )->delFile( );
}

=item _configureSpamassMilter( [ $action = 'deconfigure' ] )

 Configure/Deconfigure spamass-milter service

 Param string $action Action to perform (configure|deconfigure)
 Return int 0 on success, other on failure

=cut

sub _configureSpamassMilter
{
    my ($self, $action) = @_;
    $action //= 'deconfigure';

    my $file = iMSCP::File->new( filename => '/etc/default/spamass-milter' );
    my $fileContent = $file->get( );
    unless (defined $fileContent) {
        error( sprintf( "Couldn't read %s file", $file->{'filename'} ) );
        return 1;
    }

    if ($action eq 'configure') {
        my $options = $self->{'config'}->{'spamass_milter'}->{'options'};
        my $socketPath = $self->{'config'}->{'spamass_milter'}->{'socket_path'};
        my $socketOwner = $self->{'config'}->{'spamass_milter'}->{'socket_owner'};
        my $socketMode = $self->{'config'}->{'spamass_milter'}->{'socket_mode'};
        my $spamcFlags;

        # Extract SPAMC(1) flags before adding any option for SPAMASS_MILTER(8)
        # Flags will be re-appended later
        $options =~ s/\s+\Q$spamcFlags\E$// if ($spamcFlags) = $options =~ /(\s+--\s+.*)$/;
        $options .= " -i $_" for @{$self->{'config'}->{'spamass_milter'}->{'networks'}};
        $options .= ' -I' if $self->{'config'}->{'spamass_milter'}->{'ignore_auth_sender_msgs'};
        $options .= ' -r '.$self->{'config'}->{'spamass_milter'}->{'spam_reject_policy'};

        $fileContent =~ s%^OPTIONS=.*%OPTIONS="@{[ $options.($spamcFlags // '') ]}"%m;
        $fileContent =~ s/^[#\s]+SOCKET=.*/SOCKET="$socketPath"/m;
        $fileContent =~ s/^[#\s]+SOCKETOWNER=.*/SOCKETOWNER="$socketOwner"/m;
        $fileContent =~ s/^[#\s]+SOCKETMODE=.*/SOCKETMODE="$socketMode"/m;
    } else {
        $fileContent =~ s/^OPTIONS=.*/OPTIONS="-u spamass-milter -i 127.0.0.1"/m;
        $fileContent =~ s%^SOCKET=.*%# SOCKET="/var/spool/postfix/spamass/spamass.sock"%m;
        $fileContent =~ s/^SOCKETOWNER=.*/# SOCKETOWNER="postfix:postfix"/m;
        $fileContent =~ s/^SOCKETMODE=.*/# SOCKETMODE="0660"/m;
    }

    $file->set( $fileContent );
    $file->save( );
}

=item _configurePostfix( [ $action = 'deconfigure' ] )

 Configure/Deconfigure postfix

 Param string $action Action to be performed (configure|deconfigure)
 Return int 0 on success, other on failure

=cut

sub _configurePostfix
{
    my ($self, $action) = @_;
    $action //= 'deconfigure';

    (my $milterValuePrev = $self->{'config_prev'}->{'spamass_milter'}->{'socket_path'}) =~ s%/var/spool/postfix%unix:%;
    my $milterMacros = 'j {daemon_name} v {if_name} _';

    my $rs = Servers::mta->factory( )->postconf(
        (
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
                values => [ qr/(?:i\s+)?\Q$milterMacros\E/ ]
            }
        )
    );
    return $rs if $rs || $action ne 'configure';

    (my $milterValue = $self->{'config'}->{'spamass_milter'}->{'socket_path'}) =~ s%/var/spool/postfix%unix:%;

    Servers::mta->factory( )->postconf(
        (
            milter_default_action => {
                action => 'replace',
                values => [ 'tempfail' ]
            },
            smtpd_milters         => {
                action => 'add',
                values => [ $milterValue ]
            },
            non_smtpd_milters     => {
                action => 'add',
                values => [ $milterValue ]
            },
            milter_connect_macros => {
                action => 'replace',
                values => [ $milterMacros ]
            }
        )
    );
}

=item _registerCronjob( $cronjobId )

 Register the given cronjob

 Param string $cronjobId Cronjob unique identifier
 Return int 0 on success, other on failure

=cut

sub _registerCronjob
{
    my ($self, $cronjobId) = @_;

    my $cronjobFilePath = "$main::imscpConfig{'PLUGINS_DIR'}/SpamAssassin/cronjobs/$cronjobId.pl";

    if ($cronjobId eq 'bayes_sa_learn') {
        Servers::cron->factory( )->addTask(
            {
                TASKID  => 'Plugin::SpamAssassin::BayesSaLearn',
                MINUTE  => $self->{'config'}->{'spamassassin'}->{'Bayes'}->{'cronjob_sa_learn'}->{'minute'},
                HOUR    => $self->{'config'}->{'spamassassin'}->{'Bayes'}->{'cronjob_sa_learn'}->{'hour'},
                DAY     => $self->{'config'}->{'spamassassin'}->{'Bayes'}->{'cronjob_sa_learn'}->{'day'},
                MONTH   => $self->{'config'}->{'spamassassin'}->{'Bayes'}->{'cronjob_sa_learn'}->{'month'},
                DWEEK   => $self->{'config'}->{'spamassassin'}->{'Bayes'}->{'cronjob_sa_learn'}->{'dweek'},
                COMMAND => "nice -n 15 ionice -c2 -n5 perl $cronjobFilePath >/dev/null 2>&1"
            }
        );
        return 0;
    }

    if ($cronjobId eq 'clean_bayes_db') {
        Servers::cron->factory( )->addTask(
            {
                TASKID  => 'Plugin::SpamAssassin::CleanBayesDb',
                MINUTE  => $self->{'config'}->{'spamassassin'}->{'Bayes'}->{'cronjob_clean_db'}->{'minute'},
                HOUR    => $self->{'config'}->{'spamassassin'}->{'Bayes'}->{'cronjob_clean_db'}->{'hour'},
                DAY     => $self->{'config'}->{'spamassassin'}->{'Bayes'}->{'cronjob_clean_db'}->{'day'},
                MONTH   => $self->{'config'}->{'spamassassin'}->{'Bayes'}->{'cronjob_clean_db'}->{'month'},
                DWEEK   => $self->{'config'}->{'spamassassin'}->{'Bayes'}->{'cronjob_clean_db'}->{'dweek'},
                COMMAND => "nice -n 15 ionice -c2 -n5 perl $cronjobFilePath >/dev/null 2>&1"
            }
        );
        return 0;
    }

    if ($cronjobId eq 'clean_awl_db') {
        Servers::cron->factory( )->addTask(
            {
                TASKID  => 'Plugin::SpamAssassin::CleanAwlDb',
                MINUTE  => $self->{'config'}->{'spamassassin'}->{'AWL'}->{'cronjob_clean_db'}->{'minute'},
                HOUR    => $self->{'config'}->{'spamassassin'}->{'AWL'}->{'cronjob_clean_db'}->{'hour'},
                DAY     => $self->{'config'}->{'spamassassin'}->{'AWL'}->{'cronjob_clean_db'}->{'day'},
                MONTH   => $self->{'config'}->{'spamassassin'}->{'AWL'}->{'cronjob_clean_db'}->{'month'},
                DWEEK   => $self->{'config'}->{'spamassassin'}->{'AWL'}->{'cronjob_clean_db'}->{'dweek'},
                COMMAND => "nice -n 15 ionice -c2 -n5 perl $cronjobFilePath >/dev/null 2>&1"
            }
        );
        return 0;
    }

    return 0 unless $cronjobId eq 'discover_razor';

    Servers::cron->factory( )->addTask(
        {
            TASKID  => 'Plugin::SpamAssassin::DiscoverRazor',
            MINUTE  => '@weekly',
            COMMAND => "nice -n 15 ionice -c2 -n5 perl $cronjobFilePath >/dev/null 2>&1"
        }
    );
}

=item _unregisterCronjob( $cronjobId )

 Unregister the given cronjob

 Param string $cronjobId Cronjob unique identifier
 Return int 0 on success, other on failure

=cut

sub _unregisterCronjob
{
    my (undef, $cronjobId) = @_;

    Servers::cron->factory( )->deleteTask( { TASKID => 'Plugin::SpamAssassin::'.$cronjobId } );
}

=item _configureSa( [ $action = 'deconfigure ] )

 Configure/Deconfigure SpamAssassin

 Param string $action Action to be performed (configure|deconfigure)
 Return int 0 on success, other on failure

=cut

sub _configureSa
{
    my ($self, $action) = @_;
    $action //= 'deconfigure';

    if ($action eq 'configure') {
        my $file = iMSCP::File->new(
            filename => "$main::imscpConfig{'PLUGINS_DIR'}/SpamAssassin/config-templates/spamassassin/00_imscp.cf"
        );
        my $fileContent = $file->get( );
        unless (defined $fileContent) {
            error( sprintf( "Couldn't read %s file", $file->{'filename'} ) );
            return 1;
        }

        $fileContent = process(
            {
                DATABASE_HOST        => $main::imscpConfig{'DATABASE_HOST'},
                DATABASE_PORT        => $main::imscpConfig{'DATABASE_PORT'},
                SA_DATABASE_NAME     => "$main::imscpConfig{'DATABASE_NAME'}_spamassassin",
                SA_DATABASE_USER     => 'sa_user',
                SA_DATABASE_PASSWORD => $self->{'_sa_db_passwd'},
                DISABLE_DCC          => $self->{'config'}->{'spamassassin'}->{'DCC'}->{'enabled'}
                    ? '' : 'AND preference NOT LIKE \'use_dcc\''
            },
            $fileContent
        );

        if ($self->{'config'}->{'spamassassin'}->{'Bayes'}->{'site_wide'}) {
            $fileContent =~ s/^[#\s]+(bayes_(?:auto_expire|sql_override_username)/$1/gm;
        } else {
            $fileContent =~ s/^(bayes_(?:auto_expire|sql_override_username)/#$1/gm;
        }

        local $UMASK = 027;
        $file->{'filename'} = "/etc/spamassassin/00_imscp.cf";

        my $rs = $file->set( $fileContent );
        $rs ||= $file->save( );
        $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'_sa_group'} );
        $rs ||= $file->mode( 0640 );
        return $rs if $rs;

        while(my ($plg, $data) = each %{$self->{'config'}->{'spamassassin'}}) {
            next unless defined $data->{'config_sections'};
            $rs = $self->_enableSaPlugin( $plg, $data->{'config_sections'}, $data->{'enabled'} ? 'enable' : 'disable' );
            return $rs if $rs;
        }
    } elsif (-f '/etc/spamassassin/00_imscp.cf') {
        my $rs = iMSCP::File->new( filename => '/etc/spamassassin/00_imscp.cf' )->delFile();
        return $rs if $rs;
    }

    my $file = iMSCP::File->new( filename => '/etc/default/spamassassin' );
    my $fileContent = $file->get( );
    unless (defined $fileContent) {
        error( sprintf( "Couldn't read %s file", $file->{'filename'} ) );
        return 1;
    }

    if ($action eq 'configure') {
        $fileContent =~ s/^ENABLED=.*/ENABLED=1/gm;
        $fileContent =~ s/^OPTIONS=.*/OPTIONS="$self->{'config'}->{'spamd'}->{'options'}"/gm;
        $fileContent =~ s/^CRON=.*/CRON=1/gm;
    } else {
        $fileContent =~ s/^ENABLED=.*/ENABLED=0/gm;
        $fileContent =~ s/^OPTIONS=.*/OPTIONS="--create-prefs --max-children 5 --helper-home-dir"/gm;
        $fileContent =~ s/^CRON=.*/CRON=0/gm;
    }

    $file->set( $fileContent );
    $file->save( );
}

=item _enableSaPlugin($plugin, $sections, $action)

 Enable/Disable the given SpamAssassin plugin in the given SpamAssassin configuration sections

 Param string $plugin Plugin name
 Param string $sections SpamAssassin configuration section(s)
 Param string $action Action to perform (enable/disable)
 Return int 0 on success, other on failure

=cut

sub _enableSaPlugin
{
    my (undef, $plugin, $sections, $action) = @_;

    for(split ',', $sections) {
        next unless -f; # Ignore missing file silently

        my $file = iMSCP::File->new( filename => $_ );
        my $fileContent = $file->get( );
        unless (defined $fileContent) {
            error( sprintf( "Couldn't read %s file", $file->{'filename'} ) );
            return 1;
        }

        $fileContent =~ s/^(loadplugin Mail::SpamAssassin::Plugin::$plugin)/#$1/m if $action eq 'disable';
        $fileContent =~ s/^[#\s]+(loadplugin Mail::SpamAssassin::Plugin::$plugin)/$1/m if $action eq 'enable';
        $file->set( $fileContent );
        my $rs = $file->save( );
        return $rs if $rs;
    }
}

=item _setupSaPlugins( )

 Setup SpamAssassin plugins

 Return int 0 on success, other on failure

=cut

sub _setupSaPlugins
{
    my ($self) = @_;

    if ($self->{'config'}->{'spamassassin'}->{'Pyzor'}->{'enabled'}) {
        my $rs = $self->_setSaUserprefs( 'use_pyzor', '1' );
        $rs ||= $self->_setupPyzor( );
        return $rs if $rs;
    } else {
        my $rs = $self->_setSaUserprefs( 'use_pyzor', '0' );
        return $rs if $rs;
    }

    if ($self->{'config'}->{'spamassassin'}->{'Razor2'}->{'enabled'}) {
        my $rs = $self->_setSaUserprefs( 'use_razor2', '1' );
        $rs ||= $self->_setupRazor( );
        $rs ||= $self->_registerCronjob( 'discover_razor' );
        return $rs if $rs;
    } else {
        my $rs = $self->_setSaUserprefs( 'use_razor2', '0' );
        return $rs if $rs;
    }

    if ($self->{'config'}->{'spamassassin'}->{'AWL'}->{'enabled'}) {
        my $rs = $self->_setSaUserprefs( 'use_auto_whitelist', '1' );
        $rs ||= $self->_registerCronjob( 'clean_awl_db' );
        return $rs if $rs;
    } else {
        my $rs = $self->_setSaUserprefs( 'use_auto_whitelist', '0' );
        $rs ||= $self->cleanAwlDb( );
        return $rs if $rs;
    }

    if ($self->{'config'}->{'spamassassin'}->{'Bayes'}->{'enabled'}
        && $self->{'config'}->{'spamassassin'}->{'Bayes'}->{'site_wide'}
    ) {
        my $rs = $self->_registerCronjob( 'clean_bayes_db' );
        return $rs if $rs;
    }

    my $rs ||= $self->_setSaUserprefs(
        'use_bayes', $self->{'config'}->{'spamassassin'}->{'Bayes'}->{'enabled'} ? '1' : '0'
    );
    $rs ||= $self->_setSaUserprefs(
        'use_dcc', $self->{'config'}->{'spamassassin'}->{'DCC'}->{'enabled'} ? '1' : '0' );
    $rs ||= $self->_setSaUserprefs(
        'skip_rbl_checks', $self->{'config'}->{'spamassassin'}->{'use_rbl_checks'}->{'enabled'} eq 'yes' ? '0' : '1'
    );
}

=item _installRoundcubePlugins( [ $action = 'uninstall' ] )

 Install/Uninstall Roundcube plugins

 Param string $action Action to perform (install|uninstall)
 Return int 0 on success, other on failure

=cut

sub _installRoundcubePlugins
{
    my ($self, $action) = @_;
    $action //= 'uninstall';

    my $pluginsSrcDir = "$main::imscpConfig{'PLUGINS_DIR'}/SpamAssassin/roundcube-plugins";
    my $pluginDestDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/plugins";

    local $@;
    eval { iMSCP::Dir->new( dirname => $pluginDestDir.'/'.basename( $_ ) )->remove( ) for <$pluginsSrcDir/*> };
    if ($@) {
        error( $@ );
        return 1
    }

    return 0 unless $action eq 'install';

    for(<$pluginsSrcDir/*>) {
        my $pluginName = basename( $_ );
        next unless $self->{'config'}->{'roundcube'}->{$pluginName}->{'enabled'};

        eval { iMSCP::Dir->new( dirname => $_ )->rcopy( $pluginDestDir.'/'.$pluginName ); };
        if ($@) {
            error( $@ );
            return 1;
        }

        my $rs = setRights(
            $pluginDestDir.'/'.$pluginName,
            {
                user      => $self->{'_panel_user'},
                group     => $self->{'_panel_group'},
                dirmode   => '0550',
                filemode  => '0440',
                recursive => 1
            }
        );
        return $rs if $rs;
    }

    0;
}

=item _configureRoundcubePlugins( )

 Configure Roundcube plugins

 Return int 0 on success, other on failure

=cut

sub _configureRoundcubePlugins
{
    my ($self) = @_;

    for(qw / sauserprefs markasjunk2 /) {
        next unless $self->{'config'}->{'roundcube'}->{$_}->{'enabled'};

        my $file = iMSCP::File->new(
            filename => "$main::imscpConfig{'PLUGINS_DIR'}/SpamAssassin/config-templates/$_/config.inc.php"
        );
        my $fileContent = $file->get( );
        unless (defined $fileContent) {
            error( sprintf( "Couldn't read %s file", $file->{'filename'} ) );
            return 1;
        }

        if ($_ eq 'sauserprefs') {
            $fileContent = process(
                {
                    DATABASE_HOST        => $main::imscpConfig{'DATABASE_HOST'},
                    DATABASE_PORT        => $main::imscpConfig{'DATABASE_PORT'},
                    SA_DATABASE_NAME     => "$main::imscpConfig{'DATABASE_NAME'}_spamassassin",
                    SA_DATABASE_USER     => 'sa_user',
                    SA_DATABASE_PASSWORD => $self->{'_sa_db_passwd'}
                },
                $fileContent
            );

            my @settings = @{$self->{'config'}->{'roundcube'}->{'sauserprefs'}->{'sauserprefs_dont_override'}};

            if ($self->{'config'}->{'spamass_milter'}->{'spam_reject_policy'} == -1) {
                push @settings, 'rewrite_header Subject', '{report}';
            }

            push @settings, '{bayes}' unless $self->{'config'}->{'spamassassin'}->{'Bayes'}->{'enabled'};

            if ($self->{'config'}->{'spamassassin'}->{'Bayes'}->{'site_wide'}) {
                push @settings, 'bayes_auto_learn_threshold_nonspam', 'bayes_auto_learn_threshold_spam';
            }

            unless ($self->{'config'}->{'spamassassin'}->{'Razor2'}->{'enabled'}
                || $self->{'config'}->{'spamassassin'}->{'Pyzor'}->{'enabled'}
                || $self->{'config'}->{'spamassassin'}->{''}->{'enabled'}
                || $self->{'config'}->{'spamassassin'}->{'use_rbl_checks'}->{'enabled'}
            ) {
                push @settings, '{tests}';
            } else {
                push @settings, 'use_dcc' unless $self->{'config'}->{'spamassassin'}->{'DCC'}->{'enabled'};
                push @settings, 'use_pyzor' unless $self->{'config'}->{'spamassassin'}->{'Pyzor'}->{'enabled'};
                push @settings, 'use_razor2' unless $self->{'config'}->{'spamassassin'}->{'Razor2'}->{'enabled'};

                unless ($self->{'config'}->{'spamassassin'}->{'use_rbl_checks'}->{'enabled'}) {
                    push @settings, 'skip_rbl_checks'
                }
            }

            unless ($self->{'config'}->{'spamassassin'}->{'TextCat'}->{'enabled'}) {
                push @settings, 'ok_languages', 'ok_locales'
            }

            $fileContent =~ s/\Q{SAUSERPREFS_DONT_OVERRIDE}\E/@{[ join ",\n    ", map qq{'$_'}, uniq @settings ]}/g;
        } else {
            $fileContent =~ s/\Q{GUI_ROOT_DIR}\E/$main::imscpConfig{'GUI_ROOT_DIR'}/g;
        }

        local $UMASK = 227;
        $file->{'filename'} = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/plugins/$_/config.inc.php";

        $file->set( $fileContent );
        my $rs = $file->save( );
        $rs ||= $file->owner( $self->{'_panel_user'}, $self->{'_panel_group'} );
        $rs ||= $file->mode( 0440 );
        return $rs if $rs;

        if ($_ eq 'markasjunk2') {
            if ($self->{'config'}->{'roundcube'}->{'markasjunk2'}->{'enabled'}
                && $self->{'config'}->{'spamassassin'}->{'Bayes'}->{'enabled'}
            ) {
                $rs = $self->_registerCronjob( 'bayes_sa_learn' );
                return $rs if $rs;
            }

            $rs = $self->bayesSaLearn( );
            return $rs if $rs;
        }
    }

    0;
}

=item _enableRoundcubePlugins( [ $action = 'disable' ] )

 Enable/Disable Roundcube Plugins

 Param string $action Action to perform (enable|disable)
 Return int 0 on success, other on failure

=cut

sub _enableRoundcubePlugins
{
    my ($self, $action) = @_;
    $action //= 'disable';

    my $confFilename;
    if (-f "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/config/main.inc.php") {
        $confFilename = 'main.inc.php';
    } elsif (-f "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/config/config.inc.php") {
        $confFilename = 'config.inc.php';
    } else {
        error( "Couldn't find Roundcube configuration file" );
        return 1;
    }

    my $file = iMSCP::File->new(
        filename => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/config/$confFilename"
    );
    my $fileContent = $file->get( );
    unless (defined $fileContent) {
        error( sprintf( "Couldn't read %s file", $file->{'filename'} ) );
        return 1;
    }

    $fileContent = replaceBloc(
        qr/(:?^\n)?\Q# Begin Plugin::SpamAssassin\E\n/m, qr/\Q# Ending Plugin::SpamAssassin\E\n/, '', $fileContent
    );

    if ($action eq 'enable') {
        my @plugins;
        push @plugins, 'sauserprefs' if $self->{'config'}->{'roundcube'}->{'sauserprefs'}->{'enabled'};
        push @plugins, 'markasjunk2' if $self->{'config'}->{'roundcube'}->{'markasjunk2'}->{'enabled'}
            && $self->{'config'}->{'spamassassin'}->{'Bayes'}->{'enabled'};

        $fileContent .= <<"EOF" if @plugins;

# Begin Plugin::SpamAssassin
@{[ $confFilename eq 'main.inc.php' ? '$rcmail_config' : '$config' ]}\['plugins'] = array_merge(
    @{[ $confFilename eq 'main.inc.php' ? '$rcmail_config' : '$config' ]}\['plugins'],
    array(
        @{[ join ",\n        ", map qq/'$_'/, @plugins ]}
    )
);
# Ending Plugin::SpamAssassin
EOF
    }

    $file->set( $fileContent );
    $file->save( );
}

=item _setSaUserprefs( $preference, $value )

 Set the values in the SpamAssassin userpref table

 Param string $preference Preference name
 Param string $value Preference value
 Return int 0 on success, other on failure

=cut

sub _setSaUserprefs
{
    my (undef, $preference, $value) = @_;

    my $qrs = iMSCP::Database->factory( )->doQuery(
        'u',
        "UPDATE `$main::imscpConfig{'DATABASE_NAME'}_spamassassin`.`userpref` SET `value` = ? WHERE `preference` = ?",
        $value,
        $preference
    );
    unless (ref $qrs eq 'HASH') {
        error( $qrs );
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
    my ($self) = @_;

    my $dbName = $main::imscpConfig{'DATABASE_NAME'}.'_spamassassin';

    my $db = iMSCP::Database->factory( );
    my $qrs = $db->doQuery( '1', 'SHOW DATABASES LIKE ?', $dbName );
    unless (ref $qrs eq 'HASH') {
        error( $qrs );
        return 1;
    }

    unless (%{$qrs}) {
        error( sprintf( "Couldn't find the `%s' SQL database for SpamAssassin.", $dbName ) );
        return 1;
    }

    my $rs = $self->_dropSaSqlUser( );
    return $rs if $rs;

    $self->{'_sa_db_passwd'} = randomStr( 16, iMSCP::Crypt::ALNUM );

    local $@;
    eval {
        Servers::sqld->factory( )->createUser(
            'sa_user', $main::imscpConfig{'DATABASE_USER_HOST'}, $self->{'_sa_db_passwd'}
        );
    };
    if ($@) {
        error( sprintf( "Couldn't create SQL user for SpamAssassin: %s", $@ ) );
        return 1;
    }

    (my $quotedDbName = $db->quoteIdentifier( $dbName )) =~ s/([%_])/\\$1/g;
    $qrs = $db->doQuery(
        'g',
        "GRANT SELECT, INSERT, UPDATE, DELETE ON $quotedDbName.* TO ?\@?",
        'sa_user',
        $main::imscpConfig{'DATABASE_USER_HOST'}
    );
    unless (ref $qrs eq 'HASH') {
        error( sprintf( "Couldn't grant privileges on the `%s` database: %s", $dbName, $qrs ) );
        return 1;
    }

    0;
}

=item _dropSaSqlUser( )

 Drop SpamAssassin SQL user

 Return int 0 on success, 1 on failure

=cut

sub _dropSaSqlUser
{
    local $@;
    eval { Servers::sqld->factory( )->dropUser( 'sa_user', $main::imscpConfig{'DATABASE_USER_HOST'} ); };
    if ($@) {
        error( sprintf( "Couldn't drop SpamAssassin SQL user: %s", $@ ) );
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
    my ($self, $action) = @_;
    $action //= 'uninstall';

    for my $plugin(qw/ DecodeShortURLs iXhash2 /) {
        for(qw/ cf pm /) {
            if ($action eq 'install' && $self->{'config'}->{'spamassassin'}->{$plugin}->{'enabled'}) {
                my $file = iMSCP::File->new(
                    filename => "$main::imscpConfig{'PLUGINS_DIR'}/SpamAssassin/spamassassin-plugins/$plugin/$plugin.$_"
                );
                my $rs = $file->copyFile( '/etc/spamassassin', { preserve => 'no' } );
                return $rs if $rs;

                $file->{'filename'} = "/etc/spamassassin/$plugin.$_";
                $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
                $rs ||= $file->mode( 0644 );
                return $rs if $rs;
                next;
            }

            next unless -f "/etc/spamassassin/$plugin.$_";
            my $rs = iMSCP::File->new( filename => "/etc/spamassassin/$plugin.$_" )->delFile( );
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
