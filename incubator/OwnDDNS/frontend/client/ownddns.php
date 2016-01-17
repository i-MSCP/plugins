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
 * @param int $userId
 * @return void
 */
function ownddns_generateAccountsList($tpl, $pluginManager, $userId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	
	if (($plugin = $pluginManager->loadPlugin('OwnDDNS', false, false)) !== null) {
		$pluginConfig = $plugin->getConfig();
	} else {
		set_page_message(
			tr("Can't load plugin configuration!"), 'error'
		);
		redirectTo('ownddns.php');
	}
	
	$baseServerVhostPrefix = ($cfg->BASE_SERVER_VHOST_PREFIX == 'https://') ? 'http(s)://' : $cfg->BASE_SERVER_VHOST_PREFIX;
	
	if($pluginConfig['use_base64_encoding'] === TRUE) {
		$udateURL = $baseServerVhostPrefix.$cfg->BASE_SERVER_VHOST . '/ownddns.php?action=update&data=<b64>AccountName;AccessKey;FQDN</b64>';
		set_page_message(tr('Base64 encoding is activated. Your router must send the data base64 encrypted!<br />Update-URL: <strong>%s</strong>' , $udateURL), 'info');
	} else {
		$udateURL = $baseServerVhostPrefix.$cfg->BASE_SERVER_VHOST . '/ownddns.php?action=update&data=AccountName;AccessKey;FQDN';
		set_page_message(tr('Base64 encoding is deactivated. <br />Update-URL: <strong>%s</strong>' , $udateURL), 'info');
	}

	$rowsPerPage = $cfg->DOMAIN_ROWS_PER_PAGE;
	
	if (isset($_GET['psi']) && $_GET['psi'] == 'last') {
		unset($_GET['psi']);
	}
	
	$startIndex = isset($_GET['psi']) ? (int)$_GET['psi'] : 0;
	
	$countQuery = "
		SELECT COUNT(`admin_id`) AS `cnt` 
		FROM 
			`ownddns_accounts`
		WHERE
			`admin_id` = ?
	";
		
	$stmt = exec_query($countQuery, $userId);
	$recordsCount = $stmt->fields['cnt'];
	
	$query = "
		SELECT
			*
		FROM
			`ownddns_accounts`
		WHERE
			`admin_id` = ?
		ORDER BY
			`ownddns_account_name` ASC
	";
	
	
	$stmt = exec_query($query, $userId);
	
	if ($stmt->rowCount()) {
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
			if($data['ownddns_account_status'] == $cfg->ITEM_OK_STATUS) $statusIcon = 'ok';
			elseif ($data['ownddns_account_status'] == $cfg->ITEM_DISABLED_STATUS) $statusIcon = 'disabled';
			elseif (
				(
					$data['ownddns_account_status'] == $cfg->ITEM_TOADD_STATUS ||
					$data['ownddns_account_status'] == $cfg->ITEM_TOCHANGE_STATUS ||
					$data['ownddns_account_status'] == $cfg->ITEM_TODELETE_STATUS
				) ||
				(
					$data['ownddns_account_status'] == $cfg->ITEM_TOADD_STATUS ||
					$data['ownddns_account_status'] == $cfg->ITEM_TORESTORE_STATUS ||
					$data['ownddns_account_status'] == $cfg->ITEM_TOCHANGE_STATUS ||
					$data['ownddns_account_status'] == $cfg->ITEM_TOENABLE_STATUS ||
					$data['ownddns_account_status'] == $cfg->ITEM_TODISABLE_STATUS ||
					$data['ownddns_account_status'] == $cfg->ITEM_TODELETE_STATUS
				)
			) {
				$statusIcon = 'reload';
			} else {
				$statusIcon = 'error';
			}
			
			$tpl->assign(
				array(
					'OWNDDNS_ACCOUNT_NAME' => $data['ownddns_account_name'],
					'OWNDDNS_ACCOUNT_FQDN' => $data['ownddns_account_fqdn'],
					'OWNDDNS_ACCOUNT_KEY' => $data['ownddns_key'],
					'OWNDDNS_ACCOUNT_ID' => $data['ownddns_account_id'],
					'OWNDDNS_LAST_IP' => ($data['ownddns_last_ip'] != '') ? $data['ownddns_last_ip'] : 'N/A',
					'OWNDDNS_LAST_UPDATE' => ($data['ownddns_last_update'] != '0000-00-00 00:00:00') ? $data['ownddns_last_update'] : 'N/A',
					'OWNDDNS_ACCOUNT_STATUS' => translate_dmn_status($data['ownddns_account_status']),
					'STATUS_ICON' => $statusIcon
				)
			);
			
			$tpl->parse('OWNDDNS_ACCOUNT_ITEM', '.ownddns_account_item');
		}
		
		$tpl->assign('OWNDDNS_NO_ACCOUNT_ITEM', '');
		
		$tpl->parse('OWNDDNS_ACCOUNT_LIST', 'ownddns_account_list');
	} else {
		$tpl->assign(
			array(
				'OWNDDNS_ACCOUNT_LIST' => '',
				'SCROLL_PREV' => '',
				'SCROLL_PREV_GRAY' => '',
				'SCROLL_NEXT' => '',
				'SCROLL_NEXT_GRAY' => '',
			)
		);
	}
	
	$tpl->assign(
		array(
			'OWNDDNS_EDIT_ACCOUNT' => '',
			'MAX_ACCOUNT_NAME_LENGHT' => $pluginConfig['max_accounts_lenght']
		)
	);
}

