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
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  ServerProvisioning
 * @copyright   2014 by Laurent Declercq <l.declercq@nuxwin.com>
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/lgpl-2.1.txt LGPL v2.1
 */

namespace ServerProvisioning;

use AltoRouter as Router;
use Exception;
use iMSCP_Authentication as Authentication;
use iMSCP_Events as Events;
use iMSCP_Events_Manager as EventsManager;
use iMSCP_Registry as Registry;
use ServerProvisioning\Controller\AbstractController;

/**
 * Class ApiController
 *
 * @package ServerProvisioning
 */
class Api
{
	const API_VERSION = '0.0.1';

	/**
	 * @var string Request host
	 */
	protected $requestHost;

	/**
	 * @var int Request port
	 */
	protected $requestPort;

	/**
	 * @var array Request headers
	 */
	protected $requestHeaders = array();

	/**
	 * @var array Array containing Request parameters
	 */
	protected $requestParams = array();

	/**
	 * @var string Request body
	 */
	protected $requestBody = '';

	/**
	 * @var Router
	 */
	protected $router;

	/**
	 * @var string Response content-type
	 */
	public $responseContentType = 'application/json';

	/**
	 * Constructor
	 *
	 * @return Api
	 */
	public function __construct()
	{
		$this->parseRequest();
		$this->router = new Router();
	}

	/**
	 * Get rounter
	 *
	 * @return Router
	 */
	public function getRouter()
	{
		return $this->router;
	}

	/**
	 * Process API call
	 *
	 * @return void
	 */
	public function run()
	{
		try {
			$response = array();

			if (($route = $this->router->match())) {
				if($route['name'] != 'login') {
					EventsManager::getInstance()->registerListener(
						Events::onAuthentication, new HMACAuthentication($this)
					);

					// Process authentication
					$result = Authentication::getInstance()->authenticate();
				} else {
					$result = null;
				}

				if (!$result || $result->isValid()) {
					$controllerClass = $route['target']['class'];
					$controllerMethod = $route['target']['function'];
					$params = array_merge($route['params'], $this->getRequestParams());

					/** @var AbstractController $controller */
					$controller = new $controllerClass();

					if (is_callable(array($controller, $controllerMethod))) {
						if ($controller->checkPayload($controllerMethod, $params)) {
							$response = array_merge($response, $controller->$controllerMethod($params));
						} else {
							$response['code'] = '422';
						}
					} else {
						$response['code'] = '500';
					}
				} else {
					$response['code'] = '401';
				}
			} else {
				$response['code'] = '404';
			}
		} catch (Exception $e) {
			// TODO send email to admin
			$response['code'] = 500;
			$response['message'] = 'Internal Server Error';
		}

		$this->response($response);
	}

	/**
	 * Get HTTP method
	 *
	 * @return string HTTP method
	 */
	public function getRequestMethod()
	{
		return isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'GET';
	}


	public function getRequestHost()
	{
		return $this->requestHost;

	}

	public function getRequestPort()
	{
		return $this->requestPort;
	}

	/**
	 * Get request header of full set of headers
	 *
	 * @param null $name
	 * @param null $default
	 * @return array|null
	 */
	public function getRequestHeaders($name = null, $default = null)
	{
		if($name) {
			$name = $this->normalizeKey($name);
			return isset($this->requestHeaders[$name]) ? $this->requestHeaders[$name] : $default;
		}

		return $this->requestHeaders;
	}

	/**
	 * Return request body
	 *
	 * @return string
	 */
	public function getRequestBody()
	{
		return $this->requestBody;
	}

	/**
	 * Return the given request param of full request params
	 *
	 * @param null $name
	 * @param null $default
	 * @return array|null
	 */
	public function getRequestParams($name = null, $default = null)
	{
		if($name) {
			return isset($this->requestParams[$name]) ? $this->requestParams[$name] : $default;
		}

		return $this->requestParams;
	}

