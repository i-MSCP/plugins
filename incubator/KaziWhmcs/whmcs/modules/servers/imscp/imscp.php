<?php
/**
 * i-MSCP KaziWhmcs plugin
 * Copyright (C) 2014 Laurent Declercq <l.declercq@nuxwin.com>
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
 * @param array $data Provisioning data
 * @return string
 */
function imscp_CreateAccount($data)
{
    if (empty($data['clientsdetails']['email'])) {
        return 'KaziWhmcs: Customer email is not set in WHMCS';
    }

    $ret = _imscp_sendRequest(
        $data['serverhostname'],
        array(
            'action' => 'create',
            'domain' => $data['domain'],
            'hp_name' => $data['configoption1'],
            'admin_name' => $data['domain'],
            'admin_pass' => $data['password'],
            'customer_id' => $data['clientsdetails']['userid'],
            'fname' => $data['clientsdetails']['firstname'],
            'lname' => $data['clientsdetails']['lastname'],
            'firm' => $data['clientsdetails']['companyname'],
            'zip' => $data['clientsdetails']['postcode'],
            'city' => $data['clientsdetails']['city'],
            'state' => $data['clientsdetails']['state'],
            'country' => $data['clientsdetails']['countryname'],
            'email' => $data['clientsdetails']['email'],
            'phone' => $data['clientsdetails']['phonenumber'],
            'street1' => $data['clientsdetails']['address1'],
            'street2' => $data['clientsdetails']['address2'],
            'reseller_name' => $data['serverusername'],
            'reseller_pass' => $data['serverpassword']
        )
    );

    if ($ret === 'success') {
        //update_query('tblhosting', array('username' => $data['domain']), array('id' => $data['serviceid']));
    }

    return $ret;
}

/**
 * Suspend the given customer account
 *
 * @param array $data Provisioning data
 * @return string
 */
function imscp_SuspendAccount($data)
{
    return _imscp_sendRequest(
        $data['serverhostname'],
        array(
            'action' => 'suspend',
            'reseller_name' => $data['serverusername'],
            'reseller_pass' => $data['serverpassword'],
            'domain' => $data['domain']
        )
    );
}

/**
 * Unsuspend the given customer account
 *
 * @param array $data Provisioning data
 * @return string
 */
function imscp_UnsuspendAccount($data)
{
    return _imscp_sendRequest(
        $data['serverhostname'],
        array(
            'action' => 'unsuspend',
            'reseller_name' => $data['serverusername'],
            'reseller_pass' => $data['serverpassword'],
            'domain' => $data['domain']
        )
    );
}

/**
 * Terminate the given customer account
 *
 * @param array $data Provisioning data
 * @return string
 */
function imscp_TerminateAccount($data)
{
    return _imscp_sendRequest(
        $data['serverhostname'],
        array(
            'action' => 'terminate',
            'reseller_name' => $data['serverusername'],
            'reseller_pass' => $data['serverpassword'],
            'domain' => $data['domain']
        )
    );
}

/**
 * Return admin form
 *
 * @param array $serverData Server data
 * @return string
 */
function imscp_AdminLink($serverData)
{
    if ($serverData['serversecure']) {
        $scheme = 'https://';
    } else {
        $scheme = 'http://';
    }

    $host = $serverData['serverhostname'];
    $username = htmlentities($serverData['serverusername'], ENT_QUOTES, 'UTF-8', false);
    $password = htmlentities($serverData['serverpassword'], ENT_QUOTES, 'UTF-8', false);

    return <<<EOT
<form action="$scheme$host" method="post" target="_blank">
    <input type="hidden" name="uname" value="$username" />
    <input type="hidden" name="upass" value="$password" />
    <input type="hidden" name="action" value="login">
    <input type="submit" value="Login to i-MSCP" />
</form>
EOT;
}

/**
 * Login link
 *
 * @param array $serverData Server data
 */
function imscp_LoginLink($serverData)
{
    if ($serverData['serversecure']) {
        $scheme = 'https://';
    } else {
        $scheme = 'http://';
    }

    echo <<<EOT
<a href="$scheme{$serverData['serverhostname']}" target="_blank">Login to i-MSCP</a>';
EOT;
}

/**
 * Client login and link
 *
 * @param array $serverData Server data
 * @return string
 */
function imscp_ClientArea($serverData)
{
    if ($serverData['serversecure']) {
        $scheme = 'https://';
    } else {
        $scheme = 'http://';
    }

    $host = $serverData['serverhostname'];
    $username = htmlentities($serverData['domain'], ENT_QUOTES, 'UTF-8', false);
    $password = htmlentities($serverData['serverpassword'], ENT_QUOTES, 'UTF-8', false);

    return <<<EOT
<form action="$scheme$host" method="post" target="_blank">
    <input type="hidden" name="uname" value="$username" />
    <input type="hidden" name="upass" value="$password" />
    <input type="hidden" name="action" value="login">
    <input type="submit" value="Login to i-MSCP CP" />
    <input type="button" value="Login to Webmail" onClick="window.open('$scheme$host/webmail')" />
</form>
EOT;
}