function ownddns_generateSelect($tpl, $userId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	
	$query = "
		SELECT
			`domain_id`, `domain_name`
		FROM
			`domain`
		WHERE
			`domain_admin_id` = ?
		AND
			`domain_status` IN (?, ?)
		ORDER BY
			`domain_name` ASC
	";
	
	$stmt = exec_query($query, array($userId, $cfg->ITEM_OK_STATUS, $cfg->ITEM_TOCHANGE_STATUS));
	
	$selected = $cfg->HTML_SELECTED;
	
	if ($stmt->rowCount()) {
		while ($data = $stmt->fetchRow()) {
			$tpl->assign(
				array(
					'TR_OWNDDNS_SELECT_VALUE' => $data['domain_id'] . ';0',
					'TR_OWNDDNS_SELECT_NAME' => decode_idna($data['domain_name']),
					'ACCOUNT_NAME_SELECTED' => (isset($_POST['ownddns_domain_id']) && $_POST['ownddns_domain_id'] == $data['domain_id'] . ';0')  ? $selected : ''
					)
				);

			$tpl->parse('OWNDDNS_SELECT_ITEM', '.ownddns_select_item');
			
			$query2 = "
				SELECT
					`alias_id`, `alias_name`
				FROM
					`domain_aliasses`
				WHERE
					`domain_id` = ?
				AND
					`alias_status` = ?
				ORDER BY
					`alias_name` ASC
			";
			
			$stmt2 = exec_query($query2, array($data['domain_id'], $cfg->ITEM_OK_STATUS));
			
			if ($stmt2->rowCount()) {
				while ($data2 = $stmt2->fetchRow()) {
					$tpl->assign(
						array(
							'TR_OWNDDNS_SELECT_VALUE' => $data['domain_id'] . ';' . $data2['alias_id'],
							'TR_OWNDDNS_SELECT_NAME' => decode_idna($data2['alias_name']),
							'ACCOUNT_NAME_SELECTED' => (isset($_POST['ownddns_domain_id']) && $_POST['ownddns_domain_id'] == $data['domain_id'] . ';' . $data2['alias_id'])  ? $selected : ''
							)
						);

					$tpl->parse('OWNDDNS_SELECT_ITEM', '.ownddns_select_item');
				}
			}
		}
	} else {
		$tpl->assign('OWNDDNS_SELECT_ITEM', '');
	}
}

