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
			$this->syncTemplates(true);
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
			$this->syncTemplates();
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
	 * This method is automatically called by the plugin manager when the plugin is being enabled (activated).
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function enable(iMSCP_Plugin_Manager $pluginManager)
	{
		//if($pluginManager->getPluginStatus($this->getName()) == 'tochange') {
			try {
				$this->syncTemplates();
			} catch (iMSCP_Exception $e) {
				throw new iMSCP_Plugin_Exception(tr('Unable to change: %s', $e->getMessage()), $e->getCode(), $e);
			}
		//}
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
	 * @throw iMSCP_Exception_Database When synchronization fail
	 * @param bool $force Force template synchronization
	 * @return void
	 */
	public function syncTemplates($force = false)
	{
		if ($force || $this->getConfigParam('sync_default_templates', true)) {
			$db = iMSCP_Database::getRawInstance();
			$serviceTemplates = $this->getConfigParam('service_templates', array());
			$serviceNames = array();

			if (!empty($serviceTemplates)) {
				foreach ($serviceTemplates as $serviceName => $templates) {
					$serviceNames[] = $serviceName;

					foreach ($templates as $templateName => $templateData) {
						$templateFiles = $templateData['files'];
						$templateScope = $templateData['scope'];

						if(preg_match('/^[a-z0-9\s_-]+$/i', $templateName)) {
							try {
								$db->beginTransaction();

								// Create/Update template
								exec_query(
									'
										INSERT IGNORE INTO template_editor_templates (
											name, service_name, is_default, scope
										) VALUES (
											?, ?, ?, ?
										)
									',
									array($templateName, $serviceName, 1, $templateScope)
								);

								if (!($templateId = $db->lastInsertId())) {
									$stmt = exec_query(
										'SELECT id FROM template_editor_templates WHERE name = ? AND service_name = ?',
										array($templateName, $serviceName)
									);

									$templateId = $stmt->fields('id');
								}

								// Insert/Update files which belong to the template

								foreach ($templateFiles as $fileName => $filePath) {
									$fileNames[$serviceName] = $fileName;

									if (is_readable($filePath)) {
										$fileContent = file_get_contents($filePath);

										$stmt = exec_query(
											'
												INSERT INTO template_editor_files (
													template_id, name, content
												) VALUES (
													:template_id, :name, :content
												) ON DUPLICATE KEY UPDATE
													content = :content
											',
											array(
												'template_id' => $templateId,
												'name' => $fileName,
												'content' => $fileContent
											)
										);


										if ($stmt->rowCount()) {
											// New file added to template which have childs?
											// Then, we add new file for child too
											$stmt = exec_query(
												'
													SELECT
														id
													FROM
														template_editor_templates
													WHERE
														parent_id = ?
													AND
														service_name = ?
												',
												array($templateId, $serviceName)
											);

											if ($stmt->rowCount()) {
												while ($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
													exec_query(
														'
															INSERT IGNORE template_editor_files (
																template_id, name, content
															) value (
																?, ?, ?
															)
														',
														array($data['id'], $fileName, $fileContent)
													);
												}
											}
										}
									} else {
										set_page_message(
											tr("File %s is not readable or doesn't exist.", $filePath), 'warning'
										);
									}
								}

								$db->commit();
							} catch (iMSCP_Exception_Database $e) {
								$db->rollBack();
								throw $e;
							}
						} else {
							set_page_message(
								tr("Template %s is not valid: Character out of allowed range.", $templateName), 'warning'
							);
						}
					}
				}
			}

			if(!empty($serviceNames)) {
				// Delete template which are no longer set in configuration file
				$serviceNames = implode(',', array_map('quoteValue', $serviceNames));
				exec_query(
					"
						DELETE FROM
							template_editor_templates
						WHERE
							parent_id IS NULL
						AND
							service_name NOT IN($serviceNames)
					"
				);

				// TODO Delete files which are no longer in plugin configuration file
			} else {
				exec_query('DELETE FROM template_editor_templates');
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
