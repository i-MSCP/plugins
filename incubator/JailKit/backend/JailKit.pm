#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) Laurent Declercq <l.declercq@nuxwin.com>
# Copyright (C) Sascha Bay <info@space2place.de>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#
# @category    iMSCP
# @package     iMSCP_Plugin
# @subpackage  JailKit
# @copyright   Laurent Declercq <l.declercq@nuxwin.com>
# @copyright   Sascha Bay <info@space2place.de>
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @author      Sascha Bay <info@space2place.de>
# @link        http://www.i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Plugin::JailKit;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::Templator;
use JSON;

use feature 'state';

use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP JailKit plugin.

=head1 PUBLIC METHODS

=over 4

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $rs = $self->_installJailKit();
	return $rs if $rs;

	$rs = $self->_createRootJail();
	return $rs if $rs;

	$self->_processLogrotateEntries();
}

=item update()

 Process update tasks

 Return int 0 on success, other on failure

=cut

sub update
{
	my $self = shift;

	my $rs = $self->_install();
	return $rs if $rs;

	#$rs = $self->_updateJails();
	#return $rs if $rs;

	#$rs = $self->_processFstabEntries();
	#return $rs if $rs;

	$self->_processJkSocketdEntries();
}

=item change()

 Process change tasks

 Return int 0 on success, other on failure

=cut

sub change
{
	my $self = shift;

	if (defined $main::execmode && $main::execmode eq 'setup') {
		# Event listener which is responsible to update SSH users in case parent user UID/GID get modified by i-MSCP
		$self->{'hooksManager'}->register('onAfterAddImscpUnixUser', sub {
			my ($customerId, $parentUserGroup, $parentUserUid, $parentUserGid) = ($_[0], $_[3], $_[8], $_[9]);

			state $rdata;

			unless(defined $rdata) {
				$rdata = iMSCP::Database->factory()->doQuery(
					'jailkit_login_id',
					'
						SELECT
							jailkit_login_id, admin_id, ssh_login_name, admin_name
						FROM
							jailkit_login
						INNER JOIN
							admin USING(admin_id)
					'
				);
				unless(ref $rdata eq 'HASH') {
					error($rdata);
					return 1;
				}
			}

			if(%{$rdata}) {
				my $rootJailPath = $self->{'config'}->{'root_jail_path'};

				for(keys %{$rdata}) {
					if($rdata->{$_}->{'admin_id'} == $customerId) {
						my $sshLoginName = $rdata->{$_}->{'ssh_login_name'};
						my $customerName = $rdata->{$_}->{'admin_name'};

						if(my @pwnam = getpwnam($sshLoginName)) {
							if($parentUserUid != $pwnam[2] || $parentUserGid != $pwnam[3]) {
								my @cmd = (
									"$main::imscpConfig{'CMD_PKILL'} --KILL -f -u", escapeShell($sshLoginName) . ';',
									$main::imscpConfig{'CMD_USERMOD'},
									'-u', escapeShell($parentUserUid), # user UID
									'-g', escapeShell($parentUserGid), # group GID
									'-o', # Allow to reuse UID of existent user
									escapeShell($sshLoginName) # username
								);
								my($stdout, $stderr);
								my $rs = execute("@cmd", \$stdout, \$stderr);
								debug($stdout) if $stdout;
								error($stderr) if $stderr && $rs;

								unless($rs) {
									# Remove SSH user from the jail passwd file
									my $file = iMSCP::File->new(
										'filename' => "$rootJailPath/$customerName/etc/passwd"
									);

									my $fileContent = $file->get();

									if(defined $fileContent) {
										$fileContent =~ s/^$sshLoginName:.*\n//gm;

										$rs = $file->set($fileContent);
										return $rs if $rs;

										$file->save();

										# Re-add SSH user in the jail of the customer to which it belong to
										my @cmd = (
											'umask 022;',
											"$self->{'config'}->{'install_path'}/sbin/jk_jailuser -n",
											'-s', escapeShell($self->{'config'}->{'shell'}),
											'-j', escapeShell("$rootJailPath/$customerName"),
											escapeShell($sshLoginName)
										);
										$rs ||= execute("@cmd", \$stdout, \$stderr);
										debug($stdout) if $stdout;
										error($stderr) if $stderr && $rs;

										require iMSCP::Rights;
										iMSCP::Rights->import();

										$rs ||= setRights(
											"$self->{'config'}->{'root_jail_path'}/$customerName/home/$sshLoginName",
											{ 'user' =>  $sshLoginName, 'group' => $parentUserGroup, 'recursive' => 1 }
										);
									} else {
										error("Unable to read $file->{'filename'}");
										$rs = 1;
									}
								}

								if($rs) {
									$rs = iMSCP::Database->factory()->doQuery(
										'dummy',
										'UPDATE jailkit_login SET jailkit_login_status = ? WHERE jailkit_login_id = ?',
										($rs ? scalar getMessageByType('error') : 'ok'),
										$rdata->{$_}->{'jailkit_login_id'}
									);
									unless(ref $rs eq 'HASH') {
										error($rs);
										return 1;
									}
								}
							}
						} else {
							warning("SSH user $sshLoginName doesn't exist");
						}
					}
				}
			}

			0;
		});
	} else {
		# Plugin configuration has been changed. All jails must be updated
		$self->_updateJails();
	}
}

