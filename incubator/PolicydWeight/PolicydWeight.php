<?php
/**
 * i-MSCP PolicydWeight plugin
 * @copyright 2015-2017 Laurent Declercq <l.declercq@nuxwin.com>
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
use iMSCP_Plugin_Exception as PluginException;
use iMSCP_Plugin_Manager as PluginManager;
use iMSCP_Registry as Registry;

/**
 * Class iMSCP_Plugin_PolicydWeight
 */
class iMSCP_Plugin_PolicydWeight extends PluginAction
{
    /**
     * @inheritdoc
     */
    public function install(PluginManager $pluginManager)
    {
        // Only there to force installation context, else distribution packages
        // won't be installed and an error will be raised
    }

    /**
     * @inheritdoc
     */
    public function enable(PluginManager $pluginManager)
    {
        try {
            # Make sure that Postgrey smtp restriction is evaluated first. This is based on plugin_priority field.
            if ($pluginManager->pluginIsKnown('Postgrey') && $pluginManager->pluginIsEnabled('Postgrey')) {
                $pluginManager->pluginChange('Postgrey');
            }

            Registry::get('dbConfig')->set(
                'PORT_POLICYD_WEIGHT',
                $this->getConfigParam('policyd_weight_port', 12525) . ';tcp;POLICYD_WEIGHT;1;127.0.0.1'
            );
        } catch (Exception $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function disable(PluginManager $pluginManager)
    {
        try {
            Registry::get('dbConfig')->del('PORT_POLICYD_WEIGHT');
        } catch (Exception $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
