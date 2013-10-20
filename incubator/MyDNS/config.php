<?php
/**
 * i-MSCP MyDNS Plugin
 * Copyright (C) 2010-2013 by Laurent Declercq
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
 * @subpackage  MyDNS
 * @copyright   2010-2013 by Laurent Declercq
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

return array(

	// Default configuration and data directories
	'confdir' => '/etc/bind',
	'datadir' => '/var/cache/bind',

	// Default value for nameservers, zones and DNS resource records
	'default_nameserver_ttl'  => '86400',
	'default_zone_ttl' => '86400',
	'default_zone_mailaddr' => 'hostmaster.{ZONE}.',
	'default_zone_retry'  => '900',      // RFC 1912 range (180-900 sec)
	'default_zone_expire' => '1048576',  // RFC 1912 range (14 - 28 days)
	'default_zone_minimum' => '2560',    // RFC 2308 range (1 - 3 hours)
	'default_zone_record_ttl' => '86400'
);
