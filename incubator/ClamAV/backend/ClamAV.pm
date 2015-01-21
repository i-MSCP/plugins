=head1 NAME

 Plugin::ClamAV

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by internet Multi Server Control Panel
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
#
# @copyright   Sascha Bay <info@space2place.de>
# @copyright   Rene Schuster <mail@reneschuster.de>
# @author      Sascha Bay <info@space2place.de>
# @author      Rene Schuster <mail@reneschuster.de>
# @contributor Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Plugin::ClamAV;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::File;
use iMSCP::Execute;
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

	$rs = $self->_clamavMilterConfig('add');
	return $rs if $rs;

	$rs = $self->_postfixConfig('add');
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

	my $rs = $self->_clamavMilterConfig('remove');
	return $rs if $rs;

	$rs = $self->_postfixConfig('remove');
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

=item _clamavMilterConfig($action)

 Add or remove clamav-milter configuration for this plugin

 Param string $action Action to be performed ( add|remove )
 Return int 0 on success, other on failure

=cut

sub _clamavMilterConfig
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
			'LogFileUnlock|LogFileMaxSize|MilterSocketGroup|MilterSocketMode).*)';

		if($action eq 'add') {
			$fileContent =~ s/^$baseRegexp/#$1/gm;

			my $config = "\n# Begin Plugin::ClamAV\n";

			for my $paramName(
				qw /
					MilterSocket FixStaleSocket User AllowSupplementaryGroups ReadTimeout Foreground PidFile ClamdSocket
					OnClean OnInfected OnFail AddHeader LogSyslog LogFacility LogVerbose LogInfected LogClean MaxFileSize
					TemporaryDirectory LogFile LogTime LogFileUnlock LogFileMaxSize MilterSocketGroup MilterSocketMode
					RejectMsg
				/
			) {
				$config .= "$paramName $self->{'config'}->{$paramName}\n";
			}

			$config .= "# Ending Plugin::ClamAV\n";

			if ($fileContent =~ /^# Begin Plugin::ClamAV.*Ending Plugin::ClamAV\n/sm) {
				$fileContent =~ s/^\n# Begin Plugin::ClamAV.*Ending Plugin::ClamAV\n/$config/sm;
			} else {
				$fileContent .= $config;
			}
		} elsif($action eq 'remove') {
			$fileContent =~ s/^#$baseRegexp/$1/gm;
			$fileContent =~ s/^\n# Begin Plugin::ClamAV.*Ending Plugin::ClamAV\n//sm;
		}

		my $rs = $file->set($fileContent);
		return $rs if $rs;

		$file->save();
	} else {
		error('File /etc/clamav/clamav-milter.conf not found');
		return 1;
	}
}

=item _postfixConfig($action)

 Add or remove postfix configuration snippet for this plugin

 Param string $action Action to be performed ( add|remove )
 Return int 0 on success, other on failure

=cut

sub _postfixConfig
{
	my ($self, $action) = @_;

	my ($stdout, $stderr);
	my $rs = execute('postconf smtpd_milters non_smtpd_milters', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Extract postconf values
	s/^.*=\s*(.*)/$1/ for ( my @postconfValues = split "\n", $stdout );

	if($action eq 'add') {
		my @postconf = (
			# milter_default_action
			'milter_default_action=accept',

			# smtpd_milters
			($postconfValues[0] !~ /$self->{'config'}->{'PostfixMilterSocket'}/)
				? 'smtpd_milters=' . escapeShell("$postconfValues[0] $self->{'config'}->{'PostfixMilterSocket'}") : '',

			# non_smtpd_milters
			($postconfValues[1] !~ /\$smtpd_milters/)
				? 'non_smtpd_milters=' . escapeShell("$postconfValues[1] \$smtpd_milters") : ''
		);

		$rs = execute("postconf -e @postconf", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	} elsif($action eq 'remove') {
		$postconfValues[0] =~ s/\s*$self->{'config'}->{'PostfixMilterSocket'}//;
		$rs = execute('postconf -e smtpd_milters=' . escapeShell($postconfValues[0]), \$stdout, \$stderr);
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
	my $self = $_[0];

	my ($stdout, $stderr);
	my $rs = execute("$main::imscpConfig{'SERVICE_MNGR'} clamav-milter restart", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs;
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
