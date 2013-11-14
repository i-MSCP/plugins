#!/usr/bin/perl

=head1 NAME

 Plugin::PostfixSmarthost - i-MSCP PostfixSmarthost plugin (backend)

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2013 by internet Multi Server Control Panel
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
#
# @category    i-MSCP
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Plugin::PostfixSmarthost;

use strict;
use warnings;
use iMSCP::File;
use iMSCP::Execute;
use JSON;

=head1 DESCRIPTION

 This package provides backend part for the i-MSCP PostfixSmarthost plugin.

=head1 PUBLIC METHODS

=over 4

=item install()

 Install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	# Get plugin configuration parameters
	my $database = iMSCP::Database->factory();

	my $rdata = $database->doQuery(
		'plugin_name', 'SELECT `plugin_name`, `plugin_config` FROM `plugin` WHERE `plugin_name` = ?', 'PostfixSmarthost'
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	} elsif(! %{$rdata}) {
		error('Unable to retrieve PostfixSmarthost plugin configuration parameters from database');
		return 1;
	}

	my $config = decode_json($rdata->{'PostfixSmarthost'}->{'plugin_config'});

	unless(
		$config->{'relayHost'} eq '' || $config->{'relayPort'} eq '' || $config->{'saslAuthUser'} eq '' ||
		$config->{'saslAuthPasswd'} eq '', $config->{'saslPasswdMapsFile'} eq ''
	) {

		# Create password maps file
		my $rs = $self->__createSaslPasswdMaps(
			$config->{'relayhost'}, $config->{'relayPort'}, $config->{'saslAuthUser'}, $config->{'saslAuthPasswd'},
			$config->{'saslPasswdMapsFile'},
		);
		return $rs if $rs;
	
		# Configure Postfix as smarthost
		$self->_configureSmarthost($config->{'relayHost'}, $config->{'relayPort'},  $config->{'saslPasswdMapsFile'});
	} else {
		warning('Plugin unconfigured - skipping installation');
	}

	0;
}

=item update()

 Update tasks

 Return int 0 on success, other on failure

=cut

sub update
{
	my $self = shift;

	$self->install();
}

=item uninstall()

 Uninstallation tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	# TODO

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _createSaslPasswdMaps($relayHost, $relayPort, $saslAuthPasswd, $relayPasswdMapsFile)

 Create SMTP SASL password maps

 Return int 0 on success, other on failure

=cut

sub _createSaslPasswdMaps($$$$$$)
{
	my $self = shift;
	my $relayHost = shift;
	my $relayPort = shift;
	my $saslAuthUser = shift;
	my $saslAuthPasswd = shift;
	my $relayPasswdMapsFile = shift;

	my $saslPasswdMapsFile = iMSCP::File->new('filename' => $relayPasswdMapsFile);

	my $rs = $saslPasswdMapsFile->set("$relayHost:$relayPort\t$saslAuthUser:$saslAuthPasswd");
	return $rs if $rs;

	$rs = $saslPasswdFile->save();
	return $rs if $rs;

	$rs = $saslPasswdFile->mode(0600);
	return $rs if $rs;

	my ($stdout, $stderr);
	$rs = execute("/usr/bin/postmap -v hash:$relayPasswdMapsFile", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs;
}

=item configureSmartHost($relayHost, $relayPort, $relayPasswdMapsFile)

 Add relayhost and SMTP SASL parameters in Postfix main.cf

 Return int 0

=cut

sub _configureSmarthost($$$$)
{
	my $self = shift;
	my $relayHost = shift;
	my $relayPort = shift;
	my $relayPasswdMapsFile = shift;

	my @cmd = (
		escapeShell("relayhost=$relayHost:$relayPort"),
		'smtp_sasl_auth_enable=yes',
		escapeShell("smtp_sasl_password_maps=hash:$relayPasswdMapsFile"),
		'smtp_sasl_security_options=noanonymous'
	)

	my ($stdout, $stderr);
	$rs = execute("/usr/bin/postconf -e @cmd", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
