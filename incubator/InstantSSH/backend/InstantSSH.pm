#!/usr/bin/perl

=head1 NAME

 Plugin::InstantSSH

=cut

# i-MSCP InstantSSH plugin
# Copyright (C) 2014 Laurent Declercq <l.declercq@nuxwin.com>
#
# This library is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 2.1 of the License, or (at your option) any later version.
#
# This library is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this library; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301 USA

package Plugin::InstantSSH;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use lib "$main::imscpConfig{'PLUGINS_DIR'}/InstantSSH/backend",
        "$main::imscpConfig{'PLUGINS_DIR'}/InstantSSH/backend/Vendor";

use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::Ext2Attributes qw(clearImmutable isImmutable setImmutable);
use InstantSSH::JailBuilder;
use InstantSSH::JailBuilder::Utils qw(normalizePath);
use Unix::PasswdFile;
use JSON;

use parent 'Common::SingletonClass';

my %customerShells = ();

=head1 DESCRIPTION

 This package provide the backend part of the InstantSSH plugin.

=head1 PUBLIC METHODS

=over 4

=item install()

 Process plugin installation tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = $_[0];

	my $rs = $self->_checkRequirements();
	return $rs if $rs;

	# Create configuration directory for makejail ( Since version 2.1.0 )
	$rs = iMSCP::Dir->new( dirname => $self->{'config'}->{'makejail_confdir_path'} )->make(
		'user' => $main::imscpConfig{'ROOT_USER'}, 'group' => $main::imscpConfig{'IMSCP_GROUP'}, 'mode' => 0750
	);
	return $rs if $rs;

	$rs = $self->_configurePamChroot();
	return $rs if $rs;

	$self->_configureBusyBox();
}

=item uninstall()

 Process plugin uninstallation tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = $_[0];

	my $rootJailDir = normalizePath($self->{'config'}->{'root_jail_dir'});

	# Remove the shared jail if any.
	# Note: Customer jails are removed when the plugin is being deactivated,
	# which is a pre-condition for the plugin uninstallation
	if(-d "$rootJailDir/shared_jail") {
		my $jailBuilder;
		eval { $jailBuilder = InstantSSH::JailBuilder->new( id => 'shared_jail', config => $self->{'config'} ); };
		if($@) {
			error("Unable to create JailBuilder object: $@");
			return 1;
		}

		my $rs = $jailBuilder->removeJail();
		return $rs if $rs;
	}

	# Remove the jails root directory ( only if empty )
	if(-d $rootJailDir && iMSCP::Dir->new( dirname => $rootJailDir )->isEmpty()) {
		my $rs = iMSCP::Dir->new( dirname => $rootJailDir )->remove();
		return $rs if $rs;

		$rs = iMSCP::Dir->new( dirname => $self->{'config'}->{'makejail_confdir_path'} )->remove();
		return $rs if $rs;
	} else {
		error("Unable to delete the $rootJailDir directory: Directory is not empty");
		return 1;
	}

	my $rs = $self->_configurePamChroot('uninstall');
	return $rs if $rs;

	$self->_configureBusyBox('uninstall');
}

=item update($fromVersion, $toVersion)

 Process plugin update tasks

 Param string $fromVersion
 Param string $toVersion
 Return int 0 on success, other on failure

=cut

sub update
{
	my ($self, $fromVersion, $toVersion) = @_;

	my $rs = $self->install();
	return $rs if $rs;

	# Since version 2.1.0, we are using our own directory to store makejail configuration files
	# In oldest versions, those files were stored in default directory ( /etc/makejail ). We move
	# them to the new directory using the new naming convention.
	if($toVersion eq '2.1.0' && -d '/etc/makejail') {
		for my $file(iMSCP::Dir->new( dirname => '/etc/makejail', fileType => 'InstantSSH.*\\.py' )->getFiles()) {
			my $nPath;

			if($file eq 'InstantSSH.py') {
				$nPath = $self->{'config'}->{'makejail_confdir_path'} . '/' . 'shared_jail.py';
			} else {
				($nPath = $self->{'config'}->{'makejail_confdir_path'} . '/' . $file) =~ s/InstantSSH\.(.+)$/$1/;
			}

			$rs = iMSCP::File->new( filename => "/etc/makejail/$file" )->moveFile($nPath);
			return $rs if $rs;
		}
	}

	0;
}

