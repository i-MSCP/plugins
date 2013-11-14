<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2013 by i-MSCP Team
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
 * @copyright   2010-2013 by i-MSCP Team
 * @author      Sascha Bay <info@space2place.de>
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
 * @param int $resellerId
 * @return void
 */
function jailkit_generateSelect($tpl, $resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "
		SELECT
			`admin_id`, `admin_name`
		FROM
			`admin`
		WHERE
			`created_by` = ?
		AND
			`admin_status` = ?
		AND
			`admin_id` NOT IN (SELECT `admin_id` FROM `jailkit`)
		ORDER BY
			`admin_name` ASC
	";
	$stmt = exec_query($query, array($resellerId, $cfg->ITEM_OK_STATUS));

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
 * Generate activated customer list
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

	$countQuery = "
		SELECT
			COUNT(`t1`.`admin_id`) AS `cnt`
		FROM 
			`admin` AS `t1`
		LEFT JOIN
			`jailkit` AS `t2` ON(`t2`.`admin_id` = `t1`.`admin_id`)
		WHERE
			`t1`.`created_by` = ?
		AND
			`t1`.`admin_id` IN (SELECT `admin_id` FROM `jailkit`)
	";
	$stmt = exec_query($countQuery, $resellerId);

	$recordsCount = $stmt->fields['cnt'];

	$query = "
		SELECT
			`t2`.*
		FROM
			`admin` AS `t1`
		LEFT JOIN
			`jailkit` AS `t2` ON(`t2`.`admin_id` = `t1`.`admin_id`)
		WHERE
			`t1`.`created_by` = ?
		AND
			`t1`.`admin_id` IN (SELECT `admin_id` FROM `jailkit`)
		ORDER BY
			`t2`.`admin_name` ASC
		LIMIT
			$startIndex, $rowsPerPage
	";
	$stmt = exec_query($query, $resellerId);

	if ($recordsCount > 0) {
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
			} elseif ($data['jailkit_status'] == $cfg->ITEM_DISABLED_STATUS) {
				$statusIcon = 'disabled';
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
			} else {
				$statusIcon = 'error';
			}

			$tpl->assign(
				array(
					'JAILKIT_CUSTOMER_NAME' => decode_idna($data['admin_name']),
					'JAILKIT_STATUS' => translate_dmn_status($data['jailkit_status']),
					'JAILKIT_LOGIN_LIMIT' => get_jailkitLoginLimit($data['admin_id']),
					'JAILKIT_ADMIN_ID' => $data['admin_id'],
					'STATUS_ICON' => $statusIcon
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
				'SCROLL_NEXT_GRAY' => '',
			)
		);
	}

	$tpl->assign('JAILKIT_EDIT', '');
}

/**
 * Login limit
 *
 * @param int $customerAdminId
 * @return string
 */
function get_jailkitLoginLimit($customerAdminId)
{
	$countQuery = "
		SELECT
			COUNT(`jailkit_login_id`) AS `cnt`
		FROM 
			`jailkit_login`
		WHERE
			`admin_id` = ?
	";
	$stmt = exec_query($countQuery, $customerAdminId);
	$recordsCount = $stmt->fields['cnt'];

	$query = "SELECT `max_logins` FROM `jailkit` WHERE `admin_id` = ?";
	$stmt = exec_query($query, $customerAdminId);

	return $recordsCount . ' of ' . (($stmt->fields['max_logins'] == 0) ? '<b>unlimited</b>' : $stmt->fields['max_logins']);
}

/**
 * Activate customer
 *
 * @param $pluginManager iMSCP_Plugin_Manager
 * @param $customerAdminId
 * @param $resellerId
 */
function jailkit_activateCustomer($pluginManager, $customerAdminId, $resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = '
		SELECT `admin_id`, `admin_name` FROM `admin` WHERE `admin_id` = ? AND `created_by` = ? AND `admin_status` = ?
	';
	$stmt = exec_query($query, array($customerAdminId, $resellerId, $cfg->ITEM_OK_STATUS));

	if (($plugin = $pluginManager->loadPlugin('JailKit', false, false)) !== null) {
		$pluginConfig = $plugin->getConfig();
	} else {
		set_page_message(
			tr("Can't load plugin configuration!"), 'error'
		);
		redirectTo('jailkit.php');
		exit;
	}

	if ($stmt->rowCount()) {
		while ($data = $stmt->fetchRow()) {
			$query = '
				INSERT INTO `jailkit` (
					`admin_id`, `admin_name`, `max_logins`, `jailkit_status`
				) VALUES (
					?, ?, ?, ?
				)
			';
			exec_query(
				$query,
				array(
					$data['admin_id'], $data['admin_name'], $pluginConfig['max_allowed_ssh-user'],
					$cfg->ITEM_TOADD_STATUS
				)
			);
		}

		send_request();

		set_page_message(tr('Customer activated for JailKit support. This can take few seconds.'), 'success');
	} else {
		set_page_message(tr("The customer you are trying to activate JailKit doesn't exist."), 'error');
	}

	redirectTo('jailkit.php');
}