function checkDomainExist($accountNameFQDN)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	
	$query = "
		SELECT
			*
		FROM
			`domain`
		WHERE
			`domain_name` = ?
	";
	
	$stmt = exec_query($query, $accountNameFQDN);
	
	return ($stmt->rowCount()) ? TRUE : FALSE;
}

function checkSubDomainExist($accountName, $domainId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	
	$query = "
		SELECT
			*
		FROM
			`subdomain`
		WHERE
			`domain_id` = ?
		AND
			`subdomain_name` = ?
	";
	
	$stmt = exec_query($query, array($domainId, $accountName));
	
	return ($stmt->rowCount()) ? TRUE : FALSE;
}

function checkDomainAliasExist($accountNameFQDN)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	
	$query = "
		SELECT
			*
		FROM
			`domain_aliasses`
		WHERE
			`alias_name` = ?
	";
	
	$stmt = exec_query($query, $accountNameFQDN);
	
	return ($stmt->rowCount()) ? TRUE : FALSE;
}

function checkSubDomainAliasExist($accountName, $aliasId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	
	$query = "
		SELECT
			*
		FROM
			`subdomain_alias`
		WHERE
			`alias_id` = ?
		AND
			`subdomain_alias_name` = ?
	";
	
	$stmt = exec_query($query, array($aliasId, $accountName));
	
	return ($stmt->rowCount()) ? TRUE : FALSE;
}

