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
 * @copyright   2010-2013 by i-MSCP Team
 * @author      i-MSCP Team
 * @Contributor bristohn
 * @Contributor Sascha Bay <info@space2place.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */
 
define('WHMCS_IMSCP_TIMEOUT', 1000);
define('WHMCS_IMSCP_SERVER_PORT', 80);

function imscp_ConfigOptions() {
	$configarray = array(
		"hosting_plan" => array("FriendlyName" => "Package Name", "Type" => "text", "Size" => "25", "Description" => "Live it blank to use custom settings"),
		"hp_mail" => array("FriendlyName" => "Mail accounts", "Type" => "text", "Size" => "5", "Description" => "-1:disabled, 0:unlimited or value >0 "),
		"hp_ftp" => array("FriendlyName" => "FTP accounts", "Type" => "text", "Size" => "5", "Description" => "-1:disabled, 0:unlimited or value >0 "),
		"hp_traff" => array("FriendlyName" => "Traffic limits", "Type" => "text", "Size" => "5", "Description" => "MB (0:unlimited or value >0)"),
		"hp_sql_db" => array("FriendlyName" => "MySQL Databases", "Type" => "text", "Size" => "5", "Description" => "-1:disabled, 0:unlimited or value >0"),
		"hp_sql_user" => array("FriendlyName" => "MySQL User accounts", "Type" => "text", "Size" => "5", "Description" => "-1:disabled, 0:unlimited or value >0"),
		"hp_sub" => array("FriendlyName" => "Subdomains", "Type" => "text", "Size" => "5", "Description" => "-1:disabled, 0:unlimited or value >0"),
		"hp_disk" => array("FriendlyName" => "Disk space", "Type" => "text", "Size" => "5", "Description" => "MB (0:unlimited or value >0)"),
		"hp_als" => array("FriendlyName" => "Domain alias", "Type" => "text", "Size" => "5", "Description" => "-1:disabled, 0:unlimited or value >0"),
		"hp_php" => array("FriendlyName" => "Php scripts", "Type" => "yesno", "Description" => "Tick to grant access"),
		"hp_cgi" => array("FriendlyName" => "CGI Scripts", "Type" => "yesno", "Description" => "Tick to grant access"),
		"hp_backup" => array("FriendlyName" => "Backup", "Type" => "yesno", "Description" => "Tick to grant access"),
		"hp_dns" => array("FriendlyName" => "Custom DNS", "Type" => "yesno", "Description" => "Tick to grant access"),
		"hp_allowsoftware" => array("FriendlyName" => "Softwares", "Type" => "yesno", "Description" => "Tick to grant access"),
		"external_mail" => array("FriendlyName" => "External mail server", "Type" => "yesno", "Description" => "Tick to grant access"),
		"web_folder_protection" => array("FriendlyName" => "Folder protection", "Type" => "yesno", "Description" => "Tick to grant access"),
		"phpini_system" => array("FriendlyName" => "Custom php.ini settings", "Type" => "yesno", "Description" => "Tick to grant access"),
		"phpini_perm_allow_url_fopen" => array("FriendlyName" => "HP allow_url_fopen", "Type" => "yesno", "Description" => "Tick to grant access"),
		"phpini_perm_display_errors" => array("FriendlyName" => "PHP display_errors", "Type" => "yesno", "Description" => "Tick to grant access"),
		"phpini_post_max_size" => array("FriendlyName" => "PHP post_max_size", "Type" => "text", "Size" => "5", "Description" => "MB (value >0)"),
		"phpini_upload_max_filesize" => array("FriendlyName" => "PHP upload_max_filesize", "Type" => "text", "Size" => "5", "Description" => "MB (value >0)"),
		"phpini_max_execution_time" => array("FriendlyName" => "PHP max_execution_time", "Type" => "text", "Size" => "5", "Description" => "seconds (value >0)"),
		"phpini_max_input_time" => array("FriendlyName" => "PHP max_input_time", "Type" => "text", "Size" => "5", "Description" => "seconds (value >0)"),
		"phpini_memory_limit" => array("FriendlyName" => "PHP memory_limit", "Type" => "text", "Size" => "5", "Description" => "MB (value >0)")
	);
	
	return $configarray;
}

/**
 * Create imsp account
 * 
 * @param array $data
 * @return string 'success' or error message
 */
