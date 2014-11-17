<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by Laurent Declercq
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
 * @subpackage  HelloWorld
 * @copyright   Copyright (C) 2010-2014 by Laurent Declercq
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Hello World Plugin
 *
 * This plugin is only intended to be used in documentation to explain how to create a plugin for i-MSCP. This plugin
 * simply say 'Hello World' when the login page is loaded.
 *
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  HelloWorld
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 */
class iMSCP_Plugin_HelloWorld extends iMSCP_Plugin_Action
{
	/**
	 * Register event listeners
	 *
	 * @param iMSCP_Events_Manager_Interface $eventsManager
	 */
	public function register(iMSCP_Events_Manager_Interface $eventsManager)
	{
		$eventsManager->registerListener(iMSCP_Events::onBeforeEnablePlugin, $this);
		$eventsManager->registerListener(iMSCP_Events::onLoginScriptStart, $this, -500);
	}

	/**
	 * onBeforeEnablePlugin event listener
	 *
	 * @param iMSCP_Events_Event $event
	 */
	public function onBeforeEnablePlugin($event)
	{
		if($event->getParam('pluginName') == $this->getName()) {
			if(version_compare($event->getParam('pluginManager')->getPluginApiVersion(), '0.2.0', '<')) {
				set_page_message(
					tr('Your i-MSCP version is not compatible with this plugin. Try with a newer version.'), 'error'
				);

				$event->stopPropagation();
			}
		}
	}

	/**
	 * onLoginScriptStart event listener
	 *
	 * @param iMSCP_Events_Event $event
	 */
	public function onLoginScriptStart($event)
	{
		// Say Hello World on the login page
		set_page_message('i-MSCP HelloWorld plugin says: Hello World', 'info');
	}
}
