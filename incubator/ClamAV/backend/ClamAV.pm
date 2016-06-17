=head1 NAME

 Plugin::ClamAV

=cut

# i-MSCP ClamAV plugin
# Copyright (C) 2014-2016 Laurent Declercq <l.declercq@nuxwin.com>
# Copyright (C) 2013-2016 Rene Schuster <mail@reneschuster.de>
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
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Rights;
use iMSCP::TemplateParser;
use Servers::mta;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP ClamAV plugin backend.

=head1 PUBLIC METHODS

=over 4

=item install()

 Perform install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my $self = shift;

    my $rs = $self->_checkRequirements();
    $rs ||= $self->_installClamavUnofficialSigs();
}

=item uninstall()

 Perform uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    $_[0]->_removeClamavUnofficialSigs();
}

=item update()

 Perform update tasks

 Return int 0 on success, other on failure

=cut

sub update
{
    $_[0]->install();
}

=item enable()

 Process enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
    my $self = shift;

    my $rs = $self->_setupClamavMilter( 'configure' );
    $rs ||= $self->_setupPostfix( 'configure' );
    $rs ||= $self->_setupClamavUnofficialSigs( 'configure' );
    $rs ||= $self->_restartServices();
}

=item disable()

 Process disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
    my $self = shift;

    my $rs = $self->_setupClamavMilter( 'deconfigure' );
    $rs ||= $self->_setupPostfix( 'deconfigure' );
    $rs ||= $self->_setupClamavUnofficialSigs( 'deconfigure' );
    return $rs if $rs;

    unless ($self->{'action'} eq 'change') {
        $rs = $self->_restartServices();
        return $rs if $rs;
    }

    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize plugin

 Return Plugin::ClamAV or die on failure

=cut

sub _init
{
    my $self = shift;

    $self->{'FORCE_RETVAL'} = 'yes';
    $self;
}

=item _installClamavUnofficialSigs()

 Install clamav-unofficial-sigs

 Return int 0 on success, other on failure

=cut

sub _installClamavUnofficialSigs
{
    my $rs = iMSCP::File->new(
        filename => "$main::imscpConfig{'PLUGINS_DIR'}/ClamAV/clamav-unofficial-sigs/clamav-unofficial-sigs.sh"
    )->copyFile(
        "/usr/local/bin/"
    );
    $rs ||= setRights(
        '/usr/local/bin/clamav-unofficial-sigs.sh',
        {
            user  => 'root',
            group => 'staff',
            mode  => '0755'
        }
    );
}

=item _removeClamavUnofficialSigs()

 Remove clamav-unofficial-sigs

 Return int 0 on success, other on failure

=cut

sub _removeClamavUnofficialSigs
{
    my $self = shift;

    my $rs = $self->_disableClamavUnofficialSigs();

    # remove clamav-unofficial-sigs script
    $rs ||= iMSCP::File->new( filename => '/usr/local/bin/clamav-unofficial-sigs.sh' )->delFile();
}

=item _setupClamavUnofficialSigs($action)

 Configure or deconfigure clamav-unofficial-sigs

 Param string $action Action to be performed ( configure|deconfigure )
 Return int 0 on success, other on failure

=cut

sub _setupClamavUnofficialSigs
{
    my ($self, $action) = @_;

    if ($action eq 'configure') {
        if ($self->{'config'}->{'clamav_unofficial_sigs'} eq 'yes') {
            my $rs = $self->_checkRequirementsClamavUnofficialSigs();
            $rs ||= $self->_configureClamavUnofficialSigs( 'master.conf' );
            $rs ||= $self->_configureClamavUnofficialSigs( 'os.conf' );
            $rs ||= $self->_configureClamavUnofficialSigs( 'user.conf' );

            # execute the script
            $rs ||= execute( 'clamav-unofficial-sigs.sh >/dev/null', \my $stdout, \my $stderr );
            error( $stderr ) if $stderr && $rs;
            return $rs if $rs;

            # generate the cron, logrotate and man file
            $rs = execute( 'clamav-unofficial-sigs.sh --install-all >/dev/null', \$stdout, \$stderr );
            error( $stderr ) if $stderr && $rs;
            return $rs if $rs;
        } else {
            my $rs = $self->_disableClamavUnofficialSigs();
            return $rs if $rs;
        }
    } elsif ($action eq 'deconfigure') {
        # for deactivation remove user.conf file
        if (-f '/etc/clamav-unofficial-sigs/user.conf') {
            my $rs = iMSCP::File->new( filename => '/etc/clamav-unofficial-sigs/user.conf' )->delFile();
            return $rs if $rs;
        }
    }

    0;
}

