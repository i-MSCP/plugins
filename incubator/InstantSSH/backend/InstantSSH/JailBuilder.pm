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

use JSON;
use List::MoreUtils qw(uniq);

use parent 'Common::Object';

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

my %jailCfg = (
	'jail_dir' => '',
	'paths' => [],
	'commands' => [],
	'packages' => [],
	'include_pkg_deps' => 0,
	'preserve_files' => ['/home'],
	'users' => [],
	'groups' => [],
	'devices' => [],
	'mounts' => [],
	'need_logsocket' => 0
);

my $makejailCfgDir = '/etc/makejail';
my $buildMakejailCfgfile = 1;

=head1 DESCRIPTION

 This package is part of the InstantSSH i-MSCP plugin. It provide jail builder which allow to build jailed shell
environments.

=head1 PUBLIC METHODS

=over 4

=item makeJail(\%config, $user)

 Create or update jail for the given user using the give jail configuration options

 Param hash \%config Hash containing Jail configuration options
 Param string $user i-MSCP user to add into the jail
 Return int 0 on success, other on failure

=cut

sub makeJail
{
	my ($self, $cfg, $user) = @_;

	$cfg = {} unless $cfg && ref $cfg eq 'HASH';
	$user ||= '__unknown__';

	if(getpwnam($user)) {
		# Override default root jail directory if needed
		if(exists $cfg->{'root_jail_dir'}) {
			$jailCfg{'jail_dir'} = $cfg->{'root_jail_dir'} . (($cfg->{'shared_jail'}) ? '/shared_jail' : "/$user");
		} else {
			error("InstantSSH::JailBuilder: The 'root_jail_dir' is missing in jail configuration options");
			return 1;
		}

		#  Create the jail directory if it doesn't already exists or set it permissions
		my $rs = iMSCP::Dir->new(
			'dirname' => $jailCfg{'jail_dir'}
		)->make(
			{ 'user' => $main::imscpConfig{'ROOT_USER'}, 'group' => $main::imscpConfig{'ROOT_GROUP'} => 'mode' => 0750 }
		);
		return $rs if $rs;

		# Create home directory for the given user if it doesn't already exists
		unless (-d $jailCfg{'jail_dir'} . "/home/$user") {
			$rs = iMSCP::Dir->new(
				'dirname' => $jailCfg{'jail_dir'} . "/home/$user"
			)->make(
				{
					'user' => $main::imscpConfig{'ROOT_USER'},
					'group' => $main::imscpConfig{'ROOT_GROUP'},
					'mode' => 0750
				}
			);
			return $rs if $rs;
		}

		# Set owner/group for user home directory
		setRights(
			$jailCfg{'jail_dir'} . "/home/$user", { 'user' => $user, 'group' => (getgrgid((getpwnam($user))[3]))[0] }
		);

		my $makejailCfgfilePath = "$makejailCfgDir/InstantSSH" . (($cfg->{'shared_jail'}) ? '.py' : ".$user.py");

		# Build makejail configuration file if needed
		if($buildMakejailCfgfile || ! -f $makejailCfgfilePath) {
			$rs = $self->_buildMakejailCfgfile($cfg, $user);
			return $rs if $rs;
		} else {
			$rs = $self->_addPasswdFile('/etc/passwd', $user);
			return $rs if $rs;

			$rs = $self->_addPasswdFile('/etc/group', (getgrgid((getpwnam($user))[3]))[0]);
			return $rs if $rs;
		}

		# Build the jail
		#my ($stdout, $stderr);
		#$rs = execute("makejail $makejailCfgfilePath", \$stdout, \$stderr);
		#debug($stdout) if $stdout;
		#error("InstantSSH::JailBuilder:: $stderr") if $rs && $stderr;
		#error('InstantSSH::JailBuilder: Unable to build jail for unknown reason') if $rs && !$stderr;
		#return $rs if $rs;

		# TODO:
		# Create device if needed
		# Mount folder if needed
		# Create log socket if needed
	} else {
		error(sprintf("InstantSSH::JailBuilder: The %s i-MSCP unix user doesn't exists", $user));
		return 1;
	}

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _buildMakejailCfgfile(\%cfg, $user)

 Build makejail configuration file

 Param hash \%cfg Hash containing Jail configuration options
 Return int 0 on success, other on failure

=cut

sub _buildMakejailCfgfile
{
	my ($self, $cfg, $user) = @_;

	$cfg = {} unless $cfg && ref $cfg eq 'HASH';

	if(exists $cfg->{'apps_sections'}) {
		# Process sections as specified in apps_sections configuration options.
		if(ref $cfg->{'apps_sections'} eq 'ARRAY') {
			for my $section(@{$cfg->{'apps_sections'}}) {
				if(exists $cfg->{$section}) {
					$self->_handleAppsSection($cfg, $section);
				} else {
					error(sprinf("InstantSSH::JailBuilder: The %s applications section doesn't exists", $cfg->{$section}));
					return 1;
				}
			}

			push @{$jailCfg{'users'}}, $user;
			push @{$jailCfg{'groups'}}, (getgrgid((getpwnam($user))[3]))[0];
		} else {
			error("InstantSSH::JailBuilder: The 'apps_sections' option must be an array");
			return 1;
		}
	} else {
		error("InstantSSH::JailBuilder: The 'apps_sections' is missing in jail configuration options");
		return 1;
	}

	my $fileContent = "# File auto-generated by i-MSCP InstantSSH plugin\n";

	$fileContent .= "chroot = \"$jailCfg{'jail_dir'}\"\n";
	$fileContent .= "cleanJailFirst = 1\n";

	if(@{$jailCfg{'preserve_files'}}) {
		$fileContent .= "preserve = [" . (join ', ', map { qq/"$_"/ } @{$jailCfg{'preserve_files'}}) . "]\n"
	}

	if(@{$jailCfg{'paths'}}) {
		$fileContent .= "forceCopy = [" . (join ', ', map { qq/"$_"/ } @{$jailCfg{'paths'}}) . "]\n";
	}

	if(@{$jailCfg{'commands'}}) {
		$fileContent .= "testCommandsInsideJail = [" . (join ', ', map { qq/"$_"/ } @{$jailCfg{'commands'}}) . "]\n";
	}

	if(@{$jailCfg{'packages'}}) {
		$fileContent .= "packages = [" . (join ', ', map { qq/"$_"/ } @{$jailCfg{'packages'}}) . "]\n";
		$fileContent .= "useDepends = $jailCfg{'include_pkg_deps'}\n";
	}

	if(@{$jailCfg{'users'}}) {
		$fileContent .= "users = [" . (join ', ', map { qq/"$_"/ } @{$jailCfg{'users'}}) . "]\n";
	}

	if(@{$jailCfg{'groups'}}) {
		$fileContent .= "groups = [" . (join ', ', map { qq/"$_"/ } @{$jailCfg{'groups'}}) . "]\n";
	}

	$fileContent .= "sleepAfterTest = 0.2\n";
	$fileContent .= "sleepAfterStartCommand = 0.2\n";
	$fileContent .= "sleepAfterKillall = 1.0\n"; # Not really needed ATM
	$fileContent .= "sleepAfterStraceAttachPid = 1.0\n"; # Not really needed ATM

	my $file = iMSCP::File->new(
		'filename' => "$makejailCfgDir/InstanSSH" . (($cfg->{'shared_jail'}) ? '.py' : ".$user.py")
	);
	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	0;
}

=item handleAppsSection(\%config, $section)

 Handle applications sections

 Param hash \%config Hash containing Jail configuration options
 Param string $section Applications section definition
 Return int 0 on success, 1 on failure

=cut

sub _handleAppsSection()
{
	my ($self, $cfg, $section) = @_;

	# Handle included application sections

	if(exists $cfg->{$section}->{'include_apps_sections'}) {
		if(ref $cfg->{$section}->{'include_apps_sections'} eq 'ARRAY') {
			for my $includedAppsSection(@{$cfg->{$section}->{'include_apps_sections'}}) {
				if(not grep $_ eq $includedAppsSection, @{$self->{'_apps_sections'}}) {
					$self->_handleAppsSection($cfg, $includedAppsSection);
					push @{$self->{'_apps_sections'}}, $includedAppsSection;
				}
			}
		} else {
			error("InstantSSH::JailBuilder: The 'include_apps_sections' applications section option must be an array");
			return 1;
		}
	}

	# Handle list options from application section

	for my $option(qw/paths commands packages devices mounts preserve_files users groups/) {
		if(exists $cfg->{$section}->{$option}) {
			if(ref $cfg->{$section}->{$option} eq 'ARRAY') {
				for my $item (@{$cfg->{$section}->{$option}}) {
					push @{$jailCfg{$option}}, $item;
				}

				@{$jailCfg{$option}} = uniq(@{$jailCfg{$option}});
			} else {
				error(sprintf("InstantSSH::JailBuilder: The '%s' applications section option must be an array", $option));
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

=item _addPasswdFile($file, $what)

 Add the given user/group into the passwd/group file of the Jail if any

 Param string $file Path of system passwd/group file
 Param string $what User/group name
 Return int 0 on success, 1 on failure

=cut

sub _addPasswdFile
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

						if ((split ':', $line, 2)[0] eq $what) {
							print $fh $line;
							last;
						}
					}
				}

				close $fh;
			} else {
				error("InstantSSH::JailBuilder: Unable to open file for writing: $!");
				return 1;
			}
		} else {
			error("InstantSSH::JailBuilder: Unable to open file for reading: $!");
			return 1;
		}
	}

	0;
}

=item _removePasswdFile($file, $what)

 Remove the given user/group from the passwd/group file of the Jail if any

 Param string $file Path of system passwd/group file
 Param string $what User/group name
 Return int 0 on success, 1 on failure

=cut

sub _removePasswdFile
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
				error("InstantSSH::JailBuilder: Unable to open file for writing: $!");
				return 1;
			}

		} else {
			error("InstantSSH::JailBuilder: Unable to open file for reading: $!");
			return 1;
		}
	}

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
