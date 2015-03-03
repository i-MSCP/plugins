<?php
/**
 * i-MSCP ImscpBoxBilling plugin
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

/**
 * Class Server_Manager_Imscp
 */
class Server_Manager_Imscp extends Server_Manager
{
	const API_ENDPOINT = '/boxbilling';
	const CONNECTION_TIMEOUT = 5;
	const READ_TIMEOUT = 3;

	/**
	 * Initialization
	 *
	 * @throws Server_Exception
	 */
	public function init()
	{
		//error_reporting(0);
		//ini_set('display_errors', 1);

		if(empty($this->_config['host'])) {
			throw new Server_Exception('ImscpBoxBilling: Server hostname is not set');
		}

		if(empty($this->_config['username'])) {
			throw new Server_Exception('ImscpBoxBilling: Server username is not set');
		}

		if(empty($this->_config['password'])) {
			throw new Server_Exception('ImscpBoxBilling: Server password is not set');
		}
	}

	/**
	 * Get client login URL
	 *
	 * @return string
	 */
	public function getLoginUrl()
	{
		if($this->_config['secure']) {
			$scheme = 'https://';
		} else {
			$scheme = 'http://';
		}

		return $scheme . $this->_config['host'];
	}

	/**
	 * Get reseller login URL
	 *
	 * @return string
	 */
	public function getResellerLoginUrl()
	{
		if($this->_config['secure']) {
			$scheme = 'https://';
		} else {
			$scheme = 'http://';
		}

		return $scheme . $this->_config['host'];
	}

	/**
	 * Get form label
	 *
	 * @return array
	 */
	public static function getForm()
	{
		return array(
			'label' => 'i-MSCP Server Manager'
		);
	}

	/**
	 * Test connection
	 *
	 * @throws Server_Exception
	 * @return bool TRUE on success
	 */
	public function testConnection()
	{
		$ret = $this->doRequest(
			array(
				'reseller_name' => $this->_config['username'],
				'reseller_pass' => $this->_config['password'],
				'action' => 'testconnection'
			)
		);

		if($ret !== 'success') {
			throw new Server_Exception($ret);
		}

		return true;
	}

	/**
	 * Create account
	 *
	 * @throws Server_Exception
	 * @param Server_Account $serverAccount Server account
	 * @return bool TRUE on success
	 */
	public function createAccount(Server_Account $serverAccount)
	{
		$this->getLog()->info(sprintf('Creating account %s', $serverAccount->getDomain()));

		if($serverAccount->getReseller()) {
			throw new Server_Exception('ImscpBoxBilling: Only customer accounts can be created');
		}

		$client = $serverAccount->getClient();
		$package = $serverAccount->getPackage();

		// Override service username (i-MSCP is using domain as username)
		$this->overrideServiceUsername($client, $serverAccount->getDomain());
		$serverAccount->setUsername($serverAccount->getDomain());

		$ret = $this->doRequest(
			array(
				'reseller_name' => $this->_config['username'],
				'reseller_pass' => $this->_config['password'],
				'action' => 'create',
				'domain' => $serverAccount->getDomain(),
				'hp_name' => $package->getName(),
				'admin_name' => $serverAccount->getUsername(),
				'admin_pass' => $serverAccount->getPassword(),
				'email' => $client->getEmail(),
				'customer_id' => ($client->getId() != '') ? 'boxbilling_' . $client->getId() : '',
				'fname' => $client->getFullName(),
				'lname' => '',
				'firm' => $client->getCompany(),
				'street1' => $client->getAddress1(),
				'street2' => $client->getAddress2(),
				'city' => $client->getCity(),
				'state' => $client->getState(),
				'country' => $client->getCountry(),
				'zip' => $client->getZip(),
				'phone' => $client->getTelephone(),
				'fax' => $client->getFax()
			)
		);

		if($ret !== 'success') {
			throw new Server_Exception($ret);
		}

		return true;
	}

	/**
	 * Synchronize server account
	 *
	 * @throws Server_Exception
	 * @param Server_Account $serverAccount Server account
	 * @return Server_Account
	 */
	public function synchronizeAccount(Server_Account $serverAccount)
	{
		$this->getLog()->info(sprintf('Synchronizing account %s', $serverAccount->getDomain()));

		return clone $serverAccount;
	}

	/**
	 * Suspend account
	 *
	 * @throws Server_Exception
	 * @param Server_Account $serverAccount Server account
	 * @return bool TRUE on success
	 */
	public function suspendAccount(Server_Account $serverAccount)
	{
		$this->getLog()->info(sprintf('Suspending account %s', $serverAccount->getDomain()));

		$ret = $this->doRequest(
			array(
				'action' => 'suspend',
				'reseller_name' => $this->_config['username'],
				'reseller_pass' => $this->_config['password'],
				'domain' => $serverAccount->getDomain()
			)
		);

		if($ret !== 'success') {
			throw new Server_Exception($ret);
		}

		return true;
	}

