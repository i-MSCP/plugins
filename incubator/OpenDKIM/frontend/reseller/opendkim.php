<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP Team
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
 * @param int $resellerId
 * @return void
 */
function opendkim_generateSelect($tpl, $resellerId)
{
	$stmt = exec_query(
		'
			SELECT
				admin_id, admin_name
			FROM
				admin
			WHERE
				created_by = ?
			AND
				admin_status = ?
			AND
				admin_id NOT IN (SELECT admin_id FROM opendkim)
			ORDER BY
				admin_name ASC
		',
		array($resellerId, 'ok')
	);

	if ($stmt->rowCount()) {
		while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			$tpl->assign(
				array(
					'TR_OPENDKIM_SELECT_VALUE' => $row['admin_id'],
					'TR_OPENDKIM_SELECT_NAME' => decode_idna($row['admin_name']),
				)
			);

			$tpl->parse('OPENDKIM_SELECT_ITEM', '.opendkim_select_item');
		}
	} else {
		$tpl->assign('OPENDKIM_SELECT_ITEM', '');
	}
}

/**
 * Generate activated domains
 *
 * @param iMSCP_pTemplate $tpl
 * @param $resellerId
 */
function opendkim_generateActivatedDomains($tpl, $resellerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$rowsPerPage = $cfg['DOMAIN_ROWS_PER_PAGE'];

	if (isset($_GET['psi']) && $_GET['psi'] == 'last') {
		unset($_GET['psi']);
	}

	$startIndex = isset($_GET['psi']) ? (int)$_GET['psi'] : 0;

	$stmt = exec_query(
		'
			SELECT
				COUNT(t1.admin_id) AS cnt
			FROM
				admin AS t1
			LEFT JOIN
				opendkim AS t2 ON(t2.admin_id = t1.admin_id)
			WHERE
				t1.created_by = ?
			AND
				t2.alias_id = ?
		',
		array($resellerId, 0)
	);
	$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
	$recordsCount = $row['cnt'];

	$stmt = exec_query(
		"
			SELECT
				t1.admin_name, t1.admin_id, t2.*
			FROM
				admin AS t1
			LEFT JOIN
				opendkim AS t2 ON(t2.admin_id = t1.admin_id)
			WHERE
				t1.created_by = ?
			AND
				t1.admin_id IN (SELECT admin_id FROM opendkim)
			AND
				t2.alias_id = ?
			ORDER BY
				t1.admin_id ASC
			LIMIT
				$startIndex, $rowsPerPage
		"
		,
		array($resellerId, 0)
	);

	if ($recordsCount) {
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

		while ($row = $stmt->fetchRow()) {
			$stmt2 = exec_query(
				'
					SELECT
						t1.*, t2.*
					FROM
						opendkim AS t1
					LEFT JOIN
						domain_dns AS t2 ON(
							t2.domain_id = t1.domain_id AND t2.alias_id = t1.alias_id AND t2.domain_dns = ?
						)
					WHERE
						t1.admin_id = ?
					ORDER BY
						t1.domain_id ASC, t1.alias_id ASC
				',
				array('mail._domainkey', $row['admin_id'])
			);

			if ($stmt2->rowCount()) {
				while ($row2 = $stmt2->fetchRow()) {
					if ($row2['opendkim_status'] == 'ok') {
						$statusIcon = 'ok';
					} elseif ($row2['opendkim_status'] == 'disabled') {
						$statusIcon = 'disabled';
					} elseif (
						in_array(
							$row2['opendkim_status'],
							array(
								'toadd', 'tochange', 'todelete', 'torestore', 'tochange', 'toenable', 'todisable',
								'todelete'
							)
						)
					) {
						$statusIcon = 'reload';
					} else {
						$statusIcon = 'error';
					}

					$tpl->assign(
						array(
							'OPENDKIM_DOMAIN_NAME' => decode_idna($row2['domain_name']),
							'OPENDKIM_DOMAIN_KEY' => ($row2['domain_text'])
								? $row2['domain_text']
								: tr('No OpenDKIM domain key has been found. Please reload the page'),
							'OPENDKIM_ID' => $row2['opendkim_id'],
							'OPENDKIM_DNS_NAME' => decode_idna($row2['domain_dns']),
							'OPENDKIM_KEY_STATUS' => translate_dmn_status($row2['opendkim_status']),
							'STATUS_ICON' => $statusIcon
						)
					);

					$tpl->parse('OPENDKIM_DOMAINKEY_ITEM', '.opendkim_domainkey_item');
				}
			} else {
				$tpl->assign('OPENDKIM_DOMAINKEY_ITEM', '');
			}

			$tpl->assign(
				array(
					'TR_OPENDKIM_CUSTOMER' => tr(
						'OpenDKIM domain entries for customer: %s', decode_idna($row['admin_name'])
					),
					'TR_OPENDKIM_DEACTIVATE_CUSTOMER' => tr(
						'Deactivate OpenDKIM for customer: %s', decode_idna($row['admin_name'])
					),
					'TR_DEACTIVATE_CUSTOMER_TOOLTIP' => tr(
						'This will deactivate OpenDKIM for the customer %s.', decode_idna($row['admin_name'])
					),
					'OPENDKIM_CUSTOMER_ID' => $row['admin_id']
				)
			);

			$tpl->parse('OPENDKIM_CUSTOMER_ITEM', '.opendkim_customer_item');
			$tpl->assign('OPENDKIM_DOMAINKEY_ITEM', '');
		}

		$tpl->assign('OPENDKIM_NO_CUSTOMER_ITEM', '');
	} else {
		$tpl->assign(
			array(
				'OPENDKIM_CUSTOMER_LIST' => '',
				'SCROLL_PREV' => '',
				'SCROLL_PREV_GRAY' => '',
				'SCROLL_NEXT' => '',
				'SCROLL_NEXT_GRAY' => ''
			)
		);
	}
}

