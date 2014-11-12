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
 * Get SSH user
 *
 * @return void
 */
function getSshUser()
{
	if(isset($_GET['ssh_user_id'])) {
		$sshUserId = intval($_GET['ssh_user_id']);

		try {
			$stmt = exec_query(
				'SELECT * FROM instant_ssh_users WHERE ssh_user_admin_id = ? AND ssh_user_id = ?',
				array($_SESSION['user_id'], $sshUserId)
			);

			if($stmt->rowCount()) {
				Common::sendJsonResponse(200, $stmt->fetchRow(\PDO::FETCH_ASSOC));
			}

			Common::sendJsonResponse(404, array('message' => tr('SSH user not found.', true)));
		} catch(ExceptionDatabase $e) {
			write_log(sprintf('InstantSSH: Unable to get SSH user: %s', $e->getMessage()), E_USER_ERROR);

			Common::sendJsonResponse(500, array('message' => tr('An unexpected error occurred.', true)));
		}
	}

	Common::sendJsonResponse(400, array('message' => tr('Bad request.', true)));
}

/**
 * Add/Update SSH user
 *
 * @param \iMSCP_Plugin_Manager $pluginManager
 * @param array $sshPermissions SSH permissions
 * @return void
 */
function addSshUser($pluginManager, $sshPermissions)
{
	if(isset($_POST['ssh_user_id']) && isset($_POST['ssh_user_name'])) {
		$sshUserId = intval($_POST['ssh_user_id']);
		$sshUserName = clean_input($_POST['ssh_user_name']);
		$sshUserPassword = $sshUserPasswordConfirmation = null;
		$sshUserKey = clean_input($_POST['ssh_user_key']);
		$sshUserKeyFingerprint = '';

		/** @var \iMSCP_Plugin_InstantSSH $plugin */
		$plugin = $pluginManager->getPlugin('InstantSSH');

		if(!$plugin->getConfigParam('passwordless_authentication', false)) {
			if(isset($_POST['ssh_user_password']) && isset($_POST['ssh_user_password_confirmation'])) {
				$sshUserPassword = clean_input($_POST['ssh_user_password']);
				$sshUserPasswordConfirmation = clean_input($_POST['ssh_user_password_confirmation']);
			} else {
				Common::sendJsonResponse(400, array('message' => tr('Bad requests.', true)));
			}
		}

		/** @var \iMSCP_Plugin_InstantSSH $plugin */
		$plugin = $pluginManager->getPlugin('InstantSSH');

		$sshAuthOptions = $plugin->getConfigParam('default_ssh_auth_options', null);

		$errorMsgs = array();

		if($sshPermissions['ssh_permission_auth_options']) {
			if(isset($_POST['ssh_user_auth_options']) && is_string($_POST['ssh_user_auth_options'])) {
				$sshAuthOptions = clean_input($_POST['ssh_user_auth_options']);
				$sshAuthOptions = str_replace(array("\r\n", "\r", "\n"), '', $sshAuthOptions);
				$allowedAuthOptions = $plugin->getConfigParam('allowed_ssh_auth_options', array());

				$validator = new SshAuthOptions(array('auth_option' => $allowedAuthOptions));

				if(!$validator->isValid($sshAuthOptions)) {
					$errorMsgs[] = implode('<br />', $validator->getMessages());
				}
			} else {
				Common::sendJsonResponse(400, array('message' => tr('Bad requests.', true)));
			}
		}

		if(!$sshUserId) {
			if($sshUserName == '') {
				$errorMsgs[] = tr('The username field is required.', true);
			} elseif(!preg_match('/^[[:alnum:]]+$/i', $sshUserName)) {
				$errorMsgs[] = tr('Un-allowed username. Please use alphanumeric characters only.', true);
			} elseif(strlen($sshUserName) > 8) {
				$errorMsgs[] = tr('The username is too long (Max 8 characters).', true);
			}

			$sshUserName = $plugin->getConfigParam('ssh_user_name_prefix', 'ssh_') . $sshUserName;

			if(posix_getpwnam($sshUserName)) {
				$errorMsgs[] = tr("This username is not available.", true);
			}
		}

		if($sshUserPassword == '' && $sshUserKey == '') {
			if($plugin->getConfigParam('passwordless_authentication', false)) {
				$errorMsgs[] = tr('You must enter an SSH key.', true);
			} else {
				$errorMsgs[] = tr('You must enter either a password, an SSH key or both.', true);
			}
		}

		if($sshUserPassword != '') {
			if(!preg_match('/^[[:alnum:]]+$/i', $sshUserPassword)) {
				$errorMsgs[] = tr('Un-allowed password. Please use alphanumeric characters only.', true);
			} elseif (strlen($sshUserPassword) < 8) {
				$errorMsgs[] = tr('Wrong password length (Min 6 characters).', true);
			} elseif(strlen($sshUserPassword) > 32) {
				$errorMsgs[] = tr('Wrong password length (Max 32 characters).', true);
			} elseif($sshUserPassword !== $sshUserPasswordConfirmation) {
				$errorMsgs[] = tr('Passwords do not match.', true);
			}
		}

		if($sshUserKey != '') {
			if(($sshUserKey = getOpenSshKey($sshUserKey)) === false) {
				$errorMsgs[] = tr('Invalid SSH key.', true);
			} else {
				$sshUserKeyFingerprint = $sshUserKey['fingerprint'];
				$sshUserKey = $sshUserKey['key'];
			}
		} else {
			$sshUserKey = $sshAuthOptions = $sshUserKeyFingerprint = null;
		}

		if($errorMsgs) {
			Common::sendJsonResponse(400, array('message' => implode('<br />', $errorMsgs)));
		}

		if($sshUserPassword != '') {
			$sshUserPassword = cryptPasswordWithSalt($sshUserPassword, generateRandomSalt(true));
		} else {
			$sshUserPassword = null;
		}

		try {
			if(!$sshUserId) { // Add SSH user
				if(
					$sshPermissions['ssh_permission_max_users'] == 0 ||
					$sshPermissions['ssh_permission_cnb_users'] < $sshPermissions['ssh_permission_max_users']
				) {
					exec_query(
						'
							INSERT INTO instant_ssh_users (
								ssh_user_permission_id, ssh_user_admin_id, ssh_user_name, ssh_user_password,
								ssh_user_key, ssh_user_key_fingerprint, ssh_user_auth_options, ssh_user_status
							) VALUES (
								?, ?, ?, ?, ?, ?, ?, ?
							)
						',
						array(
							$sshPermissions['ssh_permission_id'], $_SESSION['user_id'], $sshUserName, $sshUserPassword,
							$sshUserKey, $sshUserKeyFingerprint, $sshAuthOptions, 'toadd'
						)
					);

					send_request();

					write_log(
						sprintf(
							'InstantSSH: %s added new SSH user: %s', decode_idna($_SESSION['user_logged']),
							$sshUserName
						),
						E_USER_NOTICE
					);

					Common::sendJsonResponse(
						200, array('message' => tr('SSH user has been scheduled for addition.', true))
					);
				} else {
					Common::sendJsonResponse(400, array('message' => tr('Your SSH user limit is reached.', true)));
				}
			} else { // Update SSH user
				exec_query(
					'
						UPDATE
							instant_ssh_users
						SET
							ssh_user_password = ?, ssh_user_key = ?, ssh_user_key_fingerprint = ?,
							ssh_user_auth_options = ?, ssh_user_status = ?
						WHERE
							ssh_user_id = ?
						AND
							ssh_user_admin_id = ?
						AND
							ssh_user_status = ?
					',
					array(
						$sshUserPassword, $sshUserKey, $sshUserKeyFingerprint, $sshAuthOptions, 'tochange', $sshUserId,
						$_SESSION['user_id'], 'ok'
					)
				);

				send_request();

				write_log(
					sprintf('InstantSSH: %s updated SSH user : %s', decode_idna($_SESSION['user_logged']), $sshUserName),
					E_USER_NOTICE
				);

				Common::sendJsonResponse(200, array('message' => tr('SSH user has been scheduled for update.', true)));
			}
		} catch(ExceptionDatabase $e) {
			if($e->getCode() == '23000') {
				Common::sendJsonResponse(400, array('message' => tr("This SSH key is already assigned to another SSH user.", true)));
			} else {
				write_log(sprintf('InstantSSH: Unable to add or update SSH user: %s', $e->getMessage()), E_USER_ERROR);

				Common::sendJsonResponse(
					500, array('message' => tr('An unexpected error occurred. Please contact your reseller', true))
				);
			}
		}
	}

	Common::sendJsonResponse(400, array('message' => tr('Bad request.', true)));
}

