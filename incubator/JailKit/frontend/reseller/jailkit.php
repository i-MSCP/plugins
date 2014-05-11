<?php
/**
 * i-MSCP JailKit plugin
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
 * Activate SSH feature for the given customer
 *
 * @param $pluginManager iMSCP_Plugin_Manager
 * @param int $customerId Customer unique identifier
 * @return void
 */
function jailkit_activateSsh($customerId)
{
	if ($customerId) {
		$stmt = exec_query(
			'
				INSERT IGNORE INTO
					jailkit_jails (jail_owner_id, jail_max_logins, jail_status)
				SELECT
					admin_id, ?, ?
				FROM
					admin
				WHERE
				 	admin_id = ?
				AND
					created_by = ?
				AND
					admin_status = ?
			',
			array('1', 'toadd', $customerId, $_SESSION['user_id'], 'ok')
		);

		if ($stmt->rowCount()) {
			send_request();
			write_log(
				"{$_SESSION['user_logged']} activated SSH feature for customer with ID $customerId", E_USER_NOTICE
			);
			set_page_message(tr('SSH feature scheduled for activation. This can take few seconds.'), 'success');
			redirectTo('ssh_accounts.php');
		}
	}

	showBadRequestErrorPage();
}

/**
 * Deactivate SSH feature for the given customer
 *
 * @param int $customerId Customer unique identifier
 * @return void
 */
function jailkit_deactivateSsh($customerId)
{
	if ($customerId) {
		$stmt = exec_query(
			'
				UPDATE
					jailkit_jails
				INNER JOIN
					admin ON(admin_id = jail_owner_id)
				SET
					jail_status = ?
				WHERE
					admin_id = ?
				AND
					created_by = ?
				AND
					admin_status = ?
			',
			array('todelete', $customerId, $_SESSION['user_id'], 'ok')
		);

		if ($stmt->rowCount()) {
			send_request();
			write_log(
				"{$_SESSION['user_logged']} deactivated SSH feature for customer with ID $customerId", E_USER_NOTICE
			);
			set_page_message(tr('SSH feature scheduled for deactivation. This can take few seconds.'), 'success');
			redirectTo('ssh_accounts.php');
		}
	}

	showBadRequestErrorPage();
}

/**
 * Change customer jail
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $customerId Customer unique identifier
 * @return void
 */
function jailkit_editCustomerJail($tpl, $customerId)
{
	if ($customerId && isset($_POST['max_logins'])) {
		$maxLogins = clean_input($_POST['max_logins']);

		$stmt = exec_query(
			'
				SELECT
					jail_id, count(jail_login_id) as login_cnt
				FROM
					jailkit_jails
				INNER JOIN
					admin ON(admin_id = jail_owner_id)
				LEFT JOIN
					jailkit_login USING(jail_id)
				WHERE
					admin_id = ?
				AND
					created_by = ?
			',
			array($customerId, $_SESSION['user_id'])
		);
		$row = $stmt->fetchRow();

		if (!is_null($row['jail_id'])) {
			$error = false;

			if (!is_number($_POST['max_logins'])) {
				set_page_message(tr('Invalid SSH user limit.'), 'error');
				$error = true;
			} elseif($maxLogins != 0 && $maxLogins < $row['login_cnt']) {
				set_page_message('SSH user limit cannot be lower than the number of existent SSH users.', 'error');
				$error = true;
			}

			if (!$error) {
				exec_query(
                    'UPDATE jailkit_jails SET jail_max_logins = ? WHERE jail_owner_id = ?',
                    array($maxLogins, $customerId)
                );
				write_log(
					"{$_SESSION['user_logged']} edited SSH user limit for customer with ID $customerId", E_USER_NOTICE
				);
				set_page_message(tr('SSH user limit has been updated.'), 'success');
			} else {
				$tpl->assign(
					array(
						'MAX_LOGINS' => tohtml($maxLogins),
						'JAILKIT_EDIT_ADMIN_ID' => $customerId,
						'JAILKIT_DIALOG_OPEN' => 1
					)
				);

				return;
			}
		}

		redirectTo('ssh_accounts.php');
	}

	showBadRequestErrorPage();
}

/**
 * Suspend/Unsuspend SSH feature for the given customer
 *
 * @param int $customerId Customer unique identifier
 * @param string $action Action (suspend|unsuspend)
 * @return void
 */
