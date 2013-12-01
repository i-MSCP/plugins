<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) Laurent Declercq <l.declercq@nuxwin.com>
 * Copyright (C) Sascha Bay <info@space2place.de>
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
 * @copyright   Laurent Declercq <l.declercq@nuxwin.com>
 * @copyright   Sascha Bay <info@space2place.de>
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @author      Sascha Bay <info@space2place.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate SSH user list
 *
 * @param $tpl iMSCP_pTemplate
 * @param iMSCP_Plugin_Manager $pluginManager
 * @param int $customerId Customer unique identifier
 * @return void
 */
function jailkit_generateSshUserList($tpl, $customerId)
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
				jailkit_login_id, ssh_login_name, jailkit_login_status, jailkit_status
			FROM
				jailkit_login
			INNER JOIN
				jailkit USING(admin_id)
			WHERE
				admin_id = ?
			ORDER BY
				ssh_login_name ASC
		',
		$customerId
	);


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

		$stmt2 = exec_query('SELECT COUNT(admin_id) AS cnt FROM  jailkit_login WHERE admin_id = ?', $customerId);
		$recordsCount = $stmt2->fields['cnt'];

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
			if ($data['jailkit_login_status'] == $cfg['ITEM_OK_STATUS']) {
				$statusIcon = 'ok';
			} elseif ($data['jailkit_login_status'] == $cfg['ITEM_DISABLED_STATUS']) {
				$statusIcon = 'disabled';
			} elseif (
				(
					$data['jailkit_login_status'] == $cfg['ITEM_TOADD_STATUS'] ||
					$data['jailkit_login_status'] == $cfg['ITEM_TOCHANGE_STATUS'] ||
					$data['jailkit_login_status'] == $cfg['ITEM_TODELETE_STATUS']
				) ||
				(
					$data['jailkit_login_status'] == $cfg['ITEM_TOADD_STATUS'] ||
					$data['jailkit_login_status'] == $cfg['ITEM_TORESTORE_STATUS'] ||
					$data['jailkit_login_status'] == $cfg['ITEM_TOCHANGE_STATUS'] ||
					$data['jailkit_login_status'] == $cfg['ITEM_TOENABLE_STATUS'] ||
					$data['jailkit_login_status'] == $cfg['ITEM_TODISABLE_STATUS'] ||
					$data['jailkit_login_status'] == $cfg['ITEM_TODELETE_STATUS']
				)
			) {
				$statusIcon = 'reload';
			} else {
				$statusIcon = 'error';
			}

			$tpl->assign(
				array(
					'JAILKIT_USER_NAME' => tohtml($data['ssh_login_name']),
					'JAILKIT_LOGIN_ID' => tohtml($data['jailkit_login_id']),
					'JAILKIT_LOGIN_STATUS' => tohtml(translate_dmn_status($data['jailkit_login_status'])),
					'STATUS_ICON' => $statusIcon
				)
			);

			if($data['jailkit_login_status'] != $cfg['ITEM_OK_STATUS']) {
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

			if ($data['jailkit_status'] == $cfg['ITEM_DISABLED_STATUS']) {
				$tpl->parse('JAILKIT_LOGIN_ITEM_DISABLED', '.jailkit_login_item_disabled');
				$tpl->assign('JAILKIT_LOGIN_ITEM', '');
			} else {
				$tpl->parse('JAILKIT_LOGIN_ITEM', '.jailkit_login_item');
				$tpl->assign('JAILKIT_LOGIN_ITEM_DISABLED', '');
			}
		}

		$tpl->assign('JAILKIT_NO_LOGIN_ITEM', '');
		$tpl->parse('JAILKIT_LOGIN_LIST', 'jailkit_login_list');
	} else {
		$tpl->assign(
			array(
				'JAILKIT_LOGIN_LIST' => '',
				'SCROLL_PREV' => '',
				'SCROLL_PREV_GRAY' => '',
				'SCROLL_NEXT' => '',
				'SCROLL_NEXT_GRAY' => ''
			)
		);
	}

	$tpl->assign('JAILKIT_EDIT_LOGIN', '');
}

/**
 * Add SSH user
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $customerId Customer unique identifier
 * @return bool
 */
