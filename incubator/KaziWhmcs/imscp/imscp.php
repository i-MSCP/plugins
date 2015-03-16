<?php
/**
 * i-MSCP KaziWhmcs plugin
 * Copyright (C) 2014-2015 Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */

error_reporting(0);
//ini_set('display_errors', 1);

define('KAZIWHMCS_API_ENDPOINT', '/kaziwhmcs');
define('KAZIWHMCS_CONNECTION_TIMEOUT', 5);
define('KAZIWHMCS_READ_TIMEOUT', 3);

/**
 * Get config options
 *
 * @return array Config options
 */
function imscp_ConfigOptions()
{
	return array('Package Name' => array('Type' => 'text', 'Size' => '30'));
}

/**
 * Create the given customer account
 *
 * @param array $params Module parameters
 * @return string
 */
function imscp_CreateAccount($params)
{
	if(empty($params['clientsdetails']['email'])) {
		return 'KaziWhmcs: Customer email is not set in WHMCS';
	}

	return _imscp_sendRequest(
		$params['serverhostname'],
		($params['serversecure'] == 'on') ? true : false,
		array(
			'reseller_name' => $params['serverusername'],
			'reseller_pass' => $params['serverpassword'],
			'action' => 'create',
			'domain' => $params['domain'],
			'hp_name' => $params['configoption1'],
			'admin_name' => $params['domain'],
			'admin_pass' => $params['password'],
			'customer_id' => 'whmcs_' . $params['clientsdetails']['userid'],
			'fname' => $params['clientsdetails']['firstname'],
			'lname' => $params['clientsdetails']['lastname'],
			'firm' => $params['clientsdetails']['companyname'],
			'zip' => $params['clientsdetails']['postcode'],
			'city' => $params['clientsdetails']['city'],
			'state' => $params['clientsdetails']['state'],
			'country' => $params['clientsdetails']['countryname'],
			'email' => $params['clientsdetails']['email'],
			'phone' => $params['clientsdetails']['phonenumber'],
			'street1' => $params['clientsdetails']['address1'],
			'street2' => $params['clientsdetails']['address2']
		)
	);
}

/**
 * Suspend the given customer account
 *
 * @param array $params Module parameters
 * @return string
 */
function imscp_SuspendAccount($params)
{
	return _imscp_sendRequest(
		$params['serverhostname'],
		($params['serversecure'] == 'on') ? true : false,
		array(
			'action' => 'suspend',
			'reseller_name' => $params['serverusername'],
			'reseller_pass' => $params['serverpassword'],
			'domain' => $params['domain']
		)
	);
}

/**
 * Unsuspend the given customer account
 *
 * @param array $params Module parameters
 * @return string
 */
function imscp_UnsuspendAccount($params)
{
	return _imscp_sendRequest(
		$params['serverhostname'],
		($params['serversecure'] == 'on') ? true : false,
		array(
			'action' => 'unsuspend',
			'reseller_name' => $params['serverusername'],
			'reseller_pass' => $params['serverpassword'],
			'domain' => $params['domain']
		)
	);
}

/**
 * Terminate the given customer account
 *
 * @param array $params Module parameters
 * @return string
 */
function imscp_TerminateAccount($params)
{
	return _imscp_sendRequest(
		$params['serverhostname'],
		($params['serversecure'] == 'on') ? true : false,
		array(
			'action' => 'terminate',
			'reseller_name' => $params['serverusername'],
			'reseller_pass' => $params['serverpassword'],
			'domain' => $params['domain']
		)
	);
}

/**
 * Update password of the given customer account
 *
 * @param array $params Module parameters
 * @return string
 */
function imscp_ChangePassword($params)
{
	return _imscp_sendRequest(
		$params['serverhostname'],
		($params['serversecure'] == 'on') ? true : false,
		array(
			'action' => 'changepw',
			'reseller_name' => $params['serverusername'],
			'reseller_pass' => $params['serverpassword'],
			'admin_name' => $params['username'],
			'admin_pass' => $params['password']
		)
	);
}

/**
 * Update usage stats
 *
 * @param array $params Module parameters
 * @return string
 */