=item _configureClamavUnofficialSigs($confFile)

 Configure clamav-unofficial-sigs config files

 Param string $confFile 
 Return int 0 on success, other on failure

=cut

sub _configureClamavUnofficialSigs
{
    my ($self, $confFile) = @_;

    my $clamavUnofficialSigsEtc = '/etc/clamav-unofficial-sigs';
    if (!-d $clamavUnofficialSigsEtc) {
        my $rs = iMSCP::Dir->new( dirname => $clamavUnofficialSigsEtc )->make();
        return $rs if $rs;
    }
    my $rs = iMSCP::File->new(
        filename => "$main::imscpConfig{'PLUGINS_DIR'}/ClamAV/clamav-unofficial-sigs/config/$confFile"
    )->copyFile(
        "$clamavUnofficialSigsEtc/$confFile",
        {
            preserve => 'no'
        }
    );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => "$clamavUnofficialSigsEtc/$confFile" );
    my $fileContent = $file->get();
    unless (defined $fileContent) {
        error( sprintf( 'Could not read %s file', $file->{'filename'} ) );
        return 1;
    }

    my $data = { };
    if ($confFile eq 'user.conf') {
        my $userConf = <<"EOF";
malwarepatrol_receipt_code="$self->{'config'}->{'malwarepatrol_receipt_code'}"
malwarepatrol_product_code="$self->{'config'}->{'malwarepatrol_product_code'}"
malwarepatrol_list="$self->{'config'}->{'malwarepatrol_list'}"
malwarepatrol_free="$self->{'config'}->{'malwarepatrol_free'}"

securiteinfo_authorisation_signature="$self->{'config'}->{'securiteinfo_authorisation_signature'}"

sanesecurity_enabled="$self->{'config'}->{'sanesecurity_enabled'}"
securiteinfo_enabled="$self->{'config'}->{'securiteinfo_enabled'}"
linuxmalwaredetect_enabled="$self->{'config'}->{'linuxmalwaredetect_enabled'}"
malwarepatrol_enabled="$self->{'config'}->{'malwarepatrol_enabled'}"
yararulesproject_enabled="$self->{'config'}->{'yararulesproject_enabled'}"

sanesecurity_dbs_rating="$self->{'config'}->{'sanesecurity_dbs_rating'}"
securiteinfo_dbs_rating="$self->{'config'}->{'securiteinfo_dbs_rating'}"
linuxmalwaredetect_dbs_rating="$self->{'config'}->{'linuxmalwaredetect_dbs_rating'}"
yararulesproject_dbs_rating="$self->{'config'}->{'yararulesproject_dbs_rating'}"

enable_random="no"
user_configuration_complete="yes"
EOF
        $fileContent = process(
            {
                user_configuration => $userConf
            },
            $fileContent
        );
    }

    $rs ||= $file->set( $fileContent );
    $rs ||= $file->save();
}

=item _disableClamavUnofficialSigs

 Disable clamav-unofficial-sigs

 Return int 0 on success, other on failure

=cut

