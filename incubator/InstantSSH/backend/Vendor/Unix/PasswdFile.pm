package Unix::PasswdFile;

use strict;
use vars qw($VERSION @ISA @EXPORT @EXPORT_OK);
use Unix::ConfigFile;
use Tie::IxHash;

require Exporter;

@ISA = qw(Unix::ConfigFile Exporter);

# Items to export into callers namespace by default. Note: do not export names by default without a very good reason.
# Use EXPORT_OK instead. Do not simply export all your public functions/methods/constants.
@EXPORT = qw( );

$VERSION = '0.07';

# Implementation notes
#
# This module only adds a single field to the basic ConfigFile object. The field is called 'pwent' (password entry) and
# is a hash of arrays (or, more properly, a reference to a hash of references to arrays!). The key is the username and
# the array contents are the next six fields found in the passwd file.

# Preloaded methods go here.

# Read the file and build the data structures
sub read
{
	my ($this, $fh) = @_;

	tie %{$this->{pwent}}, 'Tie::IxHash';

	while (<$fh>) {
		chop;
		$this->user(split /:/);
	}

	1;
}

# Add or change a user
sub user
{
	my $this = shift;
	my $username = shift;

	unless (@_) {
		return undef unless defined $this->{pwent}{$username};
		return @{$this->{pwent}{$username}};
	}

	return undef if @_ > 6;

	# Need to pad the list to 6 elements or we might lose colons during commit
	push @_, '' while @_ < 6;

	$this->{pwent}{$username} = [ @_ ];
}


# Rename a user
sub rename
{
	my ($this, $olduser, $newuser) = @_;

	return 0 unless defined $this->user($olduser);

	$this->user($newuser, $this->user($olduser));
	$this->delete($olduser);

	1;
}

# Delete a user
sub delete
{
	my ($this, $username) = @_;

	delete $this->{pwent}{$username};
}

# Return the list of usernames
# Accepts an optional sorting order parameter: uid or name
sub users
{
	my $this = shift;
	my $order = shift;

	return keys %{$this->{pwent}} unless wantarray;

	if(defined $order) {
		if ($order eq 'name') {
			return sort keys %{$this->{pwent}};
		} elsif($order = 'uid') {
			return sort { $this->uid($a) <=> $this->uid($b) } keys %{$this->{pwent}};
		}
	}

	keys %{$this->{pwent}};
}

# Returns the maximum UID in use in the file
sub maxuid
{
	my ($this, $ignore) = @_;
	my @uids = sort { $a <=> $b } map { $this->{pwent}{$_}[1] } keys %{$this->{pwent}};

	return undef unless @uids;

	my $retval = pop @uids;

	if (defined $ignore) {
		while ($retval >= $ignore && @uids) {
			$retval = pop @uids;
		}
	}

	$retval;
}

# Output the file to disk
sub write
{
	my ($this, $fh) = @_;

	for my $user ($this->users) {
		print $fh join(':', $user, $this->user($user)), "\n" or return 0;
	}

	1;
}

# Accessors (these all accept a username and an optional value)
# These must check for undefined data, or the act of accessing an array element will create the data.

sub passwd
{
	my $this = shift;
	my $username = shift;

	return undef unless defined $this->{pwent}{$username};
	@_ ? $this->{pwent}{$username}[0] = shift : $this->{pwent}{$username}[0];
}

sub uid
{
	my $this = shift;
	my $username = shift;

	return undef unless defined $this->{pwent}{$username};
	@_ ? $this->{pwent}{$username}[1] = shift : $this->{pwent}{$username}[1];
}

sub gid
{
	my $this = shift;
	my $username = shift;

	return undef unless defined $this->{pwent}{$username};
	@_ ? $this->{pwent}{$username}[2] = shift : $this->{pwent}{$username}[2];
}

sub gecos
{
	my $this = shift;
	my $username = shift;

	return undef unless defined $this->{pwent}{$username};
	@_ ? $this->{pwent}{$username}[3] = shift : $this->{pwent}{$username}[3];
}

