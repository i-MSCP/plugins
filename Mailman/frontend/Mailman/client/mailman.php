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
 * Add or update mailing-list
 *
 * @return boolean TRUE on success, FALSE otherwise
 */
function mailman_manageList()
{
	if (
		isset($_POST['id']) && isset($_POST['list']) && isset($_POST['admin_email']) &&
		isset($_POST['admin_password']) && isset($_POST['admin_password_confirm'])
	) {
		$error = false;
		$listId = clean_input($_POST['id']);
		$list = clean_input($_POST['list']);
		$adminEmail = clean_input($_POST['admin_email']);
		$adminPassword = clean_input($_POST['admin_password']);
		$adminPasswordConfirm = clean_input($_POST['admin_password_confirm']);


		if($list == '') {
			set_page_message(tr("List field cannot be empty"), 'error');
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
			$error = 'true';
		}

		if (!$error) {
			/** @var $dbConfig iMSCP_Config_Handler_Db */
			$dbConfig = iMSCP_Registry::get('dbConfig');

			if (isset($dbConfig->PLUGIN_MAILMAN)) {
				$config = json_decode($dbConfig->PLUGIN_MAILMAN, true);
			} else {
				$config = array();
			}

			/** @var iMSCP_Config_Handler_File $cfg */
			$cfg = iMSCP_Registry::get('config');

			if ($listId == '-1') { // Add
				$config[] = array(
					'list' => $list,
					'admin_email' => $adminEmail,
					'admin_password' => $adminPassword,
					'status' => $cfg->ITEM_ADD_STATUS,
				);
			} else { // Update
				$config[$listId] = array(
					'list' => $list,
					'admin_email' => $adminEmail,
					'admin_password' => $adminPassword,
					'status' => $cfg->ITEM_CHANGE_STATUS
				);
			}

			$dbConfig->PLUGIN_MAILMAN = json_encode($config);
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
 * Delete mailing-list
 *
 * @return void
 */
function mailman_deleteList()
{
	if (isset($_REQUEST['id'])) {
		$listId = clean_input($_REQUEST['id']);

		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		if (isset($dbConfig->PLUGIN_MAILMAN)) {
			$config = json_decode($dbConfig->PLUGIN_MAILMAN, true);
		} else {
			$config = array();
		}

		if (isset($config[$listId])) {
			/** @var iMSCP_Config_Handler_File $cfg */
			$cfg = iMSCP_Registry::get('config');
			$config[$listId]['status'] = $cfg->ITEM_DELETE_STATUS;
			$dbConfig->PLUGIN_MAILMAN = json_encode($config);
			send_request();
		} else {
			showBadRequestErrorPage();
		}
	} else {
		showBadRequestErrorPage();
	}
}

/**
 * Generate page.
 *
 * @param $tpl iMSCP_pTemplate
 */
function mailman_generatePage($tpl)
{
	/** @var iMSCP_Config_Handler_File $cfg */
	$cfg = iMSCP_Registry::get('config');

	/** @var $dbConfig iMSCP_Config_Handler_Db */
	$dbConfig = iMSCP_Registry::get('dbConfig');

	if (isset($dbConfig->PLUGIN_MAILMAN) && ($config = json_decode($dbConfig->PLUGIN_MAILMAN, true))) {
		foreach ($config as $listId => $listData) {
			$tpl->assign(
				array(
					'LIST_URL' => isset($listData['list_url']) ? tohtml($listData['list_url']) : '#',
					'LIST' => tohtml($listData['list']),
					'ADMIN_EMAIL' => tohtml($listData['admin_email']),
					'ADMIN_PASSWORD' => '',
					'STATUS' => tohtml(translate_dmn_status($listData['status']))
				)
			);

			if ($listData['status'] == $cfg->ITEM_OK_STATUS) {
				$tpl->assign(
					array(
						'EDIT_LINK' => "mailman.php.php?action=edit&id=$listId",
						'EDIT_ICON' => 'i_edit',
						'TR_EDIT' => tr('Edit'),
						'DELETE_LINK' => "mailman.php.php?action=edit&action=delete&id=$listId",
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
		set_page_message(tr('You do not have created any E-mail list yet'), 'info');
	}

	if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'edit') {
		$listId = clean_input($_REQUEST['id']);

		if (isset($config[$listId])) {
			$tpl->assign(
				array(
					'LIST' => tohtml($config[$listId]['list']),
					'ADMIN_EMAIL' => tohtml($config[$listId]['admin_email']),
					'ADMIN_PASSWORD' => '',
					'ADMIN_PASSWORD_CONFIRM' => '',
					'ID' => tohtml($listId),
					'TR_ACTION' => tr('Update')
				)
			);
		} else {
			showBadRequestErrorPage();
		}
	} else {
		$tpl->assign(
			array(
				'LIST' => isset($_REQUEST['list']) ? tohtml($_REQUEST['list']) : '',
				'ADMIN_EMAIL' => isset($_REQUEST['admin_email']) ? tohtml($_REQUEST['admin_email']) : '',
				'ADMIN_PASSWORD' => '',
				'ADMIN_PASSWORD_CONFIRM' => '',
				'ID' => '-1',
				'TR_ACTION' => tr('Add'),
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
		if(mailman_manageList()) {
			set_page_message(tr('E-mail list successfully scheduled for addition'), 'success');
		}
	} elseif ($action === 'delete') {
		mailman_deleteList();
		set_page_message(tr('E-mail list successfully scheduled for deletion'), 'success');
	} elseif($action !== 'edit') {
		showBadRequestErrorPage();
	}
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => '../../plugins/Mailman/client/mailman.tpl',
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
		'TR_MAIL_LISTS' => tr('E-Mail List'),
		'TR_EDIT' => tr('Edit'),
		'TR_DELETE' => tr('Delete'),
		'TR_ADD_LIST' => tr('Add list'),
		'TR_MAIL_LIST' => tr('E-Mail List'),
		'TR_LIST' => tr('List'),
		'TR_ADMIN_EMAIL' => tr('Admin email'),
		'TR_ADMIN_PASSWORD' => tr('Password'),
		'TR_ADMIN_PASSWORD_CONFIRM' => tr('Password confirmation'),
		'TR_URL' => tr('Url')
	)
);

generateNavigation($tpl);
mailman_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
