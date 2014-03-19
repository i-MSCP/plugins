<?php
/**
 * ServerProvisioning plugin for i-MSCP
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
 *
 * @category    iMSCP_Plugin
 * @package     ServerProvisioning
 * @copyright   2014 by Laurent Declercq <l.declercq@nuxwin.com>
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/lgpl-2.1.txt LGPL v2.1
 */

namespace ServerProvisioning;

use DateTime;
use iMSCP_Authentication_Result as AuthResult;
use iMSCP_Events_Event as Event;
use PDO;

/**
 * Class HMACAuthentication
 *
 * Authentication handler providing an  hash based message authentication.
 *
 * @package ServerProvisioning
 */
class HMACAuthentication
{
	/**
	 * @var Api
	 */
	protected $api;

	/**
	 * @var int Signature version
	 */
	protected $signatureVersion = 1;

	/**
	 * Constructor
	 *
	 * @param Api $api Api controller instance
	 * @return void
	 */
	public function __construct(Api $api)
	{
		$this->api = $api;
	}

	/**
	 * HMAC authentication handler
	 *
	 * @return AuthResult
	 */
	public function __invoke(Event $event)
	{
		$datetime = DateTime::createFromFormat('Y-m-d\TH:i:s+', $this->api->getRequestParams('SpTimestamp'));
		$accessKeyId = $this->api->getRequestParams('SpAccessKeyId');

		if($accessKeyId && $datetime && (($datetime->getTimestamp() + 30 ) <= time())) {
			$identity = $this->getIdentity($accessKeyId);

			if($identity && $this->checkSign($identity->private_key)) {
				$event->stopPropagation();
				return new AuthResult(AuthResult::SUCCESS, $identity);
			}
		}

		return new AuthResult(AuthResult::FAILURE, null);
	}

	/**
	 * Get identity of user which belong to the given access key
	 *
	 * @param $accessKeyId
	 * @return \stdClass|null
	 */
	protected function getIdentity($accessKeyId)
	{
		$stmt = exec_query(
			'
				SELECT
					private_key, admin_id, admin_name, admin_type, email, created_by
				FROM
					server_provisioning_keys
				INNER JOIN
					admin USING(admin_id)
				WHERE
					access_key_id = ?
			',
			$accessKeyId
		);

		if ($stmt->rowCount()) {
			return $stmt->fetchRow(PDO::FETCH_OBJ);
		}

		return null;
	}

	/**
	 * Check request signature
	 *
	 * @param string $privateKey private key
	 * @return bool
	 */
	protected function checkSign($privateKey)
	{
		$signatureVersion = $this->api->getRequestParams('SpSignatureVersion');
		$signatureMethod = $this->api->getRequestParams('SpSignatureMethod');
		$signature = $this->api->getRequestParams('SpSignature');

		if(
			$signature && $signatureVersion == $this->signatureVersion &&
			in_array($signatureMethod, array('HmacSHA1', 'HmacSHA256'))
		) {
			// Get the path and ensure it's absolute
			$path = '/' . ltrim($this->normalizePath($_SERVER['REQUEST_URI']), '/');

			// build string to sign
			$sign = $this->api->getRequestMethod() . "\n"
				. $this->api->getRequestHost() . "\n"
				. $path . "\n"
				. $this->getCanonicalizedParameterString();

			$sign = base64_encode(
				hash_hmac(($signatureMethod == 'HmacSHA1') ? 'sha1' : 'sha256', $sign, $privateKey, true)
			);

			return ($sign === $signature);
		}

		return false;
	}

	/**
	 * Normalize the URL so that double slashes and relative paths are removed
	 *
	 * @param $path
	 * @return string
	 */
	protected function normalizePath($path)
	{
		if (!$path || $path == '/' || $path == '*') {
			return $path;
		}

		$results = array();
		$segments = array_slice(explode('/', $path), 1);

		foreach ($segments as $segment) {
			if ($segment == '..') {
				array_pop($results);
			} elseif ($segment != '.' && $segment != '') {
				$results[] = $segment;
			}
		}

		// Combine the normalized parts and add the leading slash if needed
		$path = ($path[0] == '/' ? '/' : '') . implode('/', $results);

		// Add the trailing slash if necessary
		if ($path != '/' && end($segments) == '') {
			$path .= '/';
		}

		return $path;
	}

	/**
	 * Canocalize parameters
	 *
	 * @return string
	 */
	protected function getCanonicalizedParameterString()
	{
		if ($this->api->getRequestMethod() == 'POST') {
			$params = $_POST;
		} else {
			$params = $_GET;
		}

		unset($params['SpSignature']);

		uksort($params, 'strcmp');

		$str = '';
		foreach ($params as $k => $v) {
			$str .= rawurlencode($k) . '=' . rawurlencode($v) . '&';
		}

		return substr($str, 0, -1);
	}
}
