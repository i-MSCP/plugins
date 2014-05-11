#!/usr/bin/perl

package Plugin::JailKit;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::Templator;
use Params::Check qw[check];
use File::Spec;
use JSON;
use feature 'state';
use parent 'Common::SingletonClass';

local $Params::Check::STRICT_TYPE = 1;

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
	my $self = $_[0];

	my $rs = $self->_installJailKit();
	return $rs if $rs;

	$rs = $self->_createRootJailDirectory();
	return $rs if $rs;

	$self->_processLogrotateEntries();
}

=item update()

 Process update tasks

 Return int 0 on success, other on failure

=cut

sub update
{
	my $self = $_[0];

	my $rs = $self->install();
	return $rs if $rs;

	$self->_processJkSocketdEntries();
}

=item change()

 Process change tasks

 Return int 0 on success, other on failure

=cut

sub change
{
	my $self = $_[0];

	if (defined $main::execmode && $main::execmode eq 'setup') {
		# Event listener which is responsible to update SSH users' group name in jails
		$self->{'hooksManager'}->register('onBeforeAddImscpUnixUser', sub {
			my ($customerId, $parentUserGroup, $parentUserGid) = ($_[0], $_[3], $_[9]);

			state $rdata; # We get the data once to avoid too many queries

			unless(defined $rdata) {
				$rdata = $self->{'db'}->doQuery(
					'admin_id', 'SELECT admin_id, admin_name FROM jailkit INNER JOIN admin USING(admin_id)'
				);
				unless(ref $rdata eq 'HASH') {
					error($rdata);
					return 1;
				}
			}

			my $rootJailDir = $self->{'config'}->{'root_jail_dir'};

			if($parentUserGid && exists $rdata->{$customerId}) {
				my $customerName = $rdata->{$customerId}->{'admin_name'};
				my $groupName = getgrgid($parentUserGid);

				my $file = iMSCP::File->new('filename' => "$rootJailDir/$customerName/etc/group");
				my $fileContent = $file->get();
				unless(defined $fileContent) {
					error("Unable to read $file->{'filename'}");
					return 1;
				}

				$fileContent =~ s/^$groupName:/$parentUserGroup:/gm;

				my $rs = $file->set($fileContent);
				return $rs if $rs;

				$file->save();
				return $rs if $rs;
			}

			0;
		});
	} else {
		# Plugin configuration has changed. All jails must be updated
		$self->_updateJails();
	}
}

