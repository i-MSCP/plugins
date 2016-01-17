=head1 NAME

 Plugin::PolicydWeight

=cut

# i-MSCP PolicydWeight plugin
# Copyright (C) 2015-2016 Laurent Declercq <l.declercq@nuxwin.com>
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

package Plugin::PolicydWeight;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::Execute;
use iMSCP::Service;
use Servers::mta;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part of the PolicydWeight plugin.

=head1 PUBLIC METHODS

=over 4

=item enable()

 Perform enable tasks

 Return 0 on success, other on failure

=cut

sub enable
{
	my $self = shift;

	my $rs = $self->_checkRequirements();
	return $rs if $rs;

	$rs = execute('postconf -h smtpd_recipient_restrictions', \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Extract postconf values
	chomp($stdout);
	my $postconfValues = $stdout;
	my @smtpRestrictions = split ', ', $postconfValues;

	# Add policyd-weight policy server
	s/^permit$/check_policy_service inet:127.0.0.1:$self->{'config'}->{'policyd_weight_port'}/ for @smtpRestrictions;
	push @smtpRestrictions, 'permit';

	my $postconf = 'smtpd_recipient_restrictions=' . escapeShell(join ', ', @smtpRestrictions);

	$rs = execute("postconf -e $postconf", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Create policyd-weight configuration file if needed
	unless(-f '/etc/policyd-weight.conf') {
		$rs = execute('policyd-weight defaults >/etc/policyd-weight.conf', \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	# Make sure that policyd-weight daemon is running
	iMSCP::Service->getInstance()->restart('policyd-weight');

	Servers::mta->factory()->{'restart'} = 1;

	0;
}

=item disable()

 Perform disable tasks

 Return 0 on success, other on failure

=cut

sub disable
{
	my $self = shift;

	my $rs = execute('postconf -h smtpd_recipient_restrictions', \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Extract postconf values
	chomp($stdout);
	my $postconfValues = $stdout;

	# Remove policyd-weight policy server
	my @smtpRestrictions = grep {
		$_ !~ /^check_policy_service\s+inet:127.0.0.1:$self->{'config_prev'}->{'policyd_weight_port'}$/
	} split ', ', $postconfValues;

	my $postconf = 'smtpd_recipient_restrictions=' . escapeShell(join ', ', @smtpRestrictions);

	$rs = execute("postconf -e $postconf", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	Servers::mta->factory()->{'restart'} = 1;

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _checkRequirements()

 Check for requirements

 Return int 0 if all requirements are meet, other otherwise

=cut

sub _checkRequirements
{
	my $rs = execute(
		"LANG=C dpkg-query --show --showformat '\${Status}' policyd-weight | cut -d ' ' -f 3", \my $stdout, \my $stderr
	);
	debug($stdout) if $stdout;
	if($stdout ne 'installed') {
		error("The policyd-weight package is not installed on your system");
		return 1;
	}

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
