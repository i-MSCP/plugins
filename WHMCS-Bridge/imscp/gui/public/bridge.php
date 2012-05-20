<?php
define('iMSCP_Min_Vers', '1.0.1.6');
define('iMSCP_Max_Vers', '1.0.3.0');

require 'imscp-lib.php';
//error_reporting(E_ALL);

testVersion();
$reseller_id = testReseller();

switch($_POST['action']){
	case 'create':
		$hp = testHP($reseller_id);
		$ip = testIP($reseller_id);
		createUser($reseller_id, $hp, $ip);
		break;
	case 'Terminate':
		deleteUser();
		break;
	case 'Suspend':
		disableUser();
		break;
	case 'Unsuspend':
		enableUser();
		break;
	case 'create':
		createUser();
		break;
	default:
		exit("Error: action $_POST[action] is not implemented");
}

function deleteUser(){
	$domain_name	= encode_idna(urldecode($_POST['domain']));

	$query = "
		SELECT
			`domain_id`,
			`domain_name`
		FROM
			`domain`
		WHERE
			`domain_name` = ?
	";

	$rs = exec_query($query, $domain_name);

	if (!$rs->fields['domain_id']){
		exit("Error: no such domain $domain_name");
	}

	$_SESSION['user_logged'] = 'WHMCS';

	echo 'success';

	delete_domain($rs->fields['domain_id']);
}

function enableUser(){
	$domain_name	= encode_idna(urldecode($_POST['domain']));

	$query = "
		SELECT
			`domain_id`,
			`domain_name`
		FROM
			`domain`
		WHERE
			`domain_name` = ?
	";

	$rs = exec_query($query, $domain_name);

	if (!$rs->fields['domain_id']){
		exit("Error: no such domain $domain_name");
	}

	$_SESSION['user_logged'] = 'WHMCS';

	echo 'success';

	change_domain_status($rs->fields['domain_id'], $domain_name, 'enable', '');
}

function disableUser(){
	$domain_name	= encode_idna(urldecode($_POST['domain']));

	$query = "
		SELECT
			`domain_id`,
			`domain_name`
		FROM
			`domain`
		WHERE
			`domain_name` = ?
	";

	$rs = exec_query($query, $domain_name);

	if (!$rs->fields['domain_id']){
		exit("Error: no such domain $domain_name");
	}

	$_SESSION['user_logged'] = 'WHMCS';

	echo 'success';

	change_domain_status($rs->fields['domain_id'], $domain_name, 'disable', '');
}

