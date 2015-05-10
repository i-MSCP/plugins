=head1 NAME

 Plugin::ServerDefaultPage

=cut

# i-MSCP ServerDefaultPage plugin
# Copyright (C) 2014-2015 by Ninos Ego <me@ninosego.de>
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

package Plugin::ServerDefaultPage;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Rights;
use iMSCP::Database;
use iMSCP::Net;
use Servers::httpd;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP ServerDefaultPage plugin

=head1 PUBLIC METHODS

=over 4

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	$_[0]->_copyFolder();
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	$_[0]->_removeFolder();
}

=item update($fromVersion, $toVersion)

 Process update tasks

 Param string $fromVersion
 Param string $toVersion
 Return int 0 on success, other on failure

=cut

sub update
{
	my ($self, $fromVersion, $toVersion) = @_;

	if(version->parse($fromVersion) < version->parse("1.0.6")) {
		$self->_copyFolder();
	}
}

=item enable()

 Process enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = $_[0];

	my $rs = $self->_getIps();
	return $rs if $rs;

	my $ipMngr = iMSCP::Net->getInstance();

	my $directives = [ ];
	for( @{$self->{'ipaddrs'}} ) {
		push @{$directives}, ($ipMngr->getAddrVersion($_) eq 'ipv4') ? "$_:80" : "[$_]:80";
	}

	$rs = $self->_createConfig('00_ServerDefaultPage.conf', $directives);
	return $rs if $rs;

	$directives = [ ];

	if($main::imscpConfig{'PANEL_SSL_ENABLED'} eq 'yes') {
		for( @{$self->{'ssl_ipaddrs'}} ) {
			push @{$directives}, ($ipMngr->getAddrVersion($_) eq 'ipv4') ? "$_:443" : "[$_]:443";
		}

		$rs = $self->_createConfig('00_ServerDefaultPage_ssl.conf', $directives);
		return $rs if $rs;
	} else {
		$rs = $self->_removeConfig('00_ServerDefaultPage_ssl.conf');
		return $rs if $rs;
	}

	$self->{'httpd'}->{'restart'} = 'yes';

	0;
}

=item disable()

 Process disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
	my $self = $_[0];

	for('00_ServerDefaultPage.conf', '00_ServerDefaultPage_ssl.conf') {
		my $rs = $self->_removeConfig($_);
		return $rs if $rs;
	}

	$self->{'httpd'}->{'restart'} = 'yes';

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize plugin

 Return Plugin::ServerDefaultPage or die on failure

=cut

sub _init
{
	my $self = $_[0];

	if($self->{'action'} ~~ [ 'install', 'change', 'update', 'enable', 'disable' ]) {
		$self->{'httpd'} = Servers::httpd->factory();
	}

	$self;
}

=item _createConfig($vhostTplFile, $directives)

 Create httpd configs

 Param string $vhostTplFile Vhost template file
 Param array $directives Vhost directives
 Return int 0 on success, other on failure

=cut

sub _createConfig
{
	my ($self, $vhostTplFile, $directives) = @_;

	my $tplRootDir = "$main::imscpConfig{'PLUGINS_DIR'}/ServerDefaultPage/templates";

	my $ipMngr = iMSCP::Net->getInstance();

	$self->{'httpd'}->setData(
		{
			'IPS_PORTS' => "@{$directives}",
			'BASE_SERVER_IP' => (
				$ipMngr->getAddrVersion($main::imscpConfig{'BASE_SERVER_IP'}) eq 'ipv4'
			) ? $main::imscpConfig{'BASE_SERVER_IP'} : "[$main::imscpConfig{'BASE_SERVER_IP'}]",
			'APACHE_WWW_DIR' => $main::imscpConfig{'USER_WEB_DIR'},
			'CERTIFICATE' => (
				$self->{'config'}->{'certificate'} eq ''
			) ? "$main::imscpConfig{'CONF_DIR'}/$main::imscpConfig{'BASE_SERVER_VHOST'}.pem" : $self->{'config'}->{'certificate'},
			'AUTHZ_ALLOW_ALL' => (
				version->parse("$self->{'httpd'}->{'config'}->{'HTTPD_VERSION'}") >= version->parse('2.4.0')
			) ? 'Require all granted' : 'Allow from all',
		}
	);

	my $rs = $self->{'httpd'}->buildConfFile("$tplRootDir/$vhostTplFile");
	return $rs if $rs;

	$self->{'httpd'}->installConfFile($vhostTplFile, {
		destination => "$self->{'httpd'}->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/before"
	});
}

=item _removeConfig($vhostFile)

 Remove httpd configs

 Param string $vhostFile Vhost file
 Return int 0 on success, other on failure

=cut

