=head1 NAME

 Plugin::ClamAV

=cut

# i-MSCP ClamAV plugin
# Copyright (C) 2013-2015 Laurent Declercq <l.declercq@nuxwin.com>
# Copyright (C) 2013-2015 Rene Schuster <mail@reneschuster.de>
# Copyright (C) 2013-2015 Sascha Bay <info@space2place.de>
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

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::Service;
use iMSCP::TemplateParser;
use JSON;

use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP ClamAV plugin.

=head1 PUBLIC METHODS

=over 4

=item enable()

 Process enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = $_[0];

	my $rs = $self->_checkRequirements();
	return $rs if $rs;

	$rs = $self->_clamavMilter('configure');
	return $rs if $rs;

	$rs = $self->_postfix('configure');
	return $rs if $rs;

	$rs = $self->_restartClamavMilter();
	return $rs if $rs;

	$self->_schedulePostfixRestart();
}

=item disable()

 Process disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
	my $self = $_[0];

	my $rs = $self->_clamavMilter('deconfigure');
	return $rs if $rs;

	$rs = $self->_postfix('deconfigure');
	return $rs if $rs;

	$rs = $self->_restartClamavMilter();
	return $rs if $rs;

	$self->_schedulePostfixRestart();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Plugin::ClamAV or die on failure

=cut

sub _init
{
	my $self = $_[0];

	if($self->{'action'} ~~ [ 'install', 'update', 'change', 'enable', 'disable' ]) {
		my $config = iMSCP::Database->factory()->doQuery(
			'plugin_name', 'SELECT plugin_name, plugin_config FROM plugin WHERE plugin_name = ?', 'ClamAV'
		);
		unless(ref $config eq 'HASH') {
			die("ClamAV: $config");
		}

		$self->{'config'} = decode_json($config->{'ClamAV'}->{'plugin_config'});
	}

	$self;
}

=item _clamavMilter($action)

 Configure or deconfigure clamav-milter

 Param string $action Action to be performed ( configure|deconfigure )
 Return int 0 on success, other on failure

=cut

sub _clamavMilter
{
	my ($self, $action) = @_;

	if(-f '/etc/clamav/clamav-milter.conf') {
		my $file = iMSCP::File->new( filename => '/etc/clamav/clamav-milter.conf' );

		my $fileContent = $file->get();
		unless (defined $fileContent) {
			error("Unable to read $file->{'filename'}");
			return 1;
		}

		my $baseRegexp = '((?:MilterSocket|FixStaleSocket|User|AllowSupplementaryGroups|ReadTimeout|Foreground|PidFile|' .
			'ClamdSocket|OnClean|OnInfected|OnFail|AddHeader|LogSyslog|LogFacility|LogVerbose|LogInfected|' .
			'LogClean|LogRotate|MaxFileSize|SupportMultipleRecipients|TemporaryDirectory|LogFile|LogTime|' .
			'LogFileUnlock|LogFileMaxSize|MilterSocketGroup|MilterSocketMode|VirusAction).*)';

		if($action eq 'configure') {
			$fileContent =~ s/^$baseRegexp/#$1/gm;

			my $configSnippet = "# Begin Plugin::ClamAV\n";

			for my $paramName(
				qw /
					MilterSocket FixStaleSocket User AllowSupplementaryGroups ReadTimeout Foreground PidFile ClamdSocket
					OnClean OnInfected OnFail AddHeader LogSyslog LogFacility LogVerbose LogInfected LogClean MaxFileSize
					TemporaryDirectory LogFile LogTime LogFileUnlock LogFileMaxSize MilterSocketGroup MilterSocketMode
					RejectMsg VirusAction
				/
			) {
				$configSnippet .= "$paramName $self->{'config'}->{$paramName}\n";
			}

			$configSnippet .= "# Ending Plugin::ClamAV\n";

			if(getBloc('# Begin Plugin::ClamAV\n', '# Ending Plugin::ClamAV\n', $fileContent) ne '') {
				$fileContent = replaceBloc(
					'# Begin Plugin::ClamAV\n',
					'# Ending Plugin::ClamAV\n',
					'# Begin Plugin::ClamAV\n' .
						$configSnippet .
					'# Ending Plugin::ClamAV\n',
					$fileContent
				)
			} else {
				$fileContent .= $configSnippet
			}
		} elsif($action eq 'deconfigure') {
			$fileContent = replaceBloc('# Begin Plugin::ClamAV\n', '# Ending Plugin::ClamAV\n', '', $fileContent);
		}

		my $rs = $file->set($fileContent);
		return $rs if $rs;

		$file->save();
	} else {
		error('File /etc/clamav/clamav-milter.conf not found');
		return 1;
	}
}

=item _postfix($action)

 Configure or deconfigure postfix

 Param string $action Action to be performed ( configure|deconfigure )
 Return int 0 on success, other on failure

=cut

sub _postfix
{
	my ($self, $action) = @_;

	my ($stdout, $stderr);
	my $rs = execute('postconf smtpd_milters non_smtpd_milters', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Extract postconf values
	s/^.*=\s*(.*)/$1/ for ( my @postconfValues = split "\n", $stdout );

	my $milterValue = $self->{'config'}->{'PostfixMilterSocket'};

	# Reset milter ( See #IP-1278 )
	s%\s*(?:$milterValue|inet:localhost:32767|unix:/clamav/clamav-milter.ctl)%%g for @postconfValues;

	if($action eq 'configure') {
		my @postconf = (
			'milter_default_action=accept',
			'smtpd_milters=' . escapeShell("$postconfValues[0] $milterValue"),
			'non_smtpd_milters=' . escapeShell("$postconfValues[1] $milterValue")
		);

		$rs = execute("postconf -e @postconf", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	} elsif($action eq 'deconfigure') {
		my @postconf = (
			'smtpd_milters=' . escapeShell($postconfValues[0]), 'non_smtpd_milters=' . escapeShell($postconfValues[1])
		);
		$rs = execute("postconf -e @postconf", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	0;
}

=item _restartClamavMilter()

 Restart a ClamAV Milter

 Return int 0 on success, other on failure

=cut

sub _restartClamavMilter
{
	iMSCP::Service->getInstance()->restart('clamav-milter', 'retval');
}

=item _schedulePostfixRestart()

 Schedule restart of Postfix

 Return int 0 on success, other on failure

=cut

sub _schedulePostfixRestart
{
	require Servers::mta;

	Servers::mta->factory()->{'restart'} = 'yes';

	0;
}

=item _checkRequirements()

 Check for requirements

 Return int 0 if all requirements are meet, other otherwise

=cut

sub _checkRequirements
{
	my $ret = 0;

	for my $package (qw/clamav clamav-base clamav-daemon clamav-freshclam clamav-milter/) {
		my ($stdout, $stderr);
		my $rs = execute(
			"LANG=C dpkg-query --show --showformat '\${Status}' $package | cut -d ' ' -f 3", \$stdout, \$stderr
		);
		debug($stdout) if $stdout;
		if($stdout ne 'installed') {
			error("The $package package is not installed on your system");
			$ret ||= 1;
		}
	}

	$ret;
}

=back

=head1 AUTHORS AND CONTRIBUTORS

 Laurent Declercq <l.declercq@nuxwin.com>
 Rene Schuster <mail@reneschuster.de>
 Sascha Bay <info@space2place.de>

=cut

1;
__END__
