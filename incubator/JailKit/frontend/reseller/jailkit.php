<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) Sascha Bay <info@space2place.de>
 * Copyright (C) Laurent Declercq <l.declercq@nuxwin.com>
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
 * @subpackage  JailKit
 * @copyright   Sascha Bay <info@space2place.de>
 * @copyright   Laurent Declercq <l.declercq@nuxwin.com>
 * @author      Sascha Bay <info@space2place.de>
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate page
 *
 * @param $tpl iMSCP_pTemplate
 * @param int $resellerId Reseller unique identifier
 * @return void
 */
function jailkit_generateSelect($tpl, $resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$stmt = exec_query(
		'
			SELECT
				t1.admin_id, t1.admin_name
			FROM
				admin as t1
			INNER JOIN
				jailkit as t2 ON(t1.admin_id <> t2.admin_id)
			WHERE
				t1.created_by = ?
			AND
				t1.admin_status = ?
			ORDER BY
				t1.admin_name ASC
		',
		array($resellerId, $cfg->ITEM_OK_STATUS)
	);

	if ($stmt->rowCount()) {
		while ($data = $stmt->fetchRow()) {
			$tpl->assign(
				array(
					'TR_JAILKIT_SELECT_VALUE' => $data['admin_id'],
					'TR_JAILKIT_SELECT_NAME' => decode_idna($data['admin_name']),
				)
			);

			$tpl->parse('JAILKIT_SELECT_ITEM', '.jailkit_select_item');
		}
	} else {
		$tpl->assign('JAILKIT_SELECT_ITEM', '');
	}
}

/**
 * Generate list of customers for which SSH support is activated
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $resellerId Reseller unique identifier
 */
function jailkit_generateActivatedCustomers($tpl, $resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$rowsPerPage = $cfg->DOMAIN_ROWS_PER_PAGE;

	if (isset($_GET['psi']) && $_GET['psi'] == 'last') {
		unset($_GET['psi']);
	}

	$startIndex = isset($_GET['psi']) ? (int)$_GET['psi'] : 0;

	$stmt = exec_query(
		'SELECT COUNT(admin_id) AS cnt FROM admin INNER JOIN jailkit USING(admin_id) WHERE created_by = ?', $resellerId
	);
	$recordsCount = $stmt->fields['cnt'];

	if ($recordsCount > 0) {
		$stmt = exec_query(
			"
				SELECT
					jailkit.*
				FROM
					admin
				INNER JOIN
					jailkit USING(admin_id)
				WHERE
					created_by = ?
				ORDER BY
					admin_name ASC
				LIMIT
					$startIndex, $rowsPerPage
			",
			$resellerId
		);

		$prevSi = $startIndex - $rowsPerPage;

		if ($startIndex == 0) {
			$tpl->assign('SCROLL_PREV', '');
		} else {
			$tpl->assign(
				array(
					'SCROLL_PREV_GRAY' => '',
					'PREV_PSI' => $prevSi
				)
			);
		}

		$nextSi = $startIndex + $rowsPerPage;

		if ($nextSi + 1 > $recordsCount) {
			$tpl->assign('SCROLL_NEXT', '');
		} else {
			$tpl->assign(
				array(
					'SCROLL_NEXT_GRAY' => '',
					'NEXT_PSI' => $nextSi
				)
			);
		}

		while ($data = $stmt->fetchRow()) {
			if ($data['jailkit_status'] == $cfg->ITEM_OK_STATUS) {
				$statusIcon = 'ok';
				$tooltip = tr('Deactivate all SSH accounts owned by this customer');
			} elseif ($data['jailkit_status'] == $cfg->ITEM_DISABLED_STATUS) {
				$statusIcon = 'disabled';
				$tooltip = tr('Activate all SSH accounts owned by this customer');
			} elseif (
				(
					$data['jailkit_status'] == $cfg->ITEM_TOADD_STATUS ||
					$data['jailkit_status'] == $cfg->ITEM_TOCHANGE_STATUS ||
					$data['jailkit_status'] == $cfg->ITEM_TODELETE_STATUS
				) ||
				(
					$data['jailkit_status'] == $cfg->ITEM_TOADD_STATUS ||
					$data['jailkit_status'] == $cfg->ITEM_TORESTORE_STATUS ||
					$data['jailkit_status'] == $cfg->ITEM_TOCHANGE_STATUS ||
					$data['jailkit_status'] == $cfg->ITEM_TOENABLE_STATUS ||
					$data['jailkit_status'] == $cfg->ITEM_TODISABLE_STATUS ||
					$data['jailkit_status'] == $cfg->ITEM_TODELETE_STATUS
				)
			) {

				$statusIcon = 'reload';
				$tooltip = translate_dmn_status($data['jailkit_status']);
			} else {
				$statusIcon = 'error';
				$tooltip = translate_dmn_status($data['jailkit_status']);
			}

			$tpl->assign(
				array(
					'JAILKIT_CUSTOMER_NAME' => decode_idna($data['admin_name']),
					'JAILKIT_STATUS' => translate_dmn_status($data['jailkit_status']),
					'JAILKIT_LOGIN_LIMIT' => get_jailkitLoginLimit($data['admin_id']),
					'JAILKIT_ADMIN_ID' => $data['admin_id'],
					'STATUS_ICON' => $statusIcon,
					'TOOLTIP_STATUS_ACTION' => $tooltip
				)
			);

			$tpl->parse('JAILKIT_CUSTOMER_ITEM', '.jailkit_customer_item');
		}

		$tpl->assign('JAILKIT_NO_CUSTOMER_ITEM', '');
	} else {
		$tpl->assign(
			array(
				'JAILKIT_CUSTOMER_LIST' => '',
				'SCROLL_PREV' => '',
				'SCROLL_PREV_GRAY' => '',
				'SCROLL_NEXT' => '',
				'SCROLL_NEXT_GRAY' => ''
			)
		);
	}

	$tpl->assign('JAILKIT_EDIT', '');
}

