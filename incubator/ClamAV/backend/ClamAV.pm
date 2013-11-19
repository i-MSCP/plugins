#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2013 by internet Multi Server Control Panel
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
# @category    i-MSCP
# @package     iMSCP_Plugin
# @subpackage  ClamAV
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Sascha Bay <info@space2place.de>
# @contributor Rene Schuster <mail@reneschuster.de>
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
	my $self = shift;
	
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
	
	0;
}

=item enable()

 Perform enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = shift;
	
	my $rs = $self->_modifyClamavMilterDefaultConfig('add');
	return $rs if $rs;
	
	$rs = $self->_modifyClamavMilterSystemConfig('add');
	return $rs if $rs;
	
	$rs = $self->_restartDaemon('clamav-milter', 'restart');
	return $rs if $rs;
	
	$rs = $self->_modifyPostfixMainConfig('add');
	return $rs if $rs;
	
	$rs = $self->_restartDaemonPostfix();
	return $rs if $rs;
	
	0;
}

=item disable()

 Perform disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
	my $self = shift;
	
	my $rs = $self->_modifyPostfixMainConfig('remove');
	return $rs if $rs;
	
	$rs = $self->_restartDaemonPostfix();
	return $rs if $rs;
	
	0;
}

=item uninstall()

 Perform uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;
		
	my $rs = $self->_modifyClamavMilterDefaultConfig('remove');
	return $rs if $rs;
	
	$rs = $self->_modifyClamavMilterSystemConfig('remove');
	return $rs if $rs;
	
	$rs = $self->_restartDaemon('clamav-milter', 'restart');
	return $rs if $rs;
	
	0;
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
	my $self = shift;
	
	# Force return value from plugin module
	$self->{'FORCE_RETVAL'} = 'yes';
	
	$self;
}

=item _modifyClamavMilterDefaultConfig($action)

 Modify clamav-milter default config file

 Return int 0 on success, other on failure

=cut

sub _modifyClamavMilterDefaultConfig($$)
{
	my ($self, $action) = @_;
	
	my $file = iMSCP::File->new('filename' => '/etc/default/clamav-milter');
	
	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read /etc/default/clamav-milter");
		return 1;
	}
	
	my $clamavMilterSocketConfig = "\n# Begin Plugin::ClamAV\n";
	$clamavMilterSocketConfig .= "SOCKET_RWGROUP=postfix\n";
	$clamavMilterSocketConfig .= "# Ending Plugin::ClamAV\n";
	
	if($action eq 'add') {
		if ($fileContent =~ /^# Begin Plugin::ClamAV.*Ending Plugin::ClamAV\n/sgm) {
			$fileContent =~ s/^\n# Begin Plugin::ClamAV.*Ending Plugin::ClamAV\n/$clamavMilterSocketConfig/sgm;
		} else {
			$fileContent .= "$clamavMilterSocketConfig";
		}
	} elsif($action eq 'remove') {
		$fileContent =~ s/^\n# Begin Plugin::ClamAV.*Ending Plugin::ClamAV\n//sgm;
	}
	
	my $rs = $file->set($fileContent);
	return $rs if $rs;
	
	$rs = $file->save();
	return $rs if $rs;
	
	0;
}

=item _modifyClamavMilterSystemConfig($action)

 Modify clamav-milter system config file

 Return int 0 on success, other on failure

=cut

