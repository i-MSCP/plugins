<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by i-MSCP Team
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
	 * Plugin initialization
	 *
	 * @return void
	 */
	public function init()
	{
		l10n_addTranslations(__DIR__ . '/l10n', 'Array', $this->getName());
	}
	
	/**
	 * Register event listeners
	 *
	 * @param iMSCP_Events_Manager_Interface $eventsManager
	 * @return void
	 */
	public function register(iMSCP_Events_Manager_Interface $eventsManager)
	{
		$eventsManager->registerListener(
			array(
				iMSCP_Events::onBeforeInstallPlugin,
				iMSCP_Events::onBeforeUpdatePlugin,
				iMSCP_Events::onBeforeEnablePlugin,

				iMSCP_Events::onResellerScriptStart,
				iMSCP_Events::onClientScriptStart,

				iMSCP_Events::onAfterDeleteCustomer,
				iMSCP_Events::onAfterAddDomainAlias,
				iMSCP_Events::onAfterDeleteDomainAlias
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
			throw new iMSCP_Plugin_Exception(tr('Unable to install: %s', $e->getMessage()), $e->getCode(), $e);
		}
	}

	/**
	 * onBeforeInstallPlugin event listener
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
			$this->clearTranslations();
		} catch (iMSCP_Plugin_Exception $e) {
			throw new iMSCP_Plugin_Exception(tr('Unable to update: %s', $e->getMessage()), $e->getCode(), $e);
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
			$this->clearTranslations();
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
	 * Plugin activation
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
	 * Plugin deactivation
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
		if (self::customerHasOpenDKIM($_SESSION['user_id'])) {
			$this->setupNavigation('client');
		}
	}

	/**
	 * onAfterDeleteCustomer event listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onAfterDeleteCustomer($event)
	{
		exec_query(
			'UPDATE opendkim SET opendkim_status = ? WHERE admin_id = ?',
			array('todelete', $event->getParam('customerId'))
		);
	}

	/**
	 * onAfterAddDomainAlias event listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onAfterAddDomainAlias($event)
	{
		// Check that the domain alias is being added and not simply ordered
		$stmt = exec_query(
			'SELECT alias_id FROM domain_aliasses WHERE alias_id = ? AND alias_status = ?',
			array($event->getParam('domainAliasId'), 'toadd')
		);

		if ($stmt->rowCount()) {
			// In case OpenDKIM is activated for the parent domain, we must activate it also for the domain alias which
			// is being added
			$stmt = exec_query(
				'SELECT admin_id FROM opendkim WHERE domain_id = ? AND alias_id IS NULL AND opendkim_status = ?',
				array($event->getParam('domainId'), 'ok')
			);

			if ($stmt->rowCount()) {
				$row = $stmt->fetchRow(PDO::FETCH_ASSOC);

				exec_query(
					'
						INSERT INTO opendkim (
							admin_id, domain_id, alias_id, domain_name, opendkim_status
						) VALUES (
							?, ?, ?, ?, ?
						)
					',
					array(
						$row['admin_id'], $event->getParam('domainId'), $event->getParam('domainAliasId'),
						encode_idna($event->getParam('domainAliasName')), 'toadd'
					)
				);
			}
		}
	}

	/**
	 * onAfterDeleteDomainAlias event listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onAfterDeleteDomainAlias($event)
	{
		exec_query(
			'UPDATE opendkim SET opendkim_status = ? WHERE alias_id = ?',
			array('todelete', $event->getParam('domainAliasId'))
		);
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
			array('ok', 'disabled', 'toadd', 'tochange', 'toenable', 'todisable', 'todelete')
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
			exec_query('UPDATE opendkim SET opendkim_status = ? WHERE opendkim_id = ?', array('tochange', $itemId));
		}
	}

	/**
	 * Return count of request in progress
	 *
	 * @return int
	 */
	public function getCountRequests()
	{
		$stmt = exec_query(
			'SELECT COUNT(opendkim_id) AS cnt FROM opendkim WHERE opendkim_status IN (?, ?, ?, ?, ?)',
			array('toadd', 'tochange', 'toenable', 'todisable', 'todelete')
		);

		$row = $stmt->fetchRow(PDO::FETCH_ASSOC);

		return $row['cnt'];
	}

	/**
	 * Does the given customer has OpenDKIM feature activated?
	 *
	 * @param int $customerId Customer unique identifier
	 * @return bool
	 */
	public static function customerHasOpenDKIM($customerId)
	{
		static $hasAccess = null;

		if(null === $hasAccess) {
			$stmt = exec_query(
				'
					SELECT
						COUNT(admin_id) as cnt
					FROM
						opendkim
					INNER JOIN
						admin USING(admin_id)
					WHERE
						admin_id = ?
					AND
						admin_status = ?
				',
				array($customerId, 'ok')
			);

			$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
			$hasAccess = (bool) $row['cnt'];
		}

		return $hasAccess;
	}

	/**
	 * Check plugin compatibility
	 *
	 * @param iMSCP_Events_Event $event
	 */
	protected function checkCompat($event)
	{
		if ($event->getParam('pluginName') == $this->getName()) {
			if (version_compare($event->getParam('pluginManager')->getPluginApiVersion(), '0.2.18', '<')) {
				set_page_message(
					tr('Your i-MSCP version is not compatible with this plugin. Try with a newer version.'), 'error'
				);

				$event->stopPropagation();
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

			if ($level == 'reseller') {
				if (($page = $navigation->findOneBy('uri', '/reseller/users.php'))) {
					$page->addPage(
						array(
							'label' => tr('OpenDKIM'),
							'uri' => '/reseller/opendkim.php',
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
							'label' => tr('OpenDKIM'),
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
	 * Clear translations if any
	 *
	 * @return void
	 */
	protected function clearTranslations()
	{
		/** @var Zend_Translate $translator */
		$translator = iMSCP_Registry::get('translator');

		if($translator->hasCache()) {
			$translator->clearCache($this->getName());
		}
	}
}
