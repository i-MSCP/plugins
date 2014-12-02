package Unix::ConfigFile;

use 5.004;
use strict;
use vars qw($VERSION @ISA @EXPORT @EXPORT_OK $LOCKEXT);
use Carp;
use IO::File;
use Fcntl qw(:flock);
use Text::Tabs;

require Exporter;

@ISA = qw(Exporter);

# Items to export into callers namespace by default. Note: do not export names by default without a very good reason.
# Use EXPORT_OK instead. Do not simply export all your public functions/methods/constants.
@EXPORT = qw( );

$VERSION = '0.06';

# Package variables
my $SALTCHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789/.';

# Preloaded methods go here.

# Create a new ConfigFile (or, more likely, a ConfigFile subclass) object.
# Opens the specified file and calls the read method (which will be located
# in the subclass package) to initialize the object data structures
sub new
{
	my ($pkg, $filename, %opt) = @_;

	# Initialize the object reference
	my $this = {
		filename => $filename,
		handle => undef,
		locked => 0,
		lockfh => undef,
		lockfile => "$filename.lock",
		locking => 'dotlock',
		mode => 'r+',
		seq => [ ]
	};

	bless $this, $pkg;

	# Set options
	$this->lockfile($opt{lockfile}) if defined $opt{lockfile};
	$this->locking($opt{locking}) if defined $opt{locking};
	$this->mode($opt{mode}) if defined $opt{mode};

	# Get a filehandle
	my $fh = new IO::File $this->filename, $this->mode;
	return undef unless defined($fh);
	$this->fh($fh);

	# Do file locking - this must happen before read is called or we could end up with stale data in memory
	if ($this->mode eq 'r') {
		$this->lock('shared') or return undef;
	} else {
		$this->lock() or return undef;
	}

	# Initialize object structure from the file
	if (exists $opt{readopts}) {
		$this->read($this->fh, $opt{readopts}) or return undef;
	} else {
		$this->read($this->fh) or return undef;
	}

	$this;
}

# Commit in-memory changes to disk
sub commit
{
	my ($this, %opt) = @_;

	return 0 if $this->mode eq 'r';

	my $tempname = $this->filename . '.tmp.' . $$;
	my $fh = new IO::File ">$tempname" or return 0;
	my ($mode, $uid, $gid) = (stat $this->fh)[2,4,5];
	chown $uid, $gid, $tempname;
	chmod $mode, $tempname;

	if (exists $opt{writeopts}) {
		$this->write($fh, $opt{writeopts}) or return 0;
	} else {
		$this->write($fh) or return 0;
	}

	undef $fh;

	if (defined $opt{backup}) {
		rename $this->filename, $this->filename . $opt{backup};
	}

	rename $tempname, $this->filename;
}

# This method is absolutely necessary to prevent leftover lock files
sub DESTROY
{
	my $this = shift;

	$this->unlock() or croak "Can't unlock file: $!";
	$this->fh->close();
}

# Filename accessor
sub filename
{
	my $this = shift;

	@_ ? $this->{filename} = shift : $this->{filename};
}

# Filehandle accessor
sub fh
{
	my $this = shift;

	@_ ? $this->{handle} = shift : $this->{handle};
}

# Locking method accessor
sub locking
{
	my $this = shift;

	return $this->{locking} unless @_;

	my $lockmethod = shift;

	return undef unless grep { $lockmethod eq $_ } qw(flock dotlock none);
	$this->{locking} = $lockmethod;
}

# Lock filehandle accessor
sub lockfh
{
	my $this = shift;
	@_ ? $this->{lockfh} = shift : $this->{lockfh};
}

# Lock file name accessor
sub lockfile
{
	my $this = shift;
	@_ ? $this->{lockfile} = shift : $this->{lockfile};
}

# Mode accessor
sub mode
{
	my $this = shift;

	return $this->{mode} unless @_;

	my $mode = shift;

	return undef unless grep { $mode eq $_ } qw(r r+ w);
	$this->{mode} = $mode;
}