=item change

 Process plugin change tasks

 Return int 0, other on failure

=cut

sub change
{
	my $self = $_[0];

	unless(defined $main::execmode && $main::execmode eq 'setup') {
		my $rootJailDir = normalizePath($self->{'config'}->{'root_jail_dir'});

		if(-d $rootJailDir) {
			for my $jailDir(iMSCP::Dir->new( dirname => $rootJailDir )->getDirs()) {
				my $jailBuilder;
				eval { $jailBuilder = InstantSSH::JailBuilder->new( id => $jailDir, config => $self->{'config'} ); };
				if($@) {
					error("Unable to create JailBuilder object: $@");
					return 1;
				}

				# We update or remove the jail according the value of the shared_jail parameter
				# which tell what type of jail is expected
				if(
					($self->{'config'}->{'shared_jail'} && $jailDir eq 'shared_jail') ||
					(!$self->{'config'}->{'shared_jail'} && $jailDir ne 'shared_jail')
				) {
					my $rs = $jailBuilder->makeJail(); # Update jail
					return $rs if $rs;
				} else {
					my $rs = $jailBuilder->removeJail(); # Remove jail
					return $rs if $rs;
				}
			}
		}
	}

	0;
}

=item enable()

 Process enable tasks

 Return int 0, other on failure

=cut

sub enable
{
	my $self = $_[0];

	my $rs = $self->{'db'}->doQuery('dummy', "UPDATE instant_ssh_permissions SET ssh_permission_status = 'toenable'");
	unless(ref $rs eq 'HASH') {
		error($rs);
		return 1;
	}

	$rs = $self->{'db'}->doQuery('dummy', "UPDATE instant_ssh_keys SET ssh_key_status = 'toenable'");
	unless(ref $rs eq 'HASH') {
		error($rs);
		return 1;
	}

	$self->run();
}

=item disable()

 Process disable tasks

 Return int 0, other on failure

=cut

sub disable
{
	my $self = $_[0];

	my $rs = $self->{'db'}->doQuery('dummy', "UPDATE instant_ssh_permissions SET ssh_permission_status = 'todisable'");
	unless(ref $rs eq 'HASH') {
		error($rs);
		return 1;
	}

	$rs = $self->run();
	return $rs if $rs;

	$rs = $self->{'db'}->doQuery('dummy', "UPDATE instant_ssh_keys SET ssh_key_status = 'disabled'");
	unless(ref $rs eq 'HASH') {
		error($rs);
		return 1;
	}

	0;
}

=item run()

 Handle SSH permissions and SSH keys

 Return int 0 on succes, other on failure

=cut