/**
 * Return SSH account limit for the given customer
 *
 * @param int $customerId Customer unique identifier
 * @return string
 */
function get_jailkitLoginLimit($customerId)
{
	$stmt = exec_query('SELECT COUNT(jailkit_login_id) AS cnt FROM jailkit_login WHERE admin_id = ?', $customerId);
	$recordsCount = $stmt->fields['cnt'];

	$stmt = exec_query('SELECT max_logins FROM jailkit WHERE admin_id = ?', $customerId);

	return $recordsCount . ' / ' . (($stmt->fields['max_logins'] == 0)
		? '<b>unlimited</b>' : $stmt->fields['max_logins']);
}

/**
 * Activate SSH support for the given customer
 *
 * @param $pluginManager iMSCP_Plugin_Manager
 * @param int $customerId Customer unique identifier
 * @param int $resellerId Reseller unique identifier
 * @return void
 */
function jailkit_activateSsh($pluginManager, $customerId, $resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$stmt = exec_query(
		'SELECT admin_id, admin_name FROM admin WHERE admin_id = ? AND created_by = ? AND admin_status = ?',
		array($customerId, $resellerId, $cfg->ITEM_OK_STATUS)
	);

	if (($plugin = $pluginManager->loadPlugin('JailKit', false, false)) !== null) {
		$pluginConfig = $plugin->getConfig();
	} else {
		set_page_message(tr("An unexpected error occured. Please contact your administrator."), 'error');
		write_log('Unable to load JailKit plugin configuration', E_USER_ERROR);
		redirectTo('jailkit.php');
		exit;
	}

	if ($stmt->rowCount()) {
		$data = $stmt->fetchRow();

		exec_query(
			'INSERT INTO jailkit (admin_id, admin_name, max_logins, jailkit_status) VALUES (?, ?, ?, ?)',
			array(
				$data['admin_id'], $data['admin_name'], $pluginConfig['max_allowed_ssh_user'], $cfg->ITEM_TOADD_STATUS
			)
		);

		send_request();
		set_page_message(tr('SSH support scheduled for activation. This can take few seconds.'), 'success');
	} else {
		showBadRequestErrorPage();
	}

	redirectTo('jailkit.php');
}

/**
 * Deactivate SSH support for the given customer
 *
 * @param int $customerId Customer unique identifier
 * @param int $resellerId Reseller unique identifier
 * @return void
 */