/**
 * Activate OpenDKIM for the given customer
 *
 * @param int $customerAdminId Customer unique identifier
 * @param int $resellerId Reseller unique identifier
 */
function opendkim_activateDomain($customerAdminId, $resellerId)
{
	$stmt = exec_query(
		'
			SELECT
				t2.domain_id, t2.domain_name, t2.domain_dns
			FROM
				admin AS t1
			LEFT JOIN
				domain AS t2 ON(t2.domain_admin_id = t1.admin_id)
			WHERE
				t1.admin_id = ?
			AND
				t1.created_by = ?
			AND
				t1.admin_status = ?
		',
		array($customerAdminId, $resellerId, 'ok')
	);

	if ($stmt->rowCount()) {
		while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			exec_query(
				'
					INSERT INTO opendkim (
						admin_id, domain_id, alias_id, domain_name, customer_dns_previous_status, opendkim_status
					) VALUES (
						?, ?, ?, ?, ?, ?
					)
				',
				array(
					$customerAdminId, $row['domain_id'], '0', $row['domain_name'], $row['domain_dns'],
					'toadd'
				)
			);

			$stmt2 = exec_query(
				'SELECT alias_id, alias_name FROM domain_aliasses WHERE domain_id = ? AND alias_status = ?',
				array($row['domain_id'], 'ok')
			);

			if ($stmt2->rowCount()) {
				while ($row2 = $stmt2->fetchRow(PDO::FETCH_ASSOC)) {
					exec_query(
						'
							INSERT INTO  opendkim (
								admin_id, domain_id, alias_id, domain_name, customer_dns_previous_status, opendkim_status
							) VALUES (
								?, ?, ?, ?, ?, ?
							)
						',
						array(
							$customerAdminId, $row['domain_id'], $row2['alias_id'], $row2['alias_name'], '', 'toadd'
						)
					);
				}
			}
		}

		send_request();

		set_page_message(tr('OpenDKIM support scheduled for activation. This can take few seconds.'), 'success');
	} else {
		showBadRequestErrorPage();
	}

	redirectTo('opendkim.php');
}

/**
 * Deactivate OpenDKIM for the given customer
 *
 * @param int $customerAdminId Customer unique identifier
 * @param int $resellerId Reseller unique identifier
 */
function opendkim_deactivateDomain($customerAdminId, $resellerId)
{
	$stmt = exec_query(
		'SELECT admin_id, admin_name FROM admin WHERE admin_id = ? AND created_by = ? AND admin_status = ?',
		array($customerAdminId, $resellerId, 'ok'));

	if ($stmt->rowCount()) {
		exec_query('UPDATE opendkim SET opendkim_status = ? WHERE admin_id = ?', array('todelete', $customerAdminId));

		send_request();
		set_page_message(tr('OpenDKIM support scheduled for deactivation. This can take few seconds.'), 'success');
	} else {
		showBadRequestErrorPage();
	}

	redirectTo('opendkim.php');
}

/***********************************************************************************************************************
 * Main
 */

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

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
		'scroll_next' => 'opendkim_customer_list'
	)
);

if (isset($_GET['action']) && $_GET['action'] == 'deactivate') {
	$customerAdminId = (isset($_GET['admin_id']) && $_GET['admin_id'] !== '')
		? (int)clean_input($_GET['admin_id']) : '';

	if ($customerAdminId != '') {
		opendkim_deactivateDomain($customerAdminId, $_SESSION['user_id']);
	}
}

if (isset($_POST['action']) && $_POST['action'] == 'activate') {
	$customerAdminId = (isset($_POST['admin_id']) && $_POST['admin_id'] !== '-1')
		? clean_input($_POST['admin_id']) : '';

	if ($customerAdminId != '') {
		opendkim_activateDomain($customerAdminId, $_SESSION['user_id']);
	}
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Customers / OpenDKIM'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'DOMAIN_NOT_SELECTED' => tr('No domain selected.'),
		'TR_OPENDKIM_SELECT_NAME_NONE' => tr('Select a domain'),
		'TR_SHOW' => tr('Activate OpenDKIM for this domain'),
		'TR_OPENDKIM_DOMAIN_NAME' => tr('Domain'),
		'TR_OPENDKIM_NO_DOMAIN' => tr('OpenDKIM domain entries'),
		'OPENDKIM_NO_DOMAIN' => tr('No domain with OpenDKIM support has been found'),
		'TR_OPENDKIM_DOMAIN_KEY' => tr('OpenDKIM domain key'),
		'TR_OPENDKIM_KEY_STATUS' => tr('Status'),
		'TR_OPENDKIM_DNS_NAME' => tr('Name'),
		'DEACTIVATE_DOMAIN_ALERT' => tr('Are you sure you want to deactivate OpenDKIM for this domain?'),
		'TR_PREVIOUS' => tr('Previous'),
		'TR_NEXT' => tr('Next')
	)
);

generateNavigation($tpl);
opendkim_generateSelect($tpl, $_SESSION['user_id']);
opendkim_generateActivatedDomains($tpl, $_SESSION['user_id']);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
