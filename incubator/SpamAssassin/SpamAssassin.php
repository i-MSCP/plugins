<?php
/**
 * i-MSCP SpamAssassin plugin
 * Copyright (C) 2015-2019 Laurent Declercq <l.declercq@nuxwin.com>
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

/**
 * Class iMSCP_Plugin_SpamAssassin
 */
class iMSCP_Plugin_SpamAssassin extends iMSCP_Plugin_Action
{
    /**
     * @inheritdoc
     */
    public function register(iMSCP_Events_Manager_Interface $events)
    {
        $events->registerListener(iMSCP_Events::onBeforeDeleteMail, $this);
    }

    /**
     * Delete bayesian data and user preferences that belong to the mail account being deleted
     *
     * @param iMSCP_Events_Event $event
     * @return void
     * @throws Exception
     */
    public function onBeforeDeleteMail(iMSCP_Events_Event $event)
    {
        $db = iMSCP_Database::getInstance();

        try {
            $db->beginTransaction();
            $stmt = exec_query(
                'SELECT mail_addr FROM mail_users WHERE mail_id = ?',
                [$event->getParam('mailId')]
            );
            if (!$stmt->rowCount()) {
                return;
            }

            $username = $stmt->fetchRow(PDO::FETCH_COLUMN);
            $saDbName = quoteIdentifier(
                iMSCP_Registry::get('config')['DATABASE_NAME'] . '_spamassassin'
            );

            exec_query(
                "
                    DELETE t1 FROM $saDbName.bayes_token AS t1
                    JOIN $saDbName.bayes_vars AS t2 USING(id)
                    WHERE t2.username = ?
                ",
                [$username]
            );
            exec_query(
                "
                    DELETE t1 FROM $saDbName.bayes_seen AS t1
                    JOIN $saDbName.bayes_vars AS t2 USING(id)
                    WHERE t2.username = ?
                ",
                [$username]
            );
            exec_query("DELETE FROM $saDbName.bayes_vars WHERE username = ?", [
                $username
            ]);
            exec_query("DELETE FROM $saDbName.userpref WHERE username = ?", [
                $username
            ]);

            $db->commit();
        } catch (iMSCP_Exception_Database $e) {
            $db->rollBack();
            throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function install(iMSCP_Plugin_Manager $pm)
    {
        try {
            $this->migrateDb('up');
        } catch (Exception $e) {
            throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function uninstall(iMSCP_Plugin_Manager $pm)
    {
        try {
            $this->migrateDb('down');
        } catch (Exception $e) {
            throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function update(iMSCP_Plugin_Manager $pm, $fromVersion, $toVersion)
    {
        try {
            $this->migrateDb('up');
        } catch (Exception $e) {
            throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function enable(iMSCP_Plugin_Manager $pm)
    {
        try {
            $this->setSpamAssassinServicePort();
        } catch (Exception $e) {
            throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function disable(iMSCP_Plugin_Manager $pm)
    {
        try {
            $dbConfig = iMSCP_Registry::get('dbConfig');
            unset($dbConfig['PORT_SPAMASSASSIN']);
        } catch (Exception $e) {
            throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
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
        $dbConfig = iMSCP_Registry::get('dbConfig');
        $pluginConfig = $this->getConfig();

        if (preg_match("/-(?:p\s+|-port=)(\d+)/", $pluginConfig['spamd_options']['options'], $spamAssassinPort)) {
            $dbConfig['PORT_SPAMASSASSIN'] = $spamAssassinPort[1] . ';tcp;SPAMASSASSIN;1;127.0.0.1';
            return;
        }

        unset($dbConfig['PORT_SPAMASSASSIN']);
    }
}
