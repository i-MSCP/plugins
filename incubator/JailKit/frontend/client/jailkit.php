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
 * Generate page
 *
 * @param $tpl iMSCP_pTemplate
 * @param iMSCP_Plugin_Manager $pluginManager
 * @param int $userId
 * @return void
 */
function jailkit_generateLoginsList($tpl, $userId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$rowsPerPage = $cfg->DOMAIN_ROWS_PER_PAGE;

	if (isset($_GET['psi']) && $_GET['psi'] == 'last') {
		unset($_GET['psi']);
	}

	$startIndex = isset($_GET['psi']) ? (int)$_GET['psi'] : 0;

	$stmt = exec_query('SELECT COUNT(admin_id) AS cnt FROM  jailkit_login WHERE admin_id = ?', $userId);
	$recordsCount = $stmt->fields['cnt'];

	$stmt = exec_query(
		'
			SELECT
				t1.*, t2.jailkit_status
			FROM
				jailkit_login AS t1
			LEFT JOIN
				jailkit AS t2 ON(t2.admin_id = t1.admin_id)
			WHERE
				t1.admin_id = ?
			ORDER BY
				t1.ssh_login_name ASC
		'
		,
		$userId
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
			if ($data['jailkit_login_status'] == $cfg->ITEM_OK_STATUS) {
				$statusIcon = 'ok';
			} elseif ($data['jailkit_login_status'] == $cfg->ITEM_DISABLED_STATUS) {
				$statusIcon = 'disabled';
			} elseif (
				(
					$data['jailkit_login_status'] == $cfg->ITEM_TOADD_STATUS ||
					$data['jailkit_login_status'] == $cfg->ITEM_TOCHANGE_STATUS ||
					$data['jailkit_login_status'] == $cfg->ITEM_TODELETE_STATUS
				) ||
				(
					$data['jailkit_login_status'] == $cfg->ITEM_TOADD_STATUS ||
					$data['jailkit_login_status'] == $cfg->ITEM_TORESTORE_STATUS ||
					$data['jailkit_login_status'] == $cfg->ITEM_TOCHANGE_STATUS ||
					$data['jailkit_login_status'] == $cfg->ITEM_TOENABLE_STATUS ||
					$data['jailkit_login_status'] == $cfg->ITEM_TODISABLE_STATUS ||
					$data['jailkit_login_status'] == $cfg->ITEM_TODELETE_STATUS
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

			if ($data['jailkit_status'] == $cfg->ITEM_DISABLED_STATUS) {
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
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $userId User unique identifier
 * @return bool
 */
function jailkit_addLoginUser($tpl, $userId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	if (isset($_POST['ssh_login_name']) && isset($_POST['ssh_login_pass'])) {
		$error = false;

		$loginUsername = 'jk_' . clean_input($_POST['ssh_login_name']);

		$loginPassword = clean_input($_POST['ssh_login_pass']);

		$stmt = exec_query('SELECT max_logins, jailkit_status FROM jailkit WHERE admin_id = ?', $userId);

		$maxLogins = $stmt->fields['max_logins'];
		$accountDisabled = $stmt->fields['jailkit_status'];

		$stmt = exec_query('SELECT COUNT(jailkit_login_id) AS cnt FROM  jailkit_login WHERE admin_id = ?', $userId);
		$activatedLogins = $stmt->fields['cnt'];

		if ($accountDisabled == $cfg->ITEM_DISABLED_STATUS) {
			$error = true;
		} elseif ($maxLogins != '0' && $activatedLogins >= $maxLogins) {
			set_page_message(tr("Max. allowed JailKit - SSH login reached."), 'error');
			$error = true;
		} elseif (strlen(clean_input($_POST['ssh_login_name'])) === 0) {
			set_page_message(tr("The username is empty"), 'error');
			$error = true;
		} elseif (strlen(clean_input($_POST['ssh_login_name'])) > 16) {
			set_page_message(tr("The username is too long (max. 16 chars)"), 'error');
			$error = true;
		} elseif (!preg_match("/^[a-z_][a-z0-9_-]*[$]?/", clean_input($_POST['ssh_login_name']))) {
			set_page_message(tr("The username does only accept alphanumeric chars"), 'error');
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
					'JAILKIT_USERNAME' => tohtml(clean_input($_POST['ssh_login_name'])),
					'JAILKIT_PASSWORD' => tohtml($loginPassword)
				)
			);
			return false;
		}
	}

	redirectTo('jailkit.php');
	exit;
}

/**
 *
 * @param int $userId User unique identifier
 * @param $loginId
 */
function jailkit_ChangeLoginUserPermission($userId, $loginId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$stmt = exec_query(
		'
			SELECT
				t1.*, t2.jailkit_status
			FROM
				jailkit_login AS t1
			LEFT JOIN
				jailkit AS t2 ON(t2.admin_id = t1.admin_id)
			WHERE
				t1.admin_id = ?
			AND
				t1.jailkit_login_id = ?
			AND
				t1.jailkit_login_status IN (?, ?)
		',
		array($userId, $loginId, $cfg->ITEM_OK_STATUS, $cfg->ITEM_DISABLED_STATUS));

	if ($stmt->rowCount()) {
		if ($stmt->fields['jailkit_status'] != $cfg->ITEM_DISABLED_STATUS) {
			if ($stmt->fields['jailkit_login_status'] == $cfg->ITEM_DISABLED_STATUS) {
				exec_query(
					'UPDATE jailkit_login SET ssh_login_locked = ?, jailkit_login_status = ? WHERE jailkit_login_id = ?',
					array('0', $cfg->ITEM_TOCHANGE_STATUS, $loginId)
				);

				send_request();
				set_page_message(tr('JailKit - SSH login enabled. This can take few seconds.'), 'success');
			} elseif ($stmt->rowCount() && $stmt->fields['jailkit_login_status'] == $cfg->ITEM_OK_STATUS) {
				exec_query(
					'
						UPDATE
							jailkit_login
						SET
							ssh_login_locked = ?, jailkit_login_status = ?
						WHERE
							jailkit_login_id = ?
					',
					array('1', $cfg->ITEM_TOCHANGE_STATUS, $loginId)
				);

				send_request();
				set_page_message(tr('JailKit - SSH login disabled. This can take few seconds.'), 'success');
			}
		}
	} else {
		set_page_message(
			tr("The JailKit - SSH login you are trying to change doesn't exist or has an invalid state."), 'error'
		);
	}

	redirectTo('jailkit.php');
}

/**
 * Edit login user
 *
 * @param iMSCP_pTemplate $tpl
 * @param int $userId User unique identifier
 * @param $loginId
 * @return bool
 */
function jailkit_editLoginUser($tpl, $userId, $loginId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$stmt = exec_query(
		'
			SELECT
				t1.*, t2.jailkit_status
			FROM
				jailkit_login AS t1
			LEFT JOIN
				jailkit AS t2 ON(t2.admin_id = t1.admin_id)
			WHERE
				t1.admin_id = ?
			AND
				t1.jailkit_login_id = ?
			AND
				t1.jailkit_login_status in (?, ?)
		',
		array($userId, $loginId, $cfg->ITEM_OK_STATUS, $cfg->ITEM_DISABLED_STATUS)
	);

	if ($stmt->rowCount()) {
		if ($stmt->fields['jailkit_status'] != $cfg->ITEM_DISABLED_STATUS) {
			if (isset($_POST['ssh_login_pass'])) {
				$error = false;
				$loginPassword = clean_input($_POST['ssh_login_pass']);

				if (!checkPasswordSyntax($loginPassword)) {
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
						array($loginPassword, $cfg->ITEM_TOCHANGE_STATUS, $loginId)
					);

					send_request();
					return true;
				}

				$tpl->assign('JAILKIT_PASSWORD', $loginPassword);
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
		} else {
			redirectTo('jailkit.php');
		}
	} else {
		set_page_message(tr("The JailKit - SSH login you are trying to edit doesn't exist or has an invalid state."), 'error');
		redirectTo('jailkit.php');
	}
}

/**
 *
 * @param $userId
 * @param $loginId
 * @return bool
 */
function jailkit_deleteLoginUser($userId, $loginId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "
		SELECT
			t1.*, t2.jailkit_status
		FROM
			jailkit_login AS t1
		LEFT JOIN
			jailkit AS t2 ON(t2.admin_id = t1.admin_id)
		WHERE
			t1.admin_id = ?
		AND
			t1.jailkit_login_id = ?
		AND
			t1.jailkit_login_status in (?, ?)
	";
	$stmt = exec_query($query, array($userId, $loginId, $cfg->ITEM_OK_STATUS, $cfg->ITEM_DISABLED_STATUS));

	if ($stmt->rowCount()) {
		if ($stmt->fields['jailkit_status'] != $cfg->ITEM_DISABLED_STATUS) {
			exec_query(
				'
					UPDATE
						jailkit_login
					SET
						jailkit_login_status = ?
					WHERE
						jailkit_login_id = ?
				',
				array($cfg->ITEM_TODELETE_STATUS, $loginId)
			);

			send_request();
			return true;
		}
	} else {
		set_page_message(
			tr("The SSH account which you are trying to delete doesn't exist or is in an inconsistent state."), 'error'
		);
	}

	redirectTo('jailkit.php');
	exit;
}

/**
 * @param iMSCP_pTemplate $tpl
 * @param $userId
 */
function get_jailkitLoginLimit($tpl, $userId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$stmt = exec_query('SELECT COUNT(jailkit_login_id) AS cnt FROM jailkit_login WHERE admin_id = ?', $userId);
	$recordsCount = $stmt->fields['cnt'];

	$stmt = exec_query('SELECT max_logins, jailkit_status FROM jailkit WHERE admin_id = ?', $userId);

	$tpl->assign(
		'TR_JAILKIT_LOGIN_AVAILABLE',
		tr(
			'Active logins: %s of %s',
			$recordsCount,
			(($stmt->fields['max_logins'] == 0) ? '<b>unlimited</b>' : $stmt->fields['max_logins'])
		)
	);

	if ($stmt->fields['jailkit_status'] == $cfg->ITEM_DISABLED_STATUS) {
		$tpl->assign('JAILKIT_ADD_BUTTON', '');
		set_page_message(tr('Your SSH account has been disabled by your reseller.'), 'error');
	} elseif ($stmt->fields['max_logins'] != 0 && $recordsCount >= $stmt->fields['max_logins']) {
		$tpl->assign('JAILKIT_ADD_BUTTON', '');
		set_page_message(tr('SSH account limit has been reached.'), 'info');
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
		'jailkit_login_item' => 'page',
		'jailkit_login_item_disabled' => 'page',
		'jailkit_no_login_item' => 'page',
		'jailkit_edit_login' => 'page',
		'jailkit_add_dialog' => 'page',
		'jailkit_add_button' => 'page',
		'scroll_prev_gray' => 'jailkit_login_list',
		'scroll_prev' => 'jailkit_login_list',
		'scroll_next_gray', 'jailkit_login_list',
		'scroll_next' => 'jailkit_login_list',
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Domains / JailKit - SSH'),
		'TR_PAGE_TITLE_JAILKIT_ADD' => tr('JailKit - SSH / New login user'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'JAILKIT_NO_LOGIN' => tr('No login for JailKit - SSH activated'),
		'TR_JAILKIT_USERNAME' => tr('SSH username'),
		'TR_SSH_USERNAME' => tr('Username'),
		'TR_SSH_PASSWORD' => tr('Password'),
		'TR_JAILKIT_LOGIN_STATUS' => tr('Status'),
		'TR_JAILKIT_LOGIN_ACTIONS' => tr('Actions'),
		'TR_ADD_JAILKIT_LOGIN' => tr('Add new JailKit ssh login'),
		'DELETE_LOGIN_ALERT' => tr('Are you sure? You want to delete this JailKit ssh login?'),
		'DISABLE_LOGIN_ALERT' => tr('Are you sure? You want to disable this JailKit ssh login?'),
		'TR_EDIT_LOGINNAME' => tr('Edit ssh login'),
		'TR_DELETE_LOGINNAME' => tr('Delete ssh login'),
		'TR_PREVIOUS' => tr('Previous'),
		'TR_NEXT' => tr('Next'),
		'TR_ADD' => tr('Add'),
		'TR_CANCEL' => tr('Cancel'),
		'JAILKIT_DIALOG_OPEN' => 0,
		'JAILKIT_USERNAME' => '',
		'JAILKIT_PASSWORD' => '',
		'TR_UPDATE' => tr('Update')
	)
);

if (isset($_REQUEST['action'])) {
	$action = clean_input($_REQUEST['action']);

	if ($action === 'add') {
		if (jailkit_addLoginUser($tpl, $_SESSION['user_id'])) {
			set_page_message(tr('New JailKit - SSH login successfully scheduled for addition'), 'success');
			redirectTo('jailkit.php');
		}
	} elseif ($action === 'edit') {
		$loginId = (isset($_GET['login_id'])) ? clean_input($_GET['login_id']) : '';

		if ($loginId != '') {
			if (jailkit_editLoginUser($tpl, $_SESSION['user_id'], $loginId)) {
				set_page_message(tr('JailKit - SSH login successfully scheduled for update'), 'success');
				redirectTo('jailkit.php');
			}
		}
	} elseif ($action === 'change') {
		$loginId = (isset($_GET['login_id'])) ? clean_input($_GET['login_id']) : '';

		if ($loginId != '') {
			jailkit_ChangeLoginUserPermission($_SESSION['user_id'], $loginId);
		}
	} elseif ($action === 'delete') {
		$loginId = (isset($_GET['login_id'])) ? clean_input($_GET['login_id']) : '';

		if ($loginId != '') {
			if (jailkit_deleteLoginUser($_SESSION['user_id'], $loginId)) {
				set_page_message(tr('JailKit - SSH login successfully scheduled for deletion'), 'success');
				redirectTo('jailkit.php');
			}
		}
	} else {
		showBadRequestErrorPage();
	}
}


generateNavigation($tpl);

if (!isset($_REQUEST['action']) || isset($_REQUEST['action']) && clean_input($_REQUEST['action']) !== 'edit') {
	jailkit_generateLoginsList($tpl, $_SESSION['user_id']);
	get_jailkitLoginLimit($tpl, $_SESSION['user_id']);
}
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
