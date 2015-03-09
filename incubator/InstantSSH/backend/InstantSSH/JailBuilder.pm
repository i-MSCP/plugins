=head1 NAME

 InstantSSH::JailBuilder

=cut

# i-MSCP InstantSSH plugin
# Copyright (C) 2014-2015 Laurent Declercq <l.declercq@nuxwin.com>
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
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::Rights;

use InstantSSH::JailBuilder::Utils qw(copyDevice normalizePath);

use File::Basename;
use File::umask;
use List::MoreUtils qw(uniq);

use parent 'Common::Object';

our $VERSION = '3.2.0';

my $securityChrootCfgFile = '/etc/security/chroot.conf';
my $fstabFile = '/etc/fstab';

=head1 DESCRIPTION

 This package is part of the i-MSCP InstantSSH plugin. It provide the jail builder layer which allows to build jailed
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

	my ($cfg, $jailId) = ($self->{'config'}, $self->{'id'});

	#  Create the jail directory if it doesn't already exists or set it permissions
	my $rs = iMSCP::Dir->new( dirname => $self->{'jailCfg'}->{'chroot'} )->make(
		{ user => $main::imscpConfig{'ROOT_USER'}, group => $main::imscpConfig{'ROOT_GROUP'}, mode => 0755 }
	);
	return $rs if $rs;

	# Any directory/file which is mounted within the jail must be umounted prior any update
	$rs = $self->umount($self->{'jailCfg'}->{'chroot'});
	return $rs if $rs;

	# Remove any fstab entry
	$rs = $self->removeFstabEntry(qr%.*?\s$self->{'jailCfg'}->{'chroot'}.*%);
	return $rs if $rs;

	# Build makejail configuration file
	$rs = $self->_buildMakejailCfgfile();
	return $rs if $rs;

	# Create/update jail
	my $stderr;
	$rs = execute(
		"python $cfg->{'makejail_path'} $cfg->{'makejail_confdir_path'}/$jailId.py 1> /dev/null", undef, \$stderr
	);
	error($stderr) if $rs && $stderr;
	error('Unable to create/update jail for unknown reason') if $rs && !$stderr;
	return $rs if $rs;

	{
		local $UMASK = 0022;

		# Create directories defined in the create_dirs option within the jails
		while(my ($dir, $perms) = each(%{$self->{'jailCfg'}->{'create_dirs'}})) {
			if(index($dir, '/') == 0) {
				if(ref $perms eq 'HASH') {
					$rs = iMSCP::Dir->new( dirname => $self->{'jailCfg'}->{'chroot'} . $dir )->make(
						{
							user => (exists $perms->{'user'}) ? $perms->{'user'} : $main::imscpConfig{'ROOT_USER'},
							group => (exists $perms->{'group'}) ? $perms->{'group'} : $main::imscpConfig{'ROOT_GROUP'},
							mode => (exists $perms->{'mode'}) ? $perms->{'mode'} : 0755
						}
					);
					return $rs if $rs;
				} else {
					error("Values for the directory paths defined in the create_dirs option must be an associative array");
					return 1;
				}
			} else {
				error("Directory paths defined in the create_dirs option must be absolute");
				return 1;
			}
		}

		# Copy files defined in the sys_copy_file_to option within the system
		while(my ($src, $dst) = each(%{$self->{'jailCfg'}->{'sys_copy_file_to'}})) {
			if(index($src, '/') == 0 && index($dst, '/') == 0) {
				$rs = iMSCP::File->new( filename => $src )->copyFile($dst, { preserve => 'no' });
				return $rs if $rs;
			} else {
				error("Any file path defined in the sys_copy_file_to option must be absolute");
				return 1;
			}
		}

		# Copy files defined in the jail_copy_file_to option within the jail
		while(my ($src, $dst) = each(%{$self->{'jailCfg'}->{'jail_copy_file_to'}})) {
			if(index($src, '/') == 0 && index($dst, '/') == 0) {
				$rs = iMSCP::File->new( filename => $src )->copyFile(
					$self->{'jailCfg'}->{'chroot'} . $dst, { preserve => 'no' }
				);
				return $rs if $rs;
			} else {
				error("Any file path defined in the jail_copy_file_to option must be absolute");
				return 1;
			}
		}
	}

	# Copy devices defined in the devices option within the jail
	for my $devicePattern (@{$self->{'jailCfg'}->{'devices'}}) {
		for my $devicePath(glob $devicePattern) {
			eval { copyDevice($self->{'jailCfg'}->{'chroot'}, $devicePath); };

			if($@) {
				error("Unable to create device within jail: $@");
				return 1;
			}
		}
	}

	# Mount filesystems defined in the fstab option within the jail and add the related fstab entries
	for (@{$self->{'jailCfg'}->{'fstab'}}) {
		if(
			exists $_->{'file_system'} && exists $_->{'mount_point'} && exists $_->{'type'} &&
			exists $_->{'options'} && exists $_->{'dump'} && exists $_->{'pass'}
		) {
			if(index($_->{'file_system'}, '/') == 0 && index($_->{'mount_point'}, '/') == 0) {
				$rs = $self->mount(
					{
						'file_system' => $_->{'file_system'},
						'mount_point' => $self->{'jailCfg'}->{'chroot'} . $_->{'mount_point'},
						'type' => $_->{'type'},
						'options' => $_->{'options'}
					}
				);
				return $rs if $rs;

				$rs = $self->addFstabEntry(
					$_->{'file_system'} . ' ' .
					$self->{'jailCfg'}->{'chroot'} . $_->{'mount_point'}  . ' ' .
					$_->{'type'} . ' ' .
					$_->{'options'} . ' ' .
					$_->{'dump'} . ' ' .
					$_->{'pass'}
				);
				return $rs if $rs;
			} else {
				error("Any filesystem or mount point defined in the fstab option must be an absolute path");
				return 1;
			}
		} else {
			error("Missing value in fstab option");
			return 1;
		}
	}

	my $stdout;

	# Run commands defined in the create_sys_commands option outside the jail
	for (@{$self->{'jailCfg'}->{'create_sys_commands'}}) {
		$rs = execute($_, \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
		return $rs if $rs;
	}

	# Run commands defined in the create_sys_commands_args option outside the jail
	for (@{$self->{'jailCfg'}->{'create_sys_commands_args'}}) {
		my @cmd = ($_, escapeShell($self->{'jailCfg'}->{'chroot'}));
		$rs = execute("@cmd", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
		return $rs if $rs;
	}

	# Run commands defined in the create_jail_commands option inside the jail
	for (@{$self->{'jailCfg'}->{'create_jail_commands'}}) {
		(my $cmd = $_) =~ s/\|/| chroot $self->{'jailCfg'}->{'chroot'}/g;
		$rs = execute(sprintf('chroot %s %s', $self->{'jailCfg'}->{'chroot'}, $cmd), \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
		return $rs if $rs;
	}

	# Run commands defined in the create_jail_commands_args option inside the jail
	for (@{$self->{'jailCfg'}->{'create_jail_commands_args'}}) {
		(my $cmd = $_) =~ s/\|/| chroot $self->{'jailCfg'}->{'chroot'}/g;
		my @cmd = ($cmd, escapeShell($self->{'jailCfg'}->{'chroot'}));
		$rs = execute(sprintf('chroot %s %s', $self->{'jailCfg'}->{'chroot'}, "@cmd"), \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
		return $rs if $rs;
	}

	0;
}

=item removeJail()

 Remove jail

 Return int 0 on success, other on failure

=cut

sub removeJail
{
	my $self = $_[0];

	my ($cfg, $jailId) = ($self->{'config'}, $self->{'id'});

	# Umount any directory which is mounted within jail
	my $rs = $self->umount($self->{'jailCfg'}->{'chroot'});
	return $rs if $rs;

	# Remove any fstab entry
	$rs = $self->removeFstabEntry(qr%.*?\s$self->{'jailCfg'}->{'chroot'}.*%);
	return $rs if $rs;

	# Remove jail configuration file if any
	if(-f "$cfg->{'makejail_confdir_path'}/$jailId.py") {
		$rs = iMSCP::File->new( filename => "$cfg->{'makejail_confdir_path'}/$jailId.py" )->delFile();
		return $rs if $rs;
	}

	# Run commands defined in the destroy_sys_commands option outside the jail
	for (@{$self->{'jailCfg'}->{'destroy_sys_commands'}}) {
		my ($stdout, $stderr);
		$rs = execute($_, \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
		return $rs if $rs;
	}

	# Run commands defined in the destroy_sys_commands_args option outside the jail
	for (@{$self->{'jailCfg'}->{'destroy_sys_commands_args'}}) {
		my @cmd = ($_, escapeShell($self->{'jailCfg'}->{'chroot'}));
		my ($stdout, $stderr);
		$rs = execute("@cmd", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
		return $rs if $rs;
	}

	# Run commands defined in the destroy_jail_commands option inside the jail
	for (@{$self->{'jailCfg'}->{'destroy_jail_commands'}}) {
		(my $cmd = $_) =~ s/\|/| chroot $self->{'jailCfg'}->{'chroot'}/g;
		my ($stdout, $stderr);
		$rs = execute(sprintf('chroot %s %s', $self->{'jailCfg'}->{'chroot'}, $cmd), \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
		return $rs if $rs;
	}

	# Run commands defined in the destroy_jail_commands_args option inside the jail
	for (@{$self->{'jailCfg'}->{'destroy_jail_commands_args'}}) {
		(my $cmd = $_) =~ s/\|/| chroot $self->{'jailCfg'}->{'chroot'}/g;
		my @cmd = ($cmd, escapeShell($self->{'jailCfg'}->{'chroot'}));
		my ($stdout, $stderr);
		$rs = execute(sprintf('chroot %s %s', $self->{'jailCfg'}->{'chroot'}, "@cmd"), \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
		return $rs if $rs;
	}

	# Remove jail
	iMSCP::Dir->new( dirname => $self->{'jailCfg'}->{'chroot'} )->remove();
}

=item existsJail()

 Does the jail already exists?

 Return bool TRUE if the jail already exists, FALSE otherwise

=cut

sub existsJail
{
	(-d $_[0]->{'jailCfg'}->{'chroot'});
}

=item jailUser($user [, $shell = system user shell ])

 Jail the given user

 Param string $user User to add into the jail
 Param string OPTIONAL $shell User shell inside jail
 Return int 0 on success, other on failure

=cut

sub jailUser
{
	my ($self, $user, $shell) = @_;

	my @pwEntry = getpwnam($user);
	unless(@pwEntry) {
		error("Unable to find $user unix user");
		return 1;
	}

	my $group = getgrgid($pwEntry[3]);
	unless(defined $group) {
		error("Unable to find $user unix user group");
		return 1;
	}

	my $homeDir = $pwEntry[7];
	unless(defined $homeDir) {
		error("Unable to find $user unix user homedir");
		return 1;
	}

	$homeDir = normalizePath($homeDir);
	my $jailedHomedir = $self->{'jailCfg'}->{'chroot'} . $homeDir;

	# Add user into the jailed passwd file if any
	my $rs = $self->addPasswdFile('/etc/passwd', $user, $shell);
	return $rs if $rs;

	# Add user group into the jailed group file if any
	$rs = $self->addPasswdFile('/etc/group', $group);
	return $rs if $rs;

	# Add user into security chroot file
	if(-f $securityChrootCfgFile) {
		my $file = iMSCP::File->new( filename => $securityChrootCfgFile );
		my $fileContent = $file->get();
		unless(defined $fileContent) {
			error("Unable to read $file->{'filename'} file");
			return 1;
		}

		debug("Adding '$user' entry in $securityChrootCfgFile");

		my $userReg = quotemeta($user);
		$fileContent =~ s/^$userReg\s+.*\n//gm;
		$fileContent .= "$user\t$self->{'jailCfg'}->{'chroot'}\n";

		$rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;
	} else {
		error("File $securityChrootCfgFile not found");
		return 1;
	}

	if(-d $homeDir) {
		unless(-d $jailedHomedir) {
			# Create jailed homedir
			$rs = iMSCP::Dir->new(
				dirname => $jailedHomedir
			)->make(
				{ user => $main::imscpConfig{'ROOT_USER'}, group => $main::imscpConfig{'ROOT_GROUP'}, mode => 0755 }
			);
			return $rs if $rs;

			# Set owner/group for jailed homedir
			$rs = setRights($jailedHomedir, { user => $user, group => $group, mode => '0550' });
			return $rs if $rs;
		}

		# Mount user homedir within the jail
		$rs = $self->mount(
			{ 'file_system' => $homeDir, 'mount_point' => $jailedHomedir, 'type' => 'auto', 'options' => 'bind' }
		);
		return $rs if $rs;

		# Add fstab entry for user homedir
		$rs = $self->addFstabEntry("$homeDir $jailedHomedir none bind 0 0");
		return $rs if $rs;
	}

	0;
}

=item unjailUser($user [, $userOnly = FALSE ])

 Unjial the given user

 Param string $user User to remove from the jail
 Param bool $userOnly OPTIONAL When TRUE only the user entries are removed (homedir, and group are kept)
 Return int 0 on success, other on failure

=cut

sub unjailUser
{
	my ($self, $user, $userOnly) = @_;

	my @pwEntry = getpwnam($user);
	unless(@pwEntry) {
		error("Unable to find $user unix user");
		return 1;
	}

	my $homeDir = $pwEntry[7];
	unless(defined $homeDir) {
		error("Unable to find $user unix user homedir");
		return 1;
	}

	$homeDir = normalizePath($homeDir);
	my $jailedHomedir = $self->{'jailCfg'}->{'chroot'} . $homeDir;

	if(-d $self->{'jailCfg'}->{'chroot'}) {
		unless($userOnly) {
			if(-d $jailedHomedir) {
				# Umount user homedir from the jail
				my $rs = $self->umount($jailedHomedir);
				return $rs if $rs;

				$rs = iMSCP::Dir->new( dirname => $jailedHomedir )->remove();
				return $rs if $rs;
			}
		}

		# Remove user from the jailed passwd file if any
		my $rs = $self->removePasswdFile('/etc/passwd', $user);
		return $rs if $rs;

		unless($userOnly) {
			my $group = getgrgid($pwEntry[3]);

			unless(defined $group) {
				error("Unable to find $user unix user group");
				return 1;
			}

			# Remove user group from the jailed group file if any
			my $rs = $self->removePasswdFile('/etc/group', $group);
			return $rs if $rs;
		}
	}

	unless($userOnly) {
		# Remove fstab entry for user homedir
		my $rs = $self->removeFstabEntry(qr%.*?\s$jailedHomedir(?:/|\s).*%);
		return $rs if $rs;
	}

	# Remove user from security chroot file if any
	if(-f $securityChrootCfgFile) {
		my $file = iMSCP::File->new( filename => $securityChrootCfgFile );
		my $fileContent = $file->get();
		unless(defined $fileContent) {
			error("Unable to read $file->{'filename'} file");
			return 1;
		}

		debug("Removing '$user' entry from $securityChrootCfgFile");

		my $userReg = quotemeta($user);
		$fileContent =~ s/^$userReg\s+.*\n//gm;

		my $rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;
	}

	0;
}

=item addPasswdFile($file, $what [, $shell = undef ])

 Add the given user/group into the passwd/group file of the jail if any

 Param string $file Path of system passwd/group file
 Param string $what User/group name to add
 Param string $shell OPTIONAL User shell
 Return int 0 on success, 1 on failure

=cut

sub addPasswdFile
{
	my ($self, $file, $what, $shell) = @_;

	my $dest = $self->{'jailCfg'}->{'chroot'} . $file;

	if(-f $dest) {
		if(open my $fh, '<', $file) {
			my @sysLines = <$fh>;
			close $fh;

			if(open $fh, '+<', $dest) {
				s/^(.*?):.*/$1/s for (my @jailLines = <$fh>);

				my $regWhat = quotemeta($what);

				if(not grep $_ eq $what, @jailLines) {
					for my $sysLine(@sysLines) {
						if ($sysLine =~ /^$regWhat:/) {
							my @sysLineFields = split ':', $sysLine;

							if(defined $sysLineFields[6]) {
								debug("Adding '$what' user into $dest");
								$sysLineFields[5] = normalizePath($sysLineFields[5]);
								$sysLineFields[6] = $shell . "\n" if defined $shell;
							} else {
								debug("Adding '$what' group into $dest");
							}

							print $fh join ':', @sysLineFields;
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

	my $dest = $self->{'jailCfg'}->{'chroot'} . $file;

	if(-f $dest) {
		if(open my $fh, '<', $dest) {
			my @jailLines = <$fh>;
			close $fh;

			if(open $fh, '>', $dest) {
				debug("Removing '$what' user/group from $dest");

				$what = quotemeta($what);
				@jailLines = grep $_ !~ /^$what:.*/s, @jailLines;
				print $fh join '', @jailLines;
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

 Param string $entry Fstab entry to add
 Return int 0 on success, other on failure

=cut

sub addFstabEntry
{
	my ($self, $entry) = @_;

	my $file = iMSCP::File->new( filename => $fstabFile );
	my $fileContent = $file->get();
	unless(defined $fileContent) {
		error("Unable to read $file->{'filename'} file");
		return 1;
	}

	debug("Adding '$entry' entry in $fstabFile");

	my $entryReg = quotemeta($entry);
	$fileContent =~ s/^$entryReg\n//gm;
	$fileContent .= "$entry\n";

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item removeFstabEntry($entry)

 Remove fstab entry

 Param string|regexp $entry Fstab entry to remove as a string or regexp
 Return int 0 on success, other on failure

=cut

sub removeFstabEntry
{
	my ($self, $entry) = @_;

	my $file = iMSCP::File->new( filename => $fstabFile );
	my $fileContent = $file->get();
	unless(defined $fileContent) {
		error("Unable to read $file->{'filename'} file");
		return 0;
	}

	debug("Removing any entry matching with '$entry' from $fstabFile");

	my $regexp = (ref $entry eq 'Regexp') ? $entry : quotemeta($entry);
	$fileContent =~ s/^$regexp\n//gm;

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item mount(\%fstabEntry)

 Mount the filesystem as specified in the given fstab entry

 Param string \%fstabEntry Hash describing an fstab entry
 Return int 0 on success, other on failure

=cut

sub mount
{
 	my ($self, $fstabEntry) = @_;

	my $fileSystem = normalizePath($fstabEntry->{'file_system'});
	my $mountPoint = normalizePath($fstabEntry->{'mount_point'});

	if(execute("mount 2>/dev/null | grep -q ' $mountPoint '")) {
		unless(-e $mountPoint) { # Don't create $newdir if it already exists
			if(-d $fileSystem) {
				my $rs = iMSCP::Dir->new( dirname => $mountPoint )->make(
					{ user => $main::imscpConfig{'ROOT_USER'}, group => $main::imscpConfig{'ROOT_GROUP'}, mode => 0555 }
				);
				return $rs if $rs;
			} else {
				my $file = iMSCP::File->new( filename => $mountPoint );
				my $rs = $file->save();
				$rs ||= $file->mode(0444);
				$rs ||= $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
				return $rs if $rs;
			}
		} elsif(! -d _ && ! -f _) { # Enssure that the mount point is valid
			error(sprintf(
				'Cannot mount %s on %s: %s is not a directory nor a regular file', $fileSystem, $mountPoint, $mountPoint
			));
			return 1;
		}

		my @cmdArgs = (
			'-o', escapeShell($fstabEntry->{'options'}),
			'-t', $fstabEntry->{'type'},
			escapeShell($fileSystem),
			escapeShell($mountPoint)
		);

		my($stdout, $stderr);
		my $rs = execute("mount @cmdArgs", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $rs && $stderr;
		return $rs if $rs;
	}

	0;
}

=item umount($path)

 Umount the given file systems in safe way

 Note: In case of a partial path, any file systems below this path will be umounted.

 Param string $path Partial or full path to umount
 Return int 0 on success, other on failure

=cut

sub umount
{
	my ($self, $path) = @_;

	$path = normalizePath($path);

	my($stdout, $stderr, $mountPoint);

	do {
		my $rs = execute("mount 2>/dev/null | grep ' $path\\(/\\| \\)' | head -n 1 | cut -d ' ' -f 3", \$stdout);
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

 Return InstantSSH:JailBuilder or die on failure

=cut

sub _init
{
	my $self = $_[0];

	$self->{'jailCfg'} = {
		chroot => '',
		paths => [],
		create_dirs => {},
		sys_copy_file_to => {},
		jail_copy_file_to => {},
		packages => [],
		discard_packages => [],
		include_pkg_deps => 0,
		preserve_files => [],
		users => [],
		groups => [],
		devices => [],
		fstab => [],
		create_sys_commands => [],
		create_sys_commands_args => [],
		destroy_sys_commands => [],
		destroy_sys_commands_args => [],
		create_jail_commands => [],
		create_jail_commands_args => [],
		destroy_jail_commands => [],
		destroy_jail_commands_args => []
	};

	if(defined $self->{'id'} && $self->{'id'} =~ /^[a-z0-9]+/i) {
		if(defined $self->{'config'} && ref $self->{'config'} eq 'HASH') {
			unless(defined $self->{'config'}->{'makejail_path'} && -f $self->{'config'}->{'makejail_path'}) {
				die("InstantSSH::JailBuilder: Missing or wrong makejail_path parameter");
			} else {
				$self->{'config'}->{'makejail_path'} = normalizePath($self->{'config'}->{'makejail_path'});
			}

			unless(defined $self->{'config'}->{'makejail_confdir_path'}) {
				die("InstantSSH::JailBuilder: Missing makejail_confdir_path parameter");
			} else {
				$self->{'config'}->{'makejail_confdir_path'} = normalizePath(
					$self->{'config'}->{'makejail_confdir_path'}
				);
			}

			if(defined $self->{'config'}->{'root_jail_dir'}) {
				if(index($self->{'config'}->{'root_jail_dir'}, '/') == 0) {
					$self->{'jailCfg'}->{'chroot'} = normalizePath(
						$self->{'config'}->{'root_jail_dir'} . '/' . $self->{'id'}
					);
				} else {
					die("InstantSSH::JailBuilder: The root_jail_dir option must define an absolute path");
				}
			} else {
				die("InstantSSH::JailBuilder: The root_jail_dir option is not defined");
			}
		} else {
			die("InstantSSH::JailBuilder: Missing or wrong config parameter");
		}
	} else {
		die("InstantSSH::JailBuilder: Missing or wrong id parameter");
	}

	$self->_extractJailConf() == 0 or die("InstantSSH::JailBuilder: Unable to extract jail configuration");

	$self;
}

=item _extractJailConf()

 Extract jail configuration

 Return 0 on success, other on failure

=cut

sub _extractJailConf()
{
	my $self = $_[0];

	if(exists $self->{'config'}->{'preserve_files'}) {
		if(ref $self->{'config'}->{'preserve_files'} eq 'ARRAY') {
			@{$self->{'jailCfg'}->{'preserve_files'}} = @{$self->{'config'}->{'preserve_files'}};
		} else {
			error("The preserve_files option must be an array");
			return 1;
		}
	}

	if(exists $self->{'config'}->{'include_pkg_deps'}) {
		$self->{'jailCfg'}->{'include_pkg_deps'} = ($self->{'config'}->{'include_pkg_deps'}) ? 1 : 0;
	}

	if(exists $self->{'config'}->{'app_sections'}) {
		if(ref $self->{'config'}->{'app_sections'} eq 'ARRAY') {
			for my $section(@{$self->{'config'}->{'app_sections'}}) {
				if(exists $self->{'config'}->{$section}) {
					$self->_handleAppSection($self->{'config'}, $section);
				} else {
					error("The $section application section doesn't exists");
					return 1;
				}
			}

			undef $self->{'_app_sections'};
		} else {
			error("The app_sections option must be an array");
			return 1;
		}
	} else {
		error('The app_sections option is not defined');
		return 1;
	}

	0;
}

=item _buildMakejailCfgfile()

 Build makejail configuration file

 Return int 0 on success, other on failure

=cut

sub _buildMakejailCfgfile
{
	my $self = $_[0];

	my $fileContent = "# File auto-generated by i-MSCP InstantSSH plugin\n";
	$fileContent .= "# Do not edit it manually\n\n";
	$fileContent .= "chroot = \"$self->{'jailCfg'}->{'chroot'}\"\n";
	$fileContent .= "cleanJailFirst = 1\n";
	$fileContent .= "maxRemove = 900000\n";
	$fileContent .= "doNotCopy = []\n";

	if(@{$self->{'jailCfg'}->{'preserve_files'}}) {
		$fileContent .= 'preserve = [' .
			(join ', ', map { qq/"$_"/ } @{$self->{'jailCfg'}->{'preserve_files'}}) .
		"]\n";
	}

	if(@{$self->{'jailCfg'}->{'paths'}}) {
		$fileContent .= 'forceCopy = [' . (join ', ', map { qq/"$_"/ } @{$self->{'jailCfg'}->{'paths'}}) . "]\n";
	}

	if(@{$self->{'jailCfg'}->{'packages'}}) {
		$fileContent .= 'packages = [' . (join ', ', map { qq/"$_"/ } @{$self->{'jailCfg'}->{'packages'}}) . "]\n";
		$fileContent .= "useDepends = $self->{'jailCfg'}->{'include_pkg_deps'}\n";

		if(@{$self->{'jailCfg'}->{'discard_packages'}}) {
			$fileContent .= 'blockDepends = [' .
				(join ', ', map { qq/"$_"/ } @{$self->{'jailCfg'}->{'discard_packages'}}) .
			"]\n";
		}
	}

	if(@{$self->{'jailCfg'}->{'users'}}) {
		$fileContent .= 'users = [' . (join ', ', map { qq/"$_"/ } @{$self->{'jailCfg'}->{'users'}}) . "]\n";
	}

	if(@{$self->{'jailCfg'}->{'groups'}}) {
		$fileContent .= 'groups = [' . (join ', ', map { qq/"$_"/ } @{$self->{'jailCfg'}->{'groups'}}) . "]\n";
	}

	$fileContent .= "sleepAfterTest = 0.2\n";
	$fileContent .= "sleepAfterStartCommand = 0.2\n";
	$fileContent .= "sleepAfterKillall = 1.0\n"; # Not really needed ATM
	$fileContent .= "sleepAfterStraceAttachPid = 1.0\n"; # Not really needed ATM

	# Create makejail configuration directory or set its permissions if already exists
	my $rs = iMSCP::Dir->new( dirname => $self->{'config'}->{'makejail_confdir_path'} )->make(
		user => $main::imscpConfig{'ROOT_USER'}, group => $main::imscpConfig{'IMSCP_GROUP'}, mode => 0750
	);
	return $rs if $rs;

	my $file = iMSCP::File->new( filename => "$self->{'config'}->{'makejail_confdir_path'}/$self->{'id'}.py" );

	$rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'IMSCP_GROUP'});
	return $rs if $rs;

	$file->mode(0640);
}

=item _handleAppSection(\%config, $section)

 Handle application section

 Param hash \%config Hash containing Jail configuration options
 Param string $section Applications section definition
 Return int 0 on success, 1 on failure

=cut

sub _handleAppSection()
{
	my ($self, $cfg, $section) = @_;

	# Handle included application sections

	if(exists $cfg->{$section}->{'include_app_sections'}) {
		if(ref $cfg->{$section}->{'include_app_sections'} eq 'ARRAY') {
			for my $includedAppSection(@{$cfg->{$section}->{'include_app_sections'}}) {
				if(not grep $_ eq $includedAppSection, @{$self->{'_app_sections'}}) {
					$self->_handleAppSection($cfg, $includedAppSection);
					push @{$self->{'_app_sections'}}, $includedAppSection;
				}
			}
		} else {
			error('The include_app_sections option must be an array');
			return 1;
		}
	}

	# Handle list options from application section

	for my $option(
		qw/
			paths packages discard_packages users groups preserve_files devices fstab
			create_sys_commands create_sys_commands_args destroy_sys_commands destroy_sys_commands_args
			create_jail_commands create_jail_commands_args destroy_jail_commands destroy_jail_commands_args
		/
	) {
		if(exists $cfg->{$section}->{$option}) {
			if(ref $cfg->{$section}->{$option} eq 'ARRAY') {
				for my $item(@{$cfg->{$section}->{$option}}) {
					push @{$self->{'jailCfg'}->{$option}}, $item;
				}

				@{$self->{'jailCfg'}->{$option}} = uniq(@{$self->{'jailCfg'}->{$option}});
			} else {
				error("The $option option must be an array");
				return 1;
			}
		}
	}

	# Handle key/value pairs options from application section
	for my $option(qw/ create_dirs sys_copy_file_to jail_copy_file_to /) {
		if(exists $cfg->{$section}->{$option}) {
			if(ref $cfg->{$section}->{$option} eq 'HASH') {
				while(my ($key, $value) = each(%{$cfg->{$section}->{$option}})) {
					$self->{'jailCfg'}->{$option}->{$key} = $value;
				}
			} else {
				error("The $option option must be a hash");
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
__END__
