<?php

<?php
/**
 * i-MSCP KaziWhmcs plugin
 * Copyright (C) 2014 Laurent Declercq <l.declercq@nuxwin.com>
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
 * Class iMSCP_Plugin_KaziWhmcs
 */
class iMSCP_Plugin_KaziWhmcs extends iMSCP_Plugin_Action
{
    /**
     * @var array customer SSH permissions
     */
    protected $customerSshPermissions;

    /**
     * Plugin initialization
     *
     * @return void
     */
    public function init()
    {
        $pluginDirectory = iMSCP_Registry::get('pluginManager')->getPluginDirectory();

        // Set include path
        set_include_path(
            get_include_path() .
            PATH_SEPARATOR . $pluginDirectory . '/InstantSSH/library' .
            PATH_SEPARATOR . $pluginDirectory . '/InstantSSH/library/vendor/phpseclib'
        );
    }

    /**
     * Register event listeners
     *
     * @param $eventManager iMSCP_Events_Manager_Interface $eventManager
     * @return void
     */
    public function register(iMSCP_Events_Manager_Interface $eventManager)
    {
        $eventManager->registerListener(
            array(
                iMSCP_Events::onBeforeInstallPlugin,
                iMSCP_Events::onBeforeUpdatePlugin,
                iMSCP_Events::onBeforeEnablePlugin,
            ),
            $this
        );
    }

    /**
     * onBeforeInstallPlugin listener
     *
     * @param iMSCP_Events_Event $event
     * @return void
     */
    public function onBeforeInstallPlugin($event)
    {
        $this->checkCompat($event);
    }

    /**
     * onBeforeUpdatePlugin listener
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
     * Get routes
     *
     * @return array An array which map routes to action scripts
     */
    public function getRoutes()
    {
        return array(
            '/whmcs' => PLUGINS_PATH . '/' .  $this->getName() . '/whmcs.php',
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
            if (version_compare($event->getParam('pluginManager')->getPluginApiVersion(), '0.2.8', '<')) {
                set_page_message(
                    tr('Your i-MSCP version is not compatible with this plugin. Try with a newer version.'), 'error'
                );

                $event->stopPropagation();
            }
        }
    }
}
