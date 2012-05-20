<?php

//error_reporting(E_ALL);

define('WHMCS_IMSCP_TIMEOUT',		1000);
define('WHMCS_IMSCP_SERVER_PORT',	80);

function imscp_ConfigOptions(){
	$configarray = array(
		"Package Name" => array( "Type" => "text", "Size" => "25", ),
	);
	return $configarray;
}

function imscp_CreateAccount($data){

	$accData = array(
		'action'		=> 'create',

		//'admin_id'
		'admin_name'	=> $data['domain'],
		'admin_pass'	=> $data['password'],
		//'admin_type'
		//'domain_created'
		'customer_id'	=> $data['clientsdetails']['userid'],
		//'created_by'
		'fname'			=> $data['clientsdetails']['firstname'],
		'lname'			=> $data['clientsdetails']['lastname'],
		//'gender'
		'firm'			=> $data['clientsdetails']['companyname'],
		'zip'			=> $data['clientsdetails']['postcode'],
		'city'			=> $data['clientsdetails']['city'],
		'state'			=> $data['clientsdetails']['state'],
		'country'		=> $data['clientsdetails']['countryname'],
		'email'			=> $data['clientsdetails']['email'],
		'phone'			=> $data['clientsdetails']['phonenumber'],
		'street1'		=> $data['clientsdetails']['address1'],
		'street2'		=> $data['clientsdetails']['address2'],
		//'uniqkey'
		//'uniqkey_time'


		'super_user'	=> $data['serverusername'],
		'super_pass'	=> $data['serverpassword'],

		'domain'		=> $data['domain'],
		'hpName'		=> $data['configoption1'],
	);

	if(!$data['clientsdetails']['email']){
		return 'Error: User does not have email set';
	}

	$result = imscp_send_request($data['serverip'], $accData);

	if(strpos($result, 'success') !== false) {
		$table = 'tblhosting';
		$array = array('username' => $data[domain]);
		$where = array('id' => $data[serviceid]);
		update_query($table, $array, $where);
		return 'success';
	}

	return $result;
}

function imscp_TerminateAccount($data){

	$accData = array(
		'action'		=> 'Terminate',

		'super_user'	=> $data['serverusername'],
		'super_pass'	=> $data['serverpassword'],

		'domain'		=> $data['domain'],
	);

	$result = imscp_send_request($data['serverip'], $accData);

	if(strpos($result, 'success') !== false) {
		return 'success';
	}

	return $result;

}

function imscp_SuspendAccount($data){

	$accData = array(
		'action'		=> 'Suspend',

		'super_user'	=> $data['serverusername'],
		'super_pass'	=> $data['serverpassword'],

		'domain'		=> $data['domain'],
	);

	$result = imscp_send_request($data['serverip'], $accData);

	if(strpos($result, 'success') !== false) {
		return 'success';
	}

	return $result;

}

function imscp_UnsuspendAccount($data){

	$accData = array(
		'action'		=> 'Unsuspend',

		'super_user'	=> $data['serverusername'],
		'super_pass'	=> $data['serverpassword'],

		'domain'		=> $data['domain'],
	);

	$result = imscp_send_request($data['serverip'], $accData);

	if(strpos($result, 'success') !== false) {
		return 'success';
	}

	return $result;

}

/*function imscp_ChangePassword($params) {

	# Code to perform action goes here...

	if ($successful) {
		$result = "success";
	} else {
		$result = "Error Message Goes Here...";
	}
	return $result;

}*/

/*function imscp_ChangePackage($params) {

	# Code to perform action goes here...

	if ($successful) {
		$result = "success";
	} else {
		$result = "Error Message Goes Here...";
	}
	return $result;

}*/


function imscp_ClientArea($data){
	return '
		<form action="http://'.$data['serverip'].'/" method="post" target="_blank">
			<input type="hidden" name="uname" value="'.$data['domain'].'" />
			<input type="hidden" name="upass" value="'.$data['password'].'" />
			<input type="submit" value="Login to iMSCP CP" />
			<input type="button" value="Login to Webmail" onClick="window.open(\'http://'.$data['serverip'].'/tools/webmail\')" />
		</form>';
}

function imscp_AdminLink($data){
	return '
		<form action="http://'.$data['serverip'].'" method="post" target="_blank">
			<input type="hidden" name="uname" value="'.$data['serverusername'].'" />
			<input type="hidden" name="upass" value="'.$data['serverpassword'].'" />
			<input type="submit" value="Login to '.$data['serverhostname'].' iMSCP CP" />
		</form>';
}

function imscp_LoginLink($data){
	echo '<a href="http://'.$data['serverip'].'" target="_blank">Login to iMSCP CP</a>';
}

