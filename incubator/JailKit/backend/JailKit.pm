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
	
	# Renew all mount binds to fstab after update
	$rs = $self->_addWebfolderMountToFstab($jailkitConfig->{'jailfolder'});
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
	
	my $rs = 0;
	my ($stdout, $stderr);
	
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
	
	# Remove all usernames from /etc/passwd and unmount the webfolder
	$rdata = $db->doQuery(
		'jailkit_login_id', 
		'
			SELECT
				`t1`.`jailkit_login_id`, `t1`.`ssh_login_name`, `t2`.`admin_name`
			FROM
				`jailkit_login` AS `t1`
			LEFT JOIN
				`jailkit` AS `t2` ON(`t2`.`admin_id` = `t1`.`admin_id`)
		'
	);
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	if(%{$rdata}) {
		for(keys %{$rdata}) {
			# Umount (bind) virtual web dir from jail user home webfolder (Must be first, otherwise the hole virtual folder will be deleted)
			$rs = execute("/bin/umount " . $jailkitConfig->{'jailfolder'} . "/" . $rdata->{$_}->{'admin_name'} . "/home/" . $rdata->{$_}->{'ssh_login_name'} . "/webfolder", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			
			return $rs if $rs;
			
			# Force logout the ssh login
			$rs = execute("/usr/bin/pkill -KILL -f -u " . $rdata->{$_}->{'ssh_login_name'}, \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			
			#This will return 1 if the user is not logged in
			#return $rs if $rs;
			
			$rs = execute($main::imscpConfig{'CMD_USERDEL'} . " -f " . $rdata->{$_}->{'ssh_login_name'}, \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			
			return $rs if $rs;
		}
	}
	
	# Removing all mount bind entries from /etc/fstab
	my $fstabMountBindConfig;
	
	my $fstabConf = '/etc/fstab';
	return 1 if ! -f $fstabConf;
	
	my $file = iMSCP::File->new('filename' => $fstabConf);
	
	my $fileContent = $file->get();
	return $fileContent if ! $fileContent;
	
	if ($fileContent =~ /^# Start Added by Plugins::JailKit.*JailKit End\n/sgm) {
		$fileContent =~ s/^# Start Added by Plugins::JailKit.*JailKit End\n/$fstabMountBindConfig/sgm;
	}
	
	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
	
	# Remove the hole jailkit folder
	my $rs = iMSCP::Dir->new('dirname' => $jailkitConfig->{'jailfolder'})->remove() if -d $jailkitConfig->{'jailfolder'};
	return $rs if $rs;
	
	# Drop jailkit and jailkit_login table
	$db->doQuery('dummy', 'DROP TABLE IF EXISTS `jailkit`');
	$db->doQuery('dummy', 'DROP TABLE IF EXISTS `jailkit_login`');
	
	0;
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
	
	# Add or remove customer ssh jail
	$rdata = $db->doQuery(
		'jailkit_id', 
		"
			SELECT
				`jailkit_id`, `admin_id`,
				`admin_name`, `jailkit_status`
			FROM
				`jailkit`
			WHERE
				`jailkit_status` IN('toadd', 'todelete')
			ORDER BY
				`admin_id` ASC
		"
	);
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	if(%{$rdata}) {
		for(keys %{$rdata}) {
			if($rdata->{$_}->{'jailkit_status'} eq 'toadd') {
				$rs = $self->_addCustomerSshJail(
					$jailkitConfig->{'jailfolder'}, $jailkitConfig->{'default_jail_apps'}, $rdata->{$_}->{'admin_name'}
				);

				@sql = (
					'UPDATE `jailkit` SET `jailkit_status` = ? WHERE `jailkit_id` = ?',
					($rs ? scalar getMessageByType('error') : 'ok'), $rdata->{$_}->{'jailkit_id'}
				);
			} elsif($rdata->{$_}->{'jailkit_status'} eq 'todelete') {
				$rs = $self->_deleteCustomerSshJail(
					$jailkitConfig->{'jailfolder'}, $rdata->{$_}->{'admin_name'}, $rdata->{$_}->{'admin_id'}
				);
				if($rs) {
					@sql = (
						'UPDATE `jailkit` SET `jailkit_status` = ? WHERE `jailkit_id` = ?',
						scalar getMessageByType('error'), $rdata->{$_}->{'jailkit_id'}
					);
				} else {
					@sql = ('DELETE FROM `jailkit` WHERE `jailkit_id` = ?', $rdata->{$_}->{'jailkit_id'});
				}
				
				# Renew all mount binds to fstab after deleting
				$rs = $self->_addWebfolderMountToFstab($jailkitConfig->{'jailfolder'});
				return $rs if $rs;
			}

			my $rdata2 = $db->doQuery('dummy', @sql);
			
			unless(ref $rdata2 eq 'HASH') {
				error($rdata2);
				return 1;
			}
		}
		
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
	
	# Add, change or remove a ssh login of a customer jail
	$rdata = $db->doQuery(
		'jailkit_login_id', 
		"
			SELECT
				`t1`.`jailkit_login_id`, `t1`.`admin_id`, `t1`.`ssh_login_name`,
				`t1`.`ssh_login_pass`, `t1`.`ssh_login_sys_uid`, `t1`.`ssh_login_sys_gid`,
				`t1`.`ssh_login_locked`, `t1`.`jailkit_login_status`, `t2`.`admin_name`
			FROM
				`jailkit_login` AS `t1`
			LEFT JOIN
				`jailkit` AS `t2` ON(`t2`.`admin_id` = `t1`.`admin_id`)
			WHERE
				`t1`.`jailkit_login_status` IN('toadd', 'tochange', 'todelete')
			ORDER BY
				`t1`.`admin_id` ASC
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
				$rs = $self->_addSshLoginToCustomerJail(
					$jailkitConfig->{'jailfolder'}, $jailkitConfig->{'default_shell'}, $rdata->{$_}->{'admin_name'}, 
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
					($rs ? scalar getMessageByType('error') : (($rdata->{$_}->{'ssh_login_locked'} eq '0')  ? 'ok' : 'disabled')), $rdata->{$_}->{'jailkit_login_id'}
				);
			} elsif($rdata->{$_}->{'jailkit_login_status'} eq 'todelete') {
				$rs = $self->_removeSshLoginFromCustomerJail(
					$jailkitConfig->{'jailfolder'}, $rdata->{$_}->{'admin_name'}, $rdata->{$_}->{'ssh_login_name'}
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
	}
	
	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _addCustomerSshJail()

 Add customer to a new ssh jail

 Return int 0 on success, other on failure

=cut

sub _addCustomerSshJail
{
	my $self = shift;
	
	my $jailFolder = shift;
	my $defaultJailApps = shift;
	my $customerName = shift;
	
	my $rs = 0;
	my ($stdout, $stderr);
	
	# Add jail to new customer
	my $JailApps;
	
	while (my($defaultJailAppsKey, $defaultJailAppsValue) = each($defaultJailApps)) {
		$JailApps .= $defaultJailAppsValue . " ";
	}
	
	$rs = execute("umask 022; /usr/sbin/jk_init -f -k -c /etc/jailkit/jk_init.ini -j " . $jailFolder . "/" . $customerName . " " . $JailApps, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	return $rs if $rs;
	
	if(! -d $jailFolder . '/' . $customerName . '/tmp') {
		my $rs = iMSCP::Dir->new('dirname' => $jailFolder . '/' . $customerName . '/tmp')->make(
			{ 'user' => 'root', 'group' => 'root', 'mode' => 0777 }
		);
		return $rs if $rs;
	}
	
	# Is needed for MySQL connect
	if(! -d $jailFolder . '/' . $customerName . '/var/run/mysqld') {
		my $rs = iMSCP::Dir->new('dirname' => $jailFolder . '/' . $customerName . '/var/run/mysqld')->make(
			{ 'user' => 'root', 'group' => 'root', 'mode' => 0750 }
		);
		return $rs if $rs;
	}
	
	if(-e '/var/run/mysqld/mysqld.sock') {
		$rs = execute($main::imscpConfig{'CMD_LN'} . " -s /var/run/mysqld/mysqld.sock " . $jailFolder . "/" . $customerName . "/var/run/mysqld/mysqld.sock", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		
		return $rs if $rs;
	}
}

=item _deleteCustomerSshJail()

 Add customer to a new ssh jail

 Return int 0 on success, other on failure

=cut

sub _deleteCustomerSshJail
{
	my $self = shift;
	
	my $jailFolder = shift;
	my $customerName = shift;
	my $customerAdminId = shift;
	
	my $rs = 0;
	my ($stdout, $stderr);
	
	my $db = iMSCP::Database->factory();
	
	my $rdata = $db->doQuery(
		'jailkit_login_id', 
		'
			SELECT
				`jailkit_login_id`, `admin_id`, `ssh_login_name`
			FROM
				`jailkit_login`
			WHERE
				`admin_id` = ?
		', $customerAdminId
	);
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	if(%{$rdata}) {
		for(keys %{$rdata}) {
			# Umount (bind) virtual web dir from jail user home webfolder (Must be first, otherwise the hole virtual folder will be deleted)
			$rs = execute("/bin/umount " . $jailFolder . "/" . $customerName . "/home/" . $rdata->{$_}->{'ssh_login_name'} . "/webfolder", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			
			return $rs if $rs;
		
			$rs = execute($main::imscpConfig{'CMD_USERDEL'} . " -f " . $rdata->{$_}->{'ssh_login_name'}, \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			
			return $rs if $rs;

			my $rdata2 = $db->doQuery('dummy', 'DELETE FROM `jailkit_login` WHERE `jailkit_login_id` = ?', $rdata->{$_}->{'jailkit_login_id'});
			
			unless(ref $rdata2 eq 'HASH') {
				error($rdata2);
				return 1;
			}
		}
	}
	
	# Remove customer jail folder
	my $jailFolderCustomer = $jailFolder . '/' . $customerName;
	$rs = iMSCP::Dir->new('dirname' => $jailFolderCustomer)->remove() if -d $jailFolderCustomer;
	return $rs if $rs;
}

=item _addSshLoginToCustomerJail()

 Add a new ssh login to customer ssh jail

 Return int 0 on success, other on failure

=cut

sub _addSshLoginToCustomerJail
{
	my $self = shift;
	
	my $jailFolder = shift;
	my $jailDefaultShell = shift;
	my $customerName = shift;
	my $sshLoginName = shift;
	my $sshLoginPass = shift;
	my $sshLoginSysUid = shift;
	my $sshLoginSysGid = shift;
	
	my $rs = 0;
	my ($stdout, $stderr);
	
	$rs = execute($main::imscpConfig{'CMD_USERADD'} . " -c 'JailKit SSH login " . $sshLoginName . "' -u " . $sshLoginSysUid . " -g " . $sshLoginSysGid . " -o -m " . $sshLoginName, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	return $rs if $rs;
	
	# Set new Password
	$rs = execute($main::imscpConfig{'CMD_ECHO'} . " -e \"" . $sshLoginPass . "\n" . $sshLoginPass ."\" | /usr/bin/passwd " .$sshLoginName, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	return $rs if $rs;
	
	# Add the user to the jail
	$rs = execute("umask 022; /usr/sbin/jk_jailuser -m -n -s " . $jailDefaultShell . " -j " . $jailFolder . "/" . $customerName . " " . $sshLoginName, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	return $rs if $rs;
	
	# Add webfolder dir in home folder of the new user
	if(! -d $jailFolder . '/' . $customerName . '/home/' . $sshLoginName . '/webfolder') {
		my $rs = iMSCP::Dir->new('dirname' => $jailFolder . '/' . $customerName . '/home/' . $sshLoginName . '/webfolder')->make(
			{ 'user' => 'root', 'group' => 'root', 'mode' => 0750 }
		);
		return $rs if $rs;
	}
	
	# Mount (bind) virtual web dir to jail user home webfolder
	$rs = execute("/bin/mount " . $main::imscpConfig{'USER_WEB_DIR'} . "/" . $customerName . " " . $jailFolder . "/" . $customerName . "/home/" . $sshLoginName . "/webfolder -o bind", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	return $rs if $rs;
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
	$rs = execute($main::imscpConfig{'CMD_ECHO'} . " -e \"" . $sshLoginPass . "\n" . $sshLoginPass ."\" | /usr/bin/passwd " .$sshLoginName, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	return $rs if $rs;
	
	if($action eq 'lock') {
		# Force logout the ssh login
		$rs = execute("/usr/bin/pkill -KILL -f -u " . $sshLoginName, \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
			
		#This will return 1 if the user is not logged in
		#return $rs if $rs;
			
		$rs = execute("/usr/bin/passwd " . $sshLoginName . " -l", \$stdout, \$stderr); # Using passwd because usermod gives no output
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
				
		return $rs if $rs;
	}
	elsif($action eq 'unlock') {
		$rs = execute("/usr/bin/passwd " . $sshLoginName . " -u", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
				
		return $rs if $rs;
	}
}

=item _removeSshLoginFromCustomerJail()

 Remove ssh login from customer ssh jail

 Return int 0 on success, other on failure

=cut

sub _removeSshLoginFromCustomerJail
{
	my $self = shift;
	
	my $jailFolder = shift;
	my $customerName = shift;
	my $sshLoginName = shift;
	
	my $rs = 0;
	my ($stdout, $stderr);
	
	# Force logout the ssh login
	$rs = execute("/usr/bin/pkill -KILL -f -u " . $sshLoginName, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	#This will return 1 if the user is not logged in	
	#return $rs if $rs;
	
	$rs = execute($main::imscpConfig{'CMD_USERDEL'} . " -f " . $sshLoginName, \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	return $rs if $rs;
	
	# Umount (bind) virtual web dir from jail user home webfolder (Must be first, otherwise the hole virtual folder will be deleted)
	$rs = execute("/bin/umount " . $jailFolder . "/" . $customerName . "/home/" . $sshLoginName . "/webfolder", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	return $rs if $rs;
	
	# Remove home folder from jail
	my $homeFolder = $jailFolder . '/' . $customerName . '/home/' . $sshLoginName;
	$rs = iMSCP::Dir->new('dirname' => $homeFolder)->remove() if -d $homeFolder;
	return $rs if $rs;
	
	# Remove ssh login from jail passwd
	my $jailPasswd = $jailFolder . '/' . $customerName . '/etc/passwd';
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
	
	my @sql;
	my $rdata2;
	
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
				`jailkit_login_status` IN ('ok', 'disabled')
		"
	);
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	if(%{$rdata}) {
		for(keys %{$rdata}) {
		
			my $sshLoginName = $rdata->{$_}->{'ssh_login_name'};
			my $sshLoginLocked = $rdata->{$_}->{'ssh_login_locked'};
			my $jailKitLoginId = $rdata->{$_}->{'jailkit_login_id'};
			
			if($action eq 'lock') {
				# Force logout the ssh login
				$rs = execute("/usr/bin/pkill -KILL -f -u " . $sshLoginName, \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr;
					
				#This will return 1 if the user is not logged in
				#return $rs if $rs;
		
				$rs = execute("/usr/bin/passwd " . $sshLoginName . " -l", \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
				
				return $rs if $rs;
			}
			elsif($action eq 'unlock' && $sshLoginLocked == 0) {
				$rs = execute("/usr/bin/passwd " . $sshLoginName . " -u", \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
				
				return $rs if $rs;
			}
			
			@sql = (
				'UPDATE `jailkit_login` SET `jailkit_login_status` = ? WHERE `jailkit_login_id` = ?',
				($action eq 'unlock' && $sshLoginLocked == 0)  ? 'ok' : 'disabled', $jailKitLoginId
			);
			
			$rdata2 = $db->doQuery('dummy', @sql);
			
			unless(ref $rdata2 eq 'HASH') {
				error($rdata2);
				return 1;
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
	
	my $db = iMSCP::Database->factory();
	
	my $rdata = $db->doQuery(
		'jailkit_login_id', 
		"
			SELECT
				`t1`.`jailkit_login_id`, `t1`.`admin_id`, `t1`.`ssh_login_name`,
				`t2`.`admin_name`
			FROM
				`jailkit_login` AS `t1`
			LEFT JOIN
				`jailkit` AS `t2` ON(`t2`.`admin_id` = `t1`.`admin_id`)
			WHERE
				`t1`.`jailkit_login_status` = 'ok'
			ORDER BY
				`t1`.`ssh_login_name` DESC
		"
	);
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	my $fstabMountBindConfig = '';
	
	if(%{$rdata}) {
		$fstabMountBindConfig = "# Start Added by Plugins::JailKit::JailKit\n";
		for(keys %{$rdata}) {
			$fstabMountBindConfig .= $main::imscpConfig{'USER_WEB_DIR'} . "/" . $rdata->{$_}->{'admin_name'} . " " . $jailFolder . "/" . $rdata->{$_}->{'admin_name'} . "/home/" . $rdata->{$_}->{'ssh_login_name'} . "/webfolder none bind 0 0\n";
		}
		$fstabMountBindConfig .= "# Added by Plugins::JailKit End\n";
	}
	
	my $fstabConf = '/etc/fstab';
	return 1 if ! -f $fstabConf;
	
	my $file = iMSCP::File->new('filename' => $fstabConf);
	
	my $fileContent = $file->get();
	return $fileContent if ! $fileContent;
	
	if ($fileContent =~ /^# Start Added by Plugins::JailKit.*JailKit End\n/sgm) {
		$fileContent =~ s/^# Start Added by Plugins::JailKit.*JailKit End\n/$fstabMountBindConfig/sgm;
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
	
	my $db = iMSCP::Database->factory();
	
	my $rdata = $db->doQuery(
		'jailkit_id', 
		"
			SELECT
				`jailkit_id`, `admin_id`,
				`admin_name`, `jailkit_status`
			FROM
				`jailkit`
			WHERE
				`jailkit_status` = 'ok'
			ORDER BY
				`admin_id` ASC
		"
	);
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	my $jkSockettdEntries = '';
	
	if(%{$rdata}) {
		$jkSockettdEntries = "# Start Added by Plugins::JailKit::JailKit\n";
		for(keys %{$rdata}) {
			$jkSockettdEntries .= "[" . $jailFolder . "/" . $rdata->{$_}->{'admin_name'} . "/dev/log]\n";
			$jkSockettdEntries .= "base=" . $jailSockettdBase . "\n";
			$jkSockettdEntries .= "peak=" . $jailSockettdPeak . "\n";
			$jkSockettdEntries .= "interval=" . $jailSockettdInterval . "\n";
		}
		$jkSockettdEntries .= "# Added by Plugins::JailKit End\n";
	}
	
	my $jkSocketdConf = '/etc/jailkit/jk_socketd.ini';
	return 1 if ! -f $jkSocketdConf;
	
	my $file = iMSCP::File->new('filename' => $jkSocketdConf);
	
	my $fileContent = $file->get();
	return $fileContent if ! $fileContent;
	
	if ($fileContent =~ /^# Start Added by Plugins::JailKit.*JailKit End\n/sgm) {
		$fileContent =~ s/^# Start Added by Plugins::JailKit.*JailKit End\n/$jkSockettdEntries/sgm;
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
	
	# Don't use here $stdout or $stderr. The requestmanager will hang up and only end if the daemon will restartet manually
	my $rs = execute("service jailkit restart");
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
			{ 'user' => 'root', 'group' => 'root', 'mode' => 0755 }
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
	
	my $rs = 0;
	my ($stdout, $stderr);
	
	$rs = execute("/bin/uname -m", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	return $rs if $rs;
	
	if($stdout =~ /^i\d+/) {
		$rs = execute($main::imscpConfig{'CMD_CP'} . " -f " . $main::imscpConfig{'GUI_ROOT_DIR'} . "/plugins/JailKit/installation/jailkit-config/32bit/* /etc/jailkit/", \$stdout, \$stderr);
	} else {
		$rs = execute($main::imscpConfig{'CMD_CP'} . " -f " . $main::imscpConfig{'GUI_ROOT_DIR'} . "/plugins/JailKit/installation/jailkit-config/64bit/* /etc/jailkit/", \$stdout, \$stderr);
	}
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
	
	$rs = execute("/bin/uname -m", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	return $rs if $rs;
	
	if($stdout =~ /^i\d+/) {
		$rs = execute("/usr/bin/dpkg -i " . $jailKitI386, \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		
		return $rs if $rs;
	} else {
		$rs = execute("/usr/bin/dpkg -i " . $jailKitAmd64, \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		
		return $rs if $rs;
	}
}

=back

=head1 AUTHORS AND CONTRIBUTORS

 Sascha Bay <info@space2place.de>

=cut

1;
