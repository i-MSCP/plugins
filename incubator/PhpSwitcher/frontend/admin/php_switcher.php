<?php
/**
 * i-MSCP PhpSwitcher plugin
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
 * Schedule change for all domains which belong to the given user list
 *
 * @param array $adminIds List of user ID for which domains configuration files must be regenerated
 * @return void
 */
function _phpSwitcher_scheduleDomainsChange(array $adminIds)
{
	$adminIdList = implode(',', $adminIds);

	exec_query(
		'UPDATE domain SET domain_status = ? WHERE domain_admin_id IN (' . $adminIdList . ') AND domain_status = ?',
		array('tochange', 'ok')
	);

	exec_query(
		'
			UPDATE
				subdomain
			JOIN
				domain USING(domain_id)
			SET
				subdomain_status = ?
			WHERE
				domain_admin_id IN (' . $adminIdList . ')
			AND
				subdomain_status = ?
		',
		array('tochange', 'ok')
	);

	exec_query(
		'
			UPDATE
				domain_aliasses
			JOIN
				domain USING(domain_id)
			SET
				alias_status = ?
			WHERE
				domain_admin_id IN (' . $adminIdList . ')
			AND
				alias_status = ?
		',
		array('tochange', 'ok')
	);

	exec_query(
		'
			UPDATE
				subdomain_alias
			JOIN
				domain_aliasses USING(alias_id)
			SET
				subdomain_alias_status = ?
			WHERE
				domain_id = (SELECT domain_id FROM domain WHERE domain_admin_id IN (' . $adminIdList . '))
			AND
				subdomain_alias_status = ?
		',
		array('tochange', 'ok')
	);
}

/**
 * Send Json response
 *
 * @param int $statusCode HTTPD status code
 * @param array $data JSON data
 * @return void
 */
function _phpSwitcher_sendJsonResponse($statusCode = 200, array $data = array())
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
 * Get PHP version
 *
 * @return void
 */
