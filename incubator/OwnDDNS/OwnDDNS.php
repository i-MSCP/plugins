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
 * @subpackage  OwnDDNS
 * @copyright   2010-2013 by i-MSCP Team
 * @author      Sascha Bay <info@space2place.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

 /**
 * OwnDDNS Plugin.
 *
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  OwnDDNS
 * @author      Sascha Bay <info@space2place.de>
 */
class iMSCP_Plugin_OwnDDNS extends iMSCP_Plugin_Action
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
				iMSCP_Events::onBeforeInstallPlugin,
				iMSCP_Events::onAdminScriptStart,
				iMSCP_Events::onResellerScriptStart,
				iMSCP_Events::onClientScriptStart,
				iMSCP_Events::onAfterDeleteUser,
				iMSCP_Events::onAfterDeleteDomainAlias,
				iMSCP_Events::onAfterEditDomain,
				iMSCP_Events::onBeforeAddSubdomain,
				iMSCP_Events::onBeforeAddDomainAlias,
				iMSCP_Events::onBeforeAddDomain
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
			if (version_compare($event->getParam('pluginManager')->getPluginApiVersion(), '0.2.3', '<')) {
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
			$this->createDbTable();
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
			$this->dropDbTable();
		} catch(iMSCP_Exception_Database $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
	}
	
	/**
	 * Process plugin enable
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function enable(iMSCP_Plugin_Manager $pluginManager)
	{
		try {
			$this->revokeOwnDDNSDnsEntries();
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
			$this->removeOwnDDNSDnsEntries();
		} catch(iMSCP_Exception_Database $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
	}
	
	/**
	 * Get routes
	 *
	 * @return array
	 */
	public function getRoutes()
	{
		$pluginDir = PLUGINS_PATH . '/' . $this->getName();

		return array(
			'/admin/ownddns.php' => $pluginDir . '/frontend/admin/ownddns.php',
			'/reseller/ownddns.php' => $pluginDir . '/frontend/reseller/ownddns.php',
			'/client/ownddns.php' => $pluginDir . '/frontend/client/ownddns.php',
			'/ownddns.php' => $pluginDir . '/public/ownddns.php'
		);
	}
	
	/**
	 * Implements the onAdminScriptStart event
	 *
	 * @return void
	 */
	public function onAdminScriptStart()
	{
		$this->setupNavigation();
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
				`admin_id` IN (SELECT `admin_id` FROM `ownddns`)
		";

		$stmt = exec_query($query, array($_SESSION['user_id'], $cfg->ITEM_OK_STATUS));
		
		if ($stmt->rowCount()) {
			$this->setupNavigation();
		}
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
		exec_query('DELETE FROM `ownddns` WHERE `admin_id` = ?', $event->getParam('userId'));
		exec_query('DELETE FROM `ownddns_accounts` WHERE `admin_id` = ?', $event->getParam('userId'));
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
		exec_query('DELETE FROM `ownddns_accounts` WHERE `alias_id` = ?', $event->getParam('domainAliasId'));
	}
	
	/**
	 * Implements the onBeforeAddDomain event
	 *
	 * This event is called when domain will be added
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeAddDomain($event)
	{
		$query = "SELECT * FROM `ownddns_accounts` WHERE `ownddns_account_fqdn` = ?";
		
		$stmt = exec_query($query, $event->getParam('domainName'));
		
		if ($stmt->rowCount()) {
			set_page_message(tr('Domain "%s" allready exists on this server.', $event->getParam('domainName')), 'error');
			
			redirectTo('index.php');
		}
	}
	
	/**
	 * Implements the onBeforeAddDomainAlias event
	 *
	 * This event is called when alias domain will be added
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeAddDomainAlias($event)
	{
		$query = "SELECT * FROM `ownddns_accounts` WHERE `ownddns_account_fqdn` = ?";
		
		$stmt = exec_query($query, $event->getParam('domainAliasName'));
		
		if ($stmt->rowCount()) {
			set_page_message(tr('Alias domain "%s" allready exists on this server.', $event->getParam('domainAliasName')), 'error');
			
			redirectTo('index.php');
		}
	}
	
	/**
	 * Implements the onBeforeAddSubdomain event
	 *
	 * This event is called when subdomain will be added
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeAddSubdomain($event)
	{
		if($event->getParam('subdomainType') != 'als') {
			$query = "SELECT * FROM `ownddns_accounts` WHERE `domain_id` = ? AND `ownddns_account_fqdn` = ?";
		} else {
			$query = "SELECT * FROM `ownddns_accounts` WHERE `alias_id` = ? AND `ownddns_account_fqdn` = ?";
		}
		
		$stmt = exec_query($query, array($event->getParam('parentDomainId'), $event->getParam('subdomainName')));
		
		if ($stmt->rowCount()) {
			set_page_message(
				tr('Subdomain "%s" already in use by the OwnDDNS feature.', $event->getParam('subdomainName')), 'error'
			);
			
			redirectTo('domains_manage.php');
		}
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

		$query = "SELECT * FROM `ownddns` WHERE `domain_id` = ? AND `ownddns_status` = ?";
		$stmt = exec_query($query, array($event->getParam('domainId'), $cfg->ITEM_OK_STATUS));
		
		if ($stmt->rowCount()) {
			$query = "SELECT `domain_dns` FROM `domain` WHERE `domain_id` = ?";
			$stmt = exec_query($query, $event->getParam('domainId'));
			
			if($stmt->fields['domain_dns'] == 'no') {
				exec_query(
					"UPDATE `domain` SET `domain_dns` = 'yes' WHERE `domain_id` = ?", $event->getParam('domainId')
				);
				
				set_page_message(
					tr('OwnDDNS feature is activated for this customer, DNS was set back to enabled.'), 'warning'
				);
			}
		}
	}

	/**
	 * Inject OwnDDNS links into the navigation object
	 */
	protected function setupNavigation()
	{
		if (iMSCP_Registry::isRegistered('navigation')) {
			/** @var Zend_Navigation $navigation */
			$navigation = iMSCP_Registry::get('navigation');

			if (($page = $navigation->findOneBy('uri', '/admin/index.php'))) {
				$page->addPage(
					array(
						'label' => tohtml(tr('OwnDDNS')),
						'uri' => '/admin/ownddns.php',
						'title_class' => 'adminlog'
					)
				);
			}
			
			if (($page = $navigation->findOneBy('uri', '/reseller/users.php'))) {
				$page->addPage(
					array(
						'label' => tohtml(tr('OwnDDNS')),
						'uri' => '/reseller/ownddns.php',
						'title_class' => 'users'
					)
				);
			}
			
			if (($page = $navigation->findOneBy('uri', '/client/domains_manage.php'))) {
				$page->addPage(
					array(
						'label' => tohtml(tr('OwnDDNS')),
						'uri' => '/client/ownddns.php',
						'title_class' => 'domains'
					)
				);
			}
		}
	}
	
	/**
	 * Create OwnDDNS database table
	 *
	 * @return void
	 */
	protected function createDbTable()
	{
		execute_query(
			'
				CREATE TABLE IF NOT EXISTS `ownddns` (
					`ownddns_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
					`admin_id` int(11) unsigned NOT NULL,
					`domain_id` int(11) unsigned NOT NULL,
					`admin_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
					`max_ownddns_accounts` int(11) default NULL,
					`customer_dns_previous_status` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
					`ownddns_status` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
					PRIMARY KEY (`OwnDDNS_id`),
					KEY `ownddns_id` (`ownddns_id`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
				
				CREATE TABLE IF NOT EXISTS `ownddns_accounts` (
					`ownddns_account_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
					`admin_id` int(11) unsigned NOT NULL,
					`domain_id` int(11) unsigned NOT NULL,
					`alias_id` int(11) unsigned NOT NULL,
					`ownddns_account_name` varchar(50) collate utf8_unicode_ci default NULL,
					`ownddns_account_fqdn` varchar(255) collate utf8_unicode_ci default NULL,
					`ownddns_key` varchar(255) collate utf8_unicode_ci default NULL,
					`ownddns_last_ip` varchar(40) collate utf8_unicode_ci default NULL,
					`ownddns_last_update` DATETIME NOT NULL,
					`ownddns_account_status` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
					PRIMARY KEY (`ownddns_account_id`),
					UNIQUE KEY `ownddns_account_fqdn` (`ownddns_account_fqdn`),
					KEY `ownddns_account_id` (`ownddns_account_id`)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			'
		);
	}
	
	/**
	 * Remove all OwnDDNS DNS entries
	 *
	 * @return void
	 */
	protected function removeOwnDDNSDnsEntries()
	{
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');
		
		$stmt = exec_query("SELECT * FROM `ownddns_accounts`");
		if ($stmt->rowCount()) {
			while ($data = $stmt->fetchRow()) {
				exec_query('DELETE FROM `domain_dns` WHERE `owned_by` = ?', 'ownddns_feature');
				
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
	 * Recovers all existing OwnDDNS DNS entries
	 *
	 * @return void
	 */
	protected function revokeOwnDDNSDnsEntries()
	{
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');
		
		$stmt = exec_query('SELECT * FROM `ownddns_accounts` WHERE `ownddns_account_status` = ?', $cfg->ITEM_OK_STATUS);
		if ($stmt->rowCount()) {
			while ($data = $stmt->fetchRow()) {
				$query = '
					INSERT INTO `domain_dns` (
						`domain_id`, `alias_id`, `domain_dns`,
						`domain_class`, `domain_type`, `domain_text`,
						`owned_by`
					) VALUES(
						?, ?, ?,
						?, ?, ?,
						?
					)
				';
				
				exec_query(
					$query, array(
						$data['domain_id'], $data['alias_id'], $data['ownddns_account_name'], 
						'IN', 'A', (($data['ownddns_last_ip'] == '') ? $_SERVER['REMOTE_ADDR'] : $data['ownddns_last_ip']),
						'ownddns_feature')
				);
				
				if($data['alias_id'] == '0') {
					exec_query('UPDATE `domain` SET `domain_status` = ? WHERE `domain_id` = ?', array($cfg->ITEM_TOCHANGE_STATUS, $data['domain_id']));
				} else {
					exec_query('UPDATE `domain_aliasses` SET `alias_status` = ? WHERE `alias_id` = ?', array($cfg->ITEM_TOCHANGE_STATUS, $data['alias_id']));
				}
			}
			
			send_request();
		}
	}
	
	/**
	 * Drop OwnDDNS database table
	 *
	 * @return void
	 */
	protected function dropDbTable()
	{
		execute_query(
			'
				DROP TABLE IF EXISTS `ownddns`;
				
				DROP TABLE IF EXISTS `ownddns_accounts`;
			'
		);
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
				(
					SELECT
						`ownddns_id` AS `item_id`, `ownddns_status` AS `status`, `admin_name` AS `item_name`,
						'ownddns' AS `table`, 'ownddns_status' AS `field`
					FROM
						`ownddns`
					WHERE
						`ownddns_status` NOT IN(?, ?, ?, ?, ?, ?, ?)
				)
				UNION
				(
					SELECT
						`ownddns_account_id` AS `item_id`, `ownddns_account_status` AS `status`, `ownddns_account_fqdn` AS `item_name`,
						'ownddns_accounts' AS `table`, 'ownddns_account_status' AS `field`
					FROM
						`ownddns_accounts`
					WHERE
						`ownddns_account_status` NOT IN(?, ?, ?, ?, ?, ?, ?)
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
	 * @param int $itemId OwnDDNS item unique identifier
	 * @return void
	*/
	public function changeItemStatus($table, $field, $itemId)
	{
		$cfg= iMSCP_Registry::get('config');
		if($table == 'ownddns' && $field == 'ownddns_status') {
			exec_query(
				"UPDATE `$table` SET `$field` = ?  WHERE `ownddns_id` = ?", array($cfg['ITEM_TOCHANGE_STATUS'], $itemId)
			);
		}
		if($table == 'ownddns_accounts' && $field == 'ownddns_account_status') {
			exec_query(
				"UPDATE `$table` SET `$field` = ?  WHERE `ownddns_account_id` = ?", array($cfg['ITEM_TOCHANGE_STATUS'], $itemId)
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
		$query = '
			SELECT
			(
				(
					SELECT
						COUNT(`ownddns_id`)
					FROM
						`ownddns`
					WHERE
						`ownddns_status` IN (?, ?, ?, ?, ?, ?)
				)
				+
				(
					SELECT
						COUNT(`ownddns_account_id`)
					FROM
						`ownddns_accounts`
					WHERE
						`ownddns_account_status` IN (?, ?, ?, ?, ?, ?)
				)
			) AS `count`
		';
		$stmt = exec_query(
			$query,
			array(
				$cfg['ITEM_DISABLED_STATUS'], $cfg['ITEM_TOADD_STATUS'], $cfg['ITEM_TOCHANGE_STATUS'],
				$cfg['ITEM_TOENABLE_STATUS'], $cfg['ITEM_TODISABLE_STATUS'], $cfg['ITEM_TODELETE_STATUS'],
				$cfg['ITEM_DISABLED_STATUS'], $cfg['ITEM_TOADD_STATUS'], $cfg['ITEM_TOCHANGE_STATUS'],
				$cfg['ITEM_TOENABLE_STATUS'], $cfg['ITEM_TODISABLE_STATUS'], $cfg['ITEM_TODELETE_STATUS']
			)
		);

		return $stmt->fields['count'];
	} 
}