function jailkit_deactivateSsh($customerId, $resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$stmt = exec_query(
		'SELECT admin_id, admin_name FROM admin WHERE admin_id = ? AND created_by = ? AND admin_status = ?',
		array($customerId, $resellerId, $cfg->ITEM_OK_STATUS)
	);

	if ($stmt->rowCount()) {
		exec_query(
			'UPDATE jailkit SET jailkit_status = ? WHERE admin_id = ?', array($cfg->ITEM_TODELETE_STATUS, $customerId)
		);

		send_request();
		set_page_message(tr('SSH support scheduled for deactivation. This can take few seconds.'), 'success');
	} else {
		showBadRequestErrorPage();
	}

	redirectTo('jailkit.php');
}

/**
 * Change customer jail
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $customerId Customer unique identifier
 * @param int $resellerId Reseller unique identifier
 * @return void
 */
function jailkit_changeCustomerJail($tpl, $customerId, $resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$stmt = exec_query(
		'SELECT admin_id, admin_name FROM admin WHERE admin_id = ? AND created_by = ? AND admin_status = ?',
		array($customerId, $resellerId, $cfg->ITEM_OK_STATUS)
	);

	if ($stmt->rowCount()) {
		$stmt2 = exec_query('SELECT max_logins FROM jailkit WHERE admin_id = ?', $customerId);

		if (isset($_POST['max_logins']) && $_POST['max_logins'] != '') {
			$maxLogins = clean_input($_POST['max_logins']);

			if ($maxLogins >= 0) {
				if ($maxLogins != $stmt2->fields['max_logins']) {
					exec_query('UPDATE jailkit SET max_logins = ? WHERE admin_id = ?', array($maxLogins, $customerId));
					set_page_message(tr('SSH account limit succesfully updated.'), 'success');
				}

				redirectTo('jailkit.php');
			} else {
				set_page_message(tr("Invalid SSH account limit."), 'error');
			}
		}

		$tpl->assign(
			array(
				'TR_CUSTOMER' => tr('%s', decode_idna($stmt->fields['admin_name'])),
				'MAX_LOGINS' => $stmt2->fields['max_logins'],
				'JAILKIT_ADMIN_ID' => $customerId
			)
		);
	} else {
		showBadRequestErrorPage();
	}

	$tpl->assign('JAILKIT_LIST', '');
}

/**
 * Change permissions
 *
 * @param int $customerId Customer unique identifier
 * @param int $resellerId Reseller unique identifier
 * @return void
 */
function jailkit_changeCustomerPermission($customerId, $resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$stmt = exec_query(
		'SELECT admin_id, admin_name FROM admin WHERE admin_id = ? AND created_by = ? AND admin_status = ?',
		array($customerId, $resellerId, $cfg->ITEM_OK_STATUS)
	);

	if ($stmt->rowCount()) {
		$stmt = exec_query('SELECT admin_id, admin_name, jailkit_status FROM jailkit WHERE admin_id = ?', $customerId);

		if ($stmt->rowCount() && $stmt->fields['jailkit_status'] == $cfg->ITEM_DISABLED_STATUS) {
			exec_query(
				'UPDATE jailkit SET jailkit_status = ? WHERE admin_id = ?', array($cfg->ITEM_OK_STATUS, $customerId)
			);

			exec_query(
				'UPDATE jailkit_login SET ssh_login_locked = ?, jailkit_login_status = ? WHERE admin_id = ?',
				array('0', $cfg->ITEM_TOCHANGE_STATUS, $customerId)
			);

			send_request();
			set_page_message(tr('SSH support scheduled for activation. This can take few seconds.'), 'success');
		} elseif ($stmt->rowCount() && $stmt->fields['jailkit_status'] == $cfg->ITEM_OK_STATUS) {
			exec_query(
				'UPDATE jailkit SET jailkit_status = ? WHERE admin_id = ?',
				array($cfg->ITEM_DISABLED_STATUS, $customerId)
			);

			exec_query(
				'UPDATE jailkit_login SET ssh_login_locked = ?, jailkit_login_status = ? WHERE admin_id = ?',
				array('1', $cfg->ITEM_TOCHANGE_STATUS, $customerId)
			);

			send_request();
			set_page_message(tr('SSH support scheduled for deactivation. This can take few seconds.'), 'success');
		}
	} else {
		showBadRequestErrorPage();
	}

	redirectTo('jailkit.php');
}