sub _disableClamavUnofficialSigs
{
    if (-d '/var/lib/clamav-unofficial-sigs') {
        my @files = (
            '/etc/cron.d/clamav-unofficial-sigs',
            '/etc/logrotate.d/clamav-unofficial-sigs',
            '/usr/share/man/man8/clamav-unofficial-sigs.8'
        );
        my @dirs = (
            '/etc/clamav-unofficial-sigs',
            '/var/lib/clamav-unofficial-sigs',
            '/var/log/clamav-unofficial-sigs'
        );

        if (-f '/var/lib/clamav-unofficial-sigs/configs/purge.txt') {
            my $file = iMSCP::File->new( filename => '/var/lib/clamav-unofficial-sigs/configs/purge.txt' );
            my $fileContent = $file->get();
            unless (defined $fileContent) {
                error( sprintf( 'Could not read %s file', $file->{'filename'} ) );
                return 1;
            }

            foreach my $line(split /\n/, $fileContent) {
                if (-f $line) {
                    my $rs = iMSCP::File->new( filename => $line )->delFile();
                    return $rs if $rs;
                }
            }
        }

        for my $removeFile(@files) {
            if (-f $removeFile) {
                my $rs = iMSCP::File->new( filename => $removeFile )->delFile();
                return $rs if $rs;
            }
        }

        for my $removeDir(@dirs) {
            if (-d $removeDir) {
                my $rs = iMSCP::Dir->new( dirname => $removeDir )->remove();
                return $rs if $rs;
            }
        }
    }

    0;
}

=item _setupClamavMilter($action)

 Configure or deconfigure clamav-milter

 Param string $action Action to be performed ( configure|deconfigure )
 Return int 0 on success, other on failure

=cut