function imscp_CreateAccount($data) {
	if (!$data['clientsdetails']['email']) {
		return 'Error: User does not have email set';
	}

	$accData = array();

	// hosting plan details
	$data['configoption1'] = trim($data['configoption1']);
	if (strlen($data['configoption1']) > 0) {
		$accData['hosting_plan'] = $data['configoption1'];
	} else {
		$accData = array(
			'hp_mail' => $data['configoption2'],
			'hp_ftp' => $data['configoption3'],
			'hp_traff' => $data['configoption4'],
			'hp_sql_db' => $data['configoption5'],
			'hp_sql_user' => $data['configoption6'],
			'hp_sub' => $data['configoption7'],
			'hp_disk' => $data['configoption8'],
			'hp_als' => $data['configoption9'],
			'hp_php' => $data['configoption10'] ? 'yes' : 'no',
			'hp_cgi' => $data['configoption11'] ? 'yes' : 'no',
			'hp_backup' => $data['configoption12'] ? 'yes' : 'no',
			'hp_dns' => $data['configoption13'] ? 'yes' : 'no',
			'hp_allowsoftware' => $data['configoption14'] ? 'yes' : 'no',
			'external_mail' => $data['configoption15'] ? 'yes' : 'no',
			'web_folder_protection' => $data['configoption16'] ? 'yes' : 'no',
			'phpini_system' => $data['configoption17'] ? 'yes' : 'no',
			'phpini_perm_allow_url_fopen' => $data['configoption18'] ? 'yes' : 'no',
			'phpini_perm_display_errors' => $data['configoption19'] ? 'yes' : 'no',
			'phpini_post_max_size' => $data['configoption20'],
			'phpini_upload_max_filesize' => $data['configoption21'],
			'phpini_max_execution_time' => $data['configoption22'],
			'phpini_max_input_time' => $data['configoption23'],
			'phpini_memory_limit' => $data['configoption24'],
		);
	}

	$accData = array_merge(
		$accData, 
		array(// imscp reseller settings and new account details
			'action' => 'create',
			'reseller_username' => $data['serverusername'],
			'reseller_password' => $data['serverpassword'],
			'domain' => $data['domain'],
			'admin_pass' => $data['password'],
			'email' => $data['clientsdetails']['email'],
		),
		array(// customer infos
			'customer_id' => $data['clientsdetails']['userid'],
			'fname' => $data['clientsdetails']['firstname'],
			'lname' => $data['clientsdetails']['lastname'],
			'firm' => $data['clientsdetails']['companyname'],
			'zip' => $data['clientsdetails']['postcode'],
			'city' => $data['clientsdetails']['city'],
			'state' => $data['clientsdetails']['state'],
			'country' => $data['clientsdetails']['countryname'],
			'phone' => $data['clientsdetails']['phonenumber'],
			//'fax' => $data['clientsdetails']['fax'],
			'street1' => $data['clientsdetails']['address1'],
			'street2' => $data['clientsdetails']['address2'],
		)
	);
	$result = imscp_send_request($data['serverusername'], $data['serveraccesshash'], $data['serverip'], $accData, $data['serversecure']);
	
	if (stripos($result['level'], 'success') !== false) {
		return "success";
	} else {
		return $result['message'];
	}
}

/**
 * Terminate imsp account
 * 
 * @param array $data
 * @return string 'success' or error message
 */
function imscp_TerminateAccount($data) {
	$accData = array(
		'action' => 'terminate',
		'reseller_username' => $data['serverusername'],
		'reseller_password' => $data['serverpassword'],
		'domain' => $data['domain'],
	);
	$result = imscp_send_request($data['serverusername'], $data['serveraccesshash'], $data['serverip'], $accData, $data['serversecure']);
	
	if (stripos($result['level'], 'success') !== false) {
		return "success";
	} else {
		return $result['message'];
	}
}

/**
 * Suspend imsp account
 * 
 * @param array $data
 * @return string 'success' or error message
 */
function imscp_SuspendAccount($data) {
	$accData = array(
		'action' => 'suspend',
		'reseller_username' => $data['serverusername'],
		'reseller_password' => $data['serverpassword'],
		'domain' => $data['domain'],
	);
	$result = imscp_send_request($data['serverusername'], $data['serveraccesshash'], $data['serverip'], $accData, $data['serversecure']);
	
	if (stripos($result['level'], 'success') !== false) {
		return "success";
	} else {
		return $result['message'];
	}
}

/**
 * Unsuspend imsp account
 * 
 * @param array $data
 * @return string 'success' or error message
 */
function imscp_UnsuspendAccount($data) {
	$accData = array(
		'action' => 'unsuspend',
		'reseller_username' => $data['serverusername'],
		'reseller_password' => $data['serverpassword'],
		'domain' => $data['domain'],
	);
	$result = imscp_send_request($data['serverusername'], $data['serveraccesshash'], $data['serverip'], $accData, $data['serversecure']);
	
	if (stripos($result['level'], 'success') !== false) {
		return "success";
	} else {
		return $result['message'];
	}
}