# Obtain a lock on the file.  You can pass "shared" to request a shared lock; the default is exclusive. This function is
# somewhat inconsistent at the moment since it will block with the flock method but return an error if the dotlock
# method fails.
sub lock
{
	my $this = shift;

	return 1 if ($this->locking eq 'none');
	return 0 if $this->{locked};

	if ($this->locking eq 'flock') {
		@_ ? flock $this->fh, LOCK_SH : flock $this->fh, LOCK_EX;
	} elsif ($this->locking eq 'dotlock') {
		# We only support exclusive locks with dotlock
		my $fh = new IO::File $this->lockfile, O_CREAT|O_EXCL|O_RDWR;

		return 0 unless defined($fh);
		$this->lockfh($fh);
	}

	$this->{locked} = 1;
}

# Unlock the file
sub unlock
{
	my $this = shift;

	# NOTE: Originally I wasn't unlinking the lock file unless the lock filehandle was defined. This led to the rather
	# unexpected discovery the Perl would sometimes destroy the filehandle before destroying the object during program
	# shutdown.  Thus, we now check if locked is set, which happens only if a lock is successfully acquired. This also
	# prevents us from unlinking someone else's lock file.

	return 1 if ($this->locking eq 'none');
	return 0 unless $this->{locked};

	$this->{locked} = 0;

	if ($this->locking eq 'flock') {
		flock $this->fh, LOCK_UN;
		return 1;
	} elsif ($this->locking eq 'dotlock') {
		$this->lockfh->close() if defined($this->lockfh);
		my $result = unlink $this->lockfile;
		return ($result == 1);
	}
}

# Encrypts a plaintext password with a random salt This is provided for use with the subclasses
sub encpass
{
	my ($this, $pass) = @_;

	my $salt = substr($SALTCHARS, int(rand(length($SALTCHARS))), 1) .
		substr($SALTCHARS, int(rand(length($SALTCHARS))), 1);

	crypt($pass, $salt);
}

# Return the file sequence
sub sequence
{
	my $this = shift;

	@{$this->{seq}};
}

# Append information to the file sequence
sub seq_append
{
	my $this = shift;

	push @{$this->{seq}}, @_;
}

# Insert information into the file sequence before the given data
sub seq_insert
{
	my $this = shift;
	my $data = shift;

	for (my $i = 0; $i < @{$this->{seq}}; $i++) {
		if ($this->{seq}[$i] eq $data) {
			splice @{$this->{seq}}, $i, 0, @_;
			return 1;
		}
	}

	0;
}

# Remove the specified data from the file sequence
sub seq_remove
{
	my ($this, $data) = @_;

	for (my $i = 0; $i < @{$this->{seq}}; $i++) {
		if ($this->{seq}[$i] eq $data) {
			splice @{$this->{seq}}, $i, 1;
			return 1;
		}
	}

	0;
}

# Joinwrap is a utility function that happens to be useful in several modules
# This thing was a bitch to get working 100% right, so use caution.
sub joinwrap
{
	my ($this, $linelen, $head, $indent, $delim, $tail, @list) = @_;

	my $result = "";
	my $line = 0;
	$linelen -= length(expand($tail));

	while (@list) {
		my $curline = $result ? $indent : $head;
		$curline =~ s/%n/$line/;
		my $appended = 0;

		while (@list && length(expand($curline . $delim . $list[0])) <= $linelen) {
			$curline .= $delim if $appended;
			$curline .= shift @list;
			$appended++;
		}

		# Special case - element is longer than linelen
		$curline .= shift @list unless $appended;

		# Append newline if this isn't the first line
		$result .= "\n" if $result;
		$result .= $curline;

		# Append tail unless this is the last line
		$result .= $tail if @list;
		$line++;
	}

	$result ? $result : $head;
}

# Autoload methods go after =cut, and are processed by the autosplit program.

1;

__END__

=head1 NAME

Unix::ConfigFile - Perl interface to various Unix configuration files

=head1 SYNOPSIS

  use Unix::ConfigFile;

=head1 DESCRIPTION

