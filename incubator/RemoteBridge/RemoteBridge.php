<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2013 by i-MSCP Team
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
 * @subpackage  RemoteBridge
 * @copyright   2010-2013 by i-MSCP Team
 * @author      Sascha Bay <info@space2place.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * RemoteBridge Plugin.
 *
 *
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  RemoteBridge
 * @author      Sascha Bay <info@space2place.de>
 */
class iMSCP_Plugin_RemoteBridge extends iMSCP_Plugin_Action
{
	/**
	 * @var array
	 */
	protected $routes = array();

	/**
	 * Process plugin installation
	 *
	 * @param iMSCP_Plugin_Manager $pluginManager
	 */
	public function install($pluginManager)
	{
		$db = iMSCP_Database::getInstance();

		try {
			$db->beginTransaction();
			$this->addDbTable();
			$db->commit();
		} catch(iMSCP_Exception $e) {
			$db->rollBack();
			$pluginManager->setStatus($this->getName(), $e->getMessage());
		}

		$pluginManager->setStatus($this->getName(), 'install');
		
		// Send backend request to do the install job on backend side
        send_request();
	}

	/**
	 * Process plugin un-installation
	 *
	 * @return void
	 */
	public function uninstall($pluginManager)
	{
		// Un-installation tasks are delegated to the engine - Just send backend request
		$pluginManager->setStatus($this->getName(), 'uninstall');
		send_request();
	}

	/**
	 * Register a callback for the given event(s).
	 *
	 * @param iMSCP_Events_Manager_Interface $controller
	 */
	public function register(iMSCP_Events_Manager_Interface $controller)
	{
		$controller->registerListener(
			array(
				iMSCP_Events::onResellerScriptStart,
				iMSCP_Events::onAfterDeleteUser
			),
			$this
		);

		$this->routes = array(
			'/reseller/remotebridge.php' => PLUGINS_PATH . '/' . $this->getName() . '/frontend/remotebridge.php',
			'/remotebridge.php' => PLUGINS_PATH . '/' . $this->getName() . '/public/remotebridge.php'
		);
	}

	/**
	 * Implements the onResellerScriptStart event
	 *
	 * @return void
	 */
	public function onResellerScriptStart()
	{
		$this->setupNavigation();
	}

	/**
	 * Implements the onAfterDeleteUser event
	 *
	 * This event is called when a reseller account is being deleted.
	 * If triggered, the RemoteBridge access
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onAfterDeleteUser($event)
	{
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');

		exec_query(
			'UPDATE `remote_bridge` SET `bridge_status` = ? WHERE `bridge_admin_id` = ?',
			array($cfg->ITEM_DELETE_STATUS, $event->getParam('userId'))
		);
		
		send_request();
	}

	/**
	 * Get routes
	 *
	 * @return array
	 */
	public function getRoutes()
	{
		return $this->routes;
	}

	/**
	 * Inject RemoteBridge links into the navigation object
	 */
	protected function setupNavigation()
	{
		if (iMSCP_Registry::isRegistered('navigation')) {
			/** @var Zend_Navigation $navigation */
			$navigation = iMSCP_Registry::get('navigation');

			if (($page = $navigation->findOneBy('uri', '/reseller/index.php'))) {
				$page->addPage(
					array(
						'label' => tohtml(tr('Remote Bridge')),
						'uri' => '/reseller/remotebridge.php',
						'title_class' => 'tools'
					)
				);
			}
		}
	}

	/**
	 * Add remote_bridge database table
	 *
	 * @return void
	 */
	protected function addDbTable()
	{
		$query = "
			CREATE TABLE IF NOT EXISTS `remote_bridge` (
				`bridge_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`bridge_admin_id` int(11) unsigned NOT NULL,
				`bridge_ipaddress` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
				`bridge_key` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
				`bridge_status` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
				PRIMARY KEY (`bridge_id`),
				UNIQUE KEY `bridge_api_key` (`bridge_admin_id`, `bridge_ipaddress`),
				KEY `bridge_admin_id` (`bridge_admin_id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
		";

		execute_query($query);
	}
}