=item enable()

 Process enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = $_[0];

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
	my $self = $_[0];

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
	my $self = $_[0];

	# Processing jailkit items - Add/change/Remove jails

	my $rdata = $self->{'db'}->doQuery(
		'jailkit_id',
		"
			SELECT
				jailkit_id, jailkit_status, admin_id, admin_name, admin_sys_uid
			FROM
				jailkit
			INNER JOIN
				admin USING(admin_id)
			WHERE
				jailkit_status IN('toadd', 'tochange', 'todelete')
		"
	);
	unless(ref $rdata eq 'HASH') {
		$self->{'FORCE_RETVAL'} = 'yes';
		error($rdata);
		return 1;
	}

	my $rootJailDir = $self->{'config'}->{'root_jail_dir'};
	my $defaultJailApps = $self->{'config'}->{'jail_app_sections'};
	my $rs = 0;

	if(%{$rdata}) {
		my @sql;

		for(keys %{$rdata}) {
			my $status = $rdata->{$_}->{'jailkit_status'};

			if($status ~~ ['toadd', 'tochange']) {
				$rs = $self->_addJail($rdata->{$_}->{'admin_sys_uid'}, $rdata->{$_}->{'admin_name'});

				@sql = (
					'UPDATE jailkit SET jailkit_status = ? WHERE jailkit_id = ?',
					($rs ? scalar getMessageByType('error') : 'ok'), $rdata->{$_}->{'jailkit_id'}
				);
			} elsif($status eq 'todelete') {
				$rs = $self->_deleteJail($rdata->{$_}->{'jailkit_id'}, $rdata->{$_}->{'admin_name'});

				if($rs) {
					@sql = (
						'UPDATE jailkit SET jailkit_status = ? WHERE jailkit_id = ?',
						scalar getMessageByType('error'), $rdata->{$_}->{'jailkit_id'}
					);
				} else {
					@sql = ('DELETE FROM jailkit WHERE jailkit_id = ?', $rdata->{$_}->{'jailkit_id'});
				}
			}

			$rs = $self->{'db'}->doQuery('dummy', @sql);
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

	$rdata = $self->{'db'}->doQuery(
		'jailkit_login_id',
		"
			SELECT
				jailkit_login_id, ssh_login_name, ssh_login_pass, ssh_login_locked, jailkit_login_status, admin_name,
				admin_sys_uid
			FROM
				jailkit_login
			INNER JOIN
				jailkit USING(jailkit_id)
			INNER JOIN
				admin USING(admin_id)
			WHERE
				jailkit_login_status IN('toadd', 'tochange', 'todelete')
			AND
				jailkit_status IN('ok', 'disabled')
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
					$rdata->{$_}->{'admin_sys_uid'}, $rdata->{$_}->{'admin_name'}, $rdata->{$_}->{'ssh_login_name'},
					$rdata->{$_}->{'ssh_login_pass'}
				);

				@sql = (
					'UPDATE jailkit_login SET jailkit_login_status = ? WHERE jailkit_login_id = ?',
					($rs ? scalar getMessageByType('error') : 'ok'), $rdata->{$_}->{'jailkit_login_id'}
				);
			} elsif($status eq 'tochange') {
				$rs = $self->_changeSshUser(
					$rdata->{$_}->{'admin_sys_uid'}, $rdata->{$_}->{'admin_name'}, $rdata->{$_}->{'ssh_login_name'},
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

			$rs = $self->{'db'}->doQuery('dummy', @sql);
			unless(ref $rs eq 'HASH') {
				$self->{'FORCE_RETVAL'} = 'yes';
				error($rs);
				return 1;
			}
		}
	}

	$rs;
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = $_[0];

	# Stopping the jailkit daemon
	my $rs = _stopDaemon();
	return $rs if $rs;

	my $rootJailDir = $self->{'config'}->{'root_jail_dir'};

	# Umount host directories from jails
	$rs = $self->processHostDirs('umount');
	return $rs if $rs;

	# Removing all SSH users

	my $rdata = $self->{'db'}->doQuery('ssh_login_name', 'SELECT ssh_login_name FROM jailkit_login');
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	if(%{$rdata}) {
		require iMSCP::SystemUser;

		for(keys %{$rdata}) {
			# Removing SSH user
			$rs = iMSCP::SystemUser->new('force' => 'yes')->delSystemUser($_);
			return $rs if $rs;
		}
	}

	# Removing any JailKit plugin entry from the logrotate conffile
	$rs = $self->_processLogrotateEntries('remove');
	return $rs if $rs;

	# Removing the root jail directory (This will delete all jails)
	$rs = iMSCP::Dir->new('dirname' => $rootJailDir)->remove();
	return $rs if $rs;

	# Removing the jailkit database table
	$rs = $self->{'db'}->doQuery('dummy', 'DROP TABLE IF EXISTS jailkit');
	unless(ref $rs eq 'HASH') {
		error($rs);
		return 1;
	}

	# Removing the jailkit_login database table
	$rs = $self->{'db'}->doQuery('dummy', 'DROP TABLE IF EXISTS jailkit_login');
	unless(ref $rs eq 'HASH') {
		error($rs);
		return 1;
	}

	# Uninstalling jailkit
	$self->_uninstallJailKit();
}

=item processHostDirs($action, [$jailDir = undef])

 Mount/Umount host directories in jail(s)

 Param string $action Action to be performed (mount|umount)
 Param string $jailDir OPTIONAL Path of the jail to operate on (Default is to operate on all jails)
 Return int 0 on success, other on failure

=cut