/**
 * Send request to iMSCP side of bridge.
 *
 * @param string $ip Target IP of server where iMSCP is installed
 * @param array $getdata HTTP GET Data ex: array('var1' => 'val1', 'var2' => 'val2')
 * @param array $postdata HTTP POST Data ie. array('var1' => 'val1', 'var2' => 'val2')
 * @return string respond text without headers
 */
function imscp_send_request($ip, $post_arr = array()){
	$rv = '';
	$post_str = '';

	foreach ($post_arr as $var => $value){
		$post_str .= urlencode($var) .'='. urlencode($value) .'&';
	}

	$req = 'POST  /bridge.php HTTP/1.1' . "\r\n";
	$req .= "Host: $ip\r\n";
	$req .= "User-Agent: Mozilla/5.0 Firefox/3.6.12\r\n";
	$req .= "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n";
	$req .= "Accept-Language: en-us,en;q=0.5\r\n";
	$req .= "Accept-Encoding: deflate\r\n";
	$req .= "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7\r\n";

	$post_str = substr($post_str, 0, -1);
	$req .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$req .= 'Content-Length: '. strlen($post_str) . "\r\n\r\n";
	$req .= $post_str;

	if (($fp = @fsockopen($ip, WHMCS_IMSCP_SERVER_PORT, $errno, $errstr)) == false){
		return "Error: Can not connect to $ip:". WHMCS_IMSCP_SERVER_PORT ."\n$errno: $errstr\n";
	}

	stream_set_timeout($fp, 0, WHMCS_IMSCP_TIMEOUT * 1000);

	fputs($fp, $req);
	while ($line = fgets($fp)){
		$rv .= $line;
	}
	fclose($fp);

	$rv = substr($rv, strpos($rv, "\r\n\r\n") + 4);
	if(!$rv){
		$rv = "Error: Unknown error\n";
	}

	return $rv;
}

/*function imscp_reboot($params) {

	# Code to perform reboot action goes here...

	if ($successful) {
		$result = "success";
	} else {
		$result = "Error Message Goes Here...";
	}
	return $result;

}*/

/*function imscp_shutdown($params) {

	# Code to perform shutdown action goes here...

	if ($successful) {
		$result = "success";
	} else {
		$result = "Error Message Goes Here...";
	}
	return $result;

}*/

/*function imscp_ClientAreaCustomButtonArray() {
	$buttonarray = array(
		"Reboot Server" => "reboot",
	);
	return $buttonarray;
}*/

/*function imscp_AdminCustomButtonArray() {
	$buttonarray = array(
		"Reboot Server" => "reboot",
		"Shutdown Server" => "shutdown",
	);
	return $buttonarray;
}*/

/*function imscp_extrapage($params) {
	$pagearray = array(
		'templatefile' => 'example',
		'breadcrumb' => ' > <a href="#">Example Page</a>',
		'vars' => array(
			'var1' => 'demo1',
			'var2' => 'demo2',
		 ),
	);
	return $pagearray;
}*/

/*function imscp_UsageUpdate($params) {

	$serverid = $params['serverid'];
	$serverhostname = $params['serverhostname'];
	$serverip = $params['serverip'];
	$serverusername = $params['serverusername'];
	$serverpassword = $params['serverpassword'];
	$serveraccesshash = $params['serveraccesshash'];
	$serversecure = $params['serversecure'];

	# Run connection to retrieve usage for all domains/accounts on $serverid

	# Now loop through results and update DB

	foreach ($results AS $domain=>$values) {
		update_query(
			"tblhosting",
			array(
				"diskused"=>$values['diskusage'],
				"dislimit"=>$values['disklimit'],
				"bwused"=>$values['bwusage'],
				"bwlimit"=>$values['bwlimit'],
				"lastupdate"=>"now()",
			),
			array(
				"server"=>$serverid,
				"domain"=>$values['domain']
			)
		);
	}

}*/

/*function imscp_AdminServicesTabFields($params) {

	$result = select_query("mod_customtable","",array("serviceid"=>$params['serviceid']));
	$data = mysql_fetch_array($result);
	$var1 = $data['var1'];
	$var2 = $data['var2'];
	$var3 = $data['var3'];
	$var4 = $data['var4'];

	$fieldsarray = array(
		'Field 1' => '<input type="text" name="modulefields[0]" size="30" value="'.$var1.'" />',
		'Field 2' => '<select name="modulefields[1]"><option>Val1</option</select>',
		'Field 3' => '<textarea name="modulefields[2]" rows="2" cols="80">'.$var3.'</textarea>',
		'Field 4' => $var4, # Info Output Only
	);
	return $fieldsarray;

}*/

/*function imscp_AdminServicesTabFieldsSave($params) {
	update_query(
		"mod_customtable",array(
			"var1"=>$_POST['modulefields'][0],
			"var2"=>$_POST['modulefields'][1],
			"var3"=>$_POST['modulefields'][2],
		),
		array("serviceid"=>$params['serviceid'])
	);
}*/
