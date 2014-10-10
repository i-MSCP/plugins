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
use InstantSSH::JailBuilder::Utils qw(copyDevice normalizePath);

use List::MoreUtils qw(uniq);

use parent 'Common::Object';

my %jailCfg = (
	'chroot' => '',
	'paths' => [],
	'packages' => [],
	'include_pkg_deps' => 0,
	'preserve_files' => [],
	'users' => [],
	'groups' => [],
	'devices' => []
);

my $makejailCfgDir = '/etc/makejail';
my $securityChrootCfgFile = '/etc/security/chroot.conf';
my $fstabFile = '/etc/fstab';

=head1 DESCRIPTION

 This package is part of the i-MSCP InstantSSH plugin. It provide jail builder which allow to build jailed environments.

=head1 PUBLIC METHODS

=over 4

=item makeJail()

 Create or update jail

 Return int 0 on success, other on failure

=cut

sub makeJail
{
	my $self = $_[0];

	my ($cfg, $user) = ($self->{'config'}, $self->{'user'});

	#  Create the jail directory if it doesn't already exists or set it permissions
	my $rs = iMSCP::Dir->new(
		dirname => $jailCfg{'chroot'}
	)->make(
		{ user => $main::imscpConfig{'ROOT_USER'}, group => $main::imscpConfig{'ROOT_GROUP'} => mode => 0755 }
	);
	return $rs if $rs;

	# Build makejail configuration file
	$rs = $self->_buildMakejailCfgfile($cfg, $user);
	return $rs if $rs;

	my $cfgFilePath = "$makejailCfgDir/InstantSSH" . (($cfg->{'shared_jail'}) ? '.py' : ".$user.py");

	# Create/update jail
	my ($stdout, $stderr);
	$rs = execute(
		"python $main::imscpConfig{'GUI_ROOT_DIR'}/plugins/InstantSSH/bin/makejail $cfgFilePath", $stdout, \$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $rs && $stderr;
	error('Unable to create/update jail for unknown reason') if $rs && !$stderr;
	return $rs if $rs;

	# Copy devices inside jail
	for my $devicePattern (@{$jailCfg{'devices'}}) {
		for my $devicePath(glob $devicePattern) {
			eval { copyDevice($jailCfg{'chroot'}, $devicePath); };

			if($@) {
				error("Unable to create device within jail: $@");
				return 1;
			}
		}
	}

	# Add /proc entry in fstab if needed
	if(-d "$jailCfg{'chroot'}/proc") {
		$rs = $self->addFstabEntry("/proc $jailCfg{'chroot'}/proc auto bind 0 0");
		return $rs if $rs;
	}

	# Add user into jail
	$rs = $self->addUserToJail($user);
	return $rs if $rs;

	0;
}

=item removeJail()

 Remove jail

 Return int 0 on success, other on failure

=cut

sub removeJail
{
	my $self = $_[0];

	my ($cfg, $user) = ($self->{'config'}, $self->{'user'});

	# Remove user from jail
	my $rs = $self->removeUserFromJail();
	return $rs if $rs;

	# Umount any directory which is mounted inside jail
	$rs = $self->umount($jailCfg{'chroot'});
	return $rs if $rs;

	# Remove any fstab entry
	$rs = $self->removeFstabEntry(qr%.*?$jailCfg{'chroot'}.*%);
	return $rs if $rs;

	# Ensure that the user web directory is empty
	my $jailUserWebDir = "$jailCfg{'chroot'}/$main::imscpConfig{'USER_WEB_DIR'}";
	if(-d $jailUserWebDir && ! iMSCP::Dir->new( dirname => $jailUserWebDir )->isEmpty()) {
		error("Unable to remove jail. Directory $jailUserWebDir is not empty");
		return 1;
	}

	# Ensure that the proc directory is emtpy if any
	my $jailProcDir = "$jailCfg{'chroot'}/proc";
	if(-d $jailProcDir && ! iMSCP::Dir->new( dirname => $jailProcDir )->isEmpty()) {
		error("Unable to remove jail. Directory $jailProcDir is not empty");
		return 1;
	}

	# Remove jail configuration file if any
	my $cfgFilePath = "$makejailCfgDir/InstantSSH" . (($cfg->{'shared_jail'}) ? '.py' : ".$user.py");
	if(-f $cfgFilePath) {
		$rs = iMSCP::File->new( filename => $cfgFilePath )->delFile();
		return $rs if $rs;
	}

	# Remove the jail
	iMSCP::Dir->new( dirname => $jailCfg{'chroot'} )->remove();
}

=item existsJail()

 Does the jail already exists

 Return bool True if the jail already exists, FALSE otherwise

=cut

sub existsJail
{
	(-d $jailCfg{'chroot'});
}

=item addUserToJail($user)

 Adds unix user into the jail

 Return int 0 on success, other on failure

=cut

sub addUserToJail
{
	my $self = $_[0];

	my ($user, $group, $homeDir) = ($self->{'user'}, $self->{'group'}, $self->{'homedir'});

	my $jailedHomedir = $jailCfg{'chroot'} . $homeDir;

	# Create jailed homedir
	my $rs = iMSCP::Dir->new(
		dirname => $jailedHomedir
	)->make(
		{ user => $main::imscpConfig{'ROOT_USER'}, group => $main::imscpConfig{'ROOT_GROUP'}, mode => 0755 }
	);
	return $rs if $rs;

	# Set owner/group for jailed homedir
	$rs = setRights($jailedHomedir, { user => $user, group => $group, mode => '0750' });
	return $rs if $rs;

	# TODO copy content from /etc/skel if any (or better self files with colors enabled)

	# Add user into the jailed passwd file if any
	$rs = $self->addPasswdFile('/etc/passwd', $user);
	return $rs if $rs;

	# Add user group into the jailed group file if any
	$rs = $self->addPasswdFile('/etc/group', $group);
	return $rs if $rs;

	# Add fstab entry for user homedir
	$rs = $self->addFstabEntry("$homeDir $jailedHomedir none bind 0 0");
	return $rs if $rs;

	# Mount user homedir within the jail
	$rs = $self->mount($homeDir, $jailedHomedir);
	return $rs if $rs;

	# Add user into security chroot file
	if(-f $securityChrootCfgFile) {
		my $file = iMSCP::File->new( filename => $securityChrootCfgFile );

		my $fileContent = $file->get();
		unless(defined $fileContent) {
			error('Unable to read file $securityChrootCfgFile');
			return 1;
		}

		debug("Adding $user entry in $securityChrootCfgFile");

		my ($userReg, $jailReg) = (quotemeta($user), quotemeta($jailCfg{'chroot'}));
		$fileContent =~ s/^$userReg\s+$jailReg\n//gm;
		$fileContent .= "$user\t$jailCfg{'chroot'}\n";

		$rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;
	} else {
		error("File $securityChrootCfgFile not found");
		return 1;
	}

	0;
}

=item removeUserFromJail()

 Remove unix user from the jail

 Return int 0 on success, other on failure

=cut

sub removeUserFromJail
{
	my $self = $_[0];

	my ($user, $group, $homeDir) = ($self->{'user'}, $self->{'group'}, $self->{'homedir'});

	my $jailedHomedir = $jailCfg{'chroot'} . $homeDir;

	if(-d $jailCfg{'chroot'}) {
		# Umount user homedir from the jail
		my $rs = $self->umount($jailedHomedir);
		return $rs if $rs;

		if(-d $jailedHomedir) {
			my $dir = iMSCP::Dir->new( dirname => $jailedHomedir);
			# Remove the directory
        	# The check of the directory should avoid any drawback in case the user homedir is still mounted
			if($dir->isEmpty()) {
				$rs = $dir->remove();
				return $rs if $rs;
			} else {
				error("Unable to remove $user user homedir within the jail. Directory not empty");
				return 1;
			}
		}

		# Remove user from the jailed passwd file if any
		$rs = $self->removePasswdFile('/etc/passwd', $user);
		return $rs if $rs;

		# Remove user group from the jailed group file if any
		$rs = $self->removePasswdFile('/etc/group', $group);
		return $rs if $rs;
	}

	# Remove fstab entry for user homedir
	my $rs = $self->removeFstabEntry(qr%.*?$jailedHomedir.*%);
	return $rs if $rs;

	# Remove user from security chroot file
	if(-f $securityChrootCfgFile) {
		my $file = iMSCP::File->new( filename => $securityChrootCfgFile );

		my $fileContent = $file->get();
		unless(defined $fileContent) {
			error("Unable to read file $securityChrootCfgFile");
			return 1;
		}

		debug("Removing $user entry from $securityChrootCfgFile");

		my ($userReg, $jailReg) = (quotemeta($user), quotemeta($jailCfg{'chroot'}));
		$fileContent =~ s/^$userReg\s+$jailReg\n//gm;

		my $rs = $file->set($fileContent);
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

	my $dest = $jailCfg{'chroot'} . $file;

	if(-f $dest) {
		if(open my $fh, '<', $file) {
			my @lines = <$fh>;
			close $fh;

			if(open $fh, '+<', $dest) {
				s/^(.*?):.*/$1/s for (my @outLines = <$fh>);

				if(not grep $_ eq $what, @outLines) {
					for my $line(@lines) {
						next if index($line, ':') == -1;

						my @lineFields = split ':', $line;

						if ($lineFields[0] eq $what) {
							debug("Adding $what user/group into $dest");

							$lineFields[5] = normalizePath($lineFields[5]) if defined $lineFields[5];
							print $fh join ':', @lineFields;
							last;
						}
					}
				}

				close $fh;
			} else {
				error("Unable to open file for writing: $!");
				return 1;
			}
		} else {
			error("Unable to open file for reading: $!");
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

	my $dest = $jailCfg{'chroot'} . $file;

	if(-f $dest) {
		if(open my $fh, '<', $dest) {
			my @lines = <$fh>;
			close $fh;

			if(open $fh, '>', $dest) {
				debug("Removing $what user/group from $dest");

				$what = quotemeta($what);
				@lines = grep $_ !~ /^$what:.*/s, @lines;
				print $fh join '', @lines;
				close $fh;
			} else {
				error("Unable to open file for writing: $!");
				return 1;
			}
		} else {
			error("Unable to open file for reading: $!");
			return 1;
		}
	}

	0;
}

=item addFstabEntry($entry)

 Add fstab entry

 Param string $entry Fstab entry to remove
 Return int 0 on success, other on failure

=cut

sub addFstabEntry
{
	my ($self, $entry) = @_;

	my $file = iMSCP::File->new( filename => $fstabFile );
	my $fileContent = $file->get();
	unless(defined $fileContent) {
		error("Unable to read file $fstabFile");
		return 0;
	}

	debug("Adding $entry entry in $fstabFile");

	my $entryReg = quotemeta($entry);
	$fileContent =~ s/^$entryReg\n//gm;
	$fileContent .= "$entry\n";

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item removeFstabEntry($entry)

 Remove fstab entry

 Param regexp|string OPTIONAL $entry Fstab entry to remove as a string or regexp
 Return int 0 on success, other on failure

=cut

sub removeFstabEntry
{
	my ($self, $entry) = @_;

	my $file = iMSCP::File->new( filename => $fstabFile );
	my $fileContent = $file->get();
	unless(defined $fileContent) {
		error("Unable to read file $fstabFile");
		return 0;
	}

	debug("Removing $entry matching entries from $fstabFile");

	my $regexp = (ref $entry eq 'Regexp') ? $entry : quotemeta($entry);
	$fileContent =~ s/^$regexp\n//gm;

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item mount($oldir, $newdir)

 Mount the given directory in safe way

 Param string $oldir Directory to mount
 Param string $newdir Mount point
 Return int 0 on success, other on failure

=cut

sub mount
{
 	my ($self, $oldir, $newdir) = @_;

	if(-d $oldir) {
		if(execute("mount | grep -q ' $newdir'")) {
			my($stdout, $stderr);
			my $rs = execute("mount --bind $oldir $newdir", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		}
	}

	0;
}

=item umount($directory)

 Umount the given directory in safe way

 Note: In case of a partial path, any directory which start by this path will be umounted.

 Param string $directory Partial or full path of directory to umount.
 Return int 0 on success, other on failure

=cut

sub umount
{
	my ($self, $directory) = @_;

	my($stdout, $stderr, $mountPoint);

	do {
		my $rs = execute("mount 2>/dev/null | grep ' $directory' | head -n 1 | cut -d ' ' -f 3", \$stdout);
		return $rs if $rs;
		$mountPoint = $stdout;

		if($mountPoint) {
			$rs = execute("umount -l $mountPoint", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		}
	} while($mountPoint);

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return InstantSSH:JailBuilder (die on failure)

=cut

sub _init
{
	my $self = $_[0];

	$self->{'user'} = '__unknown__' unless exists $self->{'user'};

	if($self->{'user'} ne '__unknown__') {
		my @pwEntry = getpwnam($self->{'user'});

		unless(@pwEntry) {
			die("InstantSSH::JailBuilder: Unable to find $self->{'user'} unix user");
			return 1;
		}

		$self->{'user'} = $pwEntry[0];
		$self->{'homedir'} = normalizePath($pwEntry[7]);

		$self->{'group'} = getgrgid($pwEntry[3]);

		unless(defined $self->{'group'}) {
			die("InstantSSH::JailBuilder: Unable to find $self->{'user'} unix user group");
			return 1;
		}

		$self->{'config'} = {} unless exists $self->{'config'} && ref $self->{'config'} eq 'HASH';

		if(%{$self->{'config'}}) {
			if(exists $self->{'config'}->{'root_jail_dir'}) {
				$jailCfg{'chroot'} = $self->{'config'}->{'root_jail_dir'} .
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
			die("InstantSSH::JailBuilder: The $self->{'user'} unix user doesn't exists");
		}
	}

	$self;
}

=item _buildMakejailCfgfile($cfg, $user)

 Build makejail configuration file

 Param hash \%cfg Hash containing Jail configuration options
 Param string $user
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
			error("The 'preserve_files' option must be an array");
			return 1;
		}
	}

	if(exists $cfg->{'include_pkg_deps'}) {
		$jailCfg{'include_pkg_deps'} = ($cfg->{'include_pkg_deps'}) ? 1 : 0;
	}

	if(exists $cfg->{'app_sections'}) {
		# Process sections as specified in app_sections configuration options.
		if(ref $cfg->{'app_sections'} eq 'ARRAY') {
			for my $section(@{$cfg->{'app_sections'}}) {
				if(exists $cfg->{$section}) {
					$self->_handleAppsSection($cfg, $section);
				} else {
					error("The $section application section doesn't exists");
					return 1;
				}
			}
		} else {
			error("The 'app_sections' option must be an array");
			return 1;
		}

		my $fileContent = "# File auto-generated by i-MSCP InstantSSH plugin\n";

		$fileContent .= "chroot = \"$jailCfg{'chroot'}\"\n";
		$fileContent .= "cleanJailFirst = 1\n";
		$fileContent .= "maxRemove = 10000\n";
		$fileContent .= "doNotCopy = []\n";

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
			filename => "$makejailCfgDir/InstantSSH" . (($cfg->{'shared_jail'}) ? '.py' : ".$user.py")
		);

		my $rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;
	} else {
		error("The 'app_sections' option is not defined");
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
			error("The 'include_app_sections' option must be an array");
			return 1;
		}
	}

	# Handle list options from application section

	for my $option(qw/paths packages devices preserve_files users groups/) {
		if(exists $cfg->{$section}->{$option}) {
			if(ref $cfg->{$section}->{$option} eq 'ARRAY') {
				for my $item (@{$cfg->{$section}->{$option}}) {
					push @{$jailCfg{$option}}, $item;
				}

				@{$jailCfg{$option}} = uniq(@{$jailCfg{$option}});
			} else {
				error("The $option option must be an array");
				return 1;
			}
		}
	}

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
