#!/usr/bin/perl

=head1 NAME

 Plugin::InstantSSH;

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

use File::Basename ();
use Cwd ();
use lib Cwd::realpath(File::Basename::dirname(__FILE__));
use lib Cwd::realpath(File::Basename::dirname(__FILE__)) . '/Vendor';

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

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = $_[0];

	my $rs = $self->_checkRequirements();
	return $rs if $rs;

	$rs =$self->_configurePamChroot();
	return $rs if $rs;

	$self->_configureBusyBox();
}

=item update()

 Process update tasks

 Return int 0 on success, other on failure

=cut

sub update
{
	$_[0]->install();
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = $_[0];

	my $rs = $self->run();
	return $rs if $rs;

	if(-d "$self->{'config'}->{'root_jail_dir'}/shared_jail") {
		my $jailBuilder;
		eval { $jailBuilder = InstantSSH::JailBuilder->new( config => $self->{'config'}, user => 'root'); };
		if($@) {
			error("Unable to create JailBuilder object: $@");
			return 1;
		}

		$rs = $jailBuilder->removeJail();
		return $rs if $rs;
	}

	$rs = iMSCP::Dir->new( dirname => $self->{'config'}->{'root_jail_dir'} )->remove();
	return $rs if $rs;

	$rs = $self->_configurePamChroot('uninstall');
	return $rs if $rs;

	$self->_configureBusyBox('uninstall');
}

=item enable()

 Process enable tasks

 Return int 0, other on failure

=cut