sub run
{
	my $self = $_[0];

	my ($rs, $ret) = (0, 0);

	my $dbh = $self->{'db'}->getRawDb();

	# Handle SSH permissions

	my $sth = $dbh->prepare(
		"
			SELECT
				t1.*, t2.admin_sys_name
			FROM
				instant_ssh_permissions AS t1
			INNER JOIN
				admin AS t2 ON(admin_id = ssh_permission_admin_id)
			WHERE
				ssh_permission_status in('toadd', 'tochange', 'toenable', 'todisable', 'todelete')
		"
	);
	unless($sth) {
		$self->{'RETVAL'} = 1; # Not an error related to a specific plugin item
		error("Couldn't prepare SQL statement: " . $dbh->errstr);
		return 1;
	}

	unless($sth->execute()) {
		$self->{'RETVAL'} = 1; # Not an error related to a specific plugin item
		error("Couldn't execute prepared statement: " . $dbh->errstr);
		return 1;
	}

	while (my $data = $sth->fetchrow_hashref()) {
		my $status = $data->{'ssh_permission_status'};

		my @sql;

		if($status ~~ ['toadd', 'tochange', 'toenable']) {
			$rs = $self->_addSshPermissions($data);
			@sql = (
				'dummy',
				'UPDATE instant_ssh_permissions SET ssh_permission_status = ? WHERE ssh_permission_id = ?',
				($rs ? scalar getMessageByType('error') : 'ok'),
				$data->{'ssh_permission_id'}
			);

			$customerShells{$data->{'admin_sys_name'}} = $data->{'ssh_permission_jailed_shell'};
		} elsif($status ~~ ['todisable', 'todelete']) {
			$rs = $self->_removeSshPermissions($data);
			unless($rs) {
				if($status eq 'todisable') {
					@sql = (
						'dummy',
						'UPDATE instant_ssh_permissions SET ssh_permission_status = ? WHERE ssh_permission_id = ?',
						'disabled',
						$data->{'ssh_permission_id'}
					);
				} else {
					@sql = (
						'dummy',
						'DELETE FROM instant_ssh_permissions WHERE ssh_permission_id = ?',
						$data->{'ssh_permission_id'}
					);
				}
			} else {
				@sql = (
					'dummy',
					'UPDATE instant_ssh_permissions SET ssh_permission_status = ? WHERE ssh_permission_id = ?',
					scalar getMessageByType('error'),
					$data->{'ssh_permission_id'}
				);
			}
		}

		my $qrs = $self->{'db'}->doQuery(@sql);
		unless(ref $qrs eq 'HASH') {
			$self->{'RETVAL'} = 1; # Not an error related to a specific plugin item
			error($qrs);
			$rs = 1;
		};

		$ret ||= $rs;
	}

	# Handle SSH keys

	$sth = $dbh->prepare(
		"
			SELECT
				t1.*, t2.admin_sys_name, t2.admin_sys_gname
			FROM
				instant_ssh_keys AS t1
			INNER JOIN
				admin AS t2 ON(admin_id = ssh_key_admin_id)
			INNER JOIN
				instant_ssh_permissions AS t3 using(ssh_permission_id)
			WHERE
				ssh_key_status IN ('toadd', 'tochange', 'toenable', 'todisable', 'todelete')
			AND
				ssh_permission_status = 'ok'
			ORDER BY
				admin_id
		"
	);
	unless($sth) {
		$self->{'RETVAL'} = 1; # Not an error related to a specific plugin item
		error("Couldn't prepare SQL statement: " . $dbh->errstr);
		return 1;
	}

	unless($sth->execute()) {
		$self->{'RETVAL'} = 1; # Not an error related to a specific plugin item
		error("Couldn't execute prepared statement: " . $dbh->errstr);
		return 1;
	}

	while (my $data = $sth->fetchrow_hashref()) {
		my $status = $data->{'ssh_key_status'};

		my @sql;

		if($status ~~ ['toadd', 'tochange', 'toenable']) {
			$rs = $self->_addSshKey($data);
			@sql = (
				'dummy', 'UPDATE instant_ssh_keys SET ssh_key_status = ? WHERE ssh_key_id = ?',
				($rs ? scalar getMessageByType('error') : 'ok'),
				$data->{'ssh_key_id'}
			);
		} elsif($status ~~ ['todisable', 'todelete']) {
			$rs = $self->_deleteSshKey($data);
			unless($rs) {
				if($status eq 'todisable') {
					@sql = (
						'dummy', 'UPDATE instant_ssh_keys SET ssh_key_status = ? WHERE ssh_key_id = ?',
						'disabled',
						$data->{'ssh_key_id'}
					);
				} else {
					@sql = ('dummy', 'DELETE FROM instant_ssh_keys WHERE ssh_key_id = ?', $data->{'ssh_key_id'});
				}
			} else {
				@sql = (
					'dummy', 'UPDATE instant_ssh_keys SET ssh_key_status = ? WHERE ssh_key_id = ?',
					scalar getMessageByType('error'),
					$data->{'ssh_key_id'}
				);
			}
		}

		my $qrs = $self->{'db'}->doQuery(@sql);
		unless(ref $qrs eq 'HASH') {
			$self->{'RETVAL'} = 1; # Not an error related to a specific plugin item
			error($qrs);
			$rs = 1;
		};

		$ret ||= $rs;
	}

	$ret;
}

=back

=head1 EVENT LISTENERS

=over 4

