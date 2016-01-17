<?php
/**
 * i-MSCP OpenDKIM plugin
 * Copyright (C) 2013-2016 Laurent Declercq <l.declercq@nuxwin.com>
 * Copyright (C) 2013-2016 Rene Schuster <mail@reneschuster.de>
 * Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>
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
 */

namespace OpenDKIM;

use iMSCP_Database as Database;
use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventManager;
use iMSCP_Exception_Database as DatabaseException;
use iMSCP_pTemplate as TemplateEngine;
use iMSCP_Registry as Registry;
use PDO;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Activate OpenDKIM for the given customer
 *
 * @throws DatabaseException
 * @param int $customerId Customer unique identifier
 */
function opendkim_activate($customerId)
{
	$stmt = exec_query(
		'
			SELECT
				domain_id, domain_name
			FROM
				domain
			INNER JOIN
				admin ON(admin_id = domain_admin_id)
			WHERE
				admin_id = ?
			AND
				created_by = ?
			AND
				admin_status = ?
		',
		array($customerId, $_SESSION['user_id'], 'ok')
	);

	if ($stmt->rowCount()) {
		$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
		$db = Database::getInstance();

		try {
			$db->beginTransaction();

			exec_query(
				'INSERT INTO opendkim (admin_id, domain_id, domain_name, opendkim_status) VALUES (?, ?, ?, ?)',
				array($customerId, $row['domain_id'], $row['domain_name'], 'toadd')
			);

			exec_query(
				'
					INSERT INTO
						opendkim (admin_id, domain_id, alias_id, domain_name, opendkim_status)
					SELECT
						?, domain_id, alias_id, alias_name, ?
					FROM
						domain_aliasses
					WHERE
						domain_id = ?
					AND
						alias_status = ?
				',
				array($customerId, 'toadd', $row['domain_id'], 'ok')
			);

			$db->commit();
			send_request();
			set_page_message(tr('OpenDKIM support scheduled for activation. This can take few seconds.'), 'success');
		} catch (DatabaseException $e) {
			$db->rollBack();
			throw $e;
		}
	} else {
		showBadRequestErrorPage();
	}
}

/**
 * Deactivate OpenDKIM for the given customer
 *
 * @param int $customerId Customer unique identifier
 * @return void
 */
function opendkim_deactivate($customerId)
{
	$stmt = exec_query(
		'SELECT COUNT(admin_id) AS cnt FROM admin WHERE admin_id = ? AND created_by = ? AND admin_status = ?',
		array($customerId, $_SESSION['user_id'], 'ok')
	);
	$row = $stmt->fetchRow(PDO::FETCH_ASSOC);

	if ($row['cnt']) {
		exec_query('UPDATE opendkim SET opendkim_status = ? WHERE admin_id = ?', array('todelete', $customerId));
		send_request();
		set_page_message(tr('OpenDKIM support scheduled for deactivation. This can take few seconds.'), 'success');
	} else {
		showBadRequestErrorPage();
	}
}

/**
 * Generate customer list for which OpenDKIM can be activated
 *
 * @param $tpl TemplateEngine
 * @return void
 */
function _opendkim_generateCustomerList($tpl)
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
		array($_SESSION['user_id'], 'ok')
	);

	if ($stmt->rowCount()) {
		while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			$tpl->assign(array(
				'SELECT_VALUE' => $row['admin_id'],
				'SELECT_NAME' => tohtml(decode_idna($row['admin_name'])),
			));

			$tpl->parse('SELECT_ITEM', '.select_item');
		}
	} else {
		$tpl->assign('SELECT_LIST', '');
	}
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl
 * @return void
 */
