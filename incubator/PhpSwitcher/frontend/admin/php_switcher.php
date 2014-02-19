<?php
/**
 * i-MSCP TemplateEditor plugin
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
		$versionId = clean_input($_REQUEST['version_id']);
		$versionName = clean_input($_REQUEST['version_name']);

		try {
			$stmt = exec_query('SELECT * FROM php_switcher_version WHERE version_id = ?', $versionId);

			if ($stmt->rowCount()) {
				_templateEditor_sendJsonResponse(200, $stmt->fetchRow(PDO::FETCH_ASSOC));
			}

			_templateEditor_sendJsonResponse(
				404, array('message' => tr('PHP Version %s has not been found.', $versionName))
			);
		} catch (iMSCP_Exception_Database $e) {
			_templateEditor_sendJsonResponse(
				500, array('message' => tr('An unexpected error occured: %s', true, $e->getMessage()))
			);
		}
	}

	_templateEditor_sendJsonResponse(400, array('message' => tr('Bad request.')));
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

			_templateEditor_sendJsonResponse(
				200, array('message' => tr('PHP Version %s successfully created.', $versionName))
			);
		} catch (iMSCP_Exception_Database $e) {
			if ($e->getCode() == '23000') {
				_templateEditor_sendJsonResponse(
					400, array('message' => tr('PHP Version %s already exists', $versionName))
				);
			} else {
				_templateEditor_sendJsonResponse(
					500, array('message' => tr('An unexpected error occured: %s', true, $e->getMessage()))
				);
			}
		}
	}

	_templateEditor_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Edit PHP version
 *
 * @return void
 */