function phpSwitcher_get()
{
	if (isset($_GET['version_id']) && isset($_GET['version_name'])) {
		$versionId = intval($_REQUEST['version_id']);
		$versionName = clean_input($_REQUEST['version_name']);

		try {
			$stmt = exec_query('SELECT * FROM php_switcher_version WHERE version_id = ?', $versionId);

			if ($stmt->rowCount()) {
				_phpSwitcher_sendJsonResponse(200, $stmt->fetchRow(PDO::FETCH_ASSOC));
			}

			_phpSwitcher_sendJsonResponse(
				404, array('message' => tr('PHP Version %s has not been found.', $versionName))
			);
		} catch (iMSCP_Exception_Database $e) {
			_phpSwitcher_sendJsonResponse(
				500, array('message' => tr('An unexpected error occured: %s', true, $e->getMessage()))
			);
		}
	}

	_phpSwitcher_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Add PHP version
 *
 * @return void
 */
function phpSwitcher_add()
{
	if (isset($_POST['version_name']) && isset($_POST['version_binary_path']) && isset($_POST['version_confdir_path'])) {
		$versionName = clean_input($_POST['version_name']);
		$versionBinaryPath = clean_input($_POST['version_binary_path']);
		$versionConfdirPath = clean_input($_POST['version_confdir_path']);

		if ($versionName == '' || $versionBinaryPath == '' || $versionConfdirPath == '') {
			_phpSwitcher_sendJsonResponse(400, array('message' => tr('All fields are required.')));
		} elseif (strtolower($versionName) == 'php' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION) {
			_phpSwitcher_sendJsonResponse(
				400,
				array('message' => tr('PHP Version %s already exists. This is the default PHP version.', $versionName))
			);
		}

		try {
			exec_query(
				'
					INSERT INTO php_switcher_version (
						version_name, version_binary_path, version_confdir_path
					) VALUES (
						?, ?, ?
					)
				',
				array($versionName, $versionBinaryPath, $versionConfdirPath)
			);

			_phpSwitcher_sendJsonResponse(
				200, array('message' => tr('PHP Version %s successfully created.', $versionName))
			);
		} catch (iMSCP_Exception_Database $e) {
			if ($e->getCode() == '23000') {
				_phpSwitcher_sendJsonResponse(
					400, array('message' => tr('PHP Version %s already exists', $versionName))
				);
			} else {
				_phpSwitcher_sendJsonResponse(
					500, array('message' => tr('An unexpected error occured: %s', true, $e->getMessage()))
				);
			}
		}
	}

	_phpSwitcher_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Edit PHP version
 *
 * @return void
 */
function phpSwitcher_edit()
{
	if (
		isset($_POST['version_id']) && isset($_POST['version_name']) && isset($_POST['version_binary_path']) &&
		isset($_POST['version_confdir_path'])
	) {
		$versionId = intval($_POST['version_id']);
		$versionName = clean_input($_POST['version_name']);
		$versionBinaryPath = clean_input($_POST['version_binary_path']);
		$versionConfdirPath = clean_input($_POST['version_confdir_path']);

		if ($versionBinaryPath == '' || $versionConfdirPath == '') {
			_phpSwitcher_sendJsonResponse(400, array('message' => tr('All fields are required.')));
		} elseif (strtolower($versionName) == 'php' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION) {
			_phpSwitcher_sendJsonResponse(
				400,
				array('message' => tr('PHP Version %s already exists. This is the default PHP version.', $versionName))
			);
		}

		$db = iMSCP_Database::getRawInstance();

		try {
			$db->beginTransaction();

			$stmt = exec_query(
				'
					UPDATE
						php_switcher_version
					SET
						version_name = ?, version_binary_path = ?, version_confdir_path = ?
					WHERE
						version_id = ?
				',
				array($versionName, $versionBinaryPath, $versionConfdirPath, $versionId)
			);

			if ($stmt->rowCount()) {
				$sendRequest = false;
				$stmt = exec_query('SELECT admin_id FROM php_switcher_version_admin WHERE version_id = ?', $versionId);

				if ($stmt->rowCount()) {
					_phpSwitcher_scheduleDomainsChange($stmt->fetchAll(PDO::FETCH_COLUMN));
					$sendRequest = true;
				}

				$db->commit();

				if ($sendRequest) {
					send_request();
				}
			}

			_phpSwitcher_sendJsonResponse(200, array('message' => tr('PHP Version successfully updated.')));
		} catch (iMSCP_Exception_Database $e) {
			$db->rollBack();

			if ($e->getCode() == '23000') {
				_phpSwitcher_sendJsonResponse(
					400, array('message' => tr('PHP Version %s already exists', $versionName))
				);
			} else {
				_phpSwitcher_sendJsonResponse(
					500, array('message' => tr('An unexpected error occured: %s', true, $e->getMessage()))
				);
			}
		}
	}

	_phpSwitcher_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Delete PHP version
 *
 * @return void
 */
function phpSwitcher_delete()
{
	if (isset($_POST['version_id']) && isset($_POST['version_name'])) {
		$versionId = intval(clean_input($_POST['version_id']));
		$versionName = clean_input($_POST['version_name']);

		$db = iMSCP_Database::getRawInstance();

		try {
			$db->beginTransaction();

			$sendRequest = false;
			$stmt = exec_query('SELECT admin_id FROM php_switcher_version_admin WHERE version_id = ?', $versionId);

			if($stmt->rowCount()) {
				_phpSwitcher_scheduleDomainsChange($stmt->fetchAll(PDO::FETCH_COLUMN));
				$sendRequest = true;
			}

			$stmt = exec_query('DELETE FROM php_switcher_version WHERE version_id = ?', $versionId);

			if ($stmt->rowCount()) {
				$db->commit();

				if ($sendRequest) {
					send_request();
				}

				_phpSwitcher_sendJsonResponse(
					200, array('message' => tr('PHP Version %s successfully deleted.', $versionName))
				);
			}
		} catch (iMSCP_Exception_Database $e) {
			$db->rollBack();
			_phpSwitcher_sendJsonResponse(
				500, array('message' => tr('An unexpected error occured: %s', true, $e->getMessage()))
			);
		}
	}

	_phpSwitcher_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Get table data
 *
 * @return void
 */
function phpSwitcher_getTable()
{
	try {
		$columns = array('version_id', 'version_name');
		$nbColumns = count($columns);

		$indexColumn = 'version_id';

		/* DB table to use */
		$table = 'php_switcher_version';

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
		$where = '';

		if ($_REQUEST['sSearch'] != '') {
			$where .= 'WHERE (';

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
			"
				SELECT SQL_CALC_FOUND_ROWS " . str_replace(' , ', ' ', implode(', ', $columns)) . "
				FROM $table $where $order $limit
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

		$trEditTooltip = tr('Edit this PHP version');
		$trDeleteTooltip = tr('Delete this PHP version');

		while ($data = $rResult->fetchRow(PDO::FETCH_ASSOC)) {
			$row = array();

			for ($i = 0; $i < $nbColumns; $i++) {
				$row[$columns[$i]] = $data[$columns[$i]];
			}

			$row['actions'] =
				"<span title=\"$trEditTooltip\" data-action=\"edit\" " .
				"data-version-id=\"{$data['version_id']}\" data-version-name=\"{$data['version_name']}\" " .
				"class=\"icon i_edit clickable\">&nbsp;</span> "
				.
				"<span title=\"$trDeleteTooltip\" data-action=\"delete\" " .
				"data-version-id=\"{$data['version_id']}\" data-version-name=\"{$data['version_name']}\" " .
				"class=\"icon i_close clickable\">&nbsp;</span>";

			$output['aaData'][] = $row;
		}

		_phpSwitcher_sendJsonResponse(200, $output);
	} catch (iMSCP_Exception_Database $e) {
		_phpSwitcher_sendJsonResponse(
			500, array('message' => tr('An unexpected error occured: %s', true, $e->getMessage()))
		);
	}

	_phpSwitcher_sendJsonResponse(400, tr('Bad request.'));
}

/***********************************************************************************************************************
 * Main
 */

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

if (isset($_REQUEST['action'])) {
	if (is_xhr()) {
		switch (clean_input($_REQUEST['action'])) {
			case 'table':
				phpSwitcher_getTable();
				break;
			case 'add':
				phpSwitcher_add();
				break;
			case 'get':
				phpSwitcher_get();
				break;
			case 'edit':
				phpSwitcher_edit();
				break;
			case 'delete':
				phpSwitcher_delete();
				break;
			default:
				_phpSwitcher_sendJsonResponse(400, array('message' => tr('Bad request.')));
		}
	}

	showBadRequestErrorPage();
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => '../../plugins/PhpSwitcher/themes/default/view/admin/page.tpl',
		'page_message' => 'layout'
	)
);

$tpl->assign(
	array(
		'THEME_CHARSET' => tr('encoding'),
		'TR_PAGE_TITLE' => tr('Admin / Settings / PHP Switcher'),
		'ISP_LOGO' => layout_getUserLogo(),
		'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations(),
		'TR_ID' => tr('Id'),
		'TR_NAME' => tr('Name'),
		'TR_ACTIONS' => tr('Actions'),
		'TR_BINARY_PATH' => tr('PHP binary path'),
		'TR_CONFDIR_PATH' => tr('PHP configuration directory path'),
		'TR_PROCESSING_DATA' => tr('Processing...'),
		'TR_NEW_PHP_VERSION' => tr('New PHP Version'),
		'TR_REQUEST_TIMEOUT' => json_encode(tr('Request Timeout: The server took too long to send the data.', true)),
		'TR_REQUEST_ERROR' => json_encode(tr("An unexpected error occurred.", true)),
		'TR_UNKNOWN_ACTION' => tojs(tr('Unknown Action', true)),
		'TR_NEW' => tojs(tr('New PHP Version', true)),
		'TR_EDIT' => tojs(tr('Edit %%s PHP Version', true)),
		'TR_SAVE' => tojs(tr('Save', true)),
		'TR_CANCEL' => tojs(tr('Cancel', true)),
		'TR_DELETE_CONFIRM' => tojs(tr('Are you sure you want to delete this PHP version? All configuration files for domains which belong to this PHP version will be scheduled for regeneration.', true))
	)
);

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
