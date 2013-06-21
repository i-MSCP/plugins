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
 * @subpackage  HelloWorld
 * @copyright   2010-2013 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Add or update list
 *
 * @return boolean TRUE on success, FALSE otherwise
 */
function mailman_manageList()
{
	if (
		isset($_POST['list_id']) && isset($_POST['list_name']) && isset($_POST['admin_email']) &&
		isset($_POST['admin_password']) && isset($_POST['admin_password_confirm'])
	) {
		$error = false;
		$listId = clean_input($_POST['list_id']);
		$listName = clean_input($_POST['list_name']);
		$adminEmail = clean_input($_POST['admin_email']);
		$adminPassword = clean_input($_POST['admin_password']);
		$adminPasswordConfirm = clean_input($_POST['admin_password_confirm']);

		if (!preg_match('/[-_a-z0-9+]/i', $listName)) {
			set_page_message(tr("Wrong list name syntax"), 'error');
			$error = true;
		}

		if (!chk_email($adminEmail)) {
			set_page_message(tr("Wrong email syntax"), 'error');
			$error = true;
		}

		if ($adminPassword != $adminPasswordConfirm) {
			set_page_message(tr("Password doesn't matches"), 'error');
			$error = true;
		} elseif (!checkPasswordSyntax($adminPassword)) {
			$error = true;
		}

		if (!$error) {
			/** @var iMSCP_Config_Handler_File $cfg */
			$cfg = iMSCP_Registry::get('config');

			if($listId === '-1') { // New list

				$db = iMSCP_Database::getInstance();

				try {
					$db->beginTransaction();

					$mainDmnProps = get_domain_default_props($_SESSION['user_id']);
					$listDmnName = 'lists.' . $mainDmnProps['domain_name'] . '.';

					$query = '
						SELECT
							`domain_id`
						FROM
							`domain_dns`
						WHERE
							`domain_id` = ?
						AND
							`domain_dns` = ?
						AND
							`domain_class` = ?
						AND
							`domain_type` = ?
						AND
							`domain_text` = ?
					';
					$stmt = exec_query(
						$query, array($mainDmnProps['domain_id'], $listDmnName, 'IN', 'A', $cfg->BASE_SERVER_IP)
					);

					if(!$stmt->rowCount()) {
						$query = '
							INSERT INTO `domain_dns` (
								`domain_id`, `alias_id`, `domain_dns`, `domain_class`, `domain_type`, `domain_text`,
								`protected`
							) VALUES(
								?, ?, ?, ?, ?, ?, ?
							)
						';
						exec_query(
							$query,
							array($mainDmnProps['domain_id'], 0, $listDmnName, 'IN', 'A', $cfg->BASE_SERVER_IP, 'yes')
						);

						exec_query(
							'UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?',
							array($cfg->ITEM_DNSCHANGE_STATUS, $mainDmnProps['domain_id'])
						);
					}

					$query = '
						INSERT INTO `mailman` (
							`mailman_admin_id`, `mailman_admin_email`, `mailman_admin_password`, `mailman_list_name`,
							`mailman_status`
						) VALUES(
							?, ?, ?, ?, ?
						)
					';
					exec_query(
						$query,
						array(
							$mainDmnProps['domain_admin_id'], $adminEmail, $adminPassword, $listName,
							$cfg->ITEM_ADD_STATUS
						)
					);

					$db->commit();
				} catch(iMSCP_Exception_Database $e) {
					$db->rollBack();

					if($e->getCode() == 23000) { // Duplicate entries
						set_page_message(
							tr("The $listName e-mail list already exists. Please, choose other name."), 'error'
						);
						return false;
					}
				}
			} else { // List update
				$query = '
					UPDATE
						`mailman`
					SET
						`mailman_admin_email` = ?, `mailman_admin_password` = ?, `mailman_status` = ?
					WHERE
						`mailman_id` = ?
					AND
						`mailman_admin_id` = ?
					AND
						`mailman_status` = ?
				';
				$stmt = exec_query(
					$query,
					array(
						$adminEmail, $adminPassword, $cfg->ITEM_CHANGE_STATUS, $listId, $_SESSION['user_id'],
						$cfg->ITEM_OK_STATUS
					)
				);

				if(!$stmt->rowCount()) {
					showBadRequestErrorPage();
				}
			}

			// Send request to i-MSCP daemon
			send_request();

			return true;
		} else {
			return false;
		}
	} else {
		showBadRequestErrorPage();
		exit;
	}
}

