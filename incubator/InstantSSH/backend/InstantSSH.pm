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

use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::Ext2Attributes qw(clearImmutable isImmutable setImmutable);

use parent 'Common::SingletonClass';

our $seenCustomers = [];

=head1 DESCRIPTION

 This package provide the backend part of the InstantSSH plugin.

=head1 PUBLIC METHODS

=over 4

=item enable()

 Enable plugin

 Return int 0, other on failure

=cut

sub enable
{
	my $self = $_[0];

	my $qrs = $self->{'db'}->doQuery(
		'dummy', 'UPDATE instant_ssh_keys SET ssh_key_status = ? WHERE ssh_permission_id IS NOT NULL', 'toenable'
	);
	unless(ref $qrs eq 'HASH') {
		error($qrs);
		return 1;
	}

	$self->run();
}

=item disable()

 Disable plugin

 Return int 0, other on failure

=cut

sub disable
{
	my $self = $_[0];

	my $qrs = $self->{'db'}->doQuery(
		'dummy', 'UPDATE instant_ssh_keys SET ssh_key_status = ? WHERE ssh_permission_id IS NOT NULL', 'todisable'
	);
	unless(ref $qrs eq 'HASH') {
		error($qrs);
		return 1;
	}

	$self->run();
}

=item run()

 Initialize instance

 Return int 0

=cut

sub run
{
	my $self = $_[0];

	my ($rs, $ret, $qrs) = (0, 0, undef);
	$seenCustomers = []; # Reset seen customer stack

	# Remove SSH permissions

	# Workflow for one customer:
	# When SSH permissions are deleted for a customer, the ssh_permission_id field of all its ssh keys is
	# automatically set to NULL. Therefore, to avoid useless works (all SSH keys have to be deleted), we are retrieving
	# only one key that belongs to the customer and we trigger the SSH permissions deletion task for this customer.
	# This way, we avoid to delete all SSH keys one by one. Instead, we remove the entire .ssh directory and we set the
	# shell back to /bin/false. Once the process is successfully done, we delete all SSH key entries from the database
	# for this customer.

	my $admins = $self->{'db'}->doQuery(
		'ssh_key_admin_id',
		'
			SELECT
				ssh_key_admin_id, admin_sys_name
			FROM
				instant_ssh_keys
			INNER JOIN
				admin ON(admin_id = ssh_key_admin_id)
			WHERE
				ssh_permission_id IS NULL
			GROUP BY
				ssh_key_admin_id
		'
	);
	unless(ref $admins eq 'HASH') {
		error($admins);
		$ret = 1;
	} elsif(%{$admins}) {
		for(keys %{$admins}) {
			$rs = $self->_deleteSshPermissions($admins->{$_}->{'admin_sys_name'});

			unless($rs) {
				$qrs = $self->{'db'}->doQuery(
					'dummy', 'DELETE FROM instant_ssh_keys WHERE ssh_key_admin_id = ? AND ssh_permission_id IS NULL', $_
				);

				unless(ref $qrs eq 'HASH') {
					error($qrs);
					$rs = 1;
				};
			}

			$ret ||= $rs;
		}
	}

	# Add/Update/enable/disable/delete SSH keys

	my $sshKeys = $self->{'db'}->doQuery(
		'ssh_key_id',
		"
			SELECT
				t1.*, t2.admin_sys_name, t2.admin_sys_gname
			FROM
				instant_ssh_keys AS t1
			INNER JOIN
				admin AS t2 ON(admin_id = ssh_key_admin_id)
			WHERE
				ssh_key_status NOT IN ('ok', 'disabled')
			AND
				ssh_permission_id IS NOT NULL
		"
	);
	unless(ref $sshKeys eq 'HASH') {
		error($sshKeys);
		$ret ||= 1;
	} elsif(%{$sshKeys}) {
		for(keys %{$sshKeys}) {
			my $sshKeyStatus = $sshKeys->{$_}->{'ssh_key_status'};

			if($sshKeyStatus ~~ ['toadd', 'tochange', 'toenable']) {
				$rs = $self->_addSshKey($sshKeys->{$_});

				push @{$seenCustomers}, $sshKeys->{$_}->{'ssh_key_admin_id'} if not $_ ~~ $seenCustomers;

				$qrs = $self->{'db'}->doQuery(
					'dummy', 'UPDATE instant_ssh_keys SET ssh_key_status = ? WHERE ssh_key_id = ?',
					($rs ? scalar getMessageByType('error') : 'ok'), $_
				);

				error($qrs) unless ref $qrs eq 'HASH';
			} elsif($sshKeyStatus eq 'todelete') {
				$rs = $self->_deleteSshKey($sshKeys->{$_});

				unless($rs) {
					$qrs = $self->{'db'}->doQuery('dummy', 'DELETE FROM instant_ssh_keys WHERE ssh_key_id = ?', $_);
				} else {
					$qrs = $self->{'db'}->doQuery(
						'dummy', 'UPDATE instant_ssh_keys SET ssh_key_status = ? WHERE ssh_key_id = ?',
						scalar getMessageByType('error'), $_
					);
				}

				error($qrs) unless ref $qrs eq 'HASH';
			} elsif($sshKeyStatus eq 'todisable') {
				if(not $_ ~~ $seenCustomers) {
					$rs = $self->_deleteSshPermissions($sshKeys->{$_}->{'admin_sys_name'});

					push @{$seenCustomers}, $sshKeys->{$_}->{'ssh_key_admin_id'} unless $rs;

					$qrs = $self->{'db'}->doQuery(
						'dummy', 'UPDATE instant_ssh_keys SET ssh_key_status = ? WHERE ssh_key_id = ?',
						($rs ? scalar getMessageByType('error') : 'disabled'), $_
					);

					error($qrs) unless ref $qrs eq 'HASH';
				} else {
					$qrs = $self->{'db'}->doQuery(
						'dummy', 'UPDATE instant_ssh_keys SET ssh_key_status = ? WHERE ssh_key_id = ?', 'disabled', $_
					);
					unless(ref $qrs eq 'HASH') {
						error($qrs);
						$rs = 1;
					};
				}
			}

			$ret ||= $rs;
		}
	}

	$ret;
}