// Internal functions

/**
 * Send POST request to i-MSCP
 *
 * @param array $serverData Server data
 * @param array $postData POST data
 * @return string String indicating if the request is successful
 */
function _imscp_sendRequest(array $serverData, array $postData)
{
    $host = $serverData['serverhostname'];

    // Create stream context
    $context = stream_context_create();

    // Set SSL option if needed
    if ($serverData['serversecure']) {
        if (!stream_context_set_option($context, 'ssl', 'verify_peer', false)) {
            return 'Error: Unable to set sslverifypeer option';
        }

        if (!stream_context_set_option($context, 'ssl', 'allow_self_signed', true)) {
            return 'Error: Unable to set sslallowselfsigned option';
        }

        $port = 443;
    } else {
        $port = 80;
    }

    // Open socket connection
    $socket = @stream_socket_client(
        "tcp://$host:$port", $errno, $errstr, KAZIWHMCS_CONNECTION_TIMEOUT, STREAM_CLIENT_CONNECT, $context
    );

    if (!$socket) {
        @fclose($socket);
        return sprintf("KaziWhmcs: Unable to connect to server (%s:%s): %s - %s", $host, $port, $errno, $errstr);
    }

    // Set the stream timeout
    if (!stream_set_timeout($socket, KAZIWHMCS_READ_TIMEOUT)) {
        return 'KaziWhmcs: Unable to set the connection timeout';
    }

    // Enable encryption if needed
    if ($serverData['serversecure']) {
        if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_SSLv23_CLIENT)) {
            $errorString = '';

            if (extension_loaded('openssl')) {
                while (($sslError = openssl_error_string()) != false) {
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
    $headers = 'POST '. KAZIWHMCS_API_ENDPOINT .  " HTTP/1.1\r\n";
    $headers .= "Host: $host\r\n";
    $headers .= "Accept: text/html\r\n";
    if (function_exists('gzinflate')) {
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
    if (!@fwrite($socket, $request)) {
        @fclose($socket);
        return 'KaziWhmcs:: Unable to write request to server';
    }

    // Read response (headers only)
    $response = '';
    $gotStatus = false;

    while (($line = fgets($socket)) !== false) {
        $gotStatus = $gotStatus || (strpos($line, 'HTTP') !== false);

        if ($gotStatus) {
            $response .= $line;
            if (rtrim($line) === '') {
                break;
            }
        }
    }

    // check timeout
    if ($socket) {
        $info = stream_get_meta_data($socket);
        $timedOut = $info['timed_out'];
        if ($timedOut) {
            @fclose($socket);
            return 'KaziWhmcs: Read timed out after 2 seconds';
        }
    }

    try {
        $responseArr = _imscp_parseResponseFromString($response);
    } catch (Exception $e) {
        return sprintf('KaziWhmcs: Unable to parse response: %s', $e->getMessage());
    }

    $headers = $responseArr['headers'];
    $transferEncoding = isset($headers['transfer-encoding']) ? $headers['transfer-encoding'] : false;
    $contentLength = isset($headers['content-length']) ? $headers['content-length'] : false;

    if ($transferEncoding !== false) {
        if (stripos($transferEncoding, 'chunked')) {
            do {
                $line = fgets($socket);

                if ($socket) { // check timeout
                    $info = stream_get_meta_data($socket);
                    $timedOut = $info['timed_out'];
                    if ($timedOut) {
                        @fclose($socket);
                        return sprintf('KaziWhmcs: Read timed out after %s seconds', KAZIWHMCS_READ_TIMEOUT);
                    }
                }

                $chunk = $line;

                // Get the next chunk size
                $chunksize = trim($line);
                if (!ctype_xdigit($chunksize)) {
                    @fclose($socket);
                    return sprintf("KaziWhmcs: Invalid chunk size '%s' unable to read chunked body", $chunksize);
                }

                // Convert the hexadecimal value to plain integer
                $chunksize = hexdec($chunksize);

                // Read next chunk
                $readTo = ftell($socket) + $chunksize;

                do {
                    $currentPos = ftell($socket);
                    if ($currentPos >= $readTo) {
                        break;
                    }

                    $line = fread($socket, $readTo - $currentPos);
                    if ($line === false || strlen($line) === 0) {
                        if ($socket) { // check timeout
                            $info = stream_get_meta_data($socket);
                            $timedOut = $info['timed_out'];
                            if ($timedOut) {
                                @fclose($socket);
                                return sprintf('KaziWhmcs: Read timed out after %s seconds', KAZIWHMCS_READ_TIMEOUT);
                            }
                        }
                        break;
                    }

                    $chunk .= $line;

                } while (!feof($socket));

                $chunk .= fgets($socket);

                if ($socket) { // check timeout
                    $info = stream_get_meta_data($socket);
                    $timedOut = $info['timed_out'];
                    if ($timedOut) {
                        @fclose($socket);
                        return sprintf('KaziWhmcs: Read timed out after %s seconds', KAZIWHMCS_READ_TIMEOUT);
                    }
                }

                $response .= $chunk;
            } while ($chunksize > 0);
        } else {
            @fclose($socket);
            return sprintf("KaziWhmcs: Cannot handle '%s' transfer encoding", $transferEncoding);
        }
    } elseif ($contentLength !== false) { //  Else, if we got the content-length header, read this number of bytes
        $currentPos = ftell($socket);

        for ($readTo = $currentPos + $contentLength; $readTo > $currentPos; $currentPos = ftell($socket)) {
            $chunk = fread($socket, $readTo - $currentPos);
            if ($chunk === false || strlen($chunk) === 0) {
                if ($socket) { // check timeout
                    $info = stream_get_meta_data($socket);
                    $timedOut = $info['timed_out'];
                    if ($timedOut) {
                        @fclose($socket);
                        return sprintf('KaziWhmcs: Read timed out after %s seconds', KAZIWHMCS_READ_TIMEOUT);
                    }
                }
                break;
            }

            $response .= $chunk;

            // Break if the connection ended prematurely
            if (feof($socket)) {
                break;
            }
        }
    } else { // Fallback: just read the response until EOF
        do {
            $buffer = fread($socket, 8192);
            if ($buffer === false || strlen($buffer) === 0) {
                if ($socket) { // check timeout
                    $info = stream_get_meta_data($socket);
                    $timedOut = $info['timed_out'];
                    if ($timedOut) {
                        @fclose($socket);
                        return sprintf('KaziWhmcs: Read timed out after %s seconds', KAZIWHMCS_READ_TIMEOUT);
                    }
                }
                break;
            } else {
                $response .= $buffer;
            }
        } while (feof($socket) === false);

        @fclose($socket);
    }

    try {
        $response = _imscp_parseResponseFromString($response);
        return $response['body'];
    } catch (Exception $e) {
        return sprintf('KaziWhmcs: Unable to parse response: %s', $e->getMessage());
    }
}

/**
 * Parse response from the given string
 *
 * @param string $string
 * @return array response
 * @throws InvalidArgumentException
 */
function _imscp_parseResponseFromString($string)
{
    $response = array();
    $lines = explode("\r\n", $string);

    if (!is_array($lines) || count($lines) == 1) {
        $lines = explode("\n", $string);
    }

    $firstLine = array_shift($lines);

    $regex = '/^HTTP\/(?P<version>1\.[01]) (?P<status>\d{3})(?:[ ]+(?P<reason>.*))?$/';
    $matches = array();

    if (!preg_match($regex, $firstLine, $matches)) {
        throw new \InvalidArgumentException('Response status not found');
    }

    $response['version'] = $matches['version'];
    $response['status'] = $matches['status'];
    $response['reason_phrase'] = (isset($matches['reason']) ? $matches['reason'] : '');

    if (count($lines) == 0) {
        return $response;
    }

    $isHeader = true;
    $headers = $body = array();

    while ($lines) {
        $nextLine = array_shift($lines);

        if ($isHeader && $nextLine == '') {
            $isHeader = false;
            continue;
        }

        if ($isHeader) {
            $headers[] = $nextLine;
        } else {
            $body[] = $nextLine;
        }
    }

    if ($headers) {
        foreach ($headers as $header) {
            $header = explode(':', $header);
            $response['headers'][strtolower($header[0])] = $header[1];
        }
    }

    if ($body) {
        $body = implode("\r\n", $body);

        $contentEncoding = isset($response['headers']['content-encoding'])
            ? $response['headers']['content-encoding'] : false;

        if (
            $contentEncoding !== false &&
            (stripos($contentEncoding, 'gzip') !== false || stripos($contentEncoding, 'deflate') !== false)
        ) {
            $unzip = function ($data) {
                $offset = 0;

                if (substr($data, 0, 2) === "\x1f\x8b") {
                    $offset = 2;
                }

                if (substr($data, $offset, 1) === "\x08") {
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
/*
$response = _imscp_sendRequest(
    array(
        'serverhostname' => 'wheezy.nuxwin.com',
        'serversecure' => true
    ),
    array(
        'action' => 'create',
        'reseller_user' => 'reseller1',
        'reseller_password' => 'dummy2305',

        'domain' => 'new.domain.tld',
        'admin_name' => 'new.domain.tld',
        'admin_pass' => 'dummy2305',
        'email' => 'l.declercq@nuxwin.com'
    )
);

print $response;
*/