sub processHostDirs($$;$)
{
	my ($self, $action, $jailDir) = @_;

	my $rootJailDir = $self->{'config'}->{'root_jail_dir'};
	my $hostDirectories = $self->{'config'}->{'jail_host_directories'};

	if($action eq 'mount') {
		if(defined $jailDir) {
			require File::Basename;
			File::Basename->import();

			$jailDir = basename($jailDir);

			if(-d "$rootJailDir/$jailDir") {
				while(my ($oldDir, $newDir) = each(%{$self->{'config'}->{'jail_host_directories'}})) {
					my $rs = $self->_mount($oldDir, "$rootJailDir/$jailDir$newDir");
					return $rs if $rs;
				}
			} else {
				error("Jail doesn't exist: $jailDir");
				return 1;
			}
		} else {
			for my $jailDir(glob("$rootJailDir/*")) {
				while(my ($oldDir, $newDir) = each(%{$self->{'config'}->{'jail_host_directories'}})) {
					my $rs = $self->_mount($oldDir, "$jailDir$newDir");
					return $rs if $rs;
				}
			}
		}
	} elsif($action eq 'umount') {
		if(defined $jailDir) {
			require File::Basename;
			File::Basename->import();

			$jailDir = basename($jailDir);

			if(-d "$rootJailDir/$jailDir") {
				for(values %{$self->{'config'}->{'jail_host_directories'}}) {
					my $rs = $self->_umount("$rootJailDir/$jailDir$_");
					return $rs if $rs;
				}
			} else {
				error("Jail doesn't exist: $jailDir");
				return 1;
			}
		} else {
			for my $jailDir(glob("$rootJailDir/*")) {
				for(values %{$self->{'config'}->{'jail_host_directories'}}) {
					my $rs = $self->_umount("$jailDir$_");
					return $rs if $rs;
				}
			}
		}
	} else {
		error("Unknown action: $action");
		return 1;
	}

	0;
}

=item processHostHomeDirs($action)

 Mount/Umount Host Web directories in jail(s)

 Param string $action Action to be performed (mount|umount)
 Return int 0 on success, other on failure

=cut