=item enable()

 Process enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = shift;

	my $rs = $self->_changeSshUsers('unlock');
	return $rs if $rs;

	$self->{'startDaemon'} = 'yes';

	0;
}

=item disable()

 Process disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
	my $self = shift;

	my $rs = $self->_changeSshUsers('lock');
	return $rs if $rs;

	$self->_stopDaemon();
}

=item run()

 Process plugin items

 Return int 0 on success, other on failure

=cut

sub run
{
	my $self = shift;

	my $db = iMSCP::Database->factory();

	# Processing jailkit items - Add/Remove jails

	my $rdata = $db->doQuery(
		'jailkit_id',
		"
			SELECT
				jailkit_id, admin_id, admin_name, jailkit_status
			FROM
				jailkit
			WHERE
				`jailkit_status` IN('toadd', 'todelete')
		"
	);
	unless(ref $rdata eq 'HASH') {
		$self->{'FORCE_RETVAL'} = 'yes';
		error($rdata);
		return 1;
	}

	my $rootJailPath = $self->{'config'}->{'root_jail_path'};
	my $defaultJailApps = $self->{'config'}->{'jail_app_sections'};
	my $rs = 0;

	if(%{$rdata}) {
		my @sql;

		for(keys %{$rdata}) {
			my $status = $rdata->{$_}->{'jailkit_status'};

			if($status eq 'toadd') {
				$rs = $self->_addJail( $rdata->{$_}->{'admin_id'}, $rdata->{$_}->{'admin_name'});

				@sql = (
					'UPDATE jailkit SET jailkit_status = ? WHERE jailkit_id = ?',
					($rs ? scalar getMessageByType('error') : 'ok'), $rdata->{$_}->{'jailkit_id'}
				);
			} elsif($status eq 'todelete') {
				$rs = $self->_deleteJail($rdata->{$_}->{'admin_id'}, $rdata->{$_}->{'admin_name'});

				if($rs) {
					@sql = (
						'UPDATE jailkit SET jailkit_status = ? WHERE jailkit_id = ?',
						scalar getMessageByType('error'), $rdata->{$_}->{'jailkit_id'}
					);
				} else {
					@sql = ('DELETE FROM jailkit WHERE jailkit_id = ?', $rdata->{$_}->{'jailkit_id'});
				}
			}

			$rs = $db->doQuery('dummy', @sql);
			unless(ref $rs eq 'HASH') {
				$self->{'FORCE_RETVAL'} = 'yes';
				error($rs);
				return 1;
			}
		}

		$rs = $self->_processJkSocketdEntries();
		$self->{'FORCE_RETVAL'} = 'yes' if $rs;
		return $rs if $rs;

		$self->{'startDaemon'} = 'yes';
	}

	# Processing jailkit_login items - Add/Change/Remove SSH users

	$rdata = $db->doQuery(
		'jailkit_login_id',
		"
			SELECT
				jailkit_login_id, admin_id, ssh_login_name, ssh_login_pass, ssh_login_locked, jailkit_login_status,
				admin_name
			FROM
				jailkit_login
			INNER JOIN
				jailkit USING(admin_id)
			WHERE
				jailkit_login_status IN('toadd', 'tochange', 'todelete')
			AND
				jailkit_status = 'ok'
		"
	);
	unless(ref $rdata eq 'HASH') {
		$self->{'FORCE_RETVAL'} = 'yes';
		error($rdata);
		return 1;
	}

	if(%{$rdata}) {
		my @sql;

		for(keys %{$rdata}) {
			my $status = $rdata->{$_}->{'jailkit_login_status'};

			if($status eq 'toadd') {
				$rs = $self->_addSshUser(
					$rdata->{$_}->{'admin_id'}, $rdata->{$_}->{'admin_name'}, $rdata->{$_}->{'ssh_login_name'},
					$rdata->{$_}->{'ssh_login_pass'}
				);

				@sql = (
					'UPDATE jailkit_login SET jailkit_login_status = ? WHERE jailkit_login_id = ?',
					($rs ? scalar getMessageByType('error') : 'ok'), $rdata->{$_}->{'jailkit_login_id'}
				);
			} elsif($status eq 'tochange') {
				$rs = $self->_changeSshUser(
					$rdata->{$_}->{'admin_id'}, $rdata->{$_}->{'admin_name'}, $rdata->{$_}->{'ssh_login_name'},
					$rdata->{$_}->{'ssh_login_pass'}, ($rdata->{$_}->{'ssh_login_locked'} eq '0')  ? 'unlock' : 'lock'
				);

				@sql = (
					'UPDATE jailkit_login SET jailkit_login_status = ? WHERE jailkit_login_id = ?',
					(
						$rs
							? scalar getMessageByType('error')
							: (($rdata->{$_}->{'ssh_login_locked'} eq '0') ? 'ok' : 'disabled')
					),
					$rdata->{$_}->{'jailkit_login_id'}
				);
			} elsif($status eq 'todelete') {
				$rs = $self->_removeSshUser($rdata->{$_}->{'admin_name'}, $rdata->{$_}->{'ssh_login_name'});

				if($rs) {
					@sql = (
						'UPDATE jailkit_login SET jailkit_login_status = ? WHERE jailkit_login_id = ?',
						scalar getMessageByType('error'), $rdata->{$_}->{'jailkit_login_id'}
					);
				} else {
					@sql = (
						'DELETE FROM jailkit_login WHERE jailkit_login_id = ?', $rdata->{$_}->{'jailkit_login_id'}
					);
				}
			}

			$rs = $db->doQuery('dummy', @sql);
			unless(ref $rs eq 'HASH') {
				$self->{'FORCE_RETVAL'} = 'yes';
				error($rs);
				return 1;
			}
		}
	}

	$rs = $self->_processFstabEntries();
	$self->{'FORCE_RETVAL'} = 'yes' if $rs;

	$rs;
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery(
		'jailkit_login_id',
		'SELECT jailkit_login_id, ssh_login_name, admin_name FROM jailkit_login INNER JOIN jailkit USING(admin_id)'
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	# Stopping the jailkit daemon
	my $rs = _stopDaemon();
	return $rs if $rs;

	my $rootJailPath = $self->{'config'}->{'root_jail_path'};

	# Removing all SSH users
	if(%{$rdata}) {
		require iMSCP::SystemUser;

		for(keys %{$rdata}) {
			my $sshLoginName = $rdata->{$_}->{'ssh_login_name'};

			# Umount the parent user homedir from the SSH user web directory. This must be done before removing the
			# SSH user, else, the parent user homedir will get deleted
			$rs = $self->_umount("$rootJailPath/$rdata->{$_}->{'admin_name'}/$sshLoginName/web");
			return $rs if $rs;

			# Removing SSH user
			$rs = iMSCP::SystemUser->new('force' => 'yes')->delSystemUser($sshLoginName);
			return $rs if $rs;
		}
	}

	# Removing any JailKit plugin entry from the /etc/fstab conffile
	$rs = $self->_processFstabEntries('remove');
	return $rs if $rs;

	# Removing any JailKit plugin entry from the logrotate conffile
	$rs = $self->_processLogrotateEntries('remove');
	return $rs if $rs;

	# Removing the root jail directory (This will delete all jails)
	$rs = iMSCP::Dir->new('dirname' => $rootJailPath)->remove();
	return $rs if $rs;

	# Removing the jailkit database table
	$rs = $db->doQuery('dummy', 'DROP TABLE IF EXISTS jailkit');
	unless(ref $rs eq 'HASH') {
		error($rs);
		return 1;
	}

	# Removing the jailkit_login database table
	$rs = $db->doQuery('dummy', 'DROP TABLE IF EXISTS jailkit_login');
	unless(ref $rs eq 'HASH') {
		error($rs);
		return 1;
	}

	# Uninstalling jailkit
	$self->_uninstallJailKit();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Plugin::JailKit

=cut

sub _init()
{
	my $self = shift;

	# Loading plugin configuration

	my $rdata = iMSCP::Database->factory()->doQuery(
		'plugin_name', 'SELECT plugin_name, plugin_config FROM plugin WHERE plugin_name = ?', 'JailKit'
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	$self->{'config'} = decode_json($rdata->{'JailKit'}->{'plugin_config'});

	$self;
}

=item _addJail($customerId, $customerName)

 Add jail for the given customer

 Return int 0 on success, other on failure

=cut

sub _addJail($$)
{
	my ($self, $customerId, $customerName) = @_;

	my $parentUser = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . ($main::imscpConfig{'SYSTEM_USER_MIN_UID'}+$customerId);

	if(getpwnam($parentUser)) {
		my $installPath = $self->{'config'}->{'install_path'};
		my $rootJailPath = $self->{'config'}->{'root_jail_path'};

		# Initialize jail using application sections
		my ($stdout, $stderr);
		my $rs = execute(
			"umask 022; $installPath/sbin/jk_init -f -k -j $rootJailPath/$customerName " .
			"@{$self->{'config'}->{'jail_app_sections'}}",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		# Installing additional apps inside the jail
		if($self->{'config'}->{'jail_additional_apps'}) {
			$rs = execute(
				"umask 022; $installPath/sbin/jk_cp -f -k -j $rootJailPath/$customerName " .
				"@{$self->{'config'}->{'jail_additional_apps'}}",
				\$stdout,
				\$stderr
			);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		}

		# Copy the motd (message of the day) file inside the jail
		$rs = iMSCP::File->new(
			'filename' => "$main::imscpConfig{'GUI_ROOT_DIR'}/plugins/JailKit/tpl/motd"
		)->copyFile(
			"$rootJailPath/$customerName/etc"
		);
		return $rs if $rs;

		# Creating /opt directory inside the jail or set its permissions if it already exists
		$rs = iMSCP::Dir->new(
			'dirname' => "$rootJailPath/$customerName/opt"
		)->make(
			{ 'user' => 'root', 'group' => 'root', 'mode' => 0755 }
		);
		return $rs if $rs;

		# Creating /tmp directory inside the jail or set its permissions if it already exist
		$rs = iMSCP::Dir->new(
			'dirname' => "$rootJailPath/$customerName/tmp"
		)->make(
			{ 'user' => 'root', 'group' => 'root', 'mode' => 0777 }
		);
		return $rs if $rs;

		# Needed for MySQL connect inside the jail. This is only required if the MySQL server is hosted locally and
		# if the mysql client is available in the jail
		if(
			$main::imscpConfig{'SQL_SERVER'} ne 'remote_server' && -d '/var/run/mysqld' &&
			'mysql-client' ~~ $self->{'config'}->{'jail_app_sections'}
		) {
			# Try to umount first to avoid to have the same mount point mounted twice
			$rs = $self->_umount("$rootJailPath/$customerName/var/run/mysqld");
			return $rs if $rs;

			# Creating var/run/mysqld directory or set its permissions if it already exist
			$rs = iMSCP::Dir->new(
				'dirname' => "$rootJailPath/$customerName/var/run/mysqld"
			)->make(
				{ 'user' => 'root', 'group' => 'root', 'mode' => 0755 }
			);
			return $rs if $rs;

			# Mount (bind) the /var/run/mysqld directory
			$rs = execute(
				"/bin/mount --bind /var/run/mysqld $rootJailPath/$customerName/var/run/mysqld", \$stdout, \$stderr
			);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		}
	} else {
		error("System user $parentUser doesn't exist."); # Should never occurs
		return 1;
	}

	0;
}

=item _deleteJail($customerId, $customerName)

 Removes the jail owned by the given customer. Also removes any SSH user which belong to the jail.

 Return int 0 on success, other on failure

=cut

sub _deleteJail($$$)
{
	my ($self, $customerId, $customerName) = @_;

	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery(
		'jailkit_login_id', 'SELECT jailkit_login_id, ssh_login_name FROM jailkit_login WHERE admin_id = ?', $customerId
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	my $rootJailPath = $self->{'config'}->{'root_jail_path'};

	if(%{$rdata}) {
		require iMSCP::SystemUser;

		for(keys %{$rdata}) {
			my $sshLoginName = $rdata->{$_}->{'ssh_login_name'};

			# Umount the parent user homedir from the SSH user web directory if any. This must be done before removing
			# the SSH user, else, the parent user homedir will get deleted
			my $rs = $self->_umount("$rootJailPath/$customerName/home/$sshLoginName/web");
			return $rs if $rs;

			# Removing SSH user
			$rs = iMSCP::SystemUser->new('force' => 'yes')->delSystemUser($sshLoginName);
			return $rs if $rs;

			# Removing SSH user from database
			$rs = $db->doQuery(
				'dummy', 'DELETE FROM jailkit_login WHERE jailkit_login_id = ?', $rdata->{$_}->{'jailkit_login_id'}
			);
			unless(ref $rs eq 'HASH') {
				error($rs);
				return 1;
			}
		}
	}

	# Umount the /var/run/mysqld directory if any. This must be done before removing the
	# jail, else, the system /var/run/mysqld directory will get deleted
    my $rs = $self->_umount("$rootJailPath/$customerName/var/run/mysqld");
    return $rs if $rs;

	# Removing jail
	iMSCP::Dir->new('dirname' => "$rootJailPath/$customerName")->remove();
}

=item _updateJails()

 Update jails.

 Update all jails with last file versions available on the system. Also add any application as specified into the
jail_app_sections and the jail_additional_apps configuration parameters.

=cut

sub _updateJails
{
	my $self = shift;

	my $rdata = iMSCP::Database->factory()->doQuery(
		'jailkit_id', "SELECT jailkit_id, admin_id, admin_name FROM jailkit WHERE jailkit_status = 'ok'"
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	if(%{$rdata}) {
		my $installPath = $self->{'config'}->{'install_path'};
		my $rootJailPath = $self->{'config'}->{'root_jail_path'};

		for(keys %{$rdata}) {
			my $customerName = $rdata->{$_}->{'admin_name'};

			# Needed in case app sections and/or additional apps parameters were changed. Will also create the jail if
			# it doesn't exist
			my $rs = $self->_addJail($rdata->{$_}->{'admin_id'}, $customerName);
            return $rs if $rs;

			# Update the jail with last system files versions
			my ($stdout, $stderr);
			$rs = execute("$installPath/sbin/jk_update -k -j $rootJailPath/$customerName", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		}
	}

	0;
}

=item _addSshUser($customerId, $customerName, $sshLoginName, $sshLoginPass)

 Add SSH user

 Return int 0 on success, other on failure

=cut

sub _addSshUser($$$$$)
{
	my ($self, $customerId, $customerName, $sshLoginName, $sshLoginPass) = @_;

	my $parentUserName =
	my $parentUserGroup =
		$main::imscpConfig{'SYSTEM_USER_PREFIX'} . ($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $customerId);

	if((my @pwnam = getpwnam($parentUserName))) {
		unless(getpwnam($sshLoginName)) { # SSH user doesn't exist, we create it
			my @cmd = (
				$main::imscpConfig{'CMD_USERADD'},
				'-c', escapeShell("i-MSCP JailKit SSH User"), # comment
				'-u', escapeShell($pwnam[2]), # user UID
				'-g', escapeShell($pwnam[3]), # group GID
				'-m', # Create home directory
				'-k', escapeShell("$main::imscpConfig{'GUI_ROOT_DIR'}/plugins/JailKit/tpl/skel"), # Skel directory
				'-o', # Allow to reuse UID of existent user
				escapeShell($sshLoginName) # username
			);
			my ($stdout, $stderr);
			my $rs = execute("@cmd", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		}

		# Setting SSH user password
		my @cmd = ($main::imscpConfig{'CMD_ECHO'}, escapeShell("$sshLoginName:$sshLoginPass"), '| /usr/sbin/chpasswd');
		my ($stdout, $stderr);
		my $rs = execute("@cmd", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		my $rootJailPath = $self->{'config'}->{'root_jail_path'};

		# Adding SSH user in the jail of the customer to which it belong to
		my @cmd = (
			'umask 022;',
			"$self->{'config'}->{'install_path'}/sbin/jk_jailuser -n",
			(-d "/home/$sshLoginName") ? '-m' : '', # Do not try to copy the homedir if it doesn't exist (recovery case)
			'-s', escapeShell($self->{'config'}->{'shell'}),
			'-j', escapeShell("$rootJailPath/$customerName"),
			escapeShell($sshLoginName)
		);
		$rs = execute("@cmd", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		# Creating web directory inside the homedir of the SSH user or sets its permissions if it already exist
		my $rs = iMSCP::Dir->new(
			'dirname' => "$rootJailPath/$customerName/home/$sshLoginName/web"
		)->make(
			{ 'user' => $main::imscpConfig{'ROOT_USER'}, 'group' => $main::imscpConfig{'ROOT_GROUP'}, 'mode' => 0755 }
		);
		return $rs if $rs;

		# Try to umount first to avoid to have the same mount point mounted twice
		$rs = $self->_umount("$rootJailPath/$customerName/home/$sshLoginName/web");
		return $rs if $rs;

		# Mount (bind) the parent user homedir into the SSH user web directory
		# FIXME (NXW): Should we rbind (i.e mount --rbind...) too in case the i-MSCP customer homedir contain submounts
		$rs = execute(
			"/bin/mount --bind $main::imscpConfig{'USER_WEB_DIR'}/$customerName " .
			"$rootJailPath/$customerName/home/$sshLoginName/web",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		$rs;
	} else {
		error("System user $parentUserName doesn't exist."); # Should never occurs
		return 1;
	}
}

=item _changeSshUser($customerId, $customerName, $sshLoginName, $sshLoginPass, $action)

 Changes the given SSH User according the given action (lock/unlock)

 Return int 0 on success, other on failure

=cut

sub _changeSshUser($$$$)
{
	my ($self, $customerId, $customerName, $sshLoginName, $sshLoginPass, $action) = @_;

	if(getpwnam($sshLoginName)) {
		# Setting new SSH user password
		my @cmd = ($main::imscpConfig{'CMD_ECHO'}, escapeShell("$sshLoginName:$sshLoginPass"), '| /usr/sbin/chpasswd');
		my ($stdout, $stderr);
		my $rs = execute("@cmd", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	} else {
		# SSH user doesn't exist so we create it
		my $rs = $self->_addSshUser($customerId, $customerName, $sshLoginName, $sshLoginPass);
		return $rs if $rs;
	}

	if($action eq 'lock') {
		# We are killing only the SSH processes of the user. By doing this, we avoid to kill others processes which were
		# not spawned through the SSH connection
		my @cmd = (
			"$main::imscpConfig{'CMD_PKILL'} --KILL -f", escapeShell("sshd: $sshLoginName") . ';',
			"/usr/bin/passwd -l", escapeShell($sshLoginName)
		);
		my ($stdout, $stderr);
		my $rs = execute("@cmd", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	} elsif($action eq 'unlock') {
		my ($stdout, $stderr);
		my $rs = execute("/usr/bin/passwd -u " . escapeShell($sshLoginName), \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	0;
}

=item _removeSshUser($customerName, $sshLoginName)

 Remove SSH user

 Return int 0 on success, other on failure

=cut

sub _removeSshUser($$$)
{
	my ($self, $customerName, $sshLoginName) = @_;

	my $rootJailPath = $self->{'config'}->{'root_jail_path'};

	# Umount the parent user homedir from the SSH user web directory. This must be done before removing the
	# SSH user else it will get deleted
    my $rs = $self->_umount("$rootJailPath/$customerName/home/$sshLoginName/web");
    return $rs if $rs;

	# Removing SSH user
	require iMSCP::SystemUser;
	$rs = iMSCP::SystemUser->new('force' => 'yes')->delSystemUser($sshLoginName);
	return $rs if $rs;

	# Remove SSH user from the jail passwd file
	if(-f "$rootJailPath/$customerName/etc/passwd") {
		my $file = iMSCP::File->new('filename' => "$rootJailPath/$customerName/etc/passwd");

		my $fileContent = $file->get();
		unless(defined $fileContent) {
			error("Unable to read $file->{'filename'}");
			return 1;
		}

		$fileContent =~ s/^$sshLoginName:.*\n//gm;

		$rs = $file->set($fileContent);
		return $rs if $rs;

		$file->save();
		return $rs if $rs;
	}

	0;
}

=item _changeSshUsers($action)

 Enable/Disable SSH users by locking/unlocking their passwords.

 Return int 0 on success, other on failure

=cut

sub _changeSshUsers($$)
{
	my ($self, $action) = @_;

	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery(
		'jailkit_login_id', 
		"
			SELECT
				jailkit_login_id, admin_id, ssh_login_name, ssh_login_pass, ssh_login_locked, admin_name
			FROM
				jailkit_login
			INNER JOIN
				admin USING(admin_id)
			WHERE
				jailkit_login_status IN ('ok', 'disabled')
		"
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	if(%{$rdata}) {
		my $needFstabUpdate = 0;

		for(keys %{$rdata}) {
			my $sshLoginName = $rdata->{$_}->{'ssh_login_name'};
			my $sshLoginLocked = $rdata->{$_}->{'ssh_login_locked'};
			my $jailKitLoginId = $rdata->{$_}->{'jailkit_login_id'};
			my $rs = 0;

			unless(getpwnam($sshLoginName)) {
				# SSH user doesn't exist so we create it
				$rs = $self->_addSshUser(
					$rdata->{$_}->{'admin_id'}, $rdata->{$_}->{'admin_name'}, $sshLoginName,
					$rdata->{$_}->{'ssh_login_pass'}
				);
				return $rs if $rs;

				$needFstabUpdate = 1;
			}

			if($action eq 'lock') {
				# We are killing only the SSH processes of the user. By doing this, we avoid to kill others processes
				# which were not spawned through the SSH connection
				my @cmd = (
					"$main::imscpConfig{'CMD_PKILL'} --KILL -f", escapeShell("sshd: $sshLoginName") . ';',
					"/usr/bin/passwd -l", escapeShell($sshLoginName)
				);
				my ($stdout, $stderr);
				$rs = execute("@cmd", \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
				return $rs if $rs;
			} elsif($action eq 'unlock' && $sshLoginLocked eq '0') {
				my ($stdout, $stderr);
				$rs = execute("/usr/bin/passwd -u " . escapeShell($sshLoginName), \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
				return $rs if $rs;
			}

			$rs = $db->doQuery(
				'dummy',
				'UPDATE jailkit_login SET jailkit_login_status = ? WHERE jailkit_login_id = ?',
				($action eq 'unlock' && $sshLoginLocked eq '0')  ? 'ok' : 'disabled', $jailKitLoginId
			);
			unless(ref $rs eq 'HASH') {
				error($rs);
				return 1;
			}
		}

		my $rs = $self->_processFstabEntries() if $needFstabUpdate;
		return $rs if $rs;
	}

	0;
}

=item _processFstabEntries([$action = 'add'])

 Add/Remove fstab entries

 Return int 0 on success, other on failure

=cut

sub _processFstabEntries($;$)
{
	my ($self, $action) = @_;

	$action ||= 'add';

	unless(-f '/etc/fstab') {
		unless($action eq 'remove') {
			error('File /etc/fstab not found');
			return 1;
		} else {
			return 0;
		}
	}

	my $jailkitEntries = {};
	my $jailkitLoginEntries = {};

	unless($action eq 'remove') {
		if(
			$main::imscpConfig{'SQL_SERVER'} ne 'remote_server' && -d '/var/run/mysqld' &&
			'mysql-client' ~~ $self->{'config'}->{'jail_app_sections'}
		) {
			$jailkitEntries = iMSCP::Database->factory()->doQuery(
				'jailkit_id', "SELECT jailkit_id, admin_name FROM jailkit WHERE jailkit_status = 'ok'"
			);
			unless(ref $jailkitEntries eq 'HASH') {
				error($jailkitEntries);
				return 1;
			}
		}

		$jailkitLoginEntries = iMSCP::Database->factory()->doQuery(
			'jailkit_login_id',
			"
				SELECT
					jailkit_login_id, ssh_login_name, admin_name
				FROM
					jailkit_login
				INNER JOIN
					jailkit USING(admin_id)
				WHERE
					jailkit_login_status = 'ok'
			"
		);
		unless(ref $jailkitEntries eq 'HASH') {
			error($jailkitEntries);
			return 1;
		}
	}

	my $file = iMSCP::File->new('filename' => '/etc/fstab');

	my $fileContent = $file->get();
	unless(defined $fileContent) {
		error('Unable to read /etc/fstab');
		return 1;
	}

	my $bTag = "# Plugins::JailKit::Mysqld START\n";
	my $eTag = "# Plugins::JailKit::Mysqld END\n";

	# Removing any previous entry
	$fileContent = replaceBloc($bTag, $eTag, '', $fileContent);

	my $rootJailPath = $self->{'config'}->{'root_jail_path'};

	if(%{$jailkitEntries}) {
		$fileContent .= $bTag;
		$fileContent .= "/var/run/mysqld $rootJailPath/$jailkitEntries->{$_}->{'admin_name'}/var/run/mysqld none bind\n"
			for keys %{$jailkitEntries};
		$fileContent .= $eTag;
	}

	$bTag = "# Plugins::JailKit::Web START\n";
	$eTag = "# Plugins::JailKit::Web END\n";

	# Removing any previous entry
	$fileContent = replaceBloc($bTag, $eTag, '', $fileContent);

	if(%{$jailkitLoginEntries}) {
		my $userWebDir = $main::imscpConfig{'USER_WEB_DIR'};

		for(keys %{$jailkitLoginEntries}) {
			my $customerName = $jailkitLoginEntries->{$_}->{'admin_name'};

			$fileContent .= $bTag;
			$fileContent .= "$userWebDir/$customerName $rootJailPath/$customerName/home/" .
				"$jailkitLoginEntries->{$_}->{'ssh_login_name'}/web none bind\n";
			$fileContent .= $eTag;
		}
	}

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _processJkSocketdEntries()

 Process jk_socketd entries

 Return int 0 on success, other on failure

=cut

sub _processJkSocketdEntries
{
	my $self = shift;

	my $jkSocketdIniFile = "$self->{'config'}->{'install_path'}/etc/jailkit/jk_socketd.ini";
	unless(-f $jkSocketdIniFile) {
		error("File $jkSocketdIniFile not found");
		return 1;
	}

	my $file = iMSCP::File->new('filename' => $jkSocketdIniFile);

	my $fileContent = $file->get();
	unless(defined $fileContent) {
		error("Unable to read $jkSocketdIniFile");
		return 1;
	}

	my $bTag = "# Plugins::JailKit START\n";
	my $eTag = "# Plugins::JailKit END\n";

	# Removing any previous JailKit plugin entry
	$fileContent = replaceBloc($bTag, $eTag, '', $fileContent);

	my $rdata = iMSCP::Database->factory()->doQuery(
		'jailkit_id', "SELECT jailkit_id, admin_name, jailkit_status FROM jailkit WHERE jailkit_status = 'ok'"
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	my $rs = 0;

	if(%{$rdata}) {
		my $rootJailPath = $self->{'config'}->{'root_jail_path'};
		my $jailSockettdBase = $self->{'config'}->{'jail_socketd_base'};
		my $jailSockettdPeak = $self->{'config'}->{'jail_socketd_peak'};
		my $jailSockettdInterval = $self->{'config'}->{'jail_socketd_interval'};

		$fileContent .= $bTag;

		for(keys %{$rdata}) {
			$fileContent .= "[$rootJailPath/$rdata->{$_}->{'admin_name'}/dev/log]\n";
			$fileContent .= "base=$jailSockettdBase\n";
			$fileContent .= "peak=$jailSockettdPeak\n";
			$fileContent .= "interval=$jailSockettdInterval\n";
		}

		$fileContent .= $eTag;
	} else {
		# FIXME (NXW): Does the daemon reads entries from the jk_socketd.ini file while stopping?
		$rs = $self->_stopDaemon();
		return $rs if $rs;
	}

	$rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _restartDaemon()

 Restart (or start) the JailKit daemon

 Return int 0 on success, other on failure

=cut

sub _restartDaemon
{
	my ($stdout, $stderr);
	my $rs = execute('/usr/sbin/service jailkit restart', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs;
}

=item _stopDaemon()

 Stop the JailKit daemon

 Return int 0 on success, other on failure

=cut

sub _stopDaemon
{
	my ($stdout, $stderr);
	my $rs = execute('/usr/sbin/service jailkit stop', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs;
}

=item _createRootJail

 Create root jail

 Return int 0 on success, other on failure

=cut

sub _createRootJail
{
	my $self = shift;

	# Creating root jail directory or set its permissions if it already exist
	iMSCP::Dir->new(
		'dirname' => $self->{'config'}->{'root_jail_path'}
	)->make(
		{ 'user' => $main::imscpConfig{'ROOT_USER'}, 'group' => $main::imscpConfig{'ROOT_GROUP'}, 'mode' => 0755 }
	);
}

=item _processLogrotateEntries([$action = 'add'])

 Add/Remove jailkit daemon entries into the rsyslog logrotate conffile

 Return int 0 on success, other on failure

=cut

sub _processLogrotateEntries($;$)
{
	my ($self, $action) = @_;

	$action ||= 'add';

	if(-f '/etc/logrotate.d/rsyslog') {
		my $file = iMSCP::File->new('filename' => '/etc/logrotate.d/rsyslog');

		my $fileContent = $file->get();
		unless(defined $fileContent) {
			error('Unable to read /etc/logrotate.d/rsyslog');
			return 1;
		}

		# Removing any previous jailkit daemon entry
		$fileContent =~ s/^\s+invoke-rc.d jailkit.*\n//gm;

		unless($action eq 'remove') {
			# Debian/Ubuntu
			$fileContent =~
				s%^(\s+)((?:invoke-rc.d|reload) rsyslog.*\n)%$1$2$1invoke-rc.d jailkit restart > /dev/null\n%gm;
		}

		my $rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;
	}

	0;
}

=item _installJailKit

 Installs jailkit

 Return int 0 on success, other on failure

=cut

sub _installJailKit
{
	my $self = shift;

	my $installPath = $self->{'config'}->{'install_path'};
	my $rs = 0;

	if(-f "/etc/init.d/jailkit") {
		$rs = $self->_stopDaemon();
		return $rs if $rs;
	}

	# Backup current jailkit configuration file if any
	if(-d "$installPath/etc/jailkit") {
		my @files = iMSCP::Dir->new('dirname' => "$installPath/etc/jailkit", 'fileType' => '\\.ini')->getFiles();

		if(@files) {
			my $file = iMSCP::File->new();

			for(@files) {
				$file->{'filename'} = "$installPath/etc/jailkit/$_";
				$rs = $file->moveFile("$installPath/etc/jailkit/$_.old");
				return $rs if $rs;
			}
		}
	}

	require Cwd;
	Cwd->import();

	require File::Temp;
	File::Temp->import();

	my $umask = umask(022);
	my $curDir = getcwd();

	# We build Jailkit into a temporary directory
	my $buildDir = File::Temp->newdir();

	# Change dir to build directory
	unless(chdir $buildDir) {
		error("Unable to change path to $buildDir");
		return 1;
	}

	# Unfortunately, VPATH build is not possible so we copy the sources into the build directory
	my ($stdout, $stderr);
	$rs = execute(
		"$main::imscpConfig{'CMD_CP'} -fr $main::imscpConfig{'GUI_ROOT_DIR'}/plugins/JailKit/src/jailkit/* .",
		\$stdout,
		\$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Applying imscp.patch on upstream sources
	$rs = execute(
		"/usr/bin/patch -f -p1 < $main::imscpConfig{'GUI_ROOT_DIR'}/plugins/JailKit/src/imscp.patch",
		\$stdout,
		\$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error($stdout) if $rs && ! $stderr && $stdout;
	return $rs if $rs;

	# Configure
	$rs = execute("sh configure --prefix=$installPath", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Install
	$rs = execute("$main::imscpConfig{'CMD_MAKE'} install", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Fixing permissions on the jailkit confdir (should be owwned by root:root and not setgid)
	require iMSCP::Rights;
	iMSCP::Rights->import();
	setRights(
		"$installPath/etc/jailkit",
		{
			'dirmode' => '00750',
			'filemode' => '0640',
			'user' => $main::imscpConfig{'ROOT_USER'},
			'group' => $main::imscpConfig{'ROOT_GROUP'},
			'recursive' => 1
		}
	);

	# Install JailKit daemon init script into the /etc/init.d directory
	if(-f 'extra/jailkit') {
		my $file = iMSCP::File->new('filename', 'extra/jailkit');

		$rs = $file->copyFile('/etc/init.d/jailkit', { 'preserve' => 'no' });
		return $rs if $rs;

		$file->{'filename'} = '/etc/init.d/jailkit';

		my $fileContent = $file->get();
		unless(defined $fileContent) {
			error("Unable to read $file->{'filename'}");
			return 1;
		}

		$fileContent =~ s%^(DAEMON=).*%$1$installPath/sbin/\$NAME%m;

		$rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->mode(0755);
		return $rs if $rs;

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;

		# Reinstall or recovery case
		$rs = execute("update-rc.d -f jailkit remove", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		$rs = execute("update-rc.d jailkit defaults", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	} else {
		error("Unable to find extra/jailkit file in $buildDir directory");
		return 1;
	}

	# Go back to previous directory
	unless(chdir $curDir) {
		error("Unable to change path to $curDir");
		return 1;
	}

	# Restore previous umask
	umask($umask);

	$rs;
}

=item _uninstallJailKit

 Uninstall JailKit

 Return int 0 on success, other on failure

=cut

sub _uninstallJailKit
{
	my $self = shift;

	my $installPath = $self->{'config'}->{'install_path'};
	my $rs = 0;

	require Cwd;
	Cwd->import();

	require File::Temp;
	File::Temp->import();

	my $umask = umask(022);
	my $curDir = getcwd();

	# We build Jailkit into a temporary directory
	my $buildDir = File::Temp->newdir();

	# Change dir to build directory
	unless(chdir $buildDir) {
		error("Unable to change path to $buildDir");
		return 1;
	}

	# Unfortunately, VPATH build is not possible so we copy the sources into the build directory
	my ($stdout, $stderr);
	my $rs = execute(
		"$main::imscpConfig{'CMD_CP'} -fr $main::imscpConfig{'GUI_ROOT_DIR'}/plugins/JailKit/src/jailkit/* .",
		\$stdout,
		\$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Applying imscp.patch on upstream sources
	$rs = execute(
		"/usr/bin/patch -f -p1 < $main::imscpConfig{'GUI_ROOT_DIR'}/plugins/JailKit/src/imscp.patch",
		\$stdout,
		\$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error($stdout) if $rs && ! $stderr && $stdout;
	return $rs if $rs;

	# Configure
	$rs = execute("sh configure --prefix=$installPath", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Uninstall
	$rs = execute("$main::imscpConfig{'CMD_MAKE'} uninstall", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Remove JailKit daemon init script from the /etc/init.d directory
	if(-f '/etc/init.d/jailkit') {
		$rs = execute("update-rc.d -f jailkit remove", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		$rs = iMSCP::File->new('filename', '/etc/init.d/jailkit')->delFile();
		return $rs if $rs;
	}

	# Remove jk_chrootsh shell from the /etc/shells file if any
	if(-f '/etc/shells') {
		my $file = iMSCP::File->new('filename' => '/etc/shells');

		my $fileContent = $file->get();
		unless(defined $fileContent) {
			error('Unable to read /etc/shells');
			return 1;
		}

		$fileContent =~ s%^$installPath/sbin/jk_chrootsh\n%%gm;

		$rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;
	}

	# Go back to previous directory
	unless(chdir $curDir) {
		error("Unable to change path to $curDir");
		return 1;
	}

	# Restore previous umask
	umask($umask);

	$rs;
}

=item _umount($mountpoint)

 Umount the given mount point in safe way

 Return int 0 on success, other on failure

=cut

sub _umount($$)
{
	my ($self, $mountpoint) = @_;

	if(-d $mountpoint) {
		my($stdout, $stderr);

		while(! execute("/bin/mount 2>/dev/null | grep 'on $mountpoint type '", \$stdout)) {
			debug($stdout) if $stdout;

			my $rs = execute("/bin/umount -v -l $mountpoint", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		}
	}

	0;
}

END
{
	my $rs = $?;
	my $pluginInstance = Plugin::JailKit->getInstance();

	if($pluginInstance->{'startDaemon'} && $pluginInstance->{'startDaemon'} eq 'yes') {
		$rs |= $pluginInstance->_restartDaemon();
	}

	$? = $rs;
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>
 Sascha Bay <info@space2place.de>

=cut

1;
