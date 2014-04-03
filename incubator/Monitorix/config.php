<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP Team
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
 *
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  Monitorix
 * @copyright   2010-2014 by i-MSCP Team
 * @author      Sascha Bay <info@space2place.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

return array(
	'graph_color' => 'white', // choose between black or white

	'graph_width' => '450', // set the width for the graphic how they appears in adminpanel (proportional to 895 in px)
	'graph_height' => '185', // set the height for the graphic how they appears in adminpanel (proportional to 367 in px)

	'graph_enabled' => array( // set which graph should be generated. This will modify the /etc/monitorix.conf
		'system' => 'y',
		'kern' => 'y',
		'proc' => 'y',
		'hptemp' => 'n',
		'lmsens' => 'n',
		'nvidia' => 'n',
		'disk' => 'n',
		'fs' => 'y',
		'net' => 'y',
		'netstat' => 'n',
		'serv' => 'y',
		'mail' => 'n',
		'port' => 'y',
		'user' => 'y',
		'ftp' => 'n',
		'apache' => 'y',
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
		'int' => 'y',
	),

	'graph_title' => array( // set the graphic titles here
		'system' => 'System load average and usage',
		'kern' => 'Global kernel usage',
		'proc' => 'Kernel usage per processor',
		'hptemp' => 'HP ProLiant System Health',
		'lmsens' => 'LM-Sensors and GPU temperatures',
		'nvidia' => 'NVIDIA temperatures and usage',
		'disk' => 'Disk drive temperatures and health',
		'fs' => 'Filesystem usage and I/O activity',
		'net' => 'Network traffic and usage',
		'netstat' => 'Netstat statistics',
		'serv' => 'System services demand',
		'mail' => 'Mail statistics',
		'port' => 'Network port traffic',
		'user' => 'Users using the system',
		'ftp' => 'FTP statistics',
		'apache' => 'Apache statistics',
		'nginx' => 'Nginx statistics',
		'lighttpd' => 'Lighttpd statistics',
		'mysql' => 'MySQL statistics',
		'squid' => 'Squid statistics',
		'nfss' => 'NFS server statistics',
		'nfsc' => 'NFS client statistics',
		'bind' => 'BIND statistics',
		'ntp' => 'NTP statistics',
		'fail2ban' => 'Fail2ban statistics',
		'icecast' => 'Icecast Streaming Media Server',
		'raspberrypi' => 'Raspberry Pi sensor statistics',
		'phpapc' => 'Alternative PHP Cache statistics',
		'memcached' => 'Memcached statistics',
		'apcupsd' => 'APC UPS statistics',
		'wowza' => 'Wowza Media Server',
		'int' => 'Devices interrupt activity',
	),

	'cronjob_enabled' => true, // TRUE to enable Monitorix plugin cronjob (default), FALSE to disable it

	// See man CRONTAB(5) for allowed values
	'cronjob_config' => array(
		'minute' => '*/5',
		'hour' => '*',
		'day' => '*',
		'month' => '*',
		'dweek' => '*'
	)
);
