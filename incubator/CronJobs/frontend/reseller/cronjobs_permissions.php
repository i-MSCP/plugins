<?php
/**
 * i-MSCP CronJobs plugin
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

namespace CronJobs\Reseller;

use iMSCP_Database as Database;
use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventsAggregator;
use iMSCP_Exception_Database as DatabaseException;
use iMSCP_Plugin_CronJobs as PluginCronJobs;
use iMSCP_Plugin_Manager as PluginManager;
use iMSCP_pTemplate as TemplateEngine;
use iMSCP_Registry as Registry;
use PDO;

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
function _sendJsonResponse($statusCode = 200, array $data = array())
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

	exit(json_encode($data));
}

/**
 * Get cron permissions
 *
 * @return void
 */
function getCronPermissions()
{
	if (isset($_GET['cron_permission_id'])) {
		try {
			$stmt = exec_query(
				'
					SELECT
						cron_permission_id, cron_permission_admin_id, cron_permission_type, cron_permission_frequency,
						admin_name
					FROM
						cron_permissions
					INNER JOIN
						admin ON(admin_id = cron_permission_admin_id)
					WHERE
						cron_permission_id = ?
				',
				intval($_GET['cron_permission_id'])
			);

			if ($stmt->rowCount()) {
				_sendJsonResponse(200, $stmt->fetchRow(PDO::FETCH_ASSOC));
			}

			_sendJsonResponse(404, array('message' => tr('Cron permissions not found.')));
		} catch (DatabaseException $e) {
			write_log(sprintf('CronJobs: Unable to get cron permissions: %s', $e->getMessage()), E_USER_ERROR);

			_sendJsonResponse(
				500, array('message' => tr('An unexpected error occurred. Please contact your administrator'))
			);
		}
	}

	_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Add/Update cron permissions
 *
 * @param array $cronPermissions Cron permissions
 * @return void
 */
function addCronPermissions($cronPermissions)
{
	if (
		isset($_POST['cron_permission_id']) && isset($_POST['admin_name']) && isset($_POST['cron_permission_type']) &&
		isset($_POST['cron_permission_frequency'])
	) {
		$cronPermissionId = intval($_POST['cron_permission_id']);
		$adminName = encode_idna(clean_input($_POST['admin_name']));
		$cronPermissionType = clean_input($_POST['cron_permission_type']);
		$cronPermissionFrequency = clean_input($_POST['cron_permission_frequency']);

		if ($adminName == '' || $cronPermissionFrequency == '') {
			_sendJsonResponse(400, array('message' => tr('All fields are required.')));
		} elseif (!is_number($cronPermissionFrequency)) {
			_sendJsonResponse(
				400, array('message' => tr("Wrong value for the 'Job frequency' field. Please, enter a number."))
			);
		}

		$db = Database::getInstance();

		try {
			$db->beginTransaction();

			if (!$cronPermissionId) { // Add cron permissions
				$stmt = exec_query(
					'
						INSERT INTO cron_permissions(
							cron_permission_admin_id, cron_permission_type, cron_permission_frequency,
							cron_permission_status
						) SELECT
							admin_id, ?, ?, ?
						FROM
							admin
						WHERE
							admin_name = ?
						AND
							created_by = ?
					',
					array(
						$cronPermissionType,
						$cronPermissionFrequency,
						($cronPermissionType == 'jailed') ? 'toadd' : 'ok',
						$adminName,
						$_SESSION['user_id']
					)
				);
				if($stmt->rowCount()) {
					$db->commit();

					if($cronPermissionType == 'jailed') {
						send_request();
					}

					write_log(sprintf('CronJobs: Cron permissions were added for %s', $adminName), E_USER_NOTICE);

					_sendJsonResponse(200, array('message' => tr('Cron permissions were scheduled for addition.')));
				}
			} else { // Update cron permissions
				$stmt = exec_query(
					'
						UPDATE
							cron_permissions
						INNER JOIN
							admin ON(admin_id = cron_permission_admin_id)
						SET
							cron_permission_type = ?, cron_permission_frequency = ?, cron_permission_status = ?
						WHERE
							cron_permission_id = ?
						AND
							created_by = ?
					',
					array(
						$cronPermissionType,
						$cronPermissionFrequency,
						($cronPermissionType == 'jailed') ? 'tochange' : 'ok',
						$cronPermissionId,
						$_SESSION['user_id']
					)
				);
				if($stmt->rowCount()) {
					$db->commit();

					if($cronPermissionType == 'jailed') {
						send_request();
					}

					write_log(sprintf('Cron permissions were updated for %s', $adminName), E_USER_NOTICE);

					_sendJsonResponse(200, array('message' => tr('Cron permissions were scheduled for update.')));
				}
			}
		} catch (DatabaseException $e) {
			$db->rollBack();

			if ($e->getCode() != '23000') {
				write_log(
					sprintf('CronJobs: Unable to update cron permissions for %s: %s', $adminName, $e->getMessage()),
					E_USER_ERROR
				);

				_sendJsonResponse(
					500, array('message' => tr('An unexpected error occurred. Please contact your administrator')
					)
				);
			}
		}
	}

	_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Delete cron permissions
 *
 * @return void
 */
function deleteCronPermissions()
{
	if (isset($_POST['cron_permission_id'])) {
		$cronPermissionId = intval($_POST['cron_permission_id']);

		try {
			$stmt = exec_query(
				'
					UPDATE
						cron_permissions
					INNER JOIN
						admin ON(admin_id = cron_permission_admin_id)
					SET
						cron_permission_status = ?
					WHERE
						cron_permission_id = ?
					AND
						created_by = ?
				',
				array('todelete', $cronPermissionId, $_SESSION['user_id'])
			);

			if ($stmt->rowCount()) {
				send_request();

				write_log(
					sprintf('CronJobs: Cron permissions with ID %s were scheduled for deletion', $cronPermissionId),
					E_USER_NOTICE
				);

				_sendJsonResponse(200, array('message' => tr('Cron permissions were scheduled for deletion.')));
			}
		} catch (DatabaseException $e) {
			write_log(
				sprintf('CronJobs: Unable to delete cron permissions with ID %s: %s', $cronPermissionId, $e->getMessage()),
				E_USER_ERROR
			);

			_sendJsonResponse(
				500, array('message' => tr('An unexpected error occurred. Please contact your administrator'))
			);
		}
	}

	_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Search customer
 *
 * Note: Only customer which doesn't have cron permissions already set are returned.
 *
 * @return void
 */
function searchCustomer()
{
	if (isset($_GET['term'])) {
		$term = encode_idna(clean_input($_GET['term'])) . '%';

		try {
			$stmt = exec_query(
				'
					SELECT
						admin_name
					FROM
						admin
					WHERE
						admin_name LIKE ?
					AND
						admin_type = ?
					AND
						created_by = ?
					AND
						admin_id NOT IN(SELECT cron_permission_admin_id FROM cron_permissions)
				',
				array($term, 'user', $_SESSION['user_id'])
			);

			if ($stmt->rowCount()) {
				$responseData = array();
				while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
					$responseData[] = decode_idna($row['admin_name']);
				}
			} else {
				$responseData = array();
			}

			_sendJsonResponse(200, $responseData);
		} catch (DatabaseException $e) {
			write_log(sprintf('CronJobs: Unable to search customer: %s', $e->getMessage()), E_USER_ERROR);

			_sendJsonResponse(
				500, array('message' => tr('An unexpected error occurred. Please contact your administrator'))
			);
		}
	}

	_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Get cron permissions list
 *
 * @return void
 */
function getCronPermissionsList()
{
	try {
		// Filterable, orderable columns
		$columns = array('admin_name', 'cron_permission_type', 'cron_permission_frequency', 'cron_permission_status');

		$nbColumns = count($columns);
		$indexColumn = 'cron_permission_id';

		/* DB table to use */
		$table = 'cron_permissions';

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
		$where = 'WHERE created_by =' . intval($_SESSION['user_id']);

		if ($_GET['sSearch'] != '') {
			$where .= 'AND (';

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
					SQL_CALC_FOUND_ROWS cron_permission_id, cron_permission_admin_id,
			' . str_replace(' , ', ' ', implode(', ', $columns)) . "
				FROM
					$table
				INNER JOIN
					admin ON(admin_id = cron_permission_admin_id)
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
		$resultTotal = execute_query(
			"
				SELECT
					COUNT($indexColumn)
				FROM
					$table
				INNER JOIN
					admin ON(admin_id = cron_permission_admin_id)
				WHERE
					created_by = " .  intval($_SESSION['user_id'])
		);
		$resultTotal = $resultTotal->fetchRow(PDO::FETCH_NUM);
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

		while ($data = $rResult->fetchRow(PDO::FETCH_ASSOC)) {
			$row = array();

			for ($i = 0; $i < $nbColumns; $i++) {
				if ($columns[$i] == 'admin_name') {
					$row[$columns[$i]] = tohtml(decode_idna($data[$columns[$i]]));
				} elseif ($columns[$i] == 'cron_permission_type') {
					$row[$columns[$i]] = tohtml(ucfirst($data[$columns[$i]]));
				} elseif ($columns[$i] == 'cron_permission_frequency') {
					$row[$columns[$i]] = tohtml(ucfirst($data[$columns[$i]])) . ' ' . tr('minutes');
				} elseif ($columns[$i] == 'cron_permission_status') {
					$row[$columns[$i]] = translate_dmn_status($data[$columns[$i]]);
				} else {
					$row[$columns[$i]] = tohtml($data[$columns[$i]]);
				}
			}

			if ($data['cron_permission_status'] == 'ok') {
				$row['cron_permission_actions'] =
					"<span title=\"$trEditTooltip\" data-action=\"edit_cron_permissions\" " .
					"data-cron-permission-id=\"{$data['cron_permission_id']}\" " .
					"class=\"icon icon_edit clickable\">&nbsp;</span> "
					.
					"<span title=\"$trDeleteTooltip\" data-action=\"delete_cron_permissions\" " .
					"data-cron-permission-id=\"{$data['cron_permission_id']}\" " .
					"class=\"icon icon_delete clickable\">&nbsp;</span>";
			} else {
				$row['cron_permission_actions'] = tr('n/a');
			}

			$output['aaData'][] = $row;
		}

		_sendJsonResponse(200, $output);
	} catch (DatabaseException $e) {
		write_log(sprintf('CronJobs: Unable to get cron permissions list: %s', $e->getMessage()), E_USER_ERROR);

		_sendJsonResponse(
			500, array('message' => tr('An unexpected error occurred. Please contact your administrator'))
		);
	}

	_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/***********************************************************************************************************************
 * Main
 */

EventsAggregator::getInstance()->dispatch(Events::onResellerScriptStart);

check_login('reseller');

/** @var PluginManager $pluginManager */
$pluginManager = Registry::get('pluginManager');

/** @var PluginCronJobs $cronjobsPlugin */
$cronjobsPlugin = $pluginManager->getPlugin('CronJobs');

$cronPermissions = $cronjobsPlugin->getCronPermissions(intval($_SESSION['user_id']));
unset($cronjobsPlugin);

if ($cronPermissions) {
	if (isset($_REQUEST['action'])) {
		if (is_xhr()) {
			$action = clean_input($_REQUEST['action']);

			switch ($action) {
				case 'get_cron_permissions_list':
					getCronPermissionsList();
					break;
				case 'search_customer':
					searchCustomer();
					break;
				case 'add_cron_permissions':
					addCronPermissions($cronPermissions);
					break;
				case 'get_cron_permissions':
					getCronPermissions();
					break;
				case 'delete_cron_permissions':
					deleteCronPermissions();
					break;
				default:
					_sendJsonResponse(400, array('message' => tr('Bad request.')));
			}
		}

		showBadRequestErrorPage();
	}

	$tpl = new TemplateEngine();
	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => '../../plugins/CronJobs/themes/default/view/reseller/cron_permissions.tpl',
			'page_message' => 'layout',
			'cron_permission_url' => 'page',
			'cron_permission_jailed' => 'page',
			'cron_permission_full' => 'page'
		)
	);

	if (Registry::get('config')->DEBUG) {
		$assetVersion = time();
	} else {
		$pluginInfo = Registry::get('pluginManager')->getPluginInfo('CronJobs');
		$assetVersion = strtotime($pluginInfo['date']);
	}

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('Reseller / Customers / Cron Permissions'),
			'ISP_LOGO' => layout_getUserLogo(),
			'CRONJOBS_ASSET_VERSION' => $assetVersion,
			'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations()
		)
	);

	if($cronPermissions['cron_permission_type'] == 'url') {
		$tpl->assign(
			array(
				'CRON_PERMISSION_JAILED' => '',
				'CRON_PERMISSION_FULL' => ''
			)
		);
	} elseif($cronPermissions['cron_permission_type'] == 'jailed') {
		$tpl->assign(
			array(
				'CRON_PERMISSION_URL' => '',
				'CRON_PERMISSION_FULL' => ''
			)
		);
	} else {
		/** @var PluginManager $pluginManager */
		$pluginManager = Registry::get('pluginManager');
		if($pluginManager->isPluginKnown('InstantSSH')) {
			$info = $pluginManager->getPluginInfo('InstantSSH');

			if(! $pluginManager->isPluginEnabled('InstantSSH') || version_compare($info['version'], '2.0.4', '<')) {
				$tpl->assign('CRON_PERMISSION_JAILED', '');
			}
		} else {
			$tpl->assign('CRON_PERMISSION_JAILED', '');
		}
	}

	generateNavigation($tpl);
	generatePageMessage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');

	EventsAggregator::getInstance()->dispatch(Events::onResellerScriptEnd, array('templateEngine' => $tpl));

	$tpl->prnt();

} else {
	showBadRequestErrorPage();
}