function ownddns_AddAccount($tpl, $pluginManager, $userId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (isset($_POST['ownddns_account_name']) && isset($_POST['ownddns_key']) && isset($_POST['ownddns_domain_id'])) {
		$error = false;
		
		if (($plugin = $pluginManager->loadPlugin('OwnDDNS', false, false)) !== null) {
			$pluginConfig = $plugin->getConfig();
		} else {
			set_page_message(
				tr("Can't load plugin configuration!"), 'error'
			);
			redirectTo('ownddns.php');
		}
		
		$accountName = clean_input($_POST['ownddns_account_name']);
		$accountKey = clean_input($_POST['ownddns_key']);
		$selectedDomainArray = explode(';', clean_input($_POST['ownddns_domain_id']));
		
		$query = "SELECT `max_ownddns_accounts` FROM `ownddns` WHERE `admin_id` = ?";	
		$stmt = exec_query($query, $userId);
		
		$maxAccounts = $stmt->fields['max_ownddns_accounts'];
		
		$query = "
			SELECT COUNT(`ownddns_account_id`) AS `cnt` 
			FROM 
				`ownddns_accounts`
			WHERE
				`admin_id` = ?
		";
			
		$stmt = exec_query($query, $userId);
		$activatedAccounts = $stmt->fields['cnt'];
		
		
		if($selectedDomainArray[0] == '-1') {
			set_page_message(tr("No domain selected"), 'error');
			$error = true;
		} elseif(count($selectedDomainArray) !== 2 || !is_numeric($selectedDomainArray[0]) || !is_numeric($selectedDomainArray[1])) {
			set_page_message(tr("Wrong values in selected domain."), 'error');
			$error = true;
		} elseif(in_array(clean_input($_POST['ownddns_account_name']), $pluginConfig['account_name_blacklist'])) {
			set_page_message(tr("The account name '%s' is blacklisted on this system)", clean_input($_POST['ownddns_account_name'])), 'error');
			$error = true;
		} elseif($maxAccounts != '0' && $activatedAccounts >= $maxAccounts) {
			set_page_message(tr("Max. allowed OwnDDNS accounts reached."), 'error');
			$error = true;
		} elseif(strlen(clean_input($_POST['ownddns_account_name'])) === 0) {
			set_page_message(tr("The account name is empty"), 'error');
			$error = true;
		} elseif(strlen(clean_input($_POST['ownddns_account_name'])) > $pluginConfig['max_accounts_lenght']) {
			set_page_message(tr("The account name is too long (max. %d chars)", $pluginConfig['max_accounts_lenght']), 'error');
			$error = true;
		} elseif(!preg_match("/^[a-zA-Z0-9]+[-]?[a-zA-Z0-9]+$/",clean_input($_POST['ownddns_account_name']))) {
			set_page_message(tr("The account name does only accept alphanumeric characters and '-' character (example: my-name)"), 'error');
			$error = true;
		} elseif(checkSubDomainExist($accountName, $selectedDomainArray[0])) {
			set_page_message(tr("The account name already exists as subdomain name"), 'error');
			$error = true;
		} elseif(checkSubDomainAliasExist($accountName, $selectedDomainArray[1])) {
			set_page_message(tr("The account name already exists as alias subdomain name"), 'error');
			$error = true;
		} elseif (strlen($accountKey) !== 30) {
			set_page_message(tr("Wrong OwnDDNS key"), 'error');
			$error = true;
		}
		
		if($selectedDomainArray[1] == '0') {
			$query = "
				SELECT
					`domain_name`
				FROM
					`domain`
				WHERE
					`domain_id` = ?
				AND
					`domain_admin_id` = ?
			";
			
			$stmt = exec_query($query, array($selectedDomainArray[0], $userId));
			$domain = decode_idna($stmt->fields['domain_name']); 
		} else {
			$query = "
				SELECT
					`alias_name`
				FROM
					`domain_aliasses`
				WHERE
					`domain_id` = ?
				AND
					`alias_id` = ?
			";
			
			$stmt = exec_query($query, array($selectedDomainArray[0], $selectedDomainArray[1]));
			$domain = decode_idna($stmt->fields['alias_name']); 
		}
		
		$accountNameFQDN = $accountName . '.' . $domain;
		
		if(checkDomainExist($accountNameFQDN)) {
			set_page_message(tr("The account name already exists on this system"), 'error');
			$error = true;
		} elseif(checkDomainAliasExist($accountNameFQDN)) {
			set_page_message(tr("The account name already exists on this system"), 'error');
			$error = true;
		}
		
		if (!$error) {
			$query = '
				INSERT INTO `ownddns_accounts` (
					`admin_id`, `domain_id`, `alias_id`,
					`ownddns_account_name`, `ownddns_account_fqdn`, `ownddns_key`, 
					`ownddns_account_status`
				) VALUES(
					?, ?, ?,
					?, ?, ?,
					?
				)
			';
			
			$query2 = '
				INSERT INTO `domain_dns` (
					`domain_id`, `alias_id`, `domain_dns`,
					`domain_class`, `domain_type`, `domain_text`,
					`owned_by`
				) VALUES(
					?, ?, ?,
					?, ?, ?,
					?
				)
			';

			try {
				exec_query(
					$query, array(
						$_SESSION['user_id'], $selectedDomainArray[0], $selectedDomainArray[1], 
						$accountName, $accountNameFQDN, $accountKey,
						$cfg->ITEM_OK_STATUS)
				);
				
				exec_query(
					$query2, array(
						$selectedDomainArray[0], $selectedDomainArray[1], $accountName . ' ' . $pluginConfig['update_ttl_time'], 
						'IN', 'A', $_SERVER['REMOTE_ADDR'], 'OwnDDNS_Plugin')
				);
			} catch(iMSCP_Exception_Database $e) {
				if($e->getCode() == 23000) { // Duplicate entries
					set_page_message(tr('The OwnDDNS account name %s already exists.', $accountNameFQDN), 'error');
					redirectTo('ownddns.php');
				}
			}
			
			if($selectedDomainArray[1] == '0') {
				exec_query('UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?', array($cfg->ITEM_TOCHANGE_STATUS, $selectedDomainArray[0]));
			} else {
				exec_query('UPDATE `domain_aliasses` SET `alias_status` = ? WHERE `alias_id` = ?', array($cfg->ITEM_TOCHANGE_STATUS, $selectedDomainArray[1]));
			}
			
			send_request();
			
			return true;
		} else {
			$tpl->assign(
				array(
					'OWNDDNS_DIALOG_OPEN' => 1,
					'OWNDDNS_ACCOUNT_NAME_ADD' => clean_input($accountName),
					'OWNDDNS_KEY_ADD' => $accountKey
				)
			);
			return false;
		}
	}
	
	redirectTo('ownddns.php');
}