sub processHostHomeDirs($$;$)
{
	my ($self, $action) = @_;

	0;
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
	my $self = $_[0];

	# Get database connection
	$self->{'db'} = iMSCP::Database->factory();

	# Loading plugin configuration

	my $rdata = $self->{'db'}->doQuery(
		'plugin_name', 'SELECT plugin_name, plugin_config FROM plugin WHERE plugin_name = ?', 'JailKit'
	);
	unless(ref $rdata eq 'HASH') {
		fatal($rdata);
	}

	my $defaultConfig = {
		'install_path' => {
			'default' => '/usr/local',
			'allow' => sub { $_[0] = File::Spec->canonpath($_[0]); 1; }
		},
		'root_jail_dir' => {
			'default' => '/home/imscp_jails',
			'allow' => sub { $_[0] = File::Spec->canonpath($_[0]); 1; }
		},
		'shell' => {
			'default' => '/bin/bash',
			allow => sub { $_[0] = File::Spec->canonpath($_[0]); 1; }
		},
		'jail_app_sections' => { 'default' => ['imscp-base', 'mysql-client'] },
		'jail_additional_apps' => {
			'default' => [
				'/bin/hostname', '/usr/bin/basename', '/usr/bin/dircolors', '/usr/bin/dirname',
				'/usr/bin/clear_console', '/usr/bin/env', '/usr/bin/id', '/usr/bin/groups', '/usr/bin/lesspipe',
				'/usr/bin/tput', '/usr/bin/which'
			],
			'allow' => sub { for(0 .. $#{$_[0]}) { $_[0]->[$_] = File::Spec->canonpath($_[0]->[$_]); } 1;}
		},
		'jail_socketd_base' => { 'default' => '512', 'allow' => qr/^\d+$/ },
		'jail_socketd_peak' => { 'default' => '2048', 'allow' => qr/^\d+$/ },
		'jail_socketd_interval' => { 'default' => '5.0', 'allow' => qr/^\d+(?:\.\d+)?$/ },
		'jail_host_directories' => {
			'default' => { '/var/run/mysqld' => '/var/run/mysqld'},
			'allow' => sub {
				$_[0]->{File::Spec->canonpath($_)} = File::Spec->canonpath(delete $_[0]->{$_}) for keys %{$_[0]}; 1;
			}
		}
	};

	$self->{'config'} = check($defaultConfig, decode_json($rdata->{'JailKit'}->{'plugin_config'})) or fatal(
		sprintf('Invalid configuration has been provided: %s1', Params::Check->last_error)
	);

	$self;
}

=item _addJail($parentUserUid, $customerName)

 Add jail for the given customer

 Param int $parentUserUid Parent SSH user uid
 Param string $customerName Customer name
 Return int 0 on success, other on failure

=cut

sub _addJail($$)
{
	my ($self, $parentUserUid, $customerName) = @_;

	if(getpwuid($parentUserUid)) {
		my $installPath = $self->{'config'}->{'install_path'};
		my $rootJailDir = $self->{'config'}->{'root_jail_dir'};

		# Initialize jail using application sections
		my ($stdout, $stderr);
		my $rs = execute(
			"umask 022; jk_init -f -k -j $rootJailDir/$customerName @{$self->{'config'}->{'jail_app_sections'}}",
			\$stdout,
			\$stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		# Install additional apps inside the jail
		if($self->{'config'}->{'jail_additional_apps'}) {
			$rs = execute(
				"umask 022; jk_cp -f -k -i -j $rootJailDir/$customerName @{$self->{'config'}->{'jail_additional_apps'}}",
				\$stdout,
				\$stderr
			);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		}

		# Copy the motd (message of the day) file inside the jail
		# FIXME: Doesn't works for ubuntu
		$rs = iMSCP::File->new(
			'filename' => "$main::imscpConfig{'GUI_ROOT_DIR'}/plugins/JailKit/tpl/motd"
		)->copyFile(
			"$rootJailDir/$customerName/etc"
		);
		return $rs if $rs;

		# Create /opt directory inside the jail or set its permissions if it already exists
		$rs = iMSCP::Dir->new(
			'dirname' => "$rootJailDir/$customerName/opt"
		)->make(
			{ 'user' => 'root', 'group' => 'root', 'mode' => 0755 }
		);
		return $rs if $rs;

		# Create /tmp directory inside the jail or set its permissions if it already exist
		$rs = iMSCP::Dir->new(
			'dirname' => "$rootJailDir/$customerName/tmp"
		)->make(
			{ 'user' => 'root', 'group' => 'root', 'mode' => 0777 }
		);
		return $rs if $rs;

		# Mount host directories into jails
		$self->processHostDirectories('mount', "$rootJailDir/$customerName");
	} else {
		error("Parent user doesn't exist."); # Should never occurs
		return 1;
	}
}

=item _deleteJail($jailId, $customerName)

 Remove the given jail. Also removes any SSH user which belong to the jail

 Param int $jailId Jail unique identifier
 Param string $customerName Customer name
 Return int 0 on success, other on failure

=cut

sub _deleteJail($$$)
{
	my ($self, $jailId, $customerName) = @_;

	my $rootJailDir = $self->{'config'}->{'root_jail_dir'};

	# Umount host directories from the jail
	my $rs = $self->processHostDirectories('umount', "$rootJailDir/$customerName");
	return $rs if $rs;

	my $rdata = $self->{'db'}->doQuery(
		'jailkit_login_id', 'SELECT jailkit_login_id, ssh_login_name FROM jailkit_login WHERE jailkit_id = ?', $jailId
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	if(%{$rdata}) {
		require iMSCP::SystemUser;

		for(keys %{$rdata}) {
			# Removing SSH user
			$rs = iMSCP::SystemUser->new('force' => 'yes')->delSystemUser($rdata->{$_}->{'ssh_login_name'});
			return $rs if $rs;

			# Removing SSH user from database
			$rs = $self->{'db'}->doQuery(
				'dummy', 'DELETE FROM jailkit_login WHERE jailkit_login_id = ?', $rdata->{$_}->{'jailkit_login_id'}
			);
			unless(ref $rs eq 'HASH') {
				error($rs);
				return 1;
			}
		}
	}

	# Removing jail
	iMSCP::Dir->new('dirname' => "$rootJailDir/$customerName")->remove();
}

=item _updateJails()

 Update jails

 Update all jails with last file versions available on the system. Also add any application as specified into the
jail_app_sections and the jail_additional_apps configuration parameters.

=cut

sub _updateJails
{
	my $self = shift;

	my $rdata = $self->{'db'}->doQuery(
		'jailkit_id',
		"
			SELECT
				jailkit_id, admin_id, admin_name, admin_sys_uid
			FROM
				jailkit
			INNER JOIN
				admin USING(admin_id)
			WHERE
				jailkit_status = IN('ok', 'disabled')
		"
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	if(%{$rdata}) {
		my $installPath = $self->{'config'}->{'install_path'};
		my $rootJailDir = $self->{'config'}->{'root_jail_dir'};

		for(keys %{$rdata}) {
			# Needed in case app sections and/or additional apps parameters were changed. Will also create the jail if
			# it doesn't exist
			my $rs = $self->_addJail($rdata->{$_}->{'admin_sys_uid'}, $rdata->{$_}->{'admin_name'});
            return $rs if $rs;

			# Update the jail with last system files versions
			my ($stdout, $stderr);
			$rs = execute("jk_update -k -j $rootJailDir/$rdata->{$_}->{'admin_name'}", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		}
	}

	0;
}

=item _addSshUser($parentUserUid, $customerName, $sshLoginName, $sshLoginPass)

 Add SSH user

 Param int $parentUserUid Parent SSH user uid
 Param string $customerName Customer name
 Param string $sshLoginName SSH user login name
 Param string $sshLoginPass SSH user hashed password
 Return int 0 on success, other on failure

=cut

sub _addSshUser($$$$$)
{
	my ($self, $parentUserUid, $customerName, $sshLoginName, $sshLoginPass) = @_;

	my ($parentUserName, undef, $parentUserUid, $parentUserGid) = getpwuid($parentUserUid);

	if($parentUserName && $parentUserUid != 0) {
		unless(getpwnam($sshLoginName)) { # SSH user doesn't exist, we create it
			my @cmd = (
				$main::imscpConfig{'CMD_USERADD'},
				'-c', escapeShell('i-MSCP Jailed SSH User'), # comment
				'-u', escapeShell($parentUserUid), # user UID
				'-g', escapeShell($parentUserGid), # group GID
				'-m', # Create home directory
				'-k', escapeShell("$main::imscpConfig{'GUI_ROOT_DIR'}/plugins/JailKit/tpl/skel"), # Skel directory
				'-p', escapeShell($sshLoginPass), # Hashed password
				'-o', # Allow to reuse UID of existent user
				escapeShell($sshLoginName) # Login
			);
			my ($stdout, $stderr);
			my $rs = execute("@cmd", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		}

		my $rootJailDir = $self->{'config'}->{'root_jail_dir'};

		# Adding SSH user in the jail of the customer to which it belong to
		my @cmd = (
			'umask 022;',
			"jk_jailuser -n",
			(-d "/home/$sshLoginName") ? '-m' : '', # Do not try to copy the homedir if it doesn't exist (recovery case)
			'-s', escapeShell($self->{'config'}->{'shell'}),
			'-j', escapeShell("$rootJailDir/$customerName"),
			escapeShell($sshLoginName)
		);
		my ($stdout, $stderr);
		my $rs = execute("@cmd", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	} else {
		error("Parent user doesn't exist."); # Should never occurs
		return 1;
	}
}

=item _changeSshUser($parentUserUid, $customerName, $sshLoginName, $sshLoginPass, $action)

 Changes the given SSH User according the given action (lock/unlock). In both case the SSH user password get updated
using hashed password as stored in the database.

 Param int $parentUserUid Parent SSH user uid
 Param string $customerName Customer name
 Param string $sshLoginName SSH user login name
 Param string $sshLoginPass SSH user hashed password
 Param string $action Action to be performed (lock|unlock)
 Return int 0 on success, other on failure

=cut

sub _changeSshUser($$$$)
{
	my ($self, $parentUserUid, $customerName, $sshLoginName, $sshLoginPass, $action) = @_;

	if(getpwnam($sshLoginName)) {
		# Setting new SSH user password
		my @cmd = (
			$main::imscpConfig{'CMD_USERMOD'},
			'-p', escapeShell($sshLoginPass), # Hashed password
			escapeShell($sshLoginName) # Login
		);
		my ($stdout, $stderr);
		my $rs = execute("@cmd", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	} else {
		# SSH user doesn't exist so we create it
		my $rs = $self->_addSshUser($parentUserUid, $customerName, $sshLoginName, $sshLoginPass);
		return $rs if $rs;
	}

	if($action eq 'lock') {
		# We are killing only the SSH processes. By doing this, we avoid to kill others processes which were not spawned
		# through the SSH connection
		my @cmd = (
			"$main::imscpConfig{'CMD_PKILL'} -KILL -u", escapeShell($sshLoginName), 'sshd', ';',
			'passwd -l', escapeShell($sshLoginName)
		);
		my ($stdout, $stderr);
		my $rs = execute("@cmd", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	} elsif($action eq 'unlock') {
		my ($stdout, $stderr);
		my $rs = execute('passwd -u ' . escapeShell($sshLoginName), \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	0;
}

=item _removeSshUser($customerName, $sshLoginName)

 Remove SSH user

 Param string $customerName Customer name
 Param string $sshLoginName SSH user login name
 Return int 0 on success, other on failure

=cut

sub _removeSshUser($$$)
{
	my ($self, $customerName, $sshLoginName) = @_;

	my $rootJailDir = $self->{'config'}->{'root_jail_dir'};

	# Removing SSH user
	require iMSCP::SystemUser;
	my $rs = iMSCP::SystemUser->new('force' => 'yes')->delSystemUser($sshLoginName);
	return $rs if $rs;

	# Remove SSH user from the jail passwd file
	if(-f "$rootJailDir/$customerName/etc/passwd") {
		my $file = iMSCP::File->new('filename' => "$rootJailDir/$customerName/etc/passwd");

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

 Param string $action Action to be performed (lock|unlock)
 Return int 0 on success, other on failure

=cut

sub _changeSshUsers($$)
{
	my ($self, $action) = @_;

	my $rdata = $self->{'db'}->doQuery(
		'jailkit_login_id', 
		"
			SELECT
				jailkit_login_id, ssh_login_name, ssh_login_pass, ssh_login_locked, admin_name, admin_sys_uid
			FROM
				jailkit_login
			INNER JOIN
				jailkit USING(jailkit_id)
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
		for(keys %{$rdata}) {
			my $sshLoginName = $rdata->{$_}->{'ssh_login_name'};
			my $sshLoginLocked = $rdata->{$_}->{'ssh_login_locked'};
			my $jailKitLoginId = $rdata->{$_}->{'jailkit_login_id'};
			my $rs = 0;

			unless(getpwnam($sshLoginName)) {
				# SSH user doesn't exist so we create it
				$rs = $self->_addSshUser(
					$rdata->{$_}->{'admin_sys_uid'}, $rdata->{$_}->{'admin_name'}, $sshLoginName,
					$rdata->{$_}->{'ssh_login_pass'}
				);
				return $rs if $rs;
			}

			if($action eq 'lock') {
				# We are killing only the SSH processes. By doing this, we avoid to kill others processes which were not
				# spawned through the SSH connection
				my @cmd = (
					"$main::imscpConfig{'CMD_PKILL'} -KILL -u", escapeShell($sshLoginName),  'sshd', ';',
					'passwd -l', escapeShell($sshLoginName)
				);
				my ($stdout, $stderr);
				$rs = execute("@cmd", \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
				return $rs if $rs;
			} elsif($action eq 'unlock' && $sshLoginLocked eq '0') {
				my ($stdout, $stderr);
				$rs = execute('passwd -u ' . escapeShell($sshLoginName), \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
				return $rs if $rs;
			}

			$rs = $self->{'db'}->doQuery(
				'dummy',
				'UPDATE jailkit_login SET jailkit_login_status = ? WHERE jailkit_login_id = ?',
				($action eq 'unlock' && $sshLoginLocked eq '0')  ? 'ok' : 'disabled', $jailKitLoginId
			);
			unless(ref $rs eq 'HASH') {
				error($rs);
				return 1;
			}
		}
	}

	0;
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

	my $rdata = $self->{'db'}->doQuery(
		'jailkit_id',
		"
			SELECT
				jailkit_id, jailkit_status, admin_name
			FROM
				jailkit
			INNER JOIN
				admin USING(admin_id)
			WHERE
				jailkit_status = 'ok'
		"
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	my $rs = 0;

	if(%{$rdata}) {
		my $rootJailDir = $self->{'config'}->{'root_jail_dir'};
		my $jailSockettdBase = $self->{'config'}->{'jail_socketd_base'};
		my $jailSockettdPeak = $self->{'config'}->{'jail_socketd_peak'};
		my $jailSockettdInterval = $self->{'config'}->{'jail_socketd_interval'};

		$fileContent .= $bTag;

		for(keys %{$rdata}) {
			$fileContent .= "[$rootJailDir/$rdata->{$_}->{'admin_name'}/dev/log]\n";
			$fileContent .= "base=$jailSockettdBase\n";
			$fileContent .= "peak=$jailSockettdPeak\n";
			$fileContent .= "interval=$jailSockettdInterval\n";
		}

		$fileContent .= $eTag;
	} else {
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
	my $rs = execute('invoke-rc.d jailkit-socketd restart', \$stdout, \$stderr);
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
	my $rs = execute('invoke-rc.d jailkit-socketd stop', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs;
}

=item _createRootJailDirectory

 Create root jail directory as specified in configuration file

 Return int 0 on success, other on failure

=cut

sub _createRootJailDirectory
{
	my $self = shift;

	# Creating root jail directory or set its permissions if it already exist
	iMSCP::Dir->new(
		'dirname' => $self->{'config'}->{'root_jail_dir'}
	)->make(
		{ 'user' => $main::imscpConfig{'ROOT_USER'}, 'group' => $main::imscpConfig{'ROOT_GROUP'}, 'mode' => 0755 }
	);
}

=item _processLogrotateEntries([$action = 'add'])

 Add/Remove jailkit daemon entries into the rsyslog logrotate conffile

 Param string $action Action to be performed (add|remove)
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

=item _mount($oldir, $newdir)

 Mount the given directory in safe way

 Param string $oldir Directory to mount
 Param string $newdir Mount point
 Return int 0 on success, other on failure

=cut

sub _mount($$$)
{
	my ($self, $oldir, $newdir) = @_;

	if(-d $oldir) {
		my($stdout, $stderr);

		if(execute("mount 2>/dev/null | grep 'on $newdir type '", \$stdout)) {
			debug($stdout) if $stdout;

			my $rs = iMSCP::Dir->new(
				'dirname' => $newdir
			)->make(
				{
					'user' => $main::imscpConfig{'ROOT_USER'},
					'group' => $main::imscpConfig{'ROOT_GROUP'},
					'mode' => 0755
				}
			);

			$rs = execute("mount --bind $oldir $newdir", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		}
	}

	0;
}

=item _umount($directory)

 Umount the given directory in safe way

 Param string $directory Directory to umount
 Return int 0 on success, other on failure

=cut

sub _umount($$)
{
	my ($self, $directory) = @_;

	my($stdout, $stderr);

	until(execute("mount 2>/dev/null | grep 'on $directory type '", \$stdout)) {
		debug($stdout) if $stdout;

		my $rs = execute("umount -l $directory", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
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

	if(-f '/etc/init.d/jailkit') {
		$rs = $self->_stopDaemon();
		return $rs if $rs;
	}

	# Backup current jailkit configuration file if any
	if(-d "$installPath/etc/jailkit") {
		require File::Copy;
		File::Copy->import('move');

		for(glob("$installPath/etc/jailkit/*.ini")) {
			unless(move($_, "$_.old")) {
				error("Unable to move file $_ to $_.old $!");
				return 1;
			}
		}
	}

	require Cwd;
	Cwd->import();

	require File::Temp;
	File::Temp->import();

	my $umask = umask(022);
	my $curDir = getcwd();

	# Build Jailkit into a temporary directory
	my $buildDir = File::Temp->newdir();

	# Change dir to build directory
	unless(chdir $buildDir) {
		error("Unable to change dir to $buildDir");
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
		"patch -f -p1 < $main::imscpConfig{'GUI_ROOT_DIR'}/plugins/JailKit/src/imscp.patch", \$stdout, \$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error($stdout) if $rs && ! $stderr && $stdout;
	return $rs if $rs;

	# Configure
	$rs = execute(
		"IMSCPENGINEDIR=$main::imscpConfig{'ENGINE_ROOT_DIR'} sh configure --prefix=$installPath", \$stdout, \$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Install
	$rs = execute("$main::imscpConfig{'CMD_MAKE'} install", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Fixing permissions on the jailkit confdir
	require iMSCP::Rights;
	iMSCP::Rights->import();

	setRights(
		"$installPath/etc/jailkit",
		{
			'dirmode' => 'a-rwxst,u+rwx,g+rx',
			'filemode' => '0640',
			'user' => $main::imscpConfig{'ROOT_USER'},
			'group' => $main::imscpConfig{'ROOT_GROUP'},
			'recursive' => 1
		}
	);

	# Install init scripts
	for('jailkit-socketd', 'jailkit-mountdirs') {
		my $file = iMSCP::File->new('filename', "extra/$_");

		$rs = $file->copyFile("/etc/init.d/$_", { 'preserve' => 'no' });
		return $rs if $rs;

		$file->{'filename'} = "/etc/init.d/$_";

		my $fileContent = $file->get();
		unless(defined $fileContent) {
			error("Unable to read $file->{'filename'}");
			return 1;
		}

		$fileContent =~ s%^(DAEMON=).*%$1$installPath/sbin/\$NAME%m;
		$fileContent =~ s%/usr/sbin

		$rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->mode(0755);
		return $rs if $rs;

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;

		# Reinstall or recovery case
		$rs = execute("update-rc.d -f $_ remove", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		$rs = execute("update-rc.d $_ defaults", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	# Go back to previous directory
	unless(chdir $curDir) {
		error("Unable to change dir to $curDir");
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
		error("Unable to change dir to $buildDir");
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
		"patch -f -p1 < $main::imscpConfig{'GUI_ROOT_DIR'}/plugins/JailKit/src/imscp.patch", \$stdout, \$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	error($stdout) if $rs && ! $stderr && $stdout;
	return $rs if $rs;

	# Configure
	$rs = execute(
		"IMSCPENGINEDIR=$main::imscpConfig{'ENGINE_ROOT_DIR'} sh configure --prefix=$installPath", \$stdout, \$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Uninstall
	$rs = execute("$main::imscpConfig{'CMD_MAKE'} uninstall", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Remove jailkit config directory
	$rs = iMSCP::Dir->new('dirname' => "$installPath/etc/jailkit")->remove();
	return $rs if $rs;

	# Remove  init scripts
	for('jailkit-socketd', 'jailkit-mountdirs') {
		if(-f "/etc/init.d/$_") {
			$rs = execute("update-rc.d -f $_ remove", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;

			$rs = iMSCP::File->new('filename', "/etc/init.d/$_")->delFile();
			return $rs if $rs;
		}
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
		error("Unable to change dir to $curDir");
		return 1;
	}

	# Restore previous umask
	umask($umask);

	$rs;
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

=cut

1;
