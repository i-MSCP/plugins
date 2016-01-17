<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 Sascha Bay <info@space2place.de>
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
 * @copyright   Sascha Bay <info@space2place.de>
 * @author      Sascha Bay <info@space2place.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

$filter = iMSCP_Registry::set(
	'bufferFilter',
	new iMSCP_Filter_Compress_Gzip(iMSCP_Filter_Compress_Gzip::FILTER_BUFFER)
);
$filter->compressionInformation = false;
ob_start(array($filter, iMSCP_Filter_Compress_Gzip::CALLBACK_NAME));

if(iMSCP_Registry::isRegistered('pluginManager')) {
	/** @var iMSCP_Plugin_Manager $pluginManager */
	$pluginManager = iMSCP_Registry::get('pluginManager');
} else {
	throw new iMSCP_Plugin_Exception('An unexpected error occured');
}

if (($plugin = $pluginManager->loadPlugin('OwnDDNS', false, false)) !== null) {
		$pluginConfig = $plugin->getConfig();
} else {
	write_log(sprintf('Plugin OwnDDNS => Error while loading configuration for OwnDDNS plugin. IP address: %s', $_SERVER['REMOTE_ADDR']));
	exit('Error loading configuration');
}

if(isset($_GET['action']) && isset($_GET['data'])) {
	if($pluginConfig['use_base64_encoding'] === TRUE && !base64_decode($_GET['data'], FALSE)) {
		if($pluginConfig['debug'] === TRUE) write_log(sprintf('Plugin OwnDDNS => Error: Sent data is not base64 encoded! IP address: %s', $_SERVER['REMOTE_ADDR']));
		exit('Error: Invalid data sent!');
	} 
	
	$action = (isset($_GET['action']) && $_GET['action'] == 'update') ? $_GET['action'] : 'default';
	switch($action) {
		case 'update':
			$decryptedData = ($pluginConfig['use_base64_encoding'] === TRUE) ? decryptUpdateData($pluginConfig, $_GET['data'], TRUE) : decryptUpdateData($pluginConfig, $_GET['data']);
			
			$username = $decryptedData[0];
			$authKey = $decryptedData[1];
			$updateDomain = $decryptedData[2];

			updateOwnDDNSAccount($pluginConfig, $username, $authKey, $updateDomain);
			
			break;
			
		default:
		if($pluginConfig['debug'] === TRUE) write_log(sprintf('Plugin OwnDDNS => Error: Action %s is not implemented! IP address: %s', $action, $_SERVER['REMOTE_ADDR']));
		exit(sprintf('Error: Action %s is not implemented!', $action));
	}
	
	exit;
}

exit('Error');

####################################################################################

function decryptUpdateData($pluginConfig, $sendetData, $base64Encoded=FALSE) {
	if($base64Encoded === TRUE) $sendetData =  base64_decode($sendetData);
	
	$decryptedDataArray = explode(";", $sendetData);

	if(count($decryptedDataArray) != 3 || $decryptedDataArray == '') {
		if($pluginConfig['debug'] === TRUE) write_log(sprintf('Plugin OwnDDNS => Error: Sent data is invalid, 3 parameters expected! IP address: %s', $_SERVER['REMOTE_ADDR']));
		exit('No update data available!');
	}
	
	return $decryptedDataArray;
}

function updateOwnDDNSAccount($pluginConfig, $username, $authKey, $updateDomain) {
	$cfg = iMSCP_Registry::get('config');
	
	$query = "
		SELECT
			*
		FROM
			`ownddns_accounts`
		WHERE
			`ownddns_account_name` = ?
		AND
			`ownddns_account_fqdn` = ?
		AND
			`ownddns_key` = ?
		AND
			`ownddns_account_status` = ?
	";
	$stmt = exec_query($query, array($username, $updateDomain, $authKey, $cfg->ITEM_OK_STATUS));

	if ($stmt->rowCount()) {
		if($stmt->fields['ownddns_last_update'] != '0000-00-00 00:00:00') {
			checkLastOwnDDNSUpdate($pluginConfig, $stmt->fields['ownddns_last_update']);
		}
	
		exec_query(
			'
				UPDATE
					`ownddns_accounts`
				SET
					`ownddns_last_ip` = ?, `ownddns_last_update` = NOW()
				WHERE
					`ownddns_account_fqdn` = ?
			', array($_SERVER['REMOTE_ADDR'], $updateDomain));
			
		exec_query(
			'
				UPDATE
					`domain_dns`
				SET
					`domain_text` = ?
				WHERE
					`domain_id` = ?
				AND
					`alias_id` = ?
				AND
					`domain_dns` = ?
				AND
					`owned_by` = ?
			', array($_SERVER['REMOTE_ADDR'], $stmt->fields['domain_id'], $stmt->fields['alias_id'], $stmt->fields['ownddns_account_name'] . ' ' . $pluginConfig['update_ttl_time'], 'OwnDDNS_Plugin'));
			
		if($stmt->fields['alias_id'] == '0') {
			exec_query('UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?', array($cfg->ITEM_TOCHANGE_STATUS, $stmt->fields['domain_id']));
		} else {
			exec_query('UPDATE `domain_aliasses` SET `alias_status` = ? WHERE `alias_id` = ?', array($cfg->ITEM_TOCHANGE_STATUS, $stmt->fields['alias_id']));
		}
			
		send_request();
		
		if($pluginConfig['debug'] === TRUE) write_log(sprintf('Plugin OwnDDNS => The account with the FQDN %s was updated successfully! IP address: %s', $updateDomain, $_SERVER['REMOTE_ADDR']));
		echo('good ' . $_SERVER['REMOTE_ADDR']);
	} else {
		if($pluginConfig['debug'] === TRUE) write_log(sprintf('Plugin OwnDDNS => Error: There is no account matching the sent data (%s;%s;%s)! IP address: %s', $username, $authKey, $updateDomain, $_SERVER['REMOTE_ADDR']));
		exit('Error: Update not possible. Account error occurred!');
	}
}

function checkLastOwnDDNSUpdate($pluginConfig, $ownDDNSLastUpdate) {
	/** @var iMSCP_Config_Handler_File $cfg */
	$cfg = iMSCP_Registry::get('config');
	
	if((time()-(60*$pluginConfig['update_repeat_time'])) < strtotime($ownDDNSLastUpdate)) {
		if($pluginConfig['debug'] === TRUE) write_log(sprintf('Plugin OwnDDNS => Error: Update too frequently. Minimum update intervall %s minutes! IP address: %s', $pluginConfig['update_repeat_time'], $_SERVER['REMOTE_ADDR']));
		exit('Error: Abuse update intervall!');
	}
}
