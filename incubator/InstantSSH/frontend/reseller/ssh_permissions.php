<?php
/**
 * i-MSCP InstantSSH plugin
 * Copyright (C) 2014-2015 Laurent Declercq <l.declercq@nuxwin.com>
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

use iMSCP_Database as Database;
use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventManager;
use iMSCP_Exception_Database as ExceptionDatabase;
use iMSCP_Plugin_Manager as PluginManager;
use iMSCP_pTemplate as TemplateEngnine;
use iMSCP_Registry as Registry;
use InstantSSH\CommonFunctions as Functions;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Get SSH permissions
 *
 * @return void
 */
function getSshPermissions()
{
	if(isset($_GET['ssh_permission_id']) && isset($_GET['ssh_permission_admin_id']) && isset($_GET['admin_name'])) {
		$sshPermId = intval($_GET['ssh_permission_id']);
		$sshPermAdminId = intval($_GET['ssh_permission_admin_id']);
		$adminName = clean_input($_GET['admin_name']);

		try {
			$stmt = exec_query(
				'
					SELECT
						ssh_permission_id, ssh_permission_admin_id, ssh_permission_max_users,
						ssh_permission_auth_options, ssh_permission_jailed_shell, admin_name
					FROM
						instant_ssh_permissions
					INNER JOIN
						admin ON(admin_id = ssh_permission_admin_id)
					WHERE
						ssh_permission_id = ?
					AND
						ssh_permission_admin_id = ?
					AND
						admin_type = ?
				',
				array($sshPermId, $sshPermAdminId, 'user')
			);

			if($stmt->rowCount()) {
				Functions::sendJsonResponse(200, $stmt->fetchRow(\PDO::FETCH_ASSOC));
			}
		} catch(ExceptionDatabase $e) {
			write_log(
				sprintf('InstantSSH: Unable to get SSH permissions for %s: %s', $adminName, $e->getMessage()),
				E_USER_ERROR
			);
			Functions::sendJsonResponse(
				500, array('message' => tr('An unexpected error occurred. Please contact your administrator.'))
			);
		}
	}

	Functions::sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Add/Update SSH permissions
 *
 * @param array $sshPermissions SSH permissions
 * @return void
 */
function addSshPermissions($sshPermissions)
{
	if(
		isset($_POST['ssh_permission_id']) && isset($_POST['ssh_permission_admin_id']) &&
		isset($_POST['ssh_permission_max_users']) && isset($_POST['admin_name'])
	) {
		$sshPermId = intval($_POST['ssh_permission_id']);
		$sshPermAdminId = intval($_POST['ssh_permission_admin_id']);
		$adminName = clean_input($_POST['admin_name']);
		$sshPermMaxUsers = intval($_POST['ssh_permission_max_users']);
		$sshPermAuthOptions = intval(
			($sshPermissions['ssh_permission_auth_options'])
				? ((isset($_POST['ssh_permission_auth_options'])) ?: 0)
				: 0
		);
		$sshPermJailedShell = intval(
			(!$sshPermissions['ssh_permission_jailed_shell'])
				? ((isset($_POST['ssh_permission_jailed_shell'])) ?: 0)
				: 1
		);

		if($adminName === '' || $sshPermMaxUsers === '') {
			Functions::sendJsonResponse(400, array('message' => tr('All fields are required.')));
		} elseif(!is_number($sshPermMaxUsers)) {
			Functions::sendJsonResponse(
				400,
				array(
					'message' => tr("Wrong value for the 'Maximum number of SSH users' field. Please, enter a number.")
				)
			);
		}

		$db = Database::getInstance();

		try {
			$db->beginTransaction();

			if(!$sshPermId) { // Add SSH permissions
				$response = EventManager::getInstance()->dispatch('onBeforeAddSshPermissions', array(
					'ssh_permission_max_user' => $sshPermMaxUsers,
					'ssh_permission_auth_options' => $sshPermAuthOptions,
					'ssh_permission_jailed_shell' => $sshPermJailedShell,
					'admin_name' => $adminName,
					'admin_type' => 'user'
				));

				if(!$response->isStopped()) {
					$stmt = exec_query(
						'
							INSERT INTO instant_ssh_permissions (
								ssh_permission_admin_id, ssh_permission_max_users, ssh_permission_auth_options,
								ssh_permission_jailed_shell, ssh_permission_status
							) SELECT
								admin_id, ?, ?, ?, ?
							FROM
								admin
							WHERE
								admin_name = ?
							AND
								admin_type = ?
						',
						array(
							$sshPermMaxUsers, $sshPermAuthOptions, $sshPermJailedShell, 'ok', encode_idna($adminName),
							'user'
						)
					);

					if($stmt->rowCount()) {
						$db->commit();

						EventManager::getInstance()->dispatch('onAfterAddSshPermissions', array(
							'ssh_permission_id' => $db->insertId(),
							'ssh_permission_max_user' => $sshPermMaxUsers,
							'ssh_permission_auth_options' => $sshPermAuthOptions,
							'ssh_permission_jailed_shell' => $sshPermJailedShell,
							'admin_name' => $adminName,
							'admin_type' => 'user'
						));

						write_log(sprintf('InstantSSH: SSH permissions were added for %s', $adminName), E_USER_NOTICE);
						Functions::sendJsonResponse(200, array('message' => tr('SSH permissions were added.')));
					}
				} else {
					Functions::sendJsonResponse(
						500, array('message' => tr('The action has been stopped by another plugin.'))
					);
				}
			} elseif($sshPermAdminId) { // Update SSH permissions
				$response = EventManager::getInstance()->dispatch('onBeforeUpdateSshPermissions', array(
					'ssh_permission_id' => $sshPermId,
					'ssh_permission_admin_id' => $sshPermAdminId,
					'ssh_permission_max_user' => $sshPermMaxUsers,
					'ssh_permission_auth_options' => $sshPermAuthOptions,
					'ssh_permission_jailed_shell' => $sshPermJailedShell,
					'admin_name' => $adminName,
					'admin_type' => 'user'
				));

				if(!$response->isStopped()) {
					# We must ensure that no child item is currently processed to avoid any race condition
					$stmt = exec_query(
						'
							SELECT
								ssh_user_id
							FROM
								instant_ssh_users
							INNER JOIN
								admin ON(admin_id = ssh_user_admin_id)
							WHERE
								created_by = ?
							AND
								ssh_user_status <> ?
							LIMIT
								1
						',
						array($sshPermAdminId, 'ok')
					);

					if(!$stmt->rowCount()) {
						if($sshPermissions['ssh_permission_id'] !== null) {
							// Update SSH permissions of the customer
							$stmt = exec_query(
								'
									UPDATE
										instant_ssh_permissions
									SET
										ssh_permission_max_users = ?, ssh_permission_auth_options = ?,
										ssh_permission_jailed_shell = ?
									WHERE
										ssh_permission_admin_id = ?
								',
								array($sshPermMaxUsers, $sshPermAuthOptions, $sshPermJailedShell, $sshPermAdminId)
							);

							if($stmt->rowCount()) {
								/** @var PluginManager $pluginManager */
								$pluginManager = Registry::get('pluginManager');

								/** @var \iMSCP_Plugin_InstantSSH $plugin */
								$plugin = $pluginManager->pluginGet('InstantSSH');

								// Update of the SSH users which belong to the customers
								$stmt = exec_query(
									'
										UPDATE
											instant_ssh_users
										INNER JOIN
											instant_ssh_permissions ON(ssh_permission_id = ssh_user_permission_id)
										SET
											ssh_user_auth_options = IF(ssh_permission_auth_options=1, ssh_user_auth_options, ?),
											ssh_user_status = ?
										WHERE
											ssh_user_admin_id = ?
									',
									array($plugin->getConfigParam('default_ssh_auth_options', null), 'tochange', $sshPermAdminId)
								);

								if($stmt->rowCount()) {
									register_shutdown_function('send_request');
								}

								EventManager::getInstance()->dispatch('onAfterUpdateSshPermissions', array(
									'ssh_permission_id' => $sshPermId,
									'ssh_permission_admin_id' => $sshPermAdminId,
									'ssh_permission_max_user' => $sshPermMaxUsers,
									'ssh_permission_auth_options' => $sshPermAuthOptions,
									'ssh_permission_jailed_shell' => $sshPermJailedShell,
									'admin_name' => $adminName,
									'admin_type' => 'reseller'
								));

								$db->commit();

								write_log(
									sprintf('InstantSSH: SSH permissions were updated for %s', $adminName),
									E_USER_NOTICE
								);
								Functions::sendJsonResponse(
									200, array('message' => tr('SSH permissions were updated.'))
								);
							} else {
								Functions::sendJsonResponse(
									202, array('message' => tr('Nothing has been changed.'))
								);
							}
						}
					} else {
						Functions::sendJsonResponse(
							409,
							array(
								'message' => tr(
									"One or many SSH users which belongs to the customer are currently processed. Please retry in few minutes."
								)
							)
						);
					}
				} else {
					Functions::sendJsonResponse(
						500, array('message' => tr('The action has been stopped by another plugin.'))
					);
				}
			}
		} catch(ExceptionDatabase $e) {
			$db->rollBack();

			write_log(
				sprintf('InstantSSH: Unable to add or update SSH permissions for %s: %s', $adminName, $e->getMessage()),
				E_USER_ERROR
			);
			Functions::sendJsonResponse(
				500, array('message' => tr('An unexpected error occurred. Please contact your administrator.'))
			);
		}
	}

	Functions::sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Delete SSH permissions
 *
 * @return void
 */
function deleteSshPermissions()
{
	if(isset($_POST['ssh_permission_id']) && isset($_POST['ssh_permission_admin_id']) && isset($_POST['admin_name'])) {
		$sshPermId = intval($_POST['ssh_permission_id']);
		$sshPermAdminId = intval($_POST['ssh_permission_admin_id']);
		$adminName = clean_input($_POST['admin_name']);

		$response = EventManager::getInstance()->dispatch('onBeforeDeleteSshPermissions', array(
			'ssh_permission_id' => $sshPermId,
			'ssh_permission_admin_id' => $sshPermAdminId,
			'admin_name' => $adminName,
			'admin_type' => 'user'
		));

		if(!$response->isStopped()) {
			$db = Database::getInstance();

			try {
				$db->beginTransaction();

				# We must ensure that no child item is currently processed to avoid any race condition
				$stmt = exec_query(
					'
						SELECT
							ssh_user_id
						FROM
							instant_ssh_users
						WHERE
							ssh_user_admin_id = ?
						AND
							ssh_user_status <> ?
						LIMIT
							1
					',
					array($sshPermAdminId, 'ok')
				);

				if(!$stmt->rowCount()) {
					$stmt = exec_query('DELETE FROM instant_ssh_permissions WHERE ssh_permission_id = ?', $sshPermId);

					if($stmt->rowCount()) {
						$db->commit();

						EventManager::getInstance()->dispatch('onAfterDeleteSshPermissions', array(
							'ssh_permission_id' => $sshPermId,
							'ssh_permission_admin_id' => $sshPermAdminId,
							'admin_name' => $adminName,
							'admin_type' => 'user'
						));

						send_request();
						write_log(
							sprintf('InstantSSH: SSH permissions were deleted for %s', $adminName), E_USER_NOTICE
						);
						Functions::sendJsonResponse(
							200, array('message' => tr('SSH permissions were deleted.', $adminName))
						);
					}
				} else {
					Functions::sendJsonResponse(
						409,
						array(
							'message' => tr(
								"One or many SSH users which belongs to the customer are currently processed. Please retry in few minutes.",
								true
							)
						)
					);
				}
			} catch(ExceptionDatabase $e) {
				$db->rollBack();
				write_log(
					sprintf('InstantSSH: Unable to delete SSH permissions ofr %s: %s', $adminName, $e->getMessage()),
					E_USER_ERROR
				);
				Functions::sendJsonResponse(
					500,
					array('message' => tr('An unexpected error occurred. Please contact your administrator.')
					)
				);
			}
		} else {
			Functions::sendJsonResponse(
				500, array('message' => tr('The action has been stopped by another plugin.'))
			);
		}
	}

	Functions::sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Search customer
 *
 * @return void
 */
function searchCustomer()
{
	if(isset($_GET['term'])) {
		$term = encode_idna(clean_input($_GET['term'])) . '%';

		try {
			$stmt = exec_query(
				'
					SELECT
						admin_name
					FROM
						admin
					LEFT JOIN
						instant_ssh_permissions ON(admin_id = ssh_permission_admin_id)
					WHERE
						admin_name LIKE ?
					AND
						admin_type = ?
					AND
						ssh_permission_admin_id IS NULL
				',
				array($term, 'user')
			);

			if($stmt->rowCount()) {
				$responseData = array();
				while($row = $stmt->fetchRow(\PDO::FETCH_ASSOC)) {
					$responseData[] = decode_idna($row['admin_name']);
				}
			} else {
				$responseData = array();
			}

			Functions::sendJsonResponse(200, $responseData);
		} catch(ExceptionDatabase $e) {
			write_log(sprintf('InstantSSH: Unable to search customer: %s', $e->getMessage()), E_USER_ERROR);
			Functions::sendJsonResponse(
				500, array('message' => tr('An unexpected error occurred. Please contact your administrator.'))
			);
		}
	}

	Functions::sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Get SSH permissions list
 *
 * @return void
 */
function getSshPermissionsList()
{
	try {
		// Filterable / orderable columns
		$cols = array(
			'admin_name', 'ssh_permission_max_users', 'ssh_permission_auth_options', 'ssh_permission_jailed_shell',
			'ssh_permission_status'
		);
		$nbCols = count($cols);
		$idxCol = 'ssh_permission_id';
		$table = 'instant_ssh_permissions'; /* DB table to use */

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
				if($_GET['bSortable_' . intval($_GET["iSortCol_$i"])] === 'true') {
					$sortDir = (
						isset($_GET["sSortDir_$i"]) && in_array($_GET["sSortDir_$i"], array('asc', 'desc'))
					) ? $_GET['sSortDir_' . $i] : 'asc';

					$order .= $cols[intval($_GET["iSortCol_$i"])] . ' ' . $sortDir . ', ';
				}
			}

			$order = substr_replace($order, '', -2);

			if($order == 'ORDER BY') {
				$order = '';
			}
		}

		/* Filtering */
		$where = "WHERE admin_type = 'user'";
		if(isset($_GET['sSearch']) && $_GET['sSearch'] !== '') {
			$where .= 'AND (';

			for($i = 0; $i < $nbCols; $i++) {
				$where .= $cols[$i] . ' LIKE ' . quoteValue('%' . $_GET['sSearch'] . '%') . ' OR ';
			}

			$where = substr_replace($where, '', -3);
			$where .= ')';
		}

		/* Individual column filtering */
		for($i = 0; $i < $nbCols; $i++) {
			if(isset($_GET["bSearchable_$i"]) && $_GET["bSearchable_$i"] === 'true' && $_GET["sSearch_$i"] !== '') {
				$where .= "AND {$cols[$i]} LIKE " . quoteValue('%' . $_GET['sSearch_' . $i] . '%');
			}
		}

		/* Get data to display */
		$rResult = execute_query(
			'
				SELECT
					SQL_CALC_FOUND_ROWS ' . str_replace(' , ', ' ', implode(', ', $cols)) . ",
					ssh_permission_id, ssh_permission_admin_id
				FROM
					$table
				INNER JOIN
					admin ON(admin_id = ssh_permission_admin_id)
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
		$resultTotal = execute_query(
			"
				SELECT
					COUNT($idxCol)
				FROM
					$table
				INNER JOIN
					admin ON(admin_id = ssh_permission_admin_id)
				WHERE
					admin_type = 'user'
			"
		);
		$resultTotal = $resultTotal->fetchRow(\PDO::FETCH_NUM);
		$total = $resultTotal[0];

		/* Output */
		$output = array(
			'sEcho' => intval($_GET['sEcho']),
			'iTotalRecords' => $total,
			'iTotalDisplayRecords' => $filteredTotal,
			'aaData' => array()
		);

		$trEditTooltip = tr('Edit permissions');
		$trDeleteTooltip = tr('Revoke permissions');

		while($data = $rResult->fetchRow(\PDO::FETCH_ASSOC)) {
			$row = array();

			for($i = 0; $i < $nbCols; $i++) {
				if($cols[$i] == 'ssh_permission_max_users') {
					$row[$cols[$i]] = (!$data[$cols[$i]]) ? tr('Unlimited') : $data[$cols[$i]];
				} elseif($cols[$i] == 'ssh_permission_auth_options') {
					$row[$cols[$i]] = ($data[$cols[$i]]) ? tr('Yes') : tr('No');
				} elseif($cols[$i] == 'ssh_permission_jailed_shell') {
					$row[$cols[$i]] = ($data[$cols[$i]]) ? tr('Yes') : tr('No');
				} elseif($cols[$i] == 'ssh_permission_status') {
					$row[$cols[$i]] = translate_dmn_status($data[$cols[$i]]);
				} else {
					$row[$cols[$i]] = $data[$cols[$i]];
				}
			}

			if($data['ssh_permission_status'] == 'ok') {
				$row['ssh_permission_actions'] =
					"<span title=\"$trEditTooltip\" data-action=\"edit_ssh_permissions\" " .
					"data-ssh-permission-id=\"" . $data['ssh_permission_id'] . "\" . " .
					"data-ssh-permission-admin-id=\"" . $data['ssh_permission_admin_id'] . "\" " .
					"data-admin-name=\"" . $data['admin_name'] . "\" . " .
					"class=\"icon icon_edit clickable\">&nbsp;</span> "
					.
					"<span title=\"$trDeleteTooltip\" data-action=\"delete_ssh_permissions\" " .
					"data-ssh-permission-id=\"" . $data['ssh_permission_id'] . "\" " .
					"data-ssh-permission-admin-id=\"" . $data['ssh_permission_admin_id'] . "\" " .
					"data-admin-name=\"" . $data['admin_name'] . "\" . " .
					"class=\"icon icon_delete clickable\">&nbsp;</span>";
			} else {
				$row['ssh_permission_actions'] = tr('n/a');
			}

			$output['aaData'][] = $row;
		}

		Functions::sendJsonResponse(200, $output);
	} catch(ExceptionDatabase $e) {
		write_log(sprintf('InstantSSH: Unable to get SSH permissions list: %s', $e->getMessage()), E_USER_ERROR);
		Functions::sendJsonResponse(
			500, array('message' => tr('An unexpected error occurred. Please contact your administrator.'))
		);
	}

	Functions::sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/***********************************************************************************************************************
 * Main
 */

EventManager::getInstance()->dispatch(Events::onResellerScriptStart);
check_login('reseller');

/** @var PluginManager $pluginManager */
$pluginManager = Registry::get('pluginManager');

/** @var \iMSCP_Plugin_InstantSSH $plugin */
$plugin = $pluginManager->pluginGet('InstantSSH');
$sshPermissions = $plugin->getResellerPermissions($_SESSION['user_id']);

if($sshPermissions['ssh_permission_id'] !== null) {
	if(isset($_REQUEST['action'])) {
		if(is_xhr()) {
			$action = clean_input($_REQUEST['action']);

			switch($action) {
				case 'get_ssh_permissions_list':
					getSshPermissionsList();
					break;
				case 'search_customer':
					searchCustomer();
					break;
				case 'add_ssh_permissions':
					addSshPermissions($sshPermissions);
					break;
				case 'get_ssh_permissions':
					getSshPermissions();
					break;
				case 'delete_ssh_permissions':
					deleteSshPermissions();
					break;
				default:
					Functions::sendJsonResponse(400, array('message' => tr('Bad request.')));
			}
		}

		showBadRequestErrorPage();
	}

	$tpl = new TemplateEngnine();
	$tpl->define_dynamic(array('layout' => 'shared/layouts/ui.tpl', 'page_message' => 'layout'));
	$tpl->define_no_file_dynamic(array(
		'page' => Functions::renderTpl(
			$pluginManager->pluginGetDirectory() . '/InstantSSH/themes/default/view/reseller/ssh_permissions.tpl'
		),
		'ssh_permission_auth_options_block' => 'page',
		'ssh_permission_jailed_shell_block' => 'page'
	));

	if(Registry::get('config')->DEBUG) {
		$assetVersion = time();
	} else {
		$pluginInfo = $pluginManager->pluginGetInfo('InstantSSH');
		$assetVersion = strtotime($pluginInfo['date']);
	}

	EventManager::getInstance()->registerListener('onGetJsTranslations', function ($e) {
		/** @var $e \iMSCP_Events_Event */
		$e->getParam('translations')->InstantSSH = array(
			'datatable' => getDataTablesPluginTranslations(false)
		);
	});

	$tpl->assign(array(
		'TR_PAGE_TITLE' => Functions::escapeHtml(tr('Reseller / Customers / SSH Permissions')),
		'INSTANT_SSH_ASSET_VERSION' => Functions::escapeUrl($assetVersion),
		'PAGE_MESSAGE' => '' // Remove default message HTML element (not used here)
	));

	if(!$sshPermissions['ssh_permission_auth_options']) {
		$tpl->assign('SSH_PERMISSION_AUTH_OPTIONS_BLOCK', '');
	}

	if($sshPermissions['ssh_permission_jailed_shell']) {
		$tpl->assign('SSH_PERMISSION_JAILED_SHELL_BLOCK', '');
	}

	generateNavigation($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');
	EventManager::getInstance()->dispatch(Events::onResellerScriptEnd, array('templateEngine' => $tpl));
	$tpl->prnt();
} else {
	showBadRequestErrorPage();
}
