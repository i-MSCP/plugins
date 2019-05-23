#!/usr/bin/perl
#
# i-MSCP SpamAssassin plugin
# Copyright (C) 2015-2019 Laurent Declercq <l.declercq@nuxwin.com>
# Copyright (C) 2013-2016 Rene Schuster <mail@reneschuster.de>
# Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>
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
use lib "$FindBin::Bin/../../../../engine/PerlLib", "$FindBin::Bin/../../../../engine/PerlVendor";
use iMSCP::Boolean;
use iMSCP::Bootstrapper;
use iMSCP::Database;
use iMSCP::Debug qw/ getMessageByType newDebug setDebug setVerbose /;
use iMSCP::EventManager;
use iMSCP::Getopt;
use iMSCP::Service;
use JSON;
use POSIX 'locale_h';

sub getPluginBackendInstance
{
    defined( my $row = iMSCP::Database->factory()->getRawDb()->selectrow_hashref(
        "
            SELECT `plugin_name`, `plugin_info`, `plugin_config`, `plugin_config_prev`
            FROM `plugin`
            WHERE `plugin_name` = 'SpamAssassin'
        "
    ) or die( 'SpamAssassin plugin configuration not found in database' ));

    my $classFile = "$::imscpConfig{'PLUGINS_DIR'}/SpamAssassin/backend/SpamAssassin.pm";
    require $classFile;

    my $class = 'Plugin::SpamAssassin';
    $class->getInstance( {
        action       => 'cron',
        config       => decode_json( $row->{'plugin_config'} ),
        config_prev  => decode_json( $row->{'plugin_config_prev'} ),
        eventManager => iMSCP::EventManager->getInstance(),
        info         => decode_json( $row->{'plugin_info'} )
    } );
}

eval {
    @{ENV}{qw/ LANG PATH /} = (
        'C.UTF-8',
        '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin'
    );

    setlocale( LC_MESSAGES, 'C.UTF-8' );
    newDebug( 'spamassassin-plugin-clean-bayes-db.log' );
    setDebug( iMSCP::Getopt->debug( TRUE ));
    setVerbose( iMSCP::Getopt->verbose( TRUE ));

    iMSCP::Bootstrapper->getInstance()->lock(
        'spamassassin-plugin-clean-bayes-db.lock', TRUE
    ) or exit;

    iMSCP::Bootstrapper->getInstance()->boot( {
        norequirements  => TRUE,
        config_readonly => TRUE,
        nolock          => TRUE
    } );

    iMSCP::Service->getInstance()->isRunning( 'mysql' ) or exit;

    getPluginBackendInstance->cleanBayesDb() == 0 or die( getMessageByType(
        'error', { amount => 1, remove => TRUE }
    ) || 'Unknown error' );
};
if ( $@ ) {
    error( $@ );
    return 1;
}

1;
__END__
