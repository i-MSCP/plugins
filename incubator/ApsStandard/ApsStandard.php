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
 * @subpackage  ApsStandard
 * @copyright   2010-2013 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * ApsStandard Plugin
 *
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  ApsStandard
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 */
class iMSCP_Plugin_ApsStandard extends iMSCP_Plugin_Action
{
	/**
	 * @var array
	 */
	protected $routes = array();

	/**
	 * Process plugin installation
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager
	 * @return void
	 */
	public function install(iMSCP_Plugin_Manager $pluginManager)
	{
		try {
			$this->checkRequirements();
			$this->createDbTable();
		} catch (iMSCP_Exception_Database $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		} catch(iMSCP_Plugin_Exception $e) {
			// TODO
		}
	}

	/**
	 * Register a callback for the given event(s).
	 *
	 * @param iMSCP_Events_Manager_Interface $controller
	 */
	public function register(iMSCP_Events_Manager_Interface $controller)
	{
		$controller->registerListener(
			array(
				iMSCP_Events::onBeforePluginsRoute,
				iMSCP_Events::onAdminScriptStart,
				iMSCP_Events::onResellerScriptStart,
				iMSCP_Events::onClientScriptStart

			),
			$this
		);
	}

	/**
	 * onBeforePluginsRoute listener
	 *
	 * @return void
	 */
	public function onBeforePluginsRoute()
	{
		$pluginRootDir = PLUGINS_PATH . DIRECTORY_SEPARATOR . $this->getName();

		$this->routes = array(
			'/admin/aps' => $pluginRootDir . '/frontend/scripts/admin/aps.php',
			'/admin/aps/upload' => $pluginRootDir . '/frontend/scripts/admin/aps.php',
			'/admin/aps/scan' => $pluginRootDir . '/frontend/scripts/admin/aps.php',
			'/admin/aps/manage_packages' =>  $pluginRootDir . '/frontend/scripts/admin/aps.php',
			'/admin/aps/manage_instances' =>  $pluginRootDir . '/frontend/scripts/admin/aps.php',
		);
	}

	/**
	 * onClientScriptStart listener
	 *
	 * @return void
	 */
	public function onAdminScriptStart()
	{
		$this->setupNavigation('admin');
	}

	/**
	 * onResellerScriptStart listener
	 *
	 * @return void
	 */
	public function onResellerScriptStart()
	{
		$this->setupNavigation('reseller');
	}

	/**
	 * onClientScriptStart listener
	 *
	 * @return void
	 */
	public function onClientScriptStart()
	{
		$this->setupNavigation('client');
	}

	/**
	 * Get plugin routes
	 *
	 * @return array
	 */
	public function getRoutes()
	{
		return $this->routes;
	}

	/**
	 * Setup plugin navigation
	 *
	 * @param string $uiLevel Current UI level
	 * @return void
	 */
	protected function setupNavigation($uiLevel)
	{
		if (iMSCP_Registry::isRegistered('navigation')) {
			/** @var Zend_Navigation $navigation */
			$navigation = iMSCP_Registry::get('navigation');

			// TODO Add APS icons
			if ($uiLevel == 'admin') {
				$navigation->addPage(
					array(
						'label' => 'APS Installer',
						'uri' => "/admin/aps",
						'class' => 'custom_link',
						'order' => 3,
						'pages' => array(
							array(
								'label' => tohtml(tr('Overview')),
								'uri' => '/admin/aps',
								'title_class' => 'aps'
							),
							array(
								'label' => tohtml(tr('Upload new packages')),
								'uri' => '/admin/aps/upload',
								'title_class' => 'aps'
							),
							array(
								'label' => tohtml(tr('Scan for new packages')),
								'uri' => '/admin/aps/scan',
								'title_class' => 'aps'
							),
							array(
								'label' => tohtml(tr('Manage packages')),
								'uri' => '/admin/aps/manage_packages',
								'title_class' => 'aps'
							),
							array(
								'label' => tohtml(tr('Manage instances')),
								'uri' => '/admin/aps/manage_instances',
								'title_class' => 'aps'
							)
						)
					)
				);
			}
		}
	}

	/**
	 * Check for plugin requirements
	 *
	 * @return void
	 */
	protected function checkRequirements()
	{

	}

	/**
	 * Create aps plugin database tables
	 *
	 * @thrown iMSCP_Exception_Database on error
	 * @return void
	 */
	protected function createDbTable()
	{
		// TODO Add indexes + some aps properties

		execute_query(
			"
				DROP TABLE IF EXISTS `aps_instances`;
				CREATE TABLE IF NOT EXISTS `aps_instances` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`admin_id` int(11) unsigned NOT NULL,
					`package_id` int(11) unsigned NOT NULL,
					`status` int(4) NOT NULL,
					PRIMARY KEY (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

				DROP TABLE IF EXISTS `aps_packages`;
				CREATE TABLE IF NOT EXISTS `aps_packages` (
					`id` int(11) unsigned NOT NULL auto_increment,
  					`path` varchar(500) NOT NULL,
  					`name` varchar(500) NOT NULL,
  					`version` varchar(20) NOT NULL,
  					`release` int(4) NOT NULL,
  					`status` int(1) NOT NULL default '1',
  					PRIMARY KEY  (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

				DROP TABLE IF EXISTS `aps_settings`;
				CREATE TABLE IF NOT EXISTS `aps_settings` (
					`id` int(4) NOT NULL auto_increment,
					`instance_id` int(11) unsigned NOT NULL,
					`name` varchar(250) NOT NULL,
					`value` varchar(250) NOT NULL,
					PRIMARY KEY (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

				DROP TABLE IF EXISTS `aps_tasks`;
				CREATE TABLE IF NOT EXISTS `aps_tasks` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`instance_id` int(11) unsigned NOT NULL,
					`task` int(11) unsigned NOT NULL,
					PRIMARY KEY (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

				DROP TABLE IF EXISTS `aps_temp_settings`;
				CREATE TABLE IF NOT EXISTS `aps_temp_settings` (
					`id` int(11) unsigned NOT NULL auto_increment,
					`package_id` int(11) unsigned NOT NULL,
					`admin_id` int(11) unsigned NOT NULL,
					`name` varchar(250) NOT NULL,
					`value` varchar(250) NOT NULL,
					PRIMARY KEY (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
			"
		);
	}
}