sub _modifyClamavMilterSystemConfig($$)
{
	my ($self, $action) = @_;
	
	my $rdata = iMSCP::Database->factory->doQuery(
		'plugin_name', 'SELECT `plugin_name`, `plugin_config` FROM `plugin` WHERE `plugin_name` = ?', 'ClamAV'
	);
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	require JSON;
	JSON->import();
	
	my $clamavMilterConfig = decode_json($rdata->{'ClamAV'}->{'plugin_config'});
	
	my $file = iMSCP::File->new('filename' => '/etc/clamav/clamav-milter.conf');
	
	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read /etc/clamav/clamav-milter.conf");
		return 1;
	}
	
	my $clamavMilterSystemConfig = "\n# Begin Plugin::ClamAV\n";
	$clamavMilterSystemConfig .= "MilterSocket " . $clamavMilterConfig->{'MilterSocket'} ."\n";
	$clamavMilterSystemConfig .= "FixStaleSocket " . $clamavMilterConfig->{'FixStaleSocket'} ."\n";
	$clamavMilterSystemConfig .= "User " . $clamavMilterConfig->{'User'} ."\n";
	$clamavMilterSystemConfig .= "AllowSupplementaryGroups " . $clamavMilterConfig->{'AllowSupplementaryGroups'} ."\n";
	$clamavMilterSystemConfig .= "ReadTimeout " . $clamavMilterConfig->{'ReadTimeout'} ."\n";
	$clamavMilterSystemConfig .= "Foreground " . $clamavMilterConfig->{'Foreground'} ."\n";
	$clamavMilterSystemConfig .= "PidFile " . $clamavMilterConfig->{'PidFile'} ."\n";
	$clamavMilterSystemConfig .= "ClamdSocket " . $clamavMilterConfig->{'ClamdSocket'} ."\n";
	$clamavMilterSystemConfig .= "OnClean " . $clamavMilterConfig->{'OnClean'} ."\n";
	$clamavMilterSystemConfig .= "OnInfected " . $clamavMilterConfig->{'OnInfected'} ."\n";
	$clamavMilterSystemConfig .= "OnFail " . $clamavMilterConfig->{'OnFail'} ."\n";
	$clamavMilterSystemConfig .= "AddHeader " . $clamavMilterConfig->{'AddHeader'} ."\n";
	$clamavMilterSystemConfig .= "LogSyslog " . $clamavMilterConfig->{'LogSyslog'} ."\n";
	$clamavMilterSystemConfig .= "LogFacility " . $clamavMilterConfig->{'LogFacility'} ."\n";
	$clamavMilterSystemConfig .= "LogVerbose " . $clamavMilterConfig->{'LogVerbose'} ."\n";
	$clamavMilterSystemConfig .= "LogInfected " . $clamavMilterConfig->{'LogInfected'} ."\n";
	$clamavMilterSystemConfig .= "LogClean " . $clamavMilterConfig->{'LogClean'} ."\n";
	$clamavMilterSystemConfig .= "MaxFileSize " . $clamavMilterConfig->{'MaxFileSize'} ."\n";
	$clamavMilterSystemConfig .= "TemporaryDirectory " . $clamavMilterConfig->{'TemporaryDirectory'} ."\n";
	$clamavMilterSystemConfig .= "LogFile " . $clamavMilterConfig->{'LogFile'} ."\n";
	$clamavMilterSystemConfig .= "LogTime " . $clamavMilterConfig->{'LogTime'} ."\n";
	$clamavMilterSystemConfig .= "LogFileUnlock " . $clamavMilterConfig->{'LogFileUnlock'} ."\n";
	$clamavMilterSystemConfig .= "LogFileMaxSize " . $clamavMilterConfig->{'LogFileMaxSize'} ."\n";
	$clamavMilterSystemConfig .= "MilterSocketGroup " . $clamavMilterConfig->{'MilterSocketGroup'} ."\n";
	$clamavMilterSystemConfig .= "MilterSocketMode " . $clamavMilterConfig->{'MilterSocketMode'} ."\n";
	$clamavMilterSystemConfig .= "RejectMsg " . $clamavMilterConfig->{'RejectMsg'} ."\n";
	$clamavMilterSystemConfig .= "# Ending Plugin::ClamAV\n";
	
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
		$fileContent =~ s/^(MaxFileSize.*)/#$1/gm;
		$fileContent =~ s/^(TemporaryDirectory.*)/#$1/gm;
		$fileContent =~ s/^(LogFile.*)/#$1/gm;
		$fileContent =~ s/^(LogTime.*)/#$1/gm;
		$fileContent =~ s/^(LogFileUnlock.*)/#$1/gm;
		$fileContent =~ s/^(LogFileMaxSize.*)/#$1/gm;
		$fileContent =~ s/^(MilterSocketGroup.*)/#$1/gm;
		$fileContent =~ s/^(MilterSocketMode.*)/#$1/gm;
		
		if ($fileContent =~ /^# Begin Plugin::ClamAV.*Ending Plugin::ClamAV\n/sgm) {
			$fileContent =~ s/^\n# Begin Plugin::ClamAV.*Ending Plugin::ClamAV\n/$clamavMilterSystemConfig/sgm;
		} else {
			$fileContent .= "$clamavMilterSystemConfig";
		}
	}
	elsif($action eq 'remove') {
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
		$fileContent =~ s/^(#)(MaxFileSize.*)/$2/gm;
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
	
	$rs = $file->save();
	return $rs if $rs;
	
	0;
}

=item _modifyPostfixMainConfig($action)

 Modify postfix main.cf config file

 Return int 0 on success, other on failure

=cut

sub _modifyPostfixMainConfig($$)
{
	my ($self, $action) = @_;
	
	my @miltersValues;
	my $postfixClamavConfig;
	
	my $file = iMSCP::File->new('filename' => '/etc/postfix/main.cf');
	
	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read /etc/postfix/main.cf");
		return 1;
	}
	
	my ($stdout, $stderr);
	my $rs = execute('postconf smtpd_milters', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;
	
	if($action eq 'add') {
		$stdout =~ /^smtpd_milters\s?=\s?(.*)/gm;
		@miltersValues = split(' ', $1);
		
		if(scalar @miltersValues >= 1) {
			$fileContent =~ s/^\t# Begin Plugin::ClamAV.*Ending Plugin::ClamAV\n//sgm;
		
			$postfixClamavConfig = "\n\t# Begin Plugin::ClamAV\n";
			$postfixClamavConfig .= "\tunix:/clamav/clamav-milter.ctl\n";
			$postfixClamavConfig .= "\t# Ending Plugin::ClamAV";
			
			$fileContent =~ s/^(smtpd_milters.*)/$1$postfixClamavConfig/gm;
		} else {
			$postfixClamavConfig = "\n# Begin Plugins::i-MSCP\n";
			$postfixClamavConfig .= "milter_default_action = accept\n";
			$postfixClamavConfig .= "smtpd_milters = \n";
			$postfixClamavConfig .= "\t# Begin Plugin::ClamAV\n";
			$postfixClamavConfig .= "\tunix:/clamav/clamav-milter.ctl\n";
			$postfixClamavConfig .= "\t# Ending Plugin::ClamAV\n";
			$postfixClamavConfig .= "non_smtpd_milters = \$smtpd_milters\n";
			$postfixClamavConfig .= "# Ending Plugins::i-MSCP\n";
			
			$fileContent .= "$postfixClamavConfig";
		}
	} elsif($action eq 'remove') {
		$stdout =~ /^smtpd_milters\s?=\s?(.*)/gm;
		@miltersValues = split(/\s+/, $1);
		
		if(scalar @miltersValues > 1) {
			$fileContent =~ s/^\t# Begin Plugin::ClamAV.*Ending Plugin::ClamAV\n//sgm;
		} elsif(! $fileContent =~ /^\t# Begin Plugin::ClamAV.*Ending Plugin::ClamAV\n/sgm) {
			$fileContent =~ s/^\n# Begin Plugins::i-MSCP.*Ending Plugins::i-MSCP\n//sgm;
		}
	}
	
	$rs = $file->set($fileContent);
	return $rs if $rs;
	
	$rs = $file->save();
	return $rs if $rs;
	
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
	my $rs = execute("service $daemon $action", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;
	
	0;
}

=item _restartDaemonPostfix()

 Restart the postfix daemon

 Return int 0 on success, other on failure

=cut

sub _restartDaemonPostfix
{
	my $self = shift;
	
	require Servers::mta;
	
	my $mta = Servers::mta->factory();
	my $rs = $mta->restart();
	return $rs if $rs;
	
	0;
}

=back

=head1 AUTHORS AND CONTRIBUTORS

 - Sascha Bay <info@space2place.de> (Author)
 - Rene Schuster <mail@reneschuster.de> (Contributor)

=cut

1;
