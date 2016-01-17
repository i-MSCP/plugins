<?php
/**
 * i-MSCP ServerDefaultPage plugin
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
 * Class iMSCP_Plugin_ServerDefaultPage
 */
class iMSCP_Plugin_ServerDefaultPage extends iMSCP_Plugin_Action
{
	/**
	 * Plugin installation
	 *
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function install(iMSCP_Plugin_Manager $pluginManager)
	{
		// Only here to tell the plugin manager that this plugin is installable
	}

	/**
	 * Plugin uninstallation
	 *
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function uninstall(iMSCP_Plugin_Manager $pluginManager)
	{
		// Only here to tell the plugin manager that this plugin can be uninstalled
	}
}
