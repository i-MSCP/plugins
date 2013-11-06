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
 * @subpackage  POP3Fetchmail
 * @copyright   2010-2013 by i-MSCP Team
 * @author      Sascha Bay <info@space2place.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  POP3Fetchmail
 * @author      Sascha Bay <info@space2place.de>
 */
class iMSCP_Plugin_POP3Fetchmail extends iMSCP_Plugin_Action
{
	
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
				iMSCP_Events::onBeforeActivatePlugin
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
	 * Create pop3fetcher roundcube database table
	 *
	 * @return void
	 */
	protected function createDbTable()
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
	protected function dropDbTable()
	{
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');
		
		execute_query('use ' . $cfg->DATABASE_NAME . '_roundcube');
		
		execute_query('DROP TABLE IF EXISTS `pop3fetcher_accounts`');
		
		execute_query('use ' . $cfg->DATABASE_NAME);
	}
}