/***********************************************************************************************************************
 * Main
 */

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => '../../plugins/JailKit/frontend/reseller/jailkit.tpl',
		'page_message' => 'layout',
		'jailkit_list' => 'page',
		'jailkit_edit' => 'page',
		'jailkit_select_item' => 'page',
		'jailkit_customer_list' => 'page',
		'jailkit_customer_item' => 'page',
		'jailkit_no_customer_item' => 'page',
		'scroll_prev_gray' => 'jailkit_customer_list',
		'scroll_prev' => 'jailkit_customer_list',
		'scroll_next_gray', 'jailkit_customer_list',
		'scroll_next' => 'jailkit_customer_list'
	)
);

if (isset($_REQUEST['action'])) {
	$action = clean_input($_REQUEST['action']);

	if ($action == 'activate') {
		$customerId = (isset($_POST['admin_id']) && $_POST['admin_id'] !== '-1') ? clean_input($_POST['admin_id']) : '';

		if ($customerId != '') {
			jailkit_activateSsh($pluginManager, $customerId, $_SESSION['user_id']);
		}
	} elseif ($action == 'change') {
		$customerId = (isset($_GET['admin_id'])) ? clean_input($_GET['admin_id']) : '';

		if ($customerId != '') {
			jailkit_changeCustomerPermission($customerId, $_SESSION['user_id']);
		}
	} elseif ($action == 'edit') {
		$customerId = ($_GET['admin_id'] !== '') ? (int)clean_input($_GET['admin_id']) : '';

		if ($customerId != '') {
			jailkit_changeCustomerJail($tpl, $customerId, $_SESSION['user_id']);
		}
	} elseif ($action == 'delete') {
		$customerId = (isset($_GET['admin_id'])) ? clean_input($_GET['admin_id']) : '';

		if ($customerId != '') {
			jailkit_deactivateSsh($customerId, $_SESSION['user_id']);
		}
	}
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Reseller / Customers - SSH Accounts'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'CUSTOMER_NOT_SELECTED' => tr("No customer selected."),
		'TR_JAILKIT_SELECT_NAME_NONE' => tr('Select a customer'),
		'TR_SELECT_ACTION' => tr('Activate SSH support'),
		'TR_SELECT_ACTION_TOOLTIP' => tr('Activate SSH support for the selected customer'),
		'TR_UPDATE' => tr('Update'),
		'TR_CANCEL' => tr('Cancel'),
		'TR_JAILKIT_CUSTOMER_NAME' => tr('Customer'),
		'TR_JAILKIT_NO_CUSTOMER' => tr('Customer SSH accounts'),
		'JAILKIT_NO_CUSTOMER' => tr('No customer with SSH support has been found'),
		'TR_JAILKIT_STATUS' => tr('Status'),
		'TR_JAILKIT_LOGIN_LIMIT' => tr('SSH accounts'),
		'DEACTIVATE_CUSTOMER_ALERT' => tr('Are you sure you want to deactivate the SSH support for this customer?'),
		'DISABLE_CUSTOMER_ALERT' => tr('Are you sure you want to deactivate all SSH accounts for this customer?'),
		'TR_PREVIOUS' => tr('Previous'),
		'TR_JAILKIT_ACTIONS' => tr('Actions'),
		'TR_EDIT_JAIL' => tr('Edit'),
		'TR_EDIT_TOOLTIP' => tr('Edit SSH account limit'),
		'TR_DELETE_JAIL' => tr('Deactivate'),
		'TR_DEACTIVATE_TOOLTIP' => tr('Deactivate SSH support for this customer'),
		'TR_MAX_SSH_ACCOUNTS' => tr('Max. SSH accounts') . '<br /><i>(0 ' . tr('unlimited') . ')</i>',
		'TR_NEXT' => tr('Next')
	)
);

generateNavigation($tpl);

if (!isset($_GET['action'])) {
	jailkit_generateSelect($tpl, $_SESSION['user_id']);
	jailkit_generateActivatedCustomers($tpl, $_SESSION['user_id']);
}

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
