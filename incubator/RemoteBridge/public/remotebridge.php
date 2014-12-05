<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP Team
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
 * @subpackage  RemoteBridge
 * @copyright   2010-2014 by i-MSCP Team
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

checkRemoteIpaddress($_SERVER['REMOTE_ADDR']);

if (isset($_POST['key']) && isset($_POST['data'])) {
	checkiMSCP_Version();

	$postData = decryptPostData($_POST['key'], $_SERVER['REMOTE_ADDR'], $_POST['data']);
	$resellerId = checkResellerLoginData($postData['reseller_username'], $postData['reseller_password']);
	$action = isset($postData['action']) ? $postData['action'] : 'default';

	switch ($action) {
		case 'create':
			$resellerHostingPlan = (isset($postData['hosting_plan']))
				? checkResellerHostingPlan($resellerId, $postData['hosting_plan']) : array();

			$resellerIpaddress = checkResellerAssignedIP($resellerId);

			if (count($resellerHostingPlan) == 0) {
				checkLimitsPostData($postData, $resellerId);
			}

			createNewUser($resellerId, $resellerHostingPlan, $resellerIpaddress, $postData);

			break;
		case 'update_dmn':
			 $resellerHostingPlan = (isset($postData['hosting_plan']))
                                ? checkResellerHostingPlan($resellerId, $postData['hosting_plan']) : array();

                        $resellerIpaddress = checkResellerAssignedIP($resellerId);

                        if (count($resellerHostingPlan) == 0) {
                                checkLimitsPostData($postData, $resellerId);
                        }

			updateDomain($resellerId, $resellerHostingPlan, $resellerIpaddress, $postData);
			break; 
		case 'addalias':
			$resellerIpaddress = checkResellerAssignedIP($resellerId);
			addAliasDomain($resellerId, $resellerIpaddress, $postData);
			break;
		case 'terminate':
			if (empty($postData['domain'])) {
				logoutReseller();
				exit(
				createJsonMessage(
					array(
						'level' => 'Error',
						'message' => 'No domain in post data available.'
					)
				)
				);
			}

			deleteUser($resellerId, $postData['domain']);
			break;
		case 'suspend':
			if (empty($postData['domain'])) {
				logoutReseller();
				exit(
				createJsonMessage(
					array(
						'level' => 'Error',
						'message' => 'No domain in post data available.'
					)
				)
				);
			}

			disableUser($resellerId, $postData['domain']);
			break;
		case 'unsuspend':
			if (empty($postData['domain'])) {
				logoutReseller();
				exit(
				createJsonMessage(
					array(
						'level' => 'Error',
						'message' => 'No domain in post data available.'
					)
				)
				);
			}

			enableUser($resellerId, $postData['domain']);

			break;
		case 'collectusagedata':
			if (empty($postData['domain'])) {
				logoutReseller();
				exit(
				createJsonMessage(
					array(
						'level' => 'Error',
						'message' => 'No domain in post data available.'
					)
				)
				);
			}

			collectUsageData($resellerId, $postData['domain']);
			break;
		case 'get_user':
			if (empty($postData['reseller_username'])) {
				logoutReseller();
				exit(
				createJsonMessage(
					array(
						'level' => 'Error',
						'message' => 'No reseller name in post data available.'
					)
				)
				);
			}

			getUserList($resellerId, $postData['reseller_username']);
			break;
		case 'add_mail':
			addMailAccount($resellerId, $postData['domain'], $postData['account'], $postData['quota'], $postData['newmailpass'], $postData['account_type'], $postData['mail_forward']);
			break;
		default:
			echo(
			createJsonMessage(
				array(
					'level' => 'Error',
					'message' => sprintf('This action: %s is not implemented.', $action)
				)
			)
			);
			exit;
	}

	logoutReseller();

	exit;
}

exit(
createJsonMessage(
	array(
		'level' => 'Error',
		'message' => 'Direct access to remote bridge not allowed.'
	)
)
);

/***********************************************************************************************************************
 * Functions
 */

/**
 * Decrypt POST data
 *
 * @param string $bridgeKey
 * @param string $ipaddress IP Adresse
 * @param string $encryptedData Encrypted data
 * @return mixed
 */
function decryptPostData($bridgeKey, $ipaddress, $encryptedData)
{
	$resName = getResellerUsername($bridgeKey, $ipaddress);

	if ($resName === false) {
		exit(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => 'No data in your post vars available.'
			)
		)
		);
	}

	$decryptedData = @unserialize(
		rtrim(
			mcrypt_decrypt(
				MCRYPT_RIJNDAEL_256,
				md5($resName),
				base64_decode(strtr($encryptedData, '-_,', '+/=')),
				MCRYPT_MODE_CBC, md5(md5($resName))
			),
			"\0"
		)
	);

	if (count($decryptedData) == 0 || $decryptedData == '') {
		exit(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => 'No data in your post vars available.'
			)
		)
		);
	}

	return $decryptedData;
}

/**
 * Get reseller username
 *
 * @param string $bridgeKey Bridge key
 * @param string $ipaddress IP address
 * @return bool|string Reseller username on success, FALSE on failure
 */
function getResellerUsername($bridgeKey, $ipaddress)
{
	/** @var iMSCP_Config_Handler_File $cfg */
	$cfg = iMSCP_Registry::get('config');

	$query = "
		SELECT
			`t1`.*, `t2`.`admin_name`
		FROM
			`remote_bridge` AS `t1`
		INNER JOIN
			`admin` AS `t2` ON (`t2`.`admin_id` = `t1`.`bridge_admin_id`)
		WHERE
			`bridge_key` = ?
		AND
			`bridge_ipaddress` = ?
		AND
			`bridge_status` = ?
	";
	$stmt = exec_query($query, array($bridgeKey, $ipaddress, $cfg->ITEM_OK_STATUS));

	return ($stmt->fields['admin_name']) ? $stmt->fields['admin_name'] : false;
}

/**
 * Check i-MSCP version
 *
 * @return void
 */
function checkiMSCP_Version()
{
	/** @var iMSCP_Config_Handler_File $cfg */
	$cfg = iMSCP_Registry::get('config');

	if (version_compare($cfg->Version, '1.1.0', '<')) {
		exit(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => sprintf(
					'iMSCP version %s is not compatible with the remote bridge. Try with a newest version.',
					$cfg->Version
				)
			)
		)
		);
	}
}

/**
 * Check remote IP address
 *
 * @param string $ipaddress Remote IP address
 */
function checkRemoteIpaddress($ipaddress)
{
	/** @var iMSCP_Config_Handler_File $cfg */
	$cfg = iMSCP_Registry::get('config');

	$query = "SELECT COUNT(*) AS `cnt` FROM `remote_bridge` WHERE `bridge_ipaddress` = ? AND `bridge_status` = ?";
	$stmt = exec_query($query, array($ipaddress, $cfg->ITEM_OK_STATUS));

	if ($stmt->fields['cnt'] == 0) {
		exit(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => sprintf('Your IP address %s does not have access to the remote bridge.', $ipaddress)
			)
		)
		);
	}
}

/**
 * Check reseller login data
 *
 * @param string $reseller_username Resller username
 * @param string $reseller_password Reseller password
 * @return mixed
 */
function checkResellerLoginData($reseller_username, $reseller_password)
{
	// Purge expired session
	do_session_timeout();

	$auth = iMSCP_Authentication::getInstance();

	// Init login process
	init_login($auth->getEvents());

	if (!empty($reseller_username) && !empty($reseller_password)) {
		$result = $auth
			->setUsername(encode_idna(clean_input($reseller_username)))
			->setPassword(clean_input($reseller_password))->authenticate();

		if (!$result->isValid()) {
			echo(
			createJsonMessage(
				array(
					'level' => 'Error',
					'message' => format_message($result->getMessages())
				)
			)
			);

			write_log(
				sprintf("Authentication via remote bridge failed. Reason: %s", format_message($result->getMessages())),
				E_USER_NOTICE
			);
			exit;
		}
	} else {
		exit(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => 'Login data are missing.'
			)
		)
		);
	}

	write_log(sprintf("%s logged in via remote bridge", $result->getIdentity()->admin_name), E_USER_NOTICE);

	return $_SESSION['user_id'];
}

/**
 * Check reseller hosting plan
 *
 * @param int $resellerId Reseller unique identifier
 * @param string $hosting_plan Hosting plan name
 * @return array
 */
