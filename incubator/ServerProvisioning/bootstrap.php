<?php
/**
 * ServerProvisioning plugin for i-MSCP
 * Copyright (C) 2014 Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category    iMSCP_Plugin
 * @package     ServerProvisioning
 * @copyright   2014 by Laurent Declercq <l.declercq@nuxwin.com>
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/lgpl-2.1.txt LGPL v2.1
 */

namespace ServerProvisioning;

use iMSCP\Loader\AutoloaderFactory as LoaderFactory;
use iMSCP\Loader\UniversalLoader as Loader;
use iMSCP_Registry as Registry;

/** @var Loader $loader */
$loader = LoaderFactory::getAutoloader('iMSCP\Loader\UniversalLoader');

// Add classMap into the universal loader (more faster than using autoloader)
$loader->addClassMap(include('autoload_classmap.php'));

$api = new Api();
$api->getRouter()
	->setBasePath(Registry::get('pluginManager')->getPlugin('ServerProvisioning')->getConfigParam('base_endpoint'))
	->addRoutes(include('routes.php'));

// Process API call
$api->run();
