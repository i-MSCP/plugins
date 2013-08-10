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
 * @subpackage  Monitorix
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
function monitorix_generateSelect($tpl, $pluginManager, $graph_name='')
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	
	$hostname = $cfg->SERVER_HOSTNAME ;
	
	if (($plugin = $pluginManager->load('Monitorix', false, false)) !== null) {
		$pluginConfig = $plugin->getConfig();
		foreach ($pluginConfig['graph_enabled'] as $key => $value) {
			if($value == 'y') {
				$tpl->assign(
					array(
						'TR_MONITORIX_SELECT_VALUE' => '_'.$key,
						'TR_MONITORIX_SELECT_NAME' => $pluginConfig['graph_title'][$key],
						'MONITORIXGRAPH_WIDTH' => $pluginConfig['graph_width'],
						'MONITORIXGRAPH_HEIGHT' => $pluginConfig['graph_height'],
						'MONITORIX_NAME_SELECTED' => ($graph_name != '' && $graph_name === $key) ? $cfg->HTML_SELECTED : '',
					)
				);
				$tpl->parse('MONITORIX_ITEM', '.monitorix_item');
			}
		}
		$tpl->assign(
			array(
				'TR_MONITORIXGRAPH' => ($graph_name != '') ? tr("Monitorix - %s - %s", $hostname, $pluginConfig['graph_title'][$graph_name]) : tr("Monitorix - %s", $hostname)
			)
		);
	} else {
		$tpl->assign(
			array(
				'MONITORIX_ITEM' => '',
				'TR_MONITORIXGRAPH' => tr("Monitorix - %s", $hostname)
			)
		);
	}
}

function monitorix_selectedGraphic($tpl, $pluginManager, $graph_name, $show_when)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$graphDirectory = $pluginManager->getPluginDirectory() . '/Monitorix/tmp_graph';
	$monitorixGraphics = array();
	
	if($dirHandle = @opendir($graphDirectory)) {
		while(($file = @readdir($dirHandle)) !== FALSE) {
			if(!is_dir($file) && preg_match("/^$graph_name\d+[a-y]?[z]\.\d$show_when\.png/", $file)) {
				array_push($monitorixGraphics, $file);
			}
		}
		closedir($dirHandle);
		if(count($monitorixGraphics) > 0) {
			sort($monitorixGraphics);
			foreach ($monitorixGraphics as $graphValue) {
				$tpl->assign(
					array(
						'MONITORIXGRAPH' => 'graph=' . pathinfo($graphValue, PATHINFO_FILENAME)
					)
				);
				$tpl->parse('MONITORIX_GRAPH_ITEM', '.monitorix_graph_item');
			}
			$tpl->assign(
				array(
					'MONITORIXGRAPH_ERROR' => ''
				)
			);
		} else {
			$tpl->assign(
				array(
					'MONITORIXGRAPH_SELECTED' => '',
					'MONITORIXGRAPHIC_ERROR' => tr("No graphics for your selection available!")
				)
			);
		}
	} else {
		$tpl->assign(
			array(
				'MONITORIXGRAPH_SELECTED' => '',
				'MONITORIXGRAPHIC_ERROR' => tr("An error occured while opening the directory: %s", $graphDirectory)
			)
		);
	}
	
	$tpl->assign(
		array(
			'M_DAY_SELECTED' => ($show_when === 'day') ? $cfg->HTML_SELECTED : '',
			'M_WEEK_SELECTED' => ($show_when === 'week') ? $cfg->HTML_SELECTED : '',
			'M_MONTH_SELECTED' => ($show_when === 'month') ? $cfg->HTML_SELECTED : '',
			'M_YEAR_SELECTED' => ($show_when === 'year') ? $cfg->HTML_SELECTED : '',
			'MONITORIXGRAPH_NOT_SELECTED' => ''
		)
	);
}

/***********************************************************************************************************************
 * Main
 */

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptStart);

check_login('admin');

if(iMSCP_Registry::isRegistered('pluginManager')) {
	/** @var iMSCP_Plugin_Manager $pluginManager */
	$pluginManager = iMSCP_Registry::get('pluginManager');
} else {
	throw new iMSCP_Plugin_Exception('An unexpected error occured');
}

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => '../../plugins/Monitorix/frontend/monitorix.tpl',
		'page_message' => 'layout',
		'monitorix_item' => 'page',
		'monitorix_graph_item' => 'page'
	)
);

$graph_name = (isset($_POST['graph_name']) && $_POST['graph_name'] !== '-1') ? clean_input($_POST['graph_name']) : '';

if(isset($_GET['action']) && $_GET['action'] === 'syncgraphs') {
	send_request();
	set_page_message(
		tr('Statistical graphics successfully scheduled for update. This can take few seconds.'), 'success'
	);
	redirectTo('monitorix.php');
}

if(isset($_POST['action']) && $_POST['action'] === 'go_show') {
	if($graph_name == '') {
		$tpl->assign(
			array(
				'M_DAY_SELECTED' => $cfg->HTML_SELECTED,
				'M_WEEK_SELECTED' => '',
				'M_MONTH_SELECTED' => '',
				'M_YEAR_SELECTED' => '',
				'MONITORIXGRAPH_SELECTED' => '',
				'MONITORIXGRAPH_ERROR' => ''
			)
		);
	} else {
		monitorix_selectedGraphic($tpl, $pluginManager, $graph_name, clean_input($_POST['show_when']));
	}
} else {
	$tpl->assign(
		array(
			'M_DAY_SELECTED' => $cfg->HTML_SELECTED,
			'M_WEEK_SELECTED' => '',
			'M_MONTH_SELECTED' => '',
			'M_YEAR_SELECTED' => '',
			'MONITORIXGRAPH_SELECTED' => '',
			'MONITORIXGRAPH_ERROR' => ''
		)
	);
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Statistics / Monitorix'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'MONITORIXGRAPHIC_NOT_EXIST'	=> tr("The requested graphic doesn't exist."),
		'MONITORIXGRAPHIC_NOT_SELECTED' => tr("No monitorix graph selected."),
		'TR_UPDATE_TOOLTIP' => tr('Schedules update of statistical graphics according the last available data. This can take few seconds.'),
		'TR_UPDATE' => tr('Force update'),
		'TR_MONITORIX_SELECT_NAME_NONE' => tr('Select the graph'),
		'M_DAY' => tr('Day'),
		'M_WEEK' => tr('Week'),
		'M_MONTH' => tr('Month'),
		'M_YEAR' => tr('Year'),
		'TR_SHOW' => tr('Show graph')
	)
);

generateNavigation($tpl);
monitorix_generateSelect($tpl, $pluginManager, substr($graph_name,1));
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
