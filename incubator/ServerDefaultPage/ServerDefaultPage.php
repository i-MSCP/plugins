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

use iMSCP_Plugin_Action as PluginAction;
use iMSCP_Plugin_Manager as PluginManager;

/**
 * Class iMSCP_Plugin_ServerDefaultPage
 */
class iMSCP_Plugin_ServerDefaultPage extends PluginAction
{
    /**
     * Plugin installation
     *
     * @param PluginManager $pluginManager
     * @return void
     */
    public function install(PluginManager $pluginManager)
    {
        // Only here to tell the plugin manager that this plugin can be installed
    }

    /**
     * Plugin uninstallation
     *
     * @param PluginManager $pluginManager
     * @return void
     */
    public function uninstall(PluginManager $pluginManager)
    {
        // Only here to tell the plugin manager that this plugin can be uninstalled
    }
}