function opendkim_generatePage($tpl)
{
	_opendkim_generateCustomerList($tpl, $_SESSION['user_id']);

	$cfg = Registry::get('config');

	$rowsPerPage = $cfg['DOMAIN_ROWS_PER_PAGE'];

	if (isset($_GET['psi']) && $_GET['psi'] == 'last') {
		unset($_GET['psi']);
	}

	$startIndex = isset($_GET['psi']) ? (int)$_GET['psi'] : 0;

	$stmt = exec_query(
		'
			SELECT
				COUNT(admin_id) AS cnt
			FROM
				admin
			INNER JOIN
				opendkim USING(admin_id)
			WHERE
				created_by = ?
			AND
				alias_id IS NULL
		',
		array($_SESSION['user_id'])
	);
	$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
	$rowCount = $row['cnt'];

	if ($rowCount) {
		$stmt = exec_query(
			"
				SELECT
					admin_name, admin_id
				FROM
					admin
				INNER JOIN
					opendkim USING(admin_id)
				WHERE
					created_by = ?
				AND
					alias_id IS NULL
				ORDER BY
					admin_id ASC
				LIMIT
					$startIndex, $rowsPerPage
			",
			array($_SESSION['user_id'])
		);

		while ($row = $stmt->fetchRow()) {
			$stmt2 = exec_query(
				'
					SELECT
						opendkim_id, domain_name, opendkim_status, domain_dns, domain_text
					FROM
						opendkim
					LEFT JOIN domain_dns ON(
							domain_dns.domain_id = opendkim.domain_id
						AND
							domain_dns.alias_id = IFNULL(opendkim.alias_id, 0)
						AND
							owned_by = ?
					)
					WHERE
						admin_id = ?
				',
				array('OpenDKIM_Plugin', $row['admin_id'])
			);

			if ($stmt2->rowCount()) {
				while ($row2 = $stmt2->fetchRow()) {
					if ($row2['opendkim_status'] == 'ok') {
						$statusIcon = 'ok';
					} elseif ($row2['opendkim_status'] == 'disabled') {
						$statusIcon = 'disabled';
					} elseif (in_array($row2['opendkim_status'], array(
						'toadd', 'tochange', 'todelete', 'torestore', 'tochange', 'toenable', 'todisable',
						'todelete'))
					) {
						$statusIcon = 'reload';
					} else {
						$statusIcon = 'error';
					}

					if ($row2['domain_text']) {
						if (strpos($row2['domain_dns'], ' ') !== false) {
							$dnsName = explode(' ', $row2['domain_dns']);
							$dnsName = $dnsName[0];
						} else {
							$dnsName = $row2['domain_dns'];
						}
					} else {
						$dnsName = '';
					}

					$tpl->assign(array(
						'KEY_STATUS' => translate_dmn_status($row2['opendkim_status']),
						'STATUS_ICON' => $statusIcon,
						'DOMAIN_NAME' => tohtml(decode_idna($row2['domain_name'])),
						'DOMAIN_KEY' => ($row2['domain_text'])
							? tohtml($row2['domain_text']) : tr('Generation in progress.'),
						'DNS_NAME' => ($dnsName) ? tohtml($dnsName) : tr('n/a'),
						'OPENDKIM_ID' => tohtml($row2['opendkim_id'])
					));

					$tpl->parse('KEY_ITEM', '.key_item');
				}
			}

			$tpl->assign(array(
				'TR_CUSTOMER' => tr('OpenDKIM entries for customer: %s', decode_idna($row['admin_name'])),
				'TR_DEACTIVATE' => tr('Deactivate OpenDKIM'),
				'CUSTOMER_ID' => tohtml($row['admin_id'])
			));

			$tpl->parse('CUSTOMER_ITEM', '.customer_item');
			$tpl->assign('KEY_ITEM', '');
		}

		$prevSi = $startIndex - $rowsPerPage;

		if ($startIndex == 0) {
			$tpl->assign('SCROLL_PREV', '');
		} else {
			$tpl->assign(array(
				'SCROLL_PREV_GRAY' => '',
				'PREV_PSI' => $prevSi
			));
		}

		$nextSi = $startIndex + $rowsPerPage;

		if ($nextSi + 1 > $rowCount) {
			$tpl->assign('SCROLL_NEXT', '');
		} else {
			$tpl->assign(array(
				'SCROLL_NEXT_GRAY' => '',
				'NEXT_PSI' => $nextSi
			));
		}
	} else {
		$tpl->assign('CUSTOMER_LIST', '');
		set_page_message(tr('No customer with OpenDKIM support has been found.'), 'static_info');
	}
}

/***********************************************************************************************************************
 * Main
 */

EventManager::getInstance()->dispatch(Events::onResellerScriptStart);
check_login('reseller');

if (resellerHasCustomers()) {
	if (isset($_REQUEST['action'])) {
		$action = clean_input($_REQUEST['action']);

		if (isset($_REQUEST['admin_id']) && $_REQUEST['admin_id'] != '') {
			$customerId = intval($_REQUEST['admin_id']);

			switch ($action) {
				case 'activate':
					opendkim_activate($customerId);
					break;
				case 'deactivate';
					opendkim_deactivate($customerId);
					break;
				default:
					showBadRequestErrorPage();
			}

			redirectTo('opendkim.php');
		} else {
			showBadRequestErrorPage();
		}
	}

	$tpl = new TemplateEngine();
	$tpl->define_dynamic(array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => '../../plugins/OpenDKIM/themes/default/view/reseller/opendkim.tpl',
		'page_message' => 'layout',
		'select_list' => 'page',
		'select_item' => 'select_list',
		'customer_list' => 'page',
		'customer_item' => 'customer_list',
		'key_item' => 'customer_item',
		'scroll_prev_gray' => 'customer_list',
		'scroll_prev' => 'customer_list',
		'scroll_next_gray', 'customer_list',
		'scroll_next' => 'customer_list'
	));

	$tpl->assign(array(
		'TR_PAGE_TITLE' => tr('Customers / OpenDKIM'),
		'TR_SELECT_NAME' => tr('Select a customer'),
		'TR_ACTIVATE_ACTION' => tr('Activate OpenDKIM for this customer'),
		'TR_DOMAIN_NAME' => tr('Domain Name'),
		'TR_DOMAIN_KEY' => tr('OpenDKIM domain key'),
		'TR_STATUS' => tr('Status'),
		'TR_DNS_NAME' => tr('Name'),
		'DEACTIVATE_DOMAIN_ALERT' => tojs(tr('Are you sure you want to deactivate OpenDKIM for this customer?')),
		'TR_PREVIOUS' => tr('Previous'),
		'TR_NEXT' => tr('Next')
	));

	generateNavigation($tpl);
	opendkim_generatePage($tpl);
	generatePageMessage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');
	EventManager::getInstance()->dispatch(Events::onResellerScriptEnd, array('templateEngine' => $tpl));
	$tpl->prnt();
} else {
	showBadRequestErrorPage();
}