/**
 * Delete list
 *
 * @throws iMSCP_Exception_Database
 * @return void
 */
function mailman_deleteList()
{
	if (isset($_REQUEST['list_id'])) {
		$listId = clean_input($_REQUEST['list_id']);

		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');

		$db = iMSCP_Database::getInstance();

		try {
			$db->beginTransaction();

			$mainDmnProps = get_domain_default_props($_SESSION['user_id']);
			$listDmnName = 'lists.' . $mainDmnProps['domain_name'] . '.';

			$query = 'UPDATE`mailman` SET `mailman_status` = ? WHERE `mailman_id` = ? AND `mailman_admin_id` = ?';
			$stmt = exec_query($query, array($cfg->ITEM_DELETE_STATUS, $listId, $mainDmnProps['domain_admin_id']));

			if(!$stmt->rowCount()) {
				showBadRequestErrorPage();
			}

			$stmt = exec_query(
				"SELECT COUNT('mailman_id') AS `cnt` FROM `mailman` WHERE mailman_admin_id = ?",
				$mainDmnProps['domain_id']
			);

			if($stmt->fields['cnt'] < 2) {
				$query = '
					DELETE FROM
						`domain_dns`
					WHERE
						`domain_id` = ?
					AND
						`alias_id` = ?
					AND
						`domain_dns` = ?
					AND
						`domain_class` = ?
					AND
						`domain_type` = ?
					AND
						`domain_text` = ?
				';
				exec_query(
					$query, array($mainDmnProps['domain_id'], '0', $listDmnName, 'IN', 'A', $cfg->BASE_SERVER_IP)
				);

				exec_query(
					'UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?',
					array($cfg->ITEM_DNSCHANGE_STATUS, $mainDmnProps['domain_id'])
				);
			}

			$db->commit();
		} catch(iMSCP_Exception_Database $e) {
			$db->rollBack();
			throw new iMSCP_Exception_Database($e->getMessage(), $e->getQuery(), $e->getCode(), $e);
		}

		// Send request to i-MSCP daemon
		send_request();
	} else {
		showBadRequestErrorPage();
	}
}

/**
 * Generate page.
 *
 * @param $tpl iMSCP_pTemplate
 * @return void
 */
