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

namespace CronJobs\CLient;

use CronJobs\CommonFunctions as Functions;
use CronJobs\Exception\CronjobException;
use CronJobs\Utils\CronjobValidator as CronjobValidator;
use iMSCP_Database as Database;
use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventsAggregator;
use iMSCP_Exception as iMSCPException;
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
 * Add/Update cron job
 *
 * @param array $cronPermissions Customer's cron job permissions
 * @return void
 */
function addCronJob($cronPermissions)
{
	if(
		isset($_POST['cron_job_id']) && isset($_POST['cron_job_notification']) && isset($_POST['cron_job_minute']) &&
		isset($_POST['cron_job_hour']) && isset($_POST['cron_job_dmonth']) && isset($_POST['cron_job_month']) &&
		isset($_POST['cron_job_dweek']) && isset($_POST['cron_job_command']) && isset($_POST['cron_job_type'])
	) {
		$customerId = intval($_SESSION['user_id']);
		$cronjobId = clean_input($_POST['cron_job_id']);
		$cronjobNotification = encode_idna(clean_input($_POST['cron_job_notification']));
		$cronjobMinute = clean_input($_POST['cron_job_minute']);
		$cronjobHour = clean_input($_POST['cron_job_hour']);
		$cronjobDmonth = clean_input($_POST['cron_job_dmonth']);
		$cronjobMonth = clean_input($_POST['cron_job_month']);
		$cronjobDweek = clean_input($_POST['cron_job_dweek']);
		$cronjobCommand = clean_input($_POST['cron_job_command']);
		$cronjobType = clean_input($_POST['cron_job_type']);

		if($cronPermissions['cron_permission_type'] == 'url') {
			$allowedCronjobTypes = array('url');
		} elseif($cronPermissions['cron_permission_type'] == 'jailed') {
			$allowedCronjobTypes = array('url', 'jailed');
		} else {
			$allowedCronjobTypes = array('url', 'full');
		}

		try {
			if(in_array($cronjobType, $allowedCronjobTypes, true)) {
				CronjobValidator::validate(
					$cronjobNotification, $cronjobMinute, $cronjobHour, $cronjobDmonth, $cronjobMonth, $cronjobDweek,
					null, $cronjobCommand, $cronjobType, $cronPermissions['cron_permission_frequency']
				);

				if($cronjobNotification === '') {
					$cronjobNotification = null;
				}

				if(!$cronjobId) { // New cron job
					if(
						$cronPermissions['cron_permission_max'] == 0 ||
						$cronPermissions['cron_permission_cnb_cron_jobs'] < $cronPermissions['cron_permission_max']
					) {
						EventsAggregator::getInstance()->dispatch('onBeforeAddCronJob', array(
							'cron_job_admin_id' => $customerId,
							'cron_job_notification' => $cronjobNotification,
							'cron_job_minute' => $cronjobMinute,
							'cron_job_hour' => $cronjobHour,
							'cron_job_dmonth' => $cronjobDmonth,
							'cron_job_month' => $cronjobMonth,
							'cron_job_dweek' => $cronjobDweek,
							'cron_job_command' => $cronjobCommand,
							'cron_job_type' => $cronjobType
						));

						exec_query(
							'
								INSERT INTO cron_jobs (
									cron_job_permission_id, cron_job_admin_id, cron_job_type, cron_job_notification,
									cron_job_minute, cron_job_hour, cron_job_dmonth, cron_job_month, cron_job_dweek,
									cron_job_user, cron_job_command, cron_job_status
								) SELECT
									?, ?, ?, ?, ?, ?, ?, ?, ?, admin_sys_name, ?, ?
								FROM
									admin
								WHERE
									admin_id = ?
							',
							array(
								$cronPermissions['cron_permission_id'], $customerId, $cronjobType, $cronjobNotification,
								$cronjobMinute, $cronjobHour, $cronjobDmonth, $cronjobMonth, $cronjobDweek, $cronjobCommand,
								'toadd', $customerId
							)
						);

						EventsAggregator::getInstance()->dispatch('onAfterAddCronJob', array(
							'cron_job_admin_id' => $customerId,
							'cron_job_id' => Database::getInstance()->insertId(),
							'cron_job_notification' => $cronjobNotification,
							'cron_job_minute' => $cronjobMinute,
							'cron_job_hour' => $cronjobHour,
							'cron_job_dmonth' => $cronjobDmonth,
							'cron_job_month' => $cronjobMonth,
							'cron_job_dweek' => $cronjobDweek,
							'cron_job_command' => $cronjobCommand,
							'cron_job_type' => $cronjobType
						));

						send_request();

						write_log(
							sprintf('CronJobs: New cron job has been added by %s', $_SESSION['user_logged']),
							E_USER_NOTICE
						);

						Functions::sendJsonResponse(
							200, array('message' => tr('Cron job has been scheduled for addition.', true))
						);
					} else {
						Functions::sendJsonResponse(
							400, array('message' => tr('Your cron jobs limit is reached.', true))
						);
					}
				} else { // Cron job update
					EventsAggregator::getInstance()->dispatch('onBeforeUpdateCronJob', array(
						'cron_job_admin_id' => $customerId,
						'cron_job_id' => $cronjobId,
						'cron_job_notification' => $cronjobNotification,
						'cron_job_minute' => $cronjobMinute,
						'cron_job_hour' => $cronjobHour,
						'cron_job_dmonth' => $cronjobDmonth,
						'cron_job_month' => $cronjobMonth,
						'cron_job_dweek' => $cronjobDweek,
						'cron_job_command' => $cronjobCommand,
						'cron_job_type' => $cronjobType
					));

					$stmt = exec_query(
						'
							UPDATE
								cron_jobs
							SET
								cron_job_type = ?, cron_job_notification = ?, cron_job_minute = ?, cron_job_hour = ?,
								cron_job_dmonth = ?, cron_job_month = ?, cron_job_dweek = ?, cron_job_command = ?,
								cron_job_status = ?
							WHERE
								cron_job_id = ?
							AND
								cron_job_admin_id = ?
							AND
								cron_job_status = ?
						',
						array(
							$cronjobType, $cronjobNotification, $cronjobMinute, $cronjobHour, $cronjobDmonth,
							$cronjobMonth, $cronjobDweek, $cronjobCommand, 'tochange', $cronjobId, $customerId, 'ok'
						)
					);

					EventsAggregator::getInstance()->dispatch('onAfterUpdateCronJob', array(
						'cron_job_admin_id' => $customerId,
						'cron_job_id' => $cronjobId,
						'cron_job_notification' => $cronjobNotification,
						'cron_job_minute' => $cronjobMinute,
						'cron_job_hour' => $cronjobHour,
						'cron_job_dmonth' => $cronjobDmonth,
						'cron_job_month' => $cronjobMonth,
						'cron_job_dweek' => $cronjobDweek,
						'cron_job_command' => $cronjobCommand,
						'cron_job_type' => $cronjobType
					));

					if($stmt->rowCount()) {
						send_request();

						write_log(
							sprintf(
								'CronJobs: Cron job with ID %s has been updated by %s',
								$cronjobId,
								$_SESSION['user_logged']
							),
							E_USER_NOTICE
						);

						Functions::sendJsonResponse(
							200, array('message' => tr('Cron job has been scheduled for update.', true))
						);
					}
				}
			}
		} catch(iMSCPException $e) {
			if($e instanceof CronjobException) {
				Functions::sendJsonResponse(400, array('message' => $e->getMessage()));
			} else {
				write_log(sprintf('CronJobs: Unable to add/update cron job: %s', $e->getMessage()), E_USER_ERROR);
				Functions::sendJsonResponse(
					500, array('message' => tr('An unexpected error occurred. Please contact your reseller.', true))
				);
			}
		}
	}

	Functions::sendJsonResponse(400, array('message' => tr('Bad request.', true)));
}