function phpSwitcher_edit()
{
	if (
		isset($_POST['version_id']) && $_POST['version_name'] && isset($_POST['version_binary_path']) &&
		isset($_POST['version_confdir_path'])
	) {
		$versionId = clean_input($_POST['version_id']);
		$versionName = clean_input($_POST['version_name']);
		$versionBinaryPath = clean_input($_POST['version_binary_path']);
		$versionConfdirPath = clean_input($_POST['version_confdir_path']);

		try {

			$stmt = exec_query(
				'
					UPDATE
						php_switcher_version
					SET
						version_binary_path = ?, version_confdir_path = ?
					WHERE
						version_id = ?
				',
				array($versionBinaryPath, $versionConfdirPath, $versionId)
			);

			if ($stmt->rowCount()) {
				_templateEditor_sendJsonResponse(
					200, array('message' => tr('PHP Version %s successfully updated.', $versionName))
				);
			}
		} catch (iMSCP_Exception_Database $e) {
			iMSCP_Database::getRawInstance()->rollBack();
			_templateEditor_sendJsonResponse(
				500, array('message' => tr('An unexpected error occured: %s', true, $e->getMessage()))
			);
		}
	}

	_templateEditor_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Delete PHP version
 *
 * @return void
 */
function phpSwitcher_delete()
{
	if (isset($_POST['version_id']) && isset($_POST['version_name'])) {
		$versionId = clean_input($_POST['version_id']);
		$versionName = clean_input($_POST['version_name']);

		try {
			$stmt = exec_query('DELETE FROM php_switcher_version WHERE version_id = ?', $versionId);

			if ($stmt->rowCount()) {
				_templateEditor_sendJsonResponse(
					200, array('message' => tr('PHP Version %s successfully deleted.', $versionName))
				);
			}
		} catch (iMSCP_Exception_Database $e) {
			_templateEditor_sendJsonResponse(
				500, array('message' => tr('An unexpected error occured: %s', true, $e->getMessage()))
			);
		}
	}

	_templateEditor_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Get Table data
 *
 * @return void
 */
function phpSwitcher_getTable()
{
	try {
		$aColumns = array('version_id', 'version_name');
		$nbAColumns = count($aColumns);

		$sIndexColumn = 'version_id';

		/* DB table to use */
		$sTable = 'php_switcher_version';

		/* Paging */
		$sLimit = '';

		if (isset($_GET['iDisplayStart']) && $_GET['iDisplayLength'] != '-1') {
			$sLimit = 'LIMIT ' . intval($_GET['iDisplayStart']) . ', ' . intval($_GET['iDisplayLength']);
		}

		/* Ordering */
		$sOrder = '';

		if (isset($_GET['iSortCol_0'])) {
			$sOrder = 'ORDER BY ';

			for ($i = 0; $i < intval($_GET['iSortingCols']); $i++) {
				if ($_GET['bSortable_' . intval($_GET['iSortCol_' . $i])] == 'true') {
					$sOrder .= $aColumns[intval($_GET['iSortCol_' . $i])] . ' ' . $_GET['sSortDir_' . $i] . ', ';
				}
			}

			$sOrder = substr_replace($sOrder, '', -2);

			if ($sOrder == 'ORDER BY') {
				$sOrder = '';
			}
		}

		/* Filtering */
		$sWhere = '';

		if ($_REQUEST['sSearch'] != '') {
			$sWhere .= ' AND (';

			for ($i = 0; $i < $nbAColumns; $i++) {
				$sWhere .= $aColumns[$i] . ' LIKE ' . quoteValue("%{$_GET['sSearch']}%") . ' OR ';
			}

			$sWhere = substr_replace($sWhere, '', -3);
			$sWhere .= ')';
		}

		/* Individual column filtering */
		for ($i = 0; $i < $nbAColumns; $i++) {
			if (isset($_GET["bSearchable_$i"]) && $_GET["bSearchable_$i"] == 'true' && $_GET["sSearch_$i"] != '') {
				$sWhere .= "AND {$aColumns[$i]} LIKE " . quoteValue("%{$_GET["sSearch_$i"]}%");
			}
		}

		/* Get data to display */
		$rResult = execute_query(
			"
				SELECT SQL_CALC_FOUND_ROWS " . str_replace(' , ', ' ', implode(', ', $aColumns)) . "
				FROM $sTable $sWhere $sOrder $sLimit
			"
		);

		/* Data set length after filtering */
		$rResultFilterTotal = execute_query('SELECT FOUND_ROWS()');
		$aResultFilterTotal = $rResultFilterTotal->fetchRow(PDO::FETCH_NUM);
		$iFilteredTotal = $aResultFilterTotal[0];

		/* Total data set length */
		$rResultTotal = execute_query("SELECT COUNT($sIndexColumn) FROM $sTable");
		$aResultTotal = $rResultTotal->fetchRow(PDO::FETCH_NUM);
		$iTotal = $aResultTotal[0];

		/* Output */
		$output = array(
			'sEcho' => intval($_GET['sEcho']),
			'iTotalRecords' => $iTotal,
			'iTotalDisplayRecords' => $iFilteredTotal,
			'aaData' => array()
		);

		$trEditTooltip = tr('Edit this PHP version');
		$trDeleteTooltip = tr('Delete this PHP version');

		while ($aRow = $rResult->fetchRow(PDO::FETCH_ASSOC)) {
			$row = array();

			for ($i = 0; $i < $nbAColumns; $i++) {
				$row[$aColumns[$i]] = $aRow[$aColumns[$i]];
			}

			$row['actions'] =
				"<span title=\"$trEditTooltip\" data-action=\"edit\" " .
				"data-version-id=\"{$aRow['version_id']}\" data-version-name=\"{$aRow['version_name']}\" " .
				"class=\"icon i_edit clickable\">&nbsp;</span> "
				.
				"<span title=\"$trDeleteTooltip\" data-action=\"delete\" " .
				"data-version-id=\"{$aRow['version_id']}\" data-version-name=\"{$aRow['version_name']}\" " .
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

		'TR_ID' => tr('ID'),
		'TR_NAME' => tr('Name'),
		'TR_ACTIONS' => tr('Actions'),

		'TR_PROCESSING_DATA' => tr('Processing...'),

		'TR_REQUEST_TIMEOUT' => json_encode(tr('Request Timeout: The server took too long to send the data.', true)),
		'TR_REQUEST_ERROR' => json_encode(tr("An unexpected error occurred.", true)),
		'TR_UNKNOWN_ACTION' => tojs(tr('Unknown Action', true)),

		'TR_NEW' => tojs(tr('New PHP Version', true)),
		'TR_EDIT' => tojs(tr('Edit %%s PHP Version', true)),
		'TR_SAVE' => tojs(tr('Save', true)),
		'TR_CANCEL' => tojs(tr('Cancel', true)),
		'TR_DELETE_CONFIRM' => tojs(tr('Are you sure you want to delete this PHP version?', true))
	)

);

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
