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
 * @subpackage  OwnDDNS
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
 * @param iMSCP_Plugin_Manager $pluginManager
 * @param int $resellerId
 * @param int $customerAdminId
 * @return void
 */

function ownddnsSettings($tpl, $pluginManager)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');
	
	$htmlChecked = $cfg->HTML_CHECKED;
	
	if (($plugin = $pluginManager->loadPlugin('OwnDDNS', false, false)) !== null) {
		$pluginConfig = $plugin->getConfig();
	} else {
		set_page_message(
			tr("Can't load plugin configuration!"), 'error'
		);
		redirectTo('index.php');
	}
	
	if (isset($_REQUEST['action'])) {
		$action = clean_input($_REQUEST['action']);
		
		if($action === 'change') {
			$error = false;
			
			$max_allowed_accounts = clean_input($_POST['max_allowed_accounts']);
			$max_accounts_lenght = clean_input($_POST['max_accounts_lenght']);
			$update_repeat_time = clean_input($_POST['update_repeat_time']);
			$debugOwnddns = clean_input($_POST['debug']);
			$use_base64_encoding = clean_input($_POST['use_base64_encoding']);
			
			$debugOwnddns = ($debugOwnddns == 'yes') ? TRUE : FALSE;
			$use_base64_encoding = ($use_base64_encoding == 'yes') ? TRUE : FALSE;
			
			if(!is_numeric($max_allowed_accounts) || !is_numeric($max_accounts_lenght) || !is_numeric($update_repeat_time)) {
				set_page_message(tr("Wrong values in your config."), 'error');
				$error = true;
			}
			
			if (!$error) {
				$configOwnddns = array(
					'debug' => $debugOwnddns,
					'use_base64_encoding' => $use_base64_encoding,
					'max_allowed_accounts' => $max_allowed_accounts,
					'max_accounts_lenght' => $max_accounts_lenght,
					'update_repeat_time' => $update_repeat_time
				);
				
				exec_query(
					'
						UPDATE
							`plugin` SET `plugin_config` = ?
						WHERE
							`plugin_name` = ?
					',
					array(
						json_encode($configOwnddns), 'OwnDDNS'
					)
				);
				set_page_message(tr('The OwnDDNS settings updated successfully.'), 'success');
			}
			
			redirectTo('ownddns.php');
		}
	}
	
	$tpl->assign(
		array(
			'OWNDDNS_DEBUG_YES' => ($pluginConfig['debug'] === TRUE) ? $htmlChecked : '',
			'OWNDDNS_DEBUG_NO' => ($pluginConfig['debug'] === FALSE) ? $htmlChecked : '',
			'OWNDDNS_BASE64_YES' => ($pluginConfig['use_base64_encoding'] === TRUE) ? $htmlChecked : '',
			'OWNDDNS_BASE64_NO' => ($pluginConfig['use_base64_encoding'] === FALSE) ? $htmlChecked : '',
			'MAX_ALLOWED_ACCOUNTS' => $pluginConfig['max_allowed_accounts'],
			'MAX_ACCOUNTS_LENGHT' => $pluginConfig['max_accounts_lenght'],
			'MAX_UPDATE_REPEAT_TIME' => $pluginConfig['update_repeat_time']
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
		'page' => '../../plugins/OwnDDNS/frontend/admin/ownddns.tpl',
		'page_message' => 'layout'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('General / OwnDDNS'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_CANCEL' => tr('Cancel'),
		'TR_UPDATE' => tr('Update'),
		'TR_YES' => tr('Yes'),
		'TR_NO' => tr('No'),
		'TR_OWNDDNS_SETTINGS' => tr('OwnDDNS - Settings'),
		'TR_OWNDDNS_DEBUG' => tr('OwnDDNS - Debug'),
		'TR_OWNDDNS_BASE64' => tr('Base64 encoding'),
		'TR_MAX_ALLOWED_ACCOUNTS' => tr('Max. account for customer (0 = unlimted)'),
		'TR_MAX_ACCOUNTS_LENGHT' => tr('Max. lenght for subdomain name'),
		'TR_UPDATE_REPEAT_TIME' => tr('Update repeat time (in minutes)'),
	)
);

ownddnsSettings($tpl, $pluginManager);

generateNavigation($tpl);

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onAdminScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
