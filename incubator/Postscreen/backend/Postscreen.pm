=head1 NAME

 Plugin::Postscreen

=cut

# i-MSCP Postscreen plugin
# @copyright 2015-2017 Laurent Declercq <l.declercq@nuxwin.com>
# @copyright 2013-2016 Rene Schuster <mail@reneschuster.de>
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

package Plugin::Postscreen;

use strict;
use warnings;
use autouse 'iMSCP::Debug' => qw/ debug error /;
use autouse 'iMSCP::Execute' => qw/ execute /;
use autouse 'iMSCP::TemplateParser' => qw/ replaceBloc /;
use Class::Autouse qw/ :nostat iMSCP::Dir iMSCP::File iMSCP::Service iMSCP::Servers::Mta /;
use version;
use parent 'iMSCP::Common::Singleton';

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP Postscreen plugin.

=head1 PUBLIC METHODS

=over 4

=item uninstall( )

 Perform uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    $_[0]->_setupPostscreenAccessFile( 'remove' );
}

=item update( $fromVersion )

 Perform update tasks

 Param string $fromVersion Version from which plugin is being updated
 Return int 0 on success, other on failure

=cut

sub update
{
    my $fromVersion = $_[1];

    return 0 unless version->parse( $fromVersion ) < version->parse( '0.0.6' );

    my $roundcubeConffile = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/config/config.inc.php";

    # Reset roundcube config.inc.php if any
    if ( -f $roundcubeConffile ) {
        my $file = iMSCP::File->new( filename => $roundcubeConffile );
        my $fileContent = $file->get();
        unless ( defined $fileContent ) {
            error( sprintf( "Couldn't read %s file", $file->{'filename'} ));
            return 1;
        }

        $fileContent = replaceBloc( "// BEGIN Plugin::Postscreen\n", "// END Plugin::Postscreen\n", '', $fileContent );
        $file->set( $fileContent );

        my $rs = $file->save();
        return $rs if $rs;
    }

    my $mta = iMSCP::Servers::Mta->factory();

    # Reset Postfix main.cf file if any
    if ( -f $mta->{'config'}->{'POSTFIX_CONF_FILE'} ) {
        my $file = iMSCP::File->new( filename => $mta->{'config'}->{'POSTFIX_CONF_FILE'} );
        my $fileContent = $file->get();
        unless ( defined $fileContent ) {
            error( sprintf( "Couldn't read %s file", $file->{'filename'} ));
            return 1;
        }

        $fileContent = replaceBloc( "// Begin Plugin::Postscreen\n", "// Ending Plugin::Postscreen\n", '', $fileContent );

        $file->set( $fileContent );
        my $rs = $file->save();
        return $rs if $rs;
    }

    # Reset Postfix master.cf file if any
    return 0 unless -f $mta->{'config'}->{'POSTFIX_MASTER_CONF_FILE'};

    my $file = iMSCP::File->new( filename => $mta->{'config'}->{'POSTFIX_MASTER_CONF_FILE'} );
    my $fileContent = $file->get();
    unless ( defined $fileContent ) {
        error( sprintf( "Couldn't read %s file", $file->{'filename'} ));
        return 1;
    }

    $fileContent = replaceBloc(
        "// Begin Plugin::Postscreen\n",
        "// Ending Plugin::Postscreen\n",
        "smtp      inet  n       -       y       -       -       smtpd\n",
        $fileContent
    );

    $file->set( $fileContent );
    $file->save();
}

=item enable( )

 Perform enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
    my ($self) = @_;

    my $rs = $self->_setupPostscreenAccessFile( 'add' );
    $rs ||= $self->_configurePostfix( 'configure' );

    return $rs if $rs || !grep( lc $_ eq 'roundcube', split ',', $main::imscpConfig{'WEBMAIL_PACKAGES'} );

    $self->_configureRoundcube( 'configure' );
}

=item disable( )

 Perform disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
    my ($self) = @_;

    return 0 if defined $main::execmode && $main::execmode eq 'setup';

    my $rs = $self->_configurePostfix( 'deconfigure' );

    return $rs if $rs || !grep( lc $_ eq 'roundcube', split ',', $main::imscpConfig{'WEBMAIL_PACKAGES'} );

    $self->_configureRoundcube( 'deconfigure' );
}

=back

=head1 PRIVATE METHODS

=over 4

=item configurePostfix( [ $action = 'deconfigure' ] )

 Configure Postfix

 Param string $action Action to perform (configure|deconfigure)
 Return int 0 on success, other on failure

=cut