function jailkit_addSshUser($tpl, $customerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (isset($_POST['ssh_login_name']) && isset($_POST['ssh_login_pass']) && isset($_POST['ssh_login_pass_confirm'])) {
		$error = false;

		$loginUsername = 'jk_' . clean_input($_POST['ssh_login_name']);

		$loginPassword = clean_input($_POST['ssh_login_pass']);
		$loginPasswordConfirm = clean_input($_POST['ssh_login_pass_confirm']);

		$stmt = exec_query('SELECT max_logins, jailkit_status FROM jailkit WHERE admin_id = ?', $customerId);

		$maxLogins = $stmt->fields['max_logins'];
		$accountDisabled = $stmt->fields['jailkit_status'];

		$stmt = exec_query('SELECT COUNT(jailkit_login_id) AS cnt FROM  jailkit_login WHERE admin_id = ?', $customerId);
		$activatedLogins = $stmt->fields['cnt'];

		if ($accountDisabled == $cfg->ITEM_DISABLED_STATUS) {
			$error = true;
		} elseif ($maxLogins != '0' && $activatedLogins >= $maxLogins) {
			set_page_message(tr('SSH account limit is reached.'), 'error');
			$error = true;
		} elseif (strlen($loginUsername) < 4) {
			set_page_message(tr('Username is empty.'), 'error');
			$error = true;
		} elseif (strlen(clean_input($_POST['ssh_login_name'])) > 16) {
			set_page_message(tr("Username is too long (max. 16 characters)."), 'error');
			$error = true;
		} elseif ($loginPassword !== $loginPasswordConfirm) {
			set_page_message(tr('Passwords do not match.'), 'error');
			$error = true;
		} elseif (!preg_match("/^[a-z][a-z0-9]*/", clean_input($_POST['ssh_login_name']))) {
			set_page_message(
				tr('Username must begin with a lower case letter, followed by lower case letters or digits.'), 'error'
			);
			$error = true;
		}

		if (!checkPasswordSyntax($loginPassword)) {
			$error = true;
		}

		if (!$error) {
			try {
				exec_query(
					'
						INSERT INTO jailkit_login (
							admin_id, ssh_login_name, ssh_login_pass, jailkit_login_status
						) VALUES(
							?, ?, ?, ?
						)
					',
					array($_SESSION['user_id'], $loginUsername, $loginPassword, $cfg->ITEM_TOADD_STATUS)
				);
			} catch (iMSCP_Exception_Database $e) {
				if ($e->getCode() == 23000) { // Duplicate entries
					set_page_message(tr("The JailKit username $loginUsername already exist."), 'error');
					return false;
				}
			}

			send_request();
			return true;
		} else {
			$tpl->assign(
				array(
					'JAILKIT_DIALOG_OPEN' => 1,
					'JAILKIT_USERNAME' => tohtml($_POST['ssh_login_name'])
				)
			);
			return false;
		}
	}

	redirectTo('jailkit.php');
	exit;
}

/**
 * Activate/Deactivate SSH user
 *
 * @param int $customerId Customer unique identifier
 * @param int $sshUserId SSH user unique identifier
 * @return void
 */
function jailkit_ChangeSshUserStatus($customerId, $sshUserId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$stmt = exec_query(
		'
			SELECT
				jailkit_login_status
			FROM
				jailkit_login
			INNER JOIN
				jailkit USING(admin_id)
			WHERE
				admin_id = ?
			AND
				jailkit_login_id = ?
			AND
				jailkit_login_status IN (?, ?)
			AND
				jailkit_status = ?
		',
		array($customerId, $sshUserId, $cfg['ITEM_OK_STATUS'], $cfg['ITEM_DISABLED_STATUS'], $cfg['ITEM_OK_STATUS'])
	);

	if ($stmt->rowCount()) {
		if ($stmt->fields['jailkit_login_status'] == $cfg['ITEM_DISABLED_STATUS']) {
			exec_query(
				'UPDATE jailkit_login SET ssh_login_locked = ?, jailkit_login_status = ? WHERE jailkit_login_id = ?',
				array('0', $cfg['ITEM_TOCHANGE_STATUS'], $sshUserId)
			);

			send_request();
			set_page_message(
				tr('SSH user successfully scheduled for activation. This can take few seconds.'), 'success'
			);
		} elseif ($stmt->fields['jailkit_login_status'] == $cfg['ITEM_OK_STATUS']) {
			exec_query(
				'UPDATE jailkit_login SET ssh_login_locked = ?, jailkit_login_status = ? WHERE jailkit_login_id = ?',
				array('1', $cfg['ITEM_TOCHANGE_STATUS'], $sshUserId)
			);

			send_request();
			set_page_message(
				tr('SSH user successfully scheduled for deactivation. This can take few seconds.'), 'success'
			);
		}
	} else {
		showBadRequestErrorPage();
	}

	redirectTo('jailkit.php');
}

