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

namespace Cronjobs;

use Cronjobs\Exception\CronjobException;
use Cronjobs\Utils\Cronjob;
use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventsAggregator;
use iMSCP_Exception_Database as DatabaseException;
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
 * Add/Update cron job
 *
 * @return void
 */
function addCronJob()
{
	if (
		isset($_POST['cron_job_id']) && isset($_POST['cron_job_minute']) && isset($_POST['cron_job_hour']) &&
		isset($_POST['cron_job_dmonth']) && isset($_POST['cron_job_month']) && isset($_POST['cron_job_dweek']) &&
		isset($_POST['cron_job_user']) && isset($_POST['cron_job_command']) && isset($_POST['cron_job_type'])
	) {
		$cronjobId = clean_input($_POST['cron_job_id']);
		$cronjobMinute = clean_input($_POST['cron_job_minute']);
		$cronjobHour = clean_input($_POST['cron_job_hour']);
		$cronjobDmonth = clean_input($_POST['cron_job_dmonth']);
		$cronjobMonth = clean_input($_POST['cron_job_month']);
		$cronjobDweek = clean_input($_POST['cron_job_dweek']);
		$cronjobUser = clean_input($_POST['cron_job_user']);
		$cronjobCommand = clean_input($_POST['cron_job_command']);
		$cronjobType = clean_input($_POST['cron_job_type']);

		try {
			Cronjob::validate(
				$cronjobMinute, $cronjobHour, $cronjobDmonth, $cronjobMonth, $cronjobDweek, $cronjobUser,
				$cronjobCommand, $cronjobType
			);

			if ($cronjobId) { // New cronjob
				exec_query(
					'
						INSERT INTO cron_jobs (
							cron_job_minute, cron_job_hour, cron_job_dmonth, cron_job_month, cron_job_dweek,
							cron_job_user, cron_job_command, cron_job_type, cron_job_status
						) VALUES(
						)
					',
					array(
						$cronjobMinute, $cronjobHour, $cronjobDmonth, $cronjobMonth, $cronjobDweek, $cronjobUser,
						$cronjobCommand, $cronjobType, 'toadd'
					)
				);

				send_request();

				write_log(
					sprintf('CronJobs: New cronjob has been added by %s', $_SESSION['user_logged']),
					E_USER_NOTICE
				);

				_sendJsonResponse(200, array('message' => tr('Cronjob has been scheduled for addition.')));
			} else { // cronjob update
				$stmt = exec_query(
					'
						UPDATE
							cron_jobs
						SET
							cron_job_minute = ?, cron_job_hour = ?, cron_job_dmonth = ?, cron_job_month = ?,
							cron_job_dweek = ?, cron_job_user = ?, cron_job_command = ?, cron_job_type = ?,
							cron_job_status = ?
						WHERE
							cron_job_id = ?
						AND
							cron_job_status = ?
					',
					array(
						$cronjobMinute, $cronjobHour, $cronjobDmonth, $cronjobMonth, $cronjobDweek, $cronjobUser,
						$cronjobCommand, $cronjobType, 'tochange', $cronjobId, 'ok'
					)
				);

				if ($stmt->rowCount()) {
					send_request();

					write_log(
						sprintf(
							'CronJobs: Cronjob with ID %s has been updated by %s', $cronjobId, $_SESSION['user_logged']
						),
						E_USER_NOTICE
					);

					_sendJsonResponse(
						200, array('message' => tr('Cronjob with ID %s has been scheduled for update.', $cronjobId))
					);
				}
			}

		} catch (CronjobException $e) {
			_sendJsonResponse(400, array('message' => tr('Unable to add/update cronjob: %s' , $e->getMessage())));
		}
	}

	_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Get cron job
 *
 * @return void
 */
function getCronJob()
{
	if (isset($_GET['cron_job_id'])) {
		$cronJobId = intval($_GET['cron_job_id']);

		try {
			$stmt = exec_query(
				'
					SELECT
						cron_job_id, cron_job_minute, cron_job_hour, cron_job_dmonth, cron_job_month, cron_job_dweek,
						cron_job_user, cron_job_command, cron_job_type
					FROM
						cron_jobs
					WHERE
						cron_job_id = ?
					AND
						cron_job_admin_id = ?
					AND
						cron_job_status = ?
				',
				array($cronJobId, intval($_SESSION['user_id']), 'ok')
			);

			if ($stmt->rowCount()) {
				$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
				_sendJsonResponse(200, $row);
			}

			_sendJsonResponse(404, array('message' => tr('Cronjob with ID %s not found.', $cronJobId)));
		} catch (DatabaseException $e) {
			write_log(
				sprintf('CronJobs: Unable to get cronjob with ID %s: %s', $cronJobId,  $e->getMessage()), E_USER_ERROR
			);

			_sendJsonResponse(
				500, array('message' => tr('An unexpected error occurred: %s', true, $e->getMessage()))
			);
		}
	}

	_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Delete cron job
 *
 * @return void
 */
function deleteCronJob()
{
	if (isset($_POST['cron_job_id'])) {
		$cronJobId = intval($_POST['cron_job_id']);

		try {
			$stmt = exec_query(
				'UPDATE cron_jobs SET cron_job_status = ? WHERE cron_job_id = ? AND cron_job_status = ?',
				array('todelete', $cronJobId, 'ok')
			);

			if ($stmt->rowCount()) {
				send_request();

				write_log(
					sprintf(
						'CronJobs: Cronjob with ID %s has been scheduled for deletion by %s',
						$_SESSION['user_logged'],
						$cronJobId
					),
					E_USER_NOTICE
				);

				_sendJsonResponse(
					200, array('message' => tr('Cronjob with ID %s has been scheduled for deletion', $cronJobId))
				);
			}
		} catch (DatabaseException $e) {
			write_log(
				sprintf('CronJobs: Unable to delete cronjob with ID %s: %s', $cronJobId, $e->getMessage()),
				E_USER_ERROR
			);

			_sendJsonResponse(
				500, array('message' => tr('An unexpected error occurred: %s', true, $e->getMessage()))
			);
		}
	}

	_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Get cron jobs list
 *
 * @return void
 */
function getCronJobsList()
{
	try {
		$columns = array(
			'cron_job_id', 'cron_job_type', 'cron_job_timedate', 'cron_job_user', 'cron_job_command', 'cron_job_status'
		);

		$nbColumns = count($columns);

		$indexColumn = 'cron_job_id';

		/* DB table to use */
		$table = 'cron_jobs';

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
		$where = "WHERE admin_type = 'admin'";

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
			"
				SELECT
					SQL_CALC_FOUND_ROWS cron_job_id, cron_job_type, cron_job_user, cron_job_command, cron_job_status,
					CONCAT(
						cron_job_minute, ' ', cron_job_hour, ' ', cron_job_dmonth, ' ', cron_job_month, ' ',
						cron_job_dweek
					) AS cron_job_timedate
				FROM
					$table
				INNER JOIN
					admin ON(admin_id = cron_job_admin_id)
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
					admin ON(admin_id = cron_job_admin_id)
				WHERE
					admin_type = 'admin'
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

		$trEditTooltip = tr('Edit cron job');
		$trDeleteTooltip = tr('Delete cron job');

		while ($data = $rResult->fetchRow(PDO::FETCH_ASSOC)) {
			$row = array();

			for ($i = 0; $i < $nbColumns; $i++) {
				if ($columns[$i] == 'cron_job_type') {
					$row[$columns[$i]] = tohtml(ucfirst($data[$columns[$i]]));
				} elseif ($columns[$i] == 'cron_job_status') {
					$row[$columns[$i]] = translate_dmn_status($data[$columns[$i]]);
				} else {
					$row[$columns[$i]] = tohtml($data[$columns[$i]]);
				}
			}

			$row['cron_job_timedate'] = tohtml($data['cron_job_timedate']);

			if ($data['cron_job_status'] == 'ok') {
				$row['cron_job_actions'] =
					"<span title=\"$trEditTooltip\" data-action=\"edit_cron_job\" " .
					"data-cron-job-id=\"{$data['cron_job_id']}\" " .
					"class=\"icon icon_edit clickable\">&nbsp;</span> "
					.
					"<span title=\"$trDeleteTooltip\" data-action=\"delete_cron_job\" " .
					"data-cron-job-id=\"{$data['cron_job_id']}\" " .
					"class=\"icon icon_delete clickable\">&nbsp;</span>";
			} else {
				$row['cron_job_actions'] = tr('n/a');
			}

			$output['aaData'][] = $row;
		}

		_sendJsonResponse(200, $output);
	} catch (DatabaseException $e) {
		write_log(sprintf('CronJobs: Unable to get cronjobs list: %s', $e->getMessage()), E_USER_ERROR);

		_sendJsonResponse(500, array('message' => tr('An unexpected error occurred: %s', true, $e->getMessage())));
	}

	_sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/***********************************************************************************************************************
 * Main
 */

EventsAggregator::getInstance()->dispatch(Events::onAdminScriptStart);

check_login('admin');

if (isset($_REQUEST['action'])) {
	if (is_xhr()) {
		$action = clean_input($_REQUEST['action']);

		switch ($action) {
			case 'get_cron_jobs_list':
				getCronJobsList();
				break;
			case 'add_cron_job':
				addCronJob();
				break;
			case 'get_cron_job':
				getCronJob();
				break;
			case 'delete_cron_job':
				deleteCronJob();
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
		'page' => '../../plugins/CronJobs/themes/default/view/admin/cron_jobs.tpl',
		'page_message' => 'layout',
		'cron_job_jailed' => 'page'
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
		'TR_PAGE_TITLE' => tr('Admin / System tools / Cron Jobs'),
		'ISP_LOGO' => layout_getUserLogo(),
		'CRONJOBS_ASSET_VERSION' => $assetVersion,
		'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations()
	)
);

/** @var PluginManager $pluginManager */
$pluginManager = Registry::get('pluginManager');
if ($pluginManager->isPluginKnown('InstantSSH')) {
	$info = $pluginManager->getPluginInfo('InstantSSH');

	if (!$pluginManager->isPluginEnabled('InstantSSH') || version_compare($info['version'], '2.0.2', '<')) {
		$tpl->assign('CRON_JOB_JAILED', '');
	}
} else {
	$tpl->assign('CRON_JOB_JAILED', '');
}

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

EventsAggregator::getInstance()->dispatch(Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
