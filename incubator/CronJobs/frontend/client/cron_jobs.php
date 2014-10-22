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
 * Add/Update cron job
 */
function cronjobs_addCronJob()
{

}

/**
 * Get cron job
 */
function cronjobs_getCronPJob()
{

}

/**
 * Delete cron job
 */
function cronjobs_deleteCronJob()
{

}

/**
 * Get cron jobs list
 */
function cronjobs_getCronJobsList()
{

}

/***********************************************************************************************************************
 * Main
 */

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

check_login('user');

if (isset($_REQUEST['action'])) {
	if (is_xhr()) {
		$action = clean_input($_REQUEST['action']);

		switch ($action) {
			case 'get_cron_jobs_list':
				cronjobs_getCronJobsList();
				break;
			case 'add_cron_job':
				cronjobs_addCronJob();
				break;
			case 'get_cron_job':
				cronjobs_getCronPJob();
				break;
			case 'delete_cron_job':
				cronjobs_deleteCronJob();
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
		'page' => '../../plugins/CronJobs/themes/default/view/client/cron_jobs.tpl',
		'page_message' => 'layout'
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
		'TR_PAGE_TITLE' => tr('Client / System tools / Cron Jobs'),
		'ISP_LOGO' => layout_getUserLogo(),
		'CRONJOBS_ASSET_VERSION' => $assetVersion,
		'DATATABLE_TRANSLATIONS' => getDataTablesPluginTranslations()
	)
);

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