function ownddns_EditAccount($tpl, $userId, $accountID)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	
	$query = "
		SELECT
			*
		FROM
			`ownddns_accounts`
		WHERE
			`admin_id` = ?
		AND
			`ownddns_account_id` = ?
		AND
			`ownddns_account_status` = ?
	";
	
	$stmt = exec_query($query, array($userId, $accountID, $cfg->ITEM_OK_STATUS));
	
	if ($stmt->rowCount()) {
		if(isset($_POST['ownddns_key'])) {
			$error = false;
			$accountKey = clean_input($_POST['ownddns_key']);
			
			if (strlen($accountKey) !== 30) {
				set_page_message(tr("Wrong OwnDDNS key"), 'error');
				$error = true;
			}
			
			if (!$error) {
				exec_query('UPDATE `ownddns_accounts` SET `ownddns_key` = ?, `ownddns_account_status` = ? WHERE `ownddns_account_id` = ?', 
					array($accountKey, $cfg->ITEM_OK_STATUS, $accountID)
				);
		
				return true;
			}
		}
		
		$tpl->assign(
			array(
				'OWNDDNS_ADD_BUTTON' => '',
				'OWNDDNS_ADD_DIALOG' => '',
				'OWNDDNS_ACCOUNT_LIST' => '',
				'OWNDDNS_NO_ACCOUNT_ITEM' => '',
				'OWNDDNS_ACCOUNT_NAME_EDIT' => $stmt->fields['ownddns_account_name'],
				'OWNDDNS_ACCOUNT_FQDN_EDIT' => $stmt->fields['ownddns_account_fqdn'],
				'OWNDDNS_KEY_EDIT' => $stmt->fields['ownddns_key'],
				'OWNDDNS_ACCOUNT_ID' => $stmt->fields['ownddns_account_id'],
			)
		);
	} else {
		set_page_message(tr("The OwnDDNS account you are trying to edit doesn't exist or is in invalid state."), 'error');
		
		redirectTo('ownddns.php');
	}
}

function ownddns_DeleteAccount($tpl, $pluginManager, $userId, $accountID)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	
	if (($plugin = $pluginManager->loadPlugin('OwnDDNS', false, false)) !== null) 
	{
		$pluginConfig = $plugin->getConfig();
	} else {
		set_page_message(
			tr("Can't load plugin configuration!"), 'error'
		);
		redirectTo('ownddns.php');
	}
	
	$query = "
		SELECT
			*
		FROM
			`ownddns_accounts`
		WHERE
			`admin_id` = ?
		AND
			`ownddns_account_id` = ?
		AND
			`ownddns_account_status` = ?
	";
	
	$stmt = exec_query($query, array($userId, $accountID, $cfg->ITEM_OK_STATUS));
	
	if ($stmt->rowCount()) {
		exec_query(
			'
				DELETE FROM
					`domain_dns`
				WHERE 
					`domain_id` = ?
				AND
					`alias_id` = ?
				AND
					`domain_dns` = ?
				AND 
					`owned_by` = ?
			', array($stmt->fields['domain_id'], $stmt->fields['alias_id'], $stmt->fields['ownddns_account_name'] . ' ' . $pluginConfig['update_ttl_time'], 'OwnDDNS_Plugin')
		);
			
		exec_query('DELETE FROM `ownddns_accounts` WHERE `ownddns_account_id` = ?', $accountID);
		
		if($stmt->fields['alias_id'] == '0') {
			exec_query('UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?', array($cfg->ITEM_TOCHANGE_STATUS, $stmt->fields['domain_id']));
		} else {
			exec_query('UPDATE `domain_aliasses` SET `alias_status` = ? WHERE `alias_id` = ?', array($cfg->ITEM_TOCHANGE_STATUS, $stmt->fields['alias_id']));
		}
		
		send_request();
		
		return true;
	} else {
		set_page_message(tr("The OwnDDNS account you are trying to delete doesn't exist or is in invalid state."), 'error');
	}
	
	redirectTo('ownddns.php');
}

