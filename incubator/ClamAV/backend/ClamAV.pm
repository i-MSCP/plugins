#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2014 by internet Multi Server Control Panel
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
# @category    iMSCP
# @package     iMSCP_Plugin
# @subpackage  ClamAV
# @copyright   Sascha Bay <info@space2place.de>
# @copyright   Rene Schuster <mail@reneschuster.de>
# @author      Sascha Bay <info@space2place.de>
# @author      Rene Schuster <mail@reneschuster.de>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Plugin::ClamAV;

use strict;
use warnings;

use iMSCP::Database;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::File;
use JSON;

use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP ClamAV plugin.

=head1 PUBLIC METHODS

=over 4

=item install()

 Perform install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = $_[0];

	if(! -x '/usr/sbin/clamd') {
		error('Unable to find clamav daemon. Please, install the clamav and clamav-daemon packages first.');
		return 1;
	}

	if(! -x '/usr/bin/freshclam') {
		error('Unable to find freshclam daemon. Please, install the clamav-freshclam package first.');
		return 1;
	}

	if(! -x '/usr/sbin/clamav-milter') {
		error('Unable to find clamav-milter daemon. Please, install the clamav-milter package first.');
		return 1;
	}

	$self->change();
}

=item change()

 Perform change tasks

 Return int 0 on success, other on failure

=cut

sub change
{
	my $self = $_[0];

	my $rs = $self->_modifyClamavMilterSystemConfig('add');
	return $rs if $rs;
	
	$self->_restartDaemon('clamav-milter', 'restart');
}

=item update()

 Perform update tasks

 Return int 0 on success, other on failure

=cut

sub update
{
	$_[0]->change();
}

=item enable()

 Perform enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = $_[0];

	my $rs = $self->_modifyPostfixMainConfig('add');
	return $rs if $rs;
	
	$self->_restartDaemonPostfix();
}

=item disable()

 Perform disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
	my $self = $_[0];

	my $rs = $self->_modifyPostfixMainConfig('remove');
	return $rs if $rs;

	$self->_restartDaemonPostfix();
}

=item uninstall()

 Perform uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = $_[0];

	my $rs = $self->_modifyClamavMilterSystemConfig('remove');
	return $rs if $rs;

	$self->_restartDaemon('clamav-milter', 'restart');
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize plugin

 Return Plugin::ClamAV

=cut

sub _init
{
	my $self = $_[0];

	# Force return value from plugin module
	$self->{'FORCE_RETVAL'} = 'yes';

	if($self->{'action'} ~~ ['install', 'uninstall', 'change', 'update', 'enable', 'disable']) {
		# Loading plugin configuration
		my $rdata = iMSCP::Database->factory()->doQuery(
			'plugin_name', 'SELECT plugin_name, plugin_config FROM plugin WHERE plugin_name = ?', 'ClamAV'
		);
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}

		$self->{'config'} = decode_json($rdata->{'ClamAV'}->{'plugin_config'});
	}

	$self;
}

=item _modifyClamavMilterSystemConfig($action)

 Modify clamav-milter system config file

 Return int 0 on success, other on failure

=cut