function checkResellerHostingPlan($resellerId, $hosting_plan)
{
	/** @var iMSCP_Config_Handler_File $cfg */
	$cfg = iMSCP_Registry::get('config');

	$hosting_plan = urldecode($hosting_plan);

	if ($cfg->HOSTING_PLANS_LEVEL == 'admin') {
		$query = "
			SELECT 
				`t1`.* 
			FROM 
				`hosting_plans` AS `t1`
			LEFT JOIN
				`admin` AS `t2` ON(`t1`.`reseller_id` = `t2`.`admin_id`)
			WHERE
				`t2`.`admin_type` = 'admin'
			AND
				`t1`.`name` = ?
			AND 
				`t1`.`status` = '1'
		";
		$param = array($hosting_plan);
	} else {
		$query = "SELECT * FROM  `hosting_plans` WHERE  `name` = ?  AND  `reseller_id` = ? AND  `status` = '1'";
		$param = array($hosting_plan, $resellerId);
	}

	$stmt = exec_query($query, $param);
	$data = $stmt->fetchRow();
	$props = $data['props'];

	if (!$data) {
		echo(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => sprintf('No such hosting plan named: %s.', $hosting_plan)
			)
		)
		);

		logoutReseller();
		exit;
	}

	$result = array_combine(
		array(
			'hp_php', 'hp_cgi', 'hp_sub', 'hp_als', 'hp_mail', 'hp_ftp', 'hp_sql_db',
			'hp_sql_user', 'hp_traff', 'hp_disk', 'hp_backup', 'hp_dns', 'hp_allowsoftware',
			'phpini_system', 'phpini_perm_allow_url_fopen', 'phpini_perm_display_errors',
			'phpini_perm_disable_functions', 'phpini_post_max_size', 'phpini_upload_max_filesize',
			'phpini_max_execution_time', 'phpini_max_input_time', 'phpini_memory_limit',
			'external_mail', 'web_folder_protection', 'mailQuota'
		),
		array_pad(explode(';', $props), 25, 'no')
	);

	return $result;
}

/**
 * Check POST data limits
 *
 * @param array $postData POST data
 * @param int $resellerId Reseller unique identifier
 * @return bool
 */
function checkLimitsPostData($postData, $resellerId)
{
	$phpini = iMSCP_PHPini::getInstance();
	$phpini->loadRePerm($resellerId);

	if (isset($postData['hp_mail'])) {
		if (!resellerHasFeature('mail') && $postData['hp_mail'] != '-1') {
			sendPostDataError('hp_mail', 'Your mail account limit is disabled');
		} elseif (!imscp_limit_check($postData['hp_mail'], -1)) {
			sendPostDataError('hp_mail', 'Incorrect mail accounts limit');
		}
	} else {
		sendPostDataError('hp_mail', 'Variable not available in your post data');
	}

	if (isset($postData['mail_quota'])) {
		$mailQuota = ($postData['mail_quota'] != '0') ? $postData['mail_quota'] * 1048576 : '0';

		if (!imscp_limit_check($mailQuota, null)) {
			sendPostDataError('mail_quota', 'Incorrect Email ' . $postData['mail_quota'] . ' quota');
		} elseif ($postData['hp_disk'] != '0' && $mailQuota > $postData['hp_disk']) {
			sendPostDataError('mail_quota', 'Email quota cannot be bigger than disk space limit.');
		} elseif ($postData['hp_disk'] != '0' && $mailQuota == '0') {
			sendPostDataError(
				'mail_quota', 'Email quota cannot be unlimited. Max value is ' . $postData['hp_disk'] . ' MiB.'
			);
		}
	} else {
		sendPostDataError('mail_quota', 'Variable not available in your post data');
	}

	if (isset($postData['external_mail'])) {
		if ($postData['external_mail'] != 'yes' && $postData['external_mail'] != 'no') {
			sendPostDataError('external_mail', 'Incorrect value. Only yes or no is allowed');
		} elseif (!resellerHasFeature('mail') && $postData['external_mail'] == 'yes') {
			sendPostDataError('external_mail', 'Your mail account limit is disabled');
		}
	} else {
		sendPostDataError('external_mail', 'Variable not available in your post data');
	}

	if (isset($postData['hp_ftp'])) {
		if (!resellerHasFeature('ftp') && $postData['hp_ftp'] != '-1') {
			sendPostDataError('hp_ftp', 'Your ftp account limit is disabled');
		} elseif (!imscp_limit_check($postData['hp_ftp'], -1)) {
			sendPostDataError('hp_ftp', 'Incorrect FTP accounts limit');
		}
	} else {
		sendPostDataError('hp_ftp', 'Variable not available in your post data');
	}

	if (isset($postData['hp_sql_db'])) {
		if (!resellerHasFeature('sql_db') && $postData['hp_sql_db'] != '-1') {
			sendPostDataError('hp_sql_db', 'Your SQL database limit is disabled');
		} elseif (!imscp_limit_check($postData['hp_sql_db'], -1)) {
			sendPostDataError('hp_sql_db', 'Incorrect SQL databases limit');
		}
	} else {
		sendPostDataError('hp_sql_db', 'Variable not available in your post data');
	}

	if (isset($postData['hp_sql_user'])) {
		if (!resellerHasFeature('sql_user') && $postData['hp_sql_user'] != '-1') {
			sendPostDataError('hp_sql_user', 'Your SQL user limit is disabled');
		} elseif (!imscp_limit_check($postData['hp_sql_user'], -1)) {
			sendPostDataError('hp_sql_user', 'Incorrect SQL users limit');
		}
	} else {
		sendPostDataError('hp_sql_db', 'Variable not available in your post data');
	}

	if (isset($postData['hp_sub'])) {
		if (!resellerHasFeature('subdomains') && $postData['hp_sub'] != '-1') {
			sendPostDataError('hp_sub', 'Your subdomains limit is disabled');
		} elseif (!imscp_limit_check($postData['hp_sub'], -1)) {
			sendPostDataError('hp_sub', 'Incorrect subdomains limit');
		}
	} else {
		sendPostDataError('hp_sub', 'Variable not available in your post data');
	}

	if (isset($postData['hp_traff'])) {
		if (!imscp_limit_check($postData['hp_traff'], null)) {
			sendPostDataError('hp_traff', 'Incorrect monthly traffic limit');
		}
	} else {
		sendPostDataError('hp_traff', 'Variable not available in your post data');
	}

	if (isset($postData['hp_disk'])) {
		if (!imscp_limit_check($postData['hp_disk'], null)) {
			sendPostDataError('hp_disk', 'Incorrect diskspace limit');
		}
	} else {
		sendPostDataError('hp_disk', 'Variable not available in your post data');
	}

	if (isset($postData['hp_als'])) {
		if (!resellerHasFeature('domain_aliases') && $postData['hp_als'] != '-1') {
			sendPostDataError('hp_als', 'Your domain aliases limit is disabled');
		} elseif (!imscp_limit_check($postData['hp_als'], -1)) {
			sendPostDataError('hp_als', 'Incorrect aliases limit');
		}
	} else {
		sendPostDataError('hp_als', 'Variable not available in your post data');
	}

	if (isset($postData['hp_php'])) {
		if ($postData['hp_php'] != 'yes' && $postData['hp_php'] != 'no') {
			sendPostDataError('hp_php', 'Incorrect value. Only yes or no is allowed');
		}
	} else {
		sendPostDataError('hp_php', 'Variable not available in your post data');
	}

	if (isset($postData['hp_cgi'])) {
		if ($postData['hp_cgi'] != 'yes' && $postData['hp_cgi'] != 'no') {
			sendPostDataError('hp_cgi', 'Incorrect value. Only yes or no is allowed');
		}
	} else {
		sendPostDataError('hp_cgi', 'Variable not available in your post data');
	}

	if (isset($postData['hp_backup'])) {
		if (
			$postData['hp_backup'] != 'no' && $postData['hp_backup'] != 'dmn' && $postData['hp_backup'] != 'sql' &&
			$postData['hp_backup'] != 'full'
		) {
			sendPostDataError('hp_backup', 'Incorrect value. Only no, dmn, sql or full is allowed');
		}
	} else {
		sendPostDataError('hp_backup', 'Variable not available in your post data');
	}

	if (isset($postData['hp_dns'])) {
		if ($postData['hp_dns'] != 'yes' && $postData['hp_dns'] != 'no') {
			sendPostDataError('hp_dns', 'Incorrect value. Only yes or no is allowed');
		}
	} else {
		sendPostDataError('hp_dns', 'Variable not available in your post data');
	}

	if (isset($postData['hp_allowsoftware'])) {
		if ($postData['hp_allowsoftware'] != 'yes' && $postData['hp_allowsoftware'] != 'no') {
			sendPostDataError('hp_allowsoftware', 'Incorrect value. Only yes or no is allowed');
		} elseif (!resellerHasFeature('aps') && $postData['hp_allowsoftware'] == 'yes') {
			sendPostDataError('hp_allowsoftware', 'Your aps installer permission is disabled');
		} elseif ($postData['hp_allowsoftware'] == 'yes' && $postData['hp_php'] == 'no') {
			sendPostDataError('hp_allowsoftware', 'The software installer require PHP, but it is disabled');
		}
	} else {
		sendPostDataError('hp_allowsoftware', 'Variable not available in your post data');
	}

	if (isset($postData['web_folder_protection'])) {
		if ($postData['web_folder_protection'] != 'yes' && $postData['web_folder_protection'] != 'no') {
			sendPostDataError('web_folder_protection', 'Incorrect value. Only yes or no is allowed');
		}
	} else {
		sendPostDataError('web_folder_protection', 'Variable not available in your post data');
	}

	if (isset($postData['phpini_system'])) {
		if ($postData['phpini_system'] != 'yes' && $postData['phpini_system'] != 'no') {
			sendPostDataError('phpini_system', 'Incorrect value. Only yes or no is allowed');
		} elseif (!$phpini->checkRePerm('phpiniSystem') && $postData['phpini_system'] == 'yes') {
			sendPostDataError('phpini_system', 'Your php editor permission is disabled');
		} elseif ($phpini->checkRePerm('phpiniSystem') && $postData['phpini_system'] == 'yes') {
			if (isset($postData['phpini_perm_allow_url_fopen'])) {
				if (!$phpini->checkRePerm('phpiniAllowUrlFopen')) {
					$phpini->setClPerm('phpiniAllowUrlFopen', clean_input($postData['phpini_perm_allow_url_fopen']));
				}
			} else {
				sendPostDataError('phpini_perm_allow_url_fopen', 'Variable not available in your post data');
			}

			if (isset($postData['phpini_perm_display_errors'])) {
				if (!$phpini->checkRePerm('phpiniDisplayErrors')) {
					$phpini->setClPerm('phpiniDisplayErrors', clean_input($postData['phpini_perm_display_errors']));
				}
			} else {
				sendPostDataError('phpini_perm_display_errors', 'Variable not available in your post data');
			}

			if (isset($postData['phpini_perm_disable_functions'])) {
				if (PHP_SAPI != 'apache2handler' && !$phpini->checkRePerm('phpiniDisableFunctions')) {
					$phpini->setClPerm('phpiniDisableFunctions', clean_input($postData['phpini_perm_disable_functions']));
				}
			} else {
				sendPostDataError('phpini_perm_display_errors', 'Variable not available in your post data');
			}

			if (
				isset($postData['phpinipostData_max_size']) &&
				(!$phpini->setDataWithPermCheck('phpiniPostMaxSize', $postData['phpinipostData_max_size']))
			) {
				$phpini->setData('phpiniPostMaxSize', $postData['phpinipostData_max_size'], false);
				sendPostDataError('phpinipostData_max_size', 'Value for the PHP this directive is out of range');
			}

			if (
				isset($postData['phpini_upload_max_filesize']) &&
				(!$phpini->setDataWithPermCheck('phpiniUploadMaxFileSize', $postData['phpini_upload_max_filesize']))
			) {
				$phpini->setData('phpiniUploadMaxFileSize', $postData['phpini_upload_max_filesize'], false);
				sendPostDataError('phpini_upload_max_filesize', 'Value for the PHP this directive is out of range');
			}

			if (
				isset($postData['phpini_max_execution_time']) &&
				(!$phpini->setDataWithPermCheck('phpiniMaxExecutionTime', $postData['phpini_max_execution_time']))
			) {
				$phpini->setData('phpiniMaxExecutionTime', $postData['phpini_max_execution_time'], false);
				sendPostDataError('phpini_max_execution_time', 'Value for the PHP this directive is out of range');
			}

			if (
				isset($postData['phpini_max_input_time']) &&
				(!$phpini->setDataWithPermCheck('phpiniMaxInputTime', $postData['phpini_max_input_time']))
			) {
				$phpini->setData('phpiniMaxInputTime', $postData['phpini_max_input_time'], false);
				sendPostDataError('phpini_max_input_time', 'Value for the PHP this directive is out of range');
			}

			if (
				isset($postData['phpini_memory_limit']) &&
				(!$phpini->setDataWithPermCheck('phpiniMemoryLimit', $postData['phpini_memory_limit']))
			) {
				$phpini->setData('phpiniMemoryLimit', $postData['phpini_memory_limit'], false);
				sendPostDataError('phpini_memory_limit', 'Value for the PHP this directive is out of range');
			}
		}
	} else {
		sendPostDataError('phpini_system', 'Variable not available in your post data');
	}

	return true;
}

