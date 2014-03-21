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

namespace ServerProvisioning\Controller;

use iMSCP_Authentication as Authentication;
use iMSCP_Config_Handler_File;
use iMSCP_Database as Database;
use iMSCP_Events_Manager as EventsManager;
use iMSCP_Registry as Registry;

/**
 * Class ServerProvisioning
 *
 * @package ServerProvisioning\Api
 */
abstract class AbstractController
{
	/**
	 * Identity object
	 *
	 * @var null|\stdClass
	 */
	protected $identity;

	/**
	 * @var iMSCP_Config_Handler_File
	 */
	protected $imscpConfig;

	/**
	 * @var Database
	 */
	protected $db;

	/**
	 * @var EventsManager
	 */
	protected $eventManager;

	/**
	 * Whether or not send backend request
	 *
	 * @var bool
	 */
	protected $sendRequest = false;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->identity = Authentication::getInstance()->getIdentity();
		$this->imscpConfig = Registry::get('config');
		$this->db = Database::getInstance();
	}

	/**
	 * Get event manager
	 *
	 * @return EventsManager
	 */
	public function getEventManager()
	{
		if(is_null($this->eventManager)) {
			$this->eventManager = EventsManager::getInstance();
		}

		return $this->eventManager;
	}

	/**
	 * Return array describing payload requirements
	 *
	 * @param string $action Action
	 * @return array
	 */
	abstract protected function getPayloadRequirements($action);

	/**
	 * Check payload
	 *
	 * @param string $action Action
	 * @param array $data
	 * @return bool
	 */
	public function checkPayload($action, array $data)
	{
		$diff = array_diff($this->getPayloadRequirements($action), array_keys($data));

		return empty($diff);
	}

	/**
	 * Send backend request;
	 *
	 * @return void
	 */
	public function sendRequest()
	{
		if($this->sendRequest) {
			send_request();
		}
	}
}
