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
function _cronjobs_sendJsonResponse($statusCode = 200, array $data = array())
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
function cronjobs_getCronPermissions()
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
				_cronjobs_sendJsonResponse(200, $stmt->fetchRow(PDO::FETCH_ASSOC));
			}

			_cronjobs_sendJsonResponse(404, array('message' => tr('Cron permissions not found.')));
		} catch (iMSCP_Exception_Database $e) {
			write_log(sprintf('CronJobs: Unable to get cron permissions: %s', $e->getMessage()), E_USER_ERROR);

			_cronjobs_sendJsonResponse(
				500, array('message' => tr('An unexpected error occurred: %s', true, $e->getMessage()))
			);
		}
	}

	_cronjobs_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Add/Update cron permissions
 *
 * @return void
 */
function cronjobs_addCronPermissions()
{
	if (
		isset($_POST['cron_permission_id']) && isset($_POST['admin_name']) && isset($_POST['cron_permission_type']) &&
		isset($_POST['cron_permission_frequency'])
	) {
		$cronPermissionId = intval($_POST['cron_permission_id']);
		$adminName = clean_input($_POST['admin_name']);
		$cronPermissionType = clean_input($_POST['cron_permission_type']);
		$cronPermissionFrequency = clean_input($_POST['cron_permission_frequency']);

		if ($adminName == '' || $cronPermissionFrequency == '') {
			_cronjobs_sendJsonResponse(400, array('message' => tr('All fields are required.')));
		} elseif (!is_number($cronPermissionFrequency)) {
			_cronjobs_sendJsonResponse(
				400, array('message' => tr("Wrong value for the 'Job frequency' field. Please, enter a number."))
			);
		}

		$db = iMSCP_Database::getInstance();

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
					',
					array($cronPermissionType, $cronPermissionFrequency, 'ok', $adminName)
				);
				if($stmt->rowCount()) {
					$db->commit();

					send_request();

					write_log(sprintf('CronJobs: Cron permissions were added for %s', $adminName), E_USER_NOTICE);

					_cronjobs_sendJsonResponse(200, array('message' => tr('Cron permissions were added.')));
				}
			} else { // Update cron permissions
				$stmt = exec_query(
					'
						UPDATE
							cron_permissions
						SET
							cron_permission_type = ?, cron_permission_frequency = ?, cron_permission_status = ?
						WHERE
							cron_permission_id = ?
					',
					array($cronPermissionType, $cronPermissionFrequency, 'ok', $cronPermissionId)
				);
				if($stmt->rowCount()) {
					$db->commit();

					send_request();

					write_log(sprintf('Cron permissions were updated for %s', $adminName), E_USER_NOTICE);

					_cronjobs_sendJsonResponse(200, array('message' => tr('Cron permissions were updated.')));
				}
			}
		} catch (iMSCP_Exception_Database $e) {
			$db->rollBack();

			if ($e->getCode() != '23000') {
				write_log(
					sprintf('CronJobs: Unable to update cron permissions for %s: %s', $adminName, $e->getMessage()),
					E_USER_ERROR
				);

				_cronjobs_sendJsonResponse(
					500, array('message' => tr('An unexpected error occurred: %s', true, $e->getMessage()))
				);
			}
		}
	}

	_cronjobs_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Delete cron permissions
 *
 * @return void
 */
