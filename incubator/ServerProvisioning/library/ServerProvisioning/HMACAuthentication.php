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
use Zend\Http\PhpEnvironment\Request as Request;

/**
 * Class HMACAuthentication
 *
 * Authentication handler providing an hash based message authentication.
 *
 * Client which want authenticate through this handler must sign their requests using their Access keys. Access keys
 * consist of an access key ID and a secret access key.
 *
 * SEE <TODO> for more information about signing process.
 *
 * @package ServerProvisioning
 */
class HMACAuthentication
{
	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * @var int Signature version
	 */
	protected $signatureVersion = 1;

	/**
	 * Constructor
	 *
	 * @param Request
	 * @return void
	 */
	public function __construct(Request $request)
	{
		$this->request = $request;
	}

	/**
	 * HMAC authentication handler
	 *
	 * @return AuthResult
	 */
	public function __invoke(Event $event)
	{
		if($this->request->isGet()) {
			$accessKeyId = $this->request->getQuery('SpAccessKeyId');
			$timestamp = $this->request->getQuery('SpTimestamp');
		} else {
			$accessKeyId = $this->request->getPost('SpAccessKeyId');
			$timestamp = $this->request->getPost('SpTimestamp');
		}

		$datetime = DateTime::createFromFormat('Y-m-d\TH:i:s+', $timestamp);

		if($accessKeyId && $datetime && (($datetime->getTimestamp() + 30 ) <= time())) {
			$identity = $this->getIdentity($accessKeyId);

			if($identity && $this->checkSign($identity->secret_access_key)) {
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
					secret_access_key, admin_id, admin_name, admin_type, email, created_by
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
	 * @param string $secretAccessKey Secret access key
	 * @return bool
	 */
	protected function checkSign($secretAccessKey)
	{
		if($this->request->isGet()) {
			$signatureVersion = $this->request->getQuery('SpSignatureVersion');
			$signatureMethod = $this->request->getQuery('SpSignatureMethod');
			$signature = $this->request->getQuery('SpSignature');
		} else {
			$signatureVersion = $this->request->getPost('SpSignatureVersion');
			$signatureMethod = $this->request->getPost('SpSignatureMethod');
			$signature = $this->request->getPost('SpSignature');
		}

		if(
			$signature && $signatureVersion == $this->signatureVersion &&
			in_array($signatureMethod, array('HmacSHA1', 'HmacSHA256'))
		) {
			// Get the path and ensure it's absolute
			$path = '/' . ltrim($this->normalizePath($this->request->getUri()->getPath()), '/');

			// build string to sign
			$sign = $this->request->getMethod() . "\n"
				. $this->request->getUri()->getHost() . "\n"
				. $path . "\n"
				. $this->getCanonicalizedParameterString();

			$sign = base64_encode(
				hash_hmac(($signatureMethod == 'HmacSHA1') ? 'sha1' : 'sha256', $sign, $secretAccessKey, true)
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
		if ($this->request->isPost()) {
			$params = $this->request->getPost();
		} else {
			$params = $this->request->getQuery();
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
