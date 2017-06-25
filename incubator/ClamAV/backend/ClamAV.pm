=head1 NAME

 Plugin::ClamAV

=cut

# i-MSCP ClamAV plugin
# Copyright (C) 2014-2017 Laurent Declercq <l.declercq@nuxwin.com>
# Copyright (C) 2013-2017 Rene Schuster <mail@reneschuster.de>
# Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>
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

package Plugin::ClamAV;

use strict;
use warnings;
use autouse 'iMSCP::Debug' => qw/ debug error /;
use autouse 'iMSCP::Execute' => qw/ execute /;
use autouse 'iMSCP::TemplateParser' => qw/ getBloc process replaceBloc /;
use Class::Autouse qw/ :nostat iMSCP::Dir iMSCP::File iMSCP::Service  Servers::mtaServers::sqld /;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP ClamAV plugin backend.

=head1 PUBLIC METHODS

=over 4

=item install( )

 Perform install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ($self) = @_;

    my $rs = $self->_installDistributionPackages( );
    $rs ||= $self->_installClamavUnofficialSigs( );
}

=item uninstall( )

 Perform uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ($self) = @_;

    $self->_removeClamavUnofficialSigs( );
}

=item update( )

 Perform update tasks

 Return int 0 on success, other on failure

=cut

sub update
{
    my ($self) = @_;

    $self->install( );
}

=item enable( )

 Perform enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
    my ($self) = @_;

    my $rs = $self->_configureClamavMilter( 'configure' );
    $rs ||= $self->_configurePostfix( 'configure' );
    $rs ||= $self->_configureClamavUnofficialSigs( 'configure' );
    return $rs if $rs;

    my $serviceTasksSub = sub {
        local $@;
        eval {
            my $serviceMngr = iMSCP::Service->getInstance( );
            for(qw/ clamav-freshclam clamav-daemon /) {
                $serviceMngr->enable( $_ );
                $serviceMngr->restart( $_ );
            }

            $serviceMngr->enable( 'clamav-milter' );
            $serviceMngr->getProvider( )->setPidPattern( 'clamav-milter' );
            $serviceMngr->restart( 'clamav-milter' );
        };
        if ($@) {
            error( $@ );
            return 1;
        }
        0;
    };

    if (defined $main::execmode && $main::execmode eq 'setup') {
        return $self->{'eventManager'}->register(
            'beforeSetupRestartServices',
            sub {
                unshift @{$_[0]}, [ $serviceTasksSub, 'ClamAV' ];
                0;
            }
        );
    }
 
    $serviceTasksSub->( );
}