/**
 * Send POST data error
 *
 * @param string $postVar POST variable name
 * @param string $errorMessage Error message
 */
function sendPostDataError($postVar, $errorMessage)
{
	logoutReseller();

	exit(
	createJsonMessage(
		array(
			'level' => 'Error',
			'message' => sprintf('Post variable: %s : %s.', $postVar, $errorMessage)
		)
	)
	);
}

/**
 * Check reseller assigned IP
 * @param $resellerId
 * @return mixed
 */
function checkResellerAssignedIP($resellerId)
{
	$query = "SELECT *  FROM  `reseller_props` WHERE `reseller_id` = ?";
	$stmt = exec_query($query, $resellerId);
	$data = $stmt->fetchRow();

	if (!$data) {
		echo(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => 'Reseller does not have any IP address assigned.'
			)
		)
		);

		logoutReseller();
		exit;
	}

	$ips = explode(';', $data['reseller_ips']);

	if (array_key_exists('0', $ips)) {
		return $ips[0];
	} else {
		echo(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => 'Cannot retrieve reseller IP address.'
			)
		)
		);

		logoutReseller();
		exit;
	}
}

/**
 * Create new user
 *
 * @param int $resellerId Reseller unique identifier
 * @param string $resellerHostingPlan Hosting plan name
 * @param string $resellerIpaddress IP address
 * @param array $postData POST data
 * @return void
 */