/**
 * Change customer jail
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $customerAdminId
 * @param int $resellerId
 */
function jailkit_changeCustomerJail($tpl, $customerAdminId, $resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = '
		SELECT `admin_id`, `admin_name` FROM `admin` WHERE `admin_id` = ? AND `created_by` = ? AND `admin_status` = ?
	';
	$stmt = exec_query($query, array($customerAdminId, $resellerId, $cfg->ITEM_OK_STATUS));

	if ($stmt->rowCount()) {
		$query = 'SELECT `max_logins` FROM `jailkit` WHERE `admin_id` = ?';
		$stmt2 = exec_query($query, $customerAdminId);

		if (isset($_POST['max_logins']) && $_POST['max_logins'] != '') {
			$maxLogins = clean_input($_POST['max_logins']);

			if ($maxLogins >= 0) {
				if ($maxLogins != $stmt2->fields['max_logins']) {
					exec_query(
						'UPDATE `jailkit` SET `max_logins` = ? WHERE `admin_id` = ?',
						array($maxLogins, $customerAdminId)
					);

					set_page_message(tr('Max logins succesfully changed.'), 'success');
				}

				redirectTo('jailkit.php');
			} else {
				set_page_message(tr("Invalid input for max logins."), 'error');
			}
		}

		$tpl->assign(
			array(
				'TR_PAGE_TITLE' => tr('Customers / Edit JailKit - SSH for customer: %s', decode_idna($stmt->fields['admin_name'])),
				'TR_JAIL_LIMITS' => tr('Jail - SSH limits for customer: %s', decode_idna($stmt->fields['admin_name'])),
				'MAX_LOGINS' => $stmt2->fields['max_logins'],
				'JAILKIT_ADMIN_ID' => $customerAdminId
			)
		);
	} else {
		redirectTo('jailkit.php');
	}

	$tpl->assign('JAILKIT_LIST', '');
}

/**
 * Deactivate customer
 *
 * @param int $customerAdminId
 * @param int $resellerId
 */
function jailkit_deactivateCustomer($customerAdminId, $resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = '
		SELECT `admin_id`, `admin_name` FROM `admin` WHERE `admin_id` = ? AND `created_by` = ? AND `admin_status` = ?
	';
	$stmt = exec_query($query, array($customerAdminId, $resellerId, $cfg->ITEM_OK_STATUS));

	if ($stmt->rowCount()) {
		exec_query(
			'UPDATE `jailkit` SET `jailkit_status` = ? WHERE `admin_id` = ?',
			array($cfg->ITEM_TODELETE_STATUS, $customerAdminId)
		);

		send_request();

		set_page_message(tr('Customer deactivated for JailKit support. This can take few seconds.'), 'success');
	} else {
		set_page_message(tr("The customer you are trying to deactivate JailKit doesn't exist."), 'error');
	}

	redirectTo('jailkit.php');
}

/**
 * Change permissions
 *
 * @param int $customerAdminId
 * @param int $resellerId
 */
