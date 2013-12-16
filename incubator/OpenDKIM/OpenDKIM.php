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
 * @copyright   Sascha Bay <info@space2place.de>
 * @copyright   Rene Schuster <mail@reneschuster.de>
 * @author      Sascha Bay <info@space2place.de>
 * @author      Rene Schuster <mail@reneschuster.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Class iMSCP_Plugin_OpenDKIM
 *
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  OpenDKIM
 * @author      Sascha Bay <info@space2place.de>
 */
class iMSCP_Plugin_OpenDKIM extends iMSCP_Plugin_Action
{
	/**
	 * Register event listeners as provided by this plugin
	 *
	 * @param iMSCP_Events_Manager_Interface $eventsManager
	 */
	public function register(iMSCP_Events_Manager_Interface $eventsManager)
	{
		$eventsManager->registerListener(
			array(
				iMSCP_Events::onBeforeInstallPlugin,
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
	 * onBeforeInstallPlugin event listener
	 *
	 * @param iMSCP_Events_Event $event
	 */
	public function onBeforeInstallPlugin($event)
	{
		if ($event->getParam('pluginName') == $this->getName()) {
			if (version_compare($event->getParam('pluginManager')->getPluginApiVersion(), '0.2.1', '<')) {
				set_page_message(
					tr('Your i-MSCP version is not compatible with this plugin. Try with a newer version.'), 'error'
				);

				$event->stopPropagation();
			}
		}
	}

	/**
	 * Process installation tasks
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
	 * Process uninstallation tasks
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function uninstall(iMSCP_Plugin_Manager $pluginManager)
	{
		try {
			$this->dropDbTable();
		} catch (iMSCP_Exception_Database $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Process enable tasks
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function enable(iMSCP_Plugin_Manager $pluginManager)
	{
		try {
			$this->addOpenDkimServicePort();
		} catch (iMSCP_Exception_Database $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Process disable tasks
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function disable(iMSCP_Plugin_Manager $pluginManager)
	{
		try {
			$this->removeOpenDkimServicePort();
		} catch (iMSCP_Exception_Database $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * onResellerScriptStart event listener
	 *
	 * @return void
	 */
	public function onResellerScriptStart()
	{
		$this->setupNavigation('reseller');
	}

	/**
	 * onClientScriptStart event listener
	 *
	 * @return void
	 */
	public function onClientScriptStart()
	{
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');

		$stmt = exec_query(
			'
				SELECT
					admin_id
				FROM
					admin
				WHERE
					admin_id = ?
				AND
					admin_status = ?
				AND
					admin_id IN (SELECT admin_id FROM opendkim)
			',
			array($_SESSION['user_id'], $cfg->ITEM_OK_STATUS)
		);

		if ($stmt->rowCount()) {
			$this->setupNavigation('client');
		}
	}

	/**
	 * onAfterDeleteCustomer event listener
	 *
	 * This listener is called when a customer account has been deleted.
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onAfterDeleteCustomer($event)
	{
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');

		exec_query(
			'UPDATE opendkim SET opendkim_status = ? WHERE admin_id = ?',
			array($cfg->ITEM_TODELETE_STATUS, $event->getParam('customerId'))
		);
	}

	/**
	 * onAfterAddDomainAlias event listener
	 *
	 * This listener is called when a domain alias has been added.
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onAfterAddDomainAlias($event)
	{
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');

		$stmt = exec_query(
			'SELECT * FROM domain_aliasses WHERE alias_id = ? AND alias_status = ?',
			array($event->getParam('domainAliasId'), $cfg->ITEM_TOADD_STATUS)
		);

		if ($stmt->rowCount()) {
			$stmt = exec_query(
				"SELECT * FROM opendkim WHERE domain_id = ? AND alias_id = '0' AND opendkim_status = ?",
				array($event->getParam('domainId'), $cfg->ITEM_OK_STATUS)
			);

			if ($stmt->rowCount()) {
				exec_query(
					'
						INSERT INTO opendkim (
							admin_id, domain_id, alias_id, domain_name, opendkim_status
						) VALUES (
							?, ?, ?, ?, ?
						)
					',
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
	 * onAfterDeleteDomainAlias event listener
	 *
	 * This listener is called when a domain alias has been deleted.
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onAfterDeleteDomainAlias($event)
	{
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');

		exec_query(
			'UPDATE opendkim SET opendkim_status = ? WHERE alias_id = ?',
			array($cfg->ITEM_TODELETE_STATUS, $event->getParam('domainAliasId'))
		);
	}

	/**
	 * onAfterEditDomain event listener
	 *
	 * This listener is called when acustomer domain has been edited.
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onAfterEditDomain($event)
	{
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');

		$stmt = exec_query(
			"SELECT * FROM opendkim WHERE domain_id = ? AND alias_id = '0' AND opendkim_status = ?",
			array($event->getParam('domainId'), $cfg->ITEM_OK_STATUS)
		);

		if ($stmt->rowCount()) {
			$stmt = exec_query("SELECT domain_dns FROM domain WHERE domain_id = ?", $event->getParam('domainId'));

			if ($stmt->fields['domain_dns'] == 'no') {
				exec_query(
					"UPDATE domain SET domain_dns = 'yes' WHERE domain_id = ?", $event->getParam('domainId')
				);

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
		$pluginDir = PLUGINS_PATH . '/' . $this->getName();

		return array(
			'/reseller/opendkim.php' => $pluginDir . '/frontend/reseller/opendkim.php',
			'/client/opendkim.php' => $pluginDir . '/frontend/client/opendkim.php'
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
				SELECT
					opendkim_id AS item_id, opendkim_status AS status, domain_name AS item_name,
					'opendkim' AS `table`, 'opendkim_status' AS field
				FROM
					opendkim
				WHERE
					opendkim_status NOT IN(?, ?, ?, ?, ?, ?, ?)
			",
			array(
				$cfg['ITEM_OK_STATUS'], $cfg['ITEM_DISABLED_STATUS'], $cfg['ITEM_TOADD_STATUS'],
				$cfg['ITEM_TOCHANGE_STATUS'], $cfg['ITEM_TOENABLE_STATUS'], $cfg['ITEM_TODISABLE_STATUS'],
				$cfg['ITEM_TODELETE_STATUS']
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
	 * @param int $itemId OpenDKIM item unique identifier
	 * @return void
	 */
	public function changeItemStatus($table, $field, $itemId)
	{
		if ($table == 'opendkim' && $field == 'opendkim_status') {
			$cfg = iMSCP_Registry::get('config');

			exec_query(
				'UPDATE opendkim SET opendkim_status = ?  WHERE opendkim_id = ?',
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

		$query = 'SELECT COUNT(opendkim_id) AS cnt FROM opendkim WHERE opendkim_status IN (?, ?, ?, ?, ?, ?)';
		$stmt = exec_query(
			$query,
			array(
				$cfg['ITEM_DISABLED_STATUS'], $cfg['ITEM_TOADD_STATUS'], $cfg['ITEM_TOCHANGE_STATUS'],
				$cfg['ITEM_TOENABLE_STATUS'], $cfg['ITEM_TODISABLE_STATUS'], $cfg['ITEM_TODELETE_STATUS']
			)
		);

		return $stmt->fields['cnt'];
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

			if ($level == 'reseller') {
				if (($page = $navigation->findOneBy('uri', '/reseller/users.php'))) {
					$page->addPage(
						array(
							'label' => tohtml(tr('OpenDKIM')),
							'uri' => '/reseller/opendkim.php',
							'title_class' => 'users'
						)
					);
				}
			} elseif ($level == 'client') {
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
	}

	/**
	 * Add OpenDKIM service port
	 *
	 * @return void
	 */
	protected function addOpenDkimServicePort()
	{
		$dbConfig = iMSCP_Registry::get('dbConfig');
		$pluginConfig = $this->getConfig();

		if (!isset($dbConfig['PORT_OPENDKIM'])) {
			$opendkimConfigValue = $pluginConfig['opendkim_port'] . ';tcp;OPENDKIM;1;127.0.0.1';
			$dbConfig['PORT_OPENDKIM'] = $opendkimConfigValue;
		} else {
			$this->removeOpenDkimServicePort();
			$opendkimConfigValue = $pluginConfig['opendkim_port'] . ';tcp;OPENDKIM;1;127.0.0.1';
			$dbConfig['PORT_OPENDKIM'] = $opendkimConfigValue;
		}
	}

	/**
	 * Remove OpenDKIM service port
	 *
	 * @return void
	 */
	protected function removeOpenDkimServicePort()
	{
		$dbConfig = iMSCP_Registry::get('dbConfig');

		unset($dbConfig['PORT_OPENDKIM']);
	}

	/**
	 * Create OpenDKIM database table
	 *
	 * @return void
	 */
	protected function createDbTable()
	{
		execute_query(
			'
				CREATE TABLE IF NOT EXISTS opendkim (
					opendkim_id int(11) unsigned NOT NULL AUTO_INCREMENT,
					admin_id int(11) unsigned NOT NULL,
					domain_id int(11) unsigned NOT NULL,
					alias_id int(11) unsigned NOT NULL,
					domain_name varchar(255) COLLATE utf8_unicode_ci NOT NULL,
					customer_dns_previous_status varchar(255) COLLATE utf8_unicode_ci NOT NULL,
					opendkim_status varchar(255) COLLATE utf8_unicode_ci NOT NULL,
					PRIMARY KEY (opendkim_id),
					KEY opendkim_id (opendkim_id)
				) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			'
		);
	}

	/**
	 * Drop OpenDKIM database table
	 *
	 * @return void
	 */
	protected function dropDbTable()
	{
		execute_query('DROP TABLE IF EXISTS opendkim');
	}
}
