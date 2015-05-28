<?php
/**
 * i-MSCP CronJobs plugin
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

namespace CronJobs\Admin;

use CronJobs\CommonFunctions as Functions;
use CronJobs\Exception\CronjobException;
use CronJobs\Utils\CronjobValidator as CronjobValidator;
use iMSCP_Database as Database;
use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventManager;
use iMSCP_Exception as iMSCPException;
use iMSCP_Exception_Database as DatabaseException;
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
 * @return void
 */
function addCronJob()
{
	if(
		isset($_POST['cron_job_id']) && isset($_POST['cron_job_notification']) && isset($_POST['cron_job_minute']) &&
		isset($_POST['cron_job_hour']) && isset($_POST['cron_job_dmonth']) && isset($_POST['cron_job_month']) &&
		isset($_POST['cron_job_dweek']) && isset($_POST['cron_job_user']) && isset($_POST['cron_job_command']) &&
		isset($_POST['cron_job_type'])
	) {
		$cronjobId = clean_input($_POST['cron_job_id']);
		$cronjobNotification = encode_idna(clean_input($_POST['cron_job_notification']));
		$cronjobMinute = clean_input($_POST['cron_job_minute']);
		$cronjobHour = clean_input($_POST['cron_job_hour']);
		$cronjobDmonth = clean_input($_POST['cron_job_dmonth']);
		$cronjobMonth = clean_input($_POST['cron_job_month']);
		$cronjobDweek = clean_input($_POST['cron_job_dweek']);
		$cronjobUser = clean_input($_POST['cron_job_user']);
		$cronjobCommand = clean_input($_POST['cron_job_command']);
		$cronjobType = clean_input($_POST['cron_job_type']);

		try {
			if($cronjobType === 'url' || $cronjobType === 'full') {
				CronjobValidator::validate(
					$cronjobNotification, $cronjobMinute, $cronjobHour, $cronjobDmonth, $cronjobMonth, $cronjobDweek,
					$cronjobUser, $cronjobCommand, $cronjobType
				);

				if($cronjobNotification === '') {
					$cronjobNotification = null;
				}

				if(!$cronjobId) { // New cron job
					EventManager::getInstance()->dispatch('onBeforeAddCronJob', array(
						'cron_job_admin_id' => null,
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
								cron_job_type, cron_job_notification, cron_job_minute, cron_job_hour, cron_job_dmonth,
								cron_job_month, cron_job_dweek, cron_job_user, cron_job_command, cron_job_status
							) VALUES(
								?, ?, ?, ?, ?, ?, ?, ?, ?, ?
							)
						',
						array(
							$cronjobType, $cronjobNotification, $cronjobMinute, $cronjobHour, $cronjobDmonth,
							$cronjobMonth, $cronjobDweek, $cronjobUser, $cronjobCommand, 'toadd'
						)
					);

					EventManager::getInstance()->dispatch('onAfterAddCronJob', array(
						'cron_job_admin_id' => null,
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
						sprintf('CronJobs: New cron job has been added by %s', $_SESSION['user_logged']), E_USER_NOTICE
					);

					Functions::sendJsonResponse(
						200, array('message' => tr('Cron job has been scheduled for addition.'))
					);
				} else { // Cron job update
					EventManager::getInstance()->dispatch('onBeforeUpdateCronJob', array(
						'cron_job_admin_id' => null,
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
								cron_job_admin_id IS NULL
							AND
								cron_job_status = ?
						',
						array(
							$cronjobType, $cronjobNotification, $cronjobMinute, $cronjobHour, $cronjobDmonth,
							$cronjobMonth, $cronjobDweek, $cronjobCommand, 'tochange', $cronjobId, 'ok'
						)
					);

					EventManager::getInstance()->dispatch('onAfterUpdateCronJob', array(
						'cron_job_admin_id' => null,
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
							200, array('message' => tr('Cron job has been scheduled for update.'))
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
					500, array('message' => tr('An unexpected error occurred: %s', $e->getMessage()))
				);
			}
		}
	}

	Functions::sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Get cron job
 *
 * @return void
 */
function getCronJob()
{
	if(isset($_GET['cron_job_id'])) {
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
						cron_job_admin_id IS NULL
					AND
						cron_job_status = ?
				',
				array($cronJobId, 'ok')
			);

			if($stmt->rowCount()) {
				Functions::sendJsonResponse(200, $stmt->fetchRow(PDO::FETCH_ASSOC));
			}
		} catch(DatabaseException $e) {
			write_log(
				sprintf('CronJobs: Unable to get cron job with ID %s: %s', $cronJobId, $e->getMessage()), E_USER_ERROR
			);

			Functions::sendJsonResponse(
				500, array('message' => tr('An unexpected error occurred: %s', $e->getMessage()))
			);
		}
	}

	Functions::sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Enable cron job
 *
 * @return void
 */
function enableCronJob()
{
	if(isset($_POST['cron_job_id'])) {
		$cronJobId = intval($_POST['cron_job_id']);

		try {
			EventManager::getInstance()->dispatch('onBeforeEnableCronJob', array(
				'cron_job_admin_id' => null,
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
						cron_job_status = ?
				',
				array('toenable', $cronJobId, 'suspended')
			);

			if($stmt->rowCount()) {
				EventManager::getInstance()->dispatch('onAfterEnableCronJob', array(
					'cron_job_admin_id' => null,
					'cron_job_id' => $cronJobId
				));

				send_request();

				write_log(
					sprintf(
						'CronJobs: Cron job with ID %s has been scheduled for activation by %s',
						$cronJobId,
						$_SESSION['user_logged']

					),
					E_USER_NOTICE
				);

				Functions::sendJsonResponse(
					200, array('message' => tr('Cron job has been scheduled for activation.', $cronJobId))
				);
			}
		} catch(DatabaseException $e) {
			write_log(
				sprintf('CronJobs: Unable to activate cron job with ID %s: %s', $cronJobId, $e->getMessage()),
				E_USER_ERROR
			);

			Functions::sendJsonResponse(
				500, array('message' => tr('An unexpected error occurred: %s', $e->getMessage()))
			);
		}
	}

	Functions::sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Disable cron job
 *
 * @return void
 */
function disableCronJob()
{
	if(isset($_POST['cron_job_id'])) {
		$cronJobId = intval($_POST['cron_job_id']);

		try {
			EventManager::getInstance()->dispatch('onBeforeDisableCronJob', array(
				'cron_job_admin_id' => null,
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
						cron_job_status = ?
				',
				array('tosuspend', $cronJobId, 'ok')
			);

			if($stmt->rowCount()) {
				EventManager::getInstance()->dispatch('onAfterDisableCronJob', array(
					'cron_job_admin_id' => null,
					'cron_job_id' => $cronJobId
				));

				send_request();

				write_log(
					sprintf(
						'CronJobs: Cron job with ID %s has been scheduled for deactivation by %s',
						$cronJobId,
						$_SESSION['user_logged']

					),
					E_USER_NOTICE
				);

				Functions::sendJsonResponse(
					200, array('message' => tr('Cron job has been scheduled for deactivation.', $cronJobId))
				);
			}
		} catch(DatabaseException $e) {
			write_log(
				sprintf('CronJobs: Unable to deactivate cron job with ID %s: %s', $cronJobId, $e->getMessage()),
				E_USER_ERROR
			);

			Functions::sendJsonResponse(
				500, array('message' => tr('An unexpected error occurred: %s', $e->getMessage()))
			);
		}
	}

	Functions::sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Delete cron job
 *
 * @return void
 */
function deleteCronJob()
{
	if(isset($_POST['cron_job_id'])) {
		$cronJobId = intval($_POST['cron_job_id']);

		try {
			EventManager::getInstance()->dispatch('onBeforeDeleteCronJob', array(
				'cron_job_admin_id' => null,
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
						cron_job_admin_id IS NULL
					AND
						cron_job_status = ?
				',
				array('todelete', $cronJobId, 'ok')
			);

			if($stmt->rowCount()) {
				EventManager::getInstance()->dispatch('onAfterDeleteCronJob', array(
					'cron_job_admin_id' => null,
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
					200, array('message' => tr('Cron job has been scheduled for deletion.', $cronJobId))
				);
			}
		} catch(DatabaseException $e) {
			write_log(
				sprintf('CronJobs: Unable to delete cron job with ID %s: %s', $cronJobId, $e->getMessage()),
				E_USER_ERROR
			);

			Functions::sendJsonResponse(
				500, array('message' => tr('An unexpected error occurred: %s', $e->getMessage()))
			);
		}
	}

	Functions::sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Get cron jobs list
 *
 * @return void
 */
function getCronJobsList()
{
	try {
		/* Columns */
		$cols = array(
			'cron_job_minute', 'cron_job_hour', 'cron_job_dmonth', 'cron_job_month', 'cron_job_dweek', 'cron_job_user',
			'cron_job_type', 'cron_job_command', 'cron_job_status'
		);

		$colsTotal = count($cols);
		$colCnt = 'cron_job_id';

		/* DB table to use */
		$table = 'cron_jobs';

		/* Filtering */
		$where = 'WHERE cron_job_admin_id IS NULL';
		if(isset($_GET['sSearch']) && $_GET['sSearch'] !== '') {
			$where .= 'AND (';

			for($i = 0; $i < $colsTotal; $i++) {
				$where .= $cols[$i] . ' LIKE ' . quoteValue('%' . $_GET['sSearch'] . '%') . ' OR ';
			}

			$where = substr_replace($where, '', -4);
			$where .= ')';
		}

		/* Ordering */
		$order = '';
		if(isset($_GET['iSortingCols']) && isset($_GET['iSortCol_0'])) {
			$colIdx = intval($_GET['iSortCol_0']);

			$sortDir = (
				isset($_GET['sSortDir_' . $colIdx]) && in_array($_GET['sSortDir_' . $colIdx], array('asc', 'desc'))
			) ? $_GET['sSortDir_' . $colIdx] : 'asc';

			$colIdx--;

			if(isset($cols[$colIdx])) {
				$order .= 'ORDER BY ' . $cols[$colIdx] . ' ' . $sortDir;
			}
		}

		/* Paging */
		$limit = '';
		if(isset($_GET['iDisplayStart']) && isset($_GET['iDisplayLength']) && $_GET['iDisplayLength'] !== '-1') {
			$limit = 'LIMIT ' . intval($_GET['iDisplayStart']) . ', ' . intval($_GET['iDisplayLength']);
		}

		/* Get data to display */
		$rResult = execute_query(
			'
				SELECT
					SQL_CALC_FOUND_ROWS cron_job_id, ' . str_replace(' , ', ' ', implode(', ', $cols)) . "
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
		$resultTotal = execute_query("SELECT COUNT($colCnt) FROM $table WHERE cron_job_admin_id IS NULL");
		$resultTotal = $resultTotal->fetchRow(PDO::FETCH_NUM);
		$total = $resultTotal[0];

		/* Output */
		$output = array(
			'sEcho' => intval($_GET['sEcho']),
			'iTotalRecords' => $total,
			'iTotalDisplayRecords' => $filteredTotal,
			'data' => array()
		);

		$trDeactivateTooltip = tr('Deactivate');
		$trActivateTooltip = tr('Activate');
		$trEditTooltip = tr('Edit');
		$trDeleteTooltip = tr('Delete');

		while($data = $rResult->fetchRow(PDO::FETCH_ASSOC)) {
			$row = array();

			for($i = 0; $i < $colsTotal; $i++) {
				if($cols[$i] === 'cron_job_type') {
					$row[$cols[$i]] = ($data[$cols[$i]] === 'url') ? tr('Url') : tr('Shell');
				} elseif($cols[$i] == 'cron_job_status') {
					$row[$cols[$i]] = ($data[$cols[$i]] === 'tosuspend')
						? translate_dmn_status('todisable', false)
						: (
						($data[$cols[$i]] === 'suspended')
							? translate_dmn_status('disabled', false)
							: translate_dmn_status($data[$cols[$i]], false)
						);
				} else {
					$row[$cols[$i]] = $data[$cols[$i]];
				}
			}

			if($data['cron_job_status'] === 'ok') {
				$row['cron_job_disable_enable'] =
					'<span title="' . $trDeactivateTooltip . '" style="vertical-align:middle">' .
					'<input type="checkbox" data-action="disable_cronjob" ' .
					'data-cron-job-id="' . $data['cron_job_id'] . '" checked></span>';

				$row['cron_job_actions'] =
					'<span title="' . $trEditTooltip . '" data-action="edit_cronjob" ' .
					'data-cron-job-id="' . $data['cron_job_id'] . '" class="icon icon_edit clickable">&nbsp;</span> ' .

					'<span title="' . $trDeleteTooltip . '" data-action="delete_cronjob" ' .
					'data-cron-job-id="' . $data['cron_job_id'] . '" class="icon icon_delete clickable">&nbsp;</span>';
			} elseif($data['cron_job_status'] === 'suspended') {
				$row['cron_job_disable_enable'] =
					'<span title="' . $trActivateTooltip . '" style="vertical-align:middle">' .
					'<input type="checkbox" data-action="enable_cronjob" ' .
					'data-cron-job-id="' . $data['cron_job_id'] . '"></span>';

				$row['cron_job_actions'] = tr('n/a');
			} else {
				if($data['cron_job_status'] === 'tosuspend') {
					$row['cron_job_disable_enable'] =
						'<span style="vertical-align: middle"><input type="checkbox" disabled></span>';
				} else {
					$row['cron_job_disable_enable'] =
						'<span style="vertical-align: middle"><input type="checkbox" disabled checked></span>';
				}

				$row['cron_job_actions'] = tr('n/a');
			}

			$output['data'][] = $row;
		}

		Functions::sendJsonResponse(200, $output);
	} catch(DatabaseException $e) {
		write_log(sprintf('CronJobs: Unable to get cron jobs list: %s', $e->getMessage()), E_USER_ERROR);

		Functions::sendJsonResponse(
			500, array('message' => tr('An unexpected error occurred: %s', $e->getMessage()))
		);
	}

	Functions::sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/***********************************************************************************************************************
 * Main
 */

EventManager::getInstance()->dispatch(Events::onAdminScriptStart);
check_login('admin');

if(isset($_REQUEST['action'])) {
	if(is_xhr()) {
		$action = clean_input($_REQUEST['action']);

		switch($action) {
			case 'get_cronjobs_list':
				getCronJobsList();
				break;
			case 'add_cronjob':
				addCronJob();
				break;
			case 'get_cronjob':
				getCronJob();
				break;
			case'disable_cronjob':
				disableCronJob();
				break;
			case 'enable_cronjob':
				enableCronJob();
				break;
			case 'delete_cronjob':
				deleteCronJob();
				break;
			default:
				Functions::sendJsonResponse(400, array('message' => tr('Bad request.')));
		}
	}

	showBadRequestErrorPage();
}

$tpl = new TemplateEngine();
$tpl->define_dynamic(array(
	'layout' => 'shared/layouts/ui.tpl',
	'page_message' => 'layout',
));

/** @var PluginManager $pluginManager */
$pluginManager = Registry::get('pluginManager');

$tpl->define_no_file_dynamic(
	'page',
	Functions::renderTpl($pluginManager->pluginGetDirectory() . '/CronJobs/themes/default/view/admin/cronjobs.tpl')
);

if(Registry::get('config')->DEBUG) {
	$assetVersion = time();
} else {
	$pluginInfo = $pluginManager->pluginGetInfo('CronJobs');
	$assetVersion = strtotime($pluginInfo['date']);
}

EventManager::getInstance()->registerListener('onGetJsTranslations', function ($e) {
	/** @var $e \iMSCP_Events_Event */
	$e->getParam('translations')->CronJobs = array(
		'datatable' => getDataTablesPluginTranslations(false)
	);
});

$tpl->assign(array(
	'TR_PAGE_TITLE' => Functions::escapeHtml(tr('Admin / System tools / Cron Jobs')),
	'CRONJOBS_ASSET_VERSION' => Functions::escapeUrl($assetVersion),
	'DEFAULT_EMAIL_NOTIFICATION' => isset($_SESSION['user_email']) ? tohtml($_SESSION['user_email']) : ''
));

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventManager::getInstance()->dispatch(Events::onAdminScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
