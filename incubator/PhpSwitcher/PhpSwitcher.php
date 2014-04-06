<?php
/**
 * i-MSCP PhpSwitcher plugin
 * Copyright (C) 2014 Laurent Declercq <l.declercq@nuxwin.com>
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
	 * Register a callback for the given event(s).
	 *
	 * @param $eventManager iMSCP_Events_Manager_Interface $eventManager
	 */
	public function register(iMSCP_Events_Manager_Interface $eventManager)
	{
		$eventManager->registerListener(
			array(
				iMSCP_Events::onBeforeInstallPlugin,
				iMSCP_Events::onBeforeUpdatePlugin,
				iMSCP_Events::onBeforeEnablePlugin,
				iMSCP_Events::onAdminScriptStart,
				iMSCP_Events::onClientScriptStart
			),
			$this
		);
	}

	/**
	 * onBeforeInstallPlugin listener
	 *
	 * @param iMSCP_Events_Event $event
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
			$this->dbMigrate($pluginManager, 'up');
		} catch (iMSCP_Plugin_Exception $e) {
			throw new iMSCP_Plugin_Exception(sprintf('Unable to install: %s', $e->getMessage()), $e->getCode(), $e);
		}
	}

	/**
	 * onBeforeUpdatePlugin listener
	 *
	 * @param iMSCP_Events_Event $event
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
			$this->dbMigrate($pluginManager,'up');
			$this->flushCache();
		} catch (iMSCP_Plugin_Exception $e) {
			throw new iMSCP_Plugin_Exception(tr('Unable to update: %s', $e->getMessage()), $e->getCode(), $e);
		}
	}

	/**
	 * onBeforeEnablePlugin listener
	 *
	 * @param iMSCP_Events_Event $event
	 *
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
			throw new iMSCP_Plugin_Exception(tr('Unable to enable: %s', $e->getMessage()), $e->getCode(), $e);
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
			throw new iMSCP_Plugin_Exception(tr('Unable to disable: %s', $e->getMessage()), $e->getCode(), $e);
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
			$this->dbMigrate($pluginManager,'down');
			$this->flushCache();
		} catch (iMSCP_Plugin_Exception $e) {
			throw new iMSCP_Plugin_Exception(tr('Unable to uninstall: %s', $e->getMessage()), $e->getCode(), $e);
		}
	}

	/**
	 * Get routes
	 *
	 * @return array
	 */
	public function getRoutes()
	{
		$pluginName = $this->getName();

		return array(
			'/admin/phpswitcher' => PLUGINS_PATH . '/' . $pluginName . '/frontend/admin/php_switcher.php',
			'/client/phpswitcher' => PLUGINS_PATH . '/' . $pluginName . '/frontend/client/php_switcher.php',
		);
	}

	/**
	 * onAdminScriptStart event listener
	 *
	 * @return void
	 */
	public function onAdminScriptStart()
	{
		$this->setupNavigation('admin');
	}

	/**
	 * onAdminScriptStart event listener
	 *
	 * @return void
	 */
	public function onClientScriptStart()
	{
		$this->setupNavigation('client');
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

					if(!empty($keys)) {
						foreach($keys as $key) {
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
	 * Check plugin compatibility
	 *
	 * @param iMSCP_Events_Event $event
	 */
	protected function checkCompat($event)
	{
		if ($event->getParam('pluginName') == $this->getName()) {
			if (version_compare($event->getParam('pluginManager')->getPluginApiVersion(), '0.2.6', '<')) {
				set_page_message(
					tr('Your i-MSCP version is not compatible with this plugin. Try with a newer version.'), 'error'
				);

				$event->stopPropagation();
			} else {
				$coreConfig = iMSCP_Registry::get('config');

				//if(!in_array($coreConfig['HTTPD_SERVER'], array('apache_fcgid', 'apache_php_fpm'))) {
				if (!in_array($coreConfig['HTTPD_SERVER'], array('apache_fcgid'))) {
					set_page_message(
						tr(
							//'This plugin require that PHP run as FastCGI application (Fcgid or PHP5-FPM). You can switch to one of these implementation by running the i-MSCP installer as follow: %s',
							'This plugin require that PHP run as FastCGI application (Fcgid). You can switch to this httpd server implementation by running the i-MSCP installer as follow: %s',
							'<br /><br /><strong>perl imscp-autoinstall -dr httpd</strong><br />'
						),
						'error'
					);

					$event->stopPropagation();
				}
			}
		}
	}

	/**
	 * Setup plugin navigation
	 *
	 * @param string $uiLevel UI level
	 * @return void
	 */
	protected function setupNavigation($uiLevel)
	{
		if (iMSCP_Registry::isRegistered('navigation')) {
			/** @var Zend_Navigation $navigation */
			$navigation = iMSCP_Registry::get('navigation');

			if ($uiLevel == 'admin' && ($page = $navigation->findOneBy('uri', '/admin/settings.php'))) {
				$page->addPage(
					array(
						'label' => tr('PHP Switcher'),
						'uri' => '/admin/phpswitcher',
						'title_class' => 'settings',
						'order' => 8
					)
				);
			} elseif (
				$uiLevel == 'client' && customerHasFeature('php') &&
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
	 * Migrate database
	 *
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @param string $migrationMode
	 * @throws iMSCP_Plugin_Exception
	 * @todo Switch to the iMSCP_Plugin::migrateDb() method when plugin API 0.2.7 will be released
	 */
	protected function dbMigrate(iMSCP_Plugin_Manager $pluginManager, $migrationMode = 'up')
	{
		$pluginName = $this->getName();
		$pluginInfo = $pluginManager->getPluginInfo($pluginName);
		$dbSchemaVersion = (isset($pluginInfo['db_schema_version'])) ? $pluginInfo['db_schema_version'] : '000';
		$migrationFiles = array();

		$sqlDir = dirname(__FILE__) . '/sql';

		if (is_dir($sqlDir)) {
			/** @var $migrationFileInfo DirectoryIterator */
			foreach (new DirectoryIterator($sqlDir) as $migrationFileInfo) {
				if (!$migrationFileInfo->isDot()) {
					$migrationFiles[] = $migrationFileInfo->getRealPath();
				}
			}

			natsort($migrationFiles);

			if ($migrationMode == 'down') {
				$migrationFiles = array_reverse($migrationFiles);
			}

			try {
				foreach ($migrationFiles as $migrationFile) {
					if (is_readable($migrationFile)) {
						if (preg_match('%/(\d+)_[^/]+?\.php$%', $migrationFile, $version)) {
							if (
								($migrationMode == 'up' && $version[1] > $dbSchemaVersion) ||
								($migrationMode == 'down' && $version[1] <= $dbSchemaVersion)
							) {
								$migrationFilesContent = include($migrationFile);

								if (isset($migrationFilesContent[$migrationMode])) {
									execute_query($migrationFilesContent[$migrationMode]);
								}

								$dbSchemaVersion = $version[1];
							}
						} else {
							throw new iMSCP_Plugin_Exception(
								sprintf("File %s doesn't look like a migration file.", $migrationFile)
							);
						}
					} else {
						throw new iMSCP_Plugin_Exception(sprintf('Migration file %s is not readable.', $migrationFile));
					}
				}

				$pluginInfo['db_schema_version'] = ($migrationMode == 'up') ? $dbSchemaVersion : '000';
				$pluginManager->updatePluginInfo($pluginName, $pluginInfo);
			} catch (iMSCP_Exception $e) {
				$pluginInfo['db_schema_version'] = $dbSchemaVersion;
				$pluginManager->updatePluginInfo($pluginName, $pluginInfo);

				throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
			}
		} else {
			throw new iMSCP_Plugin_Exception(sprintf("Directory %s doesn't exists.", $sqlDir));
		}
	}
}
