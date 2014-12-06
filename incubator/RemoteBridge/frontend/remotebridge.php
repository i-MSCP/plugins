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
 * @subpackage  RemoteBridge
 * @copyright   2010-2014 by i-MSCP Team
 * @author      Sascha Bay <info@space2place.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Add or update Remote Bridge
 *
 * @return boolean TRUE on success, FALSE otherwise
 */
function bridge_manageKEY()
{
	if (isset($_POST['bridge_id']) && isset($_POST['bridge_ipaddress']) && isset($_POST['bridge_key'])) {
		$error = false;
		$bridgeID = clean_input($_POST['bridge_id']);
		$serverIpaddress = clean_input($_POST['bridge_ipaddress']);
		$bridgeKey = clean_input($_POST['bridge_key']);

		if (filter_var($serverIpaddress, FILTER_VALIDATE_IP) === false) {
			set_page_message(tr("Wrong IP address"), 'error');
			$error = true;
		}

		if (strlen($bridgeKey) !== 30) {
			set_page_message(tr("Wrong Remote Bridge key"), 'error');
			$error = true;
		}

		if (!$error) {
			/** @var iMSCP_Config_Handler_File $cfg */
			$cfg = iMSCP_Registry::get('config');

			if ($bridgeID === '-1') { // New Remote Bridge
				$query = '
					INSERT INTO remote_bridge (
						`bridge_admin_id`, `bridge_ipaddress`, `bridge_key`, `bridge_status`
					) VALUES(
						?, ?, ?, ?
					)
				';

				try {
					exec_query(
						$query, array($_SESSION['user_id'], $serverIpaddress, $bridgeKey, $cfg->ITEM_TOADD_STATUS)
					);
				} catch (iMSCP_Exception_Database $e) {
					if ($e->getCode() == 23000) { // Duplicate entries
						set_page_message(tr("The Remote Bridge key $bridgeKey already exist."), 'error');
						return false;
					}
				}
			} else { // E-mail list update
				$query = '
					UPDATE
						`remote_bridge`
					SET
						`bridge_ipaddress` = ?, `bridge_key` = ?, `bridge_status` = ?
					WHERE
						`bridge_id` = ?
					AND
						`bridge_admin_id` = ?
				';
				$stmt = exec_query(
					$query,
					array($serverIpaddress, $bridgeKey, $cfg->ITEM_TOCHANGE_STATUS, $bridgeID, $_SESSION['user_id'])
				);

				if (!$stmt->rowCount()) {
					showBadRequestErrorPage();
				}
			}

			runBridgeRequest();

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
 * Delete Remote Bridge
 *
 * @return void
 */
function bridge_deleteKEY()
{
	if (isset($_REQUEST['bridge_id'])) {
		$bridgeID = clean_input($_REQUEST['bridge_id']);

		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');

		$query = 'UPDATE  `remote_bridge`  SET  `bridge_status` = ?  WHERE  `bridge_id` = ?  AND  `bridge_admin_id` = ?';
		$stmt = exec_query($query, array($cfg->ITEM_TODELETE_STATUS, $bridgeID, $_SESSION['user_id']));

		if (!$stmt->rowCount()) {
			showBadRequestErrorPage();
		}

		runBridgeRequest();
	} else {
		showBadRequestErrorPage();
	}
}

/**
 *
 */
function runBridgeRequest()
{
	$query = "
		SELECT
			*
		FROM
			`remote_bridge`
		WHERE
			`bridge_admin_id` = ?
		AND
			`bridge_status`  IN ('toadd', 'tochange', 'todelete')
	";
	$stmt = exec_query($query, $_SESSION['user_id']);
	$bridgelists = $stmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC | PDO::FETCH_GROUP);

	if ($stmt->rowCount()) {
		foreach ($bridgelists as $bridgeID => $bridgeData) {
			if ($bridgeData['bridge_status'] == 'toadd') {
				addRemoteBridge($bridgeData['bridge_key'], $bridgeData['bridge_ipaddress']);
			} elseif ($bridgeData['bridge_status'] == 'tochange') {
				updateRemoteBridge($bridgeData['bridge_key'], $bridgeData['bridge_ipaddress']);
			} elseif ($bridgeData['bridge_status'] == 'todelete') {
				deleteRemoteBridge($bridgeData['bridge_key'], $bridgeData['bridge_ipaddress']);
			}
		}
	}
}

/**
 * @param $bridgeKey
 * @param $serverIpAddr
 */
function addRemoteBridge($bridgeKey, $serverIpAddr)
{
	$query = "UPDATE `remote_bridge` SET  `bridge_status` = 'ok' WHERE `bridge_key` = ? AND `bridge_ipaddress` = ?";
	exec_query($query, array($bridgeKey, $serverIpAddr));
}

/**
 * @param $bridgeKey
 * @param $serverIpAddr
 */
function updateRemoteBridge($bridgeKey, $serverIpAddr)
{
	$query = "UPDATE  `remote_bridge`  SET  `bridge_status` = 'ok'  WHERE  `bridge_key` = ? AND  `bridge_ipaddress` = ?";
	exec_query($query, array($bridgeKey, $serverIpAddr));
}

/**
 * @param $bridgeKey
 * @param $serverIpAddr
 */
function deleteRemoteBridge($bridgeKey, $serverIpAddr)
{
	$query = "DELETE FROM `remote_bridge`  WHERE  `bridge_key` = ?  AND `bridge_ipaddress` = ?";
	exec_query($query, array($bridgeKey, $serverIpAddr));
}

/**
 * Generate page.
 *
 * @param $tpl iMSCP_pTemplate
 * @return void
 */
function bridge_generatePage($tpl)
{
	/** @var iMSCP_Config_Handler_File $cfg */
	$cfg = iMSCP_Registry::get('config');

	$query = 'SELECT * FROM `remote_bridge` WHERE `bridge_admin_id` = ?';
	$stmt = exec_query($query, $_SESSION['user_id']);

	$bridgelists = $stmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC | PDO::FETCH_GROUP);

	if ($stmt->rowCount()) {
		foreach ($bridgelists as $bridgeID => $bridgeData) {
			$tpl->assign(
				array(
					'BRIDGE_IPADDRESS' => tohtml($bridgeData['bridge_ipaddress']),
					'BRIDGE_KEY' => tohtml($bridgeData['bridge_key']),
					'STATUS' => tohtml(translate_dmn_status($bridgeData['bridge_status']))
				)
			);

			if ($bridgeData['bridge_status'] == $cfg->ITEM_OK_STATUS) {
				$tpl->assign(
					array(
						'EDIT_LINK' => "remotebridge.php?action=edit&bridge_id=$bridgeID",
						'EDIT_ICON' => 'i_edit',
						'TR_EDIT' => tr('Edit'),
						'DELETE_LINK' => "remotebridge.php?action=edit&action=delete&bridge_id=$bridgeID",
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

			$tpl->parse('BRIDGE_LIST', '.bridge_list');
		}

		$tpl->assign('ADD_BRIDGEKEY', '');
	} else {
		$tpl->assign('BRIDGE_LISTS', '');
		set_page_message(tr('You have not created any remote bridge.'), 'info');
	}

	if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'edit') {
		$bridgeID = clean_input($_REQUEST['bridge_id']);

		if (isset($bridgelists[$bridgeID])) {
			$bridgeData = $bridgelists[$bridgeID];

			$tpl->assign(
				array(
					'REMOTEBRIDGE_DIALOG_OPEN' => 1,
					'BRIDGE_KEY' => tohtml($bridgeData['bridge_key']),
					'BRIDGE_KEY_READONLY' => $cfg->HTML_READONLY,
					'BRIDGE_IPADDRESS' => tohtml($bridgeData['bridge_ipaddress']),
					'BRIDGE_ID' => tohtml($bridgeID),
					'ACTION' => 'edit'
				)
			);
		} else {
			showBadRequestErrorPage();
		}
	} else {
		$tpl->assign(
			array(
				'REMOTEBRIDGE_DIALOG_OPEN' => isset($_REQUEST['bridge_key']) ? 1 : 0,
				'BRIDGE_KEY' => isset($_REQUEST['bridge_key']) ? tohtml($_REQUEST['bridge_key']) : '',
				'BRIDGE_KEY_READONLY' => $cfg->HTML_READONLY,
				'BRIDGE_IPADDRESS' => isset($_REQUEST['bridge_ipaddress']) ? tohtml($_REQUEST['bridge_ipaddress']) : '',
				'BRIDGE_ID' => '-1',
				'ACTION' => 'add'
			)
		);
	}
}

/***********************************************************************************************************************
 * Main
 */


iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptStart);

check_login('reseller');

if (iMSCP_Registry::isRegistered('pluginManager')) {
	/** @var iMSCP_Plugin_Manager $pluginManager */
	$pluginManager = iMSCP_Registry::get('pluginManager');
} else {
	throw new iMSCP_Plugin_Exception('An unexpected error occured');
}

if (isset($_REQUEST['action'])) {
	$action = clean_input($_REQUEST['action']);

	if ($action == 'add') {
		if (bridge_manageKEY()) {
			set_page_message(tr('Remote Bridge successfully scheduled for addition'), 'success');
			redirectTo('remotebridge.php');
		}
	} elseif ($action == 'edit') {
		if (!empty($_POST) && bridge_manageKEY()) {
			set_page_message(tr('Remote Bridge successfully scheduled for update'), 'success');
			redirectTo('remotebridge.php');
		}
	} elseif ($action == 'delete') {
		bridge_deleteKEY();
		set_page_message(tr('Remote Bridge successfully scheduled for deletion'), 'success');
	} else {
		showBadRequestErrorPage();
	}
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => '../../plugins/RemoteBridge/frontend/remotebridge.tpl',
		'page_message' => 'layout',
		'bridge_lists' => 'page',
		'bridge_list' => 'bridge_lists'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('General / Overview / Remote Bridge'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_REMOTE_BRIDGES' => tr('Remote Bridge'),
		'TR_IP' => tr('IP'),
		'TR_BRIDGE_KEY' => tr('Bridge key'),
		'TR_GENERATE_BRIDGEKEY' => tr('Generate Bridge Key'),
		'TR_STATUS' => tr('Status'),
		'TR_ACTION' => tr('Action'),
		'TR_EDIT' => tr('Edit'),
		'TR_DELETE' => tr('Delete'),
		'TR_ADD_BRIDGE' => tr('Add Remote Bridge'),
		'TR_REMOTE_BRIDGE' => tr('Remote Bridge'),
		'TR_BRIDGE_IPADDRESS' => tr('Server ipaddress'),
		'TR_CONFIRM_DELETION' => tr('Please, confirm deletion of the %s Remote Bridge.', false, '%s'),
		'TR_APPLY' => tr('Apply'),
		'TR_CANCEL' => tr('Cancel')
	)
);

generateNavigation($tpl);
bridge_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onResellerScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();

unsetMessages();