function mailman_generatePage($tpl)
{
	/** @var iMSCP_Config_Handler_File $cfg */
	$cfg = iMSCP_Registry::get('config');

	$query = '
		SELECT
			`t1`.*, `t2`.`domain_name`
		FROM
			`mailman` AS `t1`
		INNER JOIN
			`domain` AS `t2` ON (`t2`.`domain_admin_id` = `t1`.`mailman_admin_id`)
		WHERE
			`t1`.`mailman_admin_id` = ?
		ORDER BY
			`t1`.`mailman_list_name`
	';
	$stmt = exec_query($query, $_SESSION['user_id']);
	$lists = $stmt->fetchAll(PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC|PDO::FETCH_GROUP);

	if($stmt->rowCount()) {
		foreach($lists as $listId => $listData) {
			$tpl->assign(
				array(
					'LIST_URL' => "http://lists.{$listData['domain_name']}/admin/{$listData['mailman_list_name']}",
					'LIST_NAME' => tohtml($listData['mailman_list_name']),
					'ADMIN_EMAIL' => tohtml($listData['mailman_admin_email']),
					'ADMIN_PASSWORD' => '',
					'STATUS' => tohtml(translate_dmn_status($listData['mailman_status']))
				)
			);

			if ($listData['mailman_status'] == $cfg->ITEM_OK_STATUS) {
				$tpl->assign(
					array(
						'EDIT_LINK' => "mailman.php?action=edit&list_id=$listId",
						'EDIT_ICON' => 'i_edit',
						'TR_EDIT' => tr('Edit'),
						'DELETE_LINK' => "mailman.php?action=edit&action=delete&list_id=$listId",
						'DELETE_ICON' => 'i_delete',
						'TR_DELETE' => tr('Delete')
					)
				);
			} else {
				$tpl->assign(
					array(
						'EDIT_LINK' => "#",
						'EDIT_ICON' => 'i_delete',
						'TR_EDIT' => tr('N/A'),
						'DELETE_LINK' => "#",
						'DELETE_ICON' => 'i_delete',
						'TR_DELETE' => tr('N/A')
					)
				);
			}

			$tpl->parse('EMAIL_LIST', '.email_list');
		}
	} else {
		$tpl->assign('EMAIL_LISTS', '');
		set_page_message(tr('You have not created any e-mail lists.'), 'info');
	}

	if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'edit') {
		$listId = clean_input($_REQUEST['list_id']);

		if (isset($lists[$listId])) {
			$listData = $lists[$listId];

			$tpl->assign(
				array(
					'LIST_DIALOG_OPEN' => 1,
					'LIST_NAME' => tohtml($listData['mailman_list_name']),
					'LIST_NAME_READONLY' => $cfg->HTML_READONLY,
					'ADMIN_EMAIL' => tohtml($listData['mailman_admin_email']),
					'ADMIN_PASSWORD' => '',
					'ADMIN_PASSWORD_CONFIRM' => '',
					'LIST_ID' => tohtml($listId),
					'ACTION' => 'edit'
				)
			);
		} else {
			showBadRequestErrorPage();
		}
	} else {
		$tpl->assign(
			array(
				'LIST_DIALOG_OPEN' => isset($_REQUEST['list_name']) ? 1 : 0,
				'LIST_NAME' => isset($_REQUEST['list_name']) ? tohtml($_REQUEST['list_name']) : '',
				'LIST_NAME_READONLY' => '',
				'ADMIN_EMAIL' => isset($_REQUEST['admin_email']) ? tohtml($_REQUEST['admin_email']) : '',
				'ADMIN_PASSWORD' => '',
				'ADMIN_PASSWORD_CONFIRM' => '',
				'LIST_ID' => '-1',
				'ACTION' => 'add'
			)
		);
	}
}

/***********************************************************************************************************************
 * Main
 */

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if (isset($_REQUEST['action'])) {
	$action = clean_input($_REQUEST['action']);

	if ($action === 'add') {
		if (mailman_manageList()) {
			set_page_message(tr('E-Mail list successfully scheduled for addition'), 'success');
			redirectTo('mailman.php');
		}
	} elseif($action === 'edit') {
		if (!empty($_POST) && mailman_manageList()) {
			set_page_message(tr('E-Mail list successfully scheduled for update'), 'success');
			redirectTo('mailman.php');
		}
	} elseif ($action === 'delete') {
		mailman_deleteList();
		set_page_message(tr('E-Mail list successfully scheduled for deletion'), 'success');
		redirectTo('mailman.php');
	} else {
		showBadRequestErrorPage();
	}
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => '../../plugins/Mailman/frontend/mailman.tpl',
		'page_message' => 'layout',
		'email_lists' => 'page',
		'email_list' => 'email_lists'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / Settings / Mailman'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_MAIL_LISTS' => tr('E-Mail Lists'),
		'TR_EDIT' => tr('Edit'),
		'TR_DELETE' => tr('Delete'),
		'TR_ADD_LIST' => tr('Add list'),
		'TR_MAIL_LIST' => tr('E-Mail List'),
		'TR_LIST_NAME' => tr('List name'),
		'TR_ADMIN_EMAIL' => tr('Admin email'),
		'TR_ADMIN_PASSWORD' => tr('Password'),
		'TR_ADMIN_PASSWORD_CONFIRM' => tr('Password confirmation'),
		'TR_URL' => tr('Url'),
		'TR_CONFIRM_DELETION' => tr('Please, confirm deletion of the %s e-mail list.', false, '%s'),
		'TR_APPLY' => tr('Apply'),
		'TR_CANCEL' => tr('Cancel')
	)
);

generateNavigation($tpl);
mailman_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