function jailkit_changeCustomerJail($customerId, $action)
{
	if ($customerId) {
		if ($action == 'unsuspend') {
			$bindParams = array('ok', '0', 'tochange', $customerId);
		} else {
			$bindParams = array('disabled', '1', 'tochange', $customerId);
		}

		$stmt = exec_query(
			'
				UPDATE
					jailkit_jails
				LEFT JOIN
					jailkit_ssh_logins USING(jail_id)
				SET
					jail_status = ?, ssh_login_locked = ?, ssh_login_status = ?
				WHERE
					jail_owner_id = ?
			',
			$bindParams
		);

		if ($stmt->rowCount()) {
			send_request();

			if ($action == 'unsuspend') {
				write_log(
					"{$_SESSION['user_logged']} activated SSH feature for customer with ID $customerId", E_USER_NOTICE
				);
				set_page_message(tr('SSH feature scheduled for activation. This can take few seconds.'), 'success');
			} else {
				write_log(
					"{$_SESSION['user_logged']} deactivated SSH feature for customer with ID $customerId", E_USER_NOTICE
				);
				set_page_message(tr('SSH feature scheduled for deactivation. This can take few seconds.'), 'success');
			}

			redirectTo('ssh_accounts.php');
		}
	}

	showBadRequestErrorPage();
}

/**
 * Return SSH user limit for the given customer
 *
 * @param int $customerId Customer unique identifier
 * @return string
 */
function get_jailkitLoginLimit($customerId)
{
	$stmt = exec_query(
		'
			SELECT
				jail_max_logins, COUNT(ssh_login_id) AS login_cnt
			FROM
				jailkit_jails
			LEFT JOIN
				jailkit_ssh_logins USING(jail_id)
			WHERE
				jail_owner_id = ?
		',
		$customerId
	);

	$row = $stmt->fetchRow(PDO::FETCH_ASSOC);

	return $row['login_cnt'] . ' / ' . (($row['max_logins'] == 0) ? tr('unlimited') : $row['max_logins']);
}

/**
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl
 * @return void
 */
function jailkit_generatePage($tpl)
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
				admin_id NOT IN(SELECT jail_owner_id FROM jailkit_jails)
			ORDER BY
				admin_name ASC
		',
		array($_SESSION['user_id'], 'ok')
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
		$tpl->assign('JAILKIT_SELECT_ITEMS', '');
	}

	$stmt = exec_query(
		'
			SELECT
				jail_status, jail_owner_id, admin_name AS jail_owner_name
			FROM
				jailkit_jails
			INNER JOIN
				admin ON(admin_id = jail_owner_id)
			WHERE
				created_by = ?
			ORDER BY
				jail_owner_name ASC
		',
		$_SESSION['user_id']
	);

	if ($stmt->rowCount()) {
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

		foreach ($rows as $row) {
			if ($row['jail_status'] == 'ok') {
				$statusIcon = 'ok';
				$tpl->assign(
					array(
						'TR_CHANGE_ACTION_TOOLTIP' => tr('Suspend SSH feature for this customer'),
						'TR_CHANGE_ALERT' => tr('Are you sure you want to suspend SSH feature for this customer?'),
						'CHANGE_ACTION' => 'suspend'
					)
				);
			} elseif ($row['jail_status'] == 'disabled') {
				$statusIcon = 'disabled';
				$tpl->assign(
					array(
						'TR_CHANGE_ACTION_TOOLTIP' => tr('Unsuspend SSH feature for this customer'),
						'TR_CHANGE_ALERT' => tr('Are you sure you want to unsuspend SSH feature for this customer?'),
						'CHANGE_ACTION' => 'unsuspend'
					)
				);
			} elseif (
				$row['jail_status'] == 'toadd' || $row['jail_status'] == 'tochange' || $row['jail_status'] == 'todelete'
			) {

				$statusIcon = 'reload';
			} else {
				$statusIcon = 'error';
			}

			$tpl->assign(
				array(
					'JAILKIT_CUSTOMER_NAME' => tohtml(decode_idna($row['jail_owner_name'])),
					'JAILKIT_STATUS' => translate_dmn_status($row['jail_status']),
					'JAILKIT_LOGIN_LIMIT' => get_jailkitLoginLimit($row['jail_owner_name']),
					'JAILKIT_ADMIN_ID' => $row['jail_owner_name'],
					'STATUS_ICON' => $statusIcon
				)
			);

			if (!in_array($row['jailkit_status'], array('ok', 'disabled'))) {
				$tpl->assign(
					array(
						'JAILKIT_ACTION_STATUS_LINK' => '',
						'JAILKIT_ACTION_LINKS' => ''
					)
				);
				$tpl->parse('JAILKIT_ACTION_STATUS_STATIC', 'jailkit_action_status_static');
			} else {
				$tpl->assign('JAILKIT_ACTION_STATUS_STATIC', '');
				$tpl->parse('JAILKIT_ACTION_STATUS_LINK', 'jailkit_action_status_link');
				$tpl->parse('JAILKIT_ACTION_LINKS', 'jailkit_action_links');
			}

			$tpl->parse('JAILKIT_CUSTOMER_ITEM', '.jailkit_customer_item');
		}
	} else {
		$tpl->assign(
			array(
				'JAILKIT_CUSTOMER_LIST' => '',
				'JAILKIT_EDIT_DIALOG' => '',
				'JAILKIT_JS' => ''
			)
		);

		set_page_message('No customer with SSH feature has been found.', 'info');
	}
}

