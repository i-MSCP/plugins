=head1 NAME

 Plugin::PolicydSPF

=cut

# i-MSCP PolicydSPF plugin
# Copyright (C) 2016 Ninos Ego <me@ninosego.de>
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

package Plugin::PolicydSPF;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::TemplateParser;
use Servers::mta;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part of the PolicydSPF plugin.

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

	# Add policy-spf time limit
	$rs = execute('postconf -e policy-spf_time_limit=' . escapeShell($self->{'config'}->{'policyd_spf_time_limit'}), \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$rs = execute('postconf -h smtpd_recipient_restrictions', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Extract postconf values
	chomp($stdout);
	my $postconfValues = $stdout;
	my @smtpRestrictions = split ', ', $postconfValues;

	# Add policyd-spf policy server
	s/^permit$/check_policy_service $self->{'config'}->{'policyd_spf_service'}/ for @smtpRestrictions;
	push @smtpRestrictions, 'permit';

	my $postconf = 'smtpd_recipient_restrictions=' . escapeShell(join ', ', @smtpRestrictions);

	$rs = execute("postconf -e $postconf", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Add entries to master.cf
	$rs = $self->_postfixMasterCf('configure');
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
	my $self = shift;

	# Remove policy-spf time limit
	my $rs = execute('postconf -X policy-spf_time_limit', \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$rs = execute('postconf -h smtpd_recipient_restrictions', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Extract postconf values
	chomp($stdout);
	my $postconfValues = $stdout;

	# Remove policyd-spf policy server
	my @smtpRestrictions = grep {
		$_ !~ /^check_policy_service\s+$self->{'config_prev'}->{'policyd_spf_service'}$/
	} split ', ', $postconfValues;

	my $postconf = 'smtpd_recipient_restrictions=' . escapeShell(join ', ', @smtpRestrictions);

	$rs = execute("postconf -e $postconf", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Remove entries from master.cf
	$rs = $self->_postfixMasterCf('deconfigure');
	return $rs if $rs;

	Servers::mta->factory()->{'restart'} = 1;

	0;
}

=item _postfixMasterCf($action)

 Modify postfix master.cf config file

 Param string $action Action to perform (configure|deconfigure)
 Return int 0 on success, other on failure

=cut

sub _postfixMasterCf
{
	my ($self, $action) = @_;

	my $mta = Servers::mta->factory();

	my $file = iMSCP::File->new( filename => $mta->{'config'}->{'POSTFIX_MASTER_CONF_FILE'} );

	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read $file->{'filename'} file");
		return 1;
	}

	my $confSnippet = <<EOF;
# Plugin::PolicydSPF - Begin
policy-spf  unix  -       n       n       -       -       spawn
     user=nobody argv=/usr/sbin/postfix-policyd-spf-perl
# Plugin::PolicydSPF - Ending
EOF

	if($action eq 'configure') {
		if(getBloc("# Plugin::PolicydSPF - Begin\n", "# Plugin::PolicydSPF - Ending\n", $fileContent) ne '') {
			$fileContent = replaceBloc(
				"# Plugin::PolicydSPF - Begin\n", "# Plugin::PolicydSPF - Ending\n", $confSnippet, $fileContent
			);
		} else {
			$fileContent .= $confSnippet;
		}
	} else {
		$fileContent = replaceBloc(
			"# Plugin::PolicydSPF - Begin\n", "# Plugin::PolicydSPF - Ending\n", '', $fileContent
		);
	}

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
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
		"LANG=C dpkg-query --show --showformat '\${Status}' postfix-policyd-spf-perl | cut -d ' ' -f 3", \my $stdout, \my $stderr
	);
	debug($stdout) if $stdout;
	if($stdout ne 'installed') {
		error("The postfix-policyd-spf-perl package is not installed on your system");
		return 1;
	}

	0;
}

=back

=head1 AUTHOR

 Ninos Ego <me@ninosego.de>

=cut

1;
__END__