sub enable
{
	my $self = $_[0];

	my $qrs = $self->{'db'}->doQuery('dummy', "UPDATE instant_ssh_keys SET ssh_key_status = 'toenable'");
	unless(ref $qrs eq 'HASH') {
		error($qrs);
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

	my $qrs = $self->{'db'}->doQuery('dummy', "UPDATE instant_ssh_keys SET ssh_key_status = 'todisable'");
	unless(ref $qrs eq 'HASH') {
		error($qrs);
		return 1;
	}

	$self->run();
}

=item change

 Process change tasks

 Return int 0, other on failure

=cut

sub change
{
	my $self = $_[0];

	my $jailRootDir = $self->{'config'}->{'root_jail_dir'};

	# Retrieve list of jails
	my @jailDirs = iMSCP::Dir->new( dirname => $jailRootDir )->getDirs();

	if(@jailDirs) {
		for my $jailDir(@jailDirs) {
			my %fakeCfg = %{$self->{'config'}};

			if ($jailDir eq 'shared_jail') {
				$fakeCfg{'shared_jail'} = 1;
			} else {
				$fakeCfg{'shared_jail'} = 0;
			}

			my $jailBuilder;
			eval {
				$jailBuilder = InstantSSH::JailBuilder->new(
					config => { %fakeCfg }, user => ($jailDir eq 'shared_jail') ? 'root' : $jailDir
				);
			};
			if($@) {
				error("Unable to create JailBuilder object: $@");
				return 1;
			}

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

	my $qrs = $self->{'db'}->doQuery(
		'dummy',
		"
			UPDATE
				instant_ssh_permissions
			SET
				ssh_permission_status = 'tochange'
			WHERE
				ssh_permission_status NOT IN ('toadd', 'todelete')
		"
	);
	unless(ref $qrs eq 'HASH') {
		error($qrs);
		return 1;
	}

	$self->run();
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
				ssh_permission_status in('toadd', 'tochange', 'todelete')
		"
	);
	unless($sth) {
		$self->{'RETVAL'} = 1; # Not an error related to a specific plugin item so we force retval
		error("Couldn't prepare SQL statement: " . $dbh->errstr);
		return 1;
	}

	unless($sth->execute()) {
		error("Couldn't execute prepared statement: " . $dbh->errstr);
		return 1;
	}

	while (my $sshPermissionData = $sth->fetchrow_hashref()) {
		my $sshPermissionStatus = $sshPermissionData->{'ssh_permission_status'};

		my @sql;

		if($sshPermissionStatus ~~ ['toadd', 'tochange']) {
			$rs = $self->_addSshPermissions($sshPermissionData);
			@sql = (
				'dummy',
				'UPDATE instant_ssh_permissions SET ssh_permission_status = ? WHERE ssh_permission_id = ?',
				($rs ? scalar getMessageByType('error') : 'ok'),
				$sshPermissionData->{'ssh_permission_id'}
			);

			$customerShells{$sshPermissionData->{'admin_sys_name'}} = $sshPermissionData->{'ssh_permission_jailed_shell'};
		} elsif($sshPermissionStatus eq 'todelete') {
			$rs = $self->_removeSshPermissions($sshPermissionData);
			unless($rs) {
				@sql = (
					'dummy',
					'DELETE FROM instant_ssh_permissions WHERE ssh_permission_id = ?',
					$sshPermissionData->{'ssh_permission_id'}
				);
			} else {
				@sql = (
					'dummy',
					'UPDATE instant_ssh_permissions SET ssh_permission_status = ? WHERE ssh_permission_id = ?',
					scalar getMessageByType('error'),
					$sshPermissionData->{'ssh_permission_id'}
				);
			}
		}

		my $qrs = $self->{'db'}->doQuery(@sql);
		unless(ref $qrs eq 'HASH') {
			$self->{'RETVAL'} = 1; # Not an error related to a specific plugin item so we force retval
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
		$self->{'RETVAL'} = 1; # Not an error related to a specific plugin item so we force retval
		error("Couldn't prepare SQL statement: " . $dbh->errstr);
		return 1;
	}

	unless($sth->execute()) {
		error("Couldn't execute prepared statement: " . $dbh->errstr);
		return 1;
	}

	while (my $sshKeyData = $sth->fetchrow_hashref()) {
		my $sshKeyStatus = $sshKeyData->{'ssh_key_status'};

		my @sql;

		if($sshKeyStatus ~~ ['toadd', 'tochange', 'toenable']) {
			$rs = $self->_addSshKey($sshKeyData);
			@sql = (
				'dummy', 'UPDATE instant_ssh_keys SET ssh_key_status = ? WHERE ssh_key_id = ?',
				($rs ? scalar getMessageByType('error') : 'ok'),
				$sshKeyData->{'ssh_key_id'}
			);
		} elsif($sshKeyStatus ~~ ['todisable', 'todelete']) {
			$rs = $self->_deleteSshKey($sshKeyData);
			unless($rs) {
				if($sshKeyStatus eq 'todisable') {
					@sql = (
						'dummy', 'UPDATE instant_ssh_keys SET ssh_key_status = ? WHERE ssh_key_id = ?',
						($rs ? scalar getMessageByType('error') : 'disabled'),
						$sshKeyData->{'ssh_key_id'}
					);
				} else {
					@sql = ('dummy', 'DELETE FROM instant_ssh_keys WHERE ssh_key_id = ?', $sshKeyData->{'ssh_key_id'});
				}
			} else {
				@sql = (
					'dummy', 'UPDATE instant_ssh_keys SET ssh_key_status = ? WHERE ssh_key_id = ?',
					scalar getMessageByType('error'),
					$sshKeyData->{'ssh_key_id'}
				);
			}
		}

		my $qrs = $self->{'db'}->doQuery(@sql);
		unless(ref $qrs eq 'HASH') {
			$self->{'RETVAL'} = 1; # Not an error related to a specific plugin item so we force retval
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

 Event listener which is responsible to remove SSH permission prior domain deletion (customer account)

 Param hash $domainData Domain data
 Return int 0 on success, other on failure

=cut

sub onDeleteDomain
{
	my ($self, $domainData) = @_;

	if($domainData->{'DOMAIN_TYPE'} eq 'dmn') {
		my $homeDir = (getpwnam($domainData->{'USER'}))[7];

		if(defined $homeDir && -d "$homeDir/.ssh") {
			# Force logout of ssh login if any
			my @cmd = ($main::imscpConfig{'CMD_PKILL'}, '-KILL', '-f', '-u', escapeShell($domainData->{'USER'}), 'sshd');
			my ($stdout, $stderr);
			execute("@cmd", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			debug($stderr) if $stderr;

			my $jailBuilder;
			eval {
				$jailBuilder = InstantSSH::JailBuilder->new(
					config => $self->{'config'}, user => $domainData->{'USER'}
				);
			};
			if($@) {
				error("Unable to create JailBuilder object: $@");
				return 1;
			}

			if($jailBuilder->existsJail()) {
				my $rs = ($self->{'config'}->{'shared_jail'})
					? $jailBuilder->removeUserFromJail() : $jailBuilder->removeJail();
				return $rs if $rs;
			}

			# Remove $HOME/.ssh directory
			my $isProtectedHomeDir = isImmutable($homeDir);

			clearImmutable($homeDir) if $isProtectedHomeDir;
			clearImmutable("$homeDir/.ssh/authorized_keys") if -f "$homeDir/.ssh/authorized_keys";

			my $rs = iMSCP::Dir->new( dirname => "$homeDir/.ssh" )->remove();
			return $rs if $rs;

			setImmutable($homeDir) if $isProtectedHomeDir;
		} else {
			error("Unable to find $domainData->{'USER'} unix user homedir");
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
			$$homeDir .= '/./';
			$$shell = $self->{'config'}->{'shells'}->{'jailed'};
		} else {
			$$shell = $self->{'config'}->{'shells'}->{'normal'};
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

	$self->{'eventManager'}->register('onBeforeAddImscpUnixUser', sub { $self->onUnixUserUpdate(@_); });
	$self->{'eventManager'}->register('beforeHttpdDelDmn', sub { $self->onDeleteDomain(@_); });

	$self;
}

=item _addSshPermissions(\%sshPermissionData)

 Adds the given SSH permissions

 Param hash \%sshPermissionData SSH permissions
 Return int 0 on success, other on failure

=cut

sub _addSshPermissions
{
	my($self, $sshPermissionData) = @_;

	my $homeDir = (getpwnam($sshPermissionData->{'admin_sys_name'}))[7];

	if(defined $homeDir) {
		if($sshPermissionData->{'ssh_permission_status'} eq 'tochange') {
			# Force logout of ssh logins if any
			my @cmd = (
				$main::imscpConfig{'CMD_PKILL'}, '-KILL', '-f', '-u', escapeShell($sshPermissionData->{'admin_sys_name'}),
				'sshd'
			);
			my ($stdout, $stderr);
			execute("@cmd", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			debug($stderr) if $stderr;
		}

		my $jailBuilder;
		eval {
			$jailBuilder = InstantSSH::JailBuilder->new(
				config => $self->{'config'}, user => $sshPermissionData->{'admin_sys_name'}
			);
		};
		if($@) {
			error("Unable to create JailBuilder object: $@");
			return 1;
		}

		if($sshPermissionData->{'ssh_permission_jailed_shell'}) {
			# Create jail if needed (only add user in jail if the jail already exists)
			my $rs = ($jailBuilder->existsJail()) ? $jailBuilder->addUserToJail() : $jailBuilder->makeJail();
			return $rs if $rs;
		} elsif($jailBuilder->existsJail()) {
			# Ensure that user is not jailed (tochange case)
			my $rs = ($self->{'config'}->{'shared_jail'}) ?
				$jailBuilder->removeUserFromJail() : $jailBuilder->removeJail();
			return $rs if $rs;
		}

		# Update user homedir and shell
		my $shell = ($sshPermissionData->{'ssh_permission_jailed_shell'})
			? $self->{'config'}->{'shells'}->{'jailed'} : $self->{'config'}->{'shells'}->{'normal'};

		my $homeDir = normalizePath($homeDir);
		$homeDir .= '/./' if $sshPermissionData->{'ssh_permission_jailed_shell'};

		my $pw = Unix::PasswdFile->new('/etc/passwd');
		$pw->home($sshPermissionData->{'admin_sys_name'}, $homeDir);
		$pw->shell($sshPermissionData->{'admin_sys_name'}, $shell);
		unless($pw->commit()) {
			error('Unable to update user properties');
			return 1;
		} else {
			return 0;
		}

		#my ($stdout, $stderr);
		#my @cmd = (
		#	"$main::imscpConfig{'CMD_USERMOD'}",
		#	"-d $homeDir",
		#	"-s $shell",
		#	escapeShell($sshPermissionData->{'admin_sys_name'})
		#);
		#my $rs = execute("@cmd", \$stdout, \$stderr);
		#debug($stdout) if $stdout;
		#debug($stderr) if $stderr && $rs;
		#return $rs;
	} else {
		error("Unable to find $sshPermissionData->{'admin_sys_name'} user homedir");
		return 1;
	}
}

=item _removeSshPermissions(\%sshPermissionData)

 Remove the given SSH permissions

 Param hash \%sshPermissionData SSH Permissions
 Return int 0 on success, other on failure

=cut

sub _removeSshPermissions
{
	my($self, $sshPermissionData) = @_;

	my $homeDir = (getpwnam($sshPermissionData->{'admin_sys_name'}))[7];

	if(defined $homeDir) {
		$homeDir = normalizePath($homeDir);

		# Force logout of ssh logins if any
		my @cmd = (
			$main::imscpConfig{'CMD_PKILL'}, '-KILL', '-f', '-u', escapeShell($sshPermissionData->{'admin_sys_name'}),
			'sshd'
		);
		my ($stdout, $stderr);
		execute("@cmd", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		debug($stderr) if $stderr;

		# Set the user homedir and shell back to default values

		my $pw = Unix::PasswdFile->new('/etc/passwd');
		$pw->home($sshPermissionData->{'admin_sys_name'}, $homeDir);
		$pw->shell($sshPermissionData->{'admin_sys_name'}, '/bin/false');
		unless($pw->commit()) {
			error('Unable to update user properties');
			return 1;
		}

		#@cmd = (
		#	"$main::imscpConfig{'CMD_USERMOD'}",
		#	"-d $homeDir",
		#	'-s /bin/false',
		#	escapeShell($sshPermissionData->{'admin_sys_name'})
		#);
		#my $rs = execute("@cmd", \$stdout, \$stderr);
		#debug($stdout) if $stdout;
		#debug($stderr) if $stderr && $rs;
		#return $rs if $rs;

		# Remove user from jail (also the jail if per user jail)
		if($sshPermissionData->{'ssh_permission_jailed_shell'}) {
			my $jailBuilder;
			eval {
				$jailBuilder = InstantSSH::JailBuilder->new(
					config => $self->{'config'}, user => $sshPermissionData->{'admin_sys_name'}
				);
			};
			if($@) {
				error("Unable to create JailBuilder object: $@");
				return 1;
			}

			my $rs = ($self->{'config'}->{'shared_jail'}) ? $jailBuilder->removeUserFromJail() : $jailBuilder->removeJail();
			return $rs if $rs;
		}

		# Remove $HOME/.ssh directory if any (this remove all SSH keys)
		if(-d "$homeDir/.ssh") {
			my $isProtectedHomeDir = isImmutable($homeDir);

			clearImmutable($homeDir) if $isProtectedHomeDir;
			clearImmutable("$homeDir/.ssh/authorized_keys") if -f "$homeDir/.ssh/authorized_keys";

			my $rs = iMSCP::Dir->new( dirname => "$homeDir/.ssh" )->remove();
			return $rs if $rs;

			setImmutable($homeDir) if $isProtectedHomeDir;
		}
	} else {
		error("Unable to find $sshPermissionData->{'admin_sys_name'} user homedir");
		return 1;
	}

	0;
}

=item _addSshKey(\%sshKeyData)

 Adds the given SSH key

 Param hash \%sshKeyData SSH key data
 Return int 0 on success, other on failure

=cut

sub _addSshKey
{
	my($self, $sshKeyData) = @_;

	my $homeDir = (getpwnam($sshKeyData->{'admin_sys_name'}))[7];

	if(defined $homeDir) {
		$homeDir = normalizePath($homeDir);

		my $isProtectedHomeDir = isImmutable($homeDir);

		my $file = iMSCP::File->new( filename => "$homeDir/.ssh/authorized_keys" );
		my $fileContent;

		unless(-f "$homeDir/.ssh/authorized_keys") {
			clearImmutable($homeDir) if $isProtectedHomeDir;

			# Create $HOME/.ssh directory of set its permissions if it already exists
			my $rs = iMSCP::Dir->new( dirname => "$homeDir/.ssh" )->make(
				{ user => $sshKeyData->{'admin_sys_name'}, group => $sshKeyData->{'admin_sys_gname'}, mode => 0700 }
			);

			setImmutable($homeDir) if $isProtectedHomeDir;
		} else {
			clearImmutable("$homeDir/.ssh/authorized_keys");
			$fileContent = $file->get() || '';
		}

		# Add ssh key in authorized_keys file
		my $sshKeyReg = quotemeta($sshKeyData->{'ssh_key'});
		$fileContent =~ s/[^\n]*?$sshKeyReg\n//;
		$fileContent .= "$sshKeyData->{'ssh_auth_options'} $sshKeyData->{'ssh_key'}\n";

		my $rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->mode(0600);
		return $rs if $rs;

		$rs = $file->owner($sshKeyData->{'admin_sys_name'}, $sshKeyData->{'admin_sys_gname'});
		return $rs if $rs;

		setImmutable("$homeDir/.ssh/authorized_keys");
	} else {
		error("Unable to find $sshKeyData->{'admin_sys_name'} user homedir");
		return 1;
	}

	0;
}

=item _deleteSshKey(\%sshKeyData)

 Delete the given SSH key

 Param hash \%sshKeyData SSH key data
 Return int 0 on success, other on failure

=cut

sub _deleteSshKey
{
	my($self, $sshKeyData) = @_;

	# Force logout of ssh login if any
	my @cmd = (
		$main::imscpConfig{'CMD_PKILL'}, '-KILL', '-f', '-u', escapeShell($sshKeyData->{'admin_sys_name'}), 'sshd'
	);
	my ($stdout, $stderr);
	execute("@cmd", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	debug($stderr) if $stderr;

	my $homeDir = (getpwnam($sshKeyData->{'admin_sys_name'}))[7];

	if(defined $homeDir) {
		$homeDir = normalizePath($homeDir);

		if(-f "$homeDir/.ssh/authorized_keys") {
			clearImmutable("$homeDir/.ssh/authorized_keys");

			my $file = iMSCP::File->new( filename => "$homeDir/.ssh/authorized_keys" );
			my $fileContent = $file->get();

			if(defined $fileContent) {
				my $sshKeyReg = quotemeta($sshKeyData->{'ssh_key'});
				$fileContent =~ s/[^\n]*?$sshKeyReg\n//;

				my $rs = $file->set($fileContent);
				return $rs if $rs;

				$rs = $file->save();
				return $rs if $rs;

				$rs = $file->mode(0600);
				return $rs if $rs;

				$rs = $file->owner($sshKeyData->{'admin_sys_name'}, $sshKeyData->{'admin_sys_gname'});
				return $rs if $rs;

				setImmutable("$homeDir/.ssh/authorized_keys");
			} else {
				error("Unable to read $homeDir/.ssh/authorized_keys");
				return 1;
			}
		}
	} else {
		error("Unable to find $sshKeyData->{'admin_sys_name'} user homedir");
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

	for my $package (qw/busybox libpam-chroot makejail/) {
		my ($stdout, $stderr);
		my $rs = execute("dpkg -s $package", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error("The $_ package is not installed on your system") if $rs;
		$ret |= $rs;
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
			$fileContent =~ s/^session\s+.*?pam_(?:chroot|motd|)\.so.*?\n//gm;

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
