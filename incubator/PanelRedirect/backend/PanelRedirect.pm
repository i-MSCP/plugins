=head1 NAME

 Plugin::PanelRedirect

=cut

# i-MSCP PanelRedirect plugin
# Copyright (C) 2016-2017 by Laurent Declercq <l.declercq@nuxwin.com>
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

package Plugin::PanelRedirect;

use strict;
use warnings;
use autouse 'iMSCP::TemplateParser' => qw / replaceBloc /;
use Class::Autouse qw/ :nostat iMSCP::Database iMSCP::File iMSCP::Dir iMSCP::Net Servers::httpd  /;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP PanelRedirect plugin

=head1 PUBLIC METHODS

=over 4

=item enable( )

 Process enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
    my ($self) = @_;

    my $httpd = Servers::httpd->factory( );

    if (-f "$httpd->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/$main::imscpConfig{'BASE_SERVER_VHOST'}.conf") {
        return $self->disable( );
    }

    my $rs = $self->_createLogFolder( );
    $rs ||= $self->_createConfig( 'PanelRedirect.conf' );
    return $rs if $rs;

    if ($main::imscpConfig{'PANEL_SSL_ENABLED'} eq 'yes') {
        $rs = $self->_createConfig( 'PanelRedirect_ssl.conf' );
        return $rs if $rs;
    } else {
        $rs = $self->_removeConfig( 'PanelRedirect_ssl.conf' );
        return $rs if $rs;
    }

    $httpd->{'restart'} = 1;
    0;
}

=item disable( )

 Process disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
    my ($self) = @_;

    for('PanelRedirect.conf', 'PanelRedirect_ssl.conf') {
        my $rs = $self->_removeConfig( $_ );
        return $rs if $rs;
    }

    Servers::httpd->factory( )->{'restart'} = 1;
    0;
}

=item run( )

 Process list tasks

 Return int 0 on success, other on failure

=cut

sub run
{
    my ($self) = @_;

    # Event listener responsible to remove vhost files added by this plugin
    # when BASE_SERVER_VHOST is being used as customer domain
    my $rs = $self->{'eventManager'}->register(
        [ 'beforeHttpdAddDmn', 'beforeHttpdAddSub' ],
        sub {
            my ($data) = @_;

            return 0 if $data->{'DOMAIN_NAME'} ne $main::imscpConfig{'BASE_SERVER_VHOST'};
            $self->disable( );
        }
    );

    # Event listener that add vhost files as provided by this plugin when
    # BASE_SERVER_VHOST customer domain is being removed
    $rs ||= $self->{'eventManager'}->register(
        [ 'afterHttpdDelDmn' ],
        sub {
            my ($data) = @_;

            return 0 if $data->{'DOMAIN_NAME'} ne $main::imscpConfig{'BASE_SERVER_VHOST'};
            $self->enable( );
        }
    );
}

=back

=head1 PRIVATE METHODS

=over 4

=item _createConfig( $vhostTplFile )

 Create httpd configs

 Param string $vhostTplFile Vhost template file
 Return int 0 on success, other on failure

=cut

sub _createConfig
{
    my ($self, $vhostTplFile) = @_;

    my $httpd = Servers::httpd->factory( );

    $httpd->setData(
        {
            BASE_SERVER_IP               => (
                    iMSCP::Net->getInstance( )->getAddrVersion( $main::imscpConfig{'BASE_SERVER_IP'} ) eq 'ipv4'
                ) ? $main::imscpConfig{'BASE_SERVER_IP'}
                  : "[$main::imscpConfig{'BASE_SERVER_IP'}]",
            BASE_SERVER_VHOST            => $main::imscpConfig{'BASE_SERVER_VHOST'},
            BASE_SERVER_VHOST_PREFIX     => $main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'},
            BASE_SERVER_VHOST_PORT       => ($main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'} eq 'http://')
                ? $main::imscpConfig{'BASE_SERVER_VHOST_HTTP_PORT'}
                : $main::imscpConfig{'BASE_SERVER_VHOST_HTTPS_PORT'},
            BASE_SERVER_VHOST_HTTP_PORT  => $main::imscpConfig{'BASE_SERVER_VHOST_HTTP_PORT'},
            BASE_SERVER_VHOST_HTTPS_PORT => $main::imscpConfig{'BASE_SERVER_VHOST_HTTPS_PORT'},
            DEFAULT_ADMIN_ADDRESS        => $main::imscpConfig{'DEFAULT_ADMIN_ADDRESS'},
            HTTPD_LOG_DIR                => $httpd->{'config'}->{'HTTPD_LOG_DIR'},
            CONF_DIR                     => $main::imscpConfig{'CONF_DIR'}
        }
    );

    my $rs = $self->{'eventManager'}->register(
        'afterHttpdBuildConf',
        sub {
            my ($cfgTpl, $tplName) = @_;

            return 0 unless $tplName eq 'PanelRedirect.conf' || $tplName eq 'PanelRedirect_ssl.conf';

            ${$cfgTpl} = replaceBloc(
                "# SECTION VHOST_PREFIX != $main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'} BEGIN.\n",
                "# SECTION VHOST_PREFIX != $main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'} END.\n",
                '',
                ${$cfgTpl}
            );

            0;
        }
    );
    $rs ||= $httpd->buildConfFile(
        "$main::imscpConfig{'PLUGINS_DIR'}/PanelRedirect/templates/$self->{'config'}->{'type'}/$vhostTplFile",
        { },
        { destination => "$httpd->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/before/$vhostTplFile" }
    );
}

=item _removeConfig( $vhostFile )

 Remove httpd configs

 Param string $vhostFile Vhost file
 Return int 0 on success, other on failure

=cut

sub _removeConfig
{
    my (undef, $vhostFile) = @_;

    my $httpd = Servers::httpd->factory( );

    return 0 unless -f "$httpd->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/before/$vhostFile";

    iMSCP::File->new( filename => "$httpd->{'config'}->{'HTTPD_CUSTOM_SITES_DIR'}/before/$vhostFile" )->delFile( );
}

=item _createLogFolder( )

 Create httpd log folder

 Return int 0 on success, other on failure

=cut

sub _createLogFolder( )
{
    my $httpd = Servers::httpd->factory( );

    iMSCP::Dir->new(
        dirname => "$httpd->{'config'}->{'HTTPD_LOG_DIR'}/$main::imscpConfig{'BASE_SERVER_VHOST'}"
    )->make(
        {
            user  => $main::imscpConfig{'ROOT_USER'},
            group => $main::imscpConfig{'ROOT_GROUP'},
            mode  => 0750
        }
    );
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>
 Ninos Ego <me@ninosego.de>

=cut

1;
__END__
