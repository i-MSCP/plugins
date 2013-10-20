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

namespace MyDNS;

use iMSCP_Registry as Registry;
use iMSCP\Loader\AutoloaderFactory as LoaderFactory;
use iMSCP\Loader\UniversalLoader as Loader;

$pluginDir = Registry::get('pluginManager')->getPluginDirectory();

/** @var Loader $loader */
$loader = LoaderFactory::getAutoloader('iMSCP\Loader\UniversalLoader');

// // We add our classMap into the universal loader (more faster than using autoloader)
$loader->addClassMap(
	array(
		'AltoRouter' => $pluginDir . '/MyDNS/library/vendor/AltoRouter/AltoRouter.php',
		'MyDNS\Api' => $pluginDir . '/MyDNS/library/MyDNS/Api.php',
		'MyDNS\Nameserver' => $pluginDir . '/MyDNS/library/MyDNS/Nameserver.php',
		'MyDNS\Nameserver\Sanity' => $pluginDir . '/MyDNS/library/MyDNS/Nameserver/Sanity.php',
		'MyDNS\Zone' => $pluginDir . '/MyDNS/library/MyDNS/Zone.php',
		'MyDNS\Zone\Sanity' => $pluginDir . '/MyDNS/library/MyDNS/Zone/Sanity.php',
		'MyDNS\Zone\Record' => $pluginDir . '/MyDNS/library/MyDNS/Zone/Record.php',
		'MyDNS\Zone\Record\Sanity' => $pluginDir . '/MyDNS/library/MyDNS/Zone/Record/Sanity.php'
	)
);

$api = new Api();

// Set Web service endpoint
$api->setEndpoint('/mydns/api');

# We add available REST resource URIs according HTTP method
# All resource below belongs to the authenticated users
switch ($api->getHttpMethod()) {
	case 'POST':
		$api->addRoute('POST', '/nameservers', array('class' => 'Nameserver', 'function' => 'create'));
		$api->addRoute('POST', '/zones', array('class' => 'Zone', 'function' => 'create'));
		$api->addRoute('POST', '/zones/[i:mydns_zone_id]/records', array('class' => 'Record', 'function' => 'create'));
		break;
	case 'PUT':
		$api->addRoute(
			'PUT',
			'/nameservers/[i:mydns_nameserver_id]',
			array('class' => 'Nameserver', 'function' => 'update')
		);
		$api->addRoute('PUT', '/zones/[i:mydns_zone_record_id]', array('class' => 'Zone', 'function' => 'update'));
		$api->addRoute(
			'PUT',
			'/zones/[i:mydns_zone_record_id]/records/[i:mydns_zone_record_id]',
			array('class' => 'Record', 'function' => 'update')
		);
		break;
	case 'DELETE':
		$api->addRoute(
			'DELETE', '/nameservers/[i:mydns_nameserver_id]', array('class' => 'Nameserver', 'function' => 'delete')
		);
		$api->addRoute(
			'DELETE', '/zones/[i:mydns_zone_record_id]', array('class' => 'Zone', 'function' => 'delete')
		);
		$api->addRoute(
			'DELETE',
			'/zones/[i:mydns_zone_record_id]/records/[i:mydns_zone_record_id]',
			array('class' => 'Record', 'function' => 'delete')
		);
		break;
	default:
		$api->addRoute('GET', '/nameservers', array('class' => 'Nameserver', 'function' => 'collection'));
		$api->addRoute(
			'GET', '/nameservers/[i:mydns_nameserver_id]', array('class' => 'Nameserver', 'function' => 'read')
		);
		$api->addRoute('GET', '/zones', array('class' => 'Zone', 'function' => 'collection'));
		$api->addRoute('GET', '/zones/[i:mydns_zone_id]', array('class' => 'Zone', 'function' => 'read'));
		$api->addRoute(
			'GET', '/zones/[i:mydns_zone_id]/records',
			array('class' => 'Record', 'function' => 'collection')
		);
		$api->addRoute(
			'GET', '/zones/[i:mydns_zone_id]/records/[i:mydns_zone_record_id]',
			array('class' => 'Record', 'function' => 'read')
		);
}

// Process API call
$api->process();