/**
 * Get cron job
 *
 * @return void
 */
function getCronJob()
{
	if(isset($_GET['cron_job_id'])) {
		$customerId = intval($_SESSION['user_id']);
		$cronJobId = intval($_GET['cron_job_id']);

		try {
			$stmt = exec_query(
				'
					SELECT
						cron_job_id, cron_job_type, cron_job_notification, cron_job_minute, cron_job_hour,
						cron_job_dmonth, cron_job_month, cron_job_dweek, cron_job_user, cron_job_command
					FROM
						cron_jobs
					WHERE
						cron_job_id = ?
					AND
						cron_job_admin_id =?
					AND
						cron_job_status = ?
				',
				array($cronJobId, $customerId, 'ok')
			);

			if($stmt->rowCount()) {
				Functions::sendJsonResponse(200, $stmt->fetchRow(PDO::FETCH_ASSOC));
			}
		} catch(DatabaseException $e) {
			write_log(
				sprintf('CronJobs: Unable to get cron job with ID %s: %s', $cronJobId, $e->getMessage()), E_USER_ERROR
			);

			Functions::sendJsonResponse(
				500, array('message' => tr('An unexpected error occurred. Please contact your reseller.', true))
			);
		}
	}

	Functions::sendJsonResponse(400, array('message' => tr('Bad request.', true)));
}

/**
 * Delete cron job
 *
 * @return void
 */
