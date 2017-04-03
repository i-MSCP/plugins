<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2017 Laurent Declercq <l.declercq@nuxwin.com>
 * Copyright (C) 2013-2016 Rene Schuster <mail@reneschuster.de>
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

use iMSCP_Events_Event as Event;
use iMSCP_Events_Manager_Interface as EventManagerInterface;
use iMSCP_Plugin_Action as PluginAction;
use iMSCP_Plugin_Exception as PluginException;
use iMSCP_Plugin_Manager as PluginManager;
use iMSCP_Registry as Registry;

/**
 * Class iMSCP_Plugin_RoundcubePlugins
 */
class iMSCP_Plugin_RoundcubePlugins extends PluginAction
{
    /**
     * Register a callback for the given event(s)
     *
     * @param EventManagerInterface $eventsManager
     */
    public function register(EventManagerInterface $eventsManager)
    {
        $eventsManager->registerListener(
            array(
                iMSCP_Events::onBeforeInstallPlugin,
                iMSCP_Events::onBeforeUpdatePlugin,
                iMSCP_Events::onBeforeEnablePlugin
            ),
            $this
        );
    }

    /**
     * onBeforeInstallPlugin event listener
     *
     * @param Event $event
     * @return void
     */
    public function onBeforeInstallPlugin(Event $event)
    {
        if ($event->getParam('pluginName') != $this->getName()) {
            return;
        }

        if (!$this->checkCompat()) {
            $event->stopPropagation();
        }
    }

    /**
     * Plugin installation
     *
     * @throws PluginException
     * @param PluginManager $pluginManager
     * @return void
     */
    public function install(PluginManager $pluginManager)
    {
        try {
            $this->migrateDb('up');
        } catch (Exception $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Plugin uninstallation
     *
     * @throws PluginException
     * @param PluginManager $pluginManager
     * @return void
     */
    public function uninstall(PluginManager $pluginManager)
    {
        try {
            $this->migrateDb('down');
        } catch (Exception $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * onBeforeUpdatePlugin event listener
     *
     * @param Event $event
     * @return void
     */
    public function onBeforeUpdatePlugin(Event $event)
    {
        if ($event->getParam('pluginName') != $this->getName()) {
            return;
        }

        if (!$this->checkCompat()) {
            $event->stopPropagation();
        }
    }

    /**
     * Plugin update
     *
     * @throws PluginException When update fail
     * @param PluginManager $pluginManager
     * @param string $fromVersion Version from which plugin update is initiated
     * @param string $toVersion Version to which plugin is updated
     * @return void
     */
    public function update(PluginManager $pluginManager, $fromVersion, $toVersion)
    {
        try {
            $this->migrateDb('up');
        } catch (Exception $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * onBeforeEnablePlugin listener
     *
     * @param Event $event
     * @return void
     */
    public function onBeforeEnablePlugin(Event $event)
    {
        if ($event->getParam('pluginName') != $this->getName()) {
            return;
        }

        if (!$this->checkCompat()) {
            $event->stopPropagation();
        }
    }

    /**
     * Plugin enable
     *
     * @throws PluginException
     * @param PluginManager $pluginManager
     * @return void
     */
    public function enable(PluginManager $pluginManager)
    {
        try {
            $this->addDovecotSieveServicePort();
        } catch (Exception $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Plugin disable
     *
     * @throws PluginException
     * @param PluginManager $pluginManager
     * @return void
     */
    public function disable(PluginManager $pluginManager)
    {
        try {
            $this->removeDovecotSieveServicePort();
        } catch (Exception $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Check plugin compatibility
     *
     * @return bool TRUE if all requirements are met, FALSE otherwise
     */
    protected function checkCompat()
    {
        $config = Registry::get('config');
        if (isset($config['WEBMAIL_PACKAGES']) && !in_array('Roundcube', getWebmailList())) {
            set_page_message(tr('This plugin requires the i-MSCP Roundcube package.'), 'error');
            return false;
        }

        return true;
    }

    /**
     * Add dovecot-sieve service port
     *
     * @return void
     */
    protected function addDovecotSieveServicePort()
    {
        $dbConfig = Registry::get('dbConfig');
        if (strtolower($this->getConfigParam('managesieve_plugin', 'no')) == 'yes') {
            if (!isset($dbConfig['PORT_DOVECOT-SIEVE'])) {
                $dbConfig['PORT_DOVECOT-SIEVE'] = '4190;tcp;DOVECOT-SIEVE;1;127.0.0.1';
            }
            return;
        }

        $this->removeDovecotSieveServicePort();
    }

    /**
     * Remove dovecot-sieve service port
     *
     * @return void
     */
    protected function removeDovecotSieveServicePort()
    {
        $dbConfig = Registry::get('dbConfig');
        if (isset($dbConfig['PORT_DOVECOT-SIEVE'])) {
            unset($dbConfig['PORT_DOVECOT-SIEVE']);
        }
    }
}