function createNewUser($resellerId, $resellerHostingPlan, $resellerIpaddress, $postData)
{
	$db = iMSCP_Registry::get('db');
	$cfg = iMSCP_Registry::get('config');
	$auth = iMSCP_Authentication::getInstance();

	if (empty($postData['domain']) || empty($postData['admin_pass']) || empty($postData['email'])) {
		logoutReseller();
		exit(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => 'No domain, user password, or user emailaddress in post data available.'
			)
		)
		);
	}

	remoteBridgecheckPasswordSyntax($postData['admin_pass']);

	$domain = strtolower($postData['domain']);
	$dmnUsername = encode_idna($postData['domain']);

	if (!isValidDomainName($dmnUsername)) {
		logoutReseller();
		exit(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => sprintf('The domain %s is not valid.', $domain)
			)
		)
		);
	}

	if (imscp_domain_exists($dmnUsername, $resellerId)) {
		logoutReseller();
		exit(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => sprintf('Domain %s already exist on this server.', $domain)
			)
		)
		);
	}

	$pure_user_pass = urldecode($postData['admin_pass']);
	$admin_pass = cryptPasswordWithSalt($pure_user_pass);
	//$admin_type = 'user';
	//$created_by = $resellerId;
	$fname = (isset($postData['fname'])) ? clean_input(urldecode($postData['fname'])) : '';
	$lname = (isset($postData['lname'])) ? clean_input(urldecode($postData['lname'])) : '';
	$firm = (isset($postData['firm'])) ? clean_input(urldecode($postData['firm'])) : '';
	$zip = (isset($postData['zip'])) ? clean_input(urldecode($postData['zip'])) : '';
	$city = (isset($postData['city'])) ? clean_input(urldecode($postData['city'])) : '';
	$state = (isset($postData['state'])) ? clean_input(urldecode($postData['state'])) : '';
	$country = (isset($postData['country'])) ? clean_input(urldecode($postData['country'])) : '';
	$userEmail = (isset($postData['email'])) ? clean_input(urldecode($postData['email'])) : '';
	$phone = (isset($postData['phone'])) ? clean_input(urldecode($postData['phone'])) : '';
	$fax = (isset($postData['fax'])) ? clean_input(urldecode($postData['fax'])) : '';
	$street1 = (isset($postData['street1'])) ? clean_input(urldecode($postData['street1'])) : '';
	$street2 = (isset($postData['street2'])) ? clean_input(urldecode($postData['street2'])) : '';
	$customer_id = (isset($postData['customer_id'])) ? clean_input(urldecode($postData['customer_id'])) : '';
	$gender = (
		(isset($postData['gender']) && $postData['gender'] == 'M') ||
		(isset($postData['gender']) && $postData['gender'] == 'F')
	) ? clean_input(urldecode($postData['gender'])) : 'U';

	try {
		$db->beginTransaction();

		$query = "
			INSERT INTO `admin` (
				`admin_name`, `admin_pass`, `admin_type`, `domain_created`, `created_by`, `fname`, `lname`, `firm`,
				`zip`, `city`, `state`, `country`, `email`, `phone`, `fax`, `street1`, `street2`, `customer_id`,
				`gender`, `admin_status`
			) VALUES (
				?, ?, 'user', unix_timestamp(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
			)
		";
		exec_query(
			$query,
			array(
				$dmnUsername, $admin_pass, $resellerId, $fname, $lname, $firm, $zip, $city, $state, $country,
				$userEmail, $phone, $fax, $street1, $street2, $customer_id, $gender, $cfg->ITEM_TOADD_STATUS
			)
		);

		$recordId = $db->insertId();

		iMSCP_Events_Manager::getInstance()->dispatch(
			iMSCP_Events::onBeforeAddDomain,
			array(
				'domainName' => $dmnUsername,
				'createdBy' => $resellerId,
				'customerId' => $recordId,
				'customerEmail' => $userEmail
			)
		);

		if (count($resellerHostingPlan) == 0) {
			$mailQuota = ($postData['mail_quota'] != '0') ? $postData['mail_quota'] * 1048576 : '0';
		} else {
			$mailQuota = ($resellerHostingPlan['mail_quota'] != '0') ? $resellerHostingPlan['mail_quota'] * 1048576 : '0';
		}

		$dmnExpire = 0;
		$domain_mailacc_limit = (count($resellerHostingPlan) == 0)
			? $postData['hp_mail'] : $resellerHostingPlan['hp_mail'];
		$domain_mail_quota = $mailQuota;
		$domain_ftpacc_limit = (count($resellerHostingPlan) == 0)
			? $postData['hp_ftp'] : $resellerHostingPlan['hp_ftp'];
		$domain_traffic_limit = (count($resellerHostingPlan) == 0)
			? $postData['hp_traff'] : $resellerHostingPlan['hp_traff'];
		$domain_sqld_limit = (count($resellerHostingPlan) == 0)
			? $postData['hp_sql_db'] : $resellerHostingPlan['hp_sql_db'];
		$domain_sqlu_limit = (count($resellerHostingPlan) == 0)
			? $postData['hp_sql_user'] : $resellerHostingPlan['hp_sql_user'];
		$domain_subd_limit = (count($resellerHostingPlan) == 0) ? $postData['hp_sub'] : $resellerHostingPlan['hp_sub'];
		$domain_alias_limit = (count($resellerHostingPlan) == 0) ? $postData['hp_als'] : $resellerHostingPlan['hp_als'];
		$domain_ip_id = $resellerIpaddress;
		$domain_disk_limit = (count($resellerHostingPlan) == 0)
			? $postData['hp_disk'] : $resellerHostingPlan['hp_disk'];
		$domain_php = (count($resellerHostingPlan) == 0)
			? $postData['hp_php'] : preg_replace("/\_/", '', $resellerHostingPlan['hp_php']);
		$domain_cgi = (count($resellerHostingPlan) == 0)
			? $postData['hp_cgi'] : preg_replace("/\_/", '', $resellerHostingPlan['hp_cgi']);
		$allowbackup = (count($resellerHostingPlan) == 0)
			? $postData['hp_backup'] : preg_replace("/\_/", '', $resellerHostingPlan['hp_backup']);
		$domain_dns = (count($resellerHostingPlan) == 0)
			? $postData['hp_dns'] : preg_replace("/\_/", '', $resellerHostingPlan['hp_dns']);
		$domain_software_allowed = (count($resellerHostingPlan) == 0)
			? $postData['hp_allowsoftware'] : preg_replace("/\_/", '', $resellerHostingPlan['hp_allowsoftware']);
		$phpini_perm_system = (count($resellerHostingPlan) == 0)
			? $postData['phpini_system'] : $resellerHostingPlan['phpini_system'];
		$phpini_perm_allow_url_fopen = (count($resellerHostingPlan) == 0)
			? $postData['phpini_perm_allow_url_fopen'] : $resellerHostingPlan['phpini_perm_allow_url_fopen'];
		$phpini_perm_display_errors = (count($resellerHostingPlan) == 0)
			? $postData['phpini_perm_display_errors'] : $resellerHostingPlan['phpini_perm_display_errors'];
		$phpini_perm_disable_functions = (count($resellerHostingPlan) == 0)
			? $postData['phpini_perm_disable_functions'] : $resellerHostingPlan['phpini_perm_disable_functions'];
		$domain_external_mail = (count($resellerHostingPlan) == 0)
			? $postData['external_mail'] : preg_replace("/\_/", '', $resellerHostingPlan['external_mail']);
		$webFolderProtection = (count($resellerHostingPlan) == 0)
			? $postData['web_folder_protection']
			: preg_replace("/\_/", '', $resellerHostingPlan['web_folder_protection']);

		$query = "
			INSERT INTO `domain` (
				`domain_name`, `domain_admin_id`, `domain_created`, `domain_expires`,
				`domain_mailacc_limit`, `domain_ftpacc_limit`, `domain_traffic_limit`, `domain_sqld_limit`,
				`domain_sqlu_limit`, `domain_status`, `domain_subd_limit`, `domain_alias_limit`, `domain_ip_id`,
				`domain_disk_limit`, `domain_disk_usage`, `domain_php`, `domain_cgi`, `allowbackup`, `domain_dns`,
				`domain_software_allowed`, `phpini_perm_system`, `phpini_perm_allow_url_fopen`,
				`phpini_perm_display_errors`, `phpini_perm_disable_functions`, `domain_external_mail`,
				`web_folder_protection`, `mail_quota`
			) VALUES (
				?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
			)
		";

		exec_query(
			$query,
			array(
				$dmnUsername, $recordId, time(), $dmnExpire, $domain_mailacc_limit, $domain_ftpacc_limit,
				$domain_traffic_limit, $domain_sqld_limit, $domain_sqlu_limit, $cfg->ITEM_TOADD_STATUS,
				$domain_subd_limit, $domain_alias_limit, $domain_ip_id, $domain_disk_limit, 0, $domain_php, $domain_cgi,
				$allowbackup, $domain_dns, $domain_software_allowed, $phpini_perm_system, $phpini_perm_allow_url_fopen,
				$phpini_perm_display_errors, $phpini_perm_disable_functions, $domain_external_mail,
				$webFolderProtection, $domain_mail_quota
			)
		);

		$dmnId = $db->insertId();

		if ($phpini_perm_system == 'yes') {
			$phpini = iMSCP_PHPini::getInstance();

			$phpini->setData('phpiniSystem', 'yes');
			$phpini->setData('phpiniPostMaxSize', (count($resellerHostingPlan) == 0)
				? $postData['phpini_post_max_size'] : $resellerHostingPlan['phpini_post_max_size']);
			$phpini->setData('phpiniUploadMaxFileSize', (count($resellerHostingPlan) == 0)
				? $postData['phpini_upload_max_filesize'] : $resellerHostingPlan['phpini_upload_max_filesize']);
			$phpini->setData('phpiniMaxExecutionTime', (count($resellerHostingPlan) == 0)
				? $postData['phpini_max_execution_time'] : $resellerHostingPlan['phpini_max_execution_time']);
			$phpini->setData('phpiniMaxInputTime', (count($resellerHostingPlan) == 0)
				? $postData['phpini_max_input_time'] : $resellerHostingPlan['phpini_max_input_time']);
			$phpini->setData('phpiniMemoryLimit', (count($resellerHostingPlan) == 0)
				? $postData['phpini_memory_limit'] : $resellerHostingPlan['phpini_memory_limit']);

			$phpini->saveCustomPHPiniIntoDb($dmnId);
		}

		$query = "INSERT INTO `htaccess_users` (`dmn_id`, `uname`, `upass`, `status`) VALUES (?, ?, ?, ?)";
		exec_query($query, array($dmnId, $dmnUsername, cryptPasswordWithSalt($pure_user_pass), $cfg->ITEM_TOADD_STATUS));

		$user_id = $db->insertId();

		$query = 'INSERT INTO `htaccess_groups` (`dmn_id`, `ugroup`, `members`, `status`) VALUES (?, ?, ?, ?)';
		exec_query($query, array($dmnId, $cfg->WEBSTATS_GROUP_AUTH, $user_id, $cfg->ITEM_TOADD_STATUS));

		// Create default addresses if needed
		if ($cfg->CREATE_DEFAULT_EMAIL_ADDRESSES) {
			client_mail_add_default_accounts($dmnId, $userEmail, $dmnUsername);
		}

		$query = "INSERT INTO `user_gui_props` (`user_id`, `lang`, `layout`) VALUES (?, ?, ?)";
		exec_query($query, array($recordId, $cfg->USER_INITIAL_LANG, $cfg->USER_INITIAL_THEME));

		update_reseller_c_props($resellerId);

		$db->commit();

		iMSCP_Events_Manager::getInstance()->dispatch(
			iMSCP_Events::onAfterAddDomain,
			array(
				'domainName' => $dmnUsername,
				'createdBy' => $resellerId,
				'customerId' => $recordId,
				'customerEmail' => $userEmail,
				'domainId' => $dmnId
			)
		);

		send_request();

		write_log(
			sprintf(
				"%s add user: " . $domain . " (for domain " . $domain . ") via remote bridge",
				decode_idna($auth->getIdentity()->admin_name)
			),
			E_USER_NOTICE
		);
		write_log(
			sprintf(
				"%s add user: add domain: " . $domain . " via remote bridge",
				decode_idna($auth->getIdentity()->admin_name)
			),
			E_USER_NOTICE
		);

	} catch (iMSCP_Exception_Database $e) {
		$db->rollBack();
		echo(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => sprintf(
					'Error while creating user: %s, $s, %s', $e->getMessage(), $e->getQuery(), $e->getCode()
				)
			)
		)
		);
		logoutReseller();
		exit;
	}

	if (isset($postData['alias_domains']) && count($postData['alias_domains']) > 0) {
		createAliasDomain($resellerId, $dmnId, $domain_ip_id, $postData);
	}

	echo(
	createJsonMessage(
		array(
			'level' => 'Success',
			'message' => sprintf('User %s added successfull.', $domain)
		)
	)
	);
}