function createUser($reseller_id, $hp, $ip){

	$db					= iMSCP_Registry::get('db');
	$cfg				= iMSCP_Registry::get('config');

	$createDefaultMail	= true;

	$pure_user_pass		= urldecode($_POST['admin_pass']);
	$admin_name			= encode_idna(urldecode($_POST['admin_name']));
	$admin_pass			= crypt_user_pass($pure_user_pass);
	$admin_type			= 'user';
	//$domain_created	= time();
	$created_by			= $reseller_id;
	$fname				= clean_input(urldecode($_POST['fname']));
	$lname				= clean_input(urldecode($_POST['lname']));
	$firm				= clean_input(urldecode($_POST['firm']));
	$zip				= clean_input(urldecode($_POST['zip']));
	$city				= clean_input(urldecode($_POST['city']));
	$state				= clean_input(urldecode($_POST['state']));
	$country			= clean_input(urldecode($_POST['country']));
	$email				= clean_input(urldecode($_POST['email']));
	$phone				= clean_input(urldecode($_POST['phone']));
	$fax				='';
	$street1			= clean_input(urldecode($_POST['street1']));
	$street2			= clean_input(urldecode($_POST['street2']));
	$customer_id		= clean_input(urldecode($_POST['customer_id']));
	$gender				= 'U';
	$domain_name		= encode_idna(urldecode($_POST['domain']));

	$query = "
		SELECT
			COUNT(*) AS `cnt`
		FROM
			`mail_users`
		WHERE
			`mail_addr` IN ( ?, ? ,?)
	";
	$rs = exec_query($query, array("webmaster@$domain_name", "abuse@$domain_name", "postmaster@$domain_name"));

	if ($rs->fields['cnt'] != 0 ){
		$createDefaultMail = false;
	}

	$pdo = iMSCP_Database::getRawInstance();

	try {
		$pdo->beginTransaction();

		$query = "
			INSERT INTO
				`admin`
			(
				`admin_name`, `admin_pass`, `admin_type`, `domain_created`,
				`created_by`, `fname`, `lname`, `firm`, `zip`, `city`, `state`,
				`country`, `email`, `phone`, `fax`, `street1`, `street2`,
				`customer_id`, `gender`
			) VALUES (
				?, ?, ?, unix_timestamp(),
				?, ?, ?, ?, ?, ?, ?,
				?, ?, ?, ?, ?, ?,
				?, ?
			)
		";

		exec_query(
			$query,
			array(
				$admin_name, $admin_pass, $admin_type, /*$domain_created,*/
				$created_by, $fname, $lname, $firm, $zip, $city, $state,
				$country, $email, $phone, $fax, $street1, $street2,
				$customer_id, $gender
			)
		);

		$domain_admin_id				= $db->insertId();
		$domain_created_id				= $reseller_id;
		$domain_created					= time();
		$domain_expires					= 0;
		$domain_mailacc_limit			= $hp['hp_mail'];
		$domain_ftpacc_limit			= $hp['hp_ftp'];
		$domain_traffic_limit			= $hp['hp_traff'];
		$domain_sqld_limit				= $hp['hp_sql_db'];
		$domain_sqlu_limit				= $hp['hp_sql_user'];
		$domain_status					= $cfg->ITEM_ADD_STATUS;
		$domain_subd_limit				= $hp['hp_sub'];
		$domain_alias_limit				= $hp['hp_als'];
		$domain_ip_id					= $ip;
		$domain_disk_limit				= $hp['hp_disk'];
		$domain_disk_usage				= 0;
		$domain_php						= preg_replace("/\_/", '', $hp['hp_php']);
		$domain_cgi						= preg_replace("/\_/", '', $hp['hp_cgi']);
		$allowbackup					= preg_replace("/\_/", '', $hp['hp_backup']);
		$domain_dns						= preg_replace("/\_/", '', $hp['hp_dns']);
		$domain_software_allowed		= preg_replace("/\_/", '', $hp['hp_allowsoftware']);
		$phpini_perm_system				= $hp['phpini_system'];
		$phpini_perm_register_globals	= $hp['phpini_al_register_globals'];
		$phpini_perm_allow_url_fopen	= $hp['phpini_al_allow_url_fopen'];
		$phpini_perm_display_errors		= $hp['phpini_al_display_errors'];
		$phpini_perm_disable_functions	= $hp['phpini_al_disable_functions'];

		$query = "
			INSERT INTO
				`domain` (
					`domain_name`, `domain_admin_id`, `domain_created_id`, `domain_created`,
					`domain_expires`, `domain_mailacc_limit`, `domain_ftpacc_limit`,
					`domain_traffic_limit`, `domain_sqld_limit`, `domain_sqlu_limit`,
					`domain_status`, `domain_subd_limit`, `domain_alias_limit`,
					`domain_ip_id`, `domain_disk_limit`, `domain_disk_usage`,
					`domain_php`, `domain_cgi`, `allowbackup`, `domain_dns`,
					`domain_software_allowed`, `phpini_perm_system`, `phpini_perm_register_globals`,
					`phpini_perm_allow_url_fopen`, `phpini_perm_display_errors`, `phpini_perm_disable_functions`
				) VALUES (
					?, ?, ?, ?,
					?, ?, ?,
					?, ?, ?,
					?, ?, ?,
					?, ?, ?,
					?, ?, ?, ?,
					?, ?, ?,
					?, ?, ?
				)
		";

		exec_query(
			$query,
			array(
				$domain_name, $domain_admin_id, $domain_created_id, $domain_created,
				$domain_expires, $domain_mailacc_limit, $domain_ftpacc_limit,
				$domain_traffic_limit, $domain_sqld_limit, $domain_sqlu_limit,
				$domain_status, $domain_subd_limit, $domain_alias_limit,
				$domain_ip_id, $domain_disk_limit, $domain_disk_usage,
				$domain_php, $domain_cgi, $allowbackup, $domain_dns,
				$domain_software_allowed, $phpini_perm_system, $phpini_perm_register_globals,
				$phpini_perm_allow_url_fopen, $phpini_perm_display_errors, $phpini_perm_disable_functions
			)
		);

		$dmn_id = $db->insertId();

		if ($phpini_perm_system == 'yes') {
			$phpini = iMSCP_PHPini::getInstance();

			$phpini->setData('phpiniSystem', 'yes');
			$phpini->setData('phpiniPostMaxSize', $hp['phpini_post_max_size']);
			$phpini->setData('phpiniUploadMaxFileSize', $hp['phpini_upload_max_filesize']);
			$phpini->setData('phpiniMaxExecutionTime', $hp['phpini_max_execution_time']);
			$phpini->setData('phpiniMaxInputTime', $hp['phpini_max_input_time']);
			$phpini->setData('phpiniMemoryLimit', $hp['phpini_memory_limit']);

			$phpini->saveCustomPHPiniIntoDb($dmn_id);
		}

		$query = "
			INSERT INTO
				`htaccess_users` (
					`dmn_id`, `uname`, `upass`, `status`
				) VALUES (
					?, ?, ?, ?
				)
		";

		exec_query(
			$query,
			array(
				$dmn_id, $domain_name, crypt_user_pass_with_salt($pure_user_pass), $cfg->ITEM_ADD_STATUS
			)
		);

		$user_id = $db->insertId();

		$query = "
			INSERT INTO
				`htaccess_groups` (
					`dmn_id`, `ugroup`, `members`, `status`
				) VALUES (
					?, ?, ?, ?
				)
		";

		exec_query(
			$query,
			array(
				$dmn_id,
				$cfg->AWSTATS_GROUP_AUTH,
				$user_id,
				$cfg->ITEM_ADD_STATUS
			)
		);

		// Create default addresses if needed
		if ($cfg->CREATE_DEFAULT_EMAIL_ADDRESSES && $createDefaultMail) {
			$_SESSION['user_email'] = $email;
			client_mail_add_default_accounts($dmn_id, $email, $domain_name);
		} else {
			$query = "
				UPDATE
					`mail_users`
				SET
					`domain_id` = ?
				WHERE
					`mail_addr` IN ( ?, ? ,?)
			";
			$rs = exec_query($query, array($dmn_id, "webmaster@$domain_name", "abuse@$domain_name", "postmaster@$domain_name"));
		}

		$user_def_lang = $cfg->USER_INITIAL_LANG;
		$user_theme_color = $cfg->USER_INITIAL_THEME;

		$query = "
			INSERT INTO
				`user_gui_props`
			(
				`user_id`, `lang`, `layout`
			) VALUES (
				?, ?, ?
			)
		";

		exec_query($query, array($domain_admin_id, $user_def_lang, $user_theme_color));

		send_request();

		write_log("WHCMS: add user: $domain_name (for domain $domain_name)", E_USER_NOTICE);
		write_log("WHCMS: add domain: $domain_name", E_USER_NOTICE);

		update_reseller_c_props($reseller_id);

		$pdo->commit();

	} catch (Exception $e) {
		$pdo->rollBack();
		exit("Error while creating user: ".($e->getMessage() ? $e->getMessage() :'unknown'));
	}

	echo 'success';
}

