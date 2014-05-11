<?php
/**
 * i-MSCP JailKit plugin
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
 * Class iMSCP_Plugin_JailKit
 *
 * @author Laurent Declercq <l.declercq@nuxwin.com>
 */
class iMSCP_Plugin_JailKit extends iMSCP_Plugin_Action
{
    /**
     * Register a callback for the given event(s).
     *
     * @param iMSCP_Events_Manager_Interface $eventsManager
     */
    public function register(iMSCP_Events_Manager_Interface $eventsManager)
    {
        $eventsManager->registerListener(
            array(
                iMSCP_Events::onBeforeInstallPlugin,
                iMSCP_Events::onResellerScriptStart,
                iMSCP_Events::onClientScriptStart,
                iMSCP_Events::onAfterDeleteCustomer,
                iMSCP_Events::onAfterChangeDomainStatus
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
     * Plugin installation
     *
     * @throws iMSCP_Plugin_Exception
     * @param iMSCP_Plugin_Manager $pluginManager
     * @return void
     */
    public function install(iMSCP_Plugin_Manager $pluginManager)
    {
        try {
            $this->migrateDb('up');
        } catch (iMSCP_Plugin_Exception $e) {
            throw new iMSCP_Plugin_Exception(sprintf('Unable to install: %s', $e->getMessage()), $e->getCode(), $e);
        }

        set_page_message(tr('JailKit Plugin: This task can take few minutes. Please, be patient.'), 'warning');
    }

    /**
     * Plugin uninstallation
     *
     * @throws iMSCP_Plugin_Exception
     * @param iMSCP_Plugin_Manager $pluginManager
     * @return void
     */
    public function uninstall(iMSCP_Plugin_Manager $pluginManager)
    {
        try {
            $this->migrateDb('down');
        } catch (iMSCP_Plugin_Exception $e) {
            throw new iMSCP_Plugin_Exception(tr('Unable to uninstall: %s', $e->getMessage()), $e->getCode(), $e);
        }
    }

    /**
     * Plugin update
     *
     * @throws iMSCP_Plugin_Exception When update fail
     * @param iMSCP_Plugin_Manager $pluginManager
     * @param string $fromVersion Version from which plugin update is initiated
     * @param string $toVersion Version to which plugin is updated
     * @return void
     */
    public function update(iMSCP_Plugin_Manager $pluginManager, $fromVersion, $toVersion)
    {
        try {
            $this->migrateDb('up');
        } catch (iMSCP_Plugin_Exception $e) {
            throw new iMSCP_Plugin_Exception(tr('Unable to update: %s', $e->getMessage()), $e->getCode(), $e);
        }
    }

    /**
     * Plugin activation
     *
     * @param iMSCP_Plugin_Manager $pluginManager
     * @return void
     */
    public function enable(iMSCP_Plugin_Manager $pluginManager)
    {
        if ($pluginManager->getPluginStatus($this->getName()) == 'toenable') {
            set_page_message(tr('JailKit Plugin: This task can take few seconds. Please, be patient.'), 'warning');
        }
    }

    /**
     * Plugin deactivation
     *
     * @param iMSCP_Plugin_Manager $pluginManager
     * @return void
     */
    public function disable(iMSCP_Plugin_Manager $pluginManager)
    {
        $pluginStatus = $pluginManager->getPluginStatus($this->getName());

        if ($pluginStatus == 'tochange' || $pluginStatus == 'todisable') {
            set_page_message(tr('JailKit Plugin: This task can take few seconds. Please, be patient.'), 'warning');
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
        $this->setupNavigation('client');
    }

    /**
     * onAfterDeleteCustomer event listener
     *
     * @param iMSCP_Events_Event $event
     * @return void
     */
    public function onAfterDeleteCustomer($event)
    {
        exec_query(
            'UPDATE jailkit_jails SET jail_status = ? WHERE jail_owner_id = ?',
            array('todelete', $event->getParam('customerId'))
        );
    }

    /**
     * onAfterChangeDomainStatus event listener
     *
     * @param iMSCP_Events_Event $event
     * @return void
     */
    public function onAfterChangeDomainStatus($event)
    {
        if ($event->getParam('action') == 'enable') {
            $bindParams = array('ok', '0', 'tochange', $event->getParam('customerId'));
        } else {
            $bindParams = array('disabled', '1', 'tochange', $event->getParam('customerId'));
        }

        exec_query(
            '
                UPDATE
                    jailkit_jails
                LEFT JOIN
                    jailkit_ssh_logins USING(jail_id)
                SET
                    jail_status = ?, ssh_login_locked = ?, ssh_login_status = ?
                WHERE
                    jail_owner_id = ?
            ',
            $bindParams
        );
    }

    /**
     * Get routes
     *
     * @return array
     */
    public function getRoutes()
    {
        $pluginDir = PLUGINS_PATH . '/' . $this->getName();

        return array(
            '/reseller/ssh_accounts.php' => $pluginDir . '/frontend/reseller/jailkit.php',
            '/client/ssh_users.php' => $pluginDir . '/frontend/client/jailkit.php'
        );
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
                SELECT
                    jail_id AS item_id, jail_status AS status, admin_name AS item_name, 'jailkit_jails' AS `table`,
                    'jail_status' AS field
                INNER JOIN
                  admin ON(admin_id = jail_owner_id)
                FROM
                    jailkit_jails
                WHERE
                    jail_status NOT IN(?, ?, ?, ?, ?, ?, ?)
                UNION
                SELECT
                    ssh_login_id AS item_id, ssh_login_status AS status, ssh_login_name AS item_name,
                    'jailkit_ssh_logins' AS `table`, 'ssh_login_status'AS field
                FROM
                    jailkit_ssh_logins
                WHERE
                    ssh_login_status NOT IN(?, ?, ?, ?, ?, ?, ?)
            ",
            array(
                'ok', 'disabled', 'toadd', 'tochange', 'toenable', 'todisable', 'todelete',
                'ok', 'disabled', 'toadd', 'tochange', 'toenable', 'todisable', 'todelete'
            )
        );

        if ($stmt->rowCount()) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return array();
    }

    /**
     * Set status of the given plugin item to 'tochange'
     *
     * @param string $table Table name
     * @param string $field Status field name
     * @param int $itemId JailKit item unique identifier
     * @return void
     */
    public function changeItemStatus($table, $field, $itemId)
    {
        if ($table == 'jailkit_jails' && $field == 'jail_status') {
            exec_query('UPDATE jailkit_jails SET jail_status = ? WHERE jail_id = ?', array('tochange', $itemId));
        } elseif ($table == 'jailkit_logins' && $field == 'ssh_login_status') {
            exec_query(
                'UPDATE jailkit_ssh_logins SET ssh_login_status = ? WHERE ssh_login_id = ?',
                array('tochange', $itemId)
            );
        }
    }

    /**
     * Return count of request in progress
     *
     * @return int
     */
    public function getCountRequests()
    {
        $stmt = exec_query(
            '
                SELECT
                (
                    (SELECT COUNT(jai_id) FROM jailkit_jails WHERE jail_status IN (?, ?, ?, ?, ?, ?))
                    +
                    (SELECT COUNT(ssh_login_id) FROM jailkit_ssh_logins WHERE ssh_login_status IN (?, ?, ?, ?, ?, ?))
                ) AS cnt
            ',
            array(
                'disabled', 'toadd', 'tochange', 'toenable', 'todisable', 'todelete',
                'disabled', 'toadd', 'tochange', 'toenable', 'todisable', 'todelete'
            )
        );

        return $stmt->fields['cnt'];
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

    /**
     * Setup plugin navigation
     *
     * @param string $uiLevel Current UI level
     * @return void
     */
    protected function setupNavigation($uiLevel)
    {
        if (iMSCP_Registry::isRegistered('navigation')) {
            /** @var Zend_Navigation $navigation */
            $navigation = iMSCP_Registry::get('navigation');

            if ($uiLevel == 'reseller' && ($page = $navigation->findOneBy('uri', '/reseller/users.php'))) {
                $page->addPage(
                    array(
                        'label' => tr('SSH Accounts'),
                        'uri' => '/reseller/ssh_accounts.php',
                        'title_class' => 'users',
                        'privilege_callback' => array('name' => 'resellerHasCustomers')
                    )
                );
            } elseif ($uiLevel == 'client' && ($page = $navigation->findOneBy('uri', '/client/domains_manage.php'))) {
                $page->addPage(
                    array(
                        'label' => tr('SSH Users'),
                        'uri' => '/client/ssh_users.php',
                        'title_class' => 'users',
                        'privilege_callback' => array('name' => array($this, 'clientPrivilegeCallback'))
                    )
                );
            }
        }
    }

    /**
     * Client privilege callback
     *
     * @return bool
     */
    public function clientPrivilegeCallback()
    {
        $stmt = exec_query('SELECT jail_status FROM jailkitèjails WHERE admin_id = ?', $_SESSION['user_id']);

        if ($stmt->rowCount()) {
            $row = $stmt->fetchRow(PDO::FETCH_ASSOC);
            $jailStatus = $row['jail_status'];

            if ($jailStatus != 'ok') {
                if ($_SERVER['SCRIPT_NAME'] == '/client/ssh_users.php') {
                    redirectTo('domains_manage.php');
                }

                if ($_SERVER['SCRIPT_NAME'] == '/client/domains_manage.php') {
                    if ($jailStatus == 'disabled') {
                        set_page_message(tr('SSH feature has been disabled by your reseller.'), 'warning');
                    } elseif ($jailStatus != 'toadd' && $jailStatus != 'todelete') {
                        set_page_message(
                            tr('SSH feature is currently unavailable due to maintenance operation.'), 'warning'
                        );
                    }
                }
            } else {
                return true;
            }
        } elseif ($_SERVER['SCRIPT_NAME'] == '/client/ssh_users.php') {
            showBadRequestErrorPage();
            exit;
        }

        return false;
    }
}