function deleteCronJob()
{
	if(isset($_POST['cron_job_id'])) {
		$customerId = intval($_SESSION['user_id']);
		$cronJobId = intval($_POST['cron_job_id']);

		try {
			EventsAggregator::getInstance()->dispatch('onBeforeDeleteCronJob', array(
				'cron_job_admin_id' => $customerId,
				'cron_job_id' => $cronJobId
			));

			$stmt = exec_query(
				'
					UPDATE
						cron_jobs
					SET
						cron_job_status = ?
					WHERE
						cron_job_id = ?
					AND
						cron_job_admin_id = ?
					AND
						cron_job_status = ?
				',
				array('todelete', $cronJobId, $customerId, 'ok')
			);

			if($stmt->rowCount()) {
				EventsAggregator::getInstance()->dispatch('onAfterDeleteCronJob', array(
					'cron_job_admin_id' => $customerId,
					'cron_job_id' => $cronJobId
				));

				send_request();

				write_log(
					sprintf(
						'CronJobs: Cron job with ID %s has been scheduled for deletion by %s',
						$cronJobId,
						$_SESSION['user_logged']

					),
					E_USER_NOTICE
				);

				Functions::sendJsonResponse(
					200, array('message' => tr('Cron job has been scheduled for deletion.', true, $cronJobId))
				);
			}
		} catch(DatabaseException $e) {
			write_log(
				sprintf('CronJobs: Unable to delete cron job with ID %s: %s', $cronJobId, $e->getMessage()),
				E_USER_ERROR
			);

			Functions::sendJsonResponse(
				500, array('message' => tr('An unexpected error occurred. Please contact your reseller.', true))
			);
		}
	}

	Functions::sendJsonResponse(400, array('message' => tr('Bad request.', true)));
}

/**
 * Get cron jobs list
 *
 * @return void
 */
function getCronJobsList()
{
	try {
		$customerId = intval($_SESSION['user_id']);

		// Filterable, orderable columns
		$columnDefs = array(
			'cron_job_id' => 'cron_job_id',
			'cron_job_type' => 'cron_job_type',
			'cron_job_timedate' => "
				CONCAT(
					cron_job_minute, ' ', cron_job_hour, ' ', cron_job_dmonth, ' ', cron_job_month, ' ',
					cron_job_dweek
				) AS cron_job_timedate
			",
			'cron_job_user' => 'cron_job_user',
			'cron_job_command' => 'cron_job_command',
			'cron_job_status' => 'cron_job_status',
		);

		$columnNames = array_keys($columnDefs);
		$nbColumns = count($columnDefs);
		$indexColumn = 'cron_job_id';

		/* DB table to use */
		$table = 'cron_jobs';

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

					$order .= $columnNames[intval($_GET['iSortCol_' . $i])] . ' ' . $sortDir . ', ';
				}
			}

			$order = substr_replace($order, '', -2);

			if($order == 'ORDER BY ') {
				$order = '';
			}
		}

		/* Filtering */
		$where = 'WHERE cron_job_admin_id =' . quoteValue($customerId);

		if(isset($_GET['sSearch']) && $_GET['sSearch'] !== '') {
			$where .= 'AND (';

			for($i = 0; $i < $nbColumns; $i++) {
				$where .= $columnNames[$i] . ' LIKE ' . quoteValue('%' . $_GET['sSearch'] . '%') . ' OR ';
			}

			$where = substr_replace($where, '', -3);
			$where .= ')';
		}

		/* Individual column filtering */
		for($i = 0; $i < $nbColumns; $i++) {
			if(isset($_GET['bSearchable_' . $i]) && $_GET['bSearchable_' . $i] === 'true' && $_GET['sSearch_' . $i] !== '') {
				$where .= "AND {$columnNames[$i]} LIKE " . quoteValue('%' . $_GET['sSearch_' . $i] . '%');
			}
		}

		/* Get data to display */
		$rResult = execute_query(
			'
				SELECT
					SQL_CALC_FOUND_ROWS ' . str_replace(' , ', ' ', implode(', ', $columnDefs)) . "
				FROM
					$table
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
				WHERE
					cron_job_admin_id = " . quoteValue($customerId)
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

		$trEditTooltip = tr('Edit cron job', true);
		$trDeleteTooltip = tr('Delete cron job', true);

		while($data = $rResult->fetchRow(PDO::FETCH_ASSOC)) {
			$row = array();

			for($i = 0; $i < $nbColumns; $i++) {
				if($columnNames[$i] == 'cron_job_type') {
					$row[$columnNames[$i]] = ($data[$columnNames[$i]] == 'url')
						? tr('Url', true) : tr('Shell', true);
				} elseif($columnNames[$i] == 'cron_job_status') {
					$row[$columnNames[$i]] = translate_dmn_status($data[$columnNames[$i]], false);
				} else {
					$row[$columnNames[$i]] = $data[$columnNames[$i]];
				}
			}

			$row['cron_job_timedate'] = tohtml($data['cron_job_timedate']);

			if($data['cron_job_status'] == 'ok') {
				$row['cron_job_actions'] =
					"<span title=\"$trEditTooltip\" data-action=\"edit_cronjob\" " .
					"data-cron-job-id=\"" . $data['cron_job_id'] . "\" " .
					"class=\"icon icon_edit clickable\">&nbsp;</span> "
					.
					"<span title=\"$trDeleteTooltip\" data-action=\"delete_cronjob\" " .
					"data-cron-job-id=\"" . $data['cron_job_id'] . "\" " .
					"class=\"icon icon_delete clickable\">&nbsp;</span>";
			} else {
				$row['cron_job_actions'] = tr('n/a', true);
			}

			$output['aaData'][] = $row;
		}

		Functions::sendJsonResponse(200, $output);
	} catch(DatabaseException $e) {
		write_log(sprintf('CronJobs: Unable to get cron jobs list: %s', $e->getMessage()), E_USER_ERROR);

		Functions::sendJsonResponse(
			500, array('message' => tr('An unexpected error occurred. Please contact your reseller.', true))
		);
	}

	Functions::sendJsonResponse(400, array('message' => tr('Bad request.', true)));
}

