#!/usr/bin/perl

=head1 NAME

 InstantSSH::JailBuilder::Utils;

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

package InstantSSH::JailBuilder::Utils;

use strict;
use warnings;

use File::Basename;
use File::stat ();
use File::Spec ();
use Fcntl qw(:mode);
use base qw(Exporter);

our @EXPORT_OK = qw(normpath resolveRealpath copyTimeAndPermissions createParentPath copyDevice);

my %STATCACHE = ();

=head1 DESCRIPTION

 This package is part of the i-MSCP InstantSSH plugin. It provide high level utility functions for file handling.

 Library based upon the jk_lib.py library from the Jailkit project ( http://http://olivier.sessink.nl/jailkit/ ).

=head1 PUBLIC FUNCTIONS

=over 4

=item normpath($path)

 Normalize a path, e.g. A//B, A/./B and A/foo/../B all become A/B

 It should be understood that this may change the meaning of the path if it contains symbolic links.

 Note: This is a reimplementaion in Perl of the os.path.normpath() method from the python project (Lib/posixpath.py).

 Param string $path Path to normalize
 Return string Normalized path

=cut

sub normpath($)
{
	my $path = shift;

	# Normalize path, eliminating double slashes, etc...
	my ($slash, $dot) = ('/', '.');

	return $dot if $path eq '';

	my $initialSlashes = (index($path, '/') == 0);

	# POSIX allows one or two initial slashes, but treats three or more as single slash.
	$initialSlashes = 2 if $initialSlashes && index($path, '//') == 0 && index($path, '///') != 0;

	my @comps = split '/', $path;
	my @newComps = ();

	for my $comp(@comps) {
		next if grep $_ eq $comp, ('', '.');

		if($comp ne '..' || (! $initialSlashes && ! @newComps) || (@newComps && $newComps[-1] eq '..')) {
			push @newComps, $comp;
		} elsif(@newComps) {
			pop @newComps;
		}
	}

	@comps = @newComps;
	$path = join $slash, @comps;
	$path = "$slash"x$initialSlashes . $path if $initialSlashes;

	$path || $dot;
}

=item resolveRealpath($path [, $chroot = '' [, $includeFile = false]])

 Resolves the real path of the given path relative to the given chroot, including file or not

 Param string $path Path to resolve
 Param string $chroot OPTIONAL Chroot path (default: '')
 Param bool $includeFile OPTIONAL Include file or not (default: false)
 Return string Real path (die on failure)
=cut

sub resolveRealpath($$;$)
{
	my ($path, $chroot, $includeFile) = @_;

	$chroot ||= '';

	return '/' if $path eq '/';

	my @spath = File::Spec->splitdir(File::Spec->canonpath($path));
	my $basename = '';

	unless($includeFile) {
		$basename = $spath[-1];
		@spath = splice @spath, 0, -1;
	}

	my $ret = '/';

	for my $entry (@spath) {
		$ret = File::Spec->join($ret, $entry);

		my $sb = _stat($ret);

		if(S_ISLNK($sb->mode)) {
			my $realpath = readlink($ret) or die("InstantSSH::JailBuilder::Utils: Unable to read link $ret: $!");

			if(index($path, '/') == 0) {
				$ret = normpath($chroot . $realpath);
			} else {
				my $tmp = normpath(File::Spec->join(dirname($ret), $realpath));

				if(length($chroot) > 0 && substr($chroot, 0, length($chroot)) ne $chroot) {
					die('InstantSSH::JailBuilder::Utils: Symlink $tmp points outside jail');
				}

				$ret = $tmp;
			}
		}
	}

	File::Spec->join($ret, $basename);
}

=item copyTimeAndPermissions($src, $dst [, $allowSuid = false [, $copyOwnership = false]])

 Copy time and permissions from a file to another

 Param string $src Source file from which time and permissions must be copied
 Param string $dst Destination file to which time and permissions mubt be copied
 Param bool $allowSuid OPTIONAL Whether setuid/setgid permissions must be copied (default: false)
 Param bool $copyOwnership OPTIONAL whether user/group must be copied (default: false)
 Return undef (die on failure)

=cut

sub copyTimeAndPermissions($$;$$)
{
	my($src, $dst, $allowSuid, $copyOwnership) = @_;

	my $sb = File::stat::stat($src) or die("InstantSSH::JailBuilder::Utils: Failed to stat on $src: $!");
	my $mode = S_IMODE($sb->mode);

	unless($allowSuid) {
		if($mode & (S_ISUID | S_ISGID)) {
			# Remove setuid and setgid permissions
			$mode = ($mode & ~S_ISUID) & ~S_ISGID;
		}
	}

	utime($sb->atime, $sb->mtime, $dst) or die(
		"InstantSSH::JailBuilder::Utils: Failed to copy the access and modification times on $dst: $!"
	);

	if ($copyOwnership) {
		chown($sb->uid, $sb->gid, $dst) or die("InstantSSH::JailBuilder::Utils: Failed to set ownership on $dst: $!");
	}

	# WARNING: chmod must be done AFTER chown to preserve setuid/setgid bits
	chmod($mode, $dst) or die("InstantSSH::JailBuilder::Utils: Failed to set mode on $dst: $!");

	undef;
}

=item createParentPath($chroot, $path [, $copyPermissions = true [, $allowSuid = false [, $copyOwnership = false]]])

 Create the given parent path inside the given chroot

 Param string $chroot Chroot into which the parent path must be created
 Param string $path Parent path that must be created inside the chroot
 Param bool $copyPermissions OPTIONAL Whether or not permissions must be copied (default: true)
 Param bool $copyPermissions OPTIONAL Whether or not setuid/setgid permissions must be copied (default: false)
 Param bool $copyPermissions OPTIONAL Whether or not user/group must be copied (default: false)
 Return string  (die on failure)

=cut

sub createParentPath($$;$$$)
{
	my ($chroot, $path, $copyPermissions, $allowSuid, $copyOwnership) = @_;

	my @spath = File::Spec->splitdir($path);
	my $existpath = $chroot;
	my $i = 0;

	# the first part of the function checks the already existing paths in the jail and follow any symplinks relative
	# to the jail
	while($i < scalar @spath) {
		my $tmp1 = File::Spec->catfile($existpath, $spath[$i]);
		last unless -e $tmp1;

		my $tmp = resolveRealpath($tmp1, $chroot, 1);
		last unless -e $tmp;
		$existpath = $tmp;
		$i++;
	}

	# The second part of the function create the missing parts in the jail according the original directory names,
	# including any symlinks
	while($i < scalar @spath) {
		my $origpath = File::Spec->catfile((@spath)[0..$i+1]);
		my $jailpath = File::Spec->catfile($existpath, $spath[$i]);

		my $sb = _stat($origpath);

		if(S_ISDIR($sb->mode)) {
			mkdir($jailpath, 0755) or die(sprintf('Unable to create %s: %s', $jailpath, $!));

			if($copyPermissions) {
				copyTimeAndPermissions($origpath, $jailpath, $allowSuid, $copyOwnership);
			}
		} elsif(S_ISLNK($sb->mode)) {
			my $realfile = readlink($origpath);

			symlink($realfile, $jailpath) or die('Unable to create symlink $realfile -> $jailpath');

			if(index($realfile, '/') == 0) {
				$jailpath = &createParentPath($chroot, $realfile, $copyPermissions, $allowSuid, $copyOwnership);
			} else {
				my $tmp = normpath(File::Spec->catfile(dirname($jailpath), $realfile));

				if(length($chroot) > 0 && substr($tmp, 0, length($chroot)) ne $chroot) {
					die("InstantSSH::JailBuilder::Utils: Symlink $tmp points outside jail");
				}

				$realfile = substr($tmp, length($chroot));
				$jailpath = &createParentPath($chroot, $realfile, $copyPermissions, $allowSuid, $copyOwnership);
			}
		}

		$existpath = $jailpath;
		$i++;
	}

	$existpath;
}

=item copyDevice($chroot, $path [, $copyOwnership = false])

 Copy the given character or block device inside the given jail

 Param string $chroot Path of the chroot in which device must be copied
 Param string $path Path of the device to copy
 Param bool $copyOwnership OPTIONAL Whether or not ownership must be copied (default: false)
 Return undef (die on failure)

=cut

sub copyDevice($$;$)
{
	my($chroot, $path, $copyOwnership) = @_;

	createParentPath($chroot, dirname($path), 1);

	my $chrootpath = resolveRealpath($chroot . $path, $chroot);

	# Do not try to create the device if it already exist within the jail
	unless(-e $chrootpath) {
		my $sb = File::stat::stat($path) or die("InstantSSH::JailBuilder::Utils: Failed to stat on $path: $!");
		my $mode = $sb->mode;
		my $major = int($sb->rdev / 256);
		my $minor = int($sb->rdev % 256);

		my $type;
		if(S_ISCHR($mode)) { # character device
			$type = 'c';
		} elsif(S_ISBLK($mode)) { # block device
			$type = 'b';
		} else {
			die("$path is not a character nor a block device");
		}

		# Create the device inside the jail
		system("mknod $chrootpath $type $major $minor > /dev/null 2>&1") == 0 or die(
			"InstantSSH::JailBuilder::Utils: Unable to create the $chrootpath device: $!"
		);

		# Copy time and permissions
		copyTimeAndPermissions($path, $chrootpath, undef, $copyOwnership);
	}

	undef;
}

=back

=head1 PRIVATE FUNCTIONS

=over 4

=item _stat($path)

 Get file status info

 Note: Info are cached for possible later use.

 Param string $path Path for which status info must be returned
 Return File::Stat

=cut

sub _stat($)
{
	my $path = shift;

	my $ret = (exists $STATCACHE{$path}) ? $STATCACHE{$path} : undef;

	unless (defined $ret) {
		$STATCACHE{$path} = $ret = File::stat::stat($path) or die(
			"InstantSSH::JailBuilder::Utils: Failed to stat on $path: $!"
		);
	}

	$ret;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
