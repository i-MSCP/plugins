<?php
/**
 * i-MSCP PhpSwitcher plugin
 * Copyright (C) 2014-2015 Laurent Declercq <l.declercq@nuxwin.com>
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
 */

if(isset($_GET['version_id'])) {
	$versionId = intval($_GET['version_id']);

	if ($versionId == 0) {
		phpinfo();
		exit;
	} else {
		$stmt = exec_query('SELECT version_binary_path FROM php_switcher_version WHERE version_id = ?', $versionId);

		if ($stmt->rowCount()) {
			/** @var iMSCP_Plugin_Manager $pluginManager */
			$pluginManager = iMSCP_Registry::get('pluginManager');
			$phpinfoFilePath = $pluginManager->pluginGetDirectory() . "/PhpSwitcher/phpinfo/$versionId.html";

			if (is_readable($phpinfoFilePath)) {
				include $phpinfoFilePath;
				exit;
			} else {
				showNotFoundErrorPage();
			}
		}
	}
}

showBadRequestErrorPage();