/**
 * Delete SSH user
 *
 * @return void
 */
function deleteSshUser()
{
	if(isset($_POST['ssh_user_id'])) {
		$sshUserId = intval($_POST['ssh_user_id']);

		try {
			$stmt = exec_query(
				'UPDATE instant_ssh_users SET ssh_user_status = ? WHERE ssh_user_id = ? AND ssh_user_admin_id = ?',
				array('todelete', $sshUserId, $_SESSION['user_id'])
			);

			if($stmt->rowCount()) {
				send_request();

				write_log(
					sprintf('InstantSSH: %s deleted SSH user with ID: %s', decode_idna($_SESSION['user_logged']), $sshUserId),
					E_USER_NOTICE
				);

				Common::sendJsonResponse(200, array('message' => tr('SSH user has been scheduled for deletion.', true)));
			}
		} catch(ExceptionDatabase $e) {
			write_log(sprintf('InstantSSH: Unable to delete SSH user: %s', $e->getMessage()), E_USER_ERROR);

			Common::sendJsonResponse(
				500, array('message' => tr('An unexpected error occurred. Please contact your reseller.', true))
			);
		}
	}

	Common::sendJsonResponse(400, array('message' => tr('Bad request.', true)));
}

/**
 * Get SSH users list
 *
 * @return void
 */