sub _configurePostfix
{
    my ($self, $action) = @_;
    $action //= 'deconfigure';

    my $mta = iMSCP::Servers::Mta->factory();

    if ( $action eq 'deconfigure' ) {
        my $rs = $mta->postconf( (
            postscreen_greet_action              => {
                action => 'replace',
                values => [ '' ]
            },
            postscreen_dnsbl_sites               => {
                action => 'replace',
                values => [ '' ]
            },
            postscreen_dnsbl_threshold           => {
                action => 'replace',
                values => [ '' ]
            },
            postscreen_dnsbl_whitelist_threshold => {
                action => 'replace',
                values => [ '' ]
            },
            postscreen_dnsbl_action              => {
                action => 'replace',
                values => [ '' ]
            },
            postscreen_access_list               => {
                action => 'replace',
                values => [ '' ]
            },
            postscreen_blacklist_action          => {
                action => 'replace',
                values => [ '' ]
            }
        ));
        return $rs if $rs;

        my $file = iMSCP::File->new( filename => $mta->{'config'}->{'POSTFIX_MASTER_CONF_FILE'} );
        my $fileContent = $file->get();
        unless ( defined $fileContent ) {
            error( sprintf( "Couldn't read %s file", $file->{'filename'} ));
            return 1;
        }

        $file->set( replaceBloc( "# Plugin::Postscreen - Begin\n", "# Plugin::Postscreen - Ending\n", '', $fileContent ));
        $rs = $file->save();
    } elsif ( $action eq 'configure' ) {
        my $file = iMSCP::File->new( filename => $mta->{'config'}->{'POSTFIX_MASTER_CONF_FILE'} );
        my $fileContent = $file->get();
        unless ( defined $fileContent ) {
            error( sprintf( "Couldn't read %s file", $file->{'filename'} ));
            return 1;
        }

        my $confSnippet = <<'EOF';
# Plugin::Postscreen - Begin
smtp      inet  n       -       y       -       1       postscreen
smtpd     pass  -       -       y       -       -       smtpd
tlsproxy  unix  -       -       y       -       0       tlsproxy
dnsblog   unix  -       -       y       -       0       dnsblog
# Plugin::Postscreen - Ending
EOF
        if ( $fileContent !~ /Plugin::Postscreen/ ) {
            $fileContent .= $confSnippet;
        } else {
            $fileContent = replaceBloc(
                "# Plugin::Postscreen - Begin\n", "# Plugin::Postscreen - Ending\n", $confSnippet, $fileContent
            );
        }

        $file->set( $fileContent );

        my $rs = $file->save();
        $rs ||= $mta->postconf( (
            postscreen_greet_action              => {
                action => 'replace',
                values => [ $self->{'config'}->{'postscreen_greet_action'} ]
            },
            postscreen_dnsbl_sites               => {
                action => 'replace',
                values => [ @{$self->{'config'}->{'postscreen_dnsbl_sites'}} ]
            },
            postscreen_dnsbl_threshold           => {
                action => 'replace',
                values => [ $self->{'config'}->{'postscreen_dnsbl_threshold'} ]
            },
            postscreen_dnsbl_action              => {
                action => 'replace',
                values => [ $self->{'config'}->{'postscreen_dnsbl_action'} ]
            },
            postscreen_access_list               => {
                action => 'replace',
                values => [ @{$self->{'config'}->{'postscreen_access_list'}} ]
            },
            postscreen_blacklist_action          => {
                action => 'replace',
                values => [ $self->{'config'}->{'postscreen_blacklist_action'} ]
            },
            postscreen_dnsbl_whitelist_threshold => {
                action => 'replace',
                values => [ $self->{'config'}->{'postscreen_dnsbl_whitelist_threshold'} ]
            }
        ));
        return $rs if $rs;
    }

    0
}

=item _setupPostscreenAccessFile( $action )

 Create or delete postscreen access files

 Param string $action Action to perform (add|remove)
 Return int 0 on success, other on failure

=cut

sub _setupPostscreenAccessFile
{
    my ($self, $action) = @_;

    for ( @{$self->{'config'}->{'postscreen_access_list'}} ) {
        next unless /^cidr:/;

        ( my $fileName = $_ ) =~ s/^cidr://;
        my $file = iMSCP::File->new( filename => $fileName );

        if ( $action eq 'add' ) {
            unless ( -f $fileName ) {
                my $fileContent = <<'EOF';
# For more information please check man postscreen or
# http://www.postfix.org/postconf.5.html#postscreen_access_list
#
# Rules are evaluated in specified order.
# Blacklist 192.168.* except 192.168.0.1
# 192.168.0.1         permit
# 192.168.0.0/16      reject
EOF
                my $rs = $file->set( $fileContent );
                $rs ||= $file->save();
                $rs ||= $file->mode( 0644 );
                return $rs if $rs;
            }
        } elsif ( -f $fileName ) {
            my $rs = $file->delFile();
            return $rs if $rs;
        }
    }

    0;
}

=item _configureRoundcube( $action )

 Change Roundcube SMTP port

 Param string $action Action to perform (configure|deconfigure)
 Return int 0 on success, other on failure

=cut

sub _configureRoundcube
{
    my $action = $_[1];

    my $roundcubeMainIncFile = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/config/config.inc.php";

    my $file = iMSCP::File->new( filename => $roundcubeMainIncFile );
    my $fileContent = $file->get();
    unless ( defined $fileContent ) {
        error( sprintf( "Couldn't read %s file", $file->{'filename'} ));
        return 1;
    }

    if ( $action eq 'configure' ) {
        my $confSnippet = <<'EOF';
# Plugin::Postscreen - Begin
$config['smtp_port'] = 587;
# Plugin::Postscreen - Ending
EOF
        if ( $fileContent !~ /Plugin::Postscreen/ ) {
            $fileContent .= $confSnippet;
        } else {
            $fileContent = replaceBloc( "# Plugin::Postscreen - Begin\n", "# Plugin::Postscreen - Ending\n", $confSnippet, $fileContent );
        }
    } elsif ( $action eq 'deconfigure' ) {
        $fileContent = replaceBloc( "# Plugin::Postscreen - Begin\n", "# Plugin::Postscreen - Ending\n", '', $fileContent );
    }

    my $rs = $file->set( $fileContent );
    $rs ||= $file->save();
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>
 Rene Schuster <mail@reneschuster.de>

=cut

1;
__END__
