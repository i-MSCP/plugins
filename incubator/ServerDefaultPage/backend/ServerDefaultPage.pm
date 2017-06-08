=head1 NAME

 Plugin::ServerDefaultPage

=cut

# i-MSCP ServerDefaultPage plugin
# Copyright (C) 2014-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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
use autouse 'iMSCP::Rights' => qw/ setRights /;
use autouse 'iMSCP::Debug' => qw/ error /;
use autouse 'iMSCP::TemplateParser' => qw / replaceBloc /;
use Class::Autouse qw/ :nostat
    iMSCP::Database iMSCP::Dir iMSCP::File iMSCP::Net iMSCP::OpenSSL Servers::cron Servers::httpd /;
use version;
use parent 'Common::SingletonClass';

# self-ref for use in event listener
my $instance;

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP ServerDefaultPage plugin

=head1 PUBLIC METHODS

=over 4

=item install( )

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
    my ($self) = @_;

    $self->_copyFolder( );
}

=item uninstall( )

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    local $@;
    eval { iMSCP::Dir->new( dirname => "$main::imscpConfig{'USER_WEB_DIR'}/ServerDefaultPage" )->remove( ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    if (-f "$main::imscpConfig{'CONF_DIR'}/serverdefaultpage.pem") {
        return iMSCP::File->new( filename => "$main::imscpConfig{'CONF_DIR'}/serverdefaultpage.pem" )->delFile( );
    }

    0;
}

=item update( $fromVersion )

 Process update tasks

 Param string $fromVersion Version from which the plugin is being updated
 Return int 0 on success, other on failure

=cut

sub update
{
    my ($self, $fromVersion) = @_;

    $fromVersion = version->parse( $fromVersion );

    if ($fromVersion < version->parse( '1.0.6' )) {
        my $rs = $self->_copyFolder( );
        return $rs if $rs;
    }

    return 0 unless $fromVersion < version->parse( '1.2.3' ) && -d "$main::imscpConfig{'USER_WEB_DIR'}/default";

    local $@;
    eval {
        # Rename server default page document root from `default' to `ServerDefaultPage'
        iMSCP::Dir->new( dirname => "$main::imscpConfig{'USER_WEB_DIR'}/default" )->moveDir(
            "$main::imscpConfig{'USER_WEB_DIR'}/ServerDefaultPage"
        );
    };
    if ($@) {
        error( $@ );
        return 1;
    }

    0;
}

=item enable( )

 Process enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
    my ($self) = @_;

    if ($self->{'config'}->{'certificate'} eq '') {
        my $rs = $self->createSelfSignedCertificate( );
        $rs ||= Servers::cron->factory( )->addTask(
            {
                TASKID  => 'Plugin::ServerDefaultPage',
                MINUTE  => '@monthly',
                COMMAND => 'nice -n 10 ionice -c2 -n5 '
                    ."perl $main::imscpConfig{'PLUGINS_DIR'}/ServerDefaultPage/cronjob.pl >/dev/null 2>&1"
            }
        );
        $rs ||= _updateVhost( );
        return $rs;
    }

    if (-f "$main::imscpConfig{'CONF_DIR'}/serverdefaultpage.pem") {
        my $rs = iMSCP::File->new( filename => "$main::imscpConfig{'CONF_DIR'}/serverdefaultpage.pem" )->delFile( );
        return $rs if $rs;
    }

    my $rs = Servers::cron->factory( )->deleteTask( { TASKID => 'Plugin::ServerDefaultPage' } );
    $rs ||= _updateVhost( );
}

=item disable( )

 Process disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
    my ($self) = @_;

    my $rs = $self->_deleteVhost( '00_ServerDefaultPage.conf' );
    $rs ||= $self->_deleteVhost( '00_ServerDefaultPage_ssl.conf' );
    $rs ||= Servers::cron->factory( )->deleteTask( { TASKID => 'Plugin::ServerDefaultPage' } );
    Servers::httpd->factory( )->{'restart'} = 1 unless $rs;
    $rs;
}

=item run( )

 Process plugin tasks

 Return int 0 on success, other on failure

=cut

sub run
{
    my ($self) = @_;

    $self->{'FORCE_RETVAL'} = 1;

    # Register an event listener that is responsible to update IP addresses
    # into ServerDefaultPage vhost files
    $self->{'eventManager'}->register(
        'beforeAddIpAddr', sub { $self->{'eventManager'}->register( 'afterAddIpAddr', \&_updateVhost ); }
    );
}

=item createSelfSignedCertificate( )

 Create self-signed SSL certificate for server default page SSL vhost

 Return int 0 on success, other on failure

=cut