function getSshUsers()
{
	try {
		// Filterable / orderable columns
		$columns = array('ssh_user_name', 'ssh_user_key_fingerprint', 'ssh_user_status');

		$nbColumns = count($columns);

		$indexColumn = 'ssh_user_id';

		/* DB table to use */
		$table = 'instant_ssh_users';

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
		$where = 'WHERE ssh_user_admin_id = ' . intval($_SESSION['user_id']);

		if($_GET['sSearch'] != '') {
			$where .= ' AND (';

			for($i = 0; $i < $nbColumns; $i++) {
				$where .= $columns[$i] . ' LIKE ' . quoteValue('%' . $_GET['sSearch'] . '%') . ' OR ';
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
					ssh_user_id
				FROM
					$table
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

		$trEditTooltip = tr('Edit SSH user', true);
		$trDeleteTooltip = tr('Delete this SSH user', true);

		while($data = $rResult->fetchRow(\PDO::FETCH_ASSOC)) {
			$row = array();

			for($i = 0; $i < $nbColumns; $i++) {
				if($columns[$i] == 'ssh_user_key_fingerprint') {
					$row[$columns[$i]] = ($data[$columns[$i]]) ?: tr('n/a', true);
				} elseif($columns[$i] == 'ssh_user_status') {
					$row[$columns[$i]] = translate_dmn_status($data[$columns[$i]]);
				} else {
					$row[$columns[$i]] = tohtml($data[$columns[$i]]);
				}
			}

			if($data['ssh_user_status'] == 'ok') {
				$row['ssh_user_actions'] =
					"<span title=\"$trEditTooltip\" data-action=\"edit_ssh_user\" " . "data-ssh-user-id=\"" .
					$data['ssh_user_id'] . "\" data-ssh-user-name=\"" . $data['ssh_user_name'] .
					"\" class=\"icon icon_edit clickable\">&nbsp;</span> "
					.
					"<span title=\"$trDeleteTooltip\" data-action=\"delete_ssh_user\" " . "data-ssh-user-id=\"" .
					$data['ssh_user_id'] . "\" data-ssh-user-name=\"" . $data['ssh_user_name'] .
					"\" class=\"icon icon_delete clickable\">&nbsp;</span>";
			} else {
				$row['ssh_user_actions'] = '';
			}

			$output['aaData'][] = $row;
		}

		Common::sendJsonResponse(200, $output);
	} catch(ExceptionDatabase $e) {
		write_log(sprintf('InstantSSH: Unable to get SSH users: %s', $e->getMessage()), E_USER_ERROR);

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
				case 'get_ssh_users':
					getSshUsers();
					break;
				case 'get_ssh_user':
					getSshUser();
					break;
				case 'add_ssh_user':
					addSshUser($pluginManager, $sshPermissions);
					break;
				case 'delete_ssh_user':
					deleteSshUser();
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
			'ssh_password_field_block' => 'page',
			'ssh_auth_options_block' => 'page',
			'ssh_password_key_info_block' => 'page'
		)
	);

	$tpl->define_no_file_dynamic('page', Common::renderTpl(
			PLUGINS_PATH . '/InstantSSH/themes/default/view/client/ssh_users.tpl')
	);

	if(Registry::get('config')->DEBUG) {
		$assetVersion = time();
	} else {
		$pluginInfo = $pluginManager->getPluginInfo('InstantSSH');
		$assetVersion = strtotime($pluginInfo['date']);
	}

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => Common::escapeHtml(tr('Client / Profile / SSH Users', true)),
			'ISP_LOGO' => layout_getUserLogo(),
			'INSTANT_SSH_ASSET_VERSION' => Common::escapeUrl($assetVersion),
			'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations(),
			'DEFAULT_AUTH_OPTIONS' => $plugin->getConfigParam('default_ssh_auth_options', '')
		)
	);

	if(!$sshPermissions['ssh_permission_auth_options']) {
		$tpl->assign('SSH_AUTH_OPTIONS_BLOCK', '');
	} else {
		$allowedSshAuthOptions = $plugin->getConfigParam('allowed_ssh_auth_options');

		$tpl->assign(
			'TR_ALLOWED_OPTIONS', Common::escapeHtml(
				tr('Allowed authentication options: %s', true, implode(', ', $allowedSshAuthOptions))
			)
		);
	}

	if($plugin->getConfigParam('passwordless_authentication', false)) {
		$tpl->assign(
			array(
				'SSH_PASSWORD_KEY_INFO_BLOCK' => '',
				'SSH_PASSWORD_FIELD_BLOCK' => ''
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
