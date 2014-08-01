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
 * @subpackage  JailKit
 * @copyright   2010-2013 by i-MSCP Team
 * @author      Sascha Bay <info@space2place.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  JailKit
 * @author      Sascha Bay <info@space2place.de>
 */
class iMSCP_Plugin_JailKit extends iMSCP_Plugin_Action
{
	/**
	 * @var array Routes
	 */
	protected $routes = array();

	/**
	 * Register a callback for the given event(s).
	 *
	 * @param iMSCP_Events_Manager_Interface $eventsManager
	 */
	public function register(iMSCP_Events_Manager_Interface $eventsManager)
	{
		$eventsManager->registerListener(
			array(
				iMSCP_Events::onBeforeInstallPlugin,
				iMSCP_Events::onBeforePluginsRoute,
				iMSCP_Events::onResellerScriptStart,
				iMSCP_Events::onClientScriptStart,
				iMSCP_Events::onAfterDeleteCustomer,
				iMSCP_Events::onAfterChangeDomainStatus
			),
			$this
		);
	}

	/**
	 * onBeforeInstallPlugin event listener
	 *
	 * @param iMSCP_Events_Event $event
	 */
	public function onBeforeInstallPlugin($event)
	{
		if ($event->getParam('pluginName') == $this->getName()) {
			if (version_compare($event->getParam('pluginManager')->getPluginApiVersion(), '0.2.0', '<')) {
				set_page_message(
					tr('Your i-MSCP version is not compatible with this plugin. Try with a newer version.'), 'error'
				);

				$event->stopPropagation();
			}
		}
	}

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
		} catch (iMSCP_Exception_Database $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Plugin uninstallation
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function uninstall(iMSCP_Plugin_Manager $pluginManager)
	{
		// Only there to tell the plugin manager that this plugin can be uninstalled
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
			'/reseller/jailkit.php' => PLUGINS_PATH . '/' . $pluginName . '/frontend/reseller/jailkit.php',
			'/client/jailkit.php' => PLUGINS_PATH . '/' . $pluginName . '/frontend/client/jailkit.php'
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
	 * Implements the onClientScriptStart event
	 *
	 * @return void
	 */
	public function onClientScriptStart()
	{
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');

		$query = "
			SELECT
				`admin_id`
			FROM
				`admin`
			WHERE
				`admin_id` = ?
			AND
				`admin_status` = ?
			AND
				`admin_id` IN (SELECT `admin_id` FROM `jailkit`)
		";

		$stmt = exec_query($query, array($_SESSION['user_id'], $cfg->ITEM_OK_STATUS));

		if ($stmt->rowCount()) {
			$this->setupNavigation();
		}
	}

	/**
	 * Implements the onAfterDeleteCustomer event
	 *
	 * This event is called when a customer account will be deleted.
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onAfterDeleteCustomer($event)
	{
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');

		exec_query(
			'UPDATE `jailkit` SET `jailkit_status` = ? WHERE `admin_id` = ?',
			array($cfg->ITEM_TODELETE_STATUS, $event->getParam('customerId'))
		);

		send_request();
	}

	/**
	 * Implements the onAfterChangeDomainStatus event
	 *
	 * This event is called when a customer account status will be changed.
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onAfterChangeDomainStatus($event)
	{
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');

		if ($event->getParam('action') == 'enable') {
			exec_query(
				'
					UPDATE
						`jailkit`
					SET
						`jailkit_status` = ?
					WHERE
						`admin_id` = ?
				',
				array($cfg->ITEM_OK_STATUS, $event->getParam('customerId'))
			);

			exec_query(
				'
					UPDATE
						`jailkit_login`
					SET
						`ssh_login_locked` = ?, `jailkit_login_status` = ?
					WHERE
						`admin_id` = ?
				',
				array('0', $cfg->ITEM_TOCHANGE_STATUS, $event->getParam('customerId'))
			);
		} else {
			exec_query(
				'
					UPDATE
						`jailkit`
					SET
						`jailkit_status` = ?
					WHERE
						`admin_id` = ?
				',
				array($cfg->ITEM_DISABLED_STATUS, $event->getParam('customerId'))
			);

			exec_query(
				'
					UPDATE
						`jailkit_login`
					SET
						`ssh_login_locked` = ?, `jailkit_login_status` = ?
					WHERE
						`admin_id` = ?
				',
				array('1', $cfg->ITEM_TOCHANGE_STATUS, $event->getParam('customerId'))
			);
		}

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
	 * Inject JailKit links into the navigation object
	 */
	protected function setupNavigation()
	{
		if (iMSCP_Registry::isRegistered('navigation')) {
			/** @var Zend_Navigation $navigation */
			$navigation = iMSCP_Registry::get('navigation');

			if (($page = $navigation->findOneBy('uri', '/reseller/users.php'))) {
				$page->addPage(
					array(
						'label' => tohtml(tr('JailKit - SSH')),
						'uri' => '/reseller/jailkit.php',
						'title_class' => 'users'
					)
				);
			}

			if (($page = $navigation->findOneBy('uri', '/client/webtools.php'))) {
				$page->addPage(
					array(
						'label' => tohtml(tr('JailKit - SSH')),
						'uri' => '/client/jailkit.php',
						'title_class' => 'ftp'
					)
				);
			}
		}
	}

	/**
	 * Create jailkit and jailkit_login database table
	 *
	 * @return void
	 */
	protected function createDbTable()
	{
		execute_query(
			'
				CREATE TABLE IF NOT EXISTS `jailkit` (
					`jailkit_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
					`admin_id` int(11) unsigned NOT NULL,
					`admin_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
					`max_logins` int(11) default NULL,
					`jailkit_status` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
					PRIMARY KEY (`jailkit_id`),
					KEY `jailkit_id` (`jailkit_id`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			'
		);

		execute_query(
			"
				CREATE TABLE IF NOT EXISTS `jailkit_login` (
					`jailkit_login_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
					`admin_id` int(11) unsigned NOT NULL,
					`ssh_login_name` varchar(200) collate utf8_unicode_ci default NULL,
					`ssh_login_pass` varchar(200) collate utf8_unicode_ci default NULL,
					`ssh_login_sys_uid` int(10) unsigned NOT NULL default '0',
					`ssh_login_sys_gid` int(10) unsigned NOT NULL default '0',
					`ssh_login_locked` tinyint(1) default '0',
					`jailkit_login_status` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
					PRIMARY KEY (`jailkit_login_id`),
					UNIQUE KEY `ssh_login_name` (`ssh_login_name`),
					KEY `jailkit_login_id` (`jailkit_login_id`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			"
		);
	}

	/**
	 * Get status of item with errors
	 *
	 * @return array
	 */
	public function getItemWithErrorStatus()
	{
		$cfg = iMSCP_Registry::get('config');
		$stmt = exec_query(
			"
				(
					SELECT
						`jailkit_id` AS `item_id`, `jailkit_status` AS `status`, `admin_name` AS `item_name`,
						'jailkit' AS `table`, 'jailkit_status' AS `field`
					FROM
						`jailkit`
					WHERE
						`jailkit_status` NOT IN(?, ?, ?, ?, ?, ?, ?)
				)
				UNION
				(
					SELECT
						`jailkit_login_id` AS `item_id`, `jailkit_login_status` AS `status`, `ssh_login_name` AS `item_name`,
						'jailkit_login' AS `table`, 'jailkit_login_status' AS `field`
					FROM
						`jailkit_login`
					WHERE
						`jailkit_login_status` NOT IN(?, ?, ?, ?, ?, ?, ?)
				)
			",
			array(
				$cfg['ITEM_OK_STATUS'], $cfg['ITEM_DISABLED_STATUS'], $cfg['ITEM_TOADD_STATUS'],
				$cfg['ITEM_TOCHANGE_STATUS'], $cfg['ITEM_TOENABLE_STATUS'], $cfg['ITEM_TODISABLE_STATUS'],
				$cfg['ITEM_TODELETE_STATUS'], $cfg['ITEM_OK_STATUS'], $cfg['ITEM_DISABLED_STATUS'],
				$cfg['ITEM_TOADD_STATUS'], $cfg['ITEM_TOCHANGE_STATUS'], $cfg['ITEM_TOENABLE_STATUS'],
				$cfg['ITEM_TODISABLE_STATUS'], $cfg['ITEM_TODELETE_STATUS']
			)
		);

		if ($stmt->rowCount()) {
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		}

		return array();
	}

	/**
	 * Set status of the given plugin item to 'tochange'
	 *
	 * @param string $table Table name
	 * @param string $field Status field name
	 * @param int $itemId JailKit item unique identifier
	 * @return void
	 */
	public function changeItemStatus($table, $field, $itemId)
	{
		$cfg = iMSCP_Registry::get('config');

		if ($table == 'jailkit' && $field == 'jailkit_status') {
			exec_query(
				"UPDATE `$table` SET `$field` = ?  WHERE `jailkit_id` = ?", array($cfg['ITEM_TOCHANGE_STATUS'], $itemId)
			);
		}

		if ($table == 'jailkit_login' && $field == 'jailkit_login_status') {
			exec_query(
				"UPDATE `$table` SET `$field` = ?  WHERE `jailkit_login_id` = ?",
				array($cfg['ITEM_TOCHANGE_STATUS'], $itemId)
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

		$stmt = exec_query(
			'
				SELECT
				(
					(SELECT COUNT(`jailkit_id`) FROM `jailkit` WHERE `jailkit_status` IN (?, ?, ?, ?, ?))
					+
					(
						SELECT
							COUNT(`jailkit_login_id`)
						FROM
							`jailkit_login`
						WHERE
							`jailkit_login_status` IN (?, ?, ?, ?, ?)
					)
				) AS `count`
			',
			array(
				$cfg['ITEM_TOADD_STATUS'], $cfg['ITEM_TOCHANGE_STATUS'], $cfg['ITEM_TOENABLE_STATUS'],
				$cfg['ITEM_TODISABLE_STATUS'], $cfg['ITEM_TODELETE_STATUS'], $cfg['ITEM_TOADD_STATUS'],
				$cfg['ITEM_TOCHANGE_STATUS'], $cfg['ITEM_TOENABLE_STATUS'], $cfg['ITEM_TODISABLE_STATUS'],
				$cfg['ITEM_TODELETE_STATUS']
			)
		);

		return $stmt->fields['count'];
	}
}
