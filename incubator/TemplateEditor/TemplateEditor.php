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
			$this->syncDefaultTemplateGroups();
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
	 * Synchronize default template groups
	 *
	 * @throw iMSCP_Plugin_Exception
	 * @throw iMSCP_Exception_Database
	 * @param bool $force Force template synchronization
	 * @return void
	 */
	public function syncDefaultTemplateGroups($force = false)
	{
		if ($force || $this->getConfigParam('sync_default_template_groups', true)) {
			$stpls = $this->getConfigParam('default_template_groups', array());

			$db = iMSCP_Database::getInstance();

			$tgnames = array_map(
				function($k) { return quoteValue(ucwords(str_replace('_', ' ', $k))); }, array_keys($stpls)
			);

			exec_query('DELETE FROM tple_tgroups WHERE tgname NOT IN(' . implode(',', $tgnames) . ')');

			foreach ($stpls as $tsname => $tg) {
				$tgname = ucwords(str_replace('_', ' ', $tsname));

				try {
					foreach ($tg as $tscope => $tpls) {
						$db->beginTransaction();

						# Remove any template which is no longer part of template group
						/*
						$stmt = exec_query(
							'
								SELECT
									tname
								FROM
									tple_templates
								INNER JOIN
									tple_tgroups USING(tgid)
								WHERE
									tgname = ?
								AND
									tscope = ?
							',
							array($tgname, $tscope)
						);

						if($stmt->rowCount()) {
							exec_query(
								'
									DELETE FROM
										tple_templates
									WHERE
										tname NOT IN(' . implode(',', array_diff(array_values($tpls), $stmt->fetchRow(PDO::FETCH_NUM))) . ')'
							);
						}
						*/

						exec_query('INSERT IGNORE INTO tple_tgroups SET tgname = ?', $tgname);

						if (!($tgid = $db->insertId())) {
							$stmt = exec_query('SELECT tgid FROM tple_tgroups WHERE tgname = ?', $tgname);
							$row = $stmt->fetchRow(PDO::FETCH_ASSOC);
							$tgid = $row['tgid'];
						}

						foreach ($tpls as $tname => $tdata) {
							$tpath = (isset($tdata['template_path'])) ? $tdata['template_path'] : false;
							$ttype = (isset($tdata['template_type'])) ? $tdata['template_type'] : 'none';

							if ($tpath && file_exists($tpath)) {
								$tcontent = @file_get_contents($tpath);

								if ($tcontent !== false) {
									exec_query(
										'
											INSERT INTO tple_templates (
												tname, tgid, tcontent, tsname, ttype, tscope
											) VALUES (
												:tname, :tgid, :tcontent, :tsname, :ttype, :tscope
											) ON DUPLICATE KEY UPDATE
												tcontent = :tcontent,
												ttype = :ttype

										',
										array(
											'tname' => $tname,
											'tgid' => $tgid,
											'tcontent' => $tcontent,
											'tsname' => $tsname,
											'tscope' => $tscope,
											'ttype' => $ttype,
										)
									);
								} else {
									$error = error_get_last();
									throw new iMSCP_Plugin_Exception(
										tr('Unable to update the %s template: %s', $tname, $error['message'])
									);
								}
							} else {
								exec_query(
									'
										DELETE FROM
											tple_templates
										WHERE
											tgid = ?
										AND
											tname = ?
										AND
											tsname = ?
										AND
											tscope = ?
									',
									array($tgid, $tname, $tsname, $tscope)
								);
							}
						}

						$db->commit();
					}
				} catch (iMSCP_Exception $e) {
					$db->rollBack();
					throw $e;
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
			if (version_compare($event->getParam('pluginManager')->getPluginApiVersion(), '0.2.11', '<')) {
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

			if (($page = $navigation->findOneBy('uri', '/admin/system_info.php'))) {
				$page->addPage(
					array(
						'label' => tr('Template Editor'),
						'uri' => '/admin/template_editor.php',
						'title_class' => 'tools'
					)
				);
			}
		}
	}
}
