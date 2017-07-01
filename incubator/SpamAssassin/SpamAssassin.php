<?php
/**
 * i-MSCP SpamAssassin plugin
 * Copyright (C) 2015-2017 Laurent Declercq <l.declercq@nuxwin.com>
 * Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>
 * Copyright (C) 2013-2016 Rene Schuster <mail@reneschuster.de>
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

use iMSCP_Database as Database;
use iMSCP_Events as Events;
use iMSCP_Events_Event as Event;
use iMSCP_Events_Manager_Interface as EventsManagerInterface;
use iMSCP_Exception_Database as ExceptionDatabase;
use iMSCP_Plugin_Action as PluginAction;
use iMSCP_Plugin_Exception as PluginException;
use iMSCP_Plugin_Manager as PluginManager;
use iMSCP_Registry as Registry;

/**
 * Class iMSCP_Plugin_SpamAssassin
 */
class iMSCP_Plugin_SpamAssassin extends PluginAction
{
    /**
     * Register a callback for the given event(s)
     *
     * @param EventsManagerInterface $eventsManager
     * @return void
     */
    public function register(EventsManagerInterface $eventsManager)
    {
        $eventsManager->registerListener(Events::onBeforeDeleteMail, $this);
    }

    /**
     * Delete bayesian data and user preferences that belong to the  mail account being deleted
     *
     * @param Event $e
     * @throws PluginException
     * @return void
     */
    public function onBeforeDeleteMail(Event $e)
    {
        $db = Database::getInstance();

        try {
            $db->beginTransaction();
            $stmt = exec_query('SELECT mail_addr FROM mail_users WHERE mail_id = ?', $e->getParam('mailId'));
            if (!$stmt->rowCount()) {
                return;
            }

            $username = $stmt->fetchRow(PDO::FETCH_COLUMN);
            $cfg = Registry::get('config');
            $saDbName = quoteIdentifier($cfg['DATABASE_NAME'] . '_spamassassin');

            exec_query(
                "
                    DELETE v, t, s
                    FROM $saDbName.bayes_vars v
                    LEFT JOIN $saDbName.bayes_token t ON t.id = v.id
                    LEFT JOIN $saDbName.bayes_seen s ON s.id = v.id
                    WHERE v.username = ?
                ",
                $username
            );
            exec_query("DELETE u FROM $saDbName.userpref u WHERE u.username = ?", $username);

            $db->commit();
        } catch (ExceptionDatabase $e) {
            $db->rollBack();
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
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
     * Plugin update
     *
     * @throws PluginException
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
     * Plugin activation
     *
     * @throws PluginException
     * @param PluginManager $pluginManager
     * @return void
     */
    public function enable(PluginManager $pluginManager)
    {
        try {
            $this->setSpamAssassinServicePort();
        } catch (Exception $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Plugin deactivation
     *
     * @throws iMSCP_Plugin_Exception
     * @param PluginManager $pluginManager
     * @return void
     */
    public function disable(PluginManager $pluginManager)
    {
        try {
            $dbConfig = Registry::get('dbConfig');
            unset($dbConfig['PORT_SPAMASSASSIN']);
        } catch (Exception $e) {
            throw new PluginException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Set SpamAssassin service port
     *
     * Only relevant in TCP mode (default mode is UDS since v2.0.0)
     *
     * TODO: register check status function which should call:
     *  echo 'foo' | spamc -x --socket=/var/run/spamassassin.sock >/dev/null 2>1
     *
     * @return void
     */
    protected function setSpamAssassinServicePort()
    {
        $dbConfig = Registry::get('dbConfig');
        $pluginConfig = $this->getConfig();

        if (preg_match("/-(?:p\s+|-port=)(\d+)/", $pluginConfig['spamd_options']['options'], $spamAssassinPort)) {
            $dbConfig['PORT_SPAMASSASSIN'] = $spamAssassinPort[1] . ';tcp;SPAMASSASSIN;1;127.0.0.1';
        } else {
            unset($dbConfig['PORT_SPAMASSASSIN']);
        }
    }
}
