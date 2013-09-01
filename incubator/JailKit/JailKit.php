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
				iMSCP_Events::onClientScriptStart,
				iMSCP_Events::onAfterDeleteCustomer
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
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');
		
		if($event->getParam('action') == 'install') {
			if($cfg->Version != 'Git Master' && $cfg->Version <= 20130723) {
				set_page_message(
					tr('Your i-MSCP version is not compatible with this plugin. Try with a newer version'), 'error'
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
		$this->setupNavigation();
	}
	
	/**
	 * Implements the onAfterDeleteCustomer event
	 *
	 * This event is called when a customer account wiil be deleted.
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onAfterDeleteCustomer($event)
	{
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');
		
		$query = "
			SELECT
				`domain_id`
			FROM
				`admin`
			INNER JOIN
				`domain` ON(`domain_admin_id` = `admin_id`)
			WHERE
				`admin_id` = ?
		";
		$stmt = exec_query($query, $event->getParam('customerId'));
		
		if ($stmt->rowCount()) {
			$mainDomainId = $stmt->fields['domain_id'];
			
			exec_query(
				'UPDATE `jailkit` SET `jailkit_status` = ? WHERE `domain_id` = ?',
				array($cfg->ITEM_TODELETE_STATUS, $mainDomainId)
			);
			
			send_request();
		}
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
		$query = "
			CREATE TABLE IF NOT EXISTS `jailkit` (
				`jailkit_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`domain_id` int(11) unsigned NOT NULL,
				`domain_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
				`max_logins` int(11) default NULL,
				`jailkit_status` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
				PRIMARY KEY (`jailkit_id`),
				KEY `jailkit_id` (`jailkit_id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			
			CREATE TABLE IF NOT EXISTS `jailkit_login` (
				`jailkit_login_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`domain_id` int(11) unsigned NOT NULL,
				`ssh_login_name` varchar(200) collate utf8_unicode_ci default NULL,
				`ssh_login_pass` varchar(200) collate utf8_unicode_ci default NULL,
				`ssh_login_sys_uid` int(10) unsigned NOT NULL default '0',
				`ssh_login_sys_gid` int(10) unsigned NOT NULL default '0',
				`jailkit_login_status` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
				PRIMARY KEY (`jailkit_login_id`),
				KEY `jailkit_login_id` (`jailkit_login_id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
		";

		execute_query($query);
	}
	
	/**
	 * Drop jailkit and jailkit_login database table
	 *
	 * @return void
	 */
	protected function dropDbTable()
	{
		execute_query('DROP TABLE IF EXISTS `jailkit`');
		execute_query('DROP TABLE IF EXISTS `jailkit_login`');
	}
}