function imscp_UsageUpdate($params)
{
	$response = _imscp_sendRequest(
		$params['serverhostname'],
		($params['serversecure'] == 'on') ? true : false,
		array(
			'action' => 'usageupdate',
			'reseller_name' => $params['serverusername'],
			'reseller_pass' => $params['serverpassword'],
		)
	);

	if(($usageUpdateData = unserialize($response)) !== false) {
		foreach($usageUpdateData as $data) {
			update_query(
				'tblhosting',
				array(
					'diskusage' => $data['diskusage'],
					'disklimit' => $data['disklimit'],
					'bwusage' => $data['bwusage'],
					'bwlimit' => $data['bwlimit'],
					'lastupdate' => 'now()'
				),
				array(
					'server' => $params['serverid'],
					'domain' => $data['domain']
				)
			);
		}

		return 'success';
	}

	return $response;
}

/**
 * Return admin form
 *
 * @param array $params Module parameters
 * @return string
 */
function imscp_AdminLink($params)
{
	if($params['serversecure'] == 'on') {
		$scheme = 'https://';
	} else {
		$scheme = 'http://';
	}

	$host = $params['serverhostname'];
	$username = htmlentities($params['serverusername'], ENT_QUOTES, 'UTF-8', false);
	$password = htmlentities($params['serverpassword'], ENT_QUOTES, 'UTF-8', false);

	return <<<EOT
<form action="$scheme$host" method="post" target="_blank">
    <input type="hidden" name="uname" value="$username" />
    <input type="hidden" name="upass" value="$password" />
    <input type="hidden" name="action" value="login">
    <input type="submit" value="Login to control panel" />
</form>
EOT;
}

/**
 * Login link
 *
 * @param array $params Module parameters
 * @return string
 */
function imscp_LoginLink($params)
{
	if($params['serversecure'] == 'on') {
		$scheme = 'https://';
	} else {
		$scheme = 'http://';
	}

	echo <<<EOT
<p><a href="$scheme{$params['serverhostname']}" target="_blank"><span>Go to control panel</span></a></p>
EOT;
}

/**
 * Client login and link
 *
 * @param array $params Module parameters
 * @return string
 */
function imscp_ClientArea($params)
{
	if($params['serversecure'] == 'on') {
		$scheme = 'https://';
	} else {
		$scheme = 'http://';
	}

	$host = $params['serverhostname'];
	$username = htmlentities($params['domain'], ENT_QUOTES, 'UTF-8', false);
	$password = htmlentities($params['password'], ENT_QUOTES, 'UTF-8', false);

	return <<<EOT
<form action="$scheme$host" method="post" target="_blank">
    <input type="hidden" name="uname" value="$username" />
    <input type="hidden" name="upass" value="$password" />
    <input type="hidden" name="action" value="login">
    <input type="submit" value="Control Panel" class="btn" />
    <input type="button" value="Filemanager" onClick="window.open('$scheme$host/ftp')" class="btn" />
    <input type="button" value="PhpMyAdmin" onClick="window.open('$scheme$host/pma')" class="btn" />
    <input type="button" value="Webmail" onClick="window.open('$scheme$host/webmail')" class="btn" />
</form>
EOT;
}

// Internal functions

/**
 * Send POST request to i-MSCP
 *
 * @param string $host i-MSCP server host name
 * @param bool $ssl Does the connection should be secured through SSL
 * @param array $postData POST data
 * @return string String indicating if the request is successful
 */
