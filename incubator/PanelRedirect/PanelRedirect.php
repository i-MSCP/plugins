<?php
/**
 * i-MSCP PanelRedirect plugin
 * Copyright (C) 2014-2016 by Ninos Ego <me@ninosego.de>
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
 * Class iMSCP_Plugin_PanelRedirect
 */
class iMSCP_Plugin_PanelRedirect extends iMSCP_Plugin_Action
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
				iMSCP_Events::onLoginScriptStart,
				iMSCP_Events::onLostPasswordScriptStart,
				iMSCP_Events::onAdminScriptStart,
				iMSCP_Events::onResellerScriptStart,
				iMSCP_Events::onClientScriptStart,
				iMSCP_Events::onBeforePluginsRoute
			),
			array($this, 'overrideHttpPorts')
		);
	}

	/**
	 * Override HTTP ports which are defined by i-MSCP in case of proxy usage
	 *
	 * @return void
	 */
	public function overrideHttpPorts()
	{
		if ($this->getConfigParam('type', 'unknown') == 'proxy') {
			$config = iMSCP_Registry::get('config');
			$config['BASE_SERVER_VHOST_HTTP_PORT'] = 80;
			$config['BASE_SERVER_VHOST_HTTPS_PORT'] = 443;
		}
	}
}
