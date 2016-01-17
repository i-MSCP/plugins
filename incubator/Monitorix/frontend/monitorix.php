<?php
/**
 * i-MSCP Monitorix plugin
 *
 * Copyright (C) 2013-2016 Laurent Declercq <l.declercq@nuxwin.com>
 * Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>
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
 */

namespace Monitorix;

use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventManager;
use iMSCP_Plugin_Manager as PluginManager;
use iMSCP_Plugin_Monitorix as Monitorix;
use iMSCP_pTemplate as TemplateEngine;
use iMSCP_Registry as Registry;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate page
 *
 * @param $tpl TemplateEngine
 * @param string $graphName Graphic name
 * @return void
 */
function generatePage($tpl, $graphName = '')
{
	$cfg = Registry::get('config');

	/** @var PluginManager $pluginManager */
	$pluginManager = Registry::get('pluginManager');

	/** @var Monitorix $plugin */
	$plugin = $pluginManager->pluginGet('Monitorix');

	foreach($plugin->getConfigParam('graph_enabled', array()) as $key => $value) {
		if($value == 'y') {
			$tpl->assign(array(
				'TR_MONITORIX_SELECT_VALUE' => '_' . $key,
				'TR_MONITORIX_SELECT_NAME' => tr($key),
				'MONITORIXGRAPH_WIDTH' => $plugin->getConfigParam('graph_width', '450'),
				'MONITORIXGRAPH_HEIGHT' => $plugin->getConfigParam('graph_height', '185'),
				'MONITORIX_NAME_SELECTED' => ($graphName !== '' && $graphName === $key) ? $cfg['HTML_SELECTED'] : '',
			));

			$tpl->parse('MONITORIX_ITEM', '.monitorix_item');
		}
	}

	$tpl->assign(
		'TR_MONITORIXGRAPH',
		($graphName != '')
			? tr("Monitorix - %s - %s", $cfg['SERVER_HOSTNAME'], tr($graphName))
			: tr("Monitorix - %s", $cfg['SERVER_HOSTNAME'])
	);
}

/**
 * Generate graph list
 *
 * @param TemplateEngine $tpl
 * @param string $graphName Graphic name
 * @param string $showWhen Period to show
 */
function selectedGraphic($tpl, $graphName, $showWhen)
{
	$cfg = Registry::get('config');

	/** @var PluginManager $pluginManager */
	$pluginManager = Registry::get('pluginManager');

	$graphDirectory = $pluginManager->pluginGetDirectory() . '/Monitorix/themes/default/assets/images/graphs';
	$monitorixGraphics = array();

	if($dirHandle = @opendir($graphDirectory)) {
		while(($file = @readdir($dirHandle)) !== false) {
			if(!is_dir($file) && preg_match("/^$graphName\\d+[a-y]?[z]\\.\\d$showWhen\\.png/", $file)) {
				array_push($monitorixGraphics, $file);
			}
		}

		closedir($dirHandle);

		if(count($monitorixGraphics) > 0) {
			sort($monitorixGraphics);

			foreach($monitorixGraphics as $graphValue) {
				$tpl->assign('MONITORIXGRAPH', pathinfo($graphValue, PATHINFO_FILENAME) . '.png');
				$tpl->parse('MONITORIX_GRAPH_ITEM', '.monitorix_graph_item');
			}

			$tpl->assign('MONITORIXGRAPH_ERROR', '');
		} else {
			$tpl->assign(array(
				'MONITORIXGRAPH_SELECTED' => '',
				'MONITORIXGRAPHIC_ERROR' => tr("No graph for your selection is available")
			));
		}
	} else {
		$tpl->assign(array(
			'MONITORIXGRAPH_SELECTED' => '',
			'MONITORIXGRAPHIC_ERROR' => tr("An error occured while opening the directory: %s", $graphDirectory)
		));
	}

	$htmlSelected = $cfg['HTML_SELECTED'];

	$tpl->assign(array(
		'M_HOUR_SELECTED' => ($showWhen == 'hour') ? $htmlSelected : '',
		'M_DAY_SELECTED' => ($showWhen == 'day') ? $htmlSelected : '',
		'M_WEEK_SELECTED' => ($showWhen == 'week') ? $htmlSelected : '',
		'M_MONTH_SELECTED' => ($showWhen == 'month') ? $htmlSelected : '',
		'M_YEAR_SELECTED' => ($showWhen == 'year') ? $htmlSelected : '',
		'MONITORIXGRAPH_NOT_SELECTED' => ''
	));
}

/***********************************************************************************************************************
 * Main
 */

EventManager::getInstance()->dispatch(Events::onAdminScriptStart);
check_login('admin');

$cfg = Registry::get('config');

$tpl = new TemplateEngine();
$tpl->define_dynamic(array(
	'layout' => 'shared/layouts/ui.tpl',
	'page' => '../../plugins/Monitorix/themes/default/view/admin/monitorix.tpl',
	'page_message' => 'layout',
	'monitorix_item' => 'page',
	'monitorix_graph_item' => 'page'
));

$graphName = (isset($_POST['graph_name']) && $_POST['graph_name'] !== '-1') ? clean_input($_POST['graph_name']) : '';

if(isset($_POST['action']) && $_POST['action'] == 'go_show') {
	if($graphName == '') {
		$tpl->assign(array(
			'M_HOUR_SELECTED' => $cfg['HTML_SELECTED'],
			'M_DAY_SELECTED' => '',
			'M_WEEK_SELECTED' => '',
			'M_MONTH_SELECTED' => '',
			'M_YEAR_SELECTED' => '',
			'MONITORIXGRAPH_SELECTED' => '',
			'MONITORIXGRAPH_ERROR' => ''
		));
	} else {
		selectedGraphic($tpl, $graphName, clean_input($_POST['show_when']));
	}
} else {
	$tpl->assign(array(
		'M_HOUR_SELECTED' => $cfg['HTML_SELECTED'],
		'M_DAY_SELECTED' => '',
		'M_WEEK_SELECTED' => '',
		'M_MONTH_SELECTED' => '',
		'M_YEAR_SELECTED' => '',
		'MONITORIXGRAPH_SELECTED' => '',
		'MONITORIXGRAPH_ERROR' => ''
	));
}

/** @var PluginManager $pluginManager */
$pluginManager = Registry::get('pluginManager');

if(Registry::get('config')->DEBUG) {
	$assetVersion = time();
} else {
	$pluginInfo = $pluginManager->pluginGetInfo('Monitorix');
	$assetVersion = strtotime($pluginInfo['date']);
}

$tpl->assign(array(
	'TR_PAGE_TITLE' => tr('Statistics / Monitorix'),
	'MONITORIX_ASSET_VERSION' => tohtml($assetVersion),
	'MONITORIXGRAPHIC_NOT_EXIST' => tr("The requested graphic doesn't exist."),
	'MONITORIXGRAPHIC_NOT_SELECTED' => tr("No monitorix graph selected."),
	'TR_MONITORIX_SELECT_NAME_NONE' => tr('Select a graph'),
	'M_HOUR' => tr('Hour'),
	'M_DAY' => tr('Day'),
	'M_WEEK' => tr('Week'),
	'M_MONTH' => tr('Month'),
	'M_YEAR' => tr('Year'),
	'TR_SHOW' => tr('Show graph')
));

generateNavigation($tpl);
generatePageMessage($tpl);
generatePage($tpl, substr($graphName, 1));

$tpl->parse('LAYOUT_CONTENT', 'page');
EventManager::getInstance()->dispatch(Events::onAdminScriptEnd, array('templateEngine' => $tpl));
$tpl->prnt();
