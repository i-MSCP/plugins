=head1 NAME

 Plugin::ServerDefaultPage

=cut

# i-MSCP ServerDefaultPage plugin
# Copyright (C) 2014-2016 by Ninos Ego <me@ninosego.de>
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
use iMSCP::Database;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Net;
use iMSCP::OpenSSL;
use iMSCP::Rights;
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
    my $self = shift;

    $self->_copyFolder();
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    iMSCP::Dir->new( dirname => "$main::imscpConfig{'USER_WEB_DIR'}/ServerDefaultPage" )->remove();
}

=item update($fromVersion, $toVersion)

 Process update tasks

 Param string $fromVersion Version from which the plugin is being updated
 Return int 0 on success, other on failure

=cut

sub update
{
    my ($self, $fromVersion) = @_;

    if (version->parse( $fromVersion ) < version->parse( '1.0.6' )) {
        my $rs = $self->_copyFolder();
        return $rs if $rs;
    }

    if (version->parse( $fromVersion ) < version->parse( '1.2.3' )) {
        # Rename server default page document root from `default' to `ServerDefaultPage'
        if (-d "$main::imscpConfig{'USER_WEB_DIR'}/default") {
            my $rs = iMSCP::Dir->new( dirname => "$main::imscpConfig{'USER_WEB_DIR'}/default" )->moveDir(
                "$main::imscpConfig{'USER_WEB_DIR'}/ServerDefaultPage"
            );
            return $rs if $rs;
        }
    }

    0;
}

=item enable()

 Process enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
    my $self = shift;

    my $ips = $self->_getIps();
    return 1 unless defined $ips;

    my $rs = $self->_createVhost( '00_ServerDefaultPage.conf', $ips->{'IPS'}, 80 );
    return $rs if $rs;

    if (@{$ips->{'SSL_IPS'}}) {
        if ($self->{'config'}->{'certificate'} eq '') {
            $rs = iMSCP::OpenSSL->new(
                certificate_chains_storage_dir => $main::imscpConfig{'CONF_DIR'},
                certificate_chain_name         => 'serverdefaultpage'
            )->createSelfSignedCertificate(
                {
                    common_name => $main::imscpConfig{'SERVER_HOSTNAME'},
                    email       => $main::imscpConfig{'DEFAULT_ADMIN_ADDRESS'}
                }
            );
            return $rs if $rs;
        }

        $rs = $self->_createVhost( '00_ServerDefaultPage_ssl.conf', $ips->{'SSL_IPS'}, 443 );
        return $rs if $rs;
    }

    $self->{'httpd'}->{'restart'} = 1;
    0;
}

=item disable()

 Process disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
    my $self = shift;

    my $rs = $self->_deleteVhost( '00_ServerDefaultPage.conf' );
    $rs ||= $self->_deleteVhost( '00_ServerDefaultPage_ssl.conf' );
    return $rs if $rs;

    if (-f "$main::imscpConfig{'CONF_DIR'}/serverdefaultpage.pem") {
        $rs = iMSCP::File->new( filename => "$main::imscpConfig{'CONF_DIR'}/serverdefaultpage.pem" )->delFile();
        return $rs if $rs;

    }

    $self->{'httpd'}->{'restart'} = 1;
    0;
}

=item run()

 Process plugin tasks

 Return int 0 on success, other on failure

=cut

sub run
{
    my $self = shift;

    $self->{'eventManager'}->register( 'afterAddIps', sub { $self->onAddIps( @_ ); } );
}

=back

=head1 EVENT LISTENERS

=over 4

=item onAddIps(\%ips)

 Event listener that is responsibles to update vhost files for the default page

 Param hashref \%ips IP addresses
 Return int 0 on success, other on failure

=cut

sub onAddIps
{
    my ($self, $ips) = @_;

    my $rs = $self->_deleteVhost( '00_ServerDefaultPage.conf' );
    $rs ||= $self->_deleteVhost( '00_ServerDefaultPage_ssl.conf' );
    $rs ||= $self->_createVhost( '00_ServerDefaultPage.conf', $ips->{'IPS'}, 80 );

    if (@{$ips->{'SSL_IPS'}}) {
        $rs ||= $self->_createVhost( '00_ServerDefaultPage_ssl.conf', $ips->{'SSL_IPS'}, 443 );
    }

    $self->{'httpd'}->{'restart'} = 1 unless $rs;
    $rs;
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
    my $self = shift;

    if ($self->{'action'} =~ /^(?:install|update|change|enable|disable|run)$/) {
        $self->{'httpd'} = Servers::httpd->factory();
    }

    $self;
}

=item _createVhost($vhostTplFile, $ips, $port)

 Create the given vhost using given directives

 Param string $vhostTplFile Vhost template file
 Param array_ref $ips IP addresses
 Param int $port Listen port
 Return int 0 on success, other on failure

=cut

