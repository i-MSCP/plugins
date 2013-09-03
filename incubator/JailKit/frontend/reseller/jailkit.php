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
 * @param iMSCP_Plugin_Manager $pluginManager
 * @param int $resellerId
 * @param int $domainId
 * @return void
 */
function jailkit_generateSelect($tpl, $resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	
	$query = "
		SELECT
			`domain_id`, `domain_name`
		FROM
			`domain`
		WHERE
			`domain_created_id` = ?
		AND
			`domain_status` = ?
		AND
			`domain_id` NOT IN (SELECT `domain_id` FROM `jailkit`)
		ORDER BY
			`domain_name` ASC
	";
	
	$stmt = exec_query($query, array($resellerId, $cfg->ITEM_OK_STATUS));
	
	if ($stmt->rowCount()) {
		while ($data = $stmt->fetchRow()) {
			$tpl->assign(
				array(
					'TR_JAILKIT_SELECT_VALUE' => $data['domain_id'],
					'TR_JAILKIT_SELECT_NAME' => decode_idna($data['domain_name']),
					)
				);

			$tpl->parse('JAILKIT_SELECT_ITEM', '.jailkit_select_item');
		}
	} else {
		$tpl->assign('JAILKIT_SELECT_ITEM', '');
	}
}

function jailkit_generateActivatedDomains($tpl, $resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	
	$rowsPerPage = $cfg->DOMAIN_ROWS_PER_PAGE;
	
	if (isset($_GET['psi']) && $_GET['psi'] == 'last') {
		unset($_GET['psi']);
	}
	
	$startIndex = isset($_GET['psi']) ? (int)$_GET['psi'] : 0;
	
	$countQuery = "
		SELECT COUNT(`t1`.`domain_id`) AS `cnt` 
		FROM 
			`domain` AS `t1`
		LEFT JOIN
			`jailkit` AS `t2` ON(`t1`.`domain_id` = `t2`.`domain_id`)
		WHERE
			`t1`.`domain_created_id` = '$resellerId'
		AND
			`t1`.`domain_id` IN (SELECT `domain_id` FROM `jailkit`)
	";
		
	$stmt = execute_query($countQuery);
	$recordsCount = $stmt->fields['cnt'];

	$query = "
		SELECT
			`t2`.*
		FROM
			`domain` AS `t1`
		LEFT JOIN
			`jailkit` AS `t2` ON(`t1`.`domain_id` = `t2`.`domain_id`)
		WHERE
			`t1`.`domain_created_id` = ?
		AND
			`t1`.`domain_id` IN (SELECT `domain_id` FROM `jailkit`)
		ORDER BY
			`t2`.`domain_name` ASC
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
			if($data['jailkit_status'] == $cfg->ITEM_OK_STATUS) $statusIcon = 'ok';
			elseif ($data['jailkit_status'] == $cfg->ITEM_DISABLED_STATUS) $statusIcon = 'disabled';
			elseif (
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
					'JAILKIT_DOMAIN_NAME' => decode_idna($data['domain_name']),
					'JAILKIT_STATUS' => translate_dmn_status($data['jailkit_status']),
					'JAILKIT_LOGIN_LIMIT' => get_jailkitLoginLimit($data['domain_id']),
					'JAILKIT_DOMAIN_ID' => $data['domain_id'],
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

function get_jailkitLoginLimit($domainId)
{
	$countQuery = "
		SELECT COUNT(`jailkit_login_id`) AS `cnt` 
		FROM 
			`jailkit_login`
		WHERE
			`domain_id` = ?
	";
		
	$stmt = exec_query($countQuery, $domainId);
	$recordsCount = $stmt->fields['cnt'];
	
	$query = "SELECT `max_logins` FROM `jailkit` WHERE `domain_id` = ?";	
	$stmt = exec_query($query, $domainId);
	
	return $recordsCount . ' of ' . (($stmt->fields['max_logins'] == 0) ? '<b>unlimited</b>' : $stmt->fields['max_logins']);
}

function jailkit_activateDomain($tpl, $pluginManager, $domainId, $resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	
	$query = "
		SELECT
			`domain_id`, `domain_name`, `domain_name`, `domain_dns`
		FROM
			`domain`
		WHERE
			`domain_id` = ?
		AND
			`domain_created_id` = ?
		AND
			`domain_status` = ?
	";
	
	$stmt = exec_query($query, array($domainId, $resellerId, $cfg->ITEM_OK_STATUS));
	
	if (($plugin = $pluginManager->load('JailKit', false, false)) !== null) {
		$pluginConfig = $plugin->getConfig();
	} else {
		set_page_message(
			tr("Can't load plugin configuration!"), 'error'
		);
		redirectTo('jailkit.php');
	}
	
	if ($stmt->rowCount()) {
		while ($data = $stmt->fetchRow()) {
			$query = "
				INSERT INTO
				    `jailkit` (
						`domain_id`, `domain_name`, `max_logins`, `jailkit_status`
					) VALUES (
						?, ?, ?, ?
					)
			";
			exec_query(
				$query,
				array(
					$data['domain_id'], $data['domain_name'], $pluginConfig['max_allowed_ssh-user'], $cfg->ITEM_TOADD_STATUS
				)
			);
		}
		
		send_request();
		
		set_page_message(
			tr('Domain activated for JailKit support. This can take few seconds.'), 'success'
		);
	} else {
		set_page_message(
			tr("The domain you are trying to activate JailKit doesn't exist."), 'error'
		);
	}
	
	redirectTo('jailkit.php');
}

function jailkit_changeDomainJail($tpl, $domainId, $resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	
	$query = "
		SELECT
			`domain_id`, `domain_name`, `domain_name`, `domain_dns`
		FROM
			`domain`
		WHERE
			`domain_id` = ?
		AND
			`domain_created_id` = ?
		AND
			`domain_status` = ?
	";
	
	$stmt = exec_query($query, array($domainId, $resellerId, $cfg->ITEM_OK_STATUS));
	
	if ($stmt->rowCount()) {
		$query = "SELECT `max_logins` FROM `jailkit` WHERE `domain_id` = ?";	
		$stmt2 = exec_query($query, $domainId);
		
		if(isset($_POST['max_logins']) && $_POST['max_logins'] != '') {
			$maxLogins = clean_input($_POST['max_logins']);
			if($maxLogins >= 0) {
				if($maxLogins != $stmt2->fields['max_logins']) {
					exec_query('UPDATE `jailkit` SET `max_logins` = ? WHERE `domain_id` = ?', array($maxLogins, $domainId));
					
					set_page_message(
						tr('Max logins succesfully changed.'), 'success'
					);
				}
				
				redirectTo('jailkit.php');
			} else {
				set_page_message(
					tr("Invalid input for max logins."), 'error'
				);
			}
		}
		
		$tpl->assign(
			array(
				'TR_PAGE_TITLE' => tr('Customers / Edit JailKit - SSH for customer: %s', decode_idna($stmt->fields['domain_name'])),
				'TR_JAIL_LIMITS' => tr('Jail - SSH limits for customer: %s', decode_idna($stmt->fields['domain_name'])),
				'MAX_LOGINS' => $stmt2->fields['max_logins'],
				'JAILKIT_DOMAIN_ID' => $domainId
			)
		);
	} else {
		redirectTo('jailkit.php');
	}
	
	$tpl->assign('JAILKIT_LIST', '');
}

function jailkit_deactivateDomain($tpl, $domainId, $resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	
	$query = "
		SELECT
			`domain_id`, `domain_name`, `domain_name`, `domain_dns`
		FROM
			`domain`
		WHERE
			`domain_id` = ?
		AND
			`domain_created_id` = ?
		AND
			`domain_status` = ?
	";
	
	$stmt = exec_query($query, array($domainId, $resellerId, $cfg->ITEM_OK_STATUS));
	
	if ($stmt->rowCount()) {
		exec_query('UPDATE `jailkit` SET `jailkit_status` = ? WHERE `domain_id` = ?', array($cfg->ITEM_TODELETE_STATUS, $domainId));
		
		send_request();
		
		set_page_message(
			tr('Domain deactivated for JailKit support. This can take few seconds.'), 'success'
		);
	} else {
		set_page_message(tr("The domain you are trying to deactivate JailKit doesn't exist."), 'error');
	}
	
	redirectTo('jailkit.php');
}

function jailkit_changeDomainPermission($tpl, $domainId, $resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	
	$query = "
		SELECT
			`domain_id`, `domain_name`, `domain_name`, `domain_dns`
		FROM
			`domain`
		WHERE
			`domain_id` = ?
		AND
			`domain_created_id` = ?
		AND
			`domain_status` = ?
	";
	
	$stmt = exec_query($query, array($domainId, $resellerId, $cfg->ITEM_OK_STATUS));
	
	if ($stmt->rowCount()) {
		$query = "SELECT `domain_id`, `domain_name`, `jailkit_status` FROM `jailkit` WHERE `domain_id` = ?";
		$stmt = exec_query($query, $domainId);
		
		if ($stmt->rowCount() && $stmt->fields['jailkit_status'] == $cfg->ITEM_DISABLED_STATUS) {
			exec_query('UPDATE `jailkit` SET `jailkit_status` = ? WHERE `domain_id` = ?', array($cfg->ITEM_OK_STATUS, $domainId));
		
			exec_query('UPDATE `jailkit_login` SET `ssh_login_locked` = ?, `jailkit_login_status` = ? WHERE `domain_id` = ?', array('0', $cfg->ITEM_TOCHANGE_STATUS, $domainId));
			
			send_request();
			
			set_page_message(
				tr('Domain enabled for JailKit support. This can take few seconds.'), 'success'
			);
		} elseif($stmt->rowCount() && $stmt->fields['jailkit_status'] == $cfg->ITEM_OK_STATUS) {
			exec_query('UPDATE `jailkit` SET `jailkit_status` = ? WHERE `domain_id` = ?', array($cfg->ITEM_DISABLED_STATUS, $domainId));
			
			exec_query('UPDATE `jailkit_login` SET `ssh_login_locked` = ?, `jailkit_login_status` = ? WHERE `domain_id` = ?', array('1', $cfg->ITEM_TOCHANGE_STATUS, $domainId));
			
			send_request();
			
			set_page_message(
				tr('Domain disabled for JailKit support. This can take few seconds.'), 'success'
			);
		}
	} else {
		set_page_message(tr("The domain you are trying to change the permission of JailKit doesn't exist."), 'error');
	}
	
	redirectTo('jailkit.php');
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
		'page' => '../../plugins/JailKit/frontend/reseller/jailkit.tpl',
		'page_message' => 'layout',
		'jailkit_list' => 'page',
		'jailkit_edit' => 'page',
		'jailkit_select_item' => 'page',
		'jailkit_customer_list' => 'page',
		'jailkit_customer_item' => 'page',
		'jailkit_domain_item' => 'page',
		'jailkit_no_customer_item' => 'page',
		'scroll_prev_gray' => 'jailkit_customer_list',
		'scroll_prev' => 'jailkit_customer_list',
		'scroll_next_gray', 'jailkit_customer_list',
		'scroll_next' => 'jailkit_customer_list',
	)
);

if(isset($_POST['action']) && $_POST['action'] === 'activate') {
	$domainId = (isset($_POST['domain_id']) && $_POST['domain_id'] !== '-1') ? clean_input($_POST['domain_id']) : '';
	
	if($domainId != '') {
		jailkit_activateDomain($tpl, $pluginManager, $domainId, $_SESSION['user_id']);
	}
} elseif(isset($_GET['action']) && $_GET['action'] === 'change') {
	$domainId = (isset($_GET['domain_id'])) ? clean_input($_GET['domain_id']) : '';
	
	if($domainId != '') {
		jailkit_changeDomainPermission($tpl, $domainId, $_SESSION['user_id']);
	}
} elseif(isset($_GET['action']) && $_GET['action'] === 'edit') {
	$domainId = ($_GET['domain_id'] !== '') ? (int) clean_input($_GET['domain_id']) : '';
	
	if($domainId != '') {
		jailkit_changeDomainJail($tpl, $domainId, $_SESSION['user_id']);
	}
} elseif(isset($_GET['action']) && $_GET['action'] === 'deactivate') {
	$domainId = (isset($_GET['domain_id'])) ? clean_input($_GET['domain_id']) : '';
	
	if($domainId != '') {
		jailkit_deactivateDomain($tpl, $domainId, $_SESSION['user_id']);
	}
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Customers / JailKit - SSH'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'DOMAIN_NOT_SELECTED' => tr("No domain selected."),
		'TR_JAILKIT_SELECT_NAME_NONE' => tr('Select a customer'),
		'TR_SHOW' => tr('Activate JailKit - SSH for this customer'),
		'TR_UPDATE' => tr('Update'),
		'TR_CANCEL' => tr('Cancel'),
		'TR_JAILKIT_DOMAIN_NAME' => tr('Domain'),
		'TR_JAILKIT_NO_DOMAIN' => tr('JailKit domain entries'),
		'JAILKIT_NO_DOMAIN' => tr('No domain for JailKit support activated'),
		'TR_JAILKIT_STATUS' => tr('Status'),
		'TR_JAILKIT_LOGIN_LIMIT' => tr('Login limit'),
		'DEACTIVATE_DOMAIN_ALERT' => tr('Are you sure? You want to deactivate JailKit for this customer?'),
		'DISABLE_DOMAIN_ALERT' => tr('Are you sure? You want to disable all JailKit ssh logins for this customer?'),
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

if(!isset($_GET['action'])) {
	jailkit_generateSelect($tpl, $_SESSION['user_id']);
	jailkit_generateActivatedDomains($tpl, $_SESSION['user_id']);
}

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