sub createSelfSignedCertificate
{
    my ($self) = @_;

    return 0 unless $self->{'config'}->{'certificate'} eq '';

    my $openSSL = iMSCP::OpenSSL->new(
        certificate_chains_storage_dir => $main::imscpConfig{'CONF_DIR'},
        certificate_chain_name         => 'serverdefaultpage'
    );

    if (-f "$main::imscpConfig{'CONF_DIR'}/serverdefaultpage.pem") {
        my $expireDate = $openSSL->getCertificateExpiryTime( "$main::imscpConfig{'CONF_DIR'}/serverdefaultpage.pem" );
        return 0 if defined $expireDate && $expireDate > (time()+2419200 );
    }

    $openSSL->createSelfSignedCertificate(
        {
            common_name => $main::imscpConfig{'SERVER_HOSTNAME'},
            email       => $main::imscpConfig{'DEFAULT_ADMIN_ADDRESS'}
        }
    );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize instance

 Return Plugin::ServerDefaultPage

=cut

sub _init
{
    my ($self) = @_;

    # Sets self-ref for use in class method
    $instance = $self;
    $self;
}

=item _createVhost( $vhostTplFile, \@ipAddresses, $port )

 Create the given vhost using given directives

 Param string $vhostTplFile Vhost template file
 Param arrayref \@ipAddresses IP addresses
 Param int $port Listen port
 Return int 0 on success, other on failure

=cut

sub _createVhost
{
    my ($self, $vhostTplFile, $ipAddresses, $port) = @_;

    my $httpd = Servers::httpd->factory( );
    my $net = iMSCP::Net->getInstance( );

    $httpd->setData(
        {
            IPS_PORTS      => join(
                ' ', map { ( $net->getAddrVersion( $_ ) eq 'ipv4' ? $_ : "[$_]" ).":$port" } @{$ipAddresses}
            ),
            BASE_SERVER_IP => $net->getAddrVersion($main::imscpConfig{'BASE_SERVER_IP'} ) eq 'ipv4'
                ? $main::imscpConfig{'BASE_SERVER_IP'}
                : "[$main::imscpConfig{'BASE_SERVER_IP'}]",
            APACHE_WWW_DIR => $main::imscpConfig{'USER_WEB_DIR'},
            CERTIFICATE    => ($self->{'config'}->{'certificate'} eq '')
                ? "$main::imscpConfig{'CONF_DIR'}/serverdefaultpage.pem"
                : $self->{'config'}->{'certificate'},
        }
    );

    $httpd->buildConfFile(
        "$main::imscpConfig{'PLUGINS_DIR'}/ServerDefaultPage/templates/$vhostTplFile",
        { },
        { destination => "$httpd->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/before/$vhostTplFile" }
    );
}

=item _deleteVhost( $vhostFilename )

 Delete the given vhost filename

 Param string $vhostFile Vhost filename
 Return int 0 on success, other on failure

=cut

sub _deleteVhost
{
    my (undef, $vhostFilename) = @_;

    my $httpd = Servers::httpd->factory( );

    return 0 unless -f "$httpd->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/before/$vhostFilename";

    iMSCP::File->new( filename => "$httpd->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/before/$vhostFilename" )->delFile( );
}

=item _copyFolder( )

 Copy the ServerDefaultPage folder

 Return int 0 on success, other on failure

=cut

sub _copyFolder( )
{
    my $srcDir = "$main::imscpConfig{'PLUGINS_DIR'}/ServerDefaultPage/templates/default";
    my $targetDir = "$main::imscpConfig{'USER_WEB_DIR'}/ServerDefaultPage";
    my $httpd = Servers::httpd->factory( );

    my $rs = iMSCP::Dir->new( dirname => $targetDir )->make(
        {
            user  => $httpd->{'config'}->{'HTTPD_USER'},
            group => $httpd->{'config'}->{'HTTPD_GROUP'},
            mode  => 0750
        }
    );
    $rs ||= iMSCP::Dir->new( dirname => $srcDir )->rcopy( $targetDir );
    $rs ||= setRights(
        $targetDir,
        {
            user      => $httpd->{'config'}->{'HTTPD_USER'},
            group     => $httpd->{'config'}->{'HTTPD_GROUP'},
            dirmode   => '0750',
            filemode  => '0640',
            recursive => 1
        }
    );
}

=item _getIps( )

 Get all IP addresses

 Return list of IP addresses on success, die on failure

=cut

sub _getIps( )
{
    my $ipAddresses = eval {
        my $dbh = iMSCP::Database->factory( )->getRawDb( );
        local $dbh->{'RaiseError'} = 1;

        my $sth = $dbh->prepare( "SELECT ip_number FROM server_ips WHERE ip_status IN ('toadd', 'tochange', 'ok')" );
        $sth->execute();
        $sth->fetchall_arrayref( { ip_number => 1 });
    };
    !$@ or die ( sprintf( "Couldn't get list of IP addresses: %s", $@ ) );

    my $net = iMSCP::Net->getInstance( );
    map { $net->normalizeAddr( $_->{'ip_number'} ) } @{$ipAddresses};
}

=item _updateVhost( )

 Update vhost files for the default page

 Return int 0 on success, other on failure

=cut

sub _updateVhost
{
    return unless $instance;

    # We self-unregister because we want act once
    # This will only have effect for i-MSCP version >= 1.4.4
    # For versions prior 1.4.4, the listener will be invoked on each IP addition
    my $rs = $instance->{'eventManager'}->unregister( \&_updateVhost, 'afterAddIpAddr' );
    return $rs if $rs;

    local $@;
    my @ipAddresses = eval { $instance->_getIps( ); };
    if ($@) {
        error($@);
        return 1;
    }

    $rs = $instance->_createVhost( '00_ServerDefaultPage.conf', \@ipAddresses, 80 );
    $rs ||= $instance->_createVhost( '00_ServerDefaultPage_ssl.conf', \@ipAddresses, 443 );

    Servers::httpd->factory( )->{'restart'} = 1 unless $rs;

    # Avoid re-creating vhosts files on each IP address addition (BC for i-MSCP version < 1.4.4)
    undef $instance;
    $rs;
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>
 Ninos Ego <me@ninosego.de>

=cut

1;
__END__