The Unix::ConfigFile module provides a base class from which the other Unix::*File modules are derived. It provides some
basic facilities like file opening, locking, and closing. You do not need to use this module directly unless you are
developing a derived module for an unsupported configuration file. However, some of the methods documented here are
intended for public use by users of Unix::ConfigFile submodules, so you may find this documentation useful even if you
are not developing your own module.

The ConfigFile object also provides a sequencing API for modules that wish to preserve the order of the configuration
file they read and write. The sequencer maintains a list of arbitrary data that a submodule may append, insert, and
delete from. Use of the sequencer is completely optional.

A module that subclasses from Unix::ConfigFile must, at a minimum, provide two methods, called 'read' and 'write'. Both
methods will receive a filehandle as a parameter (besides the regular object parameter). The read method is called after
the file is opened. It is expected to read in the configuration file and initialize the subclass-specific data
structures associated with the object. The write method is called when an object is committed and is expected to write
out the new configuration to the supplied filehandle.

=head1 USER METHODS

=head2 commit( [%OPTIONS] )

This writes any changes you have made to the object back to disk. If you do not call commit, none of your changes will
be reflected in the file you are modifying. Commit may not be called on files opened in read-only mode. There are some
optional parameters that may be provided; these are passed in the form of key => value pairs. The 'backup' option allows
you to specify a file extension that will be used to save a backup of the original file. The 'writeopts' option passes
module-specific options through to the write method. It will accept any scalar for its value; typically this will be a
list or hash reference. Commit returns 1 on success and 0 on failure.

=head2 encpass( PASSWORD )

This method encrypts the supplied plaintext password using a random salt and returns the encrypted password. Note that
this method does not actually make any use of the object that it is invoked on, and could be called as a class method.

=head2 new( FILENAME [,%OPTIONS] )

The new method constructs a new ConfigFile (or subclass) object using the specified FILENAME. There are several optional
parameters that may be specified. Options must be passed as keyed pairs in the form of option => value. Valid options
are 'locking', 'lockfile', 'mode', and 'readopts'.

The locking option determines what style of file locking is used; available styles are 'dotlock', 'flock', and 'none'.
The default locking style is 'dotlock'. The 'none' locking style causes no locking to be done, and all lock and unlock
requests will return success. The lockfile option can be used to specify the lock filename used with dotlocking. The
default is 'FILENAME.lock', where FILENAME is the name of the file being opened. The mode option allows the file open
mode to be specified.  The default mode is 'r+' (read/write), but 'r' and 'w' are accepted as well. Finally, the
readopts option allows module-specific options to be passed through to the read method. It will accept any scalar for
its value; typically this will be a list or hash reference.

=head1 DEVELOPER METHODS

=head2 joinwrap( LENGTH, HEAD, INDENT, DELIM, TAIL, @LIST )

This is a utility function that may be called as an object or class method. As the name suggests, this method is
basically a version of the join function that incorporates line wrapping. The specified list will be joined together,
with each list element separated by the specified delimiter.

The first line of output will be prefixed with the HEAD parameter. If a line exceeds the length parameter, output is
wrapped to the next line and the INDENT parameter is used to prefix the line. In addition, the TAIL parameter will be
added to the end of every line generated except the final one. There is one case where the resulting string can exceed
the specified line length - if a single list element, plus HEAD or INDENT, exceeds that length. One final feature is
that if the HEAD or INDENT parameters contain the text '%n', it will be replaced with the current line number, beginning
at 0.

=head2 sequence( )

Returns the current sequence list associated with the object. This is a list of arbitrary data maintained by a
ConfigFile submodule.  The ConfigFile module does not care what is contained in the list.

=head2 seq_append( @DATA )

Appends that specified data to the end of the sequence list.

=head2 seq_insert( KEY, @DATA )

Inserts the data into the sequence list before the data that matches the specified key.

=head2 seq_remove( KEY )

Removes the data from the sequence list that matches the specified key.

=head1 AUTHOR

Steve Snodgrass, ssnodgra@fore.com

=head1 SEE ALSO

Unix::AliasFile, Unix::AutomountFile, Unix::GroupFile, Unix::PasswdFile

=cut
