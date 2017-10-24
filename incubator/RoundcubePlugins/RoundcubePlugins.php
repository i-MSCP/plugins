<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 *  Copyright (C) 2017 Laurent Declercq <l.declercq@nuxwin.com>
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

use iMSCP_Plugin as Plugin;
use iMSCP_Plugin_Exception as PluginException;
use iMSCP_Plugin_Manager as PluginManager;

/**
 * Class iMSCP_Plugin_RoundcubePlugins
 */
class iMSCP_Plugin_RoundcubePlugins extends Plugin
{
    /**
     * {@inheritdoc}
     */
    public function update(PluginManager $pm, $fromVersion, $toVersion)
    {
        try {
            $this->checkRequirements();

            if (version_compare($fromVersion, '3.0.0', '>=')) {
                return;
            }

            $this->migrateDb('down');
        } catch (Exception $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function enable(PluginManager $pm)
    {
        if (!in_array('Roundcube', getWebmailList())) {
            throw new DomainException(tr('This plugin requires the i-MSCP Roundcube package.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(PluginManager $pm)
    {
        // Only there to tell the plugin manager that this plugin provide uninstallation routine (backend).
    }

    /**
     * Check for plugin requirements
     *
     * @return void
     */
    protected function checkRequirements()
    {
        if (!in_array('Roundcube', getWebmailList())) {
            throw new DomainException(tr('This plugin requires the i-MSCP Roundcube package.'));
        }
    }
}
