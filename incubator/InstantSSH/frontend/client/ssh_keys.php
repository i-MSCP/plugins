<?php
/**
 * i-MSCP InstantSSH plugin
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

namespace InstantSSH\Admin;

use Crypt_RSA as CryptRsa;
use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventsAggregator;
use iMSCP_Exception_Database as ExceptionDatabase;
use iMSCP_pTemplate as TemplateEngnine;
use iMSCP_Registry as Registry;
use InstantSSH\CommonFunctions as Common;
use InstantSSH\Validate\SshAuthOptions as SshAuthOptions;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Get openSSH key and its associated fingerprint
 *
 * @param string $rsaKey RSA key (Supported formats: PKCS#1, openSSH and XML Signature)
 * @return array|false An array which contain the normalized SSH key and its associated fingerprint or false on failure
 */
function getOpenSshKey($rsaKey)
{
	$rsa = new CryptRsa();
	$ret = false;

	if($rsa->loadKey($rsaKey)) {
		$ret = array();

		$rsa->setPublicKey();
		$ret['key'] = $rsa->getPublicKey(CRYPT_RSA_PUBLIC_FORMAT_OPENSSH);

		$content = explode(' ', $ret['key'], 3);
		$ret['fingerprint'] = join(':', str_split(md5(base64_decode($content[1])), 2));
	}

	return $ret;
}

/**
 * Get SSH Key
 *
 * @return void
 */
function getSshKey()
{
	if(isset($_GET['ssh_key_id'])) {
		$sshKeyId = intval($_GET['ssh_key_id']);

		try {
			$stmt = exec_query(
				'SELECT * FROM instant_ssh_keys WHERE ssh_key_admin_id = ? AND ssh_key_id = ?',
				array($_SESSION['user_id'], $sshKeyId)
			);

			if($stmt->rowCount()) {
				Common::sendJsonResponse(200, $stmt->fetchRow(\PDO::FETCH_ASSOC));
			}

			Common::sendJsonResponse(404, array('message' => tr('SSH Key not found.', true)));
		} catch(ExceptionDatabase $e) {
			write_log(sprintf('InstantSSH: Unable to get SSH key: %s', $e->getMessage()), E_USER_ERROR);

			Common::sendJsonResponse(500, array('message' => tr('An unexpected error occurred.', true)));
		}
	}

	Common::sendJsonResponse(400, array('message' => tr('Bad request.', true)));
}

/**
 * Add/Update SSH key
 *
 * @param \iMSCP_Plugin_Manager $pluginManager
 * @param array $sshPermissions SSH permissions
 * @return void
 */