=item disable( )

 Perform disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
    my ($self) = @_;

    my $rs = $self->_configureClamavUnofficialSigs( 'deconfigure' );
    $rs ||= $self->_configurePostfix( 'deconfigure' );
    $rs ||= $self->_configureClamavMilter( 'deconfigure' );
    return $rs if $rs || $self->{'action'} ne 'disable';

    local $@;
    eval {
        my $serviceMngr = iMSCP::Service->getInstance( );

        $serviceMngr->getProvider( )->setPidPattern( 'clamav-milter' );
        $serviceMngr->stop( 'clamav-milter' );
        $serviceMngr->disable( 'clamav-milter' );

        for(qw/ clamav-daemon clamav-freshclam /) {
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

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize plugin

 Return Plugin::ClamAV or die on failure

=cut

sub _init
{
    my ($self) = @_;

    for(qw/ PostfixMilterSocket clamav_milter_options clamav_unofficial_sigs_options /) {
        die( sprintf( 'Missing %s configuration parameter', $_ ) ) unless exists $self->{'config'}->{$_};
    }

    $self;
}

=item _installClamavUnofficialSigs( )

 Install clamav-unofficial-sigs

 Return int 0 on success, other on failure

=cut

sub _installClamavUnofficialSigs
{
    my $file = iMSCP::File->new(
        filename => "$main::imscpConfig{'PLUGINS_DIR'}/ClamAV/clamav-unofficial-sigs/clamav-unofficial-sigs.sh"
    );
    my $rs = $file->copyFile( '/usr/local/bin/clamav-unofficial-sigs.sh'  );
    return $rs if $rs;

    $file->{'filename'} = '/usr/local/bin/clamav-unofficial-sigs.sh';
    $rs = $file->owner( $main::imscpConfig{'ROOT_USER'}, 'staff' );
    $rs ||= $file->mode( 0755 );
}

=item _removeClamavUnofficialSigs( )

 Remove clamav-unofficial-sigs

 Return int 0 on success, other on failure

=cut

sub _removeClamavUnofficialSigs
{
    my ($self) = @_;

    my $rs = $self->_disableClamavUnofficialSigs( );
    return $rs if $rs || !-f '/usr/local/bin/clamav-unofficial-sigs.sh';

    iMSCP::File->new( filename => '/usr/local/bin/clamav-unofficial-sigs.sh' )->delFile( );
}

=item _configureClamavUnofficialSigs( [ $action = 'deconfigure' ] )

 Configure or deconfigure clamav-unofficial-sigs

 Param string $action Action to be performed (configure|deconfigure)
 Return int 0 on success, other on failure

=cut

sub _configureClamavUnofficialSigs
{
    my ($self, $action) = @_;
    $action //= 'deconfigure';

    if ($action eq 'configure') {
        if ($self->{'config'}->{'clamav_unofficial_sigs_options'}->{'clamav_unofficial_sigs'} eq 'yes') {
            my $rs = $self->_configureClamavUnofficialSigsConffile( 'master.conf' );
            $rs ||= $self->_configureClamavUnofficialSigsConffile( 'os.conf' );
            $rs ||= $self->_configureClamavUnofficialSigsConffile( 'user.conf' );
            $rs ||= execute( 'clamav-unofficial-sigs.sh >/dev/null', \ my $stdout, \ my $stderr );
            error( $stderr || 'Unknown error' ) if $rs;
            return $rs if $rs;

            # Generate the cron, logrotate and man files
            $rs = execute( 'clamav-unofficial-sigs.sh --install-all >/dev/null', \$stdout, \$stderr );
            error( $stderr || 'Unknown error' ) if $rs;
            return $rs;
        } else {
            return $self->_disableClamavUnofficialSigs( );
        }
    }

    return 0 unless -f '/etc/clamav-unofficial-sigs/user.conf';

    # Remove user.conf file for deactivation
    iMSCP::File->new( filename => '/etc/clamav-unofficial-sigs/user.conf' )->delFile( );
}

=item _configureClamavUnofficialSigsConffile( $confFile )

 Configure the given clamav-unofficial-sigs config files

 Param string $confFile Configuration filename
 Return int 0 on success, other on failure

=cut

sub _configureClamavUnofficialSigsConffile
{
    my ($self, $confFile) = @_;

    my $clamavUnofficialSigsEtc = '/etc/clamav-unofficial-sigs';

    local $@;
    eval { iMSCP::Dir->new( dirname => $clamavUnofficialSigsEtc )->make( ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    my $rs = iMSCP::File->new(
        filename => "$main::imscpConfig{'PLUGINS_DIR'}/ClamAV/clamav-unofficial-sigs/config/$confFile"
    )->copyFile(
        "$clamavUnofficialSigsEtc/$confFile", { preserve => 'no' }
    );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => "$clamavUnofficialSigsEtc/$confFile" );
    my $fileContent = $file->get( );
    unless (defined $fileContent) {
        error( sprintf( "Couldn't read %s file", $file->{'filename'} ) );
        return 1;
    }

    if ($confFile eq 'user.conf') {
        my $options = $self->{'config'}->{'clamav_unofficial_sigs_options'};
        my $configSnippet = '';

        # MalwarePatrol options
        $configSnippet .= "# MalwarePatrol options\n";
        while(my ($option, $value) = each( %{$options->{'malwarepatrol_options'}} )) {
            $configSnippet .= "$option=\"$value\"\n";
        }

        # SecuriteInfo options
        $configSnippet .= "\n# SecuriteInfo options\n";
        while(my ($option, $value) = each( %{$options->{'securiteinfo_options'}} )) {
            $configSnippet .= "$option=\"$value\"\n";
        }

        # Signatures enabled
        $configSnippet .= "\n# Signatures enabled\n";
        while(my ($option, $value) = each( %{$options->{'signatures_enabled'}} )) {
            $configSnippet .= "$option=\"$value\"\n";
        }

        # Rating options
        $configSnippet .= "\n# Rating options\n";
        while(my ($option, $value) = each( %{$options->{'rating_options'}} )) {
            $configSnippet .= "$option=\"$value\"\n";
        }

        $fileContent = process( { USER_CONFIGURATION => $configSnippet }, $fileContent );
    }

    $file->set( $fileContent );
    $file->save( );
}

=item _disableClamavUnofficialSigs( )

 Disable clamav-unofficial-sigs

 Return int 0 on success, other on failure

=cut

sub _disableClamavUnofficialSigs
{
    return 0 unless -d '/var/lib/clamav-unofficial-sigs';

    my @files = (
        '/etc/cron.d/clamav-unofficial-sigs',
        '/etc/logrotate.d/clamav-unofficial-sigs',
        '/usr/share/man/man8/clamav-unofficial-sigs.8'
    );

    if (-f '/var/lib/clamav-unofficial-sigs/configs/purge.txt') {
        my $file = iMSCP::File->new( filename => '/var/lib/clamav-unofficial-sigs/configs/purge.txt' );
        my $fileContent = $file->get( );
        unless (defined $fileContent) {
            error( sprintf( "Couldn't read %s file", $file->{'filename'} ) );
            return 1;
        }

        push @files, split /\n/, $fileContent;
    }

    for (@files) {
        next unless -f;
        my $rs = iMSCP::File->new( filename => $_ )->delFile( );
        return $rs if $rs;
    }

    local $@;
    eval {
        for ('/etc/clamav-unofficial-sigs', '/var/lib/clamav-unofficial-sigs', '/var/log/clamav-unofficial-sigs') {
            iMSCP::Dir->new( dirname => $_ )->remove( );
        }
    };
    if ($@) {
        error( $@ );
        return 1;
    }

    0;
}

=item _configureClamavMilter( $action )

 Configure or deconfigure clamav-milter

 Param string $action Action to be performed (configure|deconfigure)
 Return int 0 on success, other on failure

=cut

sub _configureClamavMilter
{
    my ($self, $action) = @_;

    unless (-f '/etc/clamav/clamav-milter.conf') {
        error( 'File /etc/clamav/clamav-milter.conf not found' );
        return 1;
    }

    my $file = iMSCP::File->new( filename => '/etc/clamav/clamav-milter.conf' );
    my $fileContent = $file->get( );
    unless (defined $fileContent) {
        error( sprintf( "Couldn't read %s file", $file->{'filename'} ) );
        return 1;
    }

    my $options = $self->{'config'}->{'clamav_milter_options'};
    my $optionReg = '((?:'.(join( '|', (keys %{$options}, 'AllowSupplementaryGroups') )).').*)';

    if ($action eq 'configure') {
        my $clamavVersion = $self->_getClamavMilterVersion( );
        return 1 unless defined $clamavVersion;

        # The `AllowSupplementaryGroups' option is not longer supported by ClamAV >= 0.99.2. Thus, we add it
        # only if ClamAV version is lower than 0.99.2
        if (version->parse( $clamavVersion ) >= version->parse( '0.99.2' )) {
            delete $self->{'config'}->{'clamav_milter_options'}->{'AllowSupplementaryGroups'};
        }

        $fileContent =~ s/^$optionReg/#$1/gim; # Disable default configuration options (those that we want redefine)

        my $configSnippet = "# Begin Plugin::ClamAV\n";
        while (my ($option, $value) = each( %{$options} )) {
            next if $value eq '';
            $configSnippet .= "$option $value\n";
        }
        $configSnippet .= "# Ending Plugin::ClamAV\n";

        if (getBloc( '# Begin Plugin::ClamAV\n', '# Ending Plugin::ClamAV\n', $fileContent ) ne '') {
            $fileContent = replaceBloc(
                '# Begin Plugin::ClamAV\n', '# Ending Plugin::ClamAV\n', $configSnippet, $fileContent
            );
        } else {
            $fileContent .= $configSnippet;
        }
    } elsif ($action eq 'deconfigure') {
        $fileContent = replaceBloc( "# Begin Plugin::ClamAV\n", "# Ending Plugin::ClamAV\n", '', $fileContent );
        $fileContent =~ s/^#$optionReg/$1/gim;
    }

    my $rs = $file->set( $fileContent );
    $rs ||= $file->save( );
}

=item _configurePostfix( $action )

 Configure or deconfigure postfix

 Param string $action Action to be performed (configure|deconfigure)
 Return int 0 on success, other on failure

=cut

sub _configurePostfix
{
    my ($self, $action) = @_;

    my $mta = Servers::mta->factory( );

    unless (defined $main::execmode && $main::execmode eq 'setup') {
        my $milterValuePrev = $self->{'config_prev'}->{'PostfixMilterSocket'};
        my $rs = $mta->postconf(
            (
                smtpd_milters     => {
                    action => 'remove',
                    values => [ qr/\Q$milterValuePrev\E/ ]
                },
                non_smtpd_milters => {
                    action => 'remove',
                    values => [ qr/\Q$milterValuePrev\E/ ]
                }
            )
        );
        return $rs if $rs;
    }

    return 0 unless $action eq 'configure';

    my $milterValue = $self->{'config'}->{'PostfixMilterSocket'};
    $mta->postconf(
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
            }
        )
    );
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
            '--purge', '--quiet', 'install', 'clamav', 'clamav-base', 'clamav-daemon', 'clamav-freshclam',
            'clamav-milter', 'curl', 'gnupg', 'rsync'
        ],
        \$stdout,
        \$stderr
    );
    debug( $stdout ) if $stdout;
    error( sprintf( "Couldn't install distribution packages: %s", $stderr || 'Unknown error' ) ) if $rs;
    $rs;
}

=item _getClamavMilterVersion( )

 Get ClamAV Milter version

 Return string|undef ClamAV version on success, undef on failure

=cut

sub _getClamavMilterVersion
{
    my $rs = execute( 'clamav-milter --version', \ my $stdout, \ my $stderr );

    return $stdout if !$rs && $stdout =~ s/clamav-milter\s+([\d.]+)/$1/;

    error( sprintf( "Couldn't get version: %s", $stderr || 'Version not found' ) );
    undef;
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>
 Rene Schuster <mail@reneschuster.de>
 Sascha Bay <info@space2place.de>

=cut

1;
__END__