/**
* Update domain
*
* @param int $resellerId Reseller unique identifier
* @param string $resellerHostingPlan HostingPlan name
* @param string $resellerIpaddress IP address
* @param array $postData POST data
* @return void
*/
function updateDomain($resellerId, $resellerHostingPlan, $resellerIpaddress, $postData)
{
	$db = iMSCP_Registry::get('db');
	$cfg = iMSCP_Registry::get('config');
	$auth = iMSCP_Authentication::getInstance();

	if (empty($postData['domain'])) {
		logoutReseller();
		exit(
		createJsonMessage(
		array(
		'level' => 'Error',
		'message' => 'No domain in post data available.'
				)
			)
		);
	}
	if(! empty($postData['admin_pass'])){
		remoteBridgecheckPasswordSyntax($postData['admin_pass']);
		$pure_user_pass = urldecode($postData['admin_pass']);
		$admin_pass = cryptPasswordWithSalt($pure_user_pass);
	}

	$domain = strtolower($postData['domain']);
	$dmnUsername = encode_idna($postData['domain']);

	if (! imscp_domain_exists($dmnUsername, $resellerId)) {
		logoutReseller();
		exit(
			createJsonMessage(
				array(
					'level' => 'Error',
					'message' => sprintf('Domain %s not exist on this server.', $domain)
				)
			)
		);
	}

	$query = '
		SELECT
			domain_id
		FROM
			domain
		WHERE
			domain_name = ?
	';
	$stmt = exec_query($query, $domain);
	$domainId = $stmt->fields['domain_id'];

	try {
		$db->beginTransaction();

		iMSCP_Events_Manager::getInstance()->dispatch(
			iMSCP_Events::onBeforeEditDomain,
			array(
				'domainName' => $dmnUsername,
				'createdBy' => $resellerId,
				'customerId' => $recordId,
				'customerEmail' => $userEmail
			)
		);

	$dmnExpire = 0;
	$domain_mailacc_limit = (count($resellerHostingPlan) == 0)
		? $postData['hp_mail'] : $resellerHostingPlan['hp_mail'];
	$domain_mail_quota = $mailQuota;
	$domain_ftpacc_limit = (count($resellerHostingPlan) == 0)
		? $postData['hp_ftp'] : $resellerHostingPlan['hp_ftp'];
	$domain_traffic_limit = (count($resellerHostingPlan) == 0)
		? $postData['hp_traff'] : $resellerHostingPlan['hp_traff'];
	$domain_sqld_limit = (count($resellerHostingPlan) == 0)
		? $postData['hp_sql_db'] : $resellerHostingPlan['hp_sql_db'];
	$domain_sqlu_limit = (count($resellerHostingPlan) == 0)
		? $postData['hp_sql_user'] : $resellerHostingPlan['hp_sql_user'];
	$domain_subd_limit = (count($resellerHostingPlan) == 0) 
		? $postData['hp_sub'] : $resellerHostingPlan['hp_sub'];
	$domain_alias_limit = (count($resellerHostingPlan) == 0) 
		? $postData['hp_als'] : $resellerHostingPlan['hp_als'];
	$domain_ip_id = $resellerIpaddress;
	$domain_disk_limit = (count($resellerHostingPlan) == 0)
		? $postData['hp_disk'] : $resellerHostingPlan['hp_disk'];
	$domain_php = (count($resellerHostingPlan) == 0)
		? $postData['hp_php'] : preg_replace("/\_/", '', $resellerHostingPlan['hp_php']);
	$domain_cgi = (count($resellerHostingPlan) == 0)
		? $postData['hp_cgi'] : preg_replace("/\_/", '', $resellerHostingPlan['hp_cgi']);
	$allowbackup = (count($resellerHostingPlan) == 0)
		? $postData['hp_backup'] : preg_replace("/\_/", '', $resellerHostingPlan['hp_backup']);
	$domain_dns = (count($resellerHostingPlan) == 0)
		? $postData['hp_dns'] : preg_replace("/\_/", '', $resellerHostingPlan['hp_dns']);
	$domain_software_allowed = (count($resellerHostingPlan) == 0)
		? $postData['hp_allowsoftware'] : preg_replace("/\_/", '', $resellerHostingPlan['hp_allowsoftware']);
	$phpini_perm_system = (count($resellerHostingPlan) == 0)
		? $postData['phpini_system'] : $resellerHostingPlan['phpini_system'];
	$phpini_perm_allow_url_fopen = (count($resellerHostingPlan) == 0)
		? $postData['phpini_perm_allow_url_fopen'] : $resellerHostingPlan['phpini_perm_allow_url_fopen'];
	$phpini_perm_display_errors = (count($resellerHostingPlan) == 0)
		? $postData['phpini_perm_display_errors'] : $resellerHostingPlan['phpini_perm_display_errors'];
	$phpini_perm_disable_functions = (count($resellerHostingPlan) == 0)
		? $postData['phpini_perm_disable_functions'] : $resellerHostingPlan['phpini_perm_disable_functions'];
	$domain_external_mail = (count($resellerHostingPlan) == 0)
		? $postData['external_mail'] : preg_replace("/\_/", '', $resellerHostingPlan['external_mail']);
	$webFolderProtection = (count($resellerHostingPlan) == 0)
		? $postData['web_folder_protection']
		: preg_replace("/\_/", '', $resellerHostingPlan['web_folder_protection']);

	$query = "
		UPDATE
			`domain`
		SET
			`domain_expires` = ?, `domain_last_modified` = ?, `domain_mailacc_limit` = ?,
			`domain_ftpacc_limit` = ?, `domain_traffic_limit` = ?, `domain_sqld_limit` = ?,
			`domain_sqlu_limit` = ?, `domain_status` = ?, `domain_alias_limit` = ?, `domain_subd_limit` = ?,
			`domain_ip_id` = ?, `domain_disk_limit` = ?, `domain_php` = ?, `domain_cgi` = ?, `allowbackup` = ?,
			`domain_dns` = ?,  `domain_software_allowed` = ?, `phpini_perm_system` = ?,
			`phpini_perm_allow_url_fopen` = ?, `phpini_perm_display_errors` = ?,
			`phpini_perm_disable_functions` = ?, `domain_external_mail` = ?, `web_folder_protection` = ?,
			`mail_quota` = ?
		WHERE
			`domain_id` = ?
			";
	exec_query(
		$query,
		array(
			$dmnExpire, $lastModified, $domain_mailacc_limit, 
			$domain_ftpacc_limit, $domain_traffic_limit, $domain_sqld_limit, 
			$domain_sqlu_limit, $cfg->ITEM_TOCHANGE_STATUS,	$domain_alias_limit, $domain_subd_limit, 
			$domain_ip_id, $domain_disk_limit, $domain_php, $domain_cgi, $allowbackup, 
			$domain_dns, $domain_software_allowed, $phpini_perm_system, 
			$phpini_perm_allow_url_fopen, $phpini_perm_display_errors, 
			$phpini_perm_disable_functions, $domain_external_mail,	$webFolderProtection, 
			$domain_mail_quota, $domainId
		)
	);

	$dmnId = $db->insertId();

	if ($phpini_perm_system == 'yes') {
		$phpini = iMSCP_PHPini::getInstance();
		$phpini->setData('phpiniSystem', 'yes');
		$phpini->setData('phpiniPostMaxSize', (count($resellerHostingPlan) == 0)
			? $postData['phpini_post_max_size'] : $resellerHostingPlan['phpini_post_max_size']);
		$phpini->setData('phpiniUploadMaxFileSize', (count($resellerHostingPlan) == 0)
			? $postData['phpini_upload_max_filesize'] : $resellerHostingPlan['phpini_upload_max_filesize']);
		$phpini->setData('phpiniMaxExecutionTime', (count($resellerHostingPlan) == 0)
			? $postData['phpini_max_execution_time'] : $resellerHostingPlan['phpini_max_execution_time']);
		$phpini->setData('phpiniMaxInputTime', (count($resellerHostingPlan) == 0)
			? $postData['phpini_max_input_time'] : $resellerHostingPlan['phpini_max_input_time']);
		$phpini->setData('phpiniMemoryLimit', (count($resellerHostingPlan) == 0)
			? $postData['phpini_memory_limit'] : $resellerHostingPlan['phpini_memory_limit']);
		$phpini->saveCustomPHPiniIntoDb($dmnId);
	}

	update_reseller_c_props($resellerId);

	$db->commit();

	iMSCP_Events_Manager::getInstance()->dispatch(
		iMSCP_Events::onAfterEditDomain,
		array(
			'domainName' => $dmnUsername,
			'createdBy' => $resellerId,
			'customerId' => $recordId,
			'customerEmail' => $userEmail,
			'domainId' => $dmnId
		)
	);

	send_request();

	write_log(
		sprintf(
			"%s update user: " . $domain . " (for domain " . $domain . ") via remote bridge",
			decode_idna($auth->getIdentity()->admin_name)
		),
		E_USER_NOTICE
	);
	write_log(
		sprintf(
			"%s update user: update domain: " . $domain . " via remote bridge",
			decode_idna($auth->getIdentity()->admin_name)
		),
		E_USER_NOTICE
	);

	}
	catch (iMSCP_Exception_Database $e) {
		$db->rollBack();
		echo(
			createJsonMessage(
				array(
					'level' => 'Error',
					'message' => sprintf(
						'Error while updating user: %s, $s, %s', $e->getMessage(), $e->getQuery(), $e->getCode()
					)
				)
			)
		);
		logoutReseller();
		exit;
	}

	echo(
		createJsonMessage(
			array(
			'level' => 'Success',
			'message' => sprintf('User %s update successfull.', $domain)
			)
		)
	);
}

