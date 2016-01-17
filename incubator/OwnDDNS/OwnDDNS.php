<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 Sascha Bay <info@space2place.de>
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
 * @copyright   Sascha Bay <info@space2place.de>
 * @author      Sascha Bay <info@space2place.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Class iMSCP_Plugin_OwnDDNS
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
				iMSCP_Events::onBeforeUpdatePlugin,
				iMSCP_Events::onBeforeEnablePlugin,
				
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
	 * @return void
	 */
	public function onBeforeInstallPlugin($event)
	{
		$this->checkCompat($event);
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
			$this->migrateDb('up');
		} catch (iMSCP_Plugin_Exception $e) {
			throw new iMSCP_Plugin_Exception(sprintf('Unable to install: %s', $e->getMessage()), $e->getCode(), $e);
		}
	}
	
	/**
	 * onBeforeUpdatePlugin event listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeUpdatePlugin($event)
	{
		$this->checkCompat($event);
	}
	
	/**
	 * Plugin update
	 *
	 * @throws iMSCP_Plugin_Exception When update fail
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @param string $fromVersion Version from which plugin update is initiated
	 * @param string $toVersion Version to which plugin is updated
	 * @return void
	 */
	public function update(iMSCP_Plugin_Manager $pluginManager, $fromVersion, $toVersion)
	{
		try {
			$this->migrateDb('up');
		} catch (iMSCP_Plugin_Exception $e) {
			throw new iMSCP_Plugin_Exception(sprintf('Unable to update: %s', $e->getMessage()), $e->getCode(), $e);
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
			$this->migrateDb('down');
		} catch (iMSCP_Plugin_Exception $e) {
			throw new iMSCP_Plugin_Exception(tr('Unable to uninstall: %s', $e->getMessage()), $e->getCode(), $e);
		}
	}
	
	/**
	 * onBeforeEnablePlugin listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeEnablePlugin($event)
	{
		$this->checkCompat($event);
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
	 * Check plugin compatibility
	 *
	 * @param iMSCP_Events_Event $event
	 */
	protected function checkCompat($event)
	{
		if ($event->getParam('pluginName') == $this->getName()) {
			if (version_compare($event->getParam('pluginManager')->getPluginApiVersion(), '0.2.10', '<')) {
				set_page_message(
					tr('Your i-MSCP version is not compatible with this plugin. Try with a newer version.'), 'error'
				);

				$event->stopPropagation();
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
		$this->setupNavigation('admin');
	}
	
	/**
	 * Implements the onResellerScriptStart event
	 *
	 * @return void
	 */
	public function onResellerScriptStart()
	{
		$this->setupNavigation('reseller');
	}
	
	/**
	 * Implements the onClientScriptStart event
	 *
	 * @return void
	 */
	public function onClientScriptStart()
	{
		if (self::customerHasOwnDDNS($_SESSION['user_id'])) {
			$this->setupNavigation('client');
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
	 * Inject OpenDKIM links into the navigation object
	 *
	 * @param string $level UI level
	 */
	protected function setupNavigation($level)
	{
		if (iMSCP_Registry::isRegistered('navigation')) {
			/** @var Zend_Navigation $navigation */
			$navigation = iMSCP_Registry::get('navigation');

			if ($level == 'admin') {
				if (($page = $navigation->findOneBy('uri', '/admin/index.php'))) {
					$page->addPage(
						array(
							'label' => tohtml(tr('OwnDDNS')),
							'uri' => '/admin/ownddns.php',
							'title_class' => 'adminlog'
						)
					);
				}
			} elseif ($level == 'reseller') {
				if (($page = $navigation->findOneBy('uri', '/reseller/users.php'))) {
					$page->addPage(
						array(
							'label' => tr('OwnDDNS'),
							'uri' => '/reseller/ownddns.php',
							'title_class' => 'users',
							'privilege_callback' => array(
								'name' => 'resellerHasCustomers'
							)
						)
					);
				}
			} elseif ($level == 'client') {
				if (($page = $navigation->findOneBy('uri', '/client/domains_manage.php'))) {
					$page->addPage(
						array(
							'label' => tr('OwnDDNS'),
							'uri' => '/client/ownddns.php',
							'title_class' => 'domains'
						)
					);
				}
			}
		}
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
				exec_query('DELETE FROM `domain_dns` WHERE `owned_by` = ?', 'OwnDDNS_Plugin');
				
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
		
		$ttlUpdateTime = $this->getConfigParam('update_ttl_time', '60');
		
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
						$data['domain_id'], $data['alias_id'], $data['ownddns_account_name'] . ' ' . $ttlUpdateTime, 
						'IN', 'A', (($data['ownddns_last_ip'] == '') ? $_SERVER['REMOTE_ADDR'] : $data['ownddns_last_ip']),
						'OwnDDNS_Plugin')
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
						`ownddns_status` IN (?, ?, ?, ?, ?)
				)
				+
				(
					SELECT
						COUNT(`ownddns_account_id`)
					FROM
						`ownddns_accounts`
					WHERE
						`ownddns_account_status` IN (?, ?, ?, ?, ?)
				)
			) AS `count`
		';
		$stmt = exec_query(
			$query,
			array(
				$cfg['ITEM_TOADD_STATUS'], $cfg['ITEM_TOCHANGE_STATUS'], $cfg['ITEM_TOENABLE_STATUS'],
				$cfg['ITEM_TODISABLE_STATUS'], $cfg['ITEM_TODELETE_STATUS'], $cfg['ITEM_TOADD_STATUS'],
				$cfg['ITEM_TOCHANGE_STATUS'], $cfg['ITEM_TOENABLE_STATUS'], $cfg['ITEM_TODISABLE_STATUS'],
				$cfg['ITEM_TODELETE_STATUS']
			)
		);

		return $stmt->fields['count'];
	}
	
	/**
	 * Does the given customer has OwnDDNS feature activated?
	 *
	 * @param int $customerId Customer unique identifier
	 * @return bool
	 */
	public static function customerHasOwnDDNS($customerId)
	{
		static $hasAccess = null;
		
		/** @var $cfg iMSCP_Config_Handler_File */
		$cfg = iMSCP_Registry::get('config');

		if(null === $hasAccess) {
			$stmt = exec_query(
				'
					SELECT
						COUNT(admin_id) as cnt
					FROM
						ownddns
					INNER JOIN
						admin USING(admin_id)
					WHERE
						admin_id = ?
					AND
						admin_status = ?
				',
				array($customerId, $cfg->ITEM_OK_STATUS)
			);

			$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
			$hasAccess = (bool) $row['cnt'];
		}

		return $hasAccess;
	}
}