=item onDeleteDomain(\%data)

 Event listener which is responsible to remove SSH permissions prior domain deletion (customer account)

 Param hash \%data Domain data
 Return int 0 on success, other on failure

=cut

sub onDeleteDomain
{
	my ($self, $data) = @_;

	if($data->{'DOMAIN_TYPE'} eq 'dmn') {
		my $homeDir = (getpwnam($data->{'USER'}))[7];

		if(defined $homeDir) {
			$homeDir = normalizePath($homeDir);

			# Force logout of ssh login if any
			my @cmd = ($main::imscpConfig{'CMD_PKILL'}, '-KILL', '-f', '-u', escapeShell($data->{'USER'}), 'sshd');
			my ($stdout, $stderr);
			execute("@cmd", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			debug($stderr) if $stderr;

			if(-d "$homeDir/.ssh") {
				clearImmutable($homeDir);
				clearImmutable("$homeDir/.ssh", 'recursive');

				my $rs = iMSCP::Dir->new( dirname => "$homeDir/.ssh" )->remove();
				return $rs if $rs;
			}

			my $jailBuilder;
				eval {
					$jailBuilder = InstantSSH::JailBuilder->new(
						id => ($self->{'config'}->{'shared_jail'}) ? 'shared_jail' : $data->{'USER'},
						config => $self->{'config'}
					);
				};
				if($@) {
					error("Unable to create JailBuilder object: $@");
					return 1;
				}

				if($jailBuilder->existsJail()) {
					if($self->{'config'}->{'shared_jail'}) {
						my $rs = $jailBuilder->removeUserFromJail($data->{'USER'});
						return $rs if $rs;
					} else {
						my $rs = $jailBuilder->removeJail();
						return $rs if $rs;
					}
				}
		} else {
			error("Unable to find $data->{'USER'} unix user homedir");
			return 1;
		}
	}

	0;
}

=item onUnixUserUpdate(\%data)

 Event listener which listen on the onBeforeAddImscpUnixUser event

 When unix users are updated, the user shell and homedir are set back to the default values. This listener set the
needed values for customers which have SSH permissions.

 Return int 0 on success, other on failure

=cut

sub onUnixUserUpdate
{
	my ($self, $user, $homeDir, $shell) = ($_[0], $_[2], $_[6], $_[8]);

	if(exists $customerShells{$user}) {
		if($customerShells{$user}) {
			$$homeDir = normalizePath($$homeDir) . '/./';
			$$shell = $self->{'config'}->{'shells'}->{'jailed'};
		} else {
			$$shell = $self->{'config'}->{'shells'}->{'full'};
		}
	}

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init

 Initialize instance

 Return Plugin::InstantSSH

=cut

sub _init
{
	my $self = $_[0];

	$self->{'db'} = iMSCP::Database->factory();

	my $config = $self->{'db'}->doQuery(
		'plugin_name', "SELECT plugin_name, plugin_config FROM plugin WHERE plugin_name = 'InstantSSH'"
	);
	unless(ref $config eq 'HASH') {
		die("InstantSSH: $config");
	} else {
		$self->{'config'} = decode_json($config->{'InstantSSH'}->{'plugin_config'})
	}

	for(qw/makejail_confdir_path root_jail_dir shared_jail shells/) {
		die("Missing $_ configuration parameter") unless exists $self->{'config'}->{$_};
	}

	$self->{'eventManager'}->register('onBeforeAddImscpUnixUser', sub { $self->onUnixUserUpdate(@_); });
	$self->{'eventManager'}->register('beforeHttpdDelDmn', sub { $self->onDeleteDomain(@_); });

	$self;
}

=item _addSshPermissions(\%data)

 Adds the given SSH permissions

 Param hash \%data SSH permissions data
 Return int 0 on success, other on failure

=cut

sub _addSshPermissions
{
	my($self, $data) = @_;

	my $homeDir = (getpwnam($data->{'admin_sys_name'}))[7];

	if(defined $homeDir) {
		$homeDir = normalizePath($homeDir);

		# Force logout of ssh logins if any ( tochange case )
		my @cmd = ($main::imscpConfig{'CMD_PKILL'}, '-KILL', '-f', '-u', escapeShell($data->{'admin_sys_name'}), 'sshd');
		my ($stdout, $stderr);
		execute("@cmd", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		debug($stderr) if $stderr;

		my $jailBuilder;
		eval {
			$jailBuilder = InstantSSH::JailBuilder->new(
				id => ($self->{'config'}->{'shared_jail'}) ? 'shared_jail' : $data->{'admin_sys_name'},
				config => $self->{'config'}
			);
		};
		if($@) {
			error("Unable to create JailBuilder object: $@");
			return 1;
		}

		my $shell;
		if($data->{'ssh_permission_jailed_shell'}) { # Jailed shell
			$shell = $self->{'config'}->{'shells'}->{'jailed'};
			$homeDir .= '/./';

			# Create jail if needed
			my $rs = $jailBuilder->makeJail() unless $jailBuilder->existsJail();
			return $rs if $rs;

			# Add user in jail
			$rs = $jailBuilder->addUserToJail($data->{'admin_sys_name'}, $shell);
			return $rs if $rs;
		} else { # Full shell
			$shell = $self->{'config'}->{'shells'}->{'full'};

			# Ensure that user is not jailed
			if($jailBuilder->existsJail()) {
				if($self->{'config'}->{'shared_jail'}) {
					my $rs = $jailBuilder->removeUserFromJail($data->{'admin_sys_name'});
					return $rs if $rs;
				} else {
					my $rs = $jailBuilder->removeJail();
					return $rs if $rs;
				}
			}
		}

		# Update user homedir and shell ( must be done last to prevent any full SSH login while jail is bein created )
		my $pw = Unix::PasswdFile->new('/etc/passwd');
		$pw->home($data->{'admin_sys_name'}, $homeDir);
		$pw->shell($data->{'admin_sys_name'}, $shell);
		unless($pw->commit()) {
			error("Unable to update $data->{'admin_sys_name'} unix user properties");
			return 1;
		} else {
			return 0;
		}
	} else {
		error("Unable to find $data->{'admin_sys_name'} user homedir");
		return 1;
	}
}

=item _removeSshPermissions(\%data)

 Remove the given SSH permissions

 Param hash \%data SSH permissions data
 Return int 0 on success, other on failure

=cut

sub _removeSshPermissions
{
	my($self, $data) = @_;

	my $homeDir = (getpwnam($data->{'admin_sys_name'}))[7];

	if(defined $homeDir) {
		$homeDir = normalizePath($homeDir);

		# Force logout of ssh logins if any
		my @cmd = ($main::imscpConfig{'CMD_PKILL'}, '-KILL', '-f', '-u', escapeShell($data->{'admin_sys_name'}), 'sshd');
		my ($stdout, $stderr);
		execute("@cmd", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		debug($stderr) if $stderr;

		# Set the user homedir and shell back to default values

		my $pw = Unix::PasswdFile->new('/etc/passwd');
		$pw->home($data->{'admin_sys_name'}, $homeDir);
		$pw->shell($data->{'admin_sys_name'}, '/bin/false');
		unless($pw->commit()) {
			error("Unable to update $data->{'admin_sys_name'} unix user properties");
			return 1;
		}

		my $jailBuilder;
		eval {
			$jailBuilder = InstantSSH::JailBuilder->new(
				id => ($self->{'config'}->{'shared_jail'}) ? 'shared_jail' : $data->{'admin_sys_name'},
				config => $self->{'config'}
			);
		};
		if($@) {
			error("Unable to create JailBuilder object: $@");
			return 1;
		}

		if($jailBuilder->existsJail()) {
			# Remove user from jail (also the jail if per user jail and task != change)
			if($self->{'config'}->{'shared_jail'} || $self->{'action'} eq 'change') {
				my $rs = $jailBuilder->removeUserFromJail($data->{'admin_sys_name'});
				return $rs if $rs;
			} else {
				my $rs = $jailBuilder->removeJail();
				return $rs if $rs;
			}
		}

		# Remove $HOME/.ssh directory if any
		if(-d "$homeDir/.ssh") {
			my $isProtectedHomeDir = isImmutable($homeDir);

			clearImmutable($homeDir);
			clearImmutable("$homeDir/.ssh", 'recursive');

			my $rs = iMSCP::Dir->new( dirname => "$homeDir/.ssh" )->remove();
			return $rs if $rs;

			setImmutable($homeDir) if $isProtectedHomeDir;
		}
	} else {
		error("Unable to find $data->{'admin_sys_name'} user homedir");
		return 1;
	}

	0;
}

=item _addSshKey(\%data)

 Adds the given SSH key

 Param hash \%data SSH key data
 Return int 0 on success, other on failure

=cut

sub _addSshKey
{
	my($self, $data) = @_;

	my $homeDir = (getpwnam($data->{'admin_sys_name'}))[7];

	if(defined $homeDir) {
		$homeDir = normalizePath($homeDir);

		my $isProtectedHomeDir = isImmutable($homeDir);
		clearImmutable($homeDir) if $isProtectedHomeDir;
		clearImmutable("$homeDir/.ssh") if -d "$homeDir/.ssh";

		# Create $HOME/.ssh directory of set its permissions if it already exists
		my $rs = iMSCP::Dir->new( dirname => "$homeDir/.ssh" )->make(
			{ user => $data->{'admin_sys_name'}, group => $data->{'admin_sys_gname'}, mode => 0700 }
		);

		my $file = iMSCP::File->new( filename => "$homeDir/.ssh/authorized_keys" );
		my $fileContent = '';

		if(-f "$homeDir/.ssh/authorized_keys") {
			$fileContent = $file->get();
			unless(defined $fileContent) {
				error("Unable to read $homeDir/.ssh/authorized_keys");
				return 1;
			}

			# Prevent duplicate entry
			my $sshKeyReg = quotemeta($data->{'ssh_key'});
			$fileContent =~ s/[^\n]*?$sshKeyReg\n//;
		}

		# Add ssh key in authorized_keys file
		$fileContent .= "$data->{'ssh_auth_options'} $data->{'ssh_key'}\n";

		$rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->mode(0600);
		return $rs if $rs;

		$rs = $file->owner($data->{'admin_sys_name'}, $data->{'admin_sys_gname'});
		return $rs if $rs;

		setImmutable("$homeDir/.ssh", 'recursive');
		setImmutable($homeDir) if $isProtectedHomeDir;
	} else {
		error("Unable to find $data->{'admin_sys_name'} user homedir");
		return 1;
	}

	0;
}

=item _deleteSshKey(\%data)

 Delete the given SSH key

 Param hash \%data SSH key data
 Return int 0 on success, other on failure

=cut

sub _deleteSshKey
{
	my($self, $data) = @_;

	# Force logout of ssh login if any
	my @cmd = ($main::imscpConfig{'CMD_PKILL'}, '-KILL', '-f', '-u', escapeShell($data->{'admin_sys_name'}), 'sshd');
	my ($stdout, $stderr);
	execute("@cmd", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	debug($stderr) if $stderr;

	my $homeDir = (getpwnam($data->{'admin_sys_name'}))[7];

	if(defined $homeDir) {
		$homeDir = normalizePath($homeDir);

		if(-d "$homeDir/.ssh") {
			clearImmutable("$homeDir/.ssh", 'recursive');

			if(-f "$homeDir/.ssh/authorized_keys") {
				my $file = iMSCP::File->new( filename => "$homeDir/.ssh/authorized_keys" );
				my $fileContent = $file->get();

				if(defined $fileContent) {
					my $sshKeyReg = quotemeta($data->{'ssh_key'});
					$fileContent =~ s/[^\n]*?$sshKeyReg\n//;

					my $rs = $file->set($fileContent);
					return $rs if $rs;

					$rs = $file->save();
					return $rs if $rs;

					$rs = $file->mode(0600);
					return $rs if $rs;

					$rs = $file->owner($data->{'admin_sys_name'}, $data->{'admin_sys_gname'});
					return $rs if $rs;
				} else {
					error("Unable to read $homeDir/.ssh/authorized_keys");
					return 1;
				}
			}

			setImmutable("$homeDir/.ssh", 'recursive');
		}
	} else {
		error("Unable to find $data->{'admin_sys_name'} user homedir");
		return 1;
	}

	0;
}

=item _checkRequirements()

 Check for requirements

 Return int 0 if all requirements are meet, other otherwise

=cut

sub _checkRequirements
{
	my $ret = 0;

	for my $package (qw/libpam-chroot makejail/) {
		my ($stdout, $stderr);
		my $rs = execute(
			"LANG=C dpkg-query --show --showformat '\${Status}' $package | cut -d ' ' -f 3", \$stdout, \$stderr
		);
		debug($stdout) if $stdout;
		if($stdout ne 'installed') {
			error("The $_ package is not installed on your system");
			$ret ||= 1;
		}
	}

	# Process dedicated test for busybox
	# This allow the admin to install either the busybox package or the
	# busybox-static package, as a own compiled version
	unless(-x '/bin/busybox') {
		error("The busybox package is not installed on your system");
		$ret ||= 1;
	}

	$ret;
}

=item _configurePamChroot($uninstall = false)

 Configure pam chroot

 Param bool $uninstall OPTIONAL Whether pam chroot configuration must be removed (default: false)
 Return int 0 on success, other on failure

=cut

sub _configurePamChroot
{
	my $uninstall = $_[1] // 0;

	if(-f '/etc/pam.d/sshd') {
		my $file = iMSCP::File->new( filename => '/etc/pam.d/sshd' );

		my $fileContent = $file->get();
		unless(defined $fileContent) {
			error('Unable to read file /etc/pam.d/sshd');
			return 1;
		}

		unless($uninstall) {
			# Remove any pam_motd.so and pam_chroot.so lines
			# Note: pam_motd lines must be moved below the pam_chroot declaration because we want read motd from jail
			$fileContent =~ s/^session\s+.*?pam_(?:chroot|motd)\.so.*?\n//gm;

			$fileContent .= "session required pam_chroot.so debug\n";
			$fileContent .= "session optional pam_motd.so motd=/run/motd.dynamic\n";

			# The pam_motd module shipped with libpam-modules versions oldest than 1.1.3-7 doesn't provide the
			# 'noupdate' option. Thus, we must check the package version

			my ($stdout, $stderr);
			my $rs = execute('dpkg-query --show --showformat \'${Version}\' libpam-modules', \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $rs && $stderr;
			return $rs if $rs;

			my $ret = execute("dpkg --compare-versions $stdout lt 1.1.3-7", \$stdout, \$stderr);
			error($stderr) if $stderr;
			return 1 if $stderr;

			$fileContent .= ($ret) ? "session optional pam_motd.so noupdate\n" : "session optional pam_motd.so\n";
		} else {
			$fileContent =~ s/^session\s+.*?pam_chroot\.so.*?\n//gm;
		}

		my $rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;
	} else {
		error('File /etc/pam.d/sshd not found');
		return 1;
	}

	0;
}

=item _configureBusyBox($uninstall = false)

 Configure BusyBox

 Param bool $uninstall OPTIONAL Whether BusyBox configuration must be removed (default: false)
 Return int 0 on success, other on failure

=cut

sub _configureBusyBox
{
	my $uninstall = $_[1] // 0;

	# Handle /bin/ash symlink

	if(-e '/bin/ash') {
		if(-s _) {
			unless(unlink('/bin/ash')) {
				error('Unable to remove /bin/ash symlink');
				return 1;
			}
		} else {
			error('/bin/ash should be a symlink');
			return 1;
		}
	}

	unless($uninstall) {
		unless(symlink('/bin/busybox', '/bin/ash')) {
			error("Unable to create /bin/ash symlink to /bin/busybox: $!");
			return 1;
		}
	}

	# Handle /etc/shells file
	if(-f '/etc/shells') {
		my $file = iMSCP::File->new( filename => '/etc/shells' );

		my $fileContent = $file->get();
		unless(defined $fileContent) {
			error('Unable to read /etc/shells');
			return 1;
		}

		$fileContent =~ s%^/bin/ash\n%%gm;
		$fileContent .= "/bin/ash\n" unless $uninstall;

		my $rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;
	}

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
