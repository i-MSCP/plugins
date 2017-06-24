=head1 NAME

 Plugin::PolicydSPF

=cut

# i-MSCP PolicydSPF plugin
# @copyright (C) 2016-2017 Laurent Declercq <l.declercq@nuxwin.com>
# @copyright 2016 Ninos Ego <me@ninosego.de>
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
use autouse 'iMSCP::Debug' => qw/ debug error /;
use autouse 'iMSCP::Execute' => qw/ execute /;
use autouse 'iMSCP::TemplateParser' => qw/ getBloc replaceBloc /;
use Class::Autouse qw/ :nostat iMSCP::File Servers::mta /;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part of the PolicydSPF plugin.

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

    $self->_configurePostfix( 'configure' );
}

=item disable( )

 Perform disable tasks

 Return 0 on success, other on failure

=cut

sub disable
{
    my ($self) = @_;

    return 0 if defined $main::execmode && $main::execmode eq 'setup';

    $self->_configurePostfix( 'deconfigure' );
}

=item _configurePostfix( [ $action = 'deconfigure' ] )

 Configure Postfix

 Param string $action Action to perform (configure|deconfigure)
 Return int 0 on success, other on failure

=cut

sub _configurePostfix
{
    my ($self, $action) = @_;
    $action //= 'deconfigure';

    my $mta = Servers::mta->factory( );

    if ($action eq 'configure') {
        my $file = iMSCP::File->new( filename => $mta->{'config'}->{'POSTFIX_MASTER_CONF_FILE'} );
        my $fileContent = $file->get( );
        unless (defined $fileContent) {
            error( sprintf( "Couldn't read %s file", $file->{'filename'} ) );
            return 1;
        }

        my $confSnippet = <<'EOF';
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
        $rs ||= $file->save( );
        $rs ||= $mta->postconf(
            (
                'policy-spf_time_limit'      => {
                    action => 'replace',
                    values => [ "$self->{'config'}->{'policyd_spf_time_limit'}" ]
                },
                smtpd_recipient_restrictions => {
                    action => 'add',
                    before => qr/permit/,
                    values => [ "check_policy_service $self->{'config'}->{'policyd_spf_service'}" ]
                }
            )
        );
        return $rs;
    }

    my $rs = $mta->postconf(
        'policy-spf_time_limit'      => {
            action => 'replace',
            values => [ '' ]
        },
        smtpd_recipient_restrictions => {
            action => 'remove',
            values => [ qr/check_policy_service\s+\Q$self->{'config_prev'}->{'policyd_spf_service'}\E/ ]
        }
    );
    return $rs if $rs;

    my $file = iMSCP::File->new( filename => $mta->{'config'}->{'POSTFIX_MASTER_CONF_FILE'} );
    my $fileContent = $file->get( );
    unless (defined $fileContent) {
        error( sprintf( "Couldn't read %s file", $file->{'filename'} ) );
        return 1;
    }
    $fileContent = replaceBloc(
        "# Plugin::PolicydSPF - Begin\n", "# Plugin::PolicydSPF - Ending\n", '', $fileContent
    );
    $rs = $file->set( $fileContent );
    $rs ||= $file->save( );
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
    $ENV{'DEBIAN_FRONTEND'} = 'noninteractive';

    my $rs = execute( [ 'apt-get', 'update' ], \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( sprintf("Couldn't update APT index: %s", $stderr || 'Unknown error' ) ) if $rs;
    return $rs if $rs;

    $rs = execute(
        [
            'apt-get', '-o', 'DPkg::Options::=--force-confold', '-o', 'DPkg::Options::=--force-confdef',
            '-o', 'DPkg::Options::=--force-confmiss', '--assume-yes', '--auto-remove', '--no-install-recommends',
            '--purge', '--quiet', 'install', 'postfix-policyd-spf-perl'
        ],
        \$stdout,
        \$stderr
    );
    debug( $stdout ) if $stdout;
    error( sprintf( "Couldn't install distribution packages: %s", $stderr || 'Unknown error' ) ) if $rs;
    $rs;
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>
 Ninos Ego <me@ninosego.de>

=cut

1;
__END__
