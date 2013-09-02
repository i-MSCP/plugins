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
# @subpackage  JailKit
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Sascha Bay <info@space2place.de>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Plugin::JailKit;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::Database;

use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Plugin allows i-MSCP accounts to create chroot ssh logins.

=head1 PUBLIC METHODS

=over 4

=item install()

 Perform install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;
	
	my $rs = 0;

	if(! -x '/usr/sbin/jk_init') {
		$rs = $self->_installJailKitPackage();
		return $rs if $rs;
	}
	
	$rs = $self->_checkRequirements();
	return $rs if $rs;
	
	$rs = $self->_copyJailKitConfigFiles();
	return $rs if $rs;
	
	$self->_restartDaemonJailKit();
}

=item change()

 Perform change tasks

 Return int 0 on success, other on failure

=cut

sub change
{
	my $self = shift;
	
	0;
}

=item update()

 Perform update tasks

 Return int 0 on success, other on failure

=cut

sub update
{
	my $self = shift;
	
	my $db = iMSCP::Database->factory();
	
	my $rdata = $db->doQuery(
		'plugin_name', 'SELECT `plugin_name`, `plugin_config` FROM `plugin` WHERE `plugin_name` = ?', 'JailKit'
	);
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	require JSON;
	JSON->import();

	my $jailkitConfig = decode_json($rdata->{'JailKit'}->{'plugin_config'});
	
	my $rs = $self->_copyJailKitConfigFiles();
	return $rs if $rs;
	
	# Add jail to /etc/jailkit/jk_socketd.ini
	$rs = $self->_addJailsToJkSockettd(
		$jailkitConfig->{'jailfolder'}, $jailkitConfig->{'jail_sockettd_base'}, $jailkitConfig->{'jail_sockettd_peak'},
		$jailkitConfig->{'jail_sockettd_interval'}
	);
	return $rs if $rs;
	
	$self->_restartDaemonJailKit();
}

=item enable()

 Perform enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = shift;
	
	$self->_changeAllJailKitSshLogins('unlock');
}

=item disable()

 Perform disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
	my $self = shift;
	
	$self->_changeAllJailKitSshLogins('lock');
}

=item uninstall()

 Perform uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;
	
	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery(
		'plugin_name', 'SELECT `plugin_name`, `plugin_config` FROM `plugin` WHERE `plugin_name` = ?', 'JailKit'
	);
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	require JSON;
	JSON->import();

	my $jailkitConfig = decode_json($rdata->{'JailKit'}->{'plugin_config'});
	
	my $rs = iMSCP::Dir->new('dirname' => $jailkitConfig->{'jailfolder'})->remove() if -d $jailkitConfig->{'jailfolder'};
	return $rs if $rs;
}

=item run()

 Create new entry for the jailkit

 Return int 0 on success, other on failure

=cut

