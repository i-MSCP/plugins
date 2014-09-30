#!/usr/bin/perl

=head1 NAME

 InstantSSH::JailBuilder;

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

package InstantSSH::JailBuilder;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::Rights;
use iMSCP::Execute;

use File::HomeDir;
use JSON;
use List::MoreUtils qw(uniq);

use parent 'Common::Object';

my %jailCfg = (
	'jail_dir' => '',
	'paths' => [],
	'packages' => [],
	'include_pkg_deps' => 0,
	'preserve_files' => [],
	'users' => [],
	'groups' => [],
	'devices' => [],
	'mounts' => [],
	'need_logsocket' => 0
);

my $makejailCfgDir = '/etc/makejail';
my $securityChrootCfgFile = '/etc/security/chroot.conf';

=head1 DESCRIPTION

 This package is part of the i-MSCP InstantSSH plugin. It provide jail builder which allow to build jailed shell
environments.

=head1 PUBLIC METHODS

=over 4

=item makeJail()

 Create or update jail
 Return int 0 on success, other on failure

=cut

sub makeJail
{
	my $self = $_[0];

	my $cfg = $self->{'config'};
	my $user = $self->{'user'};

	#  Create the jail directory if it doesn't already exists or set it permissions
	my $rs = iMSCP::Dir->new(
		'dirname' => $jailCfg{'jail_dir'}
	)->make(
		{ 'user' => $main::imscpConfig{'ROOT_USER'}, 'group' => $main::imscpConfig{'ROOT_GROUP'} => 'mode' => 0755 }
	);
	return $rs if $rs;

	my $makejailCfgfilePath = "$makejailCfgDir/InstantSSH" . (($cfg->{'shared_jail'}) ? '.py' : ".$user.py");

	# Build makejail configuration file if needed
	$rs = $self->_buildMakejailCfgfile($cfg, $user);
	return $rs if $rs;

	# Create/Update the jail
	my ($stdout, $stderr);
	$rs = execute("makejail $makejailCfgfilePath", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error("InstantSSH::JailBuilder:: $stderr") if $rs && $stderr;
	error('InstantSSH::JailBuilder: Unable to create/update jail for unknown reason') if $rs && !$stderr;
	return $rs if $rs;

	# Add user into jail
	$rs = $self->addUserToJail($user);
	return $rs if $rs;

	# TODO:
	# Create devices if needed
	# Create log socket if needed

	0;
}

=item addUserToJail($user)

 Adds the given unix user into the jail

 Param string $user Unix user
 Return int 0 on success, other on failure

=cut

sub addUserToJail
{
	my ($self, $user) = @_;

	my $group = (getgrgid((getpwnam($user))[3]))[0];

	unless(defined $group) {
		error(sprintf('InstantSSH::JailBuilder: Unable to find %s unix user group', $user));
		return 1;
	}

	# Create home directory for the given user inside the jail
	unless (-d $jailCfg{'jail_dir'} . "/home/$user") {
		my $rs = iMSCP::Dir->new(
			'dirname' => $jailCfg{'jail_dir'} . "/home/$user"
		)->make(
			{ 'user' => $main::imscpConfig{'ROOT_USER'}, 'group' => $main::imscpConfig{'ROOT_GROUP'}, 'mode' => 0755 }
		);
		return $rs if $rs;
	}

	# Set owner/group for user home directory inside the jail
	setRights($jailCfg{'jail_dir'} . "/home/$user", { 'user' => $user, 'group' => $group, 'mode' => '0750' });

	# TODO copy content from /etc/skel if any

	# Add user into the jailed passwd file if any
	my $rs = $self->addPasswdFile('/etc/passwd', $user);
	return $rs if $rs;

	# Add user group into the jailed group file if any
	$rs = $self->addPasswdFile('/etc/group', $group);
	return $rs if $rs;

#	my $homeDir = File::HomeDir->users_home($user);
#
#	if(defined $homeDir) {
#		# TODO mount user Web folder into the jail ($userhome => <jail_dir>/home/$user/web)
#		# $rs = $self->mount($homeDir, $jailCfg{'jail_dir'} . "/home/$user");
#		# Return $rs if $rs;
#	} else {
#		error(sprintf('InstantSSH::JailBuilder: Unable to retrieve %s unix user home dir', $user));
#		return 1;
#	}

	# Add user into security chroot file
	if(-f $securityChrootCfgFile) {
		my $file = iMSCP::File->new('filename' => $securityChrootCfgFile);

		my $fileContent = $file->get();
		unless(defined $fileContent) {
			error(sprintf('InstantSSH::JailBuilder: Unable to read file %s', $securityChrootCfgFile));
			return 1;
		}

		my ($userReg, $jailReg) = (quotemeta($user), quotemeta($jailCfg{'jail_dir'}));
		$fileContent =~ s/\n$userReg\s+$jailReg\n//;
		$fileContent .= "\n$user\t$jailCfg{'jail_dir'}\n";

		$rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;
	} else {
		error(sprintf('InstantSSH::JailBuilder: File %s not found', $securityChrootCfgFile));
		return 1;
	}

	0;
}

=item removeUserFromJail($user)

 Remove the given unix user from the jail

 Param string $user Unix user
 Return int 0 on success, other on failure

=cut

sub removeUserFromJail
{
	my ($self, $user) = @_;

	my $group = (getgrgid((getpwnam($user))[3]))[0];

	unless(defined $group) {
		error(sprintf('InstantSSH::JailBuilder: Unable to find %s unix user group', $user));
		return 1;
	}

	# TODO umount user Web folder from jail
	# TODO remove user homedir from jail

	# Remove user from the jailed passwd file if any
	my $rs = $self->removePasswdFile('/etc/passwd', $user);
	return $rs if $rs;

	# Remove user group from the jailed group file if any
	$rs = $self->removePasswdFile('/etc/group', $group);
	return $rs if $rs;

	# Remove user from security chroot file
	if(-f $securityChrootCfgFile) {
		my $file = iMSCP::File->new('filename' => $securityChrootCfgFile);

		my $fileContent = $file->get();
		unless(defined $fileContent) {
			error("InstantSSH::JailBuilder: Unable to read file %s", $securityChrootCfgFile);
			return 1;
		}

		my ($userReg, $jailReg) = (quotemeta($user), quotemeta($jailCfg{'jail_dir'}));
		$fileContent =~ s/\n$userReg\s+$jailReg\n//;

		$rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;
	}

	0;
}

=item addPasswdFile($file, $what)

 Add the given user/group into the passwd/group file of the Jail if any

 Param string $file Path of system passwd/group file
 Param string $what User/group name to add
 Return int 0 on success, 1 on failure

=cut

sub addPasswdFile
{
	my ($self, $file, $what) = @_;

	my $dest = $jailCfg{'jail_dir'} . $file;

	if(-f $dest) {
		if(open my $fh, '<', $file) {
			my @lines = <$fh>;
			close $fh;

			if(open $fh, '+<', $dest) {
				s/^(.*?):.*\n/$1/ for (my @outLines = <$fh>);

				if(not grep $_ eq $what, @outLines) {
					for my $line(@lines) {
						next if index($line, ':') == -1;
						my @fields = split ':', $line;

						if ($fields[0] eq $what) {
							$fields[5] = "/home/$what" if exists $fields[5]; # passwd file - override homedir field
							print $fh join ':', @fields;
							last;
						}
					}
				}

				close $fh;
			} else {
				error(sprintf("InstantSSH::JailBuilder: Unable to open file for writing: %s", $!));
				return 1;
			}
		} else {
			error(sprintf("InstantSSH::JailBuilder: Unable to open file for reading: %s", $!));
			return 1;
		}
	}

	0;
}

=item removePasswdFile($file, $what)

 Remove the given user/group from the passwd/group file of the Jail if any

 Param string $file Path of system passwd/group file
 Param string $what User/group name to remove
 Return int 0 on success, 1 on failure

=cut

sub removePasswdFile
{
	my ($self, $file, $what) = @_;

	my $dest = $jailCfg{'jail_dir'} . $file;

	if(-f $dest) {
		if(open my $fh, '<', $dest) {
			my @lines = <$fh>;
			close $fh;

			if(open $fh, '>', $dest) {
				$what = quotemeta($what);
				@lines = grep $_ !~ /^$what:.*\n/, @lines;
				print $fh "@lines";
				close $fh;
			} else {
				error(sprintf("InstantSSH::JailBuilder: Unable to open file for writing: %s", $!));
				return 1;
			}
		} else {
			error(sprintf("InstantSSH::JailBuilder: Unable to open file for reading: %s", $!));
			return 1;
		}
	}

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return InstantSSH:JailBuilder

=cut

sub _init
{
	my $self = $_[0];

	$self->{'user'} = '__unknown__' unless exists $self->{'user'};

	if($self->{'user'} ne '__unknown__' && getpwnam($self->{'user'})) {
		$self->{'config'} = {} unless exists $self->{'config'} && ref $self->{'config'} eq 'HASH';

		if(%{$self->{'config'}}) {
			if(exists $self->{'config'}->{'root_jail_dir'}) {
				$jailCfg{'jail_dir'} = $self->{'config'}->{'root_jail_dir'} .
					(($self->{'config'}->{'shared_jail'}) ? '/shared_jail' : "/$self->{'user'}");
			} else {
				die("InstantSSH::JailBuilder: The 'root_jail_dir' option is not defined");
			}
		} else {
			die("InstantSSH::JailBuilder: Invalid or missing 'config' parameter")
		}
	} else {
		if($self->{'user'} eq '__unknown__') {
			die("InstantSSH::JailBuilder: The 'user' parameter is missing");
		} else {
			die(sprintf("InstantSSH::JailBuilder: The %s unix user doesn't exists", $self->{'user'}));
		}
	}

	$self;
}

=item _buildMakejailCfgfile()

 Build makejail configuration file

 Param hash \%cfg Hash containing Jail configuration options
 Return int 0 on success, other on failure

=cut

sub _buildMakejailCfgfile
{
	my ($self, $cfg, $user) = @_;

	$cfg = {} unless $cfg && ref $cfg eq 'HASH';

	if(exists $cfg->{'preserve_files'}) {
		if(ref $cfg->{'preserve_files'} eq 'ARRAY') {
			@{$jailCfg{'preserve_files'}} = @{$cfg->{'preserve_files'}} if exists $cfg->{'preserve_files'};
		} else {
			error("InstantSSH::JailBuilder: The 'preserve_files' option must be an array");
			return 1;
		}
	}

	if(exists $cfg->{'app_sections'}) {
		# Process sections as specified in app_sections configuration options.
		if(ref $cfg->{'app_sections'} eq 'ARRAY') {
			for my $section(@{$cfg->{'app_sections'}}) {
				if(exists $cfg->{$section}) {
					$self->_handleAppsSection($cfg, $section);
				} else {
					error(sprinf(
						"InstantSSH::JailBuilder: The %s application section doesn't exists", $cfg->{$section}
					));
					return 1;
				}
			}
		} else {
			error("InstantSSH::JailBuilder: The 'app_sections' option must be an array");
			return 1;
		}

		my $fileContent = "# File auto-generated by i-MSCP InstantSSH plugin\n";

		$fileContent .= "chroot = \"$jailCfg{'jail_dir'}\"\n";
		$fileContent .= "cleanJailFirst = 1\n";
		$fileContent .= "maxRemove = 5000\n";

		if(@{$jailCfg{'preserve_files'}}) {
			$fileContent .= 'preserve = [' . (join ', ', map { qq/"$_"/ } @{$jailCfg{'preserve_files'}}) . "]\n"
		}

		if(@{$jailCfg{'paths'}}) {
			$fileContent .= 'forceCopy = [' . (join ', ', map { qq/"$_"/ } @{$jailCfg{'paths'}}) . "]\n";
		}

		if(@{$jailCfg{'packages'}}) {
			$fileContent .= 'packages = [' . (join ', ', map { qq/"$_"/ } @{$jailCfg{'packages'}}) . "]\n";
			$fileContent .= "useDepends = $jailCfg{'include_pkg_deps'}\n";
		}

		if(@{$jailCfg{'users'}}) {
			$fileContent .= 'users = [' . (join ', ', map { qq/"$_"/ } @{$jailCfg{'users'}}) . "]\n";
		}

		if(@{$jailCfg{'groups'}}) {
			$fileContent .= 'groups = [' . (join ', ', map { qq/"$_"/ } @{$jailCfg{'groups'}}) . "]\n";
		}

		$fileContent .= "sleepAfterTest = 0.2\n";
		$fileContent .= "sleepAfterStartCommand = 0.2\n";
		$fileContent .= "sleepAfterKillall = 1.0\n"; # Not really needed ATM
		$fileContent .= "sleepAfterStraceAttachPid = 1.0\n"; # Not really needed ATM

		my $file = iMSCP::File->new(
			'filename' => "$makejailCfgDir/InstantSSH" . (($cfg->{'shared_jail'}) ? '.py' : ".$user.py")
		);

		my $rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;
	} else {
		error("InstantSSH::JailBuilder: The 'app_sections' option is not defined");
		return 1;
	}

	0;
}

=item _handleAppsSection(\%config, $section)

 Handle applications sections

 Param hash \%config Hash containing Jail configuration options
 Param string $section Applications section definition
 Return int 0 on success, 1 on failure

=cut

sub _handleAppsSection()
{
	my ($self, $cfg, $section) = @_;

	# Handle included application sections

	if(exists $cfg->{$section}->{'include_app_sections'}) {
		if(ref $cfg->{$section}->{'include_app_sections'} eq 'ARRAY') {
			for my $includedAppsSection(@{$cfg->{$section}->{'include_app_sections'}}) {
				if(not grep $_ eq $includedAppsSection, @{$self->{'_app_sections'}}) {
					$self->_handleAppsSection($cfg, $includedAppsSection);
					push @{$self->{'_app_sections'}}, $includedAppsSection;
				}
			}
		} else {
			error("InstantSSH::JailBuilder: The 'include_app_sections' option must be an array");
			return 1;
		}
	}

	# Handle list options from application section

	for my $option(qw/paths packages devices mounts preserve_files users groups/) {
		if(exists $cfg->{$section}->{$option}) {
			if(ref $cfg->{$section}->{$option} eq 'ARRAY') {
				for my $item (@{$cfg->{$section}->{$option}}) {
					push @{$jailCfg{$option}}, $item;
				}

				@{$jailCfg{$option}} = uniq(@{$jailCfg{$option}});
			} else {
				error(sprintf("InstantSSH::JailBuilder: The '%s' option must be an array", $option));
				return 1;
			}
		}
	}

	# Handle boolean options from application section

	if (exists $cfg->{$section}->{'include_pkg_deps'}) {
		$jailCfg{'include_pkg_deps'} = 1;
	}

	if (exists $cfg->{$section}->{'need_logsocket'}) {
		$jailCfg{'need_logsocket'} = 1;
	}

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
