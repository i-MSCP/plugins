<?php
/**
 * i-MSCP Monitorix plugin
 * Copyright (C) 2013-2016 Laurent Declercq <l.declercq@nuxwin.com>
 * Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

return array(
	// Monitorix binary path ( default: /usr/bin/monitorix )
	'bin_path' => '/usr/bin/monitorix',

	// Path to monitorix CGI script ( default: /var/lib/monitorix/www/cgi/monitorix.cgi )
	'cgi_path' => '/var/lib/monitorix/www/cgi/monitorix.cgi',

	// Path to monitorix configuration directory
	'confdir_path' => '/etc/monitorix',

	// Color for graph background ( default: white )
	// Possible value: black or white
	'graph_color' => 'white',

	// Graphs width, proportional to 895 px ( default: 450 )
	'graph_width' => '450',

	// Graphs height, proportional to 367 px ( default: 185 )
	'graph_height' => '185',

	// Graphs to enable/disable
	//
	// WARNING:
	// Be aware that some graphs require further configuration ( see http://www.monitorix.org/documentation.html )
	// The default configuration as provided by the Monitorix package doesn't work with all systems. In most cases,
	// enabling a graph is not sufficient. Default enabled graphs are know to work out of box. Other require further
	// configuration which is curently not done automatically by this plugin.
	'graph_enabled' => array(
		'system' => 'y',
		'kern' => 'y',
		'proc' => 'y',
		'hptemp' => 'n',
		'lmsens' => 'n',
		'nvidia' => 'n',
		'disk' => 'n',
		'fs' => 'n',
		'net' => 'n',
		'netstat' => 'y',
		'serv' => 'y',
		'mail' => 'n',
		'port' => 'y',
		'user' => 'y',
		'ftp' => 'n',
		'apache' => 'n',
		'nginx' => 'n',
		'lighttpd' => 'n',
		'mysql' => 'n',
		'squid' => 'n',
		'nfss' => 'n',
		'nfsc' => 'n',
		'bind' => 'n',
		'ntp' => 'n',
		'fail2ban' => 'n',
		'icecast' => 'n',
		'raspberrypi' => 'n',
		'phpapc' => 'n',
		'memcached' => 'n',
		'apcupsd' => 'n',
		'wowza' => 'n',
		'int' => 'n'
	),

	// Enable or disable Monitorix cronjob ( default: true )
	// If disabled, the graphs will not be updated
	'cronjob_enabled' => true,

	// Timedate at which monitorix cronjob must be run ( default: Every 30 minutes )
	// See man CRONTAB(5) for allowed values
	'cronjob_timedate' => array(
		'minute' => '*/30',
		'hour' => '*',
		'day' => '*',
		'month' => '*',
		'dweek' => '*'
	)
);