function get_ownddnsAccountLimit($tpl, $userId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$countQuery = "
		SELECT COUNT(`ownddns_account_id`) AS `cnt` 
		FROM 
			`ownddns_accounts`
		WHERE
			`admin_id` = ?
	";
		
	$stmt = exec_query($countQuery, $userId);
	$recordsCount = $stmt->fields['cnt'];
	
	$query = "SELECT `max_ownddns_accounts`, `ownddns_status` FROM `ownddns` WHERE `admin_id` = ?";	
	$stmt = exec_query($query, $userId);
	
	$tpl->assign('TR_OWNDDNS_ACCOUNT_AVAILABLE', tr('Active accounts: %s of %s', $recordsCount, (($stmt->fields['max_ownddns_accounts'] == 0) ? '<b>unlimited</b>' : $stmt->fields['max_ownddns_accounts'])));
	if($stmt->fields['max_ownddns_accounts'] != 0 && $recordsCount >= $stmt->fields['max_ownddns_accounts']) {
		$tpl->assign('OWNDDNS_ADD_BUTTON', '');
		set_page_message(tr('OwnDDNS account limit exceeded.'), 'info');
	}
}

/***********************************************************************************************************************
 * Main
 */

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

check_login('user');

if (iMSCP_Plugin_OwnDDNS::customerHasOwnDDNS($_SESSION['user_id'])) {
	if(iMSCP_Registry::isRegistered('pluginManager')) {
		/** @var iMSCP_Plugin_Manager $pluginManager */
		$pluginManager = iMSCP_Registry::get('pluginManager');
	} else {
		throw new iMSCP_Plugin_Exception('An unexpected error occured');
	}

	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => '../../plugins/OwnDDNS/frontend/client/ownddns.tpl',
			'page_message' => 'layout',
			'ownddns_select_item' => 'page',
			'ownddns_account_list' => 'page',
			'ownddns_account_item' => 'page',
			'ownddns_no_account_item' => 'page',
			'ownddns_edit_login' => 'page',
			'ownddns_add_dialog' => 'page',
			'ownddns_add_button' => 'page',
			'scroll_prev_gray' => 'ownddns_account_list',
			'scroll_prev' => 'ownddns_account_list',
			'scroll_next_gray', 'ownddns_account_list',
			'scroll_next' => 'ownddns_account_list',
		)
	);

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('Domains / OwnDDNS'),
			'TR_PAGE_TITLE_OWNDDNS_ADD' => tr('OwnDDNS - Account'),
			'THEME_CHARSET' => tr('encoding'),
			'ISP_LOGO' => layout_getUserLogo(),
			'OWNDDNS_NO_ACCOUNT' => tr('No account for OwnDDNS activated'),
			'TR_OWNDDNS_ACCOUNT_NAME' => tr('OwnDDNS account name'),
			'TR_OWNDDNS_ACCOUNT_FQDN' => tr('OwnDDNS FQDN'),
			'TR_POPUP_OWNDDNS_ACCOUNT_NAME' => tr('OwnDDNS account name:'),
			'TR_POPUP_OWNDDNS_KEY' => tr('OwnDDNS access key:'),
			'TR_OWNDDNS_ACCOUNT_STATUS' => tr('Status'),
			'TR_OWNDDNS_ACCOUNT_ACTIONS' => tr('Actions'),
			'TR_ADD_OWNDDNS_ACCOUNT' => tr('Add new OwnDDNS account'),
			'DELETE_ACCOUNT_ALERT' => tr('Are you sure, You want to delete this OwnDDNS account?'),
			'TR_EDIT_ACCOUNT' => tr('Edit account'),
			'TR_DELETE_ACCOUNT' => tr('Delete account'),
			'TR_PREVIOUS' => tr('Previous'),
			'TR_NEXT' => tr('Next'),
			'TR_ADD' => tr('Add'),
			'TR_CANCEL' => tr('Cancel'),
			'OWNDDNS_DIALOG_OPEN' => 0,
			'OWNDDNS_ACCOUNT_NAME' => '',
			'OWNDDNS_ACCOUNT_FQDN' => '',
			'OWNDDNS_PASSWORD' => '',
			'TR_UPDATE' => tr('Update'),
			'TR_CANCEL' => tr('Cancel'),
			'OWNDDNS_KEY_READONLY' => $cfg->HTML_READONLY,
			'OWNDDNS_KEY_ADD' => '',
			'OWNDDNS_KEY_EDIT' => '',
			'OWNDDNS_ACCOUNT_NAME_ADD' => '',
			'OWNDDNS_ACCOUNT_NAME_EDIT' => '',
			'OWNDDNS_ACCOUNT_FQDN_EDIT' => '',
			'TR_GENERATE_OWNDDNSKEY' => tr('Generate account key'),
			'TR_OWNDDNS_SELECT_NAME_NONE' => tr('Select a domain'),
			'TR_OWNDDNS_LAST_IP' => tr('Last ipaddress'),
			'TR_OWNDDNS_LAST_UPDATE' => tr('Last update'),
			'ACCOUNT_NAME_SELECTED' => ''
		)
	);

	if (isset($_REQUEST['action'])) {
		$action = clean_input($_REQUEST['action']);

		if ($action === 'add') {
			if (ownddns_AddAccount($tpl, $pluginManager, $_SESSION['user_id'])) {
				set_page_message(tr('New OwnDDNS account successfully scheduled for addition'), 'success');
				redirectTo('ownddns.php');
			}
		} elseif($action === 'edit') {
			$accountID = (isset($_GET['ownddns_account_id'])) ? clean_input($_GET['ownddns_account_id']) : '';
			
			if($accountID != '') {
				if (ownddns_EditAccount($tpl, $_SESSION['user_id'], $accountID)) {
					set_page_message(tr('OwnDDNS account successfully scheduled for update'), 'success');
					redirectTo('ownddns.php');
				}
			}
		} elseif ($action === 'delete') {
			$accountID = (isset($_GET['ownddns_account_id'])) ? clean_input($_GET['ownddns_account_id']) : '';
			
			if($accountID != '') {
				if (ownddns_DeleteAccount($tpl, $pluginManager, $_SESSION['user_id'], $accountID)) {
					set_page_message(tr('OwnDDNS account successfully scheduled for deletion'), 'success');
					redirectTo('ownddns.php');
				}
			}
		} else {
			showBadRequestErrorPage();
		}
	}


	generateNavigation($tpl);

	get_ownddnsAccountLimit($tpl, $_SESSION['user_id']);
	ownddns_generateSelect($tpl, $_SESSION['user_id']);

	if(!isset($_REQUEST['action']) || isset($_REQUEST['action']) && clean_input($_REQUEST['action']) !== 'edit') {
		ownddns_generateAccountsList($tpl, $pluginManager, $_SESSION['user_id']);
	}

	generatePageMessage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');

	iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

	$tpl->prnt();
} else {
	showBadRequestErrorPage();
}