function addSshKey($pluginManager, $sshPermissions)
{
	if(isset($_POST['ssh_key_id']) && isset($_POST['ssh_key_name']) && isset($_POST['ssh_key'])) {
		$sshKeyId = intval($_POST['ssh_key_id']);
		$sshKeyName = clean_input($_POST['ssh_key_name']);
		$sshKey = clean_input($_POST['ssh_key']);
		$sshKeyFingerprint = '';

		/** @var \iMSCP_Plugin_InstantSSH $plugin */
		$plugin = $pluginManager->getPlugin('InstantSSH');

		$sshAuthOptions = $plugin->getConfigParam('default_ssh_auth_options', '');

		if($sshPermissions['ssh_permission_auth_options']) {
			if(isset($_POST['ssh_auth_options']) && is_string($_POST['ssh_auth_options'])) {
				$sshAuthOptions = clean_input($_POST['ssh_auth_options']);
				$sshAuthOptions = str_replace(array("\r\n", "\r", "\n"), '', $sshAuthOptions);
				$allowedAuthOptions = $plugin->getConfigParam('allowed_ssh_auth_options', array());

				$validator = new SshAuthOptions(array('auth_option' => $allowedAuthOptions));

				if(!$validator->isValid($sshAuthOptions)) {
					Common::sendJsonResponse(400, array('message' => implode('<br />', $validator->getMessages())));
				}
			} else {
				Common::sendJsonResponse(400, array('message' => tr('Bad requests.', true)));
			}
		}

		if($sshKeyName == '' || $sshKey == '') {
			Common::sendJsonResponse(400, array('message' => tr('All fields are required.', true)));
		} elseif(!preg_match('/^[[:alnum:] ]+$/i', $sshKeyName)) {
			Common::sendJsonResponse(
				400,
				array('message' => tr('Un-allowed SSH key name. Please use alphanumeric and space characters only.', true))
			);
		} elseif(strlen($sshKeyName) > 255) {
			Common::sendJsonResponse(400, array('message' => tr('SSH key name is too long (Max 255 characters).', true)));
		} else {
			if(($sshKey = getOpenSshKey($sshKey)) === false) {
				Common::sendJsonResponse(400, array('message' => tr('Invalid SSH key.', true)));
			}

			$sshKeyFingerprint = $sshKey['fingerprint'];
			$sshKey = $sshKey['key'];
		}

		try {
			if(!$sshKeyId) { // Add SSH key
				if(
					$sshPermissions['ssh_permission_max_keys'] == 0 ||
					$sshPermissions['ssh_permission_cnb_keys'] < $sshPermissions['ssh_permission_max_keys']
				) {
					exec_query(
						'
							INSERT INTO instant_ssh_keys (
								ssh_permission_id, ssh_key_admin_id, ssh_key_name, ssh_key, ssh_key_fingerprint,
								ssh_auth_options, ssh_key_status
							) VALUES (
								?, ?, ?, ?, ?, ?, ?
							)
						',
						array(
							$sshPermissions['ssh_permission_id'], $_SESSION['user_id'], $sshKeyName, $sshKey,
							$sshKeyFingerprint, $sshAuthOptions, 'toadd'
						)
					);

					send_request();

					write_log(
						sprintf(
							'InstantSSH: %s added new SSH key with fingerprint: %s',
							decode_idna($_SESSION['user_logged']),
							$sshKeyFingerprint
						),
						E_USER_NOTICE
					);

					Common::sendJsonResponse(200, array('message' => tr('SSH key scheduled for addition.', true)));
				} else {
					Common::sendJsonResponse(400, array('message' => tr('Your SSH key limit is reached.', true)));
				}
			} elseif($sshPermissions['ssh_permission_auth_options']) { // Update SSH key
				exec_query(
					'
						UPDATE
							instant_ssh_keys
						SET
							ssh_auth_options = ?, ssh_key_status = ?
						WHERE
							ssh_key_id = ?
						AND
							ssh_key_admin_id = ?
						AND
							ssh_key_status = ?
					',
					array($sshAuthOptions, 'tochange', $sshKeyId, $_SESSION['user_id'], 'ok')
				);

				send_request();

				write_log(
					sprintf(
						'InstantSSH: %s updated SSH key with fingerprint: %s',
						decode_idna($_SESSION['user_logged']),
						$sshKeyFingerprint
					),
					E_USER_NOTICE
				);

				Common::sendJsonResponse(200, array('message' => tr('SSH key scheduled for update.', true)));
			}
		} catch(ExceptionDatabase $e) {
			if($e->getCode() == '23000') {
				Common::sendJsonResponse(
					400, array('message' => tr('SSH key with same name or same fingerprint already exists.', true))
				);
			} else {
				write_log(sprintf('InstantSSH: Unable to add or update SSH key: %s', $e->getMessage()), E_USER_ERROR);

				Common::sendJsonResponse(
					500, array('message' => tr('An unexpected error occurred. Please contact your reseller.', true))
				);
			}
		}
	}

	Common::sendJsonResponse(400, array('message' => tr('Bad request.', true)));
}

/**
 * Delete SSH key
 *
 * @return void
 */
function deleteSshKey()
{
	if(isset($_POST['ssh_key_id'])) {
		$sshKeyId = intval($_POST['ssh_key_id']);

		try {
			exec_query(
				'UPDATE instant_ssh_keys SET ssh_key_status = ? WHERE ssh_key_id = ? AND ssh_key_admin_id = ?',
				array('todelete', $sshKeyId, $_SESSION['user_id'])
			);

			send_request();

			write_log(
				sprintf('InstantSSH: %s deleted SSH key with ID: %s', decode_idna($_SESSION['user_logged']), $sshKeyId),
				E_USER_NOTICE
			);

			Common::sendJsonResponse(200, array('message' => tr('SSH key scheduled for deletion.', true)));
		} catch(ExceptionDatabase $e) {
			write_log(sprintf('InstantSSH: Unable to delete SSH key: %s', $e->getMessage()), E_USER_ERROR);

			Common::sendJsonResponse(
				500, array('message' => tr('An unexpected error occurred. Please contact your reseller.', true))
			);
		}
	}

	Common::sendJsonResponse(400, array('message' => tr('Bad request.', true)));
}

/**
 * Get SSH keys list
 *
 * @return void
 */
