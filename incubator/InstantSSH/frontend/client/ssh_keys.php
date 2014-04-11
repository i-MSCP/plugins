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

/***********************************************************************************************************************
 * Functions
 */

/**
 * Send Json response
 *
 * @param int $statusCode HTTP status code
 * @param array $data JSON data
 * @return void
 */
function _instantssh_sendJsonResponse($statusCode = 200, array $data = array())
{
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-type: application/json');

	switch ($statusCode) {
		case 400:
			header('Status: 400 Bad Request');
			break;
		case 404:
			header('Status: 404 Not Found');
			break;
		case 500:
			header('Status: 500 Internal Server Error');
			break;
		case 501:
			header('Status: 501 Not Implemented');
			break;
		default:
			header('Status: 200 OK');
	}

	echo json_encode($data);
	exit;
}

/**
 * Get openSSH key and its associated fingerprint
 *
 * @param string $rsaKey RSA key (Supported formats: PKCS#1, openSSH and XML Signature)
 * @return array|false An array which contain the normalized SSH key and its associated fingerprint or false on failure
 */
function _instant_getOpenSshKey($rsaKey)
{
	require_once 'Crypt/RSA.php';

	$rsa = new Crypt_RSA();
	$ret = false;

	if ($rsa->loadKey($rsaKey)) {
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
function instantssh_getSshKey()
{
	if (isset($_GET['ssh_key_id'])) {
		$sshKeyId = intval($_GET['ssh_key_id']);

		try {
			$stmt = exec_query(
				'SELECT * FROM instant_ssh_keys WHERE ssh_key_admin_id = ? AND ssh_key_id = ?',
				array($_SESSION['user_id'], $sshKeyId)
			);

			if ($stmt->rowCount()) {
				_instantssh_sendJsonResponse(200, $stmt->fetchRow(PDO::FETCH_ASSOC));
			}

			_instantssh_sendJsonResponse(404, array('message' => tr('SSH Key not found.')));
		} catch (iMSCP_Exception_Database $e) {
			_instantssh_sendJsonResponse(500, array('message' => tr('An unexpected error occurred.')));
		}
	}

	_instantssh_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Add/Update SSH key
 *
 * @param iMSCP_Plugin_Manager $pluginManager
 * @param array $sshPermissions SSH permissions
 * @return void
 */
function instantssh_addSshKey($pluginManager, $sshPermissions)
{
	if (isset($_POST['ssh_key_id']) && isset($_POST['ssh_key_name']) && isset($_POST['ssh_key'])) {
		$sshKeyId = intval($_POST['ssh_key_id']);
		$sshKeyName = clean_input($_POST['ssh_key_name']);
		$sshKey = clean_input($_POST['ssh_key']);
		$sshKeyFingerprint = '';

		/** @var iMSCP_Plugin_InstantSSH $plugin */
		$plugin = $pluginManager->getPlugin('InstantSSH');

		$sshAuthOptions = $plugin->getConfigParam('default_ssh_auth_options', '');

		if ($sshPermissions['ssh_permission_auth_options']) {
			if (isset($_POST['ssh_auth_options']) && is_string($_POST['ssh_auth_options'])) {
				$sshAuthOptions = clean_input($_POST['ssh_auth_options']);
				$sshAuthOptions = str_replace(array("\r\n", "\r", "\n"), '', $sshAuthOptions);
				$allowedAuthOptions = $plugin->getConfigParam('allowed_ssh_auth_options', array());

				require_once 'InstantSSH/Validate/SshAuthOptions.php';

				$validator = new \InstantSSH\Validate\SshAuthOptions(array('auth_option' => $allowedAuthOptions));

				if (!$validator->isValid($sshAuthOptions)) {
					_instantssh_sendJsonResponse(400, array('message' => implode('<br />', $validator->getMessages())));
				}
			} else {
				_instantssh_sendJsonResponse(400, array('message' => tr('Bad requests.')));
			}
		}

		if ($sshKeyName == '' || $sshKey == '') {
			_instantssh_sendJsonResponse(400, array('message' => tr('All fields are required.')));
		} elseif (!preg_match('/^[[:alnum:] ]+$/i', $sshKeyName)) {
			_instantssh_sendJsonResponse(
				400, array('message' => tr('Unallowed SSH key name. Please use alphanumeric and space characters only.'))
			);
		} elseif (strlen($sshKeyName) > 255) {
			_instantssh_sendJsonResponse(400, array('message' => tr('SSH key name is too long (Max 255 characters).')));
		} else {
			if (($sshKey = _instant_getOpenSshKey($sshKey, $pluginManager)) === false) {
				_instantssh_sendJsonResponse(400, array('message' => tr('Invalid SSH key.')));
			}

			$sshKeyFingerprint = $sshKey['fingerprint'];
			$sshKey = $sshKey['key'];
		}

		try {
			if (!$sshKeyId) { // Add SSH key
				if (
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
					_instantssh_sendJsonResponse(
						200, array('message' => tr('SSH key scheduled for addition.'))
					);
				} else {
					_instantssh_sendJsonResponse(400, array('message' => tr('Your SSH key limit is reached.')));
				}
			} elseif ($sshPermissions['ssh_permission_auth_options']) { // Update SSH key
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
				_instantssh_sendJsonResponse(200, array('message' => tr('SSH key scheduled for update.')));
			}
		} catch (iMSCP_Exception_Database $e) {
			if ($e->getCode() == '23000') {
				_instantssh_sendJsonResponse(
					400, array('message' => tr('SSH key with same name or same fingerprint already exists.'))
				);
			} else {
				_instantssh_sendJsonResponse(500, array('message' => tr('An unexpected error occurred.')));
			}
		}
	}

	_instantssh_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Delete SSH key
 *
 * @return void
 */
function instantssh_deleteSshKey()
{
	if (isset($_POST['ssh_key_id'])) {
		$sshKeyId = intval($_POST['ssh_key_id']);

		try {
			exec_query(
				'
					UPDATE
						instant_ssh_keys
					SET
						ssh_key_status = ?
					WHERE
						ssh_key_id = ?
					AND
						ssh_key_admin_id = ?
				',
				array('todelete', $sshKeyId, $_SESSION['user_id'])
			);

			send_request();

			_instantssh_sendJsonResponse(200, array('message' => tr('SSH key scheduled for deletion.')));
		} catch (iMSCP_Exception_Database $e) {
			_instantssh_sendJsonResponse(
				500, array('message' => tr('An unexpected error occurred. Please contact your reseller.'))
			);
		}
	}

	_instantssh_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Get SSH keys list
 *
 * @return void
 */
function instantssh_getSshKeys()
{
	global $sshPermissions;

	try {
		$columns = array(
			'ssh_key_id', 'ssh_key_name', 'ssh_key', 'ssh_key_fingerprint', 'admin_sys_name', 'ssh_key_status'
		);

		$nbColumns = count($columns);

		$indexColumn = 'ssh_key_id';

		/* DB table to use */
		$table = 'instant_ssh_keys';

		/* Paging */
		$limit = '';

		if (isset($_GET['iDisplayStart']) && $_GET['iDisplayLength'] != '-1') {
			$limit = 'LIMIT ' . intval($_GET['iDisplayStart']) . ', ' . intval($_GET['iDisplayLength']);
		}

		/* Ordering */
		$order = '';

		if (isset($_GET['iSortCol_0'])) {
			$order = 'ORDER BY ';

			for ($i = 0; $i < intval($_GET['iSortingCols']); $i++) {
				if ($_GET['bSortable_' . intval($_GET['iSortCol_' . $i])] == 'true') {
					$order .= $columns[intval($_GET['iSortCol_' . $i])] . ' ' . $_GET['sSortDir_' . $i] . ', ';
				}
			}

			$order = substr_replace($order, '', -2);

			if ($order == 'ORDER BY') {
				$order = '';
			}
		}

		/* Filtering */
		$where = 'WHERE ssh_key_admin_id = ' . quoteValue($_SESSION['user_id']);

		if ($_GET['sSearch'] != '') {
			$where .= ' AND (';

			for ($i = 0; $i < $nbColumns; $i++) {
				$where .= $columns[$i] . ' LIKE ' . quoteValue("%{$_GET['sSearch']}%") . ' OR ';
			}

			$where = substr_replace($where, '', -3);
			$where .= ')';
		}

		/* Individual column filtering */
		for ($i = 0; $i < $nbColumns; $i++) {
			if (isset($_GET["bSearchable_$i"]) && $_GET["bSearchable_$i"] == 'true' && $_GET["sSearch_$i"] != '') {
				$where .= "AND {$columns[$i]} LIKE " . quoteValue("%{$_GET["sSearch_$i"]}%");
			}
		}

		/* Get data to display */
		$rResult = execute_query(
			'
				SELECT
					SQL_CALC_FOUND_ROWS ' . str_replace(' , ', ' ', implode(', ', $columns)) . " , admin_sys_name
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
		$resultFilterTotal = $resultFilterTotal->fetchRow(PDO::FETCH_NUM);
		$filteredTotal = $resultFilterTotal[0];

		/* Total data set length */
		$resultTotal = execute_query("SELECT COUNT($indexColumn) FROM $table");
		$resultTotal = $resultTotal->fetchRow(PDO::FETCH_NUM);
		$total = $resultTotal[0];

		/* Output */
		$output = array(
			'sEcho' => intval($_GET['sEcho']),
			'iTotalRecords' => $total,
			'iTotalDisplayRecords' => $filteredTotal,
			'aaData' => array()
		);

		$trShowSshKey = tr('Show SSH key');
		$trEditTooltip = tr('Edit SSH key options');
		$trDeleteTooltip = tr('Delete this SSH key');

		while ($data = $rResult->fetchRow(PDO::FETCH_ASSOC)) {
			$row = array();

			for ($i = 0; $i < $nbColumns; $i++) {
				if ($columns[$i] == 'ssh_key_status') {
					$row[$columns[$i]] = translate_dmn_status($data[$columns[$i]]);
				} else {
					$row[$columns[$i]] = tohtml($data[$columns[$i]]);
				}
			}

			if ($data['ssh_key_status'] == 'ok') {
				$row['ssh_key_actions'] =
					(
					($sshPermissions['ssh_permission_auth_options'])
						? "<span title=\"$trEditTooltip\" data-action=\"edit_ssh_key\" " .
						"data-ssh-key-id=\"{$data['ssh_key_id']}\" data-ssh-key-name=\"{$data['ssh_key_name']}\" " .
						"class=\"icon icon_edit clickable\">&nbsp;</span> "
						: "<span title=\"$trShowSshKey\" data-action=\"show_ssh_key\" " .
						"data-ssh-key-id=\"{$data['ssh_key_id']}\" data-ssh-key-name=\"{$data['ssh_key_name']}\" " .
						"class=\"icon icon_show clickable\">&nbsp;</span> "
					)
					.
					"<span title=\"$trDeleteTooltip\" data-action=\"delete_ssh_key\" " .
					"data-ssh-key-id=\"{$data['ssh_key_id']}\" data-ssh-key-name=\"{$data['ssh_key_name']}\" " .
					"class=\"icon icon_delete clickable\">&nbsp;</span>";
			} else {
				$row['ssh_key_actions'] = '';
			}

			$output['aaData'][] = $row;
		}

		_instantssh_sendJsonResponse(200, $output);
	} catch (iMSCP_Exception_Database $e) {
		_instantssh_sendJsonResponse(500, array('message' => tr('An unexpected error occurred')));
	}

	_instantssh_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/***********************************************************************************************************************
 * Main
 */

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

/** @var iMSCP_Plugin_Manager $pluginManager */
$pluginManager = iMSCP_Registry::get('pluginManager');

/** @var iMSCP_Plugin_InstantSSH $plugin */
$plugin = $pluginManager->getPlugin('InstantSSH');

$sshPermissions = $plugin->getCustomerPermissions($_SESSION['user_id']);

if ($sshPermissions['ssh_permission_max_keys'] > -1) {
	if (isset($_REQUEST['action'])) {
		if (is_xhr()) {
			$action = clean_input($_REQUEST['action']);

			switch ($action) {
				case 'get_ssh_keys':
					instantssh_getSshKeys();
					break;
				case 'get_ssh_key':
					instantssh_getSshKey();
					break;
				case 'add_ssh_key':
					instantssh_addSshKey($pluginManager, $sshPermissions);
					break;
				case 'delete_ssh_key':
					instantssh_deleteSshKey();
					break;
				default:
					_instantssh_sendJsonResponse(400, array('message' => tr('Bad request.')));
			}
		}

		showBadRequestErrorPage();
	}

	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => '../../plugins/InstantSSH/themes/default/view/client/ssh_keys.tpl',
			'page_message' => 'layout',
			'ssh_auth_options_block' => 'page',
			'ssh_key_save_button_block' => 'page'
		)
	);

	if (iMSCP_Registry::get('config')->DEBUG) {
		$assetVersion = time();
	} else {
		$pluginInfo = $pluginManager->getPluginInfo('InstantSSH');
		$assetVersion = strtotime($pluginInfo['date']);
	}

	$tpl->assign(
		array(
			'THEME_CHARSET' => tr('encoding'),
			'TR_PAGE_TITLE' => tr('Client / Profile / SSH Keys'),
			'ISP_LOGO' => layout_getUserLogo(),
			'INSTANT_SSH_ASSET_VERSION' => $assetVersion,
			'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations(),
			'TR_DYN_ACTIONS' => ($sshPermissions['ssh_permission_auth_options']) ? tr('Add / Edit') : tr('Add / Show'),
			'DEFAULT_AUTH_OPTIONS' => $plugin->getConfigParam('default_ssh_auth_options', '')
		)
	);

	if (!$sshPermissions['ssh_permission_auth_options']) {
		$tpl->assign(
			array(
				'TR_RESET_BUTTON_LABEL' => tr('Reset'),
				'SSH_AUTH_OPTIONS_BLOCK' => '',
				'SSH_KEY_SAVE_BUTTON_BLOCK' => ''
			)
		);
	} else {
		require_once 'InstantSSH/Validate/SshAuthOptions.php';

		$allowedSshAuthOptions = $plugin->getConfigParam('allowed_ssh_auth_options');

		$tpl->assign(
			array(
				'TR_ALLOWED_OPTIONS' => tr(
					'Allowed authentication options: %s <br />See man authorized_keys for more details',
					implode(', ', $allowedSshAuthOptions)
				),
				'TR_RESET_BUTTON_LABEL' => tr('Cancel')
			)
		);
	}

	generateNavigation($tpl);
	generatePageMessage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');

	iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

	$tpl->prnt();
} else {
	showBadRequestErrorPage();
}
