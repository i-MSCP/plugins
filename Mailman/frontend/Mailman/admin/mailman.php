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
 * Add mailing-list
 *
 * @return void
 */
function mailman_addList()
{
	if(
		isset($_POST['list_id']) && isset($_POST['list']) && isset($_POST['admin_password']) &&
		isset($_POST['admin_name']) && isset($_POST['list_url'])
	) {
		$listId = clean_input($_POST['list_id']);
		$list = clean_input($_POST['list']);
		$adminName = clean_input($_POST['admin_name']);
		$adminPassword = clean_input($_POST['admin_password']);
		$listUrl = clean_input($_POST['list_url']);

		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		if(isset($dbConfig->PLUGIN_MAILMAN)) {
			$config = json_decode($dbConfig->PLUGIN_MAILMAN);
		} else {
			$config = array();
		}

		$config[$listId] = array(
			'list' => $list,
			'admin_name' => $adminName,
			'admin_password' => $adminPassword,
			'list_url' => $listUrl
		);

		$dbConfig->PLUGIN_MAILMAN = json_encode($config);
		send_request();
	} else {
		showBadRequestErrorPage();
	}
}

/**
 * Edit mailing-list
 *
 * @return void
 */
function mailman_editList()
{
	if(isset($_REQUEST['list_id'])) {
		$listId = clean_input($_REQUEST['list_id']);

		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		if(isset($dbConfig->PLUGIN_MAILMAN)) {
			$config = json_decode($dbConfig->PLUGIN_MAILMAN);
		} else {
			$config = array();
		}

		if(isset($config[$listId])) {
			echo json_encode($config[$listId]);
			exit;
		} else {
			showBadRequestErrorPage();
		}
	} else {
		mailman_addList();
	}
}

/**
 * Delete mailing-list
 *
 * @return void
 */
function mailman_deleteList()
{
	if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete' && isset($_REQUEST['list_id'])) {
		$listId = clean_input($_REQUEST['list_id']);

		/** @var $dbConfig iMSCP_Config_Handler_Db */
		$dbConfig = iMSCP_Registry::get('dbConfig');

		if(isset($dbConfig->PLUGIN_MAILMAN)) {
			$config = json_decode($dbConfig->PLUGIN_MAILMAN);
		} else {
			$config = array();
		}

		if(isset($config[$listId])) {
			unset($config[$listId]);
			$dbConfig->PLUGIN_MAILMAN = json_encode($config);
			send_request();
			exit;
		} else {
			showBadRequestErrorPage();
		}
	} else {
		mailman_addList();
	}
}

/**
 * Generate page.
 *
 * @param $tpl iMSCP_pTemplate
 */
function mailman_generatePage($tpl)
{
	/** @var $dbConfig iMSCP_Config_Handler_Db */
	$dbConfig = iMSCP_Registry::get('dbConfig');

	if(isset($dbConfig->PLUGIN_MAILMAN) && ($config = json_decode($dbConfig->PLUGIN_MAILMAN))) {
		foreach($config as $listId => $listData) {
			$tpl->assign(
				array(
					'LIST_ID' => tohtml($listId),
					'LIST' => tohtml($listData['list']),
					'ADMIN_NAME' => tohtml($listData['admin_name']),
					'ADMIN_PASSWORD' => tohtml($listData['admin_password']),
					'LIST_URL' => tohtml($listData['list_url'])
				)
			);

			$tpl->parse('MAILING_LIST', '.mailing_list');
		}
	} else {
		$tpl->assign('MAILING_LISTS', '');
		set_page_message(tr('You do not have created any mailing-list yet'), 'info');
	}
}

/***********************************************************************************************************************
 * Main
 */
check_login('admin');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

if(isset($_REQUEST['action'])) {
	$action = clean_input($_REQUEST['action']);

	if($action == 'add') {
		mailman_addList();
		set_page_message(tr('Mailing list successfully scheduled for addition'), 'success');
		redirectTo('setting.php?plugin=mailman');
	} elseif(is_xhr() && $action == 'edit') {
		mailman_editList();
	} elseif($action == 'delete') {
		mailman_deleteList();
		set_page_message(tr('Mailing list successfully scheduled for deletion'), 'success');
		redirectTo('setting.php?plugin=mailman');
	} else {
		showBadRequestErrorPage();
	}
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' =>  '../../plugins/Mailman/admin/mailman.tpl',
		'page_message' => 'layout',
		'mailing_lists' => 'page',
		'mailing_list' => 'mailing_lists'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / Settings / Mailman'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_MAIL_LISTS' => tr('Mailing Lists'),
		'TR_EDIT' => tr('Edit'),
		'TR_DELETE' => tr('Delete'),
		'TR_ADD_LIST' => tr('Add list'),
		'TR_MAIL_LIST' => tr('Mailing List'),
		'TR_LIST' => tr('List'),
		'TR_ADMIN_NAME' => tr('Admin name'),
		'TR_ADMIN_PASSWORD' => tr('Password'),
		'TR_URL' => tr('Url')
	)
);

generateNavigation($tpl);
mailman_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
