<?php
/**
 * i-MSCP TemplateEditor plugin
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
 * Class iMSCP_Plugin_TemplateEditor
 */
class iMSCP_Plugin_TemplateEditor extends iMSCP_Plugin_Action
{
	/**
	 * Register a callback for the given event(s).
	 *
	 * @param iMSCP_Events_Manager_Interface $eventsManager
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
			$this->setupDbTables($pluginManager);
			$this->syncTemplates();
		} catch (iMSCP_Exception_Database $e) {
			throw new iMSCP_Plugin_Exception(sprintf('Unable to create database schema: %s', $e->getMessage()));
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
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @param string $fromVersion Version from which plugin update is initiated
	 * @param string $toVersion Version to which plugin is updated
	 * @return void
	 */
	public function update(iMSCP_Plugin_Manager $pluginManager, $fromVersion, $toVersion)
	{
		try {
			$this->setupDbTables($pluginManager);
			$this->syncTemplates();
		} catch (iMSCP_Exception_Database $e) {
			throw new iMSCP_Plugin_Exception(sprintf('Unable to update database schema: %s', $e->getMessage()));
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
			$pluginName = $this->getName();

			execute_query('DROP TABLE IF EXISTS termplate_editor_template_admin');
			execute_query('DROP TABLE IF EXISTS template_editor_template');

			$pluginInfo = $pluginManager->getPluginInfo($pluginName);
			$pluginInfo['db_schema_version'] = '000';
			$pluginManager->updatePluginInfo($pluginName, $pluginInfo);
		} catch (iMSCP_Exception_Database $e) {
			throw new iMSCP_Plugin_Exception(sprintf('Unable to drop database table: %s', $e->getMessage()));
		}
	}

	/**
	 * onBeforeEnablePlugin event listener
	 *
	 * @param iMSCP_Events_Event $event
	 */
	public function onBeforeEnablePlugin($event)
	{
		$this->onBeforeInstallPlugin($event);
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
			'/admin/template_editor.php' => PLUGINS_PATH . '/' . $pluginName . '/frontend/admin/template_editor.php',
		);
	}

	/**
	 * onAdminScriptStart event listener
	 *
	 * @param iMSCP_Events_Event $event
	 */
	public function onAdminScriptStart($event)
	{
		$this->setupNavigation();
	}

	/**
	 * Sync default templates
	 *
	 * @return void
	 */
	public function syncTemplates()
	{
		$services = $this->getConfigParam('service_templates');
		$templateNames = array();

		// Sync default templates
		if (!empty($services)) {
			foreach ($services as $serviceName => $serviceTemplates) {
				foreach ($serviceTemplates as $templateName => $templateMetadata) {
					$templateNames[] = $templateName;

					if (isset($templateMetadata['path']) && is_readable($templateMetadata['path'])) {
						$fileContent = file_get_contents($templateMetadata['path']);

						exec_query(
							'REPLACE INTO template_editor_template (service, name, content, scope) VALUE (?, ?, ?, ?)',
							array(
								$serviceName, $templateName, $fileContent,
								isset($templateMetadata['scope']) ? $templateMetadata['scope'] : 'system'
							)
						);
					}
				}
			}
		}

		// Purge old templates (including childs)
		if(!empty($templateNames)) {
			$templateNames = explode(',', array_map('quoteValue', $templateNames));
			execute_query(
				"DELETE FROM template_editor_template WHERE parent_id IS NULL AND name NOT INT($templateNames)"
			);
		}
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
						'label' => tr('Template Editor'),
						'uri' => '/admin/template_editor.php',
						'title_class' => 'settings',
						'order' => 7
					)
				);
			}
		}
	}

	/**
	 * Setup database tables
	 *
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @throws iMSCP_Plugin_Exception
	 */
	protected function setupDbTables(iMSCP_Plugin_Manager $pluginManager)
	{
		$pluginName = $this->getName();

		$pluginInfo = $pluginManager->getPluginInfo($pluginName);
		$dbSchemaVersion = (isset($pluginInfo['db_version'])) ? $pluginInfo['db_schema_version'] : '000';

		$sqlFiles = array();

		/** @var $fileInfo DirectoryIterator */
		foreach (new DirectoryIterator(dirname(__FILE__) . '/data') as $fileInfo) {
			if (!$fileInfo->isDot()) {
				$sqlFiles[] = $fileInfo->getRealPath();
			}
		}

		sort($sqlFiles, SORT_NATURAL | SORT_FLAG_CASE);

		foreach ($sqlFiles as $sqlFile) {
			if (preg_match('%([^/]+)\.sql$%', $sqlFile, $match) && $match[1] > $dbSchemaVersion) {
				$sqlFileContent = file_get_contents($sqlFile);
				execute_query($sqlFileContent);
				$dbSchemaVersion = $match[1];
			}
		}

		$pluginInfo['db_schema_version'] = $dbSchemaVersion;
		$pluginManager->updatePluginInfo($pluginName, $pluginInfo);
	}
}
