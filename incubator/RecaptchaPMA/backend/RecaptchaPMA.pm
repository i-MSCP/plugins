=head1 NAME

 Plugin::RecaptchaPMA

=cut

# i-MSCP RecaptchaPMA plugin
# Copyright (C) 2017 Laurent Declercq <l.declercq@nuxwin.com>
# Copyright (C) 2010-2016 by Sascha Bay <info@space2place.de>
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

package Plugin::RecaptchaPMA;

use strict;
use warnings;
use iMSCP::Database;
use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Service;
use iMSCP::TemplateParser;
use version;
use parent 'iMSCP::Common::Singleton';

=head1 DESCRIPTION

 This package provide the backend part of the RecaptchaPMA plugin.

=head1 PUBLIC METHODS

=over 4

=item enable()

 Process enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
    my $self = shift;

    my $rs = $self->_pmaConfig( 'configure' );

    return 0 if $rs || ( defined $main::execmode && $main::execmode eq 'setup' );

    eval { iMSCP::Service->getInstance()->restart( 'imscp_panel' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item disable()

 Process disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
    my $self = shift;

    my $rs = $self->_pmaConfig( 'deconfigure' );
    return $rs if $rs || ( defined $main::execmode && $main::execmode eq 'setup' );

    local $@;
    eval { iMSCP::Service->getInstance()->restart( 'imscp_panel' ); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize plugin

 Return Plugin::RecaptchaPMA or die on failure

=cut

sub _init
{
    my $self = shift;

    $self->{'FORCE_RETVAL'} = 'yes' if $self->{'action'} =~ /^(?:enable|disable|change|update)$/;
    $self;
}

=item _pmaConfig($action)

 Configure or deconfigure PMA

 Param string $action Action to perform ( configure|deconfigure)
 Return int 0 on success, other on failure

=cut

sub _pmaConfig
{
    my ($self, $action) = @_;

    my $file = iMSCP::File->new( filename => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/pma/config.inc.php" );
    my $fileContent = $file->get();
    unless ( defined $fileContent ) {
        error( sprintf( 'Could not read %s file', $file->{'filename'} ));
        return 1;
    }

    if ( $action eq 'configure' ) {
        my $configSnippet = <<"EOF";
# Begin Plugin::RecaptchaPMA
\$cfg['CaptchaLoginPublicKey'] = "$self->{'config'}->{'reCaptchaLoginPublicKey'}";
\$cfg['CaptchaLoginPrivateKey'] = "$self->{'config'}->{'reCaptchaLoginPrivateKey'}";
# Ending Plugin::RecaptchaPMA
EOF
        if ( getBloc( "# Begin Plugin::RecaptchaPMA\n", "# Ending Plugin::RecaptchaPMA\n", $fileContent ) ne '' ) {
            $fileContent = replaceBloc( "# Begin Plugin::RecaptchaPMA\n", "# Ending Plugin::RecaptchaPMA\n", $configSnippet, $fileContent );
        } else {
            $fileContent .= $configSnippet;
        }
    } elsif ( $action eq 'deconfigure' ) {
        $fileContent = replaceBloc( "# Begin Plugin::RecaptchaPMA\n", "# Ending Plugin::RecaptchaPMA\n", '', $fileContent );
    }

    my $rs = $file->set( $fileContent );
    $rs ||= $file->save();
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>
 Sascha Bay <info@space2place.de>

=cut

1;
__END__
