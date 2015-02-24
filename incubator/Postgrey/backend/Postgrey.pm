=head1 NAME

 Plugin::Postgrey

=cut

# i-MSCP Postgrey plugin
# Copyright (C) 2015 Laurent Declercq <l.declercq@nuxwin.com>
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

package Plugin::Postgrey;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::Execute;
use iMSCP::Service;
use Servers::mta;
use JSON;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provide the backend part of the Postgrey plugin.

=head1 PUBLIC METHODS

=over 4

=item enable()

 Perform enable tasks

 Return 0 on success, other on failure

=cut

sub enable
{
	my $self = $_[0];

	my $rs = $self->_checkRequirements();
	return $rs if $rs;

	my ($stdout, $stderr);
	$rs = execute('postconf smtpd_recipient_restrictions', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Extract postconf values
	chomp($stdout);
	(my $postconfValues = $stdout) =~ s/^.*=\s*(.*)/$1/;
	my @smtpRestrictions = split ', ', $postconfValues;

	# Add Postgrey policy server
	s/^permit$/check_policy_service inet:127.0.0.1:$self->{'config'}->{'postgrey_port'}/ for @smtpRestrictions;
	push @smtpRestrictions, 'permit';

	my $postconf = 'smtpd_recipient_restrictions=' . escapeShell(join ', ', @smtpRestrictions);

	$rs = execute("postconf -e $postconf", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Make sure that postgrey daemon is running
	$rs = iMSCP::Service->getInstance()->restart('postgrey', '-f postgrey');
	return $rs if $rs;

	Servers::mta->factory()->{'restart'} = 1;

	0;
}

=item disable()

 Perform disable tasks

 Return 0 on success, other on failure

=cut

sub disable
{
	my $self = $_[0];

	my ($stdout, $stderr);
	my $rs = execute('postconf smtpd_recipient_restrictions', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Extract postconf values
	chomp($stdout);
	(my $postconfValues = $stdout) =~ s/^.*=\s*(.*)/$1/;

	# Remove Postgrey policy server
	my @smtpRestrictions = grep {
		$_ !~ /^check_policy_service\s+inet:127.0.0.1:$self->{'config_prev'}->{'postgrey_port'}$/
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

=item _init()

 Initialize instance

 Return Plugin::Postgrey or die on failure

=cut

sub _init
{
	my $self = $_[0];

	if($self->{'action'} ~~ [ 'enable', 'disable', 'change', 'update' ]) {
		my $config = iMSCP::Database->factory->doQuery(
			'plugin_name',
			"SELECT plugin_name, plugin_config, plugin_config_prev FROM plugin WHERE plugin_name = 'Postgrey'"
		);
		unless(ref $config eq 'HASH') {
			die("Postgrey: $config");
		}

		$self->{'config'} = decode_json($config->{'Postgrey'}->{'plugin_config'});
		$self->{'config_prev'} = decode_json($config->{'Postgrey'}->{'plugin_config_prev'});
	}

	$self;
}

=item _checkRequirements()

 Check for requirements

 Return int 0 if all requirements are meet, other otherwise

=cut

sub _checkRequirements
{
	my ($stdout, $stderr);
	my $rs = execute(
		"LANG=C dpkg-query --show --showformat '\${Status}' postgrey | cut -d ' ' -f 3", \$stdout, \$stderr
	);
	debug($stdout) if $stdout;
	if($stdout ne 'installed') {
		error("The postgrey package is not installed on your system");
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
