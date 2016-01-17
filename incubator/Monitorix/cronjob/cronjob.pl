#!/usr/bin/perl
# i-MSCP Monitorix plugin
# Copyright (C) 2013-2016 Laurent Declercq <l.declercq@nuxwin.com>
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

use lib '{IMSCP_PERLLIB_PATH}';
use iMSCP::Debug;
use iMSCP::Bootstrapper;
use iMSCP::Database;
use iMSCP::EventManager;
use JSON;

sub getData
{
	my $row = iMSCP::Database->factory()->doQuery(
		'plugin_name',
		'SELECT plugin_name, plugin_info, plugin_config, plugin_config_prev FROM plugin WHERE plugin_name = ?',
		'Monitorix'
	);
	ref $row eq 'HASH' or die($row);
	$row->{'Monitorix'} or die('Monitorix plugin data not found in database');

	{
		action => 'cron',
		config => decode_json($row->{'Monitorix'}->{'plugin_config'}),
		config_prev => decode_json($row->{'Monitorix'}->{'plugin_config_prev'}),
		eventManager => iMSCP::EventManager->getInstance(),
		info => decode_json($row->{'Monitorix'}->{'plugin_info'})
	};
}

newDebug('monitorix-plugin-cronjob.log');
iMSCP::Bootstrapper->getInstance()->boot({ norequirements => 'yes', config_readonly => 'yes', nolock => 'yes' });

my $pluginFile = "$main::imscpConfig{'PLUGINS_DIR'}/Monitorix/backend/Monitorix.pm";
require $pluginFile;

my $pluginClass = "Plugin::Monitorix";
$pluginClass->getInstance(getData())->buildGraphs() == 0 or die(
	getMessageByType('error', { amount => 1, remove => 1 }) || 'Unknown error'
);
