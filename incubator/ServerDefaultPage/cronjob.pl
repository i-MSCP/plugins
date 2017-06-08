#!/usr/bin/perl

# i-MSCP ServerDefaultPage plugin
# Copyright (C) 2014-2017 Laurent Declercq <l.declercq@nuxwin.com>
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
use File::Basename;
use FindBin;
use lib "$FindBin::Bin/../../../engine/PerlLib", "$FindBin::Bin/../../../engine/PerlVendor";
use iMSCP::Bootstrapper;
use iMSCP::Database;
use iMSCP::Debug;
use iMSCP::EventManager;
use iMSCP::Service;
use JSON;

sub getData
{
    my $row = iMSCP::Database->factory( )->doQuery(
        'plugin_name',
        'SELECT plugin_name, plugin_info, plugin_config, plugin_config_prev FROM plugin WHERE plugin_name = ?',
        'ServerDefaultPage'
    );
    ref $row eq 'HASH' or die( $row );
    $row->{'ServerDefaultPage'} or die( 'ServerDefaultPage plugin data not found in database' );

    {
        action       => 'cron',
        config       => decode_json( $row->{'ServerDefaultPage'}->{'plugin_config'} ),
        config_prev  => decode_json( $row->{'ServerDefaultPage'}->{'plugin_config_prev'} ),
        eventManager => iMSCP::EventManager->getInstance( ),
        info         => decode_json( $row->{'ServerDefaultPage'}->{'plugin_info'} )
    };
}

my $bootstrapper = iMSCP::Bootstrapper->getInstance( );
exit unless $bootstrapper->lock( '/tmp/imscp-serverdefaultpage-cronjob.lock', 'nowait' );

eval {
    newDebug( 'serverdefaultpage-plugin-cronjob.log' );

    iMSCP::Getopt->parseNoDefault( sprintf( 'Usage: perl %s [OPTION]...', basename( $0 ) ).qq {

Renew ServerDefaultPage plugin self-signed SSL certificate if needed.

OPTIONS:
 -d,    --debug         Enable debug mode.
 -v,    --verbose       Enable verbose mode.},
        'debug|d'   => \&iMSCP::Getopt::debug,
        'verbose|v' => \&iMSCP::Getopt::verbose
    );

    setVerbose( iMSCP::Getopt->verbose );

    iMSCP::Bootstrapper->getInstance( )->boot(
        {
            norequirements  => 'yes',
            config_readonly => 'yes'
        }
    );

    iMSCP::Service->getInstance( )->isRunning( 'mysql' ) or exit;

    my $pluginFile = "$main::imscpConfig{'PLUGINS_DIR'}/ServerDefaultPage/backend/ServerDefaultPage.pm";
    require $pluginFile;

    Plugin::ServerDefaultPage->getInstance( getData( ) )->createSelfSignedCertificate( ) == 0 or die(
        getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
    );
};
if ($@) {
    error("Couldn't renew ServerDefaultPage plugin self-signed SSL certificate: %s", $@);
    exit 1;
}

END { $bootstrapper->unlock( '/tmp/imscp-serverdefaultpage-cronjob.lock' ); }

1;
__END__