sub _setupClamavMilter
{
    my ($self, $action) = @_;

    if (-f '/etc/clamav/clamav-milter.conf') {
        my $file = iMSCP::File->new( filename => '/etc/clamav/clamav-milter.conf' );
        my $fileContent = $file->get();
        unless (defined $fileContent) {
            error( sprintf( 'Could not read %s file', $file->{'filename'} ) );
            return 1;
        }

        my $baseRegexp = '((?:MilterSocket|MilterSocketGroup|MilterSocketMode|FixStaleSocket|User|'.
            'AllowSupplementaryGroups|ReadTimeout|Foreground|Chroot|PidFile|TemporaryDirectory|ClamdSocket|LocalNet|'.
            'Whitelist|SkipAuthenticated|MaxFileSize|OnClean|OnInfected|OnFail|RejectMsg|AddHeader|ReportHostname|'.
            'VirusAction|LogFile|LogFileUnlock|LogFileMaxSize|LogTime|LogSyslog|LogFacility|LogVerbose|LogInfected|'.
            'LogClean|LogRotate|SupportMultipleRecipients).*)';

        if ($action eq 'configure') {
            $fileContent =~ s/^$baseRegexp/#$1/gm;
            my $configSnippet = "# Begin Plugin::ClamAV\n";
            for my $option(
                qw /
                    MilterSocket MilterSocketGroup MilterSocketMode FixStaleSocket User AllowSupplementaryGroups
                    ReadTimeout Foreground Chroot PidFile TemporaryDirectory ClamdSocket LocalNet Whitelist
                    SkipAuthenticated MaxFileSize OnClean OnInfected OnFail RejectMsg AddHeader ReportHostname
                    VirusAction LogFile LogFileUnlock LogFileMaxSize LogTime LogSyslog LogFacility LogVerbose
                    LogInfected LogClean LogRotate SupportMultipleRecipients
                    /
            ) {
                if (exists $self->{'config'}->{$option} && $self->{'config'}->{$option} ne '') {                  
                    # If Clamav Milter version < 0.99.2 then add option 'AllowSupplementaryGroups'
                    if ($option eq 'AllowSupplementaryGroups') {
                        if (version->parse( $self->_getClamavMilterVersion() ) < version->parse( '0.99.2' )) {
                            $configSnippet .= "$option $self->{'config'}->{$option}\n";
                        }
                    } else {
                        $configSnippet .= "$option $self->{'config'}->{$option}\n";
                    }
                }
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
            $fileContent =~ s/^#$baseRegexp/$1/gm;
        }

        my $rs = $file->set( $fileContent );
        $rs ||= $file->save();
    } else {
        error( 'File /etc/clamav/clamav-milter.conf not found' );
        return 1;
    }
}

=item _setupPostfix($action)

 Configure or deconfigure postfix

 Param string $action Action to be performed ( configure|deconfigure )
 Return int 0 on success, other on failure

=cut

sub _setupPostfix
{
    my ($self, $action) = @_;

    my $rs = execute( 'postconf -h smtpd_milters non_smtpd_milters', \my $stdout, \my $stderr );
    error( $stderr ) if $stderr && $rs;
    return $rs if $rs;

    # Extract postconf values
    my @postconfValues = split /\n/, $stdout;
    my $milterValue = $self->{'config'}->{'PostfixMilterSocket'};
    my $milterValuePrev = $self->{'config_prev'}->{'PostfixMilterSocket'};

    s/\s*(?:\Q$milterValuePrev\E|\Q$milterValue\E)//g for @postconfValues;

    if ($action eq 'configure') {
        my @postconf = (
            'milter_default_action=accept',
            'smtpd_milters='.(
                (@postconfValues ? escapeShell( "$postconfValues[0] $milterValue" ) : escapeShell( $milterValue ))
            ),
            'non_smtpd_milters='.(
                (@postconfValues > 1 ? escapeShell( "$postconfValues[1] $milterValue" ) : escapeShell( $milterValue ))
            )
        );

        $rs = execute( "postconf -e @postconf", \ my $stdout, \ my $stderr );
        debug( $stdout ) if $stdout;
        error( $stderr ) if $stderr && $rs;
    } elsif ($action eq 'deconfigure') {
        if (@postconfValues) {
            my @postconf = ( 'smtpd_milters='.escapeShell( $postconfValues[0] ) );
            if (@postconfValues > 1) {
                push @postconf, 'non_smtpd_milters='.escapeShell( $postconfValues[1] );
            }

            $rs = execute( "postconf -e @postconf", \ my $stdout, \ my $stderr );
            debug( $stdout ) if $stdout;
            error( $stderr ) if $stderr && $rs;
        }
    }

    $rs;
}

=item _checkRequirements

 Check for requirements

 Return int 0 if all requirements are met, other otherwise

=cut

sub _checkRequirements
{
    my $ret = 0;
    for(qw/ clamav clamav-base clamav-daemon clamav-freshclam clamav-milter /) {
        if (execute( "dpkg-query -W -f='\${Status}' $_ 2>/dev/null | grep -q '\\sinstalled\$'" )) {
            error( sprintf( 'The `%s` package is not installed on your system', $_ ) );
            $ret ||= 1;
        }
    }

    $ret;
}

=item _checkRequirementsClamavUnofficialSigs

 Check clamav-unofficial-sigs requirements

 Return int 0 if all requirements are meet, other otherwise

=cut

sub _checkRequirementsClamavUnofficialSigs
{
    my $ret = 0;
    for(qw/ curl gnupg rsync /) {
        if (execute( "dpkg-query -W -f='\${Status}' $_ 2>/dev/null | grep -q '\\sinstalled\$'" )) {
            error( sprintf( 'The `%s` package is not installed on your system', $_ ) );
            $ret ||= 1;
        }
    }

    $ret;
}

=item _restartServices

 Restart clamav-milter and schedule restart of Postfix

 Return int 0, other on failure

=cut

sub _restartServices
{
    # Here, we cannot use the i-MSCP service manager because the init script is returning specific status code (4)
    # even when this is expected (usage of tcp socket instead of unix socket)
    my $rs = execute( 'service clamav-milter restart', \ my $stdout, \ my $stderr );
    debug( $stdout ) if $stdout;
    error( $stderr ) if $rs && $stderr;
    return $rs if $rs;

    Servers::mta->factory()->{'reload'} = 1;
    0;
}

=item _getClamavMilterVersion()

 Get ClamAV Milter version

 Return string ClamAV Milter version

=cut

sub _getClamavMilterVersion
{
    my $rs = execute( "clamav-milter --version | awk '{print \$NF}'", \ my $stdout, \ my $stderr );
    error( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs;

    $stdout =~ s/^([\d.]+)/$1/r;
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>
 Rene Schuster <mail@reneschuster.de>
 Sascha Bay <info@space2place.de>

=cut

1;
__END__