sub home
{
	my $this = shift;
	my $username = shift;

	return undef unless defined $this->{pwent}{$username};
	@_ ? $this->{pwent}{$username}[4] = shift : $this->{pwent}{$username}[4];
}

sub shell
{
	my $this = shift;
	my $username = shift;

	return undef unless defined $this->{pwent}{$username};
	@_ ? $this->{pwent}{$username}[5] = shift : $this->{pwent}{$username}[5];
}

1;

__END__

=head1 NAME

Unix::PasswdFile - Perl interface to /etc/passwd format files

=head1 SYNOPSIS

  use Unix::PasswdFile;

  $pw = new Unix::PasswdFile "/etc/passwd";
  $pw->user("joeblow", $pw->encpass("secret"), $pw->maxuid + 1, 10, "Joe Blow", "/export/home/joeblow", "/bin/ksh");
  $pw->delete("deadguy");
  $pw->passwd("johndoe", $pw->encpass("newpass"));

  for $user ($pw->users) {
    print "Username: $user, Full Name: ", $pw->gecos($user), "\n";
  }

  $pw->commit();
  undef $pw;

=head1 DESCRIPTION

The Unix::PasswdFile module provides an abstract interface to /etc/passwd format files. It automatically handles file
locking, getting colons in the right places, and all the other niggling details.

=head1 METHODS

=head2 commit( [ BACKUPEXT ] )

See the Unix::ConfigFile documentation for a description of this method.

=head2 delete( USERNAME )

This method will delete the named user. It has no effect if the supplied user does not exist.

=head2 encpass( PASSWORD )

See the Unix::ConfigFile documentation for a description of this method.

=head2 gecos( USERNAME [, GECOS ] )

Read or modify a user's GECOS string (typically their full name).  Returns the GECOS string in either case.

=head2 gid( USERNAME [,GID] )

Read or modify a user's GID.  Returns the GID in either case.

=head2 home( USERNAME [, HOMEDIR ] )

Read or modify a user's home directory.  Returns the home directory in either case.

=head2 maxuid( [IGNORE] )

This method returns the maximum UID in use by all users. If you pass in the optional IGNORE parameter, it will ignore
all UIDs greater or equal to IGNORE when doing this calculation. This is useful for excluding accounts like nobody.

=head2 new( FILENAME [, OPTIONS ] )

See the Unix::ConfigFile documentation for a description of this method.

=head2 passwd( USERNAME [, PASSWD ] )

Read or modify a user's password.  Returns the encrypted password in either case. If you have a plaintext password, use
the encpass method to encrypt it before passing it to this method.

=head2 rename( OLDNAME, NEWNAME )

This method changes the username for a user. If NEWNAME corresponds to an existing user, that user will be overwritten.
It returns 0 on failure and 1 on success.

=head2 shell( USERNAME [,SHELL] )

Read or modify a user's shell.  Returns the shell in either case.

=head2 uid( USERNAME [,UID] )

Read or modify a user's UID.  Returns the UID in either case.

=head2 user( USERNAME [,PASSWD, UID, GID, GECOS, HOMEDIR, SHELL] )

This method can add, modify, or return information about a user. Supplied with a single username parameter, it will
return a six element list consisting of (PASSWORD, UID, GID, GECOS, HOMEDIR, SHELL), or undef if no such user exists.
If you supply all seven parameters, the named user will be created or modified if it already exists. The six element
list is also returned to you in this case.

=head2 users( [SORTBY] )

This method returns a list of all existing usernames. You may supply "name" as a parameter to the method to get the list
sorted by username or "uid" to get the list sorted by uid. In scalar context, this method returns the total number of
users.

=head1 AUTHORS

Laurent Declercq, l.declercq@nuxwin.com
Steve Snodgrass, ssnodgra@fore.com

=head1 SEE ALSO

Unix::AliasFile, Unix::AutomountFile, Unix::ConfigFile, Unix::GroupFile

=cut
