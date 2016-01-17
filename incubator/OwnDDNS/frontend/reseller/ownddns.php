<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 Sascha Bay <info@space2place.de>
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
 * @subpackage  OwnDDNS
 * @copyright   Sascha Bay <info@space2place.de>
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
 * @param iMSCP_Plugin_Manager $pluginManager
 * @param int $resellerId
 * @param int $customerAdminId
 * @return void
 */
function ownddns_generateSelect($tpl, $resellerId)
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
			`admin_id` NOT IN (SELECT `admin_id` FROM `ownddns`)
		ORDER BY
			`admin_name` ASC
	";
	
	$stmt = exec_query($query, array($resellerId, $cfg->ITEM_OK_STATUS));
	
	if ($stmt->rowCount()) {
		while ($data = $stmt->fetchRow()) {
			$tpl->assign(
				array(
					'TR_OWNDDNS_SELECT_VALUE' => $data['admin_id'],
					'TR_OWNDDNS_SELECT_NAME' => decode_idna($data['admin_name']),
					)
				);

			$tpl->parse('OWNDDNS_SELECT_ITEM', '.ownddns_select_item');
		}
	} else {
		$tpl->assign('OWNDDNS_SELECT_ITEM', '');
	}
}

function ownddns_generateActivatedCustomers($tpl, $resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	
	$rowsPerPage = $cfg->DOMAIN_ROWS_PER_PAGE;
	
	if (isset($_GET['psi']) && $_GET['psi'] == 'last') {
		unset($_GET['psi']);
	}
	
	$startIndex = isset($_GET['psi']) ? (int)$_GET['psi'] : 0;
	
	$countQuery = "
		SELECT COUNT(`t1`.`admin_id`) AS `cnt` 
		FROM 
			`admin` AS `t1`
		LEFT JOIN
			`ownddns` AS `t2` ON(`t2`.`admin_id` = `t1`.`admin_id`)
		WHERE
			`t1`.`created_by` = ?
		AND
			`t1`.`admin_id` IN (SELECT `admin_id` FROM `ownddns`)
	";
		
	$stmt = exec_query($countQuery, $resellerId);
	$recordsCount = $stmt->fields['cnt'];

	$query = "
		SELECT
			`t2`.*
		FROM
			`admin` AS `t1`
		LEFT JOIN
			`ownddns` AS `t2` ON(`t2`.`admin_id` = `t1`.`admin_id`)
		WHERE
			`t1`.`created_by` = ?
		AND
			`t1`.`admin_id` IN (SELECT `admin_id` FROM `ownddns`)
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
			if($data['ownddns_status'] == $cfg->ITEM_OK_STATUS) $statusIcon = 'ok';
			elseif ($data['ownddns_status'] == $cfg->ITEM_DISABLED_STATUS) $statusIcon = 'disabled';
			elseif (
				(
					$data['ownddns_status'] == $cfg->ITEM_TOADD_STATUS ||
					$data['ownddns_status'] == $cfg->ITEM_TOCHANGE_STATUS ||
					$data['ownddns_status'] == $cfg->ITEM_TODELETE_STATUS
				) ||
				(
					$data['ownddns_status'] == $cfg->ITEM_TOADD_STATUS ||
					$data['ownddns_status'] == $cfg->ITEM_TORESTORE_STATUS ||
					$data['ownddns_status'] == $cfg->ITEM_TOCHANGE_STATUS ||
					$data['ownddns_status'] == $cfg->ITEM_TOENABLE_STATUS ||
					$data['ownddns_status'] == $cfg->ITEM_TODISABLE_STATUS ||
					$data['ownddns_status'] == $cfg->ITEM_TODELETE_STATUS
				)
			) {
				$statusIcon = 'reload';
			} else {
				$statusIcon = 'error';
			}
			
			$tpl->assign(
				array(
					'OWNDDNS_CUSTOMER_NAME' => decode_idna($data['admin_name']),
					'OWNDDNS_STATUS' => translate_dmn_status($data['ownddns_status']),
					'OWNDDNS_ACCOUNT_LIMIT' => get_ownddnsAccountLimit($data['admin_id']),
					'OWNDDNS_ADMIN_ID' => $data['admin_id'],
					'STATUS_ICON' => $statusIcon
				)
			);
			
			$tpl->parse('OWNDDNS_CUSTOMER_ITEM', '.ownddns_customer_item');
		}
		
		$tpl->assign('OWNDDNS_NO_CUSTOMER_ITEM', '');
	} else {
		$tpl->assign(
			array(
				'OWNDDNS_CUSTOMER_LIST' => '',
				'SCROLL_PREV' => '',
				'SCROLL_PREV_GRAY' => '',
				'SCROLL_NEXT' => '',
				'SCROLL_NEXT_GRAY' => '',
			)
		);
	}
	
	$tpl->assign('OWNDDNS_EDIT', '');
}

