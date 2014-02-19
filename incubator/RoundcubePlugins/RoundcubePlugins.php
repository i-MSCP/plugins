<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP Team
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
 * @copyright   Rene Schuster <mail@reneschuster.de>
 * @copyright   Sascha Bay <info@space2place.de>
 * @author      Rene Schuster <mail@reneschuster.de>
 * @author      Sascha Bay <info@space2place.de>
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
		$eventsManager->registerListener(iMSCP_Events::onBeforeInstallPlugin, $this);
	}

	/**
	 * onBeforeInstallPlugin event listener
	 *
	 * @param iMSCP_Events_Event $event
	 */
	public function onBeforeInstallPlugin($event)
	{
		if ($event->getParam('pluginName') == $this->getName()) {
			if (version_compare($event->getParam('pluginManager')->getPluginApiVersion(), '0.2.4', '<')) {
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
			$this->createCalendarDbTable();
			$this->createPop3fetcherDbTable();
			$this->createTasklistDbTable();
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
			$this->dropCalendarDbTable();
			$this->dropPop3fetcherDbTable();
			$this->dropTasklistDbTable();
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
	 * Create calendar roundcube database tables
	 *
	 * @return void
	 */
	protected function createCalendarDbTable()
	{
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');
		
		execute_query('use ' . $cfg->DATABASE_NAME . '_roundcube');
		$query = "
			CREATE TABLE IF NOT EXISTS `calendars` (
				`calendar_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
				`user_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
				`name` varchar(255) NOT NULL,
				`color` varchar(8) NOT NULL,
				`showalarms` tinyint(1) NOT NULL DEFAULT '1',
				PRIMARY KEY(`calendar_id`),
				INDEX `user_name_idx` (`user_id`, `name`),
				CONSTRAINT `fk_calendars_user_id` FOREIGN KEY (`user_id`)
				REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
			) /*!40000 ENGINE=INNODB */ /*!40101 CHARACTER SET utf8 COLLATE utf8_general_ci */;

			CREATE TABLE IF NOT EXISTS `events` (
				`event_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
				`calendar_id` int(11) UNSIGNED NOT NULL DEFAULT '0',
				`recurrence_id` int(11) UNSIGNED NOT NULL DEFAULT '0',
				`uid` varchar(255) NOT NULL DEFAULT '',
				`created` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
				`changed` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
				`sequence` int(1) UNSIGNED NOT NULL DEFAULT '0',
				`start` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
				`end` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
				`recurrence` varchar(255) DEFAULT NULL,
				`title` varchar(255) NOT NULL,
				`description` text NOT NULL,
				`location` varchar(255) NOT NULL DEFAULT '',
				`categories` varchar(255) NOT NULL DEFAULT '',
				`all_day` tinyint(1) NOT NULL DEFAULT '0',
				`free_busy` tinyint(1) NOT NULL DEFAULT '0',
				`priority` tinyint(1) NOT NULL DEFAULT '0',
				`sensitivity` tinyint(1) NOT NULL DEFAULT '0',
				`alarms` varchar(255) DEFAULT NULL,
				`attendees` text DEFAULT NULL,
				`notifyat` datetime DEFAULT NULL,
				PRIMARY KEY(`event_id`),
				INDEX `uid_idx` (`uid`),
				INDEX `recurrence_idx` (`recurrence_id`),
				INDEX `calendar_notify_idx` (`calendar_id`,`notifyat`),
				CONSTRAINT `fk_events_calendar_id` FOREIGN KEY (`calendar_id`)
				REFERENCES `calendars`(`calendar_id`) ON DELETE CASCADE ON UPDATE CASCADE
			) /*!40000 ENGINE=INNODB */ /*!40101 CHARACTER SET utf8 COLLATE utf8_general_ci */;

			CREATE TABLE IF NOT EXISTS `attachments` (
				`attachment_id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
				`event_id` int(11) UNSIGNED NOT NULL DEFAULT '0',
				`filename` varchar(255) NOT NULL DEFAULT '',
				`mimetype` varchar(255) NOT NULL DEFAULT '',
				`size` int(11) NOT NULL DEFAULT '0',
				`data` longtext NOT NULL DEFAULT '',
				PRIMARY KEY(`attachment_id`),
				CONSTRAINT `fk_attachments_event_id` FOREIGN KEY (`event_id`)
				REFERENCES `events`(`event_id`) ON DELETE CASCADE ON UPDATE CASCADE
			) /*!40000 ENGINE=INNODB */ /*!40101 CHARACTER SET utf8 COLLATE utf8_general_ci */;

			CREATE TABLE IF NOT EXISTS `itipinvitations` (
				`token` VARCHAR(64) NOT NULL,
				`event_uid` VARCHAR(255) NOT NULL,
				`user_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
				`event` TEXT NOT NULL,
				`expires` DATETIME DEFAULT NULL,
				`cancelled` TINYINT(3) UNSIGNED NOT NULL DEFAULT '0',
				PRIMARY KEY(`token`),
				INDEX `uid_idx` (`user_id`,`event_uid`),
				CONSTRAINT `fk_itipinvitations_user_id` FOREIGN KEY (`user_id`)
				REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
			) /*!40000 ENGINE=INNODB */ /*!40101 CHARACTER SET utf8 COLLATE utf8_general_ci */;
		";

		execute_query($query);
		
		execute_query('use ' . $cfg->DATABASE_NAME);
	}

	/**
	 * Drop calendar roundcube database tables
	 *
	 * @return void
	 */
	protected function dropCalendarDbTable()
	{
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');
		
		execute_query('use ' . $cfg->DATABASE_NAME . '_roundcube');
		
		execute_query('DROP TABLE IF EXISTS `attachments`');
		execute_query('DROP TABLE IF EXISTS `events`');
		execute_query('DROP TABLE IF EXISTS `itipinvitations`');
		execute_query('DROP TABLE IF EXISTS `calendars`');
		
		execute_query('use ' . $cfg->DATABASE_NAME);
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
	 * Create tasklist roundcube database tables
	 *
	 * @return void
	 */
	protected function createTasklistDbTable()
	{
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');
		
		execute_query('use ' . $cfg->DATABASE_NAME . '_roundcube');
		$query = "
			CREATE TABLE IF NOT EXISTS `tasklists` (
				`tasklist_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`user_id` int(10) unsigned NOT NULL,
				`name` varchar(255) NOT NULL,
				`color` varchar(8) NOT NULL,
				`showalarms` tinyint(2) unsigned NOT NULL DEFAULT '0',
				PRIMARY KEY (`tasklist_id`),
				KEY `user_id` (`user_id`),
				CONSTRAINT `fk_tasklist_user_id` FOREIGN KEY (`user_id`)
				REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
			) /*!40000 ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci */;

			CREATE TABLE IF NOT EXISTS `tasks` (
				`task_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`tasklist_id` int(10) unsigned NOT NULL,
				`parent_id` int(10) unsigned DEFAULT NULL,
				`uid` varchar(255) NOT NULL,
				`created` datetime NOT NULL,
				`changed` datetime NOT NULL,
				`del` tinyint(1) unsigned NOT NULL DEFAULT '0',
				`title` varchar(255) NOT NULL,
				`description` text,
				`tags` text,
				`date` varchar(10) DEFAULT NULL,
				`time` varchar(5) DEFAULT NULL,
				`startdate` varchar(10) DEFAULT NULL,
				`starttime` varchar(5) DEFAULT NULL,
				`flagged` tinyint(4) NOT NULL DEFAULT '0',
				`complete` float NOT NULL DEFAULT '0',
				`alarms` varchar(255) DEFAULT NULL,
				`recurrence` varchar(255) DEFAULT NULL,
				`organizer` varchar(255) DEFAULT NULL,
				`attendees` text,
				`notify` datetime DEFAULT NULL,
				PRIMARY KEY (`task_id`),
				KEY `tasklisting` (`tasklist_id`,`del`,`date`),
				KEY `uid` (`uid`),
				CONSTRAINT `fk_tasks_tasklist_id` FOREIGN KEY (`tasklist_id`)
				REFERENCES `tasklists`(`tasklist_id`) ON DELETE CASCADE ON UPDATE CASCADE
			) /*!40000 ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci */;
		";

		execute_query($query);
		
		execute_query('use ' . $cfg->DATABASE_NAME);
	}

	/**
	 * Drop tasklist roundcube database tables
	 *
	 * @return void
	 */
	protected function dropTasklistDbTable()
	{
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');
		
		execute_query('use ' . $cfg->DATABASE_NAME . '_roundcube');
		
		execute_query('DROP TABLE IF EXISTS `tasks`');
		execute_query('DROP TABLE IF EXISTS `tasklists`');
		
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