sub _modifyClamavMilterSystemConfig($$)
{
	my ($self, $action) = @_;

	my $file = iMSCP::File->new('filename' => '/etc/clamav/clamav-milter.conf');

	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read /etc/clamav/clamav-milter.conf");
		return 1;
	}

	if($action eq 'add') {
		$fileContent =~ s/^(MilterSocket.*)/#$1/gm;
		$fileContent =~ s/^(FixStaleSocket.*)/#$1/gm;
		$fileContent =~ s/^(User.*)/#$1/gm;
		$fileContent =~ s/^(AllowSupplementaryGroups.*)/#$1/gm;
		$fileContent =~ s/^(ReadTimeout.*)/#$1/gm;
		$fileContent =~ s/^(Foreground.*)/#$1/gm;
		$fileContent =~ s/^(PidFile.*)/#$1/gm;
		$fileContent =~ s/^(ClamdSocket.*)/#$1/gm;
		$fileContent =~ s/^(OnClean.*)/#$1/gm;
		$fileContent =~ s/^(OnInfected.*)/#$1/gm;
		$fileContent =~ s/^(OnFail.*)/#$1/gm;
		$fileContent =~ s/^(AddHeader.*)/#$1/gm;
		$fileContent =~ s/^(LogSyslog.*)/#$1/gm;
		$fileContent =~ s/^(LogFacility.*)/#$1/gm;
		$fileContent =~ s/^(LogVerbose.*)/#$1/gm;
		$fileContent =~ s/^(LogInfected.*)/#$1/gm;
		$fileContent =~ s/^(LogClean.*)/#$1/gm;
		$fileContent =~ s/^(LogRotate.*)/#$1/gm;
		$fileContent =~ s/^(MaxFileSize.*)/#$1/gm;
		$fileContent =~ s/^(SupportMultipleRecipients.*)/#$1/gm;
		$fileContent =~ s/^(TemporaryDirectory.*)/#$1/gm;
		$fileContent =~ s/^(LogFile.*)/#$1/gm;
		$fileContent =~ s/^(LogTime.*)/#$1/gm;
		$fileContent =~ s/^(LogFileUnlock.*)/#$1/gm;
		$fileContent =~ s/^(LogFileMaxSize.*)/#$1/gm;
		$fileContent =~ s/^(MilterSocketGroup.*)/#$1/gm;
		$fileContent =~ s/^(MilterSocketMode.*)/#$1/gm;

		my $clamavMilterSystemConfig = "\n# Begin Plugin::ClamAV\n";
		$clamavMilterSystemConfig .= "MilterSocket " . $self->{'config'}->{'MilterSocket'} ."\n";
		$clamavMilterSystemConfig .= "FixStaleSocket " . $self->{'config'}->{'FixStaleSocket'} ."\n";
		$clamavMilterSystemConfig .= "User " . $self->{'config'}->{'User'} ."\n";
		$clamavMilterSystemConfig .= "AllowSupplementaryGroups " . $self->{'config'}->{'AllowSupplementaryGroups'} ."\n";
		$clamavMilterSystemConfig .= "ReadTimeout " . $self->{'config'}->{'ReadTimeout'} ."\n";
		$clamavMilterSystemConfig .= "Foreground " . $self->{'config'}->{'Foreground'} ."\n";
		$clamavMilterSystemConfig .= "PidFile " . $self->{'config'}->{'PidFile'} ."\n";
		$clamavMilterSystemConfig .= "ClamdSocket " . $self->{'config'}->{'ClamdSocket'} ."\n";
		$clamavMilterSystemConfig .= "OnClean " . $self->{'config'}->{'OnClean'} ."\n";
		$clamavMilterSystemConfig .= "OnInfected " . $self->{'config'}->{'OnInfected'} ."\n";
		$clamavMilterSystemConfig .= "OnFail " . $self->{'config'}->{'OnFail'} ."\n";
		$clamavMilterSystemConfig .= "AddHeader " . $self->{'config'}->{'AddHeader'} ."\n";
		$clamavMilterSystemConfig .= "LogSyslog " . $self->{'config'}->{'LogSyslog'} ."\n";
		$clamavMilterSystemConfig .= "LogFacility " . $self->{'config'}->{'LogFacility'} ."\n";
		$clamavMilterSystemConfig .= "LogVerbose " . $self->{'config'}->{'LogVerbose'} ."\n";
		$clamavMilterSystemConfig .= "LogInfected " . $self->{'config'}->{'LogInfected'} ."\n";
		$clamavMilterSystemConfig .= "LogClean " . $self->{'config'}->{'LogClean'} ."\n";
		$clamavMilterSystemConfig .= "MaxFileSize " . $self->{'config'}->{'MaxFileSize'} ."\n";
		$clamavMilterSystemConfig .= "TemporaryDirectory " . $self->{'config'}->{'TemporaryDirectory'} ."\n";
		$clamavMilterSystemConfig .= "LogFile " . $self->{'config'}->{'LogFile'} ."\n";
		$clamavMilterSystemConfig .= "LogTime " . $self->{'config'}->{'LogTime'} ."\n";
		$clamavMilterSystemConfig .= "LogFileUnlock " . $self->{'config'}->{'LogFileUnlock'} ."\n";
		$clamavMilterSystemConfig .= "LogFileMaxSize " . $self->{'config'}->{'LogFileMaxSize'} ."\n";
		$clamavMilterSystemConfig .= "MilterSocketGroup " . $self->{'config'}->{'MilterSocketGroup'} ."\n";
		$clamavMilterSystemConfig .= "MilterSocketMode " . $self->{'config'}->{'MilterSocketMode'} ."\n";
		$clamavMilterSystemConfig .= "RejectMsg " . $self->{'config'}->{'RejectMsg'} ."\n";
		$clamavMilterSystemConfig .= "# Ending Plugin::ClamAV\n";

		if ($fileContent =~ /^# Begin Plugin::ClamAV.*Ending Plugin::ClamAV\n/sgm) {
			$fileContent =~ s/^\n# Begin Plugin::ClamAV.*Ending Plugin::ClamAV\n/$clamavMilterSystemConfig/sgm;
		} else {
			$fileContent .= "$clamavMilterSystemConfig";
		}
	} elsif($action eq 'remove') {
		$fileContent =~ s/^(#)(MilterSocket.*)/$2/gm;
		$fileContent =~ s/^(#)(FixStaleSocket.*)/$2/gm;
		$fileContent =~ s/^(#)(User.*)/$2/gm;
		$fileContent =~ s/^(#)(AllowSupplementaryGroups.*)/$2/gm;
		$fileContent =~ s/^(#)(ReadTimeout.*)/$2/gm;
		$fileContent =~ s/^(#)(Foreground.*)/$2/gm;
		$fileContent =~ s/^(#)(PidFile.*)/$2/gm;
		$fileContent =~ s/^(#)(ClamdSocket.*)/$2/gm;
		$fileContent =~ s/^(#)(OnClean.*)/$2/gm;
		$fileContent =~ s/^(#)(OnInfected.*)/$2/gm;
		$fileContent =~ s/^(#)(OnFail.*)/$2/gm;
		$fileContent =~ s/^(#)(AddHeader.*)/$2/gm;
		$fileContent =~ s/^(#)(LogSyslog.*)/$2/gm;
		$fileContent =~ s/^(#)(LogFacility.*)/$2/gm;
		$fileContent =~ s/^(#)(LogVerbose.*)/$2/gm;
		$fileContent =~ s/^(#)(LogInfected.*)/$2/gm;
		$fileContent =~ s/^(#)(LogClean.*)/$2/gm;
		$fileContent =~ s/^(#)(LogRotate.*)/$2/gm;
		$fileContent =~ s/^(#)(MaxFileSize.*)/$2/gm;
		$fileContent =~ s/^(#)(SupportMultipleRecipients.*)/$2/gm;
		$fileContent =~ s/^(#)(TemporaryDirectory.*)/$2/gm;
		$fileContent =~ s/^(#)(LogFile.*)/$2/gm;
		$fileContent =~ s/^(#)(LogTime.*)/$2/gm;
		$fileContent =~ s/^(#)(LogFileUnlock.*)/$2/gm;
		$fileContent =~ s/^(#)(LogFileMaxSize.*)/$2/gm;
		$fileContent =~ s/^(#)(MilterSocketGroup.*)/$2/gm;
		$fileContent =~ s/^(#)(MilterSocketMode.*)/$2/gm;
		$fileContent =~ s/^\n# Begin Plugin::ClamAV.*Ending Plugin::ClamAV\n//sgm;
	}

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _modifyPostfixMainConfig($action)

 Modify postfix main.cf config file

 Return int 0 on success, other on failure

=cut

sub _modifyPostfixMainConfig($$)
{
	my ($self, $action) = @_;

	my ($rs, $stdout, $stderr);
	
	$rs = execute('postconf smtpd_milters', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;
	
	my $smtpdMiltersOutput = $stdout;
	
	$rs = execute('postconf non_smtpd_milters', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;
	
	my $nonSmtpdMiltersOutput = $stdout;

	if($action eq 'add') {
		#Set milter_default_action to accept
		$rs = execute('postconf -e "milter_default_action = accept"', \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
		
		#Set smtpd_milters values
		$rs = execute('postconf -e "' . $smtpdMiltersOutput . ' ' . $self->{'config'}->{'PostfixMilterSocket'} . '"', \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
		
		#Set non_smtpd_milters value if not exist
		if($nonSmtpdMiltersOutput !~ /\$smtpd_milters/) {
			$rs = execute(sprintf('postconf -e "%s \$smtpd_milters"', $nonSmtpdMiltersOutput), \$stdout, \$stderr);
			#$rs = execute('postconf -e "non_smtpd_milters = \$smtpd_milters"', \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		}
			
	} elsif($action eq 'remove') {
		$smtpdMiltersOutput =~ s/\s?($self->{'config'}->{'PostfixMilterSocket'})//g;
		$rs = execute('postconf -e "' . $smtpdMiltersOutput . '"', \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	0;
}

=item _restartDaemon($daemon, $action)

 Restart the daemon

 Return int 0 on success, other on failure

=cut

sub _restartDaemon($$$)
{
	my ($self, $daemon, $action) = @_;

	my ($stdout, $stderr);
	my $rs = execute("umask 022; service $daemon $action", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs;
}

=item _restartDaemonPostfix()

 Restart the postfix daemon

 Return int 0 on success, other on failure

=cut

sub _restartDaemonPostfix
{
	require Servers::mta;
	Servers::mta->factory()->{'restart'} = 'yes';

	0;
}

=back

=head1 AUTHORS

 Sascha Bay <info@space2place.de>
 Rene Schuster <mail@reneschuster.de>

=cut

1;