function get_ownddnsAccountLimit($customerAdminId)
{
	$countQuery = "
		SELECT COUNT(`ownddns_account_id`) AS `cnt` 
		FROM 
			`ownddns_accounts`
		WHERE
			`admin_id` = ?
	";
		
	$stmt = exec_query($countQuery, $customerAdminId);
	$recordsCount = $stmt->fields['cnt'];
	
	$query = "SELECT `max_ownddns_accounts` FROM `ownddns` WHERE `admin_id` = ?";	
	$stmt = exec_query($query, $customerAdminId);
	
	return $recordsCount . ' of ' . (($stmt->fields['max_ownddns_accounts'] == 0) ? '<b>unlimited</b>' : $stmt->fields['max_ownddns_accounts']);
}

function ownddns_activateCustomer($tpl, $pluginManager, $customerAdminId, $resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	
	$query = "
		SELECT
			`t1`.`admin_id`, `t1`.`admin_name`, `t2`.`domain_id`,
			`t2`.`domain_dns`
		FROM
			`admin` AS `t1`
		LEFT JOIN
			`domain` AS `t2` ON(`t2`.`domain_admin_id` = `t1`.`admin_id`)
		WHERE
			`t1`.`admin_id` = ?
		AND
			`t1`.`created_by` = ?
		AND
			`t1`.`admin_status` = ?
	";
	
	$stmt = exec_query($query, array($customerAdminId, $resellerId, $cfg->ITEM_OK_STATUS));
	
	if (($plugin = $pluginManager->loadPlugin('OwnDDNS', false, false)) !== null) {
		$pluginConfig = $plugin->getConfig();
	} else {
		set_page_message(
			tr("Can't load plugin configuration!"), 'error'
		);
		redirectTo('ownddns.php');
	}
	
	if ($stmt->rowCount()) {
		while ($data = $stmt->fetchRow()) {
			$query = "
				INSERT INTO
				    `ownddns` (
						`admin_id`, `domain_id`, `admin_name`,
						`max_ownddns_accounts`, `customer_dns_previous_status`, `ownddns_status`
					) VALUES (
						?, ?, ?,
						?, ?, ?
					)
			";
			exec_query(
				$query,
				array(
					$data['admin_id'], $data['domain_id'], $data['admin_name'], 
					$pluginConfig['max_allowed_accounts'], $data['domain_dns'], $cfg->ITEM_OK_STATUS
				)
			);
			
			exec_query('UPDATE `domain` SET `domain_dns` = ?, `domain_status` = ? WHERE `domain_admin_id` = ?', array('yes', 'tochange', $customerAdminId));
		}
		
		send_request();
		
		set_page_message(
			tr('Customer activated for OwnDDNS support. This can take a few seconds.'), 'success'
		);
	} else {
		set_page_message(
			tr("The customer you are trying to activate OwnDDNS for doesn't exist."), 'error'
		);
	}
	
	redirectTo('ownddns.php');
}

