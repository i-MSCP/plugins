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
use iMSCP::Debug;
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
    $rs ||= $self->_configurePostfix( 'configure' );
}

=item disable()

 Perform disable tasks

 Return 0 on success, other on failure

=cut

sub disable
{
    my $self = shift;

    return 0 if defined $main::execmode && $main::execmode eq 'setup';

    $self->_configurePostfix( 'deconfigure' );
}

=item _configurePostfix($action)

 Configure Postfix

 Param string $action Action to perform (configure|deconfigure)
 Return int 0 on success, other on failure

=cut

sub _configurePostfix
{
    my ($self, $action) = @_;

    my $mta = Servers::mta->factory();

    if ($action eq 'configure') {
        my $file = iMSCP::File->new( filename => $mta->{'config'}->{'POSTFIX_MASTER_CONF_FILE'} );
        my $fileContent = $file->get();
        unless (defined $fileContent) {
            error( sprintf( 'Could not read %s file', $file->{'filename'} ) );
            return 1;
        }

        my $confSnippet = <<EOF;
# Plugin::PolicydSPF - Begin
policy-spf  unix  -       n       n       -       -       spawn
  user=nobody argv=/usr/sbin/postfix-policyd-spf-perl
# Plugin::PolicydSPF - Ending
EOF
        if (getBloc( "# Plugin::PolicydSPF - Begin\n", "# Plugin::PolicydSPF - Ending\n", $fileContent ) ne '') {
            $fileContent = replaceBloc(
                "# Plugin::PolicydSPF - Begin\n", "# Plugin::PolicydSPF - Ending\n", $confSnippet, $fileContent
            );
        } else {
            $fileContent .= $confSnippet;
        }

        my $rs = $file->set( $fileContent );
        $rs ||= $file->save();
        $rs ||= $mta->postconf(
            (
                'policy-spf_time_limit'        => {
                    action => 'replace',
                    values => [ "$self->{'config'}->{'policyd_spf_time_limit'}" ]
                },
                'smtpd_recipient_restrictions' => {
                    action => 'add',
                    before => qr/permit/,
                    values => [ "check_policy_service $self->{'config'}->{'policyd_spf_service'}" ]
                }
            )
        );
        return $rs if $rs;
    } elsif ($action eq 'deconfigure') {
        my $rs = $mta->postconf(
            'policy-spf_time_limit'        => {
                action => 'replace',
                values => [ '' ]
            },
            'smtpd_recipient_restrictions' => {
                action => 'remove',
                values => [ qr/check_policy_service\s+\Q$self->{'config_prev'}->{'policyd_spf_service'}\E/ ]
            }
        );
        return $rs if $rs;

        my $file = iMSCP::File->new( filename => $mta->{'config'}->{'POSTFIX_MASTER_CONF_FILE'} );
        my $fileContent = $file->get();
        unless (defined $fileContent) {
            error( sprintf( 'Could not read %s file', $file->{'filename'} ) );
            return 1;
        }
        $fileContent = replaceBloc(
            "# Plugin::PolicydSPF - Begin\n", "# Plugin::PolicydSPF - Ending\n", '', $fileContent
        );
        $rs = $file->set( $fileContent );
        $rs ||= $file->save();
        return $rs if $rs;
    }

    $mta->{'restart'} = 1;
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
    if (execute( "dpkg-query -W -f='\${Status}' postfix-policyd-spf-perl 2>/dev/null | grep -q '\\sinstalled\$'" )) {
        error( "The `postfix-policyd-spf-perl' package is not installed on your system" );
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