/**
 * Add domain alias
 *
 * @param int $resellerId Reseller unique identifier
 * @param string $resellerIpaddress IP Address
 * @param array $postData POST data
 * @return void
 */
function addAliasDomain($resellerId, $resellerIpaddress, $postData)
{
	//$db = iMSCP_Registry::get('db');
	//$cfg = iMSCP_Registry::get('config');
	//$auth = iMSCP_Authentication::getInstance();

	if (empty($postData['domain']) || count($postData['alias_domains']) == 0) {
		logoutReseller();
		exit(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => 'No domain or domain aliases in post data available.'
			)
		)
		);
	}

	$domain = strtolower($postData['domain']);
	$dmnUsername = encode_idna($postData['domain']);

	$query = '
		SELECT
			domain_admin_id, domain_status, created_by
		FROM
			domain
		INNER JOIN
			admin ON(admin_id = domain_admin_id)
		WHERE
			domain_name= ?
	';
	$stmt = exec_query($query, $dmnUsername);

	if ($stmt->rowCount() && $stmt->fields['created_by'] == $resellerId) {
		$customerId = $stmt->fields['domain_admin_id'];
		createAliasDomain($resellerId, $customerId, $resellerIpaddress, $postData);
		echo(
		createJsonMessage(
			array(
				'level' => 'Success',
				'message' => sprintf(
					'Domain aliases: %s succesfully added.',
					implode(', ', $postData['alias_domains'])
				)
			)
		)
		);
	} else {
		echo(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => sprintf('Unknown domain %s.', $domain)
			)
		)
		);
	}
}

/**
 * Create domain alias
 *
 * @param int $resellerId Reseller unique identifier
 * @param int $customerDmnId Customer domain unique identifier
 * @param int $domain_ip_id Domain IP unique identifier
 * @param array $postData POST data
 * @return void
 */
function createAliasDomain($resellerId, $customerDmnId, $domain_ip_id, $postData)
{
	$db = iMSCP_Registry::get('db');
	$cfg = iMSCP_Registry::get('config');
	$auth = iMSCP_Authentication::getInstance();

	foreach ($postData['alias_domains'] as $aliasdomain) {
		$aliasdomain = strtolower($aliasdomain);
		$alias_domain = encode_idna($aliasdomain);

		if (!isValidDomainName(decode_idna($alias_domain))) {
			logoutReseller();
			exit(
			createJsonMessage(
				array(
					'level' => 'Error',
					'message' => sprintf('The alias domain %s is not valid.', $aliasdomain)
				)
			)
			);
		}

		if (imscp_domain_exists($alias_domain, $resellerId)) {
			logoutReseller();
			exit(
			createJsonMessage(
				array(
					'level' => 'Error',
					'message' => sprintf('Alias domain %s already exist on this server.', $aliasdomain)
				)
			)
			);
		}

		$mountPoint = array_encode_idna(strtolower(trim(clean_input($aliasdomain))), true);

		try {
			$db->beginTransaction();

			$customerId = who_owns_this($customerDmnId, 'dmn_id');

			$query = "
				INSERT INTO `domain_aliasses` (
					`domain_id`, `alias_name`, `alias_mount`, `alias_status`, `alias_ip_id`
				) VALUES (
					?, ?, ?, ?, ?
				)
			";
			exec_query($query, array($customerDmnId, $alias_domain, $mountPoint, $cfg->ITEM_TOADD_STATUS, $domain_ip_id));

			$alsId = $db->insertId();

			// Since the reseller is allowed to add an alias for customer accounts, whatever the value of
			// their domain aliases limit, we update the related fields to avoid any consistency problems.

			$customerProps = get_domain_default_props($customerId);
			$newCustomerAlsLimit = 0;

			if ($customerProps['domain_alias_limit'] > 0) { // Customer has als limit
				$query = 'SELECT COUNT(`alias_id`) AS `cnt` FROM `domain_aliasses` WHERE `domain_id` = ?';
				$stmt = exec_query($query, $customerDmnId);
				$customerAlsCount = $stmt->fields['cnt'];

				// If the customer als limit is reached, we extend it
				if ($customerAlsCount >= $customerProps['domain_alias_limit']) {
					$newCustomerAlsLimit += $customerAlsCount;
				}
			} elseif ($customerProps['domain_alias_limit'] != 0) { // Als feature is disabled for the customer.
				// We simply enable als feature by setting the limit to 1
				$newCustomerAlsLimit = 1;

				// We also update reseller current als count (number of assigned als) by incrementing the current value.
				$query = "
					UPDATE
						`reseller_props`
					SET
						`current_als_cnt` = (`current_als_cnt` + 1)
					WHERE
						`reseller_id` = ?
				";
				exec_query($query, $_SESSION['user_id']);
			}

			// We update the customer als limit according if needed
			if ($newCustomerAlsLimit) {
				exec_query(
					"UPDATE `domain` SET `domain_alias_limit` = ? WHERE `domain_admin_id` = ?",
					array($newCustomerAlsLimit, $customerId)
				);
			}

			$query = "SELECT `email` FROM `admin` WHERE `admin_id` = ? LIMIT 1";
			$stmt = exec_query($query, $customerId);
			$customerEmail = $stmt->fields['email'];

			// Create default email accounts if needed
			if ($cfg->CREATE_DEFAULT_EMAIL_ADDRESSES) {
				client_mail_add_default_accounts($customerDmnId, $customerEmail, $alias_domain, 'alias', $alsId);
			}

			$db->commit();

		} catch (iMSCP_Exception_Database $e) {
			$db->rollBack();
			echo(
			createJsonMessage(
				array(
					'level' => 'Error',
					'message' => sprintf(
						'Error while creating alias domain: %s, %s, %s',
						$e->getMessage(),
						$e->getQuery(),
						$e->getCode()
					)
				)
			)
			);
			logoutReseller();
			exit;
		}

		send_request();
		write_log(
			sprintf(
				'%s added domain alias: %s via remote bridge',
				decode_idna($auth->getIdentity()->admin_name),
				$aliasdomain
			),
			E_USER_NOTICE
		);
	}
}

