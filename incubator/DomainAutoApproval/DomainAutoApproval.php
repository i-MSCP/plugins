<?php
/**
 * i-MSCP DomainAutoApproval plugin
 * Copyright (C) 2012-2017 Laurent Declercq <l.declercq@nuxwin.com>
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

use iMSCP_Authentication as Authentication;
use iMSCP_Database as Database;
use iMSCP_Events as Events;
use iMSCP_Events_Event as Event;
use iMSCP_Events_Manager_Interface as EventsManagerInterface;
use iMSCP_Plugin_Action as PluginAction;
use iMSCP_Registry as Registry;

/**
 * Class iMSCP_Plugin_DomainAutoApproval
 */
class iMSCP_Plugin_DomainAutoApproval extends PluginAction
{
    /**
     * Register a callback for the given event(s)
     *
     * @param EventsManagerInterface $eventsManager
     * @return void
     */
    public function register(EventsManagerInterface $eventsManager)
    {
        # We register this listener with low priority to let any other plugin which listen on the same event a chance
        # to act before the redirect
        $eventsManager->registerListener(Events::onAfterAddDomainAlias, $this, -99);
    }

    /**
     * onAfterAddDomainAlias listener
     *
     * @throws Exception
     * @param Event $event
     * @return void
     */
    public function onAfterAddDomainAlias(Event $event)
    {
        $userIdentity = Authentication::getInstance()->getIdentity();

        // 1. Do not act if the logged-in user is not the real client (due to changes in i-MSCP v1.2.12)
        // 2. Do not act if the event has been triggered from reseller interface
        if (isset($_SESSION['logged_from_type']) || $userIdentity->admin_type == 'reseller') {
            return;
        }

        $disallowedDomains = (array)$this->getConfigParam('ignored_domains', array());
        $domainAliasNameAscii = $event->getParam('domainAliasName');

        if (in_array(decode_idna($domainAliasNameAscii), $disallowedDomains)) {
            return; # Only domain aliases which are not listed in the ignored_domains list are auto-approved
        }

        $username = decode_idna($userIdentity->admin_name);
        $approvalRule = $this->getConfigParam('approval_rule', true);
        $userAccounts = (array)$this->getConfigParam('user_accounts', array());

        # 1. Only domain aliases added by user which are listed in the 'user_accounts' list are auto-approved
        # 2. Only domain aliases added by user which are not listed in the 'user_accounts' list are auto-approved
        if (($approvalRule && !in_array($username, $userAccounts)) || in_array($username, $userAccounts)) {
            return;
        }

        $db = Database::getInstance();

        try {
            $db->beginTransaction();
            $domainAliasId = $event->getParam('domainAliasId');

            exec_query('UPDATE domain_aliasses SET alias_status = ? WHERE alias_id = ?', array('toadd', $domainAliasId));

            $config = Registry::get('config');
            if ($config['CREATE_DEFAULT_EMAIL_ADDRESSES'] && $userIdentity->email !== '') {
                client_mail_add_default_accounts(get_user_domain_id(
                    $userIdentity->admin_id), $userIdentity->email, $domainAliasNameAscii, 'alias', $domainAliasId
                );
            }

            $db->commit();
            send_request();
            write_log(sprintf('DomainAutoApproval plugin: The `%s` domain alias has been auto-approved', decode_idna($domainAliasNameAscii)), E_USER_NOTICE);
            set_page_message(tr('Domain alias auto-approved.'), 'success');
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
