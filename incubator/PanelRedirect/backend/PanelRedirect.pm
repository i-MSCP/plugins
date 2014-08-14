#!/usr/bin/perl

=head1 NAME

 Plugin::PanelRedirect

=cut

# i-MSCP PanelRedirect plugin
# Copyright (C) 2014 by Ninos Ego <me@ninosego.de>
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
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA

package Plugin::PanelRedirect;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Rights;
use iMSCP::Database;
use iMSCP::TemplateParser;
use iMSCP::Net;
use Servers::httpd;
use version;
use JSON;

use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP PanelRedirect plugin

=head1 PUBLIC METHODS

=over 4

=item install()

 Perform install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = $_[0];

	my $rs = $self->_createLogFolder();
	return $rs if $rs;

	$self->change();
}

=item update()

 Perform update tasks

 Return int 0 on success, other on failure

=cut

sub update
{
	$_[0]->change();
}

=item enable()

 Perform enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	$_[0]->change();
}

=item disable()

 Perform disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
	$_[0]->reset();
}

=item uninstall()

 Perform uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = $_[0];

	my $rs = $self->reset();
	return $rs if $rs;

	$self->_removeLogFolder();
}

=item change()

 Perform change tasks

 Return int 0 on success, other on failure

=cut

sub change
{
	my $self = $_[0];

	my $rs = $self->reset();
	return $rs if $rs;

	$self->_createConfig('00_PanelRedirect.conf');

	if($main::imscpConfig{'PANEL_SSL_ENABLED'} eq 'yes') {
		$self->_createConfig('00_PanelRedirect_ssl.conf');
	}

	$self->{'httpd'}->{'restart'} = 'yes';

	0;
}

=item reset()

 Perform reset tasks

 Return int 0 on success, other on failure

=cut

sub reset
{
	my $self = $_[0];

	$self->_removeConfig('00_PanelRedirect.conf');

	if($main::imscpConfig{'PANEL_SSL_ENABLED'} eq 'yes') {
		$self->_removeConfig('00_PanelRedirect_ssl.conf');
	}

	$self->{'httpd'}->{'restart'} = 'yes';

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize plugin

 Return Plugin::PanelRedirect

=cut

sub _init
{
	my $self = $_[0];

	$self->{'httpd'} = Servers::httpd->factory();

	if($self->{'action'} ~~ ['install', 'change', 'update', 'enable']) {
		# Loading plugin configuration
		my $rdata = iMSCP::Database->factory()->doQuery(
			'plugin_name', 'SELECT plugin_name, plugin_config FROM plugin WHERE plugin_name = ?', 'PanelRedirect'
		);
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}

		$self->{'config'} = decode_json($rdata->{'PanelRedirect'}->{'plugin_config'});
	}

	$self;
}

=item _createConfig($sdpFile)

 Create httpd configs

 Return int 0 if all requirements are meet, 1 otherwise

=cut

sub _createConfig($$)
{
	my ($self, $sdpFile) = @_;

	my $ipMngr = iMSCP::Net->getInstance();

	my $directoryRoot = "$main::imscpConfig{'GUI_ROOT_DIR'}/plugins/PanelRedirect";
	my $directoryTemplates = "$directoryRoot/templates/$self->{'config'}->{'type'}";
	my $directoryConfig = "$self->{'httpd'}->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}";

	my $data = {
		BASE_SERVER_IP => ($ipMngr->getAddrVersion($main::imscpConfig{'BASE_SERVER_IP'}) eq 'ipv4')
			? $main::imscpConfig{'BASE_SERVER_IP'} : "[$main::imscpConfig{'BASE_SERVER_IP'}]",
		BASE_SERVER_VHOST => $main::imscpConfig{'BASE_SERVER_VHOST'},
		BASE_SERVER_VHOST_PREFIX => $main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'},
		BASE_SERVER_VHOST_PORT => ($main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'} eq 'http://')
			? $main::imscpConfig{'BASE_SERVER_VHOST_HTTP_PORT'} : $main::imscpConfig{'BASE_SERVER_VHOST_HTTPS_PORT'},
		DEFAULT_ADMIN_ADDRESS => $main::imscpConfig{'DEFAULT_ADMIN_ADDRESS'},
		HTTPD_LOG_DIR => $self->{'httpd'}->{'config'}->{'HTTPD_LOG_DIR'},
		CONF_DIR => $main::imscpConfig{'CONF_DIR'}
	};
	$self->{'httpd'}->setData($data);

	my $rs = $self->{'httpd'}->buildConfFile(
		"$directoryTemplates/$sdpFile",
		$data,
		{ 'destination' => "$directoryConfig/$sdpFile" }
	);
	return $rs if $rs;

	$rs = $self->{'httpd'}->enableSites($sdpFile);
	return $rs if $rs;
}

=item _removeConfig($sdpFile)

 Remove httpd configs

 Return int 0 if all requirements are meet, 1 otherwise

=cut

sub _removeConfig($$)
{
	my ($self, $sdpFile) = @_;

	my $directoryConfig = "$self->{'httpd'}->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}";

	my $rs = $self->{'httpd'}->disableSites($sdpFile);
	return $rs if $rs;

	$rs = iMSCP::File->new('filename' => "$directoryConfig/$sdpFile")->delFile() if -f "$directoryConfig/$sdpFile";
	return $rs if $rs;
}

=item _createLogFolder()

 Create httpd log folder

 Return int 0 if all requirements are meet, 1 otherwise

=cut

sub _createLogFolder()
{
	my $self = $_[0];

	iMSCP::Dir->new(
		'dirname' => "$self->{'httpd'}->{'config'}->{'HTTPD_LOG_DIR'}/$main::imscpConfig{'BASE_SERVER_VHOST'}"
	)->make(
		{
			'user' => 'root',
			'group' => 'root',
			'mode' => '0750'
		}
	);
}

=item _removeLogFolder()

 Remove httpd log folder.

 Return int 0 if all requirements are meet, 1 otherwise

=cut

sub _removeLogFolder()
{
	my $self = $_[0];

	iMSCP::Dir->new(
		'dirname' => "$self->{'httpd'}->{'config'}->{'HTTPD_LOG_DIR'}/$main::imscpConfig{'BASE_SERVER_VHOST'}"
	)->remove();
}

=back

=head1 AUTHOR

 Ninos Ego <me@ninosego.de>

=cut

1;
