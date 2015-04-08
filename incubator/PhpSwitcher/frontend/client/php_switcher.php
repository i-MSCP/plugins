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

/***********************************************************************************************************************
 * Functions
 */

namespace PhpSwitcher;

use iMSCP_Database as Database;
use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventManager;
use iMSCP_Exception_Database as DatabaseException;
use iMSCP_Plugin_Manager as PluginManager;
use iMSCP_Plugin_PhpSwitcher as PhpSwitcher;
use iMSCP_pTemplate as TemplateEngine;
use iMSCP_Registry as Registry;
use PDO;

/**
 * Set PHP version
 *
 * @return void
 */
function changePhpVersion()
{
	if(isset($_POST['version_id'])) {
		$versionId = intval($_POST['version_id']);

		$db = Database::getRawInstance();

		try {
			$db->beginTransaction();

			if(!$versionId) {
				exec_query('DELETE FROM php_switcher_version_admin WHERE admin_id = ?', $_SESSION['user_id']);
			} else {
				exec_query(
					'
						INSERT INTO php_switcher_version_admin (version_id, admin_id)
						SELECT
							php_switcher_version.version_id, ?
						FROM
							php_switcher_version
						WHERE
							php_switcher_version.version_id = ?
						AND
							php_switcher_version.version_status = ?
						ON DUPLICATE KEY UPDATE
							version_id = php_switcher_version.version_id
					',
					array($_SESSION['user_id'], $versionId, 'ok')
				);
			}

			/** @var PluginManager $pluginManager */
			$pluginManager = Registry::get('pluginManager');

			/** @var PhpSwitcher $plugin */
			$plugin = $pluginManager->pluginGet('PhpSwitcher');

			$plugin->scheduleDomainsChange(array($_SESSION['user_id']));

			$db->commit();

			send_request();

			write_log('%s updated its PHP version.', decode_idna($_SESSION['user_logged']), E_USER_NOTICE);
			set_page_message(tr('PHP version successfully scheduled for update. Please be patient.'), 'success');
		} catch(DatabaseException $e) {
			$db->rollBack();
			set_page_message(tr('An unexpected error occurred.', 'error'));
		}
	} else {
		showBadRequestErrorPage();
	}
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl
 */
function generatePage($tpl)
{
	$stmt = exec_query(
		'
			SELECT
				t1.version_name, t1.version_id, IFNULL(t2.version_id, 0) AS current_version
			FROM
				php_switcher_version AS t1
			LEFT JOIN
				php_switcher_version_admin AS t2 ON(t2.admin_id = ?)
			WHERE
				version_status = ?
		',
		array($_SESSION['user_id'], 'ok')
	);

	$rows = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);

	$selectedVersion = 0;
	$versions = array();

	foreach($rows as $version => $data) {
		$selectedVersion = $data[0]['current_version'];
		$versions[$version] = array('version_id' => $data[0]['version_id']);
	}

	$versions['PHP' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION] = array(
		'version_id' => 0
	);

	ksort($versions);

	foreach($versions as $version => $data) {
		$tpl->assign(
			array(
				'VERSION_ID' => $data['version_id'],
				'VERSION_NAME' => tohtml($version),
				'SELECTED' => ((int)$data['version_id'] == $selectedVersion) ? ' selected' : ''
			)
		);

		$tpl->parse('VERSION_OPTION', '.version_option');
	}
}

/***********************************************************************************************************************
 * Main
 */

EventManager::getInstance()->dispatch(Events::onClientScriptStart);
check_login('user');

if(customerHasFeature('php')) {
	if(isset($_POST['version_id'])) {
		changePhpVersion();
		redirectTo('/client/phpswitcher');
	}

	$tpl = new TemplateEngine();
	$tpl->define_dynamic(array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => '../../plugins/PhpSwitcher/themes/default/view/client/page.tpl',
		'page_message' => 'layout',
		'version_option' => 'page'
	));

	$tpl->assign(array(
		'TR_PAGE_TITLE' => tr('Client / Settings / PHP Switcher'),
		'TR_HINT' => tr('Please choose the PHP version you want use below.'),
		'TR_VERSION' => tr('Version'),
		'TR_UPDATE' => tr('Update')
	));

	generateNavigation($tpl);
	generatePageMessage($tpl);
	generatePage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');
	EventManager::getInstance()->dispatch(Events::onClientScriptEnd, array('templateEngine' => $tpl));
	$tpl->prnt();
} else {
	showNotFoundErrorPage();
}