function ownddns_changeCustomerOwnDDNS($tpl, $customerAdminId, $resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	
	$query = "
		SELECT
			`admin_id`, `admin_name`
		FROM
			`admin`
		WHERE
			`admin_id` = ?
		AND
			`created_by` = ?
		AND
			`admin_status` = ?
	";
	
	$stmt = exec_query($query, array($customerAdminId, $resellerId, $cfg->ITEM_OK_STATUS));
	
	if ($stmt->rowCount()) {
		$query = "SELECT `max_ownddns_accounts` FROM `ownddns` WHERE `admin_id` = ?";	
		$stmt2 = exec_query($query, $customerAdminId);
		
		if(isset($_POST['max_ownddns_accounts']) && $_POST['max_ownddns_accounts'] != '') {
			$maxLogins = clean_input($_POST['max_ownddns_accounts']);
			if($maxLogins >= 0) {
				if($maxLogins != $stmt2->fields['max_ownddns_accounts']) {
					exec_query('UPDATE `ownddns` SET `max_ownddns_accounts` = ? WHERE `admin_id` = ?', array($maxLogins, $customerAdminId));
					
					set_page_message(
						tr('Max accounts succesfully changed.'), 'success'
					);
				}
				
				redirectTo('ownddns.php');
			} else {
				set_page_message(
					tr("Invalid input for max accounts."), 'error'
				);
			}
		}
		
		$tpl->assign(
			array(
				'TR_PAGE_TITLE' => tr('Customers / Edit OwnDDNS for customer: %s', decode_idna($stmt->fields['admin_name'])),
				'TR_ACCOUNT_LIMITS' => tr('OwnDDNS account limits for customer: %s', decode_idna($stmt->fields['admin_name'])),
				'MAX_ACCOUNTS' => $stmt2->fields['max_ownddns_accounts'],
				'OWNDDNS_ADMIN_ID' => $customerAdminId
			)
		);
	} else {
		redirectTo('ownddns.php');
	}
	
	$tpl->assign('OWNDDNS_LIST', '');
}

function ownddns_deactivateCustomer($tpl, $customerAdminId, $resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	
	$query = "
		SELECT
			`admin_id`, `admin_name`
		FROM
			`admin`
		WHERE
			`admin_id` = ?
		AND
			`created_by` = ?
		AND
			`admin_status` = ?
	";
	
	$stmt = exec_query($query, array($customerAdminId, $resellerId, $cfg->ITEM_OK_STATUS));
	
	if ($stmt->rowCount()) {
		$stmt2 = exec_query(
			'
				SELECT
					`t1`.*, `t2`.`customer_dns_previous_status`
				FROM
					`ownddns_accounts` AS `t1`
				LEFT JOIN
					`ownddns` AS `t2` ON(`t2`.`admin_id` = `t1`.`admin_id`)
				WHERE
					`t1`.`admin_id` = ?
			', $customerAdminId
		);
		
		if ($stmt2->rowCount()) {
			while ($data = $stmt2->fetchRow()) {
				if($data['alias_id'] == '0') {
					$stmt3 = exec_query('SELECT * FROM `domain_dns` WHERE `domain_id` = ?', $data['domain_id']);
						
					if (! $stmt3->rowCount()) {
						exec_query('UPDATE `domain` SET `domain_status` = ?, `domain_dns` = ? WHERE `domain_id` = ?',
							array($cfg->ITEM_TOCHANGE_STATUS, $data['customer_dns_previous_status'], $data['domain_id'])
						);
					} else {
						exec_query('UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?', array($cfg->ITEM_TOCHANGE_STATUS, $data['domain_id']));
					}
				} else {
					exec_query('UPDATE `domain_aliasses` SET `alias_status` = ? WHERE `alias_id` = ?', array($cfg->ITEM_TOCHANGE_STATUS, $data['alias_id']));
				}
				
				exec_query('DELETE FROM `domain_dns` WHERE `domain_id` AND `alias_id` AND `owned_by` = ?', $data['domain_id'], $data['alias_id'], 'ownddns_feature');
			}
		} else {
			$stmt2 = exec_query(
				'
					SELECT
						*
					FROM
						`ownddns`
					WHERE
						`admin_id` = ?
				', $customerAdminId
			);
			
			while ($data = $stmt2->fetchRow()) {
				$stmt3 = exec_query('SELECT * FROM `domain_dns` WHERE `domain_id` = ?', $data['domain_id']);
						
				if (! $stmt3->rowCount()) {
					exec_query('UPDATE `domain` SET `domain_status` = ?, `domain_dns` = ? WHERE `domain_id` = ?',
						array($cfg->ITEM_TOCHANGE_STATUS, $data['customer_dns_previous_status'], $data['domain_id'])
					);
				} else {
					exec_query('UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?', array($cfg->ITEM_TOCHANGE_STATUS, $data['domain_id']));
				}
			}
		}
		
		send_request();
		
		exec_query('DELETE FROM `ownddns` WHERE `admin_id` = ?', $customerAdminId);
		exec_query('DELETE FROM `ownddns_accounts` WHERE `admin_id` = ?', $customerAdminId);
		
		set_page_message(
			tr('Customer deactivated for OwnDDNS support. This can take a few seconds.'), 'success'
		);
	} else {
		set_page_message(tr("The customer you are trying to deactivate OwnDDNS doesn't exist."), 'error');
	}
	
	redirectTo('ownddns.php');
}