function imscp_ClientArea($data) {
	$iMSCPLink = ($data['serversecure'] ? "https://" : "http://") . $data['serverip'] . ":" . WHMCS_IMSCP_SERVER_PORT;
	return '
		<form action="' . $iMSCPLink . '/" method="post" target="_blank">
			<input type="hidden" name="uname" value="' . $data['domain'] . '" />
			<input type="hidden" name="upass" value="' . $data['password'] . '" />
			<input type="submit" name="login" value="Login to iMSCP CP" />
			<input type="button" value="Webmail" onClick="window.open(\'' . $iMSCPLink . '/webmail\')" />
			<input type="hidden" name="action" value="login">
		</form>
	';
}

function imscp_AdminLink($data) {
	$iMSCPLink = ($data['serversecure'] ? "https://" : "http://") . $data['serverip'] . ":" . WHMCS_IMSCP_SERVER_PORT;
	return '
		<form action="' . $iMSCPLink . '/" method="post" target="_blank">
			<input type="hidden" name="uname" value="' . $data['serverusername'] . '" />
			<input type="hidden" name="upass" value="' . $data['serverpassword'] . '" />
			<input type="submit" name="login" value="Sign in ' . $data['serverhostname'] . ' iMSCP CP" />
			<input type="hidden" name="action" value="login">
		</form>
	';
}

function imscp_LoginLink($data) {
	$iMSCPLink = ($data['serversecure'] ? "https://" : "http://") . $data['serverip'] . ":" . WHMCS_IMSCP_SERVER_PORT;
	echo '<a href="' . $iMSCPLink . '" class="btn" target="_blank">Login to iMSCP CP</a>';
}

/**
 * Encrypt data to remote bridge format
 * 
 * @param mixed $data Data to encrypt
 * @param string $ResellerUsername iMSCP reseller
 * @return string  encrypted value
 */
function imscp_encrypt_data($data, $ResellerUsername) {
	return strtr(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($ResellerUsername), serialize($data), MCRYPT_MODE_CBC, md5(md5($ResellerUsername)))), '+/=', '-_,');
}

/**
 * Send request to iMSCP side of bridge.
 *
 * @param string $resellerUsername iMSCP reseller
 * @param string $resellerHash iMSCP bridge key
 * @param array $data Request values
 * @param type $remoteIP iMSCP server remote host
 * @param bool $secure use ssl mode (https) ?
 * @return array iMSCP response
 */
function imscp_send_request($resellerUsername, $resellerHash, $remoteIP, array $data, $secure = false) {
	$resellerUsername = trim((string) $resellerUsername);
	
	if (strlen($resellerUsername) == 0) {
		return "Empty reseller username";
	}
	
	$resellerHash = trim((string) $resellerHash);
	
	if (strlen($resellerHash) == 0) {
		return "Empty reseller hash";
	}
	
	$remoteUrl = ($secure ? "https" : "http") . "://" . $remoteIP . ":" . WHMCS_IMSCP_SERVER_PORT . "/remotebridge.php";

	$ch = curl_init($remoteUrl);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, 'key=' . $resellerHash . '&data=' . imscp_encrypt_data($data, $resellerUsername));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, WHMCS_IMSCP_TIMEOUT);

	try {
		$httpResponse = curl_exec($ch);
		curl_close($ch);
	} catch (Exception $ex) {
		return $ex->getMessage();
	}
	$decodeResponse = json_decode($httpResponse, true);

	if (!is_array($decodeResponse)) {
		return array(
				'level' => "Error",
				'message' => "Invalid server response!",
				'data' => array()
			);
	} else {
		return $decodeResponse;
	}
}

/**
 * Import disk and bandwith usage from iMSCP remote server
 * 
 * @param array $params
 */
function imscp_UsageUpdate($params) {

	$accData = array(
		'action' => 'collectusagedata',
		'reseller_username' => $params['serverusername'],
		'reseller_password' => $params['serverpassword'],
	);
	
	$result = imscp_send_request($params['serverusername'], $params['serveraccesshash'], $params['serverip'], $accData, $params['serversecure']);
	if (stripos($result['level'], 'success') !== false) {
		foreach ($result['data'] AS $domain => $values) {
			update_query(
				"tblhosting", 
				array(
					"diskusage" => ceil($values['disk_used'] / 1048576),
					"disklimit" => ceil($values['disk_limit'] / 1048576),
					"bwusage" => ceil($values['bw_used'] / 1048576),
					"bwlimit" => ceil($values['bw_limit'] / 1048576),
					"lastupdate" => "now()",
				), 
				array(
					"server" => $params['serverid'],
					"domain" => $domain
				)
			);
		}
	}
}