/**
 * Delete user
 *
 * @param int $resellerId Reseller unique identifier
 * @param string $domain Customer main domain name
 * @return void
 */
function deleteUser($resellerId, $domain)
{
	//$db = iMSCP_Registry::get('db');
	//$cfg = iMSCP_Registry::get('config');

	$auth = iMSCP_Authentication::getInstance();

	$dmnUsername = encode_idna($domain);

	$query = '
		SELECT
			domain_admin_id, domain_status, created_by
		FROM
			domain
		INNER JOIN
			admin ON(admin_id = domain_admin_id)
		WHERE
			domain_name = ?
	';
	$stmt = exec_query($query, $dmnUsername);

	if ($stmt->rowCount() && $stmt->fields['created_by'] == $resellerId) {
		$customerId = $stmt->fields['domain_admin_id'];
		try {
			if (!deleteCustomer($customerId, true)) {
				echo(
				createJsonMessage(
					array(
						'level' => 'Error',
						'message' => sprintf('Customer account %s not found.', $domain)
					)
				)
				);
				logoutReseller();
				exit;
			}
			echo(
			createJsonMessage(
				array(
					'level' => 'Success',
					'message' => sprintf('Customer account: %s successfully scheduled for deletion.', $domain)
				)
			)
			);
			write_log(
				sprintf('%s scheduled deletion of the customer account: %s',
					decode_idna($auth->getIdentity()->admin_name), $domain
				),
				E_USER_NOTICE
			);
			send_request();
		} catch (iMSCP_Exception $e) {
			write_log(
				sprintf(
					'System was unable to schedule deletion of the customer account: %s. Message was: %s',
					$domain,
					$e->getMessage()
				),
				E_USER_ERROR
			);
			echo(
			createJsonMessage(
				array(
					'level' => 'Error',
					'message' => sprintf(
						'System was unable to schedule deletion of the customer account: %s.', $domain
					)
				)
			)
			);

			logoutReseller();
			exit;
		}
	} else {
		echo(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => sprintf('Unknown domain %s.', $domain)
			)
		)
		);
	}
}

/**
 * Disable user
 *
 * @param int $resellerId Reseller unique identifier
 * @param string $domain Customer main domain name
 * @return void
 */
function disableUser($resellerId, $domain)
{
	//$db = iMSCP_Registry::get('db');
	$cfg = iMSCP_Registry::get('config');
	$auth = iMSCP_Authentication::getInstance();

	$dmnUsername = encode_idna($domain);

	$query = '
		SELECT
			domain_admin_id, domain_status, created_by
		FROM
			domain
		INNER JOIN
			admin ON(admin_id = domain_admin_id)
		WHERE
			domain_name = ?
	';
	$stmt = exec_query($query, $dmnUsername);

	if ($stmt->rowCount() && $stmt->fields['created_by'] == $resellerId) {
		$customerId = $stmt->fields['domain_admin_id'];

		if ($stmt->fields['domain_status'] == $cfg->ITEM_OK_STATUS) {
			change_domain_status($customerId, 'deactivate');
			send_request();
			write_log(
				sprintf(
					'%s disabled the customer account: %s via remote bridge',
					decode_idna($auth->getIdentity()->admin_name),
					$domain
				),
				E_USER_NOTICE
			);
			echo(
			createJsonMessage(
				array(
					'level' => 'Success',
					'message' => sprintf('Domain %s succesfully disabled.', $domain)
				)
			)
			);
		} else {
			echo(
			createJsonMessage(
				array(
					'level' => 'Error',
					'message' => sprintf(
						'Cannot disable domain %s. Current domain status is: %s.',
						$domain,
						$stmt->fields['domain_status']
					)
				)
			)
			);
		}
	} else {
		echo(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => sprintf('Unknown domain %s.', $domain)
			)
		)
		);
	}
}

/**
 * Enable user
 *
 * @param int $resellerId Reseller unique identifier
 * @param string $domain Customer main domain name
 * @return void
 */
function enableUser($resellerId, $domain)
{
	//$db = iMSCP_Registry::get('db');
	$cfg = iMSCP_Registry::get('config');
	$auth = iMSCP_Authentication::getInstance();

	$dmnUsername = encode_idna($domain);

	$query = '
		SELECT
			domain_admin_id, domain_status, created_by
		FROM
			domain
		INNER JOIN
			admin ON(admin_id = domain_admin_id)
		WHERE
			domain_name = ?
	';
	$stmt = exec_query($query, $dmnUsername);

	if ($stmt->rowCount() && $stmt->fields['created_by'] == $resellerId) {
		$customerId = $stmt->fields['domain_admin_id'];

		if ($stmt->fields['domain_status'] == $cfg->ITEM_DISABLED_STATUS) {
			change_domain_status($customerId, 'activate');
			send_request();
			write_log(
				sprintf(
					'%s activated the customer account: %s via remote bridge',
					decode_idna($auth->getIdentity()->admin_name),
					$domain
				),
				E_USER_NOTICE
			);

			echo(
			createJsonMessage(
				array(
					'level' => 'Success',
					'message' => sprintf('Domain %s succesfully activated.', $domain)
				)
			)
			);
		} else {
			echo(
			createJsonMessage(
				array(
					'level' => 'Error',
					'message' => sprintf(
						'Cannot activate domain %s. Current domain status is: %s.',
						$domain, $stmt->fields['domain_status']
					)
				)
			)
			);
		}
	} else {
		echo(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => sprintf('Unknown domain %s.', $domain)
			)
		)
		);
	}
}

/**
 * Collect usage data
 *
 * @param int $resellerId Reseller unique identifier
 * @param string $domain Customer main domain name
 * @return void
 */
function collectUsageData($resellerId, $domain)
{
	$query = '
		SELECT
			domain_id
		FROM
			domain
		INNER JOIN
			admin ON(admin_id = domain_admin_id)
		WHERE
			created_by = ?
	';

	if ($domain == 'all') {
		$stmt = exec_query($query, $resellerId);
	} else {
		$query .= ' AND domain_name = ?';
		$dmnUsername = encode_idna($domain);
		$stmt = exec_query($query, array($resellerId, $dmnUsername));
	}

	if (!$stmt->rowCount()) {
		exit(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => ($domain === 'all')
					? sprintf('No usage data available.') : sprintf('Unknown domain %s.', $domain)
			)
		)
		);
	} else {
		$usageData = array();

		foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $domainId) {
			list(
				$domainName, $domainId, , , , , $trafficUsageBytes, $diskspaceUsageBytes
				) = generate_user_traffic($domainId);

			list(
				$usub_current, $usub_max, $uals_current, $uals_max, $umail_current, $umail_max, $uftp_current, $uftp_max,
				$usql_db_current, $usql_db_max, $usql_user_current, $usql_user_max, $trafficLimit, $diskspaceLimit
				) = generate_user_props($domainId);

			if ($domainName != 'n/a') {
				$usageData[$domainName] = array(
					'domain' => $domainName,
					'disk_used' => $diskspaceUsageBytes,
					'disk_limit' => $diskspaceLimit * 1048576,
					'bw_used' => $trafficUsageBytes,
					'bw_limit' => $trafficLimit * 1048576,
					'subdomain_used' => $usub_current,
					'subdomain_limit' => $usub_max,
					'alias_used' => $uals_current,
					'alias_limit' => $uals_max,
					'mail_used' => $umail_current,
					'mail_limit' => $umail_max,
					'ftp_used' => $uftp_current,
					'ftp_limit' => $uftp_max,
					'sqldb_used' => $usql_db_current,
					'sqldb_limit' => $usql_db_max,
					'sqluser_used' => $usql_user_current,
					'sqluser_limit' => $usql_user_max
				);
			} else {
				exit(
				createJsonMessage(
					array(
						'level' => 'Error',
						'message' => sprintf('Error while collecting usage statistics for domain %s.', $domain)
					)
				)
				);
			}
		}

		echo(
		createJsonMessage(
			array(
				'level' => 'Success',
				'message' => sprintf('Usage statistics for domain %s successfully generated.', $domain),
				'data' => $usageData
			)
		)
		);
	}
}