function _imscp_sendRequest($host, $ssl, array $postData)
{
	// Create stream context
	$context = stream_context_create();

	// Set SSL option if needed
	if($ssl) {
		if(!stream_context_set_option($context, 'ssl', 'verify_peer', false)) {
			return 'KaziWhmcs: Unable to set sslverifypeer option';
		}

		if(!stream_context_set_option($context, 'ssl', 'allow_self_signed', true)) {
			return 'KaziWhmcs: Unable to set sslallowselfsigned option';
		}

		if(strpos($host, ':') === false) {
			$host .= ':443';
		}
	} elseif(strpos($host, ':') === false) {
		$host .= ':80';
	}

	// Open socket connection
	$socket = @stream_socket_client(
		$host, $errno, $errstr, KAZIWHMCS_CONNECTION_TIMEOUT, STREAM_CLIENT_CONNECT, $context
	);

	if(!$socket) {
		@fclose($socket);
		return sprintf("KaziWhmcs: Unable to connect to server (%s); %s - %s", $host, $errno, $errstr);
	}

	// Set the stream timeout
	if(!stream_set_timeout($socket, KAZIWHMCS_READ_TIMEOUT)) {
		return 'KaziWhmcs: Unable to set the connection timeout';
	}

	// Enable encryption if needed
	if($ssl) {
		if(!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_SSLv23_CLIENT)) {
			$errorString = '';

			if(extension_loaded('openssl')) {
				while(($sslError = openssl_error_string()) != false) {
					$errorString .= "; SSL error: $sslError";
				}
			}

			@fclose($socket);
			return sprintf('KaziWhmcs: Unable to enable crypto on TCP connection %s%s', $host, $errorString);
		}
	}

	// Prepare request body
	$body = http_build_query($postData);

	// Prepare request headers
	$headers = 'POST ' . KAZIWHMCS_API_ENDPOINT . " HTTP/1.1\r\n";
	$headers .= "Host: $host\r\n";
	$headers .= "Accept: text/html\r\n";
	if(function_exists('gzinflate')) {
		$headers .= "Accept-Encoding: gzip, deflate\r\n";
	} else {
		$headers .= "Accept-Encoding: identity\r\n";
	}
	$headers .= "User-Agent: WHMCS\r\n";
	$headers .= "Content-Type: application/x-www-form-urlencoded\r\n";
	$headers .= 'Content-Length: ' . strlen($body) . "\r\n";

	// Prepare request
	$request = "$headers\r\n$body";

	// Write request
	if(!@fwrite($socket, $request)) {
		@fclose($socket);
		return 'KaziWhmcs: Unable to write request to server';
	}

	// Read response (headers only)
	$response = '';
	$gotStatus = false;

	while(($line = fgets($socket)) !== false) {
		$gotStatus = $gotStatus || (strpos($line, 'HTTP') !== false);

		if($gotStatus) {
			$response .= $line;
			if(rtrim($line) === '') {
				break;
			}
		}
	}

	// check timeout
	if($socket) {
		$info = stream_get_meta_data($socket);
		$timedOut = $info['timed_out'];
		if($timedOut) {
			@fclose($socket);
			return sprintf('KaziWhmcs: Read timed out after %s seconds', KAZIWHMCS_READ_TIMEOUT);
		}
	}

	try {
		$responseArr = _imscp_parseResponseFromString($response);
	} catch(\Exception $e) {
		return sprintf('KaziWhmcs: Unable to parse response: %s', $e->getMessage());
	}

	$headers = $responseArr['headers'];
	$transferEncoding = isset($headers['transfer-encoding']) ? $headers['transfer-encoding'] : false;
	$contentLength = isset($headers['content-length']) ? $headers['content-length'] : false;

	if($transferEncoding !== false) {
		if(stripos($transferEncoding, 'chunked')) {
			do {
				$line = fgets($socket);

				if($socket) { // check timeout
					$info = stream_get_meta_data($socket);
					$timedOut = $info['timed_out'];
					if($timedOut) {
						@fclose($socket);
						return sprintf('KaziWhmcs: Read timed out after %s seconds', KAZIWHMCS_READ_TIMEOUT);
					}
				}

				$chunk = $line;

				// Get the next chunk size
				$chunksize = trim($line);
				if(!ctype_xdigit($chunksize)) {
					@fclose($socket);
					return sprintf("KaziWhmcs: Invalid chunk size '%s'; unable to read chunked body", $chunksize);
				}

				// Convert the hexadecimal value to plain integer
				$chunksize = hexdec($chunksize);

				// Read next chunk
				$readTo = ftell($socket) + $chunksize;

				do {
					$currentPos = ftell($socket);
					if($currentPos >= $readTo) {
						break;
					}

					$line = fread($socket, $readTo - $currentPos);
					if($line === false || strlen($line) === 0) {
						if($socket) { // check timeout
							$info = stream_get_meta_data($socket);
							$timedOut = $info['timed_out'];
							if($timedOut) {
								@fclose($socket);
								return sprintf('KaziWhmcs: Read timed out after %s seconds', KAZIWHMCS_READ_TIMEOUT);
							}
						}
						break;
					}

					$chunk .= $line;

				} while(!feof($socket));

				$chunk .= fgets($socket);

				if($socket) { // check timeout
					$info = stream_get_meta_data($socket);
					$timedOut = $info['timed_out'];
					if($timedOut) {
						@fclose($socket);
						return sprintf('KaziWhmcs: Read timed out after %s seconds', KAZIWHMCS_READ_TIMEOUT);
					}
				}

				$response .= $chunk;
			} while($chunksize > 0);
		} else {
			@fclose($socket);
			return sprintf("KaziWhmcs: Cannot handle '%s' transfer encoding", $transferEncoding);
		}
	} elseif($contentLength !== false) { //  Else, if we got the content-length header, read this number of bytes
		$currentPos = ftell($socket);

		for($readTo = $currentPos + $contentLength; $readTo > $currentPos; $currentPos = ftell($socket)) {
			$chunk = fread($socket, $readTo - $currentPos);
			if($chunk === false || strlen($chunk) === 0) {
				if($socket) { // check timeout
					$info = stream_get_meta_data($socket);
					$timedOut = $info['timed_out'];
					if($timedOut) {
						@fclose($socket);
						return sprintf('KaziWhmcs: Read timed out after %s seconds', KAZIWHMCS_READ_TIMEOUT);
					}
				}
				break;
			}

			$response .= $chunk;

			// Break if the connection ended prematurely
			if(feof($socket)) {
				break;
			}
		}
	} else { // Fallback: just read the response until EOF
		do {
			$buffer = fread($socket, 8192);
			if($buffer === false || strlen($buffer) === 0) {
				if($socket) { // check timeout
					$info = stream_get_meta_data($socket);
					$timedOut = $info['timed_out'];
					if($timedOut) {
						@fclose($socket);
						return sprintf('KaziWhmcs: Read timed out after %s seconds', KAZIWHMCS_READ_TIMEOUT);
					}
				}
				break;
			} else {
				$response .= $buffer;
			}
		} while(feof($socket) === false);

		@fclose($socket);
	}

	try {
		$response = _imscp_parseResponseFromString($response);
		return $response['body'];
	} catch(\Exception $e) {
		return sprintf('KaziWhmcs: Unable to parse response; %s', $e->getMessage());
	}
}