	/**
	 * Unsuspend account
	 *
	 * @throws Server_Exception
	 * @param Server_Account $serverAccount Server account
	 * @return bool TRUE on success
	 */
	public function unsuspendAccount(Server_Account $serverAccount)
	{
		$this->getLog()->info(sprintf('Unsuspending account %s', $serverAccount->getDomain()));

		$ret = $this->doRequest(
			array(
				'action' => 'unsuspend',
				'reseller_name' => $this->_config['username'],
				'reseller_pass' => $this->_config['password'],
				'domain' => $serverAccount->getDomain()
			)
		);

		if($ret !== 'success') {
			throw new Server_Exception($ret);
		}

		return true;
	}

	/**
	 * Cancel (terminate) account
	 *
	 * @throws Server_Exception
	 * @param Server_Account $serverAccount Server account
	 * @return bool TRUE on success
	 */
	public function cancelAccount(Server_Account $serverAccount)
	{
		$this->getLog()->info(sprintf('Cancelling account %s', $serverAccount->getDomain()));

		$ret = $this->doRequest(
			array(
				'action' => 'cancel',
				'reseller_name' => $this->_config['username'],
				'reseller_pass' => $this->_config['password'],
				'domain' => $serverAccount->getDomain()
			)
		);

		if($ret !== 'success') {
			throw new Server_Exception($ret);
		}

		return true;
	}

	/**
	 * Change account password
	 *
	 * @throws Server_Exception
	 * @param Server_Account $serverAccount Server account
	 * @param string $newPassword New Password
	 * @return bool TRUE on success
	 */
	public function changeAccountPassword(Server_Account $serverAccount, $newPassword)
	{
		$this->getLog()->info(sprintf('Changing account password %s', $serverAccount->getDomain()));

		$ret = $this->doRequest(
			array(
				'action' => 'changepw',
				'reseller_name' => $this->_config['username'],
				'reseller_pass' => $this->_config['password'],
				'admin_name' => $serverAccount->getUsername(),
				'admin_pass' => $newPassword
			)
		);

		if($ret !== 'success') {
			throw new Server_Exception($ret);
		}

		return true;
	}

	/**
	 * Change account username
	 *
	 * @throws Server_Exception
	 * @param Server_Account $serverAccount Server account
	 * @param $newUsername New username
	 * @return bool TRUE on success
	 */
	public function changeAccountUsername(Server_Account $serverAccount, $newUsername)
	{
		throw new Server_Exception('ImscpBoxBilling: Action not allowed');
	}

	/**
	 * Change account domain
	 *
	 * @throws Server_Exception
	 * @param Server_Account $serverAccount Server account
	 * @param string $newDomain New domain
	 * @return bool TRUE on success
	 */
	public function changeAccountDomain(Server_Account $serverAccount, $newDomain)
	{
		throw new Server_Exception('ImscpBoxBilling: Action not allowed');
	}

	/**
	 * Change account IP address
	 *
	 * @throws Server_Exception
	 * @param Server_Account $serverAccount Server account
	 * @param string $newIpAddress New IP address
	 * @return bool TRUE on success
	 */
	public function changeAccountIp(Server_Account $serverAccount, $newIpAddress)
	{
		throw new Server_Exception('ImscpBoxBilling: Action not allowed');
	}

	/**
	 * Change account package (hosting plan)
	 *
	 * @throws Server_Exception
	 * @param Server_Account $serverAccount Server account
	 * @param Server_Package $serverPackage Server package
	 * @return bool TRUE on success
	 */
	public function changeAccountPackage(Server_Account $serverAccount, Server_Package $serverPackage)
	{
		throw new Server_Exception('ImscpBoxBilling: Action not allowed');
	}

	/**
	 * Override service username
	 *
	 * @throws Server_Exception
	 * @param Server_Client $client Server_Client
	 * @param string $newUsername New username
	 * @return void
	 */
	protected function overrideServiceUsername(Server_Client $client, $newUsername)
	{
		/** @var PDO $pdo */
		$pdo = Box_Db::getPdo();

		try {
			$stmt = $pdo->prepare(
				'
                  SELECT
                    co.client_id, co.config
                  FROM
                    client_order AS co
                  INNER JOIN
                    client AS c ON(c.id = co.client_id)
                  WHERE
                    c.email = :email
                '
			);
			$stmt->execute(array('email' => $client->getEmail()));

			if(!$stmt->rowCount()) {
				throw new Server_Exception('ImscpBoxBilling: Unable to retrieve order config');
			}

			$row = $stmt->fetch(PDO::FETCH_ASSOC);

			$config = json_decode($row['config'], true);
			$config['username'] = $newUsername;
			$config = json_encode($config);

			$stmt = $pdo->prepare(
				'
                  UPDATE
                    client_order AS co, service_hosting AS sh
                  SET
                    co.config = :config, sh.username = :username
                  WHERE
                    (co.client_id = :client_id OR sh.client_id = :client_id)
                '
			);
			$stmt->execute(array('config' => $config, 'username' => $newUsername, 'client_id' => $row['client_id']));
		} catch(PDOException $e) {
			throw new Server_Exception('ImscpBoxBilling: Unable to override service username');
		}
	}

