<?php
/**
 * i-MSCP Monitorix plugin
 * Copyright (C) 2013-2017 Laurent Declercq <l.declercq@nuxwin.com>
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

use iMSCP_Events as Events;
use iMSCP_Events_Manager_Interface as EventsManagerInterface;
use iMSCP_Plugin_Action as PluginAction;
use iMSCP_Plugin_Exception as PluginException;
use iMSCP_Plugin_Manager as PluginManager;
use iMSCP_Registry as Registry;

/**
 * Class iMSCP_Plugin_Monitorix
 */
class iMSCP_Plugin_Monitorix extends PluginAction
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        l10n_addTranslations(__DIR__ . '/l10n', 'Array', $this->getName());
    }

    /**
     * @inheritdoc
     */
    public function register(EventsManagerInterface $eventsManager)
    {
        $eventsManager->registerListener(Events::onAdminScriptStart, $this);
    }

    /**
     * @inheritdoc
     */
    public function install(PluginManager $pluginManager)
    {
        // Only there to tell the plugin manager that this plugin is installable
    }

    /**
     * @inheritdoc
     */
    public function uninstall(PluginManager $pluginManager)
    {
        try {
            $this->clearTranslations();
        } catch (Exception $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function update(PluginManager $pluginManager, $fromVersion, $toVersion)
    {
        try {
            $this->clearTranslations();
        } catch (Exception $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * onAdminScriptStart listener
     *
     * @return void
     * @throws Zend_Exception
     * @throws Zend_Navigation_Exception
     */
    public function onAdminScriptStart()
    {
        $this->setupNavigation();
    }

    /**
     * @inheritdoc
     */
    public function getRoutes()
    {
        $pluginDir = $this->getPluginManager()->pluginGetDirectory() . '/' . $this->getName();

        return array(
            '/admin/monitorix.php'         => $pluginDir . '/frontend/monitorix.php',
            '/admin/monitorixgraphics.php' => $pluginDir . '/frontend/monitorixgraphics.php'
        );
    }

    /**
     * Inject Monitorix links into the navigation object
     *
     * @return void
     * @throws Zend_Exception
     * @throws Zend_Navigation_Exception
     */
    protected function setupNavigation()
    {
        if (!Registry::isRegistered('navigation')) {
            return;
        }

        /** @var Zend_Navigation $navigation */
        $navigation = iMSCP_Registry::get('navigation');

        if (!($page = $navigation->findOneBy('uri', '/admin/server_statistic.php'))) {
            return;
        }

        $page->addPage(array(
            'label'       => tr('Monitorix'),
            'uri'         => '/admin/monitorix.php',
            'title_class' => 'stats'
        ));
    }

    /**
     * Clear translations if any
     *
     * @return void
     * @throws Zend_Exception
     * @throws iMSCP_Plugin_Exception
     */
    protected function clearTranslations()
    {
        /** @var Zend_Translate $translator */
        $translator = Registry::get('translator');

        if ($translator->hasCache()) {
            $translator->clearCache($this->getName());
        }
    }
}
