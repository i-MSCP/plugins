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
 * @subpackage  OpenDKIM
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
function opendkim_generateSelect($tpl, $resellerId)
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
			`domain_id` NOT IN (SELECT `domain_id` FROM `opendkim`)
		ORDER BY
			`domain_name` ASC
	";
	
	$stmt = exec_query($query, array($resellerId, $cfg->ITEM_OK_STATUS));
	
	if ($stmt->rowCount()) {
		while ($data = $stmt->fetchRow()) {
			$tpl->assign(
				array(
					'TR_OPENDKIM_SELECT_VALUE' => $data['domain_id'],
					'TR_OPENDKIM_SELECT_NAME' => decode_idna($data['domain_name']),
					)
				);

			$tpl->parse('OPENDKIM_SELECT_ITEM', '.opendkim_select_item');
		}
	} else {
		$tpl->assign('OPENDKIM_SELECT_ITEM', '');
	}
}

function opendkim_generateActivatedDomains($tpl, $resellerId)
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
			`opendkim` AS `t2` ON(`t1`.`domain_id` = `t2`.`domain_id`)
		WHERE
			`t1`.`domain_created_id` = '$resellerId'
		AND
			`t2`.`alias_id` = '0'
	";
		
	$stmt = execute_query($countQuery);
	$recordsCount = $stmt->fields['cnt'];

	$query = "
		SELECT
			`t2`.*
		FROM
			`domain` AS `t1`
		LEFT JOIN
			`opendkim` AS `t2` ON(`t1`.`domain_id` = `t2`.`domain_id`)
		WHERE
			`t1`.`domain_created_id` = ?
		AND
			`t2`.`alias_id` = '0'
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
			$query = "
				SELECT
					`t1`.*, `t2`.*
				FROM
					`opendkim` AS `t1`
				LEFT JOIN
					`domain_dns` AS `t2` 
						ON(
							`t1`.`domain_id` = `t2`.`domain_id` 
						AND
							`t1`.`alias_id` = `t2`.`alias_id`
						AND 
							`t2`.`domain_dns` = CONCAT('mail._domainkey.', `t1`.`domain_name`, '.')
						)
				WHERE
					`t1`.`domain_id` = ?
				ORDER BY
					`t1`.`domain_id` ASC, `t1`.`alias_id` ASC
			";
			$stmt2 = exec_query($query, $data['domain_id']);
			
			if ($stmt2->rowCount()) {
				while ($data2 = $stmt2->fetchRow()) {
					$tpl->assign(
						array(
							'OPENDKIM_DOMAIN_NAME' => decode_idna($data2['domain_name']),
							'OPENDKIM_DOMAIN_KEY' => ($data2['domain_text']) ? $data2['domain_text'] : tr('No OpenDKIM domain key in your dns table available. Please refresh this site'),
							'OPENDKIM_ID' => $data2['opendkim_id'],
							'OPENDKIM_KEY_STATUS' => $data2['opendkim_status']
						)
					);

					$tpl->parse('OPENDKIM_DOMAINKEY_ITEM', '.opendkim_domainkey_item');
				}
			} else {
				$tpl->assign('OPENDKIM_DOMAINKEY_ITEM', '');
			}
			
			$tpl->assign(
				array(
					'TR_OPENDKIM_DOMAIN' => tr('OpenDKIM domain entries for customer: %s', decode_idna($data['domain_name'])),
					'TR_OPENDKIM_DEACTIVATE_DOMAIN' => tr('Deativate OpenDKIM for customer: %s', decode_idna($data['domain_name'])),
					'TR_DEACTIVATE_DOMAIN_TOOLTIP' => tr('This will deactivate OpenDKIM for the customer %s.', decode_idna($data['domain_name'])),
					'OPENDKIM_DOMAIN_ID' => $data['domain_id']
				)
			);
			
			$tpl->parse('OPENDKIM_CUSTOMER_ITEM', '.opendkim_customer_item');
			
			$tpl->assign('OPENDKIM_DOMAINKEY_ITEM', '');
		}
		
		$tpl->assign('OPENDKIM_NO_CUSTOMER_ITEM', '');
		
		$tpl->parse('OPENDKIM_CUSTOMER_LIST', 'opendkim_customer_list');
	} else {
		$tpl->assign(
			array(
				'OPENDKIM_CUSTOMER_LIST' => '',
				'SCROLL_PREV' => '',
				'SCROLL_PREV_GRAY' => '',
				'SCROLL_NEXT' => '',
				'SCROLL_NEXT_GRAY' => '',
			)
		);
	}
}