sub _createVhost
{
    my ($self, $vhostTplFile, $ips, $port) = @_;

    my $net = iMSCP::Net->getInstance();
    $self->{'httpd'}->setData(
        {
            IPS_PORTS       => join( ' ', map { ( $net->getAddrVersion( $_ ) eq 'ipv4' ? $_ : "[$_]" ).":$port" } @{$ips}),
            BASE_SERVER_IP  => $net->getAddrVersion( $main::imscpConfig{'BASE_SERVER_IP'} ) eq 'ipv4'
                ? $main::imscpConfig{'BASE_SERVER_IP'} : "[$main::imscpConfig{'BASE_SERVER_IP'}]",
            APACHE_WWW_DIR  => $main::imscpConfig{'USER_WEB_DIR'},
            CERTIFICATE     => $self->{'config'}->{'certificate'} eq ''
                ? "$main::imscpConfig{'CONF_DIR'}/serverdefaultpage.pem" : $self->{'config'}->{'certificate'},
            AUTHZ_ALLOW_ALL =>
                version->parse( "$self->{'httpd'}->{'config'}->{'HTTPD_VERSION'}" ) >= version->parse( '2.4.0' )
                ? 'Require all granted' : 'Allow from all',
        }
    );
    $self->{'httpd'}->buildConfFile(
        "$main::imscpConfig{'PLUGINS_DIR'}/ServerDefaultPage/templates/$vhostTplFile",
        { },
        { destination => "$self->{'httpd'}->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/before/$vhostTplFile" }
    );
}

=item _deleteVhost($vhostFile)

 Delete the given vhost file

 Param string $vhostFile Vhost file
 Return int 0 on success, other on failure

=cut

sub _deleteVhost
{
    my ($self, $vhostFile) = @_;

    return 0 unless -f "$self->{'httpd'}->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/before/$vhostFile";

    iMSCP::File->new(
        filename => "$self->{'httpd'}->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/before/$vhostFile"
    )->delFile();
}

=item _copyFolder()

 Copy the ServerDefaultPage folder

 Return int 0 on success, other on failure

=cut

sub _copyFolder()
{
    my $self = shift;

    my $srcDir = "$main::imscpConfig{'PLUGINS_DIR'}/ServerDefaultPage/templates/default";
    my $targetDir = "$main::imscpConfig{'USER_WEB_DIR'}/ServerDefaultPage";

    my $rs = iMSCP::Dir->new( dirname => $targetDir )->make(
        {
            user  => $self->{'httpd'}->{'config'}->{'HTTPD_USER'},
            group => $self->{'httpd'}->{'config'}->{'HTTPD_GROUP'},
            mode  => 0750
        }
    );
    $rs ||= iMSCP::Dir->new( dirname => "$srcDir" )->rcopy( $targetDir );
    $rs ||= setRights(
        $targetDir,
        {
            user      => $self->{'httpd'}->{'config'}->{'HTTPD_USER'},
            group     => $self->{'httpd'}->{'config'}->{'HTTPD_GROUP'},
            dirmode   => '0750',
            filemode  => '0640',
            recursive => 1
        }
    );
}

=item _getIps()

 Get all IP addresses

 Return hashref on success, undef on failure

=cut

sub _getIps()
{
    my $db = iMSCP::Database->factory();

    my $ips = { };

    my $rdata = $db->doQuery(
        'ip_number',
        "
            SELECT domain_ip_id AS ip_id, ip_number
            FROM domain INNER JOIN server_ips ON (domain.domain_ip_id = server_ips.ip_id)
            WHERE domain_status != 'todelete'
            UNION
            SELECT alias_ip_id AS ip_id, ip_number FROM domain_aliasses
            INNER JOIN server_ips ON (domain_aliasses.alias_ip_id = server_ips.ip_id)
            WHERE alias_status NOT IN ('todelete', 'ordered')
        "
    );
    unless (ref $rdata eq 'HASH') {
        error( $rdata );
        undef;
    }

    # The Base server IP must always be here because even if not used by any domain, the panel use it
    $rdata->{$main::imscpConfig{'BASE_SERVER_IP'}} = undef;

    @{$ips->{'IPS'}} = keys %{$rdata};

    $rdata = $db->doQuery(
        'ip_number',
        "
            SELECT ip_number FROM ssl_certs INNER JOIN domain ON (ssl_certs.domain_id = domain.domain_id)
            INNER JOIN server_ips ON (domain.domain_ip_id = server_ips.ip_id) WHERE ssl_certs.domain_type = 'dmn'
            UNION
            SELECT ip_number FROM ssl_certs
            INNER JOIN domain_aliasses ON (ssl_certs.domain_id = domain_aliasses.alias_id)
            INNER JOIN server_ips ON (domain_aliasses.alias_ip_id = server_ips.ip_id)
            WHERE ssl_certs.domain_type = 'als'
            UNION
            SELECT ip_number FROM ssl_certs
            INNER JOIN subdomain_alias ON (ssl_certs.domain_id = subdomain_alias.subdomain_alias_id)
            INNER JOIN domain_aliasses ON (subdomain_alias.alias_id = domain_aliasses.alias_id)
            INNER JOIN server_ips ON (domain_aliasses.alias_ip_id = server_ips.ip_id)
            WHERE ssl_certs.domain_type = 'alssub'
            UNION
            SELECT ip_number FROM ssl_certs INNER JOIN subdomain ON (ssl_certs.domain_id = subdomain.subdomain_id)
            INNER JOIN domain ON (subdomain.domain_id = domain.domain_id)
            INNER JOIN server_ips ON (domain.domain_ip_id = server_ips.ip_id)
            WHERE ssl_certs.domain_type = 'sub'
        "
    );
    unless (ref $rdata eq 'HASH') {
        error( $rdata );
        undef;
    }

    if ($main::imscpConfig{'PANEL_SSL_ENABLED'} eq 'yes') {
        # The Base server IP must always be here because even if not used by any domain, the panel use it
        $rdata->{$main::imscpConfig{'BASE_SERVER_IP'}} = undef;
    }

    @{$ips->{'SSL_IPS'}} = keys %{$rdata};
    $ips;
}

=back

=head1 AUTHOR

 Ninos Ego <me@ninosego.de>

=cut

1;
__END__