/**
 * Parse response from the given string
 *
 * @param string $string
 * @return array response An array containing response (protocol version, status, reason phrase, headers and body)
 * @throws InvalidArgumentException
 */
function _imscp_parseResponseFromString($string)
{
	$response = array();
	$lines = explode("\r\n", $string);

	if(!is_array($lines) || count($lines) == 1) {
		$lines = explode("\n", $string);
	}

	$firstLine = array_shift($lines);

	$regex = '/^HTTP\/(?P<version>1\.[01]) (?P<status>\d{3})(?:[ ]+(?P<reason>.*))?$/';
	$matches = array();

	if(!preg_match($regex, $firstLine, $matches)) {
		throw new \InvalidArgumentException('Response status not found');
	}

	$response['version'] = $matches['version'];
	$response['status'] = $matches['status'];
	$response['reason_phrase'] = (isset($matches['reason']) ? $matches['reason'] : '');
	$response['headers'] = array();
	$response['body'] = '';

	if(count($lines) == 0) {
		return $response;
	}

	$isHeader = true;
	$headers = $body = array();

	while($lines) {
		$nextLine = array_shift($lines);

		if($isHeader && $nextLine == '') {
			$isHeader = false;
			continue;
		}

		if($isHeader) {
			$headers[] = $nextLine;
		} else {
			$body[] = $nextLine;
		}
	}

	if($headers) {
		foreach($headers as $header) {
			$header = explode(':', $header);
			$response['headers'][strtolower($header[0])] = $header[1];
		}
	}

	if($body) {
		$body = implode("\r\n", $body);

		$contentEncoding = isset($response['headers']['content-encoding'])
			? $response['headers']['content-encoding'] : false;

		if(
			$contentEncoding !== false &&
			(stripos($contentEncoding, 'gzip') !== false || stripos($contentEncoding, 'deflate') !== false)
		) {
			$unzip = function ($data) {
				$offset = 0;

				if(substr($data, 0, 2) === "\x1f\x8b") {
					$offset = 2;
				}

				if(substr($data, $offset, 1) === "\x08") {
					return gzinflate(substr($data, $offset + 8));
				}

				return $data;
			};

			$body = $unzip($body);
		}

		$response['body'] = $body;
	}

	return $response;
}
