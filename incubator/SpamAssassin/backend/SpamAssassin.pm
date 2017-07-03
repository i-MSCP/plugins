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
    iMSCP::Database iMSCP::Dir iMSCP::File iMSCP::Service iMSCP::SystemUser Servers::cron Servers::mta Servers::sqld /;
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

    return 0 unless version->parse( $fromVersion ) < version->parse('2.0.0' )
        && -f '/etc/spamassassin/00_imscp.pre';

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

    my $rs = $self->_updateSpamdUser( );
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
                unshift @{$_[0]}, [ $serviceTasksSub, 'SpamAssassin' ];
                0;
            }
        );
        return $rs if $rs;
    } else {
        $rs = $serviceTasksSub->( );
        return $rs if $rs;
        undef $serviceTasksSub;
    }

    return 0 unless grep( lc $_ eq 'roundcube', split ',', $main::imscpConfig{'WEBMAIL_PACKAGES'} );

    $rs = $self->_installRoundcubePlugins( 'install' );
    $rs ||= $self->_configureRoundcubePlugins( );
    $rs ||= $self->_enableRoundcubePlugins( 'enable' );
    return $rs if $rs || defined $main::execmode && $main::execmode eq 'setup';

    local $@;
    eval { iMSCP::Service->getInstance( )->reload( 'imscp_panel' ); };
    if ($@) {
        error( $@ );
        return 1;
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
    return $rs if $rs;

    local $@;
    eval { Servers::sqld->factory( )->dropUser( 'sa_user', $main::imscpConfig{'DATABASE_USER_HOST'} ); };
    if ($@) {
        error( sprintf( "Couldn't drop SpamAssassin SQL user: %s", $@ ) );
        return 1;
    }

    return 0 if $self->{'action'} ne 'disable';

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

    my $rs = $self->guessSpamdUserAndGroup( );
    return $rs if $rs;

    $rs = execute( "su - $self->{'_spamd_user'} -c '/usr/bin/razor-admin -discover'", \ my $stdout, \ my $stderr );
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
    my $dbh = iMSCP::Database->factory( );
    my $dbi = $dbh->getRawDb( );
    my $oldDb;

    local $@;
    eval {
        local $dbi->{'RaiseError'} = 1;
        $oldDb = $dbh->useDatabase( "$main::imscpConfig{'DATABASE_NAME'}_spamassassin" );
        $dbi->do(
            '
                DELETE FROM awl
                WHERE (count = 1 AND last_update < DATE_SUB(NOW( ), INTERVAL 1 WEEK))
                OR (last_update < DATE_SUB(NOW( ), INTERVAL 1 MONTH))
            '
        );
        $dbh->useDatabase( $oldDb ) if $oldDb;
    };
    if ($@) {
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
        my $rs = execute( "sa-learn --no-sync --$learningMode -u $username $_", \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr || 'Unknown error' ) if $rs;
        return $rs if $rs;
    }

    # Synchronize the database and the journal once per training session
    my $rs = execute( "sa-learn --sync", \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
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
    my ($self) = @_;

    $self->{'FORCE_RETVAL'} = 'yes';
    $self->{'_panel_user'} = $main::imscpConfig{'SYSTEM_USER_PREFIX'}.$main::imscpConfig{'SYSTEM_USER_MIN_UID'};
    $self->{'_panel_group'} = getgrgid ( (getpwnam( $self->{'_panel_user'} ))[3] ) or die(
        "Couldn't find panel unix user group"
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
    debug( $stdout ) if $stdout;
    error( sprintf( "Couldn't update APT index: %s", $stderr || 'Unknown error' ) ) if $rs;
    return $rs if $rs;

    $rs = execute(
        [
            'apt-get', '-o', 'DPkg::Options::=--force-confold', '-o', 'DPkg::Options::=--force-confdef',
            '-o', 'DPkg::Options::=--force-confmiss', '--assume-yes', '--auto-remove', '--no-install-recommends',
            '--purge', '--quiet', 'install', 'sa-compile', 'spamassassin', 'spamass-milter', 'libnet-ident-perl',
            'libmail-dkim-perl', 'libmail-spf-perl', 'libencode-detect-perl', 'pyzor', 'razor', 'spamc',
            'libnet-patricia-perl', 'libgeo-ip-perl'
        ],
        \$stdout,
        \$stderr
    );
    debug( $stdout ) if $stdout;
    error( sprintf( "Couldn't install distribution packages: %s", $stderr || 'Unknown error' ) ) if $rs;
    $rs;
}

=item _updateSpamdUser( )

 Update spamd unix user and its home directory

 Return int 0 on success, other on failure

=cut

sub _updateSpamdUser
{
    my ($self) = @_;

    my $rs = $self->guessSpamdUserAndGroup( );
    $rs ||= iMSCP::SystemUser->new(
        {
            username => $self->{'_spamd_user'},
            group    => $self->{'_spamd_group'},
            system   => 1,
            comment  => '',
            home     => $self->{'config'}->{'spamd'}->{'homedir'},
            shell    => '/bin/sh'
        }
    )->addSystemUser( );

    local $@;
    eval {
        iMSCP::Dir->new( dirname => $self->{'config'}->{'spamd'}->{'homedir'} )->make(
            user  => $self->{'_spamd_user'},
            group => $self->{'_spamd_group'}
        );

        iMSCP::Dir->new( dirname => "$self->{'config'}->{'spamd'}->{'homedir'}/sa-update-keys" )->make(
            user  => $self->{'_spamd_user'},
            group => $self->{'_spamd_group'},
            mode  => 0700
        );

        my ($stderr, $stdout);
        $rs = execute(
            [
                'su', '-', $self->{'_spamd_user'}, '-c',
                "sa-update --gpghomedir $self->{'config'}->{'spamd'}->{'homedir'}/sa-update-keys --import "
                    ."/usr/share/spamassassin/GPG.KEY"
            ],
            \$stdout, \$stderr,
        );
        debug( $stdout ) if $stdout;
        !$rs or die( $stderr || 'Unknown error' );
    };
    if ($@) {
        error( $@ );
        return 1;
    }

    $rs = setRights(
        $self->{'config'}->{'spamd'}->{'homedir'},
        {
            user      => $self->{'_spamd_user'},
            group     => $self->{'_spamd_group'},
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

    my $rs = $self->guessSpamdUserAndGroup( );
    return $rs if $rs;

    $rs = execute( "su - $self->{'_spamd_user'} -c 'pyzor discover'", \ my $stdout, \ my $stderr );
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

    return 0 if -d "$self->{'config'}->{'spamd'}->{'homedir'}/.razor";

    my $rs = $self->guessSpamdUserAndGroup( );
    return $rs if $rs;

    for(qw/ create register /) {
        $rs = execute( "su  - $self->{'_spamd_user'} -c 'razor-admin -$_'", \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
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
        $options =~ s/\Q$spamcFlags\E$// if ($spamcFlags) = $options =~ /(\s+--\s+.*)$/;
        $options .= " -i $_" for @{$self->{'config'}->{'spamass_milter'}->{'networks'}};
        $options .= ' -I' if $self->{'config'}->{'spamass_milter'}->{'ignore_auth_sender_msgs'};
        $options .= ' -r '.$self->{'config'}->{'spamass_milter'}->{'spam_reject_policy'};

        $fileContent =~ s%^OPTIONS=.*%OPTIONS="@{[ $options.($spamcFlags // '') ]}"%m;
        $fileContent =~ s/^[#\s]*SOCKET=.*/SOCKET="$socketPath"/m;
        $fileContent =~ s/^[#\s]*SOCKETOWNER=.*/SOCKETOWNER="$socketOwner"/m;
        $fileContent =~ s/^[#\s]*SOCKETMODE=.*/SOCKETMODE="$socketMode"/m;
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
    my $milterMacros = 'i j {daemon_name} v {if_name} _';

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
                values => [ qr/\Q$milterMacros\E/ ]
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
                values => [ $milterValue ],
                before => qr/.*/ # Make sure that SpamAssassin filtering is processed first
            },
            non_smtpd_milters     => {
                action => 'add',
                values => [ $milterValue ],
                before => qr/.*/ # Make sure that SpamAssassin filtering is processed first
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

        $fileContent = process(
            {
                DATABASE_HOST         => $main::imscpConfig{'DATABASE_HOST'},
                DATABASE_PORT         => $main::imscpConfig{'DATABASE_PORT'},
                SA_DATABASE_NAME      => "$main::imscpConfig{'DATABASE_NAME'}_spamassassin",
                SA_DATABASE_USER      => 'sa_user',
                SA_DATABASE_PASSWORD  => $self->{'_sa_db_passwd'},
                DISCARDED_PREFERENCES => (@discardedPrefs)
                    ? " AND preference NOT IN(@{[ join ', ', map qq{'$_'}, @discardedPrefs ] })" : ''
            },
            $fileContent
        );

        if ($self->{'config'}->{'spamassassin'}->{'Bayes'}->{'site_wide'}) {
            $fileContent =~ s/^[#\s](bayes_(?:auto_expire|sql_override_username))/$1/gm;
        } else {
            $fileContent =~ s/^(bayes_(?:auto_expire|sql_override_username))/#$1/gm;
        }

        local $UMASK = 027;
        $file->{'filename'} = "/etc/spamassassin/00_imscp.cf";

        my $rs = $file->set( $fileContent );
        $rs ||= $file->save( );
        $rs ||= $file->owner( $main::imscpConfig{'ROOT_USER'}, $self->{'_spamd_group'} );
        $rs ||= $file->mode( 0640 );
        return $rs if $rs;

        while(my ($plg, $data) = each %{$self->{'config'}->{'spamassassin'}}) {
            next unless defined $data->{'config_file'};
            $rs = $self->_enableSaPlugin( $plg, $data->{'config_file'}, $data->{'enabled'} ? 'enable' : 'disable' );
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

=item _enableSaPlugin( $plugin, $conffile, $action )

 Enable/Disable the given SpamAssassin plugin in the given SpamAssassin configuration file.
 
 Generally speaking, SA plugin are loaded in one file only. We support multiple files to handle
 case where their would be different installation between various supported distributions.

 Param string $plugin Plugin name
 Param string $conffile SpamAssassin configuration file in which the plugin must be enabled/disabled
 Param string $action Action to perform (enable/disable)
 Return int 0 on success, other on failure

=cut

sub _enableSaPlugin
{
    my (undef, $plugin, $conffile, $action) = @_;

    my $file = iMSCP::File->new( filename => $conffile );
    my $fileContent = $file->get( );
    unless (defined $fileContent) {
        error( sprintf( "Couldn't read %s file", $file->{'filename'} ) );
        return 1;
    }

    $fileContent =~ s/^(loadplugin Mail::SpamAssassin::Plugin::$plugin)/#$1/m if $action eq 'disable';

    if ($action eq 'enable' && !($fileContent =~ s/^[#\s]*(loadplugin Mail::SpamAssassin::Plugin::$plugin)/$1/m)) {
        # Plugin line not in file. We add it manually
        $fileContent .= <<"EOF";

loadplugin Mail::SpamAssassin::Plugin::${plugin}
EOF
    }

    $file->set( $fileContent );
    $file->save( );
}

=item _setupSaPlugins( )

 Setup SpamAssassin plugins

 Return int 0 on success, other on failure

=cut

sub _setupSaPlugins
{
    my ($self) = @_;

    my $c = $self->{'config'}->{'spamassassin'};

    my $rs = $self->_setGlobalSaPref(
        [ 'use_bayes', 'use_bayes_rules', 'bayes_auto_learn' ],
        ($c->{'Bayes'}->{'enforced'} ? 1 : 0, $c->{'Bayes'}->{'enforced'})
    );

    $rs = $self->_setGlobalSaPref( [ 'use_dcc' ], $c->{'DCC'}->{'enforced'} ? 1 : 0, $c->{'DCC'}->{'enforced'} );
    $rs = $self->_setGlobalSaPref( [ 'use_pyzor' ], $c->{'Pyzor'}->{'enforced'} ? 1 : 0, $c->{'Pyzor'}->{'enforced'} );
    $rs = $self->_setGlobalSaPref( [ 'use_bayes' ], $c->{'Razor2'}->{'enforced'} ? 1 : 0, $c->{'Razor2'}->{'enforced'} );
    $rs = $self->_setGlobalSaPref(
        [ 'skip_rbl_checks'], $c->{'rbl_checks'}->{'enforced'} ? 0 : 1, $c->{'rbl_checks'}->{'enforced'}
    );

    if ($self->{'config'}->{'spamassassin'}->{'Bayes'}->{'site_wide'}) {
        # If the SA Bayes plugin operates at site-wide, we mus prevent users to
        # act on threshold-based auto-learning discriminator for SpamAssassin's
        # Bayes subsystem.
        $rs ||= $self->_setGlobalSaPref( [ 'bayes_auto_learn_threshold_nonspam' ], '0.1', 1 );
        $rs ||= $self->_setGlobalSaPref( [ 'bayes_auto_learn_threshold_spam' ], '12.0', 1 );
        return $rs if $rs;
    }

    $rs = ($c->{'AWL'}->{'enabled'}) ? $self->_registerCronjob( 'clean_awl_db' ) : $self->cleanAwlDb( );
    $rs ||= $self->_registerCronjob( 'clean_bayes_db' ) if $c->{'Bayes'}->{'enabled'} && $c->{'Bayes'}->{'site_wide'};
    $rs ||= $self->_setupPyzor( ) if $c->{'Pyzor'}->{'enabled'};
    return $rs if $rs || !$c->{'Razor2'}->{'enabled'};

    $rs = $self->_setupRazor( );
    $rs ||= $self->_registerCronjob( 'discover_razor' );
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

            # If SPAM is never tagged, there is not reasons to let user change headers and
            # report related settings through Roundcube sauserprefs plugin
            push @settings, '{headers}', '{report}', 'rewrite_header Subject'
                if $self->{'config'}->{'spamass_milter'}->{'spam_reject_policy'} == -1;
            # Hide Bayes settings in Roundcube sauserprefs plugin if the SA plugin is disabled or enforced
            push @settings, '{bayes}' if !$self->{'config'}->{'spamassassin'}->{'Bayes'}->{'enabled'}
                || $self->{'config'}->{'spamassassin'}->{'Bayes'}->{'enforced'};
            # If the SA Bayes plugin operates on a site-wide basis, we must
            # prevent users to act on threshold-based auto-learning
            # discriminator for SpamAssassin's Bayes subsystem.
            push @settings, 'bayes_auto_learn_threshold_nonspam', 'bayes_auto_learn_threshold_spam'
                if $self->{'config'}->{'spamassassin'}->{'Bayes'}->{'site_wide'};

            if (
                (!$self->{'config'}->{'spamassassin'}->{'DCC'}->{'enabled'}
                    || $self->{'config'}->{'spamassassin'}->{'DCC'}->{'enforced'}
                ) && (!$self->{'config'}->{'spamassassin'}->{'Pyzor'}->{'enabled'}
                    || $self->{'config'}->{'spamassassin'}->{'Pyzor'}->{'enforced'}
                ) && (!$self->{'config'}->{'spamassassin'}->{'Razor2'}->{'enabled'}
                    || $self->{'config'}->{'spamassassin'}->{'Razor2'}->{'enforced'}
                ) && (!$self->{'config'}->{'spamassassin'}->{'rbl_checks'}->{'enabled'}
                    || $self->{'config'}->{'spamassassin'}->{'rbl_checks'}->{'enforced'}
                )
            ) {
                # None of plugins for which parameters are settable trough Roundcube sauserprefs plugin is enabled.
                # Therefore, there is no reasons to show them in the plugin interface
                push @settings, '{tests}';
            } else {
                # Hide DCC setting in Roundcube sauserprefs plugin if the SA plugin is disabled or enforced
                push @settings, 'use_dcc' if !$self->{'config'}->{'spamassassin'}->{'DCC'}->{'enabled'}
                    || $self->{'config'}->{'spamassassin'}->{'DCC'}->{'enforced'};
                # Hide Pyzor setting in Roundcube sauserprefs plugin if the SA plugin is disabled or enforced
                push @settings, 'use_pyzor' if !$self->{'config'}->{'spamassassin'}->{'Pyzor'}->{'enabled'}
                    || $self->{'config'}->{'spamassassin'}->{'Pyzor'}->{'enforced'};
                # Hide Razor2 setting in Roundcube sauserprefs plugin if the SA plugin is disabled or enforced
                push @settings, 'use_razor2' if !$self->{'config'}->{'spamassassin'}->{'Razor2'}->{'enabled'}
                    || $self->{'config'}->{'spamassassin'}->{'Razor2'}->{'enforced'};
                # Hide RBL checks setting in Roundcube sauserprefs plugin if SA RBL checks are disabled or enforced
                push @settings, 'use_rbl_checks' if !$self->{'config'}->{'spamassassin'}->{'rbl_checks'}->{'enabled'}
                    || $self->{'config'}->{'spamassassin'}->{'rbl_checks'}->{'enforced'}
            }

            # Hide TextCat setting in Roundcube sauserprefs plugin if the SA plugin is disabled or enforced
            push @settings, 'ok_languages' if !$self->{'config'}->{'spamassassin'}->{'TextCat'}->{'enabled'}
                || $self->{'config'}->{'spamassassin'}->{'TextCat'}->{'enforced'};

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
            } else {
                $rs = $self->bayesSaLearn( );
                return $rs if $rs;
            }
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

=item _setGlobalSaPref( \@preferences, $value [, $enforce = FALSE ] )

 Update the given global SA preferences and enforce them if requested

 Param arrayref \@preference Global List of SA preference names
 Param string $value Global SA preference value
 Param bool $enforce Whether or not the Global SA preference must be enforced
 Return int 0 on success, other on failure

=cut

sub _setGlobalSaPref
{
    my (undef, $preferences, $value, $enforce) = @_;

    my $dbh = iMSCP::Database->factory( );
    my $dbi = $dbh->getRawDb( );
    my $oldDb;

    local $@;
    eval {
        local $dbi->{'RaiseError'} = 1;
        $oldDb = $dbh->useDatabase( "$main::imscpConfig{'DATABASE_NAME'}_spamassassin" );

        for(@{$preferences}) {
            $dbi->do( "DELETE FROM userpref WHERE username <> ? AND preference = ?", undef, '$GLOBAL', $_ ) if $enforce;
            $dbi->do( "UPDATE userpref SET value = ? WHERE username = ? AND preference = ?", undef, $value, '$GLOBAL', $_ );
        }

        $dbh->useDatabase( $oldDb ) if $oldDb;
    };
    if ($@) {
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
    my ($self) = @_;

    my $dbh = iMSCP::Database->factory( );
    my $dbi = $dbh->getRawDb( );

    eval {
        $self->{'_sa_db_passwd'} = randomStr( 16, iMSCP::Crypt::ALNUM );
        local $dbi->{'RaiseError'} = 1;
        my $dbName = $main::imscpConfig{'DATABASE_NAME'}.'_spamassassin';
        my $qrs = $dbh->doQuery( '1', 'SHOW DATABASES LIKE ?', $dbName );
        %{$qrs} or die( sprintf( "Couldn't find the `%s' SQL database.", $dbName ) );
        local $dbi->{'RaiseError'} = 0; # Needed due to a bug in i-MSCP core which has been fixed in v1.4.7
        Servers::sqld->factory( )->dropUser( 'sa_user', $main::imscpConfig{'DATABASE_USER_HOST'} );
        Servers::sqld->factory( )->createUser(
            'sa_user', $main::imscpConfig{'DATABASE_USER_HOST'}, $self->{'_sa_db_passwd'}
        );
        local $dbi->{'RaiseError'} = 1;
        my $quotedDbName = $dbh->quoteIdentifier( $dbName );
        $dbi->do(
            "GRANT SELECT, INSERT, UPDATE, DELETE ON $quotedDbName.* TO ?\@?",
            undef, 'sa_user', $main::imscpConfig{'DATABASE_USER_HOST'}
        );
    };
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
                $rs = $file->owner( $main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'} );
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

=item guessSpamdUserAndGroup( )

 Guess Spamd unix user/group
 
 Return int 0 on success, 1 on failure

=cut

sub guessSpamdUserAndGroup
{
    my ($self) = @_;

    return if defined $self->{'_spamd_user'};

    local $@;
    eval {
        (my ($uid, $gid) = (CORE::stat($self->{'config'}->{'spamd'}->{'homedir'}))[4, 5]) or die(
            sprintf( "Couldn't stat spamd user homedir: %s", $! )
        );
        $self->{'_spamd_user'} = getpwuid( $uid ) or die( "Couldn't find spamd unix user" );
        $self->{'_spamd_group'} = getgrgid ( $gid ) or die( "Couldn't find spamd unix group");
        $self->{'config'}->{'spamd'}->{'options'} = process(
            {
                SPAMD_USER    => $self->{'_spamd_user'},
                SPAMD_GROUP   => $self->{'_spamd_group'},
                SPAMD_HOMEDIR => $self->{'config'}->{'spamd'}->{'homedir'},
            },
            $self->{'config'}->{'spamd'}->{'options'}
        );
    };
    if ($@) {
        error( $@ );
        return 1;
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
