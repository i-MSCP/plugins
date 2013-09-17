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
 * @subpackage  OpenDKIM
 * @copyright   2010-2013 by i-MSCP Team
 * @author      Sascha Bay <info@space2place.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  OpenDKIM
 * @author      Sascha Bay <info@space2place.de>
 */
class iMSCP_Plugin_OpenDKIM extends iMSCP_Plugin_Action
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
			$this->addOpenDkimServicePort($pluginManager);
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
			$this->removeOpenDkimServicePort();
			$this->dropDbTable();
		} catch(iMSCP_Exception_Database $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
	}
	
	/**
	 * Process plugin disable
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function disable(iMSCP_Plugin_Manager $pluginManager)
	{
		try {
			$this->removeOpendkimDnsEntries();
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
				iMSCP_Events::onAfterDeleteCustomer,
				iMSCP_Events::onAfterAddDomainAlias,
				iMSCP_Events::onAfterDeleteDomainAlias,
				iMSCP_Events::onAfterEditDomain
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
			'/reseller/opendkim.php' => PLUGINS_PATH . '/' . $pluginName . '/frontend/reseller/opendkim.php',
			'/client/opendkim.php' => PLUGINS_PATH . '/' . $pluginName . '/frontend/client/opendkim.php'
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
				`admin_id` IN (SELECT `admin_id` FROM `opendkim`)
		";

		$stmt = exec_query($query, array($_SESSION['user_id'], $cfg->ITEM_OK_STATUS));
		
		if ($stmt->rowCount()) {
			$this->setupNavigation();
		}
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
		
		exec_query(
			'UPDATE `opendkim` SET `opendkim_status` = ? WHERE `admin_id` = ?',
				array($cfg->ITEM_TODELETE_STATUS, $event->getParam('customerId'))
		);
			
		send_request();
	}

	/**
	 * Implements the onAfterAddDomainAlias event
	 *
	 * This event is called when new alias domain was added.
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onAfterAddDomainAlias($event)
	{
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');
		
		$query = "SELECT * FROM `domain_aliasses` WHERE `alias_id` = ? AND `alias_status` = ?";
		$stmt = exec_query($query, array($event->getParam('domainAliasId'), $cfg->ITEM_TOADD_STATUS));
		
		if ($stmt->rowCount()) {
			$query = "SELECT * FROM `opendkim` WHERE `domain_id` = ? AND `alias_id` = '0' AND `opendkim_status` = ?";
			$stmt = exec_query($query, array($event->getParam('domainId'), $cfg->ITEM_OK_STATUS));
			
			if ($stmt->rowCount()) {
				$query = 'INSERT INTO `opendkim` (`admin_id`, `domain_id`, `alias_id`, `domain_name`, `opendkim_status`) VALUES (?, ?, ?, ?, ?)';
				exec_query($query,
					array(
						$stmt->fields['admin_id'], $event->getParam('domainId'), $event->getParam('domainAliasId'),
						$event->getParam('domainAliasName'), $cfg->ITEM_TOADD_STATUS
					)
				);
				
				send_request();
			}
		}
	}
	
	/**
	 * Implements the onAfterDeleteDomainAlias event
	 *
	 * This event is called when alias domain was deleted
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onAfterDeleteDomainAlias($event)
	{
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');

		exec_query(
			'UPDATE `opendkim` SET `opendkim_status` = ? WHERE `alias_id` = ?',
			array($cfg->ITEM_TODELETE_STATUS, $event->getParam('domainAliasId'))
		);
	}
	
	/**
	 * Implements the onAfterEditDomain event
	 *
	 * This event is called when customer domain was edited
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onAfterEditDomain($event)
	{
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');

		$query = "SELECT * FROM `opendkim` WHERE `domain_id` = ? AND `alias_id` = '0' AND `opendkim_status` = ?";
		$stmt = exec_query($query, array($event->getParam('domainId'), $cfg->ITEM_OK_STATUS));
		
		if ($stmt->rowCount()) {
			$query = "SELECT `domain_dns` FROM `domain` WHERE `domain_id` = ?";
			$stmt = exec_query($query, $event->getParam('domainId'));
			
			if($stmt->fields['domain_dns'] == 'no') {
				exec_query("UPDATE `domain` SET `domain_dns` = 'yes' WHERE `domain_id` = ?", $event->getParam('domainId'));
				
				set_page_message(tr('OpenDKIM is activated for this customer. DNS was set back to enabled.'), 'warning');
			}
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
	 * Inject OpenDKIM links into the navigation object
	 */
	protected function setupNavigation()
	{
		if (iMSCP_Registry::isRegistered('navigation')) {
			/** @var Zend_Navigation $navigation */
			$navigation = iMSCP_Registry::get('navigation');

			if (($page = $navigation->findOneBy('uri', '/reseller/users.php'))) {
				$page->addPage(
					array(
						'label' => tohtml(tr('OpenDKIM')),
						'uri' => '/reseller/opendkim.php',
						'title_class' => 'users'
					)
				);
			}
			
			if (($page = $navigation->findOneBy('uri', '/client/domains_manage.php'))) {
				$page->addPage(
					array(
						'label' => tohtml(tr('OpenDKIM')),
						'uri' => '/client/opendkim.php',
						'title_class' => 'domains'
					)
				);
			}
		}
	}

	/**
	 * Add opendkim service port
	 *
	 * @return void
	 */
	protected function addOpenDkimServicePort($pluginManager)
	{
		$plugin = $pluginManager->load('OpenDKIM', false, false);
		$pluginConfig = $plugin->getConfig();
		
		$opendkimConfigValue = $pluginConfig['opendkim_port'] . ';tcp;OPENDKIM;1;0;127.0.0.1';
		$query = 'INSERT INTO `config` (`name`, `value`) VALUES (?, ?)';
		exec_query($query,array('PORT_OPENDKIM', $opendkimConfigValue));
	}
	
	/**
	 * Remove opendkim service port
	 *
	 * @return void
	 */
	protected function removeOpenDkimServicePort()
	{
		exec_query('DELETE FROM `config` WHERE `name` = ?', 'PORT_OPENDKIM');
	}
	
	/**
	 * Create opendkim database table
	 *
	 * @return void
	 */
	protected function createDbTable()
	{
		$query = "
			CREATE TABLE IF NOT EXISTS `opendkim` (
				`opendkim_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				`admin_id` int(11) unsigned NOT NULL,
				`domain_id` int(11) unsigned NOT NULL,
				`alias_id` int(11) unsigned NOT NULL,
				`domain_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
				`customer_dns_previous_status` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
				`opendkim_status` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
				PRIMARY KEY (`opendkim_id`),
				KEY `opendkim_id` (`opendkim_id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
		";

		execute_query($query);
	}
	
	/**
	 * Remove all OpenDKIM DNS entries
	 *
	 * @return void
	 */
	protected function removeOpendkimDnsEntries()
	{
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');
		
		$stmt = exec_query("SELECT * FROM `opendkim`");
		if ($stmt->rowCount()) {
			while ($data = $stmt->fetchRow()) {
				exec_query('DELETE FROM `domain_dns` WHERE `owned_by` = ?', 'opendkim_feature');
				
				if($data['alias_id'] == '0') {
					$stmt2 = exec_query('SELECT * FROM `domain_dns` WHERE `domain_id` = ?', $data['domain_id']);
					
					if (! $stmt2->rowCount()) {
						exec_query('UPDATE `domain` SET `domain_status` = ?, `domain_dns` = ? WHERE `domain_id` = ?',
							array($cfg->ITEM_TOCHANGE_STATUS, $data['customer_dns_previous_status'], $data['domain_id'])
						);
					} else {
						exec_query('UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?', array($cfg->ITEM_TOCHANGE_STATUS, $data['domain_id']));
					}
				} else {
					exec_query('UPDATE `domain_aliasses` SET `alias_status` = ? WHERE `alias_id` = ?', array($cfg->ITEM_TOCHANGE_STATUS, $data['alias_id']));
				}
			}
			
			send_request();
		}
	}
	
	/**
	 * Drop opendkim database table
	 *
	 * @return void
	 */
	protected function dropDbTable()
	{
		execute_query('DROP TABLE IF EXISTS `opendkim`');
	}
}
