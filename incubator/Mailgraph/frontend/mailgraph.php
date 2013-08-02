<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2013 by i-MSCP Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  Mailgraph
 * @copyright   2010-2013 by i-MSCP Team
 * @author      Sascha Bay <info@space2place.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate page
 *
 * @param $tpl iMSCP_pTemplate
 * @return void
 */
function mailgraph_generatePage($tpl)
{
	if(file_exists(PLUGINS_PATH . '/Mailgraph/tmp_graph/mailgraph_day.png')) {
		$tpl->assign('MAILGRAPH_DAY_NOT_EXIST', '');
	} else {
		$tpl->assign('MAILGRAPH_DAY_EXIST', '');
	}

	if(file_exists(PLUGINS_PATH . '/Mailgraph/tmp_graph/mailgraph_week.png')) {
		$tpl->assign('MAILGRAPH_WEEK_NOT_EXIST', '');
	} else {
		$tpl->assign('MAILGRAPH_WEEK_EXIST', '');
	}

	if(file_exists(PLUGINS_PATH . '/Mailgraph/tmp_graph/mailgraph_month.png')) {
		$tpl->assign('MAILGRAPH_MONTH_NOT_EXIST', '');
	} else {
		$tpl->assign('MAILGRAPH_MONTH_EXIST', '');
	}

	if(file_exists(PLUGINS_PATH . '/Mailgraph/tmp_graph/mailgraph_year.png')) {
		$tpl->assign('MAILGRAPH_YEAR_NOT_EXIST', '');
	} else {
		$tpl->assign('MAILGRAPH_YEAR_EXIST', '');
	}	
	
	if(file_exists(PLUGINS_PATH . '/Mailgraph/tmp_graph/mailgraph_virus_day.png')) {
		$tpl->assign('MAILGRAPH_VIRUS_DAY_NOT_EXIST', '');
	} else {
		$tpl->assign('MAILGRAPH_VIRUS_DAY_EXIST', '');
	}

	if(file_exists(PLUGINS_PATH . '/Mailgraph/tmp_graph/mailgraph_virus_week.png')) {
		$tpl->assign('MAILGRAPH_VIRUS_WEEK_NOT_EXIST', '');
	} else {
		$tpl->assign('MAILGRAPH_VIRUS_WEEK_EXIST', '');
	}

	if(file_exists(PLUGINS_PATH . '/Mailgraph/tmp_graph/mailgraph_virus_month.png')) {
		$tpl->assign('MAILGRAPH_VIRUS_MONTH_NOT_EXIST', '');
	} else {
		$tpl->assign('MAILGRAPH_VIRUS_MONTH_EXIST', '');
	}

	if(file_exists(PLUGINS_PATH . '/Mailgraph/tmp_graph/mailgraph_virus_year.png')) {
		$tpl->assign('MAILGRAPH_VIRUS_YEAR_NOT_EXIST', '');
	} else {
		$tpl->assign('MAILGRAPH_VIRUS_YEAR_EXIST', '');
	}
	
	if(file_exists(PLUGINS_PATH . '/Mailgraph/tmp_graph/mailgraph_greylist_day.png')) {
		$tpl->assign('MAILGRAPH_GREYLIST_DAY_NOT_EXIST', '');
	} else {
		$tpl->assign('MAILGRAPH_GREYLIST_DAY_EXIST', '');
	}

	if(file_exists(PLUGINS_PATH . '/Mailgraph/tmp_graph/mailgraph_greylist_week.png')) {
		$tpl->assign('MAILGRAPH_GREYLIST_WEEK_NOT_EXIST','');
	} else {
		$tpl->assign('MAILGRAPH_GREYLIST_WEEK_EXIST','');
	}

	if(file_exists(PLUGINS_PATH . '/Mailgraph/tmp_graph/mailgraph_greylist_month.png')) {
		$tpl->assign('MAILGRAPH_GREYLIST_MONTH_NOT_EXIST', '');
	} else {
		$tpl->assign('MAILGRAPH_GREYLIST_MONTH_EXIST', '');
	}

	if(file_exists(PLUGINS_PATH . '/Mailgraph/tmp_graph/mailgraph_greylist_year.png')) {
		$tpl->assign('MAILGRAPH_GREYLIST_YEAR_NOT_EXIST', '');
	} else {
		$tpl->assign('MAILGRAPH_GREYLIST_YEAR_EXIST', '');
	}
}

/***********************************************************************************************************************
 * Main
 */

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$hostname = $cfg->SERVER_HOSTNAME ;


if(isset($_GET['action']) && $_GET['action'] === 'syncgraphs') {
	send_request();
	set_page_message(
		tr('Statistical graphics successfully scheduled for update. This can take few seconds.'), 'success'
	);
	redirectTo('mailgraph.php');
}

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => '../../plugins/Mailgraph/frontend/mailgraph.tpl',
		'page_message' => 'layout'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Statistics / Mailgraph'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'MAILGRAPHIC_NOT_EXIST'	=> tr("The requested graphic doesn't exist."),
		'TR_MAILGRAPH' => tr("Mailgraph - %s", $hostname),
		'TR_MAILGRAPH_VIRUS' => tr("Mailgraph virus - %s", $hostname),
		'TR_MAILGRAPH_GREYLIST' => tr("Mailgraph greylist - %s", $hostname),
		'TR_UPDATE_TOOLTIP' => tr('Schedules update of statistical graphics according the last available data. This can take few seconds.'),
		'TR_UPDATE' => tr('Update')
	)
);

generateNavigation($tpl);
mailgraph_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