/**
 * Edit SSH user
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $customerId Customer unique identifier
 * @param int $sshUserId SSH user unique identifier
 * @return bool
 */
function jailkit_editSshUser($tpl, $customerId, $sshUserId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$stmt = exec_query(
		'
			SELECT
				jailkit_login_id, ssh_login_name, jailkit_status
			FROM
				jailkit_login
			INNER JOIN
				jailkit USING(admin_id)
			WHERE
				admin_id = ?
			AND
				jailkit_login_id = ?
			AND
				jailkit_login_status IN (?, ?)
			AND
				jailkit_status = ?
		',
		array($customerId, $sshUserId, $cfg['ITEM_OK_STATUS'], $cfg['ITEM_DISABLED_STATUS'], $cfg['ITEM_OK_STATUS'])
	);

	if ($stmt->rowCount()) {
		if (isset($_POST['ssh_login_pass'])) {
			$error = false;

			$loginPassword = clean_input($_POST['ssh_login_pass']);
			$loginPasswordConfirm = clean_input($_POST['ssh_login_pass_confirm']);

			if ($loginPassword !== $loginPasswordConfirm) {
				set_page_message(tr('Passwords do not match.'), 'error');
				$error = true;
			} elseif (!checkPasswordSyntax($loginPassword)) {
				$error = true;
			}

			if (!$error) {
				exec_query(
					'
						UPDATE
							jailkit_login
						SET
							ssh_login_pass = ?, jailkit_login_status = ?
						WHERE
							jailkit_login_id = ?
					',
					array($loginPassword, $cfg['ITEM_TOCHANGE_STATUS'], $sshUserId)
				);

				send_request();
				return true;
			}
		}

		$tpl->assign(
			array(
				'JAILKIT_ADD_BUTTON' => '',
				'JAILKIT_ADD_DIALOG' => '',
				'JAILKIT_LOGIN_LIST' => '',
				'JAILKIT_NO_LOGIN_ITEM' => '',
				'JAILKIT_USERNAME' => tohtml($stmt->fields['ssh_login_name']),
				'JAILKIT_LOGIN_ID' => tohtml($stmt->fields['jailkit_login_id']),
			)
		);

		return false;
	} else {
		showBadRequestErrorPage();
		exit;
	}
}

/**
 * Delete SSH user
 *
 * @param int $customerId Customer unique identifier
 * @param int $sshUserId SSH user unique identifier
 * @return bool
 */
function jailkit_deleteSshUser($customerId, $sshUserId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$stmt = exec_query(
		'
			SELECT
				jailkit_login_id
			FROM
				jailkit_login
			INNER JOIN
				jailkit USING(admin_id)
			WHERE
				admin_id = ?
			AND
				jailkit_login_id = ?
			AND
				jailkit_login_status IN (?, ?)
			AND
				jailkit_status = ?
		',
		array($customerId, $sshUserId, $cfg['ITEM_OK_STATUS'], $cfg['ITEM_DISABLED_STATUS'], $cfg['ITEM_OK_STATUS'])
	);

	if ($stmt->rowCount()) {
		exec_query(
			'UPDATE jailkit_login SET jailkit_login_status = ? WHERE jailkit_login_id = ?',
			array($cfg->ITEM_TODELETE_STATUS, $sshUserId)
		);

		send_request();
		return true;
	} else {
		showBadRequestErrorPage();
	}

	redirectTo('jailkit.php');
	exit;
}

/**
 * Get SSH user limit
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $customerId Customer unique identifier
 * @return void
 */
function jailkit_getSshUserLimit($tpl, $customerId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$stmt = exec_query('SELECT COUNT(jailkit_login_id) AS cnt FROM jailkit_login WHERE admin_id = ?', $customerId);
	$recordsCount = $stmt->fields['cnt'];

	$stmt = exec_query('SELECT max_logins, jailkit_status FROM jailkit WHERE admin_id = ?', $customerId);

	$tpl->assign(
		'TR_JAILKIT_LOGIN_AVAILABLE',
		tr(
			'SSH Users: %s of %s',
			$recordsCount,
			($stmt->fields['max_logins'] == 0) ? '<b>unlimited</b>' : $stmt->fields['max_logins']
		)
	);

	if ($stmt->fields['jailkit_status'] == $cfg->ITEM_DISABLED_STATUS) {
		$tpl->assign('JAILKIT_ADD_BUTTON', '');
		set_page_message(tr('SSH feature has been disabled by your reseller.'), 'error');
	} elseif ($stmt->fields['max_logins'] != 0 && $recordsCount >= $stmt->fields['max_logins']) {
		$tpl->assign('JAILKIT_ADD_BUTTON', '');
		set_page_message(tr('SSH user limit is reached.'), 'info');
	}
}

