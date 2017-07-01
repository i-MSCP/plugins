=head1 NAME

 Plugin::Postgrey

=cut

# i-MSCP Postgrey plugin
# Copyright (C) 2015-2017 Laurent Declercq <l.declercq@nuxwin.com>
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
use autouse 'iMSCP::Debug' => qw/ debug error /;
use autouse 'iMSCP::Execute' => qw/ execute /;
use Class::Autouse qw/ :nostat iMSCP::Service Servers::mta /;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part of the Postgrey plugin.

=head1 PUBLIC METHODS

=over 4

=item enable( )

 Perform enable tasks

 Return 0 on success, other on failure

=cut

sub enable
{
    my ($self) = @_;

    unless (defined $main::execmode && $main::execmode eq 'setup'
        || !grep( $_ eq $self->{'action'}, 'install', 'update' )
    ) {
        my $rs = $self->_installDistributionPackages( );
        return $rs if $rs;
    }

    my $rs = Servers::mta->factory( )->postconf(
        (
            smtpd_recipient_restrictions => {
                action => 'add',
                before => qr/permit/,
                values => [ "check_policy_service inet:127.0.0.1:$self->{'config'}->{'postgrey_port'}" ]
            }
        )
    );
    return $rs if $rs;

    my $serviceTasksSub = sub {
        local $@;
        eval {
            my $serviceMngr = iMSCP::Service->getInstance( );
            $serviceMngr->enable( 'postgrey' );
            $serviceMngr->restart( 'postgrey' );
        };
        if ($@) {
            error( $@ );
            return 1;
        }
        0;
    };

    if (defined $main::execmode && $main::execmode eq 'setup') {
        return $self->{'eventManager'}->register(
            'beforeSetupRestartServices',
            sub {
                unshift @{$_[0]}, [ $serviceTasksSub, 'Postgrey' ];
                0;
            }
        );
    }

    $serviceTasksSub->( );
}

=item disable( )

 Perform disable tasks

 Return 0 on success, other on failure

=cut

sub disable
{
    my ($self) = @_;

    return 0 if defined $main::execmode && $main::execmode eq 'setup';

    my $rs = Servers::mta->factory( )->postconf(
        (
            smtpd_recipient_restrictions => {
                action => 'remove',
                values => [ qr/check_policy_service\s+\Qinet:127.0.0.1:$self->{'config_prev'}->{'postgrey_port'}\E/ ]
            }
        )
    );
    return $rs if $rs || $self->{'action'} ne 'disable';

    local $@;
    eval {
        my $serviceMngr = iMSCP::Service->getInstance( );
        $serviceMngr->stop( 'postgrey' );
        $serviceMngr->disable( 'postgrey' );
    };
    if ($@) {
        error( $@ );
        return 1;
    }
    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _installDistributionPackages( )
 
 Install required distribution packages

 Return int 0 on success, other on failure

=cut

sub _installDistributionPackages
{
    local $ENV{'DEBIAN_FRONTEND'} = 'noninteractive';

    my $rs = execute( [ 'apt-get', 'update' ], \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( sprintf( "Couldn't update APT index: %s", $stderr || 'Unknown error' ) ) if $rs;
    return $rs if $rs;

    $rs = execute(
        [
            'apt-get', '-o', 'DPkg::Options::=--force-confold', '-o', 'DPkg::Options::=--force-confdef',
            '-o', 'DPkg::Options::=--force-confmiss', '--assume-yes', '--auto-remove', '--no-install-recommends',
            '--purge', '--quiet', 'install', 'postgrey'
        ],
        \$stdout,
        \$stderr
    );
    debug( $stdout ) if $stdout;
    error( sprintf( "Couldn't install distribution packages: %s", $stderr || 'Unknown error' ) ) if $rs;
    $rs;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
