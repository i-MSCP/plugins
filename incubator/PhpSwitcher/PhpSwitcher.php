<?php
/**
 * i-MSCP PhpSwitcher plugin
 * Copyright (C) 2014-2015 Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Class iMSCP_Plugin_PhpSwitcher
 */
class iMSCP_Plugin_PhpSwitcher extends iMSCP_Plugin_Action
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
	 * Register a callback for the given event(s)
	 *
	 * @param $eventManager iMSCP_Events_Manager_Interface $eventManager
	 */
	public function register(iMSCP_Events_Manager_Interface $eventManager)
	{
		$eventManager->registerListener(
			array(
				iMSCP_Events::onBeforeInstallPlugin,
				iMSCP_Events::onBeforeUpdatePlugin,
				iMSCP_Events::onBeforeEnablePlugin
			),
			array($this, 'checkRequirements')
		);

		$eventManager->registerListener(
			array(iMSCP_Events::onAdminScriptStart, iMSCP_Events::onClientScriptStart), array($this, 'setupNavigation')
		);
	}

	/**
	 * Check plugin requirements
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function checkRequirements(iMSCP_Events_Event $event)
	{
		if ($event->getParam('pluginName') == $this->getName()) {
			$config = iMSCP_Registry::get('config');
			if ($config['HTTPD_SERVER'] != 'apache_fcgid') {
				set_page_message(tr('This plugin require the apache fcgid i-MSCP server implementation.'), 'error');
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
			$this->migrateDb('up');
		} catch (iMSCP_Plugin_Exception $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Plugin update
	 *
	 * @throws iMSCP_Plugin_Exception When update fail
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function update(iMSCP_Plugin_Manager $pluginManager)
	{
		try {
			$this->migrateDb('up');
			$this->flushCache();
		} catch (iMSCP_Plugin_Exception $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
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
		$db = iMSCP_Database::getRawInstance();

		try {
			$db->beginTransaction();

			$stmt = execute_query('SELECT admin_id FROM php_switcher_version_admin');

			if ($stmt->rowCount()) {
				$this->scheduleDomainsChange($stmt->fetchAll(PDO::FETCH_COLUMN));
			}

			$db->commit();
			$this->flushCache();
		} catch (iMSCP_Exception_Database $e) {
			$db->rollBack();
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
		$db = iMSCP_Database::getRawInstance();

		try {
			$db->beginTransaction();

			$stmt = execute_query('SELECT admin_id FROM php_switcher_version_admin');

			if ($stmt->rowCount()) {
				$this->scheduleDomainsChange($stmt->fetchAll(PDO::FETCH_COLUMN));
			}

			$db->commit();

			$this->flushCache();
		} catch (iMSCP_Exception_Database $e) {
			$db->rollBack();
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
			$this->migrateDb('down');
			$this->flushCache();
		} catch (iMSCP_Plugin_Exception $e) {
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
		$pluginDir = $this->getPluginManager()->pluginGetDirectory() . '/' . $this->getName();

		return array(
			'/admin/phpswitcher' => $pluginDir . '/frontend/admin/php_switcher.php',
			'/client/phpswitcher' => $pluginDir . '/frontend/client/php_switcher.php',
		);
	}

	/**
	 * Setup plugin navigation
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function setupNavigation(iMSCP_Events_Event $event)
	{
		$eventName = $event->getName();

		if (iMSCP_Registry::isRegistered('navigation')) {
			/** @var Zend_Navigation $navigation */
			$navigation = iMSCP_Registry::get('navigation');

			if ($eventName == 'onAdminScriptStart' && ($page = $navigation->findOneBy('uri', '/admin/settings.php'))) {
				$page->addPage(
					array(
						'label' => tr('PHP Switcher'),
						'uri' => '/admin/phpswitcher',
						'title_class' => 'settings',
						'order' => 8
					)
				);
			} elseif (
				$eventName == 'onClientScriptStart' && customerHasFeature('php') &&
				($page = $navigation->findOneBy('uri', '/client/domains_manage.php'))
			) {
				$page->addPage(
					array(
						'label' => tr('PHP Switcher'),
						'uri' => '/client/phpswitcher',
						'title_class' => 'domains'
					)
				);
			}
		}
	}

	/**
	 * Schedule change for all domains which belong to the given user list
	 *
	 * @throw iMSCP_Exception_Database
	 * @param array $adminIds
	 * @return void
	 */
	public function scheduleDomainsChange(array $adminIds)
	{
		$adminIdList = implode(',', $adminIds);

		exec_query(
			'UPDATE domain SET domain_status = ? WHERE domain_admin_id IN (' . $adminIdList . ') AND domain_status = ?',
			array('tochange', 'ok')
		);

		exec_query(
			'
				UPDATE
					subdomain
				JOIN
					domain USING(domain_id)
				SET
					subdomain_status = ?
				WHERE
					domain_admin_id IN (' . $adminIdList . ')
				AND
					subdomain_status = ?
			',
			array('tochange', 'ok')
		);

		exec_query(
			'
				UPDATE
					domain_aliasses
				JOIN
					domain USING(domain_id)
				SET
					alias_status = ?
				WHERE
					domain_admin_id IN (' . $adminIdList . ')
				AND
					alias_status = ?
			',
			array('tochange', 'ok')
		);

		exec_query(
			'
				UPDATE
					subdomain_alias
				JOIN
					domain_aliasses USING(alias_id)
				SET
					subdomain_alias_status = ?
				WHERE
					domain_id IN (SELECT domain_id FROM domain WHERE domain_admin_id IN (' . $adminIdList . '))
				AND
					subdomain_alias_status = ?
			',
			array('tochange', 'ok')
		);
	}

	/**
	 * Flush memcached
	 *
	 * @param array $keys OPTIONAL Keys to flush in cache
	 * @return void
	 */
	public function flushCache(array $keys = array())
	{
		if (class_exists('Memcached')) {
			$memcachedConfig = $this->getConfigParam('memcached', array());

			if (!empty($memcachedConfig['enabled'])) {
				if (isset($memcachedConfig['hostname']) && isset($memcachedConfig['port'])) {
					$memcached = new Memcached($this->getName());
					$memcached->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);

					if (!count($memcached->getServerList())) {
						$memcached->addServer($memcachedConfig['hostname'], $memcachedConfig['port']);
					}

					$prefix = substr(sha1($this->getName()), 0, 8) . '_';

					if (!empty($keys)) {
						foreach ($keys as $key) {
							$memcached->delete($prefix . $key);
						}
					} else {
						$memcached->delete($prefix . 'php_version_admin');
						$memcached->delete($prefix . 'php_confdirs');
					}
				}
			}
		}
	}
}