	/**
	 * Send response
	 *
	 * @param array $response Array containing response data
	 * @return void
	 */
	protected function response(array $response)
	{
		if(Registry::isRegistered('bufferFilter')) {
			Registry::get('bufferFilter')->compressionInformation = false;
		}

		if(!isset($response['code'])) {
			$response['code'] = 200;
		}

		if (!isset($response['message'])) {
			$response['message'] = $this->getStatusMessage($response['code']);
		}

		$this->setRequestHeaders($response);
		echo $this->toJson($response);
		exit;
	}

	/**
	 * Return JSON encoded data
	 *
	 * @param array $data data to encode
	 * @return string JSON object
	 */
	protected function toJson(array $data)
	{
		return json_encode($data, JSON_PRETTY_PRINT);
	}

	/**
	 * Get status message
	 *
	 * @param string|int $statusCode HTTP status code
	 * @return mixed
	 */
	protected function getStatusMessage($statusCode)
	{
		static $status = array(
			// Informational 1xx
			100 => 'Continue',
			101 => 'Switching Protocols',
			// Successful 2xx
			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			// Redirection 3xx
			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			306 => '(Unused)',
			307 => 'Temporary Redirect',
			// Client Error 4xx
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
			418 => 'I\'m a teapot',
			422 => 'Unprocessable Entity',
			423 => 'Locked',
			// Server Error 5xx
			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported'
		);

		return isset($status[$statusCode]) ? $status[$statusCode] : 'Unknown status code';
	}

	/**
	 * Process input data
	 *
	 * @return void
	 */
	protected function parseRequest()
	{
		$this->requestHeaders = array();

		foreach ($_SERVER as $k => $v) {
			$k = strtoupper($k);

			if (
				strpos($k, 'X_') === 0 || strpos($k, 'HTTP_') === 0 ||
				in_array(
					$k,
					array(
						'CONTENT_TYPE', 'CONTENT_LENGTH', 'PHP_AUTH_USER', 'PHP_AUTH_PW', 'PHP_AUTH_DIGEST', 'AUTH_TYPE'
					)
				)
			) {
				if ($k === 'HTTP_CONTENT_LENGTH') {
					continue;
				}

				$this->requestHeaders[$this->normalizeKey($k)] = $v;
			}
		}

		$host = null;
		$port = null;

		if ($this->getRequestHeaders('host')) {
			$host = $this->getRequestHeaders('host');

			if (strpos($host, ':') !== false) {
				$hostParts = explode(':', $host);
				$host = $hostParts[0];
			}
		} else {
			//$host = $_SERVER['SERVER_NAME'];
		}

		$port = (int) $_SERVER['SERVER_PORT'];

		$this->requestHost = $host;
		$this->requestPort = $port;

		$this->requestBody = @file_get_contents('php://input');

		if (!$this->requestBody) {
			$this->requestBody = '';
		}

		switch ($this->getRequestMethod()) {
			case 'POST':
				$this->requestParams = $this->cleanInputs($_POST);
				break;
			case 'GET':
			case 'DELETE':
				$this->requestParams = $this->cleanInputs($_GET);
				break;
			case 'PUT':
				parse_str(file_get_contents('php://input'), $this->requestParams);
				$this->requestParams = $this->cleanInputs($this->requestParams);
				break;
			default:
				$this->response(array('code' => 405));
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
			if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc()) {
				$inputData = trim(stripslashes($inputData));
			}

			$cleanInput = trim(strip_tags($inputData));
		}

		return $cleanInput;
	}

	/**
	 * Set HTTP headers
	 *
	 * $param array $response Array containing response data
	 * @return void
	 */
	protected function setRequestHeaders($response)
	{
		if (headers_sent() === false) {
			if (strpos(PHP_SAPI, 'cgi') === 0) {
				header(sprintf('Status: %s %s', $response['code'], $response['message']));
			} else {
				header(sprintf('HTTP/1.1 %s %s', $response['code'], $response['message']));
			}

			header('Content-Type:' . $this->responseContentType);
		}
	}

	/**
	 * Normalize key
	 *
	 * @param $key
	 * @return mixed|string
	 */
	protected function normalizeKey($key)
	{
		$key = strtolower($key);
		$key = str_replace(array('-', '_'), ' ', $key);
		$key = preg_replace('#^http #', '', $key);
		$key = ucwords($key);
		$key = str_replace(' ', '-', $key);

		return $key;
	}
}
