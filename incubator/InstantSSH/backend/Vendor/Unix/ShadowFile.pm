package Unix::ShadowFile;

use strict;
use vars qw($VERSION @ISA @EXPORT @EXPORT_OK);
use Unix::ConfigFile;
use Tie::IxHash;

require Exporter;

@ISA = qw(Unix::ConfigFile Exporter);

# Items to export into callers namespace by default. Note: do not export names by default without a very good reason.
# Use EXPORT_OK instead. Do not simply export all your public functions/methods/constants.
@EXPORT = qw( );

$VERSION = '0.01';

# Implementation notes
#
# This module only adds a single field to the basic ConfigFile object. The field is called 'swent' (shadow entry) and
# is a hash of arrays (or, more properly, a reference to a hash of references to arrays!). The key is the username and
# the array contents are the next 8 fields found in the shadow file.

# Preloaded methods go here.

# Read the file and build the data structures
sub read
{
	my ($this, $fh) = @_;

	tie %{$this->{swent}}, 'Tie::IxHash';

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
		return undef unless defined $this->{swent}{$username};
		return @{$this->{swent}{$username}};
	}

	return undef if @_ > 8;

	# Need to pad the list to 8 elements or we might lose colons during commit
	push @_, '' while @_ < 8;

	$this->{swent}{$username} = [ @_ ];
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

	delete $this->{swent}{$username};
}

# Return the list of usernames
# Accepts an optional sorting order parameter: name
sub users
{
	my ($this, $order) = @_;

	return keys %{$this->{swent}} unless wantarray;

	if(defined $order && $order eq 'name') {
		return sort keys %{$this->{swent}};
	}

	keys %{$this->{swent}};
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

# encrypted password
sub passwd
{
	my $this = shift;
	my $username = shift;

	return undef unless defined $this->{swent}{$username};
	@_ ? $this->{swent}{$username}[0] = shift : $this->{swent}{$username}[0];
}

#  Date of last password change
sub lastmtime
{
	my $this = shift;
	my $username = shift;

	return undef unless defined $this->{swent}{$username};
	@_ ? $this->{swent}{$username}[1] = shift : $this->{swent}{$username}[1];
}

# Minimum password age
sub minage
{
	my $this = shift;
	my $username = shift;

	return undef unless defined $this->{swent}{$username};
	@_ ? $this->{swent}{$username}[2] = shift : $this->{swent}{$username}[2];
}

# Maximum password age
sub maxage
{
	my $this = shift;
	my $username = shift;

	return undef unless defined $this->{swent}{$username};
	@_ ? $this->{swent}{$username}[3] = shift : $this->{swent}{$username}[3];
}

# Password warning period
sub warningperiod
{
	my $this = shift;
	my $username = shift;

	return undef unless defined $this->{swent}{$username};
	@_ ? $this->{swent}{$username}[4] = shift : $this->{swent}{$username}[4];
}

# Password inactivity period
sub inactivityperiod
{
	my $this = shift;
	my $username = shift;

	return undef unless defined $this->{swent}{$username};
	@_ ? $this->{swent}{$username}[5] = shift : $this->{swent}{$username}[5];
}

# Account expiration date
sub expiredate
{
	my $this = shift;
	my $username = shift;

	return undef unless defined $this->{swent}{$username};
	@_ ? $this->{swent}{$username}[6] = shift : $this->{swent}{$username}[6];
}

1;

__END__

=head1 NAME

Unix::ShadowFile - Perl interface to /etc/shadow format files

=head1 SYNOPSIS

  use Unix::ShadowFile;

  $sw = new Unix::ShadowFile '/etc/shadow';
  $sw->user('joeblow', $sw->encpass('secret'), 16338, 0, 99999, 7, '', '');
  $sw->delete('deadguy');
  $sw->passwd('johndoe', $sw->encpass('newpass'));

  for $user ($sw->users) {
    print "Username: $user, Last password change: ", $sw->lastmtime($user), "\n";
  }

  $sw->commit();
  undef $sw;

=head1 DESCRIPTION

The Unix::ShadowFile module provides an abstract interface to /etc/shadow format files. It automatically handles file
locking, getting colons in the right places, and all the other niggling details.

=head1 METHODS

=head2 commit( [BACKUPEXT] )

See the Unix::ConfigFile documentation for a description of this method.

=head2 delete( USERNAME )

This method will delete the named user. It has no effect if the supplied user does not exist.

=head2 encpass( PASSWORD )

See the Unix::ConfigFile documentation for a description of this method.

=head2 expiredate( USERNAME [, EXPIREDATE ] )

Read or modify an account expiration date string (expressed as the number of days since Jan 1, 1970.). Returns the
account expiration date string in either case.

=head2 inactivityperiod( USERNAME [, INACTIVITYPERIOD ] )

Read or modify a password inactivity period string. Returns the password inactivity period string in either case.

=head2 warningperiod( USERNAME [, WARNINGPERIOD ] )

Read or modify a password warning period. Returns the password warning period string in either case.

=head2 maxage( USERNAME [, MAXAGE ] )

Read or modify a maximum password age string. Returns the maximum password age string in either case.

=head2 minage( USERNAME [, MINAGE ] )

Read or modify a minimum password age string. Returns the minimum password age string in either case.

=head2 lastmtime( USERNAME [, LASTMTIME ] )

Read or modify a date of last password change (expressed as the number of days since Jan 1, 1970.). Returns the date of
last password change string in either case.

=head2 passwd( USERNAME [, PASSWD ] )

Read or modify a user's password.  Returns the encrypted password in either case. If you have a plaintext password, use
the encpass method to encrypt it before passing it to this method.

=head2 new( FILENAME [, OPTIONS ] )

See the Unix::ConfigFile documentation for a description of this method.

=head2 rename( OLDNAME, NEWNAME )

This method changes the username for a user. If NEWNAME corresponds to an existing user, that user will be overwritten.
It returns 0 on failure and 1 on success.

=head2 user( USERNAME [, PASSWD, LASTMTIME, MINAGE, MAXAGE, WARNINGPERIOD, INACTIVITYPERIOD, EXPIREDATE ] )

This method can add, modify, or return information about a user. Supplied with a single username parameter, it will
return a six element list consisting of (PASSWORD, UID, GID, GECOS, HOMEDIR, SHELL), or undef if no such user exists.
If you supply all seven parameters, the named user will be created or modified if it already exists.  The six element
list is also returned to you in this case.

=head2 users( [ SORTBY ] )

This method returns a list of all existing usernames. You may supply "name" as a parameter to the method to get the list
sorted by username. In scalar context, this method returns the total number of users.

=head1 AUTHOR

Laurent Declercq, l.declercq@nuxwin.com

=head1 SEE ALSO

Unix::AliasFile, Unix::AutomountFile, Unix::ConfigFile, Unix::GroupFile

=cut