function cronjobs_deleteCronPermissions()
{
	if (isset($_POST['cron_permission_id'])) {
		$cronPermissionId = intval($_POST['cron_permission_id']);

		try {
			$stmt = exec_query(
				'UPDATE cron_permissions SET cron_permission_status = ? WHERE cron_permission_id = ?',
				array('todelete', $cronPermissionId)
			);

			if ($stmt->rowCount()) {
				send_request();

				write_log(
					sprintf('CronJobs: Cron permissions with ID %s were scheduled for deletion', $cronPermissionId),
					E_USER_NOTICE
				);

				_cronjobs_sendJsonResponse(200, array('message' => tr('Cron permissions were scheduled for deletion.')));
			}
		} catch (iMSCP_Exception_Database $e) {
			write_log(
				sprintf('CronJobs: Unable to delete cron permissions with ID %s: %s', $cronPermissionId, $e->getMessage()),
				E_USER_ERROR
			);

			_cronjobs_sendJsonResponse(
				500, array('message' => tr('An unexpected error occurred: %s', true, $e->getMessage()))
			);
		}
	}

	_cronjobs_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Search reseller
 *
 * Note: Only reseller which doesn't have cron permissions already set are returned.
 *
 * @return void
 */
function cronjobs_searchReseller()
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
						admin_id NOT IN(SELECT cron_permission_admin_id FROM cron_permissions)
				',
				array($term, 'reseller')
			);

			if ($stmt->rowCount()) {
				$responseData = array();
				while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
					$responseData[] = decode_idna($row['admin_name']);
				}
			} else {
				$responseData = array();
			}

			_cronjobs_sendJsonResponse(200, $responseData);
		} catch (iMSCP_Exception_Database $e) {
			write_log(sprintf('CronJobs: Unable to search reseller: %s', $e->getMessage()), E_USER_ERROR);

			_cronjobs_sendJsonResponse(
				500, array('message' => tr('An unexpected error occurred: %s', true, $e->getMessage()))
			);
		}
	}

	_cronjobs_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Get cron permissions list
 *
 * @return void
 */
function cronjobs_getCronPermissionsList()
{
	try {
		$columns = array(
			'cron_permission_id', 'cron_permission_admin_id', 'admin_name', 'cron_permission_type',
			'cron_permission_frequency', 'cron_permission_status'
		);

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
		$where = "WHERE admin_type = 'reseller'";

		if ($_REQUEST['sSearch'] != '') {
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
					SQL_CALC_FOUND_ROWS ' . str_replace(' , ', ' ', implode(', ', $columns)) . "
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
					admin_type = 'reseller'
			"
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
				} elseif($columns[$i] == 'cron_permission_type') {
					$row[$columns[$i]] = tohtml(ucfirst($data[$columns[$i]]));
				} elseif($columns[$i] == 'cron_permission_frequency') {
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

		_cronjobs_sendJsonResponse(200, $output);
	} catch (iMSCP_Exception_Database $e) {
		write_log(sprintf('CronJobs: Unable to get cron permissions list: %s', $e->getMessage()), E_USER_ERROR);

		_cronjobs_sendJsonResponse(
			500, array('message' => tr('An unexpected error occurred: %s', true, $e->getMessage()))
		);
	}

	_cronjobs_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/***********************************************************************************************************************
 * Main
 */

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

if (isset($_REQUEST['action'])) {
	if (is_xhr()) {
		$action = clean_input($_REQUEST['action']);

		switch ($action) {
			case 'get_cron_permissions_list':
				cronjobs_getCronPermissionsList();
				break;
			case 'search_reseller':
				cronjobs_searchReseller();
				break;
			case 'add_cron_permissions':
				cronjobs_addCronPermissions();
				break;
			case 'get_cron_permissions':
				cronjobs_getCronPermissions();
				break;
			case 'delete_cron_permissions':
				cronjobs_deleteCronPermissions();
				break;
			default:
				_cronjobs_sendJsonResponse(400, array('message' => tr('Bad request.')));
		}
	}

	showBadRequestErrorPage();
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => '../../plugins/CronJobs/themes/default/view/admin/cron_permissions.tpl',
		'page_message' => 'layout',
		'cron_permission_jailed' => 'page'
	)
);

if (iMSCP_Registry::get('config')->DEBUG) {
	$assetVersion = time();
} else {
	$pluginInfo = iMSCP_Registry::get('pluginManager')->getPluginInfo('CronJobs');
	$assetVersion = strtotime($pluginInfo['date']);
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Admin / Settings / Cron Permissions'),
		'ISP_LOGO' => layout_getUserLogo(),
		'CRONJOBS_ASSET_VERSION' => $assetVersion,
		'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations()
	)
);

/** @var iMSCP_Plugin_Manager $pluginManager */
$pluginManager = iMSCP_Registry::get('pluginManager');
if($pluginManager->isPluginKnown('InstantSSH')) {
	$info = $pluginManager->getPluginInfo('InstantSSH');

	if(! $pluginManager->isPluginEnabled('InstantSSH') || version_compare($info['version'], '2.0.2', '<')) {
		$tpl->assign('CRON_PERMISSION_JAILED', '');
	}
} else {
	$tpl->assign('CRON_PERMISSION_JAILED', '');
}

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
