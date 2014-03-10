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
function _templateEditor_sendJsonResponse($statusCode = 200, array $data = array())
{
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-type: application/json');

	switch($statusCode) {
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
 * Generate page
 *
 * @param iMSCP_pTemplate $tpl
 * @return void
 */
function template_editorGeneratePage($tpl)
{
	$query = 'SELECT service_name FROM template_editor_templates GROUP BY service_name';
	$stmt = execute_query($query);

	if($stmt->rowCount()) {
		$serviceNames = ($stmt->fetchAll(PDO::FETCH_COLUMN));
		natsort($serviceNames);

		$selected = false;

		foreach($serviceNames as $serviceName) {
			$tpl->assign(
				array(
					'SERVICE_NAME' => $serviceName,
					'SHOW_SERVICE_NAME' => str_replace('_', ' ', $serviceName),
					'SELECTED' => ($selected) ? '' : ' selected="selected"'
				)
			);

			$selected = true;
			$tpl->parse('SERVICE_NAME_OPTION', '.service_name_option');
		}

		$tpl->assign('DSELECTED', '');
	} else {
		$tpl->assign(
			array(
				'DSELECTED' => ' selected="selected"',
				'SERVICE_NAME_OPTION' => ''
			)
		);
	}
}


/**
 * Get admins templates
 *
 * @return void
 */
function templateEditor_getAdminsTemplates()
{
	if(isset($_GET['id'])) {
		$templateId = intval($_GET['id']);
		$temlate = array(
			'id' => $templateId,
		);

		$stmt = exec_query(
			'
				SELECT
					admin_id, admin_name, IFNULL(template_id, 0) as template_id
				FROM
					admin
				LEFT JOIN
					template_editor_admins_templates USING(admin_id)
				WHERE
					admin_type = ?
				AND
					admin_status = ?
				AND
					(template_id = ? OR template_id IS NULL)
			',
			array('reseller', 'ok', $templateId)
		);

		if($stmt->rowCount()) {
			$temlate['admins_templates'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
			_templateEditor_sendJsonResponse(200, $temlate);
		}
	}

	_templateEditor_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Set admin templates
 *
 * @return void
 */
function template_editor_setAdminsTemplates()
{
	_templateEditor_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Get datatable
 *
 * @return void
 */
function templateEditor_getDatatable()
{
	if (isset($_GET['service_name'])) {
		try {
			$columns = array('id', 'name', 'scope', 'status');
			$nbColumns = count($columns);

			$serviceName =clean_input( $_GET['service_name']);
			$indexColumn = 'id';

			/* DB table to use */
			$table = 'template_editor_templates';

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
						$order .= 't1.' . $columns[intval($_GET['iSortCol_' . $i])] . ' ' . $_GET['sSortDir_' . $i] . ', ';
					}
				}

				$order = substr_replace($order, '', -2);

				if ($order == 'ORDER BY') {
					$order = '';
				}
			}

			/* Filtering */
			$where = 'WHERE t1.service_name = ' . quoteValue($serviceName);

			if ($_GET['sSearch'] != '') {
				$where .= ' AND (';

				for ($i = 0; $i < $nbColumns; $i++) {
					$where .=  "t1.{$columns[$i]} LIKE " . quoteValue("%{$_GET['sSearch']}%") . ' OR ';
				}

				$where = substr_replace($where, '', -3);
				$where .= ')';
			}

			/* Individual column filtering */
			for ($i = 0; $i < $nbColumns; $i++) {
				if (isset($_GET["bSearchable_$i"]) && $_GET["bSearchable_$i"] == 'true' && $_GET["sSearch_$i"] != '') {
					$where .= "AND t1.{$columns[$i]} LIKE " . quoteValue("%{$_GET["sSearch_$i"]}%");
				}
			}

			/* Get data to display */
			$rResult = execute_query(
				"
					SELECT
						SQL_CALC_FOUND_ROWS " . 't1.' . implode(', t1.', $columns) . ", t2.name AS parent_name
					FROM
						$table AS t1
					LEFT JOIN
						$table AS t2 ON(t2.id = t1.parent_id)
					$where
						GROUP BY t1.name $order $limit
				"
			);

			/* Data set length after filtering */
			$stmt = execute_query('SELECT FOUND_ROWS()');
			$resultFilterTotal = $stmt->fetchRow(PDO::FETCH_NUM);
			$filteredTotal = $resultFilterTotal[0];

			/* Total data set length */
			$stmt = exec_query("SELECT COUNT($indexColumn) FROM $table WHERE service_name = ?", $serviceName);
			$resultTotal = $stmt->fetchRow(PDO::FETCH_NUM);
			$total = $resultTotal[0];

			/* Output */
			$output = array(
				'sEcho' => intval($_GET['sEcho']),
				'iTotalRecords' => $total,
				'iTotalDisplayRecords' => $filteredTotal,
				'aaData' => array()
			);

			$trNewTooltip = tr('Create new version from this template');
			$trEditTooltip = tr('Edit this template');
			$trDeleteTooltip = tr('Delete this template');
			$trSyncTooltip = tr("Synchronize the system with this template");
			$trSyncInProgressTooltip = tr('Synchronization in progress...');
			$trErrorTooltip = tr('Go to the debugger interface for more details...');
			$trManageAssignment = tr('Template assignment');

			while ($data = $rResult->fetchRow(PDO::FETCH_ASSOC)) {
				$row = array();

				for ($i = 0; $i < $nbColumns; $i++) {
					if ($columns[$i] == 'status') {
						switch ($data[$columns[$i]]) {
							case 'ok':
								$row[$columns[$i]] = "<span class=\"icon icon_ok\">&nbsp;</span>";
								break;
							case 'sync':
								$row[$columns[$i]] =
									"<span title=\"$trSyncInProgressTooltip\" class=\"icon icon_refresh\">&nbsp;</span>";
								break;
							default:
								$row[$columns[$i]] =
									"<span title=\"$trErrorTooltip\" class=\"icon icone_error\">&nbsp;</span>";
						}
					} elseif($columns[$i] == 'name') {
						$row[$columns[$i]] = tohtml($data[$columns[$i]]);
					} else {
						$row[$columns[$i]] = tohtml($data[$columns[$i]]);
					}
				}

				$row['parent_name'] = (!is_null($data['parent_name'])) ? tohtml($data['parent_name']) : tr('n/a');

				$row['actions'] =
					"<span title=\"$trNewTooltip\" data-action=\"create_template\" data-id=\"{$data['id']}\" " .
					"class=\"icon icon_add clickable\">&nbsp;</span> " .
					(
						(!is_null($data['parent_name']))
							? "<span title=\"$trEditTooltip\" data-action=\"edit_template\" " .
								"data-id=\"{$data['id']}\" class=\"icon icon_edit clickable\">&nbsp;</span> "
							: ''
					) . (
						(!is_null($data['parent_name']) && $data['status'] == 'ok')
							? "<span title=\"$trSyncTooltip\" data-action=\"sync_template\" " .
								"data-id=\"{$data['id']}\" " .
							"class=\"icon icon_refresh clickable\">&nbsp;</span>"
							: ''
					) . (
							(!is_null($data['parent_name']))
								? "<span title=\"$trDeleteTooltip\" data-action=\"delete_template\" " .
									"data-id=\"{$data['id']}\" class=\"icon icon_delete clickable\">&nbsp;</span>"
								: ''
					) . (
							(!is_null($data['parent_name']) && systemHasResellers())
								? "<span title=\"$trManageAssignment\" data-action=\"set_admins_templates\" " .
									"data-id=\"{$data['id']}\" class=\"icon icon_assign clickable\">&nbsp;</span> "
								: ''
					);

				$row['service_name'] = '';
				$output['aaData'][] = $row;
			}

			_templateEditor_sendJsonResponse(200, $output);
		} catch (iMSCP_Exception_Database $e) {
			_templateEditor_sendJsonResponse(
				500, array('message' => tr('An unexpected error occured: %s', true, $e->getMessage()))
			);
		}
	}

	_templateEditor_sendJsonResponse(400, tr('Bad request.'));
}

/**
 * Create template
 *
 * @return void
 */
function templateEditor_create()
{
	if(isset($_POST['id']) && isset($_POST['name']) && isset($_POST['files'])) {
		$templateName = clean_input($_POST['name']);
		$templateId = intval($_POST['id']);

		if(preg_match('/^[a-z0-9\s_-]+$/i', $templateName)) {
			$db = iMSCP_Database::getRawInstance();

			try {
				$db->beginTransaction();

				$stmt = exec_query(
					'
						INSERT INTO template_editor_templates (
							parent_id, name, service_name, is_default, scope
						) SELECT
							IFNULL(parent_id, id), ?, service_name, ?, scope
						FROM
							template_editor_templates
						WHERE
							id = ?
					',
					array($templateName, 0, $templateId)
				);

				if ($stmt->rowCount()) {
					$templateId = $db->lastInsertId();

					foreach ((array)$_POST['files'] as $fileName => $fileContent) {
						$fileName = clean_input($fileName);
						$fileContent = clean_input($fileContent);

						exec_query(
							'INSERT INTO template_editor_files (template_id, name, content) VALUES(?, ?, ?)',
							array($templateId, $fileName, $fileContent)
						);
					}

					$db->commit();

					_templateEditor_sendJsonResponse(200, array('message' => tr('Template successfully created.')));
				}
			} catch (iMSCP_Exception_Database $e) {
				iMSCP_Database::getRawInstance()->rollBack();

				if($e->getCode() === 23000) {
					_templateEditor_sendJsonResponse(
						400, array('message' => tr('Template with same name already exists.'))
					);
				}

				_templateEditor_sendJsonResponse(500, array('message' => tr('An unexpected error occured.')));
			}
		} else {
			_templateEditor_sendJsonResponse(
				400, array('message' => tr('Template name is not valid.'))
			);
		}
	}

	_templateEditor_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Read template
 *
 * @return void
 */
function templateEditor_get()
{
	if(isset($_GET['id'])) {
		$templateId =  intval($_GET['id']);

		$stmt = exec_query('SELECT * FROM template_editor_templates WHERE id = ?', $templateId);

		if($stmt->rowCount()) {
			$temlate = $stmt->fetchRow(PDO::FETCH_ASSOC);

			$stmt = exec_query('SELECT * FROM template_editor_files WHERE template_id = ?', $templateId);

			if($stmt->rowCount()) {
				$files = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$temlate['files'] = $files;

				_templateEditor_sendJsonResponse(200, $temlate);
			}

			_templateEditor_sendJsonResponse(404, array('message' => tr('No file found for the template.')));
		} else {
			_templateEditor_sendJsonResponse(404, array('message' => tr('Template has not been found.')));
		}
	}

	_templateEditor_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Edit template
 *
 * @return void
 */
function templateEditor_edit()
{
	if(isset($_POST['id']) && isset($_POST['files'])) {
		$templateId = intval($_POST['id']);

		$db = iMSCP_Database::getRawInstance();

		try {
			$db->beginTransaction();

			foreach((array) $_POST['files'] as $fileName => $fileContent) {
				$fileName = clean_input($fileName);
				$fileContent = clean_input($fileContent);

				$stmt = exec_query(
					'UPDATE template_editor_files SET content = ? WHERE template_id = ? AND name = ?',
					array($templateId, $fileName, $fileContent)
				);

				if(!$stmt->rowCount()) {
					_templateEditor_sendJsonResponse(400, array('message' => tr('Bad request.')));
				}
			}

			$db->commit();

			_templateEditor_sendJsonResponse(200, array('message' => tr('Template successfully edited.')));
		} catch(iMSCP_Exception_Database $e) {
			iMSCP_Database::getRawInstance()->rollBack();
			_templateEditor_sendJsonResponse(500, array('message' => tr('An unexpected error occured.')));
		}
	}

	_templateEditor_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Delete the given template
 *
 * @return void
 */
function templateEditor_delete()
{
	if(isset($_POST['id'])) {
		try {
			$templateId = intval($_POST['id']);

			$stmt = exec_query(
				'DELETE FROM template_editor_templates WHERE id = ? AND parent_id IS NOT NULL', $templateId
			);

			if($stmt->rowCount()) {
				_templateEditor_sendJsonResponse(200, array('message' => tr('Template successfully deleted.')));
			}
		} catch(iMSCP_Exception_Database $e) {
			_templateEditor_sendJsonResponse(
				500, array('message' => tr('An unexpected error occured: %s', true, $e->getMessage()))
			);
		}
	}

	_templateEditor_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Synchronize the system with template
 *
 * @return void
 */
function templateEditor_sync()
{
	// TODO
}

/***********************************************************************************************************************
 * Main
 */

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

if(isset($_REQUEST['action'])) {
	if(is_xhr()) {
		switch(clean_input($_REQUEST['action'])) {
			case 'get_table':
				templateEditor_getDatatable();
				break;
			case 'create_template':
				templateEditor_create();
				break;
			case 'get_template':
				templateEditor_get();
				break;
			case 'edit_template':
				templateEditor_edit();
				break;
			case 'delete_template':
				templateEditor_delete();
				break;
			case 'get_admins_templates':
				templateEditor_getAdminsTemplates();
				break;
			case 'set_admins_templates':
				template_editor_setAdminsTemplates();
				break;
			case 'sync_template':
				templateEditor_sync();
				break;
			case 'sync_default_templates':
				try {
					iMSCP_Registry::get('pluginManager')->getPlugin('TemplateEditor')->syncTemplates(true);
					_templateEditor_sendJsonResponse(
						200, array('message' => tr('Templates were successfully synchronized.'))
					);
				} catch(iMSCP_Exception $e) {
					_templateEditor_sendJsonResponse(500, array('message' => tr('An unexpected error occured.')));
				}
				break;
			default:
				_templateEditor_sendJsonResponse(400, array('message' => tr('Bad request.')));
		}
	}

	showBadRequestErrorPage();
}

if(iMSCP_Registry::get('config')->DEBUG) {
	$assetVersion = time();
} else {
	$pluginInfo = iMSCP_Registry::get('pluginManager')->getPluginInfo('TemplateEditor');
	$assetVersion = strtotime($pluginInfo['date']);
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => '../../plugins/TemplateEditor/themes/default/view/admin/page.tpl',
		'page_message' => 'layout',
		'service_name_option' => 'page'
	)
);

$tpl->assign(
	array(
		'THEME_CHARSET' => tr('encoding'),
		'TR_PAGE_TITLE' => tr('Admin / Settings / Template Editor'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TEMPLATE_EDITOR_ASSET_VERSION' => $assetVersion,
		'TR_HINT' => tr('This interface allows creation of customized and persistent versions of service templates which are used by i-MSCP to generate final configuration files. Only site-wide templates can be assigned to resellers. Other templates act at system-wide. If you delete a template which is already in use, it will be reset back to the default template.'),
		'TR_WARNING' => tr("You must pay attention when you create new template versions or when you edit them. This should be reserved to experts because no validity check is made, meaning that the system can break if you do a mistake. You're warned."),
		'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations(),
		'TR_NAME' => tr('Name'),
		'TR_PARENT' => tr('Parent'),
		'TR_SCOPE' => tr('Scope'),
		'TR_STATUS' => tr('Status'),
		'TR_ACTIONS' => tr('Actions'),
		'TR_AVAILABLE_FOR' => tr('Available For'),
		'TR_SERVICE_NAME' => tr('Service Name'),
		'TR_PROCESSING_DATA' => tr('Processing...'),
		'TR_TEMPLATE_ASSIGNMENT' => tr('Template assignment'),
		'TR_REQUEST_TIMEOUT' => json_encode(tr('Request Timeout: The server took too long to send the data.', true)),
		'TR_REQUEST_ERROR' => json_encode(tr("An unexpected error occurred.", true)),
		'TR_UNKNOWN_ACTION' => tojs(tr('Unknown Action', true)),
		'TR_NEW' => tojs(tr('New template version from: %%s', true)),
		'TR_EDIT' => tojs(tr('Edit %%s template', true)),
		'TR_SAVE' => tojs(tr('Save', true)),
		'TR_CANCEL' => tojs(tr('Cancel', true)),
		'TR_SYNC' => tr('Synchronize templates'),
		'TR_SYNC_TOOLTIP' => tr('Synchronize templates with last versions available in i-MSCP configuration directory.'),
		'TR_DELETE_CONFIRM' => tojs(tr('Are you sure you want to delete this template?', true)),
		'TR_TSYNC_CONFIRM' => tojs(tr("Are you sure you want to synchronize the system with this template? Be aware that the system can break in case the template is not valid. In case of a site wide template, only configuration files from customers to which this template is assigned will be synchronized.", true))
	)
);

generateNavigation($tpl);
generatePageMessage($tpl);
template_editorGeneratePage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