/**
 * Logout reseller
 *
 * @return void
 */
function logoutReseller()
{
	$auth = iMSCP_Authentication::getInstance();

	if ($auth->hasIdentity()) {
		$adminName = $auth->getIdentity()->admin_name;
		$auth->unsetIdentity();
		write_log(sprintf("%s logged out from remote bridge", idn_to_utf8($adminName)), E_USER_NOTICE);
	}
}

/**
 * Check password syntax
 *
 * @param string $password Password
 * @param string $unallowedChars Regexp representing unallowed characters
 * @return void
 */
function remoteBridgecheckPasswordSyntax($password, $unallowedChars = '')
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$passwordLength = strlen($password);

	if ($cfg->PASSWD_CHARS < 6) {
		$cfg->PASSWD_CHARS = 6;
	} elseif ($cfg->PASSWD_CHARS > 30) {
		$cfg->PASSWD_CHARS = 30;
	}

	if ($passwordLength < $cfg->PASSWD_CHARS) {
		logoutReseller();
		exit(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => sprintf('Password is shorter than %s characters.', $cfg->PASSWD_CHARS)
			)
		)
		);
	} elseif ($passwordLength > 30) {
		logoutReseller();
		exit(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => 'Password cannot be greater than 30 characters.'
			)
		)
		);
	}

	if (!empty($unallowedChars) && preg_match($unallowedChars, $password)) {
		logoutReseller();
		exit(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => 'Password includes not permitted signs.'
			)
		)
		);
	}

	if ($cfg->PASSWD_STRONG && !(preg_match('/[0-9]/', $password) && preg_match('/[a-zA-Z]/', $password))) {
		logoutReseller();
		exit(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => sprintf(
					'Password must be at least %s character long and contain letters and numbers to be valid.',
					$cfg->PASSWD_CHARS
				)
			)
		)
		);
	}
}

/**
 * Create JSON message
 *
 * @param $inputData
 * @return string
 */
function createJsonMessage($inputData)
{
	return json_encode($inputData);
}

/**
 * Create user list
 *
 * @param $resellerId
 * @param $postData['reseller_name']
 * @return user list 
 */

function getUserList($resellerId, $resellerName)
{
	$query = '
		SELECT
			admin_name
		FROM
			admin
		WHERE
			created_by = ?
	';

		$stmt = exec_query($query, $resellerId);
	
	if (!$stmt->rowCount()) {
		exit(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => sprintf('No admin data available.')
			)
				)
		);
	} else {
		$result = $stmt->fetchAll();
		
		echo(
		createJsonMessage(
			array(
				'level' => 'Success',
				'message' => sprintf('User list for reseller %s successfully generated.', $resellerName),
				'data' => $result
			)
		)
		); 

	}
}

/**
 * Create mail account
 *
 * @param int $resellerId Reseller unique identifier
 * @param $domain Users domain name
 * @param $account Mailaccount name
 * @param $quota Mailbox quota
 * @param $newmailpass password for new mailaccount
 * @param $account_type Type of mailaccount 
 * @param $mail_forward Forwarding mailaddress
 * @return void
 */
function addMailAccount($resellerId, $domain, $account, $quota, $newmailpass, $account_type, $mail_forward)
{
	$db = iMSCP_Registry::get('db');
	$cfg = iMSCP_Registry::get('config');
	$auth = iMSCP_Authentication::getInstance();

	if (empty($domain) || empty($account) || empty($newmailpass) || $quota == '' || empty($account_type)) {
		logoutReseller();
		exit(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => 'Hello, no domain ('.$domain.'), Quota ('.$quota.'), users new email accountname ('.$account.'), email password ('.$newmailpass.') or account type ('.$account_type.') in post data available.'
			)
		)
		);
	}

	$domain = strtolower($domain);
	$domain = encode_idna($domain);
	$mailAccount = (isset($account)) ? clean_input($account) : '';
	$newEmail = (isset($account)) ? clean_input($account.'@'.$domain) : '';
	$newEmailPass = (isset($newmailpass)) ? clean_input($newmailpass) : '';
        $account_type = (isset($account_type)) ? clean_input($account_type) : 'normal_mail';
	$quota = (isset($quota)) ? clean_input($quota) : '0';
	$quota = $quota * 1024*1024;
	$forwardList = (isset($mail_forward)) ? clean_input($mail_forward) : '';;

	$query = '
		SELECT
			domain_id, 
			domain_admin_id
		FROM
			domain
		WHERE
			domain_name = ?
	';
	$stmt = exec_query($query, $domain);
	$domainId = $stmt->fields['domain_id'];
        $domainAdminId = $stmt->fields['domain_admin_id'];

        $stmt = exec_query("SELECT `mail_id` FROM `mail_users` WHERE `mail_addr` = ?", $newEmail);
	if ($stmt->rowCount()) {
	logoutReseller();
        exit(
        createJsonMessage(
                array(
                        'level' => 'Error',
                        'message' => sprintf('Mailaddress: %s already in use.', $newEmail)
                )
        )
        );
	
	}

	if (($account_type == 'normal_forward' || $account_type == 'normal_mail,normal_forward') && empty($mail_forward)) {
		logoutReseller();
		exit(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => sprintf('Please add a forward address for the mailaddress: %s', $newEmail)
				)
			)
		);
	}

        $domainProperties = get_domain_default_props($domainAdminId);
        $domainQuota = $domainProperties['mail_quota'];
	$domainMails = $domainProperties['domain_mailacc_limit'];
	
	$stmt = exec_query("SELECT `mail_id` FROM `mail_users` WHERE `domain_id` = ?", $domainId);
	$domainCurrentAccounts = $stmt->rowCount();

	if($domainMails <= $domainCurrentAccounts && $domainMails > '0'){
		logoutReseller();
        	exit(
        	createJsonMessage(
                	array(
                        	'level' => 'Error',
                        	'message' => sprintf('Cannot add account: %s - You have already used all available Mailaccounts.', $newEmail)
                	)
        	)
        	);


	}
	
	$stmt = exec_query("SELECT SUM(`quota`) AS `quota` FROM `mail_users` WHERE `domain_id` = ? AND quota IS NOT NULL", $domainId);
	$domainCurrentQuota = $stmt->fields['quota'];

	if($domainQuota < $domainCurrentQuota + $quota && $domainQuota > '0'){
		logoutReseller();
        	exit(
        	createJsonMessage(
                	array(
                        	'level' => 'Error',
                        	'message' => sprintf('Cannot add account: %s - Not enough quota left.', $newEmail)
                	)
        	)
        	);


	}


	try {
		$db->beginTransaction();
		iMSCP_Events_Manager::getInstance()->dispatch(
			iMSCP_Events::onBeforeAddMail, 
			array(
				'mailUsername' => $account, 
				'MailAddress' => $newEmail
			)
		);
		
		$query = '
				INSERT INTO `mail_users` (
					`mail_acc`, `mail_pass`, `mail_forward`, `domain_id`, `mail_type`, `sub_id`, `status`,
					`mail_auto_respond`, `mail_auto_respond_text`, `quota`, `mail_addr`
				) VALUES
					(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
			';
			exec_query(
				$query,
				array(
					$account, $newEmailPass, $forwardList, $domainId, $account_type, '0',
					'toadd', '0', NULL, $quota, $newEmail
				)
			);
		$recordId = $db->insertId();

		iMSCP_Events_Manager::getInstance()->dispatch(
			iMSCP_Events::onAfterAddMail,
			array('
				mailUsername' => $account, 
				'mailAddress' => $newEmail, 
				'mailId' => $recordId)
		);
		
		send_request();

		write_log(
			sprintf(
				"%s add Mail: %s (for domain: %s) via remote bridge.",
				decode_idna($auth->getIdentity()->admin_name), $newEmail, $domain
			),
			E_USER_NOTICE
		);

		$db->commit();

	} catch (iMSCP_Exception_Database $e) {
		$db->rollBack();
		echo(
		createJsonMessage(
			array(
				'level' => 'Error',
				'message' => sprintf(
					'Error while creating New Mail: %s, $s, %s', $e->getMessage(), $e->getQuery(), $e->getCode()
				)
			)
		)
		);
		logoutReseller();
		exit;
	}

	echo(
	createJsonMessage(
		array(
			'level' => 'Success',
			'message' => sprintf('New email address %s added successfull.', $newEmail)
		)
	)
	);
}
