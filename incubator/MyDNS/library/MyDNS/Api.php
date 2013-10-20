<?php
/**
 * i-MSCP MyDNS Plugin
 * Copyright (C) 2010-2013 by Laurent Declercq
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
 * @subpackage  MyDNS
 * @copyright   2010-2013 by Laurent Declercq
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

namespace MyDNS;

use AltoRouter;
use Exception;
use iMSCP_Registry as Registry;
use iMSCP_Plugin_Exception;

class Api
{
	/**
	 * @var AltoRouter
	 */
	protected $router;

	/**
	 * @var string
	 */
	public $contentType = "application/json";

	/**
	 * @var array Array containing Request data
	 */
	public $request = array();

	/**
	 * @var int HTTP Status code
	 */
	protected $statusCode = 200;

	/**
	 * Constructor
	 *
	 * @return Api
	 */
	public function __construct()
	{
		$this->processInput();
		$this->router = new AltoRouter();
	}

	/**
	 * Set REST service endpoint
	 *
	 * @See AltoRouter
	 * @param string $endpoint REST service endpoint (only the path part)
	 * @return void
	 */
	public function setEndpoint($endpoint)
	{
		$this->router->setBasePath($endpoint);
	}

	/**
	 * Add route
	 *
	 * @throws Exception
	 * @see AltoRouter
	 * @param string $method One of 4 HTTP Methods, or a pipe-separated list of multiple HTTP Methods
	 * @param string $route The route regex
	 * @param array $target An associative array containing Api class name and function name
	 * @param string $name Optional name of this route. Supply if you want to reverse route this url in your application.
	 * @return void
	 */
	public function addRoute($method, $route, $target, $name = null)
	{
		$this->router->map($method, $route, $target, $name);
	}

	/**
	 * Process API call
	 *
	 * @return void
	 */
	public function process()
	{
		$match = $this->router->match();

		if (is_array($match)) {
			$responseData = array('code' => 200);

			$apiClass = __NAMESPACE__ . "\\{$match['target']['class']}";
			$apiFunction = $match['target']['function'];

			$apiObject = new $apiClass($this->request);

			if (is_callable(array($apiObject, $apiFunction))) {
				$match['params']['mydns_admin_id'] = $_SESSION['user_id'];
				$responseData = array_merge($responseData, $apiObject->$apiFunction($match['params']));
			} else {
				$responseData = array('code' => 500);
			}
		} else {
			$responseData = array('code' => 404);
		}

		$this->response($responseData, $responseData['code']);
	}

	/**
	 * Get HTTP method
	 *
	 * @return string HTTP method
	 */
	public function getHttpMethod()
	{
		return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
	}

	/**
	 * Send response
	 *
	 * @param array $responseData Array containing data to send
	 * @param int $status Response status
	 */
	protected function response($responseData, $status)
	{
		$filter = Registry::get('bufferFilter');
		$filter->compressionInformation = false;

		$this->statusCode = $status;

		if (!isset($responseData['message'])) {
			$responseData['message'] = $this->getStatusMessage();
		}

		$this->setHeaders();
		echo $this->toJson($responseData);
		exit;
	}

	/**
	 * Return JSON encoded data
	 *
	 * @param array $data data to encode
	 * @throws iMSCP_Plugin_Exception In case $data is not an array
	 * @return string JSON object
	 */
	protected function toJson($data)
	{
		if (is_array($data)) {
			return json_encode($data, JSON_PRETTY_PRINT);
		} else {
			throw new iMSCP_Plugin_Exception('Array expected');
		}
	}

	/**
	 * Get status message
	 *
	 * @return mixed
	 */
	protected function getStatusMessage()
	{
		$status = array(
			100 => 'Continue',
			101 => 'Switching Protocols',
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			306 => '(Unused)',
			307 => 'Temporary Redirect',
			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported'
		);

		return isset($status[$this->statusCode]) ? $status[$this->statusCode] : '';
	}

	/**
	 * Process input data
	 *
	 * @return void
	 */
	protected function processInput()
	{
		switch ($this->getHttpMethod()) {
			case "POST":
				$this->request = $this->cleanInputs($_POST);
				break;
			case "GET":
			case "DELETE":
				$this->request = $this->cleanInputs($_GET);
				break;
			case "PUT":
				parse_str(file_get_contents('php://input'), $this->request);
				$this->request = $this->cleanInputs($this->request);
				break;
			default:
				$this->response(array('code' => 406), 406);
				break;
		}
	}

	/**
	 * Clean input data
	 *
	 * @param mixed $inputData data
	 * @return array|string
	 */
	protected function cleanInputs($inputData)
	{
		$cleanInput = array();

		if (is_array($inputData)) {
			foreach ($inputData as $k => $v) {
				$cleanInput[$k] = $this->cleanInputs($v);
			}
		} else {
			if (get_magic_quotes_gpc()) {
				$inputData = trim(stripslashes($inputData));
			}

			$cleanInput = trim(strip_tags($inputData));
		}

		return $cleanInput;
	}

	/**
	 * Set HTTP headers
	 *
	 * @return void
	 */
	protected function setHeaders()
	{
		header('HTTP/1.1 ' . $this->statusCode . ' ' . $this->getStatusMessage());
		header('Content-Type:' . $this->contentType);
	}
}
