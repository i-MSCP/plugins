<?php
/**
 * i-MSCP PhpSwitcher plugin
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
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Schedule PHP version update for all customer 's domains
 *
 * @return void
 */
function _phpSwitcher_scheduleChange()
{
	$adminId = $_SESSION['user_id'];

	exec_query(
		'UPDATE domain set domain_status = ? WHERE domain_admin_id = ? AND domain_status = ?',
		array('tochange', $adminId, 'ok')
	);

	exec_query(
		'
			UPDATE
				subdomain
			JOIN
				domain USING(domain_id)
			SET
				subdomain_status = ?
			WHERE
				domain_admin_id = ?
			AND
				subdomain_status = ?
		',
		array('tochange', $adminId, 'ok')
	);

	exec_query(
		'
			UPDATE
				domain_aliasses
			JOIN
				domain USING(domain_id)
			SET
				alias_status = ?
			WHERE
				domain_admin_id = ?
			AND
				alias_status = ?
		',
		array('tochange', $adminId, 'ok')
	);

	exec_query(
		'
			UPDATE
				subdomain_alias
			JOIN
				domain_aliasses USING(alias_id)
			SET
				subdomain_alias_status = ?
			WHERE
				domain_id = (SELECT domain_id FROM domain where domain_admin_id = ?)
			AND
				subdomain_alias_status = ?
		',
		array('tochange', $adminId, 'ok')
	);
}

/**
 * Set PHP version
 *
 * @return void
 */
function phpSwitcher_setVersion()
{
	if (isset($_POST['version_id'])) {
		$versionId = intval($_POST['version_id']);

		try {
			iMSCP_Database::getRawInstance()->beginTransaction();

			if (!$versionId) {
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

			_phpSwitcher_scheduleChange();

			iMSCP_Database::getRawInstance()->commit();

			//send_request();
			set_page_message(tr('PHP version successfully scheduled for update.'), 'success');
		} catch (iMSCP_Exception_Database $e) {
			iMSCP_Database::getRawInstance()->rollBack();
			set_page_message(tr('An unexpected error occured.', 'error'));
		}
	} else {
		showBadRequestErrorPage();
	}
}

/**
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl
 */
function phpSwitcher_generatePage($tpl)
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

	foreach ($rows as $version => $data) {
		$selectedVersion = $data[0]['current_version'];
		$versions[$version] = array(
			'version_id' => $data[0]['version_id'],
		);
	}

	$versions['PHP' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION] = array('version_id' => 0);
	ksort($versions, SORT_NATURAL);

	foreach ($versions as $version => $data) {
		$tpl->assign(
			array(
				'VERSION_ID' => $data['version_id'],
				'VERSION_NAME' => tohtml($version),
				'SELECTED' => ((int)$data['version_id'] == $selectedVersion) ? ' selected=selected' : ''
			)
		);

		$tpl->parse('VERSION_OPTION', '.version_option');
	}
}

/***********************************************************************************************************************
 * Main
 */

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

if (customerHasFeature('php')) {
	if (isset($_POST['version_id'])) {
		phpSwitcher_setVersion();
		redirectTo('/client/phpswitcher');
	}

	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => '../../plugins/PhpSwitcher/themes/default/view/client/page.tpl',
			'page_message' => 'layout',
			'version_option' => 'page'
		)
	);

	$tpl->assign(
		array(
			'THEME_CHARSET' => tr('encoding'),
			'TR_PAGE_TITLE' => tr('Admin / Settings / PHP Switcher'),
			'ISP_LOGO' => layout_getUserLogo(),
			'TR_HINT' => tr('Please choose the PHP version you want use below.'),
			'TR_VERSION' => tr('VERSION'),
			'TR_UPDATE' => tr('Update'),
		)
	);

	generateNavigation($tpl);
	generatePageMessage($tpl);
	phpSwitcher_generatePage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');

	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

	$tpl->prnt();
} else {
	showNotFoundErrorPage();
}