=back

=head1 EVENT LISTENERS

=over 4

=item deleteDomain(\%data)

 Initialize instance

 Return int 0 on success, other on failure

=cut

sub deleteDomain($)
{
	my $data = $_[0];

	if($data->{'DOMAIN_TYPE'} eq 'dmn') {
		my $username = $data->{'USER'};
		my $homeDir = File::HomeDir->users_home($username);
		my $isProtectedHomeDir = isImmutable($homeDir);
		my $rs = 0;

		if(defined $homeDir) {
			if(-f "$homeDir/.ssh/authorized_keys") {
				# Force logout of ssh login if any
				my @cmd = ($main::imscpConfig{'CMD_PKILL'}, '-KILL', '-f', '-u', escapeShell($username), 'sshd');
				execute("@cmd");

				clearImmutable($homeDir);
				clearImmutable("$homeDir/.ssh/authorized_keys");

				$rs = iMSCP::Dir->new('dirname' => "$homeDir/.ssh")->remove();
				return $rs if $rs;

				setImmutable($homeDir) if $isProtectedHomeDir;
			}
		} else {
			error("Unable to retrieve $username unix user home dir");
    		return 1;
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

	eval { require File::HomeDir };
	fatal('The InstantSSH plugin require the File::HomeDir Perl module. Pleas, read the plugin documentation') if $@;

	$self->{'db'} = iMSCP::Database->factory();
	$self->{'isListenerRegistered'} = 0;

	$self->{'hooksManager'}->register('beforeHttpdDelDmn', \&deleteDomain());

	$self;
}

=item _addSshKey(\%sshKeyData)

 Add the given SSH key

 Return int 0 on success, other on failure

=cut

sub _addSshKey($$)
{
	my($self, $sshKeyData) = @_;

	my $homeDir = File::HomeDir->users_home($sshKeyData->{'admin_sys_name'});
	my $isProtectedHomeDir = isImmutable($homeDir);
	my $rs = 0;

	if(defined $homeDir) {
		clearImmutable($homeDir);

		if(not $sshKeyData->{'ssh_key_admin_id'} ~~ $seenCustomers) {
			$rs = iMSCP::Dir->new('dirname' => "$homeDir/.ssh")->make(
				{
					'user' => $sshKeyData->{'admin_sys_name'},
					'group' => $sshKeyData->{'admin_sys_gname'},
					'mode' => 0700
				}
			);

			my @cmd = (
				"$main::imscpConfig{'CMD_USERMOD'}", '-s /bin/bash', escapeShell($sshKeyData->{'admin_sys_name'})
			);
			my($stdout, $stderr);
			$rs = execute("@cmd", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			debug($stderr) if $stderr && $rs;
			return $rs if $rs;

			unless($self->{'isListenerRegistered'}) {
				$self->{'hooksManager'}->register( 'onBeforeAddImscpUnixUser', sub {
					 $$_[7] = '/bin/bash' if $_[0] ~~ $Plugin::KassaSSH::seenCustomers; 0;
				});

				$self->{'isListenerRegistered'} = 1;
			}
		}

		my $authorizedKeysFile = iMSCP::File->new('filename' => "$homeDir/.ssh/authorized_keys");
		my $authorizedKeysFileContent;

		if(-f "$homeDir/.ssh/authorized_keys") {
			clearImmutable("$homeDir/.ssh/authorized_keys");
			$authorizedKeysFileContent = $authorizedKeysFile->get()
		}

		$authorizedKeysFileContent = '' unless defined $authorizedKeysFileContent;

		my $sshKeyReg = quotemeta($sshKeyData->{'ssh_key'});
		$authorizedKeysFileContent =~ s/[^\n]*?$sshKeyReg\n//;
		$authorizedKeysFileContent .= "$sshKeyData->{'ssh_auth_options'} $sshKeyData->{'ssh_key'}\n";

		$rs = $authorizedKeysFile->set($authorizedKeysFileContent);
		return $rs if $rs;

		$rs = $authorizedKeysFile->save();
		return $rs if $rs;

		$rs = $authorizedKeysFile->mode(0600);
		return $rs if $rs;

		$rs = $authorizedKeysFile->owner($sshKeyData->{'admin_sys_name'}, $sshKeyData->{'admin_sys_gname'});
		return $rs if $rs;

		setImmutable("$homeDir/.ssh/authorized_keys");

		setImmutable($homeDir) if $isProtectedHomeDir;
	} else {
		error("Unable to retrieve $sshKeyData->{'admin_sys_name'} user home directory");
		return 1;
	}

	0;
}

=item _deleteSshKey(\%sshKeyData)

 Delete the given SSH key

 Return int 0 on success, other on failure

=cut

sub _deleteSshKey($$)
{
	my($self, $sshKeyData) = @_;

	my $homeDir = File::HomeDir->users_home($sshKeyData->{'admin_sys_name'});
	my $rs = 0;

	if(defined $homeDir) {
		if(-f "$homeDir/.ssh/authorized_keys") {
			clearImmutable("$homeDir/.ssh/authorized_keys");

			my $authorizedKeysFile = iMSCP::File->new('filename' => "$homeDir/.ssh/authorized_keys");
			my $authorizedKeysFileContent = $authorizedKeysFile->get();

			if(defined $authorizedKeysFileContent) {
				my $sshKeyReg = quotemeta($sshKeyData->{'ssh_key'});
				$authorizedKeysFileContent =~ s/[^\n]*?$sshKeyReg\n//;

				if($authorizedKeysFileContent eq '') {
					$rs = $self->_deleteSshPermissions($sshKeyData->{'admin_sys_name'});
					return $rs if $rs;
				} else {
					$rs = $authorizedKeysFile->set($authorizedKeysFileContent);
					return $rs if $rs;

					$rs = $authorizedKeysFile->save();
					return $rs if $rs;

					$rs = $authorizedKeysFile->mode(0600);
					return $rs if $rs;

					$rs = $authorizedKeysFile->owner($sshKeyData->{'admin_sys_name'}, $sshKeyData->{'admin_sys_gname'});
					return $rs if $rs;

					setImmutable("$homeDir/.ssh/authorized_keys");
				}
			} else {
				error("Unable to read $homeDir/.ssh/authorized_keys");
				return 1;
			}
		}
	} else {
		error("Unable to retrieve $sshKeyData->{'admin_sys_name'} user home directory");
		return 1;
	}

	0;
}

=item _deleteSshPermissions($adminSysName)

 Delete SSH permissions

 Return int 0 on success, other on failure

=cut

sub _deleteSshPermissions($$)
{
	my $adminSysName = $_[1];

	my $homeDir = File::HomeDir->users_home($adminSysName);
	my $isProtectedHomeDir = isImmutable($homeDir);
	my $rs = 0;

	if(defined $homeDir) {
		if(-f "$homeDir/.ssh/authorized_keys") {
			# Force logout of ssh login if any
			my @cmd = ($main::imscpConfig{'CMD_PKILL'}, '-KILL', '-f', '-u', escapeShell($adminSysName), 'sshd');
			execute("@cmd");

			clearImmutable($homeDir);
			clearImmutable("$homeDir/.ssh/authorized_keys");

			$rs = iMSCP::Dir->new('dirname' => "$homeDir/.ssh")->remove();
			return $rs if $rs;

			setImmutable($homeDir) if $isProtectedHomeDir;

			# Change customer unix user shell to /bin/false
			@cmd = ("$main::imscpConfig{'CMD_USERMOD'}", '-s /bin/false', escapeShell($adminSysName));
			my($stdout, $stderr);
			$rs = execute("@cmd", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			debug($stderr) if $stderr && $rs;
			return $rs if $rs;
		}
	} else {
		error("Unable to retrieve $adminSysName unix user home dir");
		return 1;
	}

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
