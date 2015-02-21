<?php
/**
 * i-MSCP Postscreen plugin
 * @copyright 2015 Laurent Declercq <l.declercq@nuxwin.com>
 * @copyright 2013-2015 Rene Schuster <mail@reneschuster.de>
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
 * Class iMSCP_Plugin_Postscreen
 */
class iMSCP_Plugin_Postscreen extends iMSCP_Plugin_Action
{
	/**
	 * Register a callback for the given event(s)
	 *
	 * @param iMSCP_Events_Manager_Interface $eventsManager
	 * @return void
	 */
	public function register(iMSCP_Events_Manager_Interface $eventsManager)
	{
		$eventsManager->registerListener(
			array(
				iMSCP_Events::onBeforeInstallPlugin,
				iMSCP_Events::onBeforeUpdatePlugin,
			), $this
		);
	}

	/**
	 * onBeforeInstallPlugin event listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeInstallPlugin(iMSCP_Events_Event $event)
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
		// Only there to tell the plugin manager that this plugin is installable
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
		// Only there to tell the plugin manager that this plugin can be uninstalled
	}

	/**
	 * onBeforeUpdatePlugin
	 *
	 * @param iMSCP_Events_Event $event
	 */
	public function onBeforeUpdatePlugin(iMSCP_Events_Event $event)
	{
		$this->checkCompat($event);
	}

	/**
	 * Check plugin compatibility
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	protected function checkCompat(iMSCP_Events_Event $event)
	{
		if($event->getParam('pluginName') == $this->getName()) {
			if(version_compare($event->getParam('pluginManager')->getPluginApiVersion(), '0.2.17', '<')) {
				set_page_message(
					tr('Your i-MSCP version is not compatible with this plugin. Try with a newer version.'), 'error'
				);

				$event->stopPropagation();
			}
		}
	}
}