	/**
	 * Send request to i-MSCP
	 *
	 * @param array $postData POST data
	 * @return string String indicating if the request is successful
	 */
	protected function doRequest(array $postData)
	{
		$host = $this->_config['host'];
		$ssl = $this->_config['secure'];

		// Create stream context
		$context = stream_context_create();

		// Set SSL option if needed
		if($ssl) {
			if(!stream_context_set_option($context, 'ssl', 'verify_peer', false)) {
				return 'ImscpBoxBilling: Unable to set sslverifypeer option';
			}

			if(!stream_context_set_option($context, 'ssl', 'allow_self_signed', true)) {
				return 'ImscpBoxBilling: Unable to set sslallowselfsigned option';
			}

			$port = 443;
		} else {
			$port = 80;
		}

		// Open socket connection
		$socket = @stream_socket_client(
			"$host:$port", $errno, $errstr, self::CONNECTION_TIMEOUT, STREAM_CLIENT_CONNECT, $context
		);

		if(!$socket) {
			@fclose($socket);
			return sprintf(
				"ImscpBoxBilling: Unable to connect to server (%s:%s); %s - %s", $host, $port, $errno, $errstr
			);
		}

		// Set the stream timeout
		if(!stream_set_timeout($socket, self::READ_TIMEOUT)) {
			return 'ImscpBoxBilling: Unable to set the connection timeout';
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
				return sprintf('ImscpBoxBilling: Unable to enable crypto on TCP connection %s%s', $host, $errorString);
			}
		}

		// Prepare request body
		$body = http_build_query($postData);

		// Prepare request headers
		$headers = 'POST ' . self::API_ENDPOINT . " HTTP/1.1\r\n";
		$headers .= "Host: $host\r\n";
		$headers .= "Accept: text/html\r\n";
		if(function_exists('gzinflate')) {
			$headers .= "Accept-Encoding: gzip, deflate\r\n";
		} else {
			$headers .= "Accept-Encoding: identity\r\n";
		}
		$headers .= "User-Agent: BoxBilling\r\n";
		$headers .= "Content-Type: application/x-www-form-urlencoded\r\n";
		$headers .= 'Content-Length: ' . strlen($body) . "\r\n";

		// Prepare request
		$request = "$headers\r\n$body";

		// Write request
		if(!@fwrite($socket, $request)) {
			@fclose($socket);
			return 'ImscpBoxBilling: Unable to write request to server';
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
				return sprintf('ImscpBoxBilling: Read timed out after %s seconds', self::READ_TIMEOUT);
			}
		}

		try {
			$responseArr = $this->parseResponseFromString($response);
		} catch(\Exception $e) {
			return sprintf('ImscpBoxBilling: Unable to parse response: %s', $e->getMessage());
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
							return sprintf('ImscpBoxBilling: Read timed out after %s seconds', self::READ_TIMEOUT);
						}
					}

					$chunk = $line;

					// Get the next chunk size
					$chunksize = trim($line);
					if(!ctype_xdigit($chunksize)) {
						@fclose($socket);
						return sprintf(
							"ImscpBoxBilling: Invalid chunk size '%s'; unable to read chunked body", $chunksize
						);
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
									return sprintf(
										'ImscpBoxBilling: Read timed out after %s seconds', self::READ_TIMEOUT
									);
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
							return sprintf('ImscpBoxBilling: Read timed out after %s seconds', self::READ_TIMEOUT);
						}
					}

					$response .= $chunk;
				} while($chunksize > 0);
			} else {
				@fclose($socket);
				return sprintf("ImscpBoxBilling: Cannot handle '%s' transfer encoding", $transferEncoding);
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
							return sprintf('ImscpBoxBilling: Read timed out after %s seconds', self::READ_TIMEOUT);
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
							return sprintf('ImscpBoxBilling: Read timed out after %s seconds', self::READ_TIMEOUT);
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
			$response = $this->parseResponseFromString($response);
			return $response['body'];
		} catch(\Exception $e) {
			return sprintf('ImscpBoxBilling: Unable to parse response; %s', $e->getMessage());
		}
	}

	/**
	 * Parse response from the given string
	 *
	 * @param string $string
	 * @return array response An array containing response (protocol version, status, reason phrase, headers and body)
	 * @throws InvalidArgumentException
	 */
	protected function parseResponseFromString($string)
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
}