function getSshKeys()
{
	global $sshPermissions;

	try {
		// Filterable / orderable columns
		$columns = array('ssh_key_name', 'ssh_key_fingerprint', 'admin_sys_name', 'ssh_key_status');

		$nbColumns = count($columns);

		$indexColumn = 'ssh_key_id';

		/* DB table to use */
		$table = 'instant_ssh_keys';

		/* Paging */
		$limit = '';

		if(isset($_GET['iDisplayStart']) && isset($_GET['iDisplayLength']) && $_GET['iDisplayLength'] !== '-1') {
			$limit = 'LIMIT ' . intval($_GET['iDisplayStart']) . ', ' . intval($_GET['iDisplayLength']);
		}

		/* Ordering */
		$order = '';

		if(isset($_GET['iSortCol_0']) && isset($_GET['iSortingCols'])) {
			$order = 'ORDER BY ';

			for($i = 0; $i < intval($_GET['iSortingCols']); $i++) {
				if($_GET['bSortable_' . intval($_GET['iSortCol_' . $i])] === 'true') {
					$sortDir = (
						isset($_GET['sSortDir_' . $i]) && in_array($_GET['sSortDir_' . $i], array('asc', 'desc'))
					) ? $_GET['sSortDir_' . $i] : 'asc';

					$order .= $columns[intval($_GET['iSortCol_' . $i])] . ' ' . $sortDir . ', ';
				}
			}

			$order = substr_replace($order, '', -2);

			if($order == 'ORDER BY') {
				$order = '';
			}
		}

		/* Filtering */
		$where = 'WHERE ssh_key_admin_id = ' . intval($_SESSION['user_id']);

		if($_GET['sSearch'] != '') {
			$where .= ' AND (';

			for($i = 0; $i < $nbColumns; $i++) {
				$where .= $columns[$i] . ' LIKE ' . quoteValue('%' . $_GET['sSearch'] .'%') . ' OR ';
			}

			$where = substr_replace($where, '', -3);
			$where .= ')';
		}

		/* Individual column filtering */
		for($i = 0; $i < $nbColumns; $i++) {
			if(isset($_GET['bSearchable_' . $i]) && $_GET['bSearchable_' . $i] === 'true' && $_GET['sSearch_' . $i] !== '') {
				$where .= "AND {$columns[$i]} LIKE " . quoteValue('%' . $_GET['sSearch_' . $i] . '%');
			}
		}

		/* Get data to display */
		$rResult = execute_query(
			'
				SELECT
					SQL_CALC_FOUND_ROWS ' . str_replace(' , ', ' ', implode(', ', $columns)) . ",
					ssh_key_id
				FROM
					$table
				INNER JOIN
					admin ON(admin_id = ssh_key_admin_id)
				$where
				$order
				$limit
			"
		);

		/* Data set length after filtering */
		$resultFilterTotal = execute_query('SELECT FOUND_ROWS()');
		$resultFilterTotal = $resultFilterTotal->fetchRow(\PDO::FETCH_NUM);
		$filteredTotal = $resultFilterTotal[0];

		/* Total data set length */
		$resultTotal = execute_query("SELECT COUNT($indexColumn) FROM $table");
		$resultTotal = $resultTotal->fetchRow(\PDO::FETCH_NUM);
		$total = $resultTotal[0];

		/* Output */
		$output = array(
			'sEcho' => intval($_GET['sEcho']),
			'iTotalRecords' => $total,
			'iTotalDisplayRecords' => $filteredTotal,
			'aaData' => array()
		);

		$trShowSshKey = Common::escapeJs(tr('Show SSH key', true));
		$trEditTooltip = Common::escapeJs(tr('Edit SSH key', true));
		$trDeleteTooltip = Common::escapeJs(tr('Delete this SSH key', true));

		while($data = $rResult->fetchRow(\PDO::FETCH_ASSOC)) {
			$row = array();

			for($i = 0; $i < $nbColumns; $i++) {
				if($columns[$i] == 'ssh_key_status') {
					$row[$columns[$i]] = translate_dmn_status($data[$columns[$i]]);
				} else {
					$row[$columns[$i]] = tohtml($data[$columns[$i]]);
				}
			}

			if($data['ssh_key_status'] == 'ok') {
				$row['ssh_key_actions'] =
					(
					($sshPermissions['ssh_permission_auth_options'])
						? "<span title=\"$trEditTooltip\" data-action=\"edit_ssh_key\" " .
						"data-ssh-key-id=\"" . $data['ssh_key_id'] ."\" data-ssh-key-name=\"" . $data['ssh_key_name'] .
						"\" class=\"icon icon_edit clickable\">&nbsp;</span> "
						: "<span title=\"$trShowSshKey\" data-action=\"show_ssh_key\" " .
						"data-ssh-key-id=\"" . $data['ssh_key_id'] ."\" data-ssh-key-name=\"" . $data['ssh_key_name'] .
						"\" class=\"icon icon_show clickable\">&nbsp;</span> "
					)
					.
					"<span title=\"$trDeleteTooltip\" data-action=\"delete_ssh_key\" " .
					"data-ssh-key-id=\"" . $data['ssh_key_id'] . "\" data-ssh-key-name=\"" . $data['ssh_key_name'] .
					"\" class=\"icon icon_delete clickable\">&nbsp;</span>";
			} else {
				$row['ssh_key_actions'] = '';
			}

			$output['aaData'][] = $row;
		}

		Common::sendJsonResponse(200, $output);
	} catch(ExceptionDatabase $e) {
		write_log(sprintf('InstantSSH: Unable to get SSH keys: %s', $e->getMessage()), E_USER_ERROR);

		Common::sendJsonResponse(
			500, array('message' => tr('An unexpected error occurred. Please contact your reseller.', true))
		);
	}

	Common::sendJsonResponse(400, array('message' => tr('Bad request.', true)));
}

