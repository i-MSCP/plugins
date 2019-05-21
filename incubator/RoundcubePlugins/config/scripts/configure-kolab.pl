#!/usr/bin/env perl

=head1 NAME

 configure-kolab.pl - Pre-configuration script for the Kolab Roundcube plugins

=head1 SYNOPSIS

 perl configure-kolab.pl preconfigure <tag>

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2019 Laurent Declercq <l.declercq@nuxwin.com>
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

use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../../../../engine/PerlLib", "$FindBin::Bin/../../../../../engine/PerlVendor";
use File::Basename 'basename';
use iMSCP::Boolean;
use iMSCP::Bootstrapper;
use iMSCP::Cwd '$CWD';
use iMSCP::Debug qw/ debug error newDebug setDebug setVerbose /;
use iMSCP::Dir;
use iMSCP::Execute 'executeNoWait';
use iMSCP::Getopt;
use POSIX 'locale_h';
use Servers::po;

=head1 PUBLIC FUNCTIONS

=over 4

=item preconfigure( )

 Pre-configure the plugins

 Return void, die on failure

=cut

sub preconfigure
{
    my ( $tag ) = @_;

    local $CWD = "$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/plugins/RoundcubePlugins";

    my $cmdArgv = -d 'roundcubemail-plugins-kolab' ? [
        '--git-dir', 'roundcubemail-plugins-kolab/.git',
        '--work-tree', 'roundcubemail-plugins-kolab',
        'pull',
        'origin',
        $tag
    ] : [
        'clone',
        'https://git.kolab.org/diffusion/RPK/roundcubemail-plugins-kolab.git',
        'roundcubemail-plugins-kolab'
    ];

    # Clone or pull kolab repository for Roundcube plugins
    my $stderr;
    executeNoWait(
        [ '/usr/bin/git', @{ $cmdArgv } ],
        \&_std,
        sub { $stderr .= $_[0] }
    ) == 0 or die( $stderr || 'Unknown error' );

    # Checkout the expected tag
    executeNoWait(
        [
            '/usr/bin/git',
            '--git-dir', 'roundcubemail-plugins-kolab/.git',
            '--work-tree', 'roundcubemail-plugins-kolab',
            'checkout',
            $tag
        ],
        \&_std,
        sub { $stderr .= $_[0] }
    ) == 0 or die( $stderr || 'Unknown error' );
}

=back

=head1 PRIVATE FUNCTIONS

=over 4

=item _std( )

 STD routine
 
 Return void

=cut

sub _std
{
    chomp( $_[0] );
    debug( $_[0] ) if length $_[0];
}

=back

=head1 MAIN

=over 4

=cut

eval {
    @{ENV}{qw/ LANG PATH /} = (
        'C.UTF-8',
        '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin'
    );

    setlocale( LC_MESSAGES, 'C.UTF-8' );
    setDebug( iMSCP::Getopt->debug( TRUE ));
    setVerbose( iMSCP::Getopt->verbose( TRUE ));
    newDebug( "@{ [ basename( $0, '.pl' ) ] }.log" );

    iMSCP::Bootstrapper->getInstance()->lock(
        "/var/lock/@{ [ basename( $0, '.pl' ) ] }.lock"
    );
    iMSCP::Bootstrapper->getInstance()->boot( {
        nokeys          => TRUE,
        nodatabase      => TRUE,
        config_readonly => TRUE,
        nolock          => TRUE
    } );

    my $stage = shift @ARGV or die "Missing 'stage' argument";

    if ( $stage eq 'preconfigure' ) {
        my $tag = shift @ARGV or die "Missing 'tag' argument";
        preconfigure( $tag );
    }
};
if ( $@ ) {
    error( $@ );
    exit 1;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
