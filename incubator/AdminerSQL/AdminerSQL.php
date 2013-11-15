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
 * @subpackage  AdminerSQL
 * @copyright   2010-2013 by i-MSCP Team
 * @author      Sascha Bay <info@space2place.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  AdminerSQL
 * @author      Sascha Bay <info@space2place.de>
 */
class iMSCP_Plugin_AdminerSQL extends iMSCP_Plugin_Action
{
	/**
	 * Register a callback for the given event(s).
	 *
	 * @param iMSCP_Events_Manager_Interface $eventsManager
	 */
	public function register(iMSCP_Events_Manager_Interface $eventsManager)
	{
		$eventsManager->registerListener(
			array(
				iMSCP_Events::onBeforeInstallPlugin,
				iMSCP_Events::onAdminScriptStart,
				iMSCP_Events::onResellerScriptStart,
				iMSCP_Events::onClientScriptStart
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
			if (version_compare($event->getParam('pluginManager')->getPluginApiVersion(), '0.2.0', '<')) {
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
		// Only there to tell the plugin manager that this plugin is installable
	}

	/**
	 * Implements the onAdminScriptStart event
	 *
	 * @return void
	 */
	public function onAdminScriptStart()
	{
		$this->setupNavigation('admin');
	}

	/**
	 * Implements the onResellerScriptStart event
	 *
	 * @return void
	 */
	public function onResellerScriptStart()
	{
		$this->setupNavigation('reseller');
	}

	/**
	 * Implements the onClientScriptStart event
	 *
	 * @return void
	 */
	public function onClientScriptStart()
	{
		$this->setupNavigation('client');
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
			'/adminer/adminer.php' => $pluginDir . '/frontend/adminer.php',
			'/adminer/editor.php' => $pluginDir . '/frontend/editor.php'
		);
	}

	/**
	 * Inject AdminerSQL links into the navigation object
	 *
	 * @param string $level UI level
	 */
	protected function setupNavigation($level)
	{
		if (iMSCP_Registry::isRegistered('navigation')) {
			/** @var Zend_Navigation $navigation */
			$navigation = iMSCP_Registry::get('navigation');
			switch ($level) {
				case 'admin':
					if (($page = $navigation->findOneBy('uri', '/admin/system_info.php'))) {
						$page->addPages(
							array(
								array(
									'label' => tr('AdminerSQL'),
									'uri' => '/adminer/adminer.php',
									'target' => '_blank'
								),
								array(
									'label' => tr('EditorSQL'),
									'uri' => '/adminer/editor.php',
									'target' => '_blank'
								)
							)
						);
					}

					break;
				case 'reseller':
					if (($page = $navigation->findOneBy('uri', '/reseller/index.php'))) {
						$page->addPages(
							array(
								array(
									'label' => tr('AdminerSQL'),
									'uri' => '/adminer/adminer.php',
									'target' => '_blank'
								),
								array(
									'label' => tr('EditorSQL'),
									'uri' => '/adminer/editor.php',
									'target' => '_blank'
								)
							)
						);
					}
					break;
				case 'client':
					if (($page = $navigation->findOneBy('uri', '/client/webtools.php'))) {
						$page->addPage(
							array(
								'label' => tr('AdminerSQL'),
								'uri' => '/adminer/editor.php',
								'target' => '_blank'
							)
						);
					}

					if (($page = $navigation->findOneBy('uri', '/client/sql_manage.php'))) {
						$page->addPage(
							array(
								'label' => tr('AdminerSQL'),
								'uri' => '/adminer/editor.php',
								'target' => '_blank'
							)
						);
					}
			}
		}
	}
}
