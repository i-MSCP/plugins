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
 * @subpackage  RoundcubePlugins
 * @copyright   2010-2013 by i-MSCP Team
 * @author      Rene Schuster <mail@reneschuster.de>
 * @contributor Sascha Bay <info@space2place.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * RoundcubePlugins Plugin
 *
 * This plugin allows to use Roundcube Plugins with i-MSCP.
 *
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  RoundcubePlugins
 * @author      Rene Schuster <mail@reneschuster.de>
 */
class iMSCP_Plugin_RoundcubePlugins extends iMSCP_Plugin_Action
{
	/**
	 * Register a callback for the given event(s).
	 *
	 * @param iMSCP_Events_Manager_Interface $eventsManager
	 */
	public function register(iMSCP_Events_Manager_Interface $eventsManager)
	{
		$eventsManager->registerListener(
			array(
				iMSCP_Events::onBeforeInstallPlugin
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
	 * Plugin installation
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function install(iMSCP_Plugin_Manager $pluginManager)
	{
		try {
			$this->createPop3fetcherDbTable();
		} catch(iMSCP_Exception_Database $e) {
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
		try {
			$this->dropPop3fetcherDbTable();
		} catch(iMSCP_Exception_Database $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
	}
	
	/**
	 * Plugin enable
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function enable(iMSCP_Plugin_Manager $pluginManager)
	{
		try {
			$this->addDovecotSieveServicePort();
		} catch (iMSCP_Exception_Database $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
	}
	
	/**
	 * Plugin disable
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function disable(iMSCP_Plugin_Manager $pluginManager)
	{
		try {
			$this->removeDovecotSieveServicePort();
		} catch (iMSCP_Exception_Database $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
	}
	
	/**
	 * Create pop3fetcher roundcube database table
	 *
	 * @return void
	 */
	protected function createPop3fetcherDbTable()
	{
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');
		
		execute_query('use ' . $cfg->DATABASE_NAME . '_roundcube');
		$query = "
			CREATE TABLE IF NOT EXISTS `pop3fetcher_accounts` (
				`pop3fetcher_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`pop3fetcher_email` varchar(128) NOT NULL,
				`pop3fetcher_username` varchar(128) NOT NULL,
				`pop3fetcher_password` varchar(128) NOT NULL,
				`pop3fetcher_serveraddress` varchar(128) NOT NULL,
				`pop3fetcher_serverport` varchar(128) NOT NULL,
				`pop3fetcher_ssl` varchar(10) DEFAULT '0',
				`pop3fetcher_leaveacopyonserver` tinyint(1) DEFAULT '0',
				`user_id` int(10) unsigned NOT NULL DEFAULT '0',
				`last_check` int(10) unsigned NOT NULL DEFAULT '0',
				`last_uidl` varchar(70) DEFAULT NULL,
				`update_lock` tinyint(1) NOT NULL DEFAULT '0',
				`pop3fetcher_provider` varchar(128) DEFAULT NULL,
				`default_folder` varchar(128) DEFAULT NULL,
				PRIMARY KEY (`pop3fetcher_id`),
				KEY `user_id_fk_accounts` (`user_id`)
			) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
		";

		execute_query($query);
		
		execute_query('use ' . $cfg->DATABASE_NAME);
	}
	
	/**
	 * Drop pop3fetcher roundcube database table
	 *
	 * @return void
	 */
	protected function dropPop3fetcherDbTable()
	{
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');
		
		execute_query('use ' . $cfg->DATABASE_NAME . '_roundcube');
		
		execute_query('DROP TABLE IF EXISTS `pop3fetcher_accounts`');
		
		execute_query('use ' . $cfg->DATABASE_NAME);
	}
	
	/**
	 * Add dovecot-sieve service port
	 *
	 * @return void
	 */
	protected function addDovecotSieveServicePort()
	{
		$dbConfig = iMSCP_Registry::get('dbConfig');
		$pluginConfig = $this->getConfig();
		
		if ($pluginConfig['managesieve_plugin'] == 'yes') {
			if (!isset($dbConfig['PORT_DOVECOT-SIEVE'])) {
				$dbConfig['PORT_DOVECOT-SIEVE'] = '4190;tcp;DOVECOT-SIEVE;1;127.0.0.1';
			}
		} else {
			$this->removeDovecotSieveServicePort();
		}		
	}
	
	/**
	 * Remove dovecot-sieve service port
	 *
	 * @return void
	 */
	protected function removeDovecotSieveServicePort()
	{
		$dbConfig = iMSCP_Registry::get('dbConfig');
		
		if (isset($dbConfig['PORT_DOVECOT-SIEVE'])) {
			unset($dbConfig['PORT_DOVECOT-SIEVE']);
		}
	}
	
}
