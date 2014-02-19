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
				IMSCP_Events::onAdminScriptStart
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
			if (version_compare($event->getParam('pluginManager')->getPluginApiVersion(), '0.2.5', '<')) {
				set_page_message(
					tr('Your i-MSCP version is not compatible with this plugin. Try with a newer version.'), 'error'
				);

				$event->stopPropagation();
			} else {
				$coreConfig = iMSCP_Registry::get('config');

				if(!in_array($coreConfig['HTTPD_SERVER'], array('apache_php_fpm', 'apache_php_fcgid'))) {
					set_page_message(
						tr(
							'This plugin require that PHP run as FastCGI application (Fcgid or PHP5-FPM). You can switch to one of these implementation by running the i-MSCP installer as follow: %s',
							'<strong>perl imscp-autoinstall -dr httpd</strong>'
						),
						'error'
					);
				}

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
			$this->dbMigrate($pluginManager, 'up');
		} catch (iMSCP_Exception_Database $e) {
			throw new iMSCP_Plugin_Exception(sprintf('Unable to install: %s', $e->getMessage()), $e->getCode(), $e);
		}
	}

	/**
	 * onBeforeUpdatePlugin event listener
	 *
	 * @param iMSCP_Events_Event $event
	 */
	public function onBeforeUpdatePlugin($event)
	{
		$this->onBeforeInstallPlugin($event);
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
			$this->dbMigrate($pluginManager, 'up');
		} catch (iMSCP_Exception_Database $e) {
			throw new iMSCP_Plugin_Exception(tr('Unable to update: %s', $e->getMessage()), $e->getCode(), $e);
		}
	}

	/**
	 * onBeforeEnablePlugin event listener
	 *
	 * @param iMSCP_Events_Event $event
	 *
	 */
	public function onBeforeEnablePlugin($event)
	{
		$this->onBeforeInstallPlugin($event);
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
			$this->dbMigrate($pluginManager, 'down');
		} catch (iMSCP_Exception_Database $e) {
			throw new iMSCP_Plugin_Exception(tr('Unable to uninstall: %s', $e->getMessage(), $e->getCode(), $e));
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
			'/admin/php-switcher' => PLUGINS_PATH . '/' . $pluginName . '/frontend/admin/php_switcher.php',
		);
	}

	/**
	 * onAdminScriptStart event listener
	 *
	 * @return void
	 */
	public function onAdminScriptStart()
	{
		$this->setupNavigation();
	}

	/**
	 * Inject Links into the navigation object
	 */
	protected function setupNavigation()
	{
		if (iMSCP_Registry::isRegistered('navigation')) {
			/** @var Zend_Navigation $navigation */
			$navigation = iMSCP_Registry::get('navigation');

			if (($page = $navigation->findOneBy('uri', '/admin/settings.php'))) {
				$page->addPage(
					array(
						'label' => tr('PHP Switcher'),
						'uri' => '/admin/php-switcher',
						'title_class' => 'settings',
						'order' => 8
					)
				);
			}
		}
	}

	/**
	 * Migrate database
	 *
	 * @throws iMSCP_Exception_Database When migration fail
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @param string $migrationMode Migration mode (up|down)
	 * @return void
	 */
	protected function dbMigrate(iMSCP_Plugin_Manager $pluginManager, $migrationMode = 'up')
	{
		$pluginName = $this->getName();
		$pluginInfo = $pluginManager->getPluginInfo($pluginName);
		$dbSchemaVersion = (isset($pluginInfo['db_schema_version'])) ? $pluginInfo['db_schema_version'] : '000';
		$migrationFiles = array();

		/** @var $migrationFileInfo DirectoryIterator */
		foreach (new DirectoryIterator(dirname(__FILE__) . '/sql') as $migrationFileInfo) {
			if (!$migrationFileInfo->isDot()) {
				$migrationFiles[] = $migrationFileInfo->getRealPath();
			}
		}

		natsort($migrationFiles);

		if($migrationMode != 'up') {
			$migrationFiles = array_reverse($migrationFiles);
		}

		try {
			foreach ($migrationFiles as $migrationFile) {
				if (preg_match('%(\d+)\_.*?\.php$%', $migrationFile, $match)) {
					if(
						($migrationMode == 'up' && $match[1] > $dbSchemaVersion) ||
						($migrationMode == 'down' && $match[1] <= $dbSchemaVersion)
					) {
						$migrationFilesContent = include($migrationFile);

						if(isset($migrationFilesContent[$migrationMode])) {
							execute_query($migrationFilesContent[$migrationMode]);
						}
					}

					$dbSchemaVersion = $match[1];
				}
			}
		} catch(iMSCP_Exception_Database $e) {
			$pluginInfo['db_schema_version'] =  $dbSchemaVersion;
			$pluginManager->updatePluginInfo($pluginName, $pluginInfo);
			throw $e;
		}

		$pluginInfo['db_schema_version'] =  ($migrationMode == 'up') ? $dbSchemaVersion : '000';
		$pluginManager->updatePluginInfo($pluginName, $pluginInfo);
	}
}
