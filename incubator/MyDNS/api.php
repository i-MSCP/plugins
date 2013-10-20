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

$pluginDir = iMSCP_Registry::get('pluginManager')->getPluginDirectory();

/** @var iMSCP\Loader\UniversalLoader $loader */
$loader = iMSCP\Loader\AutoloaderFactory::getAutoloader('iMSCP\Loader\UniversalLoader');
$loader->addClassMap(
	array(
		'AltoRouter' => $pluginDir . '/MyDNS/library/vendor/AltoRouter.php',
		'MyDNS_Api' => $pluginDir . '/MyDNS/library/MyDNS/Api.php',
		'MyDNS_Nameserver' => $pluginDir . '/MyDNS/library/MyDNS/Nameserver.php',
		'MyDNS_Nameserver_Sanity' => $pluginDir . '/MyDNS/library/MyDNS/Nameserver/Sanity.php',
		'MyDNS_Zone' => $pluginDir . '/MyDNS/library/MyDNS/Zone.php',
		'MyDNS_Zone_Sanity' => $pluginDir . '/MyDNS/library/MyDNS/Zone/Sanity.php',
		'MyDNS_Zone_Record' => $pluginDir . '/MyDNS/library/MyDNS/Zone/Record.php',
		'MyDNS_Zone_Record_Sanity' => $pluginDir . '/MyDNS/library/MyDNS/Zone/Record/Sanity.php',
	)
);

$api = new MyDNS_Api();

// Set Web service endpoint
$api->setEndpoint('/mydns/api');

# Add routes according HTTP method
switch ($api->getHttpMethod()) {
	case 'POST':
		$api->addRoute(
			'POST', '/nameservers', array('class' => 'MyDNS_Nameserver', 'function' => 'createNameserver')
		);

		$api->addRoute(
			'POST', '/zones', array('class' => 'MyDNS_Zone', 'function' => 'createZone')
		);

		$api->addRoute(
			'POST', '/zones/[i:id]/records', array('class' => 'MyDNS_Zone_Record', 'function' => 'createRecord')
		);
		break;
	case 'PUT':
		$api->addRoute(
			'PUT', '/nameservers/[i:id]', array('class' => 'MyDNS_Nameserver', 'function' => 'updateNameserver')
		);

		$api->addRoute(
			'PUT', '/zone/[i:id]', array('class' => 'MyDNS_Zone', 'function' => 'updateZone')
		);

		$api->addRoute(
			'PUT', '/zones/[i:id]/records/[i:id]', array('class' => 'MyDNS_Zone_Record', 'function' => 'updateRecord')
		);
		break;
	case 'DELETE':
		$api->addRoute(
			'DELETE', '/nameservers/[i:id]', array('class' => 'MyDNS_Nameserver', 'function' => 'deleteNameserver')
		);

		$api->addRoute(
			'DELETE', '/zone/[i:id]', array('class' => 'MyDNS_Zone', 'function' => 'deleteZone')
		);

		$api->addRoute(
			'DELETE', '/zones/[i:id]/records/[i:id]', array('class' => 'MyDNS_Zone_Record', 'function' => 'deleteRecord')
		);
		break;
	default:
		$api->addRoute(
			'GET', '/nameservers', array('class' => 'MyDNS_Nameserver', 'function' => 'getNameServers')
		);

		$api->addRoute(
			'GET', '/nameservers/[i:id]', array('class' => 'MyDNS_Nameserver', 'function' => 'getNameserver')
		);

		$api->addRoute(
			'GET', '/zones', array('class' => 'MyDNS_Zone', 'function' => 'getZones')
		);

		$api->addRoute(
			'GET', '/zones/[i:id]', array('class' => 'MyDNS_Zone', 'function' => 'getZone')
		);

		$api->addRoute(
			'GET', '/zones/[i:id]/records', array('class' => 'MyDNS_Zone_Record', 'function' => 'getRecords')
		);

		$api->addRoute(
			'GET', '/zones/[i:id]/records/[i:id]', array('class' => 'MyDNS_Zone_Record', 'function' => 'getRecord')
		);
}

// Process API call
$api->process();