/***********************************************************************************************************************
 * Main
 */

EventsAggregator::getInstance()->dispatch(Events::onClientScriptStart);

check_login('user');

/** @var PluginManager $pluginManager */
$pluginManager = Registry::get('pluginManager');

/** @var PluginCronJobs $cronjobsPlugin */
$cronjobsPlugin = $pluginManager->getPlugin('CronJobs');

$cronPermissions = $cronjobsPlugin->getCronPermissions(intval($_SESSION['user_id']));
unset($cronjobsPlugin);

if($cronPermissions['cron_permission_id'] !== null) {
	if(isset($_REQUEST['action'])) {
		if(is_xhr()) {
			$action = clean_input($_REQUEST['action']);

			switch($action) {
				case 'get_cronjobs_list':
					getCronJobsList();
					break;
				case 'add_cronjob':
					addCronJob($cronPermissions);
					break;
				case 'get_cronjob':
					getCronJob();
					break;
				case 'delete_cronjob':
					deleteCronJob();
					break;
				default:
					Functions::sendJsonResponse(400, array('message' => tr('Bad request.', true)));
			}
		}

		showBadRequestErrorPage();
	}

	$tpl = new TemplateEngine();
	$tpl->define_dynamic(array(
		'layout' => 'shared/layouts/ui.tpl',
		'page_message' => 'layout',
	));

	$tpl->define_no_file_dynamic(array(
		'page' => Functions::renderTpl(PLUGINS_PATH . '/CronJobs/themes/default/view/client/cronjobs.tpl'),
		'cron_job_shell_type_block' => 'page'
	));

	if(Registry::get('config')->DEBUG) {
		$assetVersion = time();
	} else {
		$pluginInfo = Registry::get('pluginManager')->getPluginInfo('CronJobs');
		$assetVersion = strtotime($pluginInfo['date']);
	}

	$tpl->assign(array(
		'TR_PAGE_TITLE' => Functions::escapeHtml(tr('Client / Web Tools / Cron Jobs', true)),
		'ISP_LOGO' => layout_getUserLogo(),
		'CRONJOBS_ASSET_VERSION' => Functions::escapeUrl($assetVersion),
		'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations(),
		'DEFAULT_EMAIL_NOTIFICATION' => isset($_SESSION['user_email']) ? tohtml($_SESSION['user_email']) : '',
		'CRON_PERMISSION_FREQUENCY' => tr(
			array('%d minute', '%d minutes', $cronPermissions['cron_permission_frequency']),
			true,
			$cronPermissions['cron_permission_frequency']
		)
	));

	if($cronPermissions['cron_permission_type'] == 'url') {
		$tpl->assign('CRON_JOB_SHELL_TYPE_BLOCK', '');
	} else {
		$tpl->assign('CRON_JOB_SHELL_TYPE', Functions::escapeHtmlAttr($cronPermissions['cron_permission_type']));
	}

	generateNavigation($tpl);
	generatePageMessage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');

	EventsAggregator::getInstance()->dispatch(Events::onClientScriptEnd, array('templateEngine' => $tpl));

	$tpl->prnt();
} else {
	showBadRequestErrorPage();
}
