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

namespace iMSCP\Plugin;

use iMSCP_Events as Events;
use iMSCP_Events_Description as Event;
use iMSCP_Events_Manager_Interface as EventManagerInterface;
use iMSCP_Plugin_Action as PluginAction;
use iMSCP_Plugin_Exception as PluginException;
use iMSCP_Plugin_Manager as PluginManager;
use iMSCP_Registry as Registry;

/**
 * Class RoundcubePlugins
 * @package iMSCP\Plugin
 */
class RoundcubePlugins extends PluginAction
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        l10n_addTranslations(__DIR__ . '/l10n', 'Array', 'RoundcubePlugins');
    }

    /**
     * {@inheritdoc}
     */
    public function register(EventManagerInterface $em)
    {
        if ($this->getPluginManager()->pluginIsEnabled('RoundcubePlugins')) {
            return;
        }

        $em->registerListener(Events::onBeforeEnablePlugin, function (Event $e) {
            if ($e->getParam('pluginName') != 'RoundcubePlugins') {
                return;
            }

            if (!in_array('Roundcube', getWebmailList())) {
                set_page_message(tr('This plugin requires the i-MSCP Roundcube package.'), 'error');
                $e->stopPropagation();
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(PluginManager $pm)
    {
        try {
            $this->clearTranslations();
        } catch (\Exception $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Clear translations if any
     *
     * @return void
     */
    protected function clearTranslations()
    {
        /** @var \Zend_Translate $translator */
        $translator = Registry::get('translator');
        if ($translator->hasCache()) {
            $translator->clearCache('RoundcubePlugins');
        }
    }
}