/***********************************************************************************************************************
 * Main
 */

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

resellerHasCustomers() or showBadRequestErrorPage();

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => '../../plugins/JailKit/frontend/reseller/jailkit.tpl',
		'page_message' => 'layout',
		'jailkit_list' => 'page',
		'jailkit_select_item' => 'jailkit_list',
		'jailkit_customer_list' => 'jailkit_list',
		'jailkit_select_items' => 'jailkit_customer_list',
		'jailkit_customer_item' => 'jailkit_select_items',
		'jailkit_action_status_link' => 'jailkit_customer_item',
		'jailkit_action_status_static' => 'jailkit_customer_item',
		'jailkit_action_links' => 'jailkit_customer_item',
		'jailkit_edit_dialog' => 'jailkit_list',
		'jailkit_js' => 'jailkit_list'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Reseller / Customers - SSH Accounts'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations(),
		'TR_DIALOG_TITLE' => tojs(tr('Edit SSH user limit', true)),
		'TR_JAILKIT_SELECT_NAME_NONE' => tr('Select a customer'),
		'TR_SELECT_ACTION' => tr('Activate SSH support'),
		'TR_SELECT_ACTION_TOOLTIP' => tr('Activate SSH support for the selected customer'),
		'TR_UPDATE' => tr('Update'),
		'TR_CANCEL' => tr('Cancel'),
		'TR_JAILKIT_CUSTOMER_NAME' => tr('Customer'),
		'TR_JAILKIT_STATUS' => tr('Status'),
		'TR_JAILKIT_LOGIN_LIMIT' => tr('SSH users'),
		'DEACTIVATE_CUSTOMER_ALERT' => tr('Are you sure you want to deactivate SSH support for this customer?'),
		'TR_PREVIOUS' => tr('Previous'),
		'TR_JAILKIT_ACTIONS' => tr('Actions'),
		'TR_EDIT_TOOLTIP' => tr('Edit SSH user limit for this customer'),
		'TR_EDIT' => tr('Edit'),
		'TR_DELETE_JAIL' => tr('Deactivate'),
		'TR_DEACTIVATE_TOOLTIP' => tr('Deactivate SSH support for this customer'),
		'TR_MAX_SSH_USERS' => tr('Max. SSH users') . '<br /><i>(0 ' . tr('unlimited') . ')</i>',
		'JAILKIT_DIALOG_OPEN' => 0,
		'TR_DIALOG_EDIT' => tojs(tr('Edit', true)),
		'TR_DIALOG_CANCEL' => tojs(tr('CANCEL', true)),
		'MAX_LOGINS' => '',
		'JAILKIT_EDIT_ADMIN_ID' => ''
	)
);

if (isset($_REQUEST['action'])) {
	$action = clean_input($_REQUEST['action']);
	$customerId = (isset($_REQUEST['admin_id'])) ? clean_input($_REQUEST['admin_id']) : '';

	if ($action == 'activate') {
		jailkit_activateSsh($customerId);
	} elseif ($action == 'deactivate') {
		jailkit_deactivateSsh($customerId);
	} elseif ($action == 'suspend' || $action == 'unsuspend') {
		jailkit_changeCustomerJail($customerId, $action);
	} elseif ($action == 'edit') {
		jailkit_editCustomerJail($tpl, $customerId);
	} else {
		showBadRequestErrorPage();
	}
}

generateNavigation($tpl);
jailkit_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