/***********************************************************************************************************************
 * Main
 */

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

check_login('user');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => '../../plugins/JailKit/frontend/client/jailkit.tpl',
		'page_message' => 'layout',
		'jailkit_login_list' => 'page',
		'jailkit_login_item' => 'jailkit_login_list',
		'jailkit_action_status_link' => 'jailkit_login_item',
		'jailkit_action_status_static' => 'jailkit_login_item',
		'jailkit_action_links' => 'jailkit_login_item',
		'jailkit_login_item_disabled' => 'jailkit_login_list',
		'jailkit_no_login_item' => 'page',
		'jailkit_edit_login' => 'page',
		'jailkit_add_dialog' => 'page',
		'jailkit_add_button' => 'page',
		'scroll_prev_gray' => 'jailkit_login_list',
		'scroll_prev' => 'jailkit_login_list',
		'scroll_next_gray', 'jailkit_login_list',
		'scroll_next' => 'jailkit_login_list'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Client / Webtool - SSH Users'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_DIALOG_TITLE_ADD' => tr('Add SSH User'),
		'TR_EDIT_SSH_USER' => tr('Edit SSH User'),
		'JAILKIT_NO_LOGIN' => tr('No SSH user found.'),
		'TR_JAILKIT_USERNAME' => tr('SSH username'),
		'TR_SSH_USERNAME' => tr('Username'),
		'TR_SSH_PASSWORD' => tr('Password'),
		'TR_SSH_PASSWORD_CONFIRM' => tr('Password confirmation'),
		'TR_JAILKIT_LOGIN_STATUS' => tr('Status'),
		'TR_JAILKIT_LOGIN_ACTIONS' => tr('Actions'),
		'TR_ADD_JAILKIT_LOGIN' => tr('Add new SSH User'),
		'DELETE_LOGIN_ALERT' => tr('Are you sure you want to delete this SSH user?'),
		'DISABLE_LOGIN_ALERT' => tr('Are you sure you want to disable this SSH user?'),
		'TR_EDIT_LOGINNAME' => tr('Edit SSH user'),
		'TR_DELETE_LOGINNAME' => tr('Delete SSH user'),
		'TR_PREVIOUS' => tr('Previous'),
		'TR_NEXT' => tr('Next'),
		'TR_ADD' => tr('Add'),
		'TR_CANCEL' => tr('Cancel'),
		'JAILKIT_DIALOG_OPEN' => 0,
		'JAILKIT_USERNAME' => '',
		'TR_UPDATE' => tr('Update')
	)
);

if (isset($_REQUEST['action'])) {
	$action = clean_input($_REQUEST['action']);

	if ($action === 'add') {
		if (jailkit_addSshUser($tpl, $_SESSION['user_id'])) {
			set_page_message(tr('SSH user successfully scheduled for addition'), 'success');
			redirectTo('jailkit.php');
		}
	} elseif ($action === 'edit') {
		$loginId = (isset($_GET['login_id'])) ? clean_input($_GET['login_id']) : '';

		if ($loginId != '') {
			if (jailkit_editSshUser($tpl, $_SESSION['user_id'], $loginId)) {
				set_page_message(tr('SSH user successfully scheduled for update'), 'success');
				redirectTo('jailkit.php');
			}
		}
	} elseif ($action === 'change') {
		$loginId = (isset($_GET['login_id'])) ? clean_input($_GET['login_id']) : '';

		if ($loginId != '') {
			jailkit_ChangeSshUserStatus($_SESSION['user_id'], $loginId);
		}
	} elseif ($action === 'delete') {
		$loginId = (isset($_GET['login_id'])) ? clean_input($_GET['login_id']) : '';

		if ($loginId != '') {
			if (jailkit_deleteSshUser($_SESSION['user_id'], $loginId)) {
				set_page_message(tr('SSH user successfully scheduled for deletion'), 'success');
				redirectTo('jailkit.php');
			}
		}
	} else {
		showBadRequestErrorPage();
	}
}

generateNavigation($tpl);

if (!isset($_REQUEST['action']) || isset($_REQUEST['action']) && clean_input($_REQUEST['action']) !== 'edit') {
	jailkit_generateSshUserList($tpl, $_SESSION['user_id']);
	jailkit_getSshUserLimit($tpl, $_SESSION['user_id']);
}

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
