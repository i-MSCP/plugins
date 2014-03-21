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

use Exception;
use iMSCP_Authentication as Authentication;
use iMSCP_Events as Events;
use iMSCP_Events_Manager as EventsManager;
use iMSCP_Registry as Registry;
use ServerProvisioning\Controller\AbstractController;
use Zend\Http\Header\ContentType as ContentTypeHeader;
use Zend\Http\PhpEnvironment\Request as Request;
use Zend\Http\PhpEnvironment\Response as Response;

/**
 * Class ApiController
 *
 * @package ServerProvisioning
 */
class ApiController
{
	const API_VERSION = '0.0.1';

	/**
	 * @var Request;
	 */
	protected $request;

	/**
	 * @var Response
	 */
	protected $response;

	/**
	 * @var Router
	 */
	protected $router;

	/**
	 * Constructor
	 *
	 * @return ApiController
	 */
	public function __construct()
	{
		$this->request = new Request();
		$this->response =  new Response();
		$this->router = new Router($this->request);
	}

	/**
	 * Get Request
	 *
	 * @return Request
	 */
	public function getRequest()
	{
		return $this->request;
	}

	/**
	 * @return Response
	 */
	public function getResonse()
	{
		return $this->response;
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
	 * Execute API call
	 *
	 * @return void
	 */
	public function execute()
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

					// TODO review this
					$params = array_merge(
						$route['params'], $this->request->getPost()->toArray(), $this->request->getQuery()->toArray()
					);

					/** @var AbstractController $controller */
					$controller = new $controllerClass();

					if (is_callable(array($controller, $controllerMethod))) {
						if ($controller->checkPayload($controllerMethod, $params)) {
							$response = $controller->$controllerMethod($params);
							$controller->sendRequest();

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
		}

		$this->response($response);
	}

	/**
	 * Send response
	 *
	 * @param array $jsonData Array containing json data
	 * @return void
	 */
	protected function response(array $jsonData)
	{
		if(Registry::isRegistered('bufferFilter')) {
			Registry::get('bufferFilter')->compressionInformation = false;
		}

		$response = $this->getResonse();
		$response->setStatusCode(isset($jsonData['code']) ? $jsonData['code'] : '200');

		if (isset($jsonData['message'])) {
			$response->setReasonPhrase($jsonData['message']);
		}

		$header = new ContentTypeHeader();
		$header->setMediaType('application/json')->setCharset('utf-8');

		$response->getHeaders()->addHeader($header);
		$response->setContent(json_encode($jsonData));

		$response->send();
		exit;
	}
}
