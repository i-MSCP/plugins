<?php
/**
 * i-MSCP Mailgraph plugin
 * Copyright (C) 2010-2015 by Sascha Bay <info@space2place.de>
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
 */

/**
 * Class iMSCP_Plugin_Mailgraph
 */
class iMSCP_Plugin_Mailgraph extends iMSCP_Plugin_Action
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
				iMSCP_Events::onBeforeUpdatePlugin,
				iMSCP_Events::onBeforeEnablePlugin,
				iMSCP_Events::onAdminScriptStart
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
	 * This method is automatically called by the plugin manager when the plugin is being installed.
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
	 * onAdminScriptStart listener
	 *
	 * @return void
	 */
	public function onAdminScriptStart()
	{
		$this->setupNavigation();
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
			'/admin/mailgraph.php' => $pluginDir . '/frontend/mailgraph.php',
			'/admin/mailgraphics.php' => $pluginDir . '/frontend/mailgraphics.php'
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
			if (version_compare($event->getParam('pluginManager')->getPluginApiVersion(), '0.2.10', '<')) {
				set_page_message(
					tr('Your i-MSCP version is not compatible with this plugin. Try with a newer version.'), 'error'
				);

				$event->stopPropagation();
			}
		}
	}

	/**
	 * Inject Mailgraph links into the navigation object
	 *
	 * @return void
	 */
	protected function setupNavigation()
	{
		if (iMSCP_Registry::isRegistered('navigation')) {
			/** @var Zend_Navigation $navigation */
			$navigation = iMSCP_Registry::get('navigation');

			if (($page = $navigation->findOneBy('uri', '/admin/server_statistic.php'))) {
				$page->addPage(
					array(
						'label' => tr('Mailgraph'),
						'uri' => '/admin/mailgraph.php',
						'title_class' => 'stats'
					)
				);
			}
		}
	}
}