function testIP($reseller_id){
	$query = "SELECT * FROM `reseller_props` WHERE `reseller_id` = ?";
	$stmt = exec_query($query, $reseller_id);
	$data = $stmt->fetchRow();
	if(!$data){
		exit("Error: reseller does not have any ip assigned");
	}
	$ips = explode(';', $data['reseller_ips']);
	if(array_key_exists('0', $ips)){
		return $ips[0];
	} else {
		exit("Error: Can not retrieve reseller ip");
	}
}

function testVersion(){
	$cfg = iMSCP_Registry::get('config');
	if(version_compare($cfg->Version, iMSCP_Min_Vers, '<') ||version_compare($cfg->Version, iMSCP_Max_Vers, '>')){
		exit("Error: iMSCP version $cfg->Version is not compatible with this bridge. Check <a href='http://i-mscp.net'>developer site</a> for newer versions");
	}
}

function testReseller(){
	// Purge expired session
	do_session_timeout();

	$auth = iMSCP_Authentication::getInstance();

	// Init login process
	init_login($auth->events());

	if (!empty($_POST['super_user']) && !empty($_POST['super_pass'])) {
		$result = $auth
			->setUsername(idn_to_ascii(clean_input($_POST['super_user'])))
			->setPassword(clean_input($_POST['super_pass']))->authenticate();

		if (!$result->isValid()) {
			if(($messages = $result->getMessages())) {
				exit(format_message($result->getMessages()));
			}
		}
	} else {
		exit(tr('Login data is missing.'));
	}
	return $_SESSION['user_id'];

}

function testHP($reseller_id){
	$cfg = iMSCP_Registry::get('config');

	$hpName = urldecode($_POST['hpName']);

	if($cfg->HOSTING_PLANS_LEVEL === 'admin'){
		$query = "SELECT * FROM `hosting_plans` WHERE `name` = ?";
		$param = array($hpName);
	} else {
		$query = "SELECT * FROM `hosting_plans` WHERE `name` = ? AND `reseller_id` = ?";
		$param = array($hpName, $reseller_id);
	}

	$stmt = exec_query($query, $param);
	$data = $stmt->fetchRow();
	$props = $data['props'];
	if(!$data){
		exit("Error: No such hosting plan named $hpName");
	}
	$result =  array_combine(
		array(
			'hp_php', 'hp_cgi', 'hp_sub', 'hp_als', 'hp_mail', 'hp_ftp', 'hp_sql_db',
			'hp_sql_user', 'hp_traff', 'hp_disk', 'hp_backup', 'hp_dns', 'hp_allowsoftware',
			'phpini_system', 'phpini_al_register_globals', 'phpini_al_allow_url_fopen',
			'phpini_al_display_errors', 'phpini_al_disable_functions', 'phpini_post_max_size',
			'phpini_upload_max_filesize', 'phpini_max_execution_time', 'phpini_max_input_time',
			'phpini_memory_limit'
		),
		array_pad(explode(';', $props), 23, 'no')
	);

	return $result;
}
