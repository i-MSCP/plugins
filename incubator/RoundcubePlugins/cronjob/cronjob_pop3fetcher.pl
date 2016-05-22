#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
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
use lib "{IMSCP_PERLLIB_PATH}";
use iMSCP::Bootstrapper;
use iMSCP::Debug;

$ENV{'LANG'} = 'C.UTF-8';

newDebug( 'roundcubeplugins-plugin-cronjob-pop3fetcher.log' );
iMSCP::Bootstrapper->getInstance()->boot(
    {
        norequirements  => 'yes',
        config_readonly => 'yes',
        nokeys          => 'yes',
        nodatabase      => 'yes',
        nolock          => 'yes'
    }
);

my $pluginFile = "$main::imscpConfig{'GUI_ROOT_DIR'}/plugins/RoundcubePlugins/backend/RoundcubePlugins.pm";
require $pluginFile;

my $pluginClass = "Plugin::SpamAssassin";
$pluginClass->getInstance()->fetchmail() == 0 or die(
    getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
);

1;
__END__
