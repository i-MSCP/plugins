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
		} catch (iMSCP_Exception $e) {
			throw new iMSCP_Plugin_Exception(tr('Unable to install: %s', $e->getMessage()), $e->getCode(), $e);
		}
	}

	/**
	 * onBeforeUpdatePlugin event listener
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
	 * @throws iMSCP_Plugin_Exception when update fail
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @param string $fromVersion Version from which plugin update is initiated
	 * @param string $toVersion Version to which plugin is updated
	 * @return void
	 */
	public function update(iMSCP_Plugin_Manager $pluginManager, $fromVersion, $toVersion)
	{
		try {
			$this->migrateDb('up');
		} catch (iMSCP_Exception $e) {
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
			$this->syncTemplates();
		} catch (iMSCP_Exception $e) {
			throw new iMSCP_Plugin_Exception(tr('Unable to enable: %s', $e->getMessage()), $e->getCode(), $e);
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
		} catch (iMSCP_Exception $e) {
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
			'/admin/template_editor.php' => PLUGINS_PATH . '/' . $pluginName . '/frontend/admin/template_editor.php',
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
	 * Sync default templates with those provided by i-MSCP core
	 *
	 * @throw iMSCP_Plugin_Exception
	 * @throw iMSCP_Exception_Database
	 * @param bool $force Force template synchronization
	 * @return void
	 */
	public function syncTemplates($force = false)
	{
		if ($force || $this->getConfigParam('sync_default_templates', true)) {
			$db = iMSCP_Database::getInstance();

			foreach($this->getConfigParam('service_templates', array()) as $templateServiceName => $templates) {
				$templatePrettyName = ucfirst($templateServiceName) . ' (default)';

				foreach($templates as $scope => $templateFiles) {
					$templateScope = $scope;
					try {
						$db->beginTransaction();
							foreach($templateFiles as $templateName => $templateFilesPath) {
								if(file_exists($templateFilesPath)) {
									$templateContent = @file_get_contents($templateFilesPath);

									if($templateContent !== false) {
										exec_query(
											'
												INSERT INTO template_editor_templates (
													template_name,
													template_pretty_name,
													template_content,
													template_service_name,
													template_scope
												) VALUES (
													:template_name,
													:template_pretty_name,
													:template_content,
													:template_service_name,
													:template_scope
												) ON DUPLICATE KEY UPDATE
													template_content = :template_content
											',
											array(
												'template_name' => $templateName,
												'template_pretty_name' => $templatePrettyName,
												'template_content' => $templateContent,
												'template_service_name' => $templateServiceName,
												'template_scope' => $templateScope
											)
										);
									} else {
										$error = error_get_last();
										throw new iMSCP_Plugin_Exception(
											tr('Unable to update the %s template: %s', $templateName, $error['message'])
										);
									}
								} else {
									exec_query(
										'
											DELETE FROM
												template_editor_templates
											WHERE
												template_name = ?
											AND
												template_service_name = ?
										',
										array($templateName, $templateServiceName)
									);
								}
							}

						$db->commit();
					} catch (iMSCP_Exception $e) {
						$db->rollBack();
						throw $e;
					}
				}
			}
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
			if (version_compare($event->getParam('pluginManager')->getPluginApiVersion(), '0.2.7', '<')) {
				set_page_message(
					tr('TemplateEditor: Your i-MSCP version is not compatible with this plugin. Try with a newer version.'),
					'error'
				);

				$event->stopPropagation();
			}
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
}