/***********************************************************************************************************************
 * Main
 */

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

if(iMSCP_Registry::isRegistered('pluginManager')) {
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
		'page' => '../../plugins/OwnDDNS/frontend/reseller/ownddns.tpl',
		'page_message' => 'layout',
		'ownddns_list' => 'page',
		'ownddns_edit' => 'page',
		'ownddns_select_item' => 'page',
		'ownddns_customer_list' => 'page',
		'ownddns_customer_item' => 'page',
		'ownddns_no_customer_item' => 'page',
		'scroll_prev_gray' => 'ownddns_customer_list',
		'scroll_prev' => 'ownddns_customer_list',
		'scroll_next_gray', 'ownddns_customer_list',
		'scroll_next' => 'ownddns_customer_list',
	)
);

if (isset($_REQUEST['action'])) {
	$action = clean_input($_REQUEST['action']);
	
	if($action === 'activate') {
		$customerAdminId = (isset($_POST['admin_id']) && $_POST['admin_id'] !== '-1') ? clean_input($_POST['admin_id']) : '';
		
		if($customerAdminId != '') {
			ownddns_activateCustomer($tpl, $pluginManager, $customerAdminId, $_SESSION['user_id']);
		}
	} elseif($action === 'edit') {
		$customerAdminId = ($_GET['admin_id'] !== '') ? (int) clean_input($_GET['admin_id']) : '';
		
		if($customerAdminId != '') {
			ownddns_changeCustomerOwnDDNS($tpl, $customerAdminId, $_SESSION['user_id']);
		}
	} elseif($action === 'delete') {
		$customerAdminId = (isset($_GET['admin_id'])) ? clean_input($_GET['admin_id']) : '';
		
		if($customerAdminId != '') {
			ownddns_deactivateCustomer($tpl, $customerAdminId, $_SESSION['user_id']);
		}
	}
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Customers / OwnDDNS'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'CUSTOMER_NOT_SELECTED' => tr("No customer selected."),
		'TR_OWNDDNS_SELECT_NAME_NONE' => tr('Select a customer'),
		'TR_SHOW' => tr('Activate OwnDDNS for this customer'),
		'TR_UPDATE' => tr('Update'),
		'TR_CANCEL' => tr('Cancel'),
		'TR_OWNDDNS_CUSTOMER_NAME' => tr('Customer'),
		'TR_OWNDDNS_NO_CUSTOMER' => tr('OwnDDNS customer entries'),
		'OWNDDNS_NO_CUSTOMER' => tr('No customer for OwnDDNS support activated'),
		'TR_OWNDDNS_STATUS' => tr('Status'),
		'TR_OWNDDNS_ACCOUNT_LIMIT' => tr('Account limit'),
		'DEACTIVATE_CUSTOMER_ALERT' => tr('Are you sure, You want to deactivate OwnDDNS for this customer?'),
		'TR_PREVIOUS' => tr('Previous'),
		'TR_OWNDDNS_ACTIONS' => tr('Actions'),
		'TR_EDIT_OWNDDNS_ACCOUNT' => tr('Edit customer OwnDDNS'),
		'TR_DELETE_OWNDDNS_ACCOUNT' => tr('Delete customer OwnDDNS'),
		'TR_LIMIT_VALUE' => tr('Limit value'),
		'TR_MAX_ACCOUNT_LIMIT' => tr('Max creating accounts') . '<br /><i>(0 ' . tr('unlimited') . ')</i>',
		'TR_NEXT' => tr('Next')
	)
);

generateNavigation($tpl);

if(!isset($_GET['action'])) {
	ownddns_generateSelect($tpl, $_SESSION['user_id']);
	ownddns_generateActivatedCustomers($tpl, $_SESSION['user_id']);
}

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