sub run
{
	my $self = shift;
	
	my $rs = 0;
	
	my @sql;
	
	my $db = iMSCP::Database->factory();
	
	my $rdata = $db->doQuery(
		'plugin_name', 'SELECT `plugin_name`, `plugin_config` FROM `plugin` WHERE `plugin_name` = ?', 'JailKit'
	);
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	require JSON;
	JSON->import();

	my $jailkitConfig = decode_json($rdata->{'JailKit'}->{'plugin_config'});
	
	# Add or remove domain ssh jail
	$rdata = $db->doQuery(
		'jailkit_id', 
		"
			SELECT
				`jailkit_id`, `domain_id`,
				`domain_name`, `jailkit_status`
			FROM
				`jailkit`
			WHERE
				`jailkit_status` IN('toadd', 'todelete')
			ORDER BY
				`domain_id` ASC
		"
	);
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	if(%{$rdata}) {
		for(keys %{$rdata}) {
			if($rdata->{$_}->{'jailkit_status'} eq 'toadd') {
				$rs = $self->_addDomainSshJail(
					$jailkitConfig->{'jailfolder'}, $jailkitConfig->{'default_jail_apps'}, $rdata->{$_}->{'domain_name'}
				);

				@sql = (
					'UPDATE `jailkit` SET `jailkit_status` = ? WHERE `jailkit_id` = ?',
					($rs ? scalar getMessageByType('error') : 'ok'), $rdata->{$_}->{'jailkit_id'}
				);
			} elsif($rdata->{$_}->{'jailkit_status'} eq 'todelete') {
				$rs = $self->_deleteDomainSshJail(
					$jailkitConfig->{'jailfolder'}, $rdata->{$_}->{'domain_name'}, $rdata->{$_}->{'domain_id'}
				);
				if($rs) {
					@sql = (
						'UPDATE `jailkit` SET `jailkit_status` = ? WHERE `jailkit_id` = ?',
						scalar getMessageByType('error'), $rdata->{$_}->{'jailkit_id'}
					);
				} else {
					@sql = ('DELETE FROM `jailkit` WHERE `jailkit_id` = ?', $rdata->{$_}->{'jailkit_id'});
				}
			}

			my $rdata2 = $db->doQuery('dummy', @sql);
			
			unless(ref $rdata2 eq 'HASH') {
				error($rdata2);
				return 1;
			}
		}
		
		# JailKit daemon must be restartet
		$rs = $self->_restartDaemonJailKit();
		return $rs if $rs;
	}
	
	# Add, change or remove a ssh login of a domain jail
	my $rdata = $db->doQuery(
		'jailkit_login_id', 
		"
			SELECT
				`t1`.`jailkit_login_id`, `t1`.`domain_id`, `t1`.`ssh_login_name`,
				`t1`.`ssh_login_pass`, `t1`.`ssh_login_sys_uid`, `t1`.`ssh_login_sys_gid`,
				`t1`.`ssh_login_locked`, `t1`.`jailkit_login_status`, `t2`.`domain_name`
			FROM
				`jailkit_login` AS `t1`
			LEFT JOIN
				`jailkit` AS `t2` ON(`t2`.`domain_id` = `t1`.`domain_id`)
			WHERE
				`t1`.`jailkit_login_status` IN('toadd', 'tochange', 'todelete')
			ORDER BY
				`t1`.`domain_id` ASC
		"
	);
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	if(%{$rdata}) {
		for(keys %{$rdata}) {
			if($rdata->{$_}->{'jailkit_login_status'} eq 'toadd') {
				if($rdata->{$_}->{'ssh_login_pass'} eq '') {
					error('Empty passwords not allowed for login: ' . $rdata->{$_}->{'ssh_login_name'} . '!');
					return 1;
				}
				$rs = $self->_addSshLoginToDomainJail(
					$jailkitConfig->{'jailfolder'}, $jailkitConfig->{'default_shell'}, $rdata->{$_}->{'domain_name'}, 
					$rdata->{$_}->{'ssh_login_name'}, $rdata->{$_}->{'ssh_login_pass'}, $rdata->{$_}->{'ssh_login_sys_uid'},
					$rdata->{$_}->{'ssh_login_sys_gid'}
				);

				@sql = (
					'UPDATE `jailkit_login` SET `jailkit_login_status` = ? WHERE `jailkit_login_id` = ?',
					($rs ? scalar getMessageByType('error') : 'ok'), $rdata->{$_}->{'jailkit_login_id'}
				);
			} elsif($rdata->{$_}->{'jailkit_login_status'} eq 'tochange') {
				if($rdata->{$_}->{'ssh_login_pass'} eq '') {
					error('Empty passwords not allowed for login: ' . $rdata->{$_}->{'ssh_login_name'} . '!');
					return 1;
				}
				$rs = $self->_changeJailKitSshLogin(
					$rdata->{$_}->{'ssh_login_name'}, $rdata->{$_}->{'ssh_login_pass'}, ($rdata->{$_}->{'ssh_login_locked'} eq '0')  ? 'unlock' : 'lock'
				);
				
				@sql = (
					'UPDATE `jailkit_login` SET `jailkit_login_status` = ? WHERE `jailkit_login_id` = ?',
					($rs ? scalar getMessageByType('error') : 'ok'), $rdata->{$_}->{'jailkit_login_id'}
				);
			} elsif($rdata->{$_}->{'jailkit_login_status'} eq 'todelete') {
				$rs = $self->_removeSshLoginFromDomainJail(
					$jailkitConfig->{'jailfolder'}, $rdata->{$_}->{'domain_name'}, $rdata->{$_}->{'ssh_login_name'}
				);
				if($rs) {
					@sql = (
						'UPDATE `jailkit_login` SET `jailkit_login_status` = ? WHERE `jailkit_login_id` = ?',
						scalar getMessageByType('error'), $rdata->{$_}->{'jailkit_login_id'}
					);
				} else {
					@sql = ('DELETE FROM `jailkit_login` WHERE `jailkit_login_id` = ?', $rdata->{$_}->{'jailkit_login_id'});
				}
			}

			my $rdata2 = $db->doQuery('dummy', @sql);
			
			unless(ref $rdata2 eq 'HASH') {
				error($rdata2);
				return 1;
			}
		}
		
		# Adding all mount binds to fstab
		$rs = $self->_addWebfolderMountToFstab($jailkitConfig->{'jailfolder'});
		return $rs if $rs;
		
		# Add jail to /etc/jailkit/jk_socketd.ini
		$rs = $self->_addJailsToJkSockettd(
			$jailkitConfig->{'jailfolder'}, $jailkitConfig->{'jail_sockettd_base'}, $jailkitConfig->{'jail_sockettd_peak'},
			$jailkitConfig->{'jail_sockettd_interval'}
		);
		return $rs if $rs;
		
		# JailKit daemon must be restartet
		$rs = $self->_restartDaemonJailKit();
		return $rs if $rs;
	}
	
	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _addDomainSshJail()

 Add domain to a new ssh jail

 Return int 0 on success, other on failure

=cut

sub _addDomainSshJail
{
	my $self = shift;
	
	my $jailFolder = shift;
	my $defaultJailApps = shift;
	my $domainName = shift;
	
	my $rs = 0;
	my ($stdout, $stderr);
	
	# Add jail to new domain
	my $JailApps;
	
	while (my($defaultJailAppsKey, $defaultJailAppsValue) = each($defaultJailApps)) {
		$JailApps .= $defaultJailAppsValue . " ";
	}
	
	$rs = execute('/usr/sbin/jk_init -f -k -c /etc/jailkit/jk_init.ini -j ' . $jailFolder . '/' . $domainName . ' ' . $JailApps, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	if(! -d $jailFolder . '/' . $domainName . '/tmp') {
		my $rs = iMSCP::Dir->new('dirname' => $jailFolder . '/' . $domainName . '/tmp')->make(
			{ 'user' => 'root', 'group' => 'root', 'mode' => 0777 }
		);
		return $rs if $rs;
	}
	
	# Is needed for MySQL connect
	if(! -d $jailFolder . '/' . $domainName . '/var/run/mysqld') {
		my $rs = iMSCP::Dir->new('dirname' => $jailFolder . '/' . $domainName . '/var/run/mysqld')->make(
			{ 'user' => 'root', 'group' => 'root', 'mode' => 0750 }
		);
		return $rs if $rs;
	}
	
	if(-e '/var/run/mysqld/mysqld.sock') {
		$rs = execute("$main::imscpConfig{'CMD_LN'} -s /var/run/mysqld/mysqld.sock " . $jailFolder . "/" . $domainName . "/var/run/mysqld/mysqld.sock", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
	}
}

=item _deleteDomainSshJail()

 Add domain to a new ssh jail

 Return int 0 on success, other on failure

=cut

sub _deleteDomainSshJail
{
	my $self = shift;
	
	my $jailFolder = shift;
	my $domainName = shift;
	my $domainId = shift;
	
	my $rs = 0;
	my ($stdout, $stderr);
	
	my $db = iMSCP::Database->factory();
	
	# Remove domain jail folder
	my $jailFolderDomain = $jailFolder . '/' . $domainName;
	$rs = iMSCP::Dir->new('dirname' => $jailFolderDomain)->remove() if -d $jailFolderDomain;
	return $rs if $rs;
	
	my $rdata = $db->doQuery(
		'jailkit_login_id', 
		'
			SELECT
				`jailkit_login_id`, `domain_id`, `ssh_login_name`,
			FROM
				`jailkit_login`
			WHERE
				`domain_id` = ?
		', $domainId
	);
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	if(%{$rdata}) {
		for(keys %{$rdata}) {
			$rs = execute("$main::imscpConfig{'CMD_USERDEL'} -f " . $rdata->{$_}->{'ssh_login_name'}, \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
		}
	}	
}

=item _addSshLoginToDomainJail()

 Add a new ssh login to domain ssh jail

 Return int 0 on success, other on failure

=cut

sub _addSshLoginToDomainJail
{
	my $self = shift;
	
	my $jailFolder = shift;
	my $jailDefaultShell = shift;
	my $domainName = shift;
	my $sshLoginName = shift;
	my $sshLoginPass = shift;
	my $sshLoginSysUid = shift;
	my $sshLoginSysGid = shift;
	
	my $rs = 0;
	my ($stdout, $stderr);
	
	$rs = execute("$main::imscpConfig{'CMD_USERADD'} -u " . $sshLoginSysUid . " -g " . $sshLoginSysGid . " -o -m " . $sshLoginName, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	# Set new Password
	$rs = execute("$main::imscpConfig{'CMD_ECHO'} -e \"" . $sshLoginPass . "\n" . $sshLoginPass ."\" | /usr/bin/passwd " .$sshLoginName, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	# Add the user to the jail
	$rs = execute('/usr/sbin/jk_jailuser -m -n -s ' . $jailDefaultShell . ' -j ' . $jailFolder . '/' . $domainName . ' ' . $sshLoginName, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	# Add webfolder dir in home folder of the new user
	if(! -d $jailFolder . '/' . $domainName . '/home/' . $sshLoginName . '/webfolder') {
		my $rs = iMSCP::Dir->new('dirname' => $jailFolder . '/' . $domainName . '/home/' . $sshLoginName . '/webfolder')->make(
			{ 'user' => 'root', 'group' => 'root', 'mode' => 0750 }
		);
		return $rs if $rs;
	}
	
	# Mount (bind) virtual web dir to jail user home webfolder
	$rs = execute("/bin/mount $main::imscpConfig{'USER_WEB_DIR'}/" . $domainName . " " . $jailFolder . "/" . $domainName . "/home/" . $sshLoginName . "/webfolder -o bind", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
}

=item _changeJailKitSshLogin()

 Changes one JailKit SSH Login

 Return int 0 on success, other on failure

=cut

sub _changeJailKitSshLogin
{
	my $self = shift;
	
	my $sshLoginName = shift;
	my $sshLoginPass = shift;
	my $action = shift;
	
	my $rs = 0;
	my ($stdout, $stderr);
	
	# New Password
	$rs = execute("$main::imscpConfig{'CMD_ECHO'} -e \"" . $sshLoginPass . "\n" . $sshLoginPass ."\" | /usr/bin/passwd " .$sshLoginName, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	if($action eq 'lock') {
		$rs = execute('/usr/bin/passwd ' . $sshLoginName . ' -l', \$stdout, \$stderr); # Using passwd because usermod gives no output
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
				
		return $rs if $rs;
	}
	elsif($action eq 'unlock') {
		$rs = execute('/usr/bin/passwd ' . $sshLoginName . ' -u', \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
				
		return $rs if $rs;
	}
}

=item _removeSshLoginFromDomainJail()

 Remove ssh login from domain ssh jail

 Return int 0 on success, other on failure

=cut

sub _removeSshLoginFromDomainJail
{
	my $self = shift;
	
	my $jailFolder = shift;
	my $domainName = shift;
	my $sshLoginName = shift;
	
	my $rs = 0;
	my ($stdout, $stderr);
	
	$rs = execute("$main::imscpConfig{'CMD_USERDEL'} -f " . $sshLoginName, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	# Remove home folder from jail
	my $homeFolder = $jailFolder . '/' . $domainName . '/home/' . $sshLoginName;
	$rs = iMSCP::Dir->new('dirname' => $homeFolder)->remove() if -d $homeFolder;
	return $rs if $rs;
	
	# Remove ssh login from jail passwd
	my $jailPasswd = $jailFolder . '/' . $domainName . '/etc/passwd';
	my $file = iMSCP::File->new('filename' => $jailPasswd);
	
	my $fileContent = $file->get();
	return $fileContent if ! $fileContent;
	
	if ($fileContent =~ /^$sshLoginName:/sgm) {
		$fileContent =~ s/^$sshLoginName:.*$//gm;
		$fileContent =~ s/^\n//gm;
	}
	
	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _changeAllJailKitSshLogins()

 Changes all JailKit SSH Logins

 Return int 0 on success, other on failure

=cut

sub _changeAllJailKitSshLogins
{
	my $self = shift;
	my $action = shift;
	
	my $rs = 0;
	my ($stdout, $stderr);
	
	my $db = iMSCP::Database->factory();
	
	my $rdata = $db->doQuery(
		'jailkit_login_id', 
		"
			SELECT
				`jailkit_login_id`, `ssh_login_name`,
				`ssh_login_locked`
			FROM
				`jailkit_login`
			WHERE
				`jailkit_login_status` = 'ok'
		"
	);
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	if(%{$rdata}) {
		for(keys %{$rdata}) {
		
			my $sshLoginName = $rdata->{$_}->{'domain_name'};
			my $sshLoginLocked = $rdata->{$_}->{'ssh_login_locked'};
			my $jailKitLoginId = $rdata->{$_}->{'jailkit_login_id'};
			
			if($action eq 'lock') {
				$rs = execute('/usr/bin/passwd ' . $sshLoginName . ' -l', \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
				
				return $rs if $rs;
			}
			elsif($action eq 'unlock' && $sshLoginLocked == 0) {
				$rs = execute('/usr/bin/passwd ' . $sshLoginName . ' -u', \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
				
				return $rs if $rs;
			}
		}
	}
	
	0;
}

=item _addWebfolderMountToFstab()

 Add all mount binds to fstab

 Return int 0 on success, other on failure

=cut

sub _addWebfolderMountToFstab
{
	my $self = shift;
	my $jailFolder = shift;
	
	my $fstabMountBindConfig;
	
	my $db = iMSCP::Database->factory();
	
	my $rdata = $db->doQuery(
		'jailkit_login_id', 
		"
			SELECT
				`t1`.`domain_id`, `t1`.`ssh_login_name`, `t2`.`domain_name`
			FROM
				`jailkit_login` AS `t1`
			LEFT JOIN
				`jailkit` AS `t2` ON(`t2`.`domain_id` = `t1`.`domain_id`)
			WHERE
				`t1`.`jailkit_login_status` = 'ok'
			ORDER BY
				`t1`.`domain_id` ASC
		"
	);
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	

	if(%{$rdata}) {
		$fstabMountBindConfig = "# Start Added by Plugins::JailKit\n";
		for(keys %{$rdata}) {
			$fstabMountBindConfig .= $main::imscpConfig{'USER_WEB_DIR'} . "/" . $rdata->{$_}->{'domain_name'} . " " . $jailFolder . "/" . $rdata->{$_}->{'domain_name'} . "/home/" . $rdata->{$_}->{'ssh_login_name'} . "/webfolder none bind 0 0\n";
		}
		$fstabMountBindConfig .= "# Added by Plugins::JailKit End\n";
	}
	
	
	
	my $file = iMSCP::File->new('filename' => '/etc/fstab');
	
	my $fileContent = $file->get();
	return $fileContent if ! $fileContent;
	
	if ($fileContent =~ /^# Start Added by Plugins.*End\n/sgm) {
		$fileContent =~ s/^# Start Added by Plugins.*End\n/$fstabMountBindConfig/sgm;
	} else {
		$fileContent .= "$fstabMountBindConfig";
	}
	
	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _addJailsToJkSockettd()

 Add the existing jails to the /etc/jailkit/jk_socketd.ini

 Return int 0 on success, other on failure

=cut

sub _addJailsToJkSockettd
{
	my $self = shift;
	my $jailFolder = shift;
	my $jailSockettdBase = shift;
	my $jailSockettdPeak = shift;
	my $jailSockettdInterval = shift;
	
	my $jkSockettdEntries;
	
	my $db = iMSCP::Database->factory();
	
	my $rdata = $db->doQuery(
		'jailkit_id', 
		"
			SELECT
				`jailkit_id`, `domain_id`,
				`domain_name`, `jailkit_status`
			FROM
				`jailkit`
			WHERE
				`jailkit_status` = 'ok'
			ORDER BY
				`domain_id` ASC
		"
	);
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	

	if(%{$rdata}) {
		$jkSockettdEntries = "# Start Added by Plugins::JailKit\n";
		for(keys %{$rdata}) {
			$jkSockettdEntries .= '[' . $jailFolder . '/' . $rdata->{$_}->{'domain_name'} . '/dev/log]';
			$jkSockettdEntries .= 'base=' . $jailSockettdBase;
			$jkSockettdEntries .= 'peak=' . $jailSockettdPeak;
			$jkSockettdEntries .= 'interval=' . $jailSockettdInterval;
			$jkSockettdEntries .= '';
		}
		$jkSockettdEntries .= "# Added by Plugins::JailKit End\n";
	}
	
	my $file = iMSCP::File->new('filename' => '/etc/jailkit/jk_socketd.ini');
	
	my $fileContent = $file->get();
	return $fileContent if ! $fileContent;
	
	if ($fileContent =~ /^# Start Added by Plugins.*End\n/sgm) {
		$fileContent =~ s/^# Start Added by Plugins.*End\n/$jkSockettdEntries/sgm;
	} else {
		$fileContent .= "$jkSockettdEntries";
	}
	
	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _restartDaemonJailKit()

 Restart the JailKit daemon

 Return int 0 on success, other on failure

=cut

sub _restartDaemonJailKit
{
	my $self = shift;
	
	my ($stdout, $stderr);
	
	my $rs = execute('service jailkit restart', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	return $rs if $rs;
}

=item _checkRequirements

 Check requirements for jailkit plugin

 Return int 0 if all requirements are meet, 1 otherwise

=cut

sub _checkRequirements
{
	my $self = shift;	
	
	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery(
		'plugin_name', 'SELECT `plugin_name`, `plugin_config` FROM `plugin` WHERE `plugin_name` = ?', 'JailKit'
	);
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	require JSON;
	JSON->import();

	my $jailkitConfig = decode_json($rdata->{'JailKit'}->{'plugin_config'});
	
	if(! -d $jailkitConfig->{'jailfolder'}) {
		my $rs = iMSCP::Dir->new('dirname' => $jailkitConfig->{'jailfolder'})->make(
			{ 'user' => 'root', 'group' => 'root', 'mode' => 0750 }
		);
		return $rs if $rs;
	}
}

=item _copyJailKitConfigFiles

 Copies the preconfigured configfiles to /etc/jailkit

 Return int 0 if all requirements are meet, 1 otherwise

=cut

sub _copyJailKitConfigFiles
{
	my $self = shift;
	
	my ($stdout, $stderr);
	
	my $rs = execute("$main::imscpConfig{'CMD_CP'} -f $main::imscpConfig{'GUI_ROOT_DIR'}/plugins/JailKit/installation/jailkit-config/* /etc/jailkit/", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;
}

=item _installJailKitPackage

 Installs the debian jailkit package

 Return int 0 if all requirements are meet, 1 otherwise

=cut

sub _installJailKitPackage
{
	my $self = shift;
	
	my $rs = 0;
	my ($stdout, $stderr);
	
	my $jailKitAmd64 = $main::imscpConfig{'GUI_ROOT_DIR'} . '/plugins/JailKit/installation/jailkit_2.16-1_amd64.deb';
	my $jailKitI386 = $main::imscpConfig{'GUI_ROOT_DIR'} . '/plugins/JailKit/installation/jailkit_2.16-1_i386.deb';
	
	$rs = execute('/bin/uname -m', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	if($stdout =~ /^i\d+/) {
		$rs = execute('/usr/bin/dpkg -i ' . $jailKitI386, \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
	} else {
		$rs = execute('/usr/bin/dpkg -i ' . $jailKitAmd64, \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
	}
}

=back

=head1 AUTHORS AND CONTRIBUTORS

 Sascha Bay <info@space2place.de>

=cut

1;