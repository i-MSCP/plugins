<?php
/**
 * i-MSCP Mailgraph plugin
 * Copyright (C) 2010-2017 Laurent Declercq <l.declercq@nuxwin.com>
 * Copyright (C) 2010-2016 Sascha Bay <info@space2place.de>
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
use iMSCP_Events_Manager_Interface as EventsMananagerInterface;
use iMSCP_Plugin_Action as PluginAction;
use iMSCP_Plugin_Manager as PluginManager;
use iMSCP_Registry as Registry;

/**
 * Class iMSCP_Plugin_Mailgraph
 */
class iMSCP_Plugin_Mailgraph extends PluginAction
{
    /**
     * @inheritdoc
     */
    public function register(EventsMananagerInterface $eventsManager)
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
        return array(
            '/admin/mailgraph.php'    =>__DIR__ . '/frontend/mailgraph.php',
            '/admin/mailgraphics.php' => __DIR__ . '/frontend/mailgraphics.php'
        );
    }

    /**
     * Inject Mailgraph links into the navigation object
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
        $navigation = Registry::get('navigation');

        if (($page = $navigation->findOneBy('uri', '/admin/server_statistic.php'))) {
            $page->addPage(array(
                'label'       => tr('Mailgraph'),
                'uri'         => '/admin/mailgraph.php',
                'title_class' => 'stats'
            ));
        }
    }
}
