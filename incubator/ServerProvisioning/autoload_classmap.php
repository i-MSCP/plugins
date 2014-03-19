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

return array(
	'AltoRouter' => __DIR__ . '/library/vendor/AltoRouter/AltoRouter.php',
	'ServerProvisioning\Api' => __DIR__ . '/library/ServerProvisioning/Api.php',
	'ServerProvisioning\HMACAuthentication' => __DIR__ . '/library/ServerProvisioning/HMACAuthentication.php',
	'ServerProvisioning\Controller\AbstractController' => __DIR__ . '/library/ServerProvisioning/Controller/AbstractController.php',
	'ServerProvisioning\Controller\Login' => __DIR__ . '/library/ServerProvisioning/Controller/Login.php',
	'ServerProvisioning\Controller\Account\Sanity' =>  __DIR__ . '/library/ServerProvisioning/Controller/Account.php',
	'ServerProvisioning\Controller\Account' =>  __DIR__ . '/library/ServerProvisioning/Controller/Account/Sanity.php',
	'ServerProvisioning\Controller\Package\Sanity' =>  __DIR__ . '/library/ServerProvisioning/Controller/Package.php',
	'ServerProvisioning\Controller\Package' =>  __DIR__ . '/library/ServerProvisioning/Models/Package/Sanity.php'
);
