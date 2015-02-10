<?php
/**
 * i-MSCP Monitorix plugin
 *
 * Copyright (C) Laurent Declercq <l.declercq@nuxwin.com>
 * Copyright (C) Sascha Bay <info@space2place.de>
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
 * @copyright   2010-2014 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @author      Sascha Bay <info@space2place.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

namespace Monitorix;

use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventManager;
use iMSCP_Plugin_Exception as PluginException;
use iMSCP_Plugin_Manager as PluginManager;
use iMSCP_pTemplate as TemplateEngine;
use iMSCP_Registry as Registry;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate page
 *
 * @param $tpl TemplateEngine
 * @param PluginManager $pluginManager
 * @param string $graphName
 * @return void
 */
function monitorix_generateSelect($tpl, $pluginManager, $graphName = '')
{
	/** @var $cfg \iMSCP_Config_Handler_File */
	$cfg = Registry::get('config');

	$hostname = $cfg['SERVER_HOSTNAME'];

	if (($plugin = $pluginManager->loadPlugin('Monitorix', false, false)) !== null) {
		$pluginConfig = $plugin->getConfig();

		foreach ($pluginConfig['graph_enabled'] as $key => $value) {
			if ($value == 'y') {
				$tpl->assign(
					array(
						'TR_MONITORIX_SELECT_VALUE' => '_' . $key,
						'TR_MONITORIX_SELECT_NAME' => tr($key),
						'MONITORIXGRAPH_WIDTH' => $pluginConfig['graph_width'],
						'MONITORIXGRAPH_HEIGHT' => $pluginConfig['graph_height'],
						'MONITORIX_NAME_SELECTED' => ($graphName != '' && $graphName === $key) ? $cfg->HTML_SELECTED : '',
					)
				);

				$tpl->parse('MONITORIX_ITEM', '.monitorix_item');
			}
		}

		$tpl->assign(
			'TR_MONITORIXGRAPH',
			($graphName != '')
				? tr("Monitorix - %s - %s", $hostname, tr($graphName, true))
				: tr("Monitorix - %s", $hostname)
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

/**
 * Generate graphic list
 *
 * @param TemplateEngine $tpl
 * @param PluginManager $pluginManager
 * @param $graphName
 * @param $showWhen
 */
function monitorix_selectedGraphic($tpl, $pluginManager, $graphName, $showWhen)
{
	/** @var $cfg \iMSCP_Config_Handler_File */
	$cfg = Registry::get('config');

	$graphDirectory = $pluginManager->getPluginDirectory() . '/Monitorix/themes/default/assets/images/graphs';
	$monitorixGraphics = array();

	if ($dirHandle = @opendir($graphDirectory)) {
		while (($file = @readdir($dirHandle)) !== FALSE) {
			if (!is_dir($file) && preg_match("/^$graphName\\d+[a-y]?[z]\\.\\d$showWhen\\.png/", $file)) {
				array_push($monitorixGraphics, $file);
			}
		}

		closedir($dirHandle);

		if (count($monitorixGraphics) > 0) {
			sort($monitorixGraphics);

			foreach ($monitorixGraphics as $graphValue) {
				$tpl->assign('MONITORIXGRAPH', pathinfo($graphValue, PATHINFO_FILENAME) . '.png');
				$tpl->parse('MONITORIX_GRAPH_ITEM', '.monitorix_graph_item');
			}

			$tpl->assign('MONITORIXGRAPH_ERROR', '');
		} else {
			$tpl->assign(
				array(
					'MONITORIXGRAPH_SELECTED' => '',
					'MONITORIXGRAPHIC_ERROR' => tr("No graph for your selection is available")
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
			'M_HOUR_SELECTED' => ($showWhen === 'hour') ? $cfg->HTML_SELECTED : '',
			'M_DAY_SELECTED' => ($showWhen === 'day') ? $cfg->HTML_SELECTED : '',
			'M_WEEK_SELECTED' => ($showWhen === 'week') ? $cfg->HTML_SELECTED : '',
			'M_MONTH_SELECTED' => ($showWhen === 'month') ? $cfg->HTML_SELECTED : '',
			'M_YEAR_SELECTED' => ($showWhen === 'year') ? $cfg->HTML_SELECTED : '',
			'MONITORIXGRAPH_NOT_SELECTED' => ''
		)
	);
}

/***********************************************************************************************************************
 * Main
 */

EventManager::getInstance()->dispatch(Events::onAdminScriptStart);

check_login('admin');

if (Registry::isRegistered('pluginManager')) {
	/** @var PLuginManager $pluginManager */
	$pluginManager = Registry::get('pluginManager');
} else {
	throw new PluginException('An unexpected error occured');
}

/** @var $cfg \iMSCP_Config_Handler_File */
$cfg = Registry::get('config');

$tpl = new TemplateEngine();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => '../../plugins/Monitorix/themes/default/view/admin/monitorix.tpl',
		'page_message' => 'layout',
		'monitorix_item' => 'page',
		'monitorix_graph_item' => 'page'
	)
);

$graphName = (isset($_POST['graph_name']) && $_POST['graph_name'] !== '-1') ? clean_input($_POST['graph_name']) : '';

if (isset($_POST['action']) && $_POST['action'] === 'go_show') {
	if ($graphName == '') {
		$tpl->assign(
			array(
				'M_HOUR_SELECTED' => $cfg->HTML_SELECTED,
				'M_DAY_SELECTED' => '',
				'M_WEEK_SELECTED' => '',
				'M_MONTH_SELECTED' => '',
				'M_YEAR_SELECTED' => '',
				'MONITORIXGRAPH_SELECTED' => '',
				'MONITORIXGRAPH_ERROR' => ''
			)
		);
	} else {
		monitorix_selectedGraphic($tpl, $pluginManager, $graphName, clean_input($_POST['show_when']));
	}
} else {
	$tpl->assign(
		array(
			'M_HOUR_SELECTED' => $cfg->HTML_SELECTED,
			'M_DAY_SELECTED' => '',
			'M_WEEK_SELECTED' => '',
			'M_MONTH_SELECTED' => '',
			'M_YEAR_SELECTED' => '',
			'MONITORIXGRAPH_SELECTED' => '',
			'MONITORIXGRAPH_ERROR' => ''
		)
	);
}

if(Registry::get('config')->DEBUG) {
	$assetVersion = time();
} else {
	$pluginInfo = Registry::get('pluginManager')->getPluginInfo('CronJobs');
	$assetVersion = strtotime($pluginInfo['date']);
}

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Statistics / Monitorix'),
		'ISP_LOGO' => layout_getUserLogo(),
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
	)
);

generateNavigation($tpl);
monitorix_generateSelect($tpl, $pluginManager, substr($graphName, 1));
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

EventManager::getInstance()->dispatch(Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
