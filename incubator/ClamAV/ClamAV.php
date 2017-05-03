<?php
/**
 * i-MSCP ClamAV plugin
 * Copyright (C) 2014-2017 Laurent Declercq <l.declercq@nuxwin.com>
 * Copyright (C) 2013-2017 Rene Schuster <mail@reneschuster.de>
 * Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>
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

use iMSCP_Plugin_Action as PluginAction;
use iMSCP_Plugin_Manager as PluginManager;

/**
 * Class iMSCP_Plugin_ClamAV
 */
class iMSCP_Plugin_ClamAV extends PluginAction
{
    /**
     * Plugin installation
     *
     * @param PluginManager $pluginManager
     * @return void
     */
    public function install(PluginManager $pluginManager)
    {
        // Only there to tell the plugin manager that this plugin is installable
    }

    /**
     * Plugin uninstallation
     *
     * @param PluginManager $pluginManager
     * @return void
     */
    public function uninstall(PluginManager $pluginManager)
    {
        // Only there to tell the plugin manager that this plugin can be uninstalled
    }
}
