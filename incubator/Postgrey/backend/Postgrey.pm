=head1 NAME

 Plugin::Postgrey

=cut

# i-MSCP Postgrey plugin
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

package Plugin::Postgrey;

use strict;
use warnings;
use iMSCP::Database;
use iMSCP::Debug;
use iMSCP::Execute;
use iMSCP::Service;
use Servers::mta;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part of the Postgrey plugin.

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

    my $mta = Servers::mta->factory();
    $rs = $mta->postconf(
        (
            smtpd_recipient_restrictions => {
                action => 'add',
                before => qr/permit/,
                values => [ "check_policy_service inet:127.0.0.1:$self->{'config'}->{'postgrey_port'}" ]
            },

        )
    );
    return $rs if $rs;

    local $@;
    eval { iMSCP::Service->getInstance()->restart( 'postgrey' ); };
    if ($@) {
        error( $@ );
        return 1;
    }

    $mta->{'reload'} = 1;
    0;
}

=item disable()

 Perform disable tasks

 Return 0 on success, other on failure

=cut

sub disable
{
    my $self = shift;

    return 0 if defined $main::execmode && $main::execmode eq 'setup';

    my $mta = Servers::mta->factory();
    my $rs = $mta->postconf(
        (
            smtpd_recipient_restrictions => {
                action => 'remove',
                values => [ qr/check_policy_service\s+\Qinet:127.0.0.1:$self->{'config_prev'}->{'postgrey_port'}\E/ ]
            },

        )
    );
    return $rs if $rs;

    $mta->{'reload'} = 1;
    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _checkRequirements()

 Check for requirements

 Return int 0 if all requirements are met, other otherwise

=cut

sub _checkRequirements
{
    if (execute( "dpkg-query -W -f='\${Status}' postgrey 2>/dev/null | grep -q '\\sinstalled\$'" )) {
        error( "The `postgrey` package is not installed on your system" );
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
