<?php
/**
 * i-MSCP RoundcubePlugins plugin
 * Copyright (C) 2019 Laurent Declercq <l.declercq@nuxwin.com>
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

/**
 * Class iMSCP_Plugin_RoundcubePlugins
 */
class iMSCP_Plugin_RoundcubePlugins extends iMSCP_Plugin_Action
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
     * @throws iMSCP_Plugin_Exception
     */
    public function register(iMSCP_Events_Manager_Interface $em)
    {
        if ($this->getPluginManager()->pluginIsEnabled('RoundcubePlugins')) {
            return;
        }

        $em->registerListener(
            [
                iMSCP_Events::onBeforeInstallPlugin,
                iMSCP_Events::onBeforeEnablePlugin
            ],
            function (iMSCP_Events_Description $e) {
                if ($e->getParam('pluginName') != 'RoundcubePlugins') {
                    return;
                }

                if (!in_array('Roundcube', getWebmailClientPackages())) {
                    set_page_message(
                        tr('This plugin requires the i-MSCP Roundcube package.'),
                        'error'
                    );
                    $e->stopPropagation();
                }
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function install(iMSCP_Plugin_Manager $pm)
    {
        // Only there to tell the plugin manager that this plugin
        // is installable
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(iMSCP_Plugin_Manager $pm)
    {
        try {
            $this->clearTranslations();
        } catch (Exception $e) {
            throw new iMSCP_Plugin_Exception(
                $e->getMessage(), $e->getCode(), $e
            );
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
        $translator = iMSCP_Registry::get('translator');
        if ($translator->hasCache()) {
            $translator->clearCache('RoundcubePlugins');
        }
    }
}