sub _removeConfig
{
	my ($self, $vhostFile) = @_;

	for(
		"$self->{'httpd'}->{'apacheWrkDir'}/$vhostFile",
		"$self->{'httpd'}->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/before/$vhostFile"
	) {
		if(-f $_) {
			my $rs = iMSCP::File->new('filename' => $_)->delFile();
			return $rs if $rs;
		}
	}

	0;
}

=item _copyFolder()

 Copy the ServerDefaultPage folder

 Return int 0 on success, other on failure

=cut

sub _copyFolder()
{
	my $self = $_[0];

	my $pluginDir = "$main::imscpConfig{'PLUGINS_DIR'}/ServerDefaultPage";
	my $tplDir = "$pluginDir/templates";
	my $defaultDir = "$main::imscpConfig{'USER_WEB_DIR'}/default";

	my $rs = iMSCP::Dir->new(
		dirname => $defaultDir
	)->make(
		{
			'user' => $self->{'httpd'}->{'config'}->{'HTTPD_USER'},
			'group' => $self->{'httpd'}->{'config'}->{'HTTPD_GROUP'},
			'mode' => '0750'
		}
	);
	return $rs if $rs;

	$rs = iMSCP::Dir->new( dirname => "$tplDir/default" )->rcopy($defaultDir);
	return $rs if $rs;

	setRights(
		$defaultDir,
		{
			'user' => $self->{'httpd'}->{'config'}->{'HTTPD_USER'},
			'group' => $self->{'httpd'}->{'config'}->{'HTTPD_GROUP'},
			'dirmode' => '0750',
			'filemode' => '0640',
			'recursive' => 1
		}
	);
}

=item _removeFolder()

 Remove the ServerDefaultPage folder

 Return int 0 on success, other on failure

=cut

sub _removeFolder()
{
	iMSCP::Dir->new( dirname => "$main::imscpConfig{'USER_WEB_DIR'}/default" )->remove();
}

=item _getIps()

 Get all the used httpd Ips

 Return int 0 on success, other on failure

=cut

sub _getIps()
{
	my $self = $_[0];

	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery(
		'ip_number',
		"
			SELECT
				domain_ip_id AS ip_id, ip_number
			FROM
				domain
			INNER JOIN
				server_ips ON (domain.domain_ip_id = server_ips.ip_id)
			WHERE
				domain_status != 'todelete'
			UNION
			SELECT
				alias_ip_id AS ip_id, ip_number
			FROM
				domain_aliasses
			INNER JOIN
				server_ips ON (domain_aliasses.alias_ip_id = server_ips.ip_id)
			WHERE
				alias_status NOT IN ('todelete', 'ordered')
		"
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	# The Base server IP must always be here because even if not used by any domain, the panel use it
	$rdata->{$main::imscpConfig{'BASE_SERVER_IP'}} = undef;

	@{$self->{'ipaddrs'}} = keys %{$rdata};

	$rdata = $db->doQuery(
		'ip_number',
		"
			SELECT
				ip_number
			FROM
				ssl_certs
			INNER JOIN
				domain ON (ssl_certs.domain_id = domain.domain_id)
			INNER JOIN
				server_ips ON (domain.domain_ip_id = server_ips.ip_id)
			WHERE
				ssl_certs.domain_type = 'dmn'

			UNION

			SELECT
				ip_number
			FROM
				ssl_certs
			INNER JOIN
				domain_aliasses ON (ssl_certs.domain_id = domain_aliasses.alias_id)
			INNER JOIN
				server_ips ON (domain_aliasses.alias_ip_id = server_ips.ip_id)
			WHERE
				ssl_certs.domain_type = 'als'

			UNION

			SELECT
				ip_number
			FROM
				ssl_certs
			INNER JOIN
				subdomain_alias ON (ssl_certs.domain_id = subdomain_alias.subdomain_alias_id)
			INNER JOIN
				domain_aliasses ON (subdomain_alias.alias_id = domain_aliasses.alias_id)
			INNER JOIN
				server_ips ON (domain_aliasses.alias_ip_id = server_ips.ip_id)
			WHERE
				ssl_certs.domain_type = 'alssub'

			UNION

			SELECT
				ip_number
			FROM
				ssl_certs
			INNER JOIN
				subdomain ON (ssl_certs.domain_id = subdomain.subdomain_id)
			INNER JOIN
				domain ON (subdomain.domain_id = domain.domain_id)
			INNER JOIN
				server_ips ON (domain.domain_ip_id = server_ips.ip_id)
			WHERE
				ssl_certs.domain_type = 'sub'
		"
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	if($main::imscpConfig{'PANEL_SSL_ENABLED'} eq 'yes') {
		# The Base server IP must always be here because even if not used by any domain, the panel use it
		$rdata->{$main::imscpConfig{'BASE_SERVER_IP'}} = undef;
	}

	@{$self->{'ssl_ipaddrs'}} = keys %{$rdata};

	0;
}

=back

=head1 AUTHOR

 Ninos Ego <me@ninosego.de>

=cut

1;
__END__
