<?php
/**
 * i-MSCP OpenDKIM plugin
 * Copyright (C) 2013-2017 Laurent Declercq <l.declercq@nuxwin.com>
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

use iMSCP_Events as Events;
use iMSCP_Events_Event as Event;
use iMSCP_Events_Manager_Interface as EventsManagerInterface;
use iMSCP_Plugin_Action as PluginAction;
use iMSCP_Plugin_Exception as PluginException;
use iMSCP_Plugin_Manager as PluginManager;
use iMSCP_Registry as Registry;

/**
 * Class iMSCP_Plugin_OpenDKIM
 */
class iMSCP_Plugin_OpenDKIM extends PluginAction
{
    /**
     * Plugin initialization
     *
     * @return void
     */
    public function init()
    {
        l10n_addTranslations(__DIR__ . '/l10n', 'Array', $this->getName());
    }

    /**
     * Register event listeners
     *
     * @param EventsManagerInterface $eventsManager
     * @return void
     */
    public function register(EventsManagerInterface $eventsManager)
    {
        $eventsManager->registerListener(
            [
                Events::onResellerScriptStart,
                Events::onClientScriptStart,
                Events::onAfterAddDomainAlias,
                Events::onAfterDeleteDomainAlias,
                Events::onAfterDeleteCustomer
            ],
            $this
        );
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
            $this->clearTranslations();
            Registry::get('dbConfig')->del('PORT_OPENDKIM');
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
            $this->clearTranslations();
        } catch (Exception $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * onResellerScriptStart event listener
     *
     * @return void
     */
    public function onResellerScriptStart()
    {
        $this->setupNavigation('reseller');
    }

    /**
     * onClientScriptStart event listener
     *
     * @return void
     */
    public function onClientScriptStart()
    {
        if (self::customerHasOpenDKIM($_SESSION['user_id'])) {
            $this->setupNavigation('client');
        }
    }

    /**
     * onAfterDeleteCustomer event listener
     *
     * @param Event $event
     * @return void
     */
    public function onAfterDeleteCustomer(Event $event)
    {
        exec_query(
            "UPDATE opendkim SET opendkim_status = 'todelete' WHERE admin_id = ?", $event->getParam('customerId')
        );
    }

    /**
     * onAfterAddDomainAlias event listener
     *
     * @param Event $event
     * @return void
     */
    public function onAfterAddDomainAlias(Event $event)
    {
        // Check that the domain alias is being added and not simply ordered
        $stmt = exec_query(
            "SELECT alias_id FROM domain_aliasses WHERE alias_id = ? AND alias_status = 'toadd'",
            $event->getParam('domainAliasId')
        );

        if (!$stmt->rowCount()) {
            return;
        }

        // In case OpenDKIM is activated for the parent domain, we must activate it also for the domain alias which
        // is being added
        $stmt = exec_query(
            "SELECT admin_id FROM opendkim WHERE domain_id = ? AND alias_id IS NULL AND opendkim_status = 'ok'",
            $event->getParam('domainId')
        );

        if (!$stmt->rowCount()) {
            return;
        }

        $row = $stmt->fetchRow(PDO::FETCH_ASSOC);
        exec_query(
            "
                INSERT INTO opendkim (
                    admin_id, domain_id, alias_id, domain_name, opendkim_status
                ) VALUES (
                    ?, ?, ?, ?, 'toadd'
                )
            ",
            [
                $row['admin_id'], $event->getParam('domainId'), $event->getParam('domainAliasId'),
                encode_idna($event->getParam('domainAliasName'))
            ]
        );
    }

    /**
     * onAfterDeleteDomainAlias event listener
     *
     * @param Event $event
     * @return void
     */
    public function onAfterDeleteDomainAlias(Event $event)
    {
        exec_query(
            "UPDATE opendkim SET opendkim_status = 'todelete' WHERE alias_id = ?", $event->getParam('domainAliasId')
        );
    }

    /**
     * Get routes
     *
     * @return array
     */
    public function getRoutes()
    {
        $pluginDir = $this->getPluginManager()->pluginGetDirectory() . '/' . $this->getName();
        return [
            '/reseller/opendkim.php' => $pluginDir . '/frontend/reseller/opendkim.php',
            '/client/opendkim.php'   => $pluginDir . '/frontend/client/opendkim.php'
        ];
    }

    /**
     * Get status of item with errors
     *
     * @return array
     */
    public function getItemWithErrorStatus()
    {
        $stmt = exec_query(
            "
                SELECT opendkim_id AS item_id, opendkim_status AS status, domain_name AS item_name,
                    'opendkim' AS `table`, 'opendkim_status' AS field
                FROM opendkim
                WHERE opendkim_status NOT IN('ok', 'toadd', 'tochange', 'todelete')
            "
        );

        if ($stmt->rowCount()) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return [];
    }

    /**
     * Set status of the given plugin item to 'tochange'
     *
     * @param string $table Table name
     * @param string $field Status field name
     * @param int $itemId OpenDKIM item unique identifier
     * @return void
     */
    public function changeItemStatus($table, $field, $itemId)
    {
        if ($table == 'opendkim' && $field == 'opendkim_status') {
            exec_query("UPDATE opendkim SET opendkim_status = 'tochange' WHERE opendkim_id = ?", $itemId);
        }
    }

    /**
     * Return count of request in progress
     *
     * @return int
     */
    public function getCountRequests()
    {
        return execute_query(
            "SELECT COUNT(opendkim_id) FROM opendkim WHERE opendkim_status IN ('toadd', 'tochange', 'todelete')"
        )->fetchRow(PDO::FETCH_COLUMN);
    }

    /**
     * Does the given customer has OpenDKIM feature activated?
     *
     * @param int $customerId Customer unique identifier
     * @return bool
     */
    public static function customerHasOpenDKIM($customerId)
    {
        static $hasAccess = NULL;

        if (NULL === $hasAccess) {
            $hasAccess = (bool)exec_query(
                "
                    SELECT COUNT(admin_id)
                    FROM opendkim
                    INNER JOIN admin USING(admin_id)
                    WHERE admin_id = ?
                    AND admin_status = 'ok'
                ",
                $customerId
            )->fetchRow(PDO::FETCH_COLUMN);
        }

        return $hasAccess;
    }

    /**
     * Inject OpenDKIM links into the navigation object
     *
     * @param string $level UI level
     * @return void;
     */
    protected function setupNavigation($level)
    {
        if (!Registry::isRegistered('navigation')) {
            return;
        }

        /** @var Zend_Navigation $navigation */
        $navigation = Registry::get('navigation');

        if ($level == 'reseller') {
            if (($page = $navigation->findOneBy('uri', '/reseller/users.php'))) {
                $page->addPage([
                    'label'              => tr('OpenDKIM'),
                    'uri'                => '/reseller/opendkim.php',
                    'title_class'        => 'users',
                    'privilege_callback' => [
                        'name' => 'resellerHasCustomers'
                    ]
                ]);
            }
            return;
        }

        if ($level == 'client') {
            if (($page = $navigation->findOneBy('uri', '/client/domains_manage.php'))) {
                $page->addPage([
                    'label'       => tr('OpenDKIM'),
                    'uri'         => '/client/opendkim.php',
                    'title_class' => 'domains'
                ]);
            }
        }
    }

    /**
     * Clear translations if any
     *
     * @return void
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