/***********************************************************************************************************************
 * Main
 */

EventsAggregator::getInstance()->dispatch(Events::onClientScriptStart);

check_login('user');

/** @var \iMSCP_Plugin_Manager $pluginManager */
$pluginManager = Registry::get('pluginManager');

/** @var \iMSCP_Plugin_InstantSSH $plugin */
$plugin = $pluginManager->getPlugin('InstantSSH');

$sshPermissions = $plugin->getCustomerPermissions($_SESSION['user_id']);

if($sshPermissions['ssh_permission_id'] !== null) {
	Common::initEscaper();

	if(isset($_REQUEST['action'])) {
		if(is_xhr()) {
			$action = clean_input($_REQUEST['action']);

			switch($action) {
				case 'get_ssh_keys':
					getSshKeys();
					break;
				case 'get_ssh_key':
					getSshKey();
					break;
				case 'add_ssh_key':
					addSshKey($pluginManager, $sshPermissions);
					break;
				case 'delete_ssh_key':
					deleteSshKey();
					break;
				default:
					Common::sendJsonResponse(400, array('message' => tr('Bad request.', true)));
			}
		}

		showBadRequestErrorPage();
	}

	$tpl = new TemplateEngnine();
	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page_message' => 'layout',
			'ssh_auth_options_block' => 'page',
			'ssh_show_action' => 'page',
			'ssh_edit_action' => 'page'
		)
	);

	$tpl->define_no_file_dynamic('page', Common::renderTpl(
		PLUGINS_PATH . '/InstantSSH/themes/default/view/client/ssh_keys.tpl')
	);

	if(Registry::get('config')->DEBUG) {
		$assetVersion = time();
	} else {
		$pluginInfo = $pluginManager->getPluginInfo('InstantSSH');
		$assetVersion = strtotime($pluginInfo['date']);
	}

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => Common::escapeHtml(tr('Client / Profile / SSH Keys', true)),
			'ISP_LOGO' => layout_getUserLogo(),
			'INSTANT_SSH_ASSET_VERSION' => Common::escapeUrl($assetVersion),
			'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations(),
			'TR_DYN_ACTIONS' => Common::escapeHtml(
				($sshPermissions['ssh_permission_auth_options'])
					? tr('Add / Edit SSH keys', true) :tr('Add / Show SSH keys', true)
			),
			'DEFAULT_AUTH_OPTIONS' => $plugin->getConfigParam('default_ssh_auth_options', '')
		)
	);

	if(!$sshPermissions['ssh_permission_auth_options']) {
		$tpl->assign(
			array(
				'TR_RESET_BUTTON_LABEL' => Common::escapeHtml(tr('Cancel', true)),
				'SSH_AUTH_OPTIONS_BLOCK' => '',
				'SSH_EDIT_ACTION' => ''
			)
		);
	} else {
		$allowedSshAuthOptions = $plugin->getConfigParam('allowed_ssh_auth_options');

		$tpl->assign(
			array(
				'TR_ALLOWED_OPTIONS' => Common::escapeHtml(
					tr('Allowed authentication options: %s', true, implode(', ', $allowedSshAuthOptions))
				),
				'TR_RESET_BUTTON_LABEL' => Common::escapeHtml(tr('Cancel', true)),
				'SSH_SHOW_ACTION' => ''
			)
		);
	}

	generateNavigation($tpl);
	generatePageMessage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');

	EventsAggregator::getInstance()->dispatch(Events::onClientScriptEnd, array('templateEngine' => $tpl));

	$tpl->prnt();
} else {
	showBadRequestErrorPage();
}