function opendkim_activateDomain($tpl, $domainId, $resellerId)
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
		while ($data = $stmt->fetchRow()) {
			$query = "
				INSERT INTO
				    `opendkim` (
						`domain_id`, `alias_id`, `domain_name`,`customer_dns_previous_status`, `opendkim_status`
					) VALUES (
						?, ?, ?, ?, ?
					)
			";
			exec_query(
				$query,
				array(
					$data['domain_id'], '0', $data['domain_name'], $data['domain_dns'], $cfg->ITEM_TOADD_STATUS
				)
			);
		
			$query = "
				SELECT
					`alias_id`, `alias_name`
				FROM
					`domain_aliasses`
				WHERE
					`domain_id` = ?
				AND
					`alias_status` = ?
			";
			
			$stmt2 = exec_query($query, array($data['domain_id'], $cfg->ITEM_OK_STATUS));
			
			if ($stmt2->rowCount()) {
				while ($data2 = $stmt2->fetchRow()) {
					$query = "
						INSERT INTO
						    `opendkim` (
								`domain_id`, `alias_id`, `domain_name`,`customer_dns_previous_status`, `opendkim_status`
							) VALUES (
								?, ?, ?, ?, ?
							)
					";
					exec_query(
						$query,
						array(
							$data['domain_id'], $data2['alias_id'], $data2['alias_name'], '', $cfg->ITEM_TOADD_STATUS
						)
					);
				}
			}
		}
		
		send_request();
		
		set_page_message(
			tr('Domain activated for OpenDKIM support. This can take few seconds.'), 'success'
		);
	} else {
		set_page_message(
			tr("The domain you are trying to activate OpenDKIM doesn't exist."), 'error'
		);
	}
	
	redirectTo('opendkim.php');
}

function opendkim_deactivateDomain($tpl, $domainId, $resellerId)
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
		exec_query('UPDATE `opendkim` SET `opendkim_status` = ? WHERE `domain_id` = ?', array($cfg->ITEM_TODELETE_STATUS, $domainId));
		
		send_request();
		
		set_page_message(
			tr('Domain deactivated for OpenDKIM support. This can take few seconds.'), 'success'
		);
	} else {
		set_page_message(tr("The domain you are trying to deactivate OpenDKIM doesn't exist."), 'error');
	}
	
	redirectTo('opendkim.php');
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
		'page' => '../../plugins/OpenDKIM/frontend/reseller/opendkim.tpl',
		'page_message' => 'layout',
		'opendkim_select_item' => 'page',
		'opendkim_customer_list' => 'page',
		'opendkim_customer_item' => 'page',
		'opendkim_domainkey_item' => 'page',
		'opendkim_no_customer_item' => 'page',
		'scroll_prev_gray' => 'opendkim_customer_list',
		'scroll_prev' => 'opendkim_customer_list',
		'scroll_next_gray', 'opendkim_customer_list',
		'scroll_next' => 'opendkim_customer_list',
	)
);

if(isset($_GET['action']) && $_GET['action'] === 'deactivate') {
	$domainId = (isset($_GET['domain_id']) && $_GET['domain_id'] !== '') ? (int) clean_input($_GET['domain_id']) : '';
	
	if($domainId != '') {
		opendkim_deactivateDomain($tpl, $domainId, $_SESSION['user_id']);
	}
}

if(isset($_POST['action']) && $_POST['action'] === 'activate') {
	$domainId = (isset($_POST['domain_id']) && $_POST['domain_id'] !== '-1') ? clean_input($_POST['domain_id']) : '';
	
	if($domainId != '') {
		opendkim_activateDomain($tpl, $domainId, $_SESSION['user_id']);
	}
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Customers / OpenDKIM'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'DOMAIN_NOT_SELECTED' => tr("No domain selected."),
		'TR_OPENDKIM_SELECT_NAME_NONE' => tr('Select a customer'),
		'TR_SHOW' => tr('Activate OpenDKIM for this customer'),
		'TR_OPENDKIM_DOMAIN_NAME' => tr('Domain'),
		'TR_OPENDKIM_NO_DOMAIN' => tr('OpenDKIM domain entries'),
		'OPENDKIM_NO_DOMAIN' => tr('No domain for OpenDKIM support activated'),
		'TR_OPENDKIM_DOMAIN_KEY' => tr('OpenDKIM domain key'),
		'TR_OPENDKIM_KEY_STATUS' => tr('Status'),
		'DEACTIVATE_DOMAIN_ALERT' => tr('Are you sure? You want to deactivate OpenDKIM for this customer?'),
		'TR_PREVIOUS' => tr('Previous'),
		'TR_NEXT' => tr('Next')
	)
);

generateNavigation($tpl);

opendkim_generateSelect($tpl, $_SESSION['user_id']);
opendkim_generateActivatedDomains($tpl, $_SESSION['user_id']);

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