function jailkit_changeCustomerPermission($customerAdminId, $resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = '
		SELECT `admin_id`, `admin_name` FROM `admin` WHERE `admin_id` = ? AND `created_by` = ? AND `admin_status` = ?
	';
	$stmt = exec_query($query, array($customerAdminId, $resellerId, $cfg->ITEM_OK_STATUS));

	if ($stmt->rowCount()) {
		$query = "SELECT `admin_id`, `admin_name`, `jailkit_status` FROM `jailkit` WHERE `admin_id` = ?";
		$stmt = exec_query($query, $customerAdminId);

		if ($stmt->rowCount() && $stmt->fields['jailkit_status'] == $cfg->ITEM_DISABLED_STATUS) {
			exec_query(
				'UPDATE `jailkit` SET `jailkit_status` = ? WHERE `admin_id` = ?',
				array($cfg->ITEM_OK_STATUS, $customerAdminId)
			);

			exec_query(
				'UPDATE `jailkit_login` SET `ssh_login_locked` = ?, `jailkit_login_status` = ? WHERE `admin_id` = ?',
				array('0', $cfg->ITEM_TOCHANGE_STATUS, $customerAdminId)
			);

			send_request();

			set_page_message(tr('Customer enabled for JailKit support. This can take few seconds.'), 'success');
		} elseif ($stmt->rowCount() && $stmt->fields['jailkit_status'] == $cfg->ITEM_OK_STATUS) {
			exec_query(
				'UPDATE `jailkit` SET `jailkit_status` = ? WHERE `admin_id` = ?',
				array($cfg->ITEM_DISABLED_STATUS, $customerAdminId)
			);

			exec_query(
				'UPDATE `jailkit_login` SET `ssh_login_locked` = ?, `jailkit_login_status` = ? WHERE `admin_id` = ?',
				array('1', $cfg->ITEM_TOCHANGE_STATUS, $customerAdminId)
			);

			send_request();

			set_page_message(tr('Customer disabled for JailKit support. This can take few seconds.'), 'success');
		}
	} else {
		set_page_message(tr("The customer you are trying to change the permission of JailKit doesn't exist."), 'error');
	}

	redirectTo('jailkit.php');
}

/***********************************************************************************************************************
 * Main
 */

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

if (iMSCP_Registry::isRegistered('pluginManager')) {
	/** @var iMSCP_Plugin_Manager $pluginManager */
	$pluginManager = iMSCP_Registry::get('pluginManager');
} else {
	throw new iMSCP_Plugin_Exception('An unexpected error occured');
}

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
		$customerAdminId = (isset($_POST['admin_id']) && $_POST['admin_id'] !== '-1') ? clean_input($_POST['admin_id']) : '';

		if ($customerAdminId != '') {
			jailkit_activateCustomer($pluginManager, $customerAdminId, $_SESSION['user_id']);
		}
	} elseif ($action == 'change') {
		$customerAdminId = (isset($_GET['admin_id'])) ? clean_input($_GET['admin_id']) : '';

		if ($customerAdminId != '') {
			jailkit_changeCustomerPermission($customerAdminId, $_SESSION['user_id']);
		}
	} elseif ($action == 'edit') {
		$customerAdminId = ($_GET['admin_id'] !== '') ? (int)clean_input($_GET['admin_id']) : '';

		if ($customerAdminId != '') {
			jailkit_changeCustomerJail($tpl, $customerAdminId, $_SESSION['user_id']);
		}
	} elseif ($action == 'delete') {
		$customerAdminId = (isset($_GET['admin_id'])) ? clean_input($_GET['admin_id']) : '';

		if ($customerAdminId != '') {
			jailkit_deactivateCustomer($customerAdminId, $_SESSION['user_id']);
		}
	}
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Customers / JailKit - SSH'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'CUSTOMER_NOT_SELECTED' => tr("No customer selected."),
		'TR_JAILKIT_SELECT_NAME_NONE' => tr('Select a customer'),
		'TR_SHOW' => tr('Activate JailKit - SSH for this customer'),
		'TR_UPDATE' => tr('Update'),
		'TR_CANCEL' => tr('Cancel'),
		'TR_JAILKIT_CUSTOMER_NAME' => tr('Customer'),
		'TR_JAILKIT_NO_CUSTOMER' => tr('JailKit customer entries'),
		'JAILKIT_NO_CUSTOMER' => tr('No customer for JailKit support activated'),
		'TR_JAILKIT_STATUS' => tr('Status'),
		'TR_JAILKIT_LOGIN_LIMIT' => tr('Login limit'),
		'DEACTIVATE_CUSTOMER_ALERT' => tr('Are you sure? You want to deactivate JailKit for this customer?'),
		'DISABLE_CUSTOMER_ALERT' => tr('Are you sure? You want to disable all JailKit ssh logins for this customer?'),
		'TR_PREVIOUS' => tr('Previous'),
		'TR_JAILKIT_ACTIONS' => tr('Actions'),
		'TR_EDIT_JAIL' => tr('Edit Customer Jail'),
		'TR_DELETE_JAIL' => tr('Delete Customer Jail'),
		'TR_LIMIT_VALUE' => tr('Limit value'),
		'TR_MAX_LOGINS_LIMIT' => tr('Max creating logins') . '<br /><i>(0 ' . tr('unlimited') . ')</i>',
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
