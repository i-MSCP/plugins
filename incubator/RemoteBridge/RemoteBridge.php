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
	 * @var array Routes
	 */
	protected $routes = array();

	/**
	 * Process plugin installation
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function install(iMSCP_Plugin_Manager $pluginManager)
	{
		try {
			$this->createDbTable();
		} catch(iMSCP_Exception_Database $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
	}
	
	/**
	 * Process plugin uninstallation
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function uninstall(iMSCP_Plugin_Manager $pluginManager)
	{
		try {
			$this->dropDbTable();
		} catch(iMSCP_Exception_Database $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
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
				iMSCP_Events::onBeforeActivatePlugin,
				iMSCP_Events::onBeforePluginsRoute,
				iMSCP_Events::onResellerScriptStart,
				iMSCP_Events::onAfterDeleteUser
			),
			$this
		);
	}

	/**
	 * onBeforeActivatePlugin event listener
	 *
	 * @param iMSCP_Events_Event $event
	 */
	public function onBeforeActivatePlugin($event)
	{
		if($event->getParam('pluginName') == $this->getName() && $event->getParam('action') == 'install') {
			/** @var iMSCP_Config_Handler_File $cfg */
			$cfg = iMSCP_Registry::get('config');

			if($cfg->Version != 'Git Master' && $cfg->BuildDate <= 20130723) {
				set_page_message(
					tr('Your i-MSCP version is not compatible with this plugin. Try with a newer version.'), 'error'
				);

				$event->stopPropagation(true);
			}
		}
	}
	
	/**
	 * Implements the onBeforePluginsRoute event
	 *
	 * @return void
	 */
	public function onBeforePluginsRoute()
	{
		$pluginName = $this->getName();

		$this->routes = array(
			'/reseller/remotebridge.php' => PLUGINS_PATH . '/' . $pluginName . '/frontend/remotebridge.php',
			'/remotebridge.php' => PLUGINS_PATH . '/' . $pluginName . '/public/remotebridge.php'
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
			array($cfg->ITEM_TODELETE_STATUS, $event->getParam('userId'))
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
	 * Create remote_bridge database table
	 *
	 * @return void
	 */
	protected function createDbTable()
	{
		$query = '
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
		';

		execute_query($query);
	}
	
	/**
	 * Drop remote_bridge database table
	 *
	 * @return void
	 */
	protected function dropDbTable()
	{
		execute_query('DROP TABLE IF EXISTS `remote_bridge`');
	}
	
	/**
	 * Get status of item with errors
	 *
	 * @return array
	*/
	public function getItemWithErrorStatus()
	{
		$cfg= iMSCP_Registry::get('config');
		$stmt = exec_query(
			"
				SELECT
					`bridge_id` AS `item_id`, `bridge_status` AS `status`, `bridge_ipaddress` AS `item_name`,
					'remote_bridge' AS `table`, 'bridge_status' AS `field`
				FROM
					`remote_bridge`
				WHERE
					`bridge_status` NOT IN(?, ?, ?, ?, ?, ?, ?)
			",
			array(
				$cfg['ITEM_OK_STATUS'], $cfg['ITEM_DISABLED_STATUS'], $cfg['ITEM_TOADD_STATUS'],
				$cfg['ITEM_TOCHANGE_STATUS'], $cfg['ITEM_TOENABLE_STATUS'], $cfg['ITEM_TODISABLE_STATUS'],
				$cfg['ITEM_TODELETE_STATUS']
			)
		);

		if($stmt->rowCount()) {
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		}

		return array();
	}
	
	/**
	 * Set status of the given plugin item to 'tochange'
	 *
	 * @param string $table Table name
	 * @param string $field Status field name
	 * @param int $itemId RemoteBridge item unique identifier
	 * @return void
	*/
	public function changeItemStatus($table, $field, $itemId)
	{
		$cfg= iMSCP_Registry::get('config');
		if($table == 'remote_bridge' && $field == 'bridge_status') {
			exec_query(
				"UPDATE `$table` SET `$field` = ?  WHERE `bridge_id` = ?", array($cfg['ITEM_TOCHANGE_STATUS'], $itemId)
			);
		}
	}
	
	/**
	 * Return count of request in progress
	 *
	 * @return int
	*/
	public function getCountRequests()
	{
		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');
		$query = 'SELECT COUNT(`bridge_id`) AS `count` FROM `remote_bridge` WHERE `bridge_status` IN (?, ?, ?, ?, ?, ?)';
		$stmt = exec_query(
			$query,
			array(
				$cfg['ITEM_DISABLED_STATUS'], $cfg['ITEM_TOADD_STATUS'], $cfg['ITEM_TOCHANGE_STATUS'],
				$cfg['ITEM_TOENABLE_STATUS'], $cfg['ITEM_TODISABLE_STATUS'], $cfg['ITEM_TODELETE_STATUS']
			)
		);

		return $stmt->fields['count'];
	}
}
