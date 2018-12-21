<?php
/**
 * i-MSCP DomainAutoApproval plugin
 * Copyright (C) 2012-2018 Laurent Declercq <l.declercq@nuxwin.com>
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
 *
 * @noinspection PhpUndefinedFunctionInspection PhpUnhandledExceptionInspection PhpDocMissingThrowsInspection
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
     * @inheritdoc
     */
    public function register(EventsManagerInterface $em)
    {
        // Registers the listener with a low priority to let other listeners
        // operate before the redirect
        $em->registerListener(Events::onAfterAddDomainAlias, $this, -99);
    }

    /**
     * onAfterAddDomainAlias listener
     *
     * @throws Exception
     * @param Event $e
     * @return void
     */
    public function onAfterAddDomainAlias(Event $e)
    {
        $identity = Authentication::getInstance()->getIdentity();

        // Only act when event has been triggered from client UI
        if ($identity->admin_type !== 'user') {
            return;
        }

        // Domain alias being added (punycode representation)
        $alias = $e->getParam('domainAliasName');

        // Domain aliases that need to be ignored by this plugin, regardless
        // value of both the 'approval_rule' and 'client_accounts' parameters.
        if (in_array($alias, $this->getConfigParam('ignored_domain_aliases', []))) {
            return;
        }

        $rule = $this->getConfigParam('approval_rule', false);
        $isClientListed = in_array($identity->admin_name, $this->getConfigParam('client_accounts', []));

        // 1. Domain aliases owned by a listed client must be ignored
        // 2. Domain aliases owned by an unlisted client must be ignored
        if ((!$rule && $isClientListed) || ($rule && !$isClientListed)) {
            return;
        }

        $aliasId = $e->getParam('domainAliasId');
        exec_query("UPDATE domain_aliasses SET alias_status = 'toadd' WHERE alias_id = ?", [$aliasId]);
        $isAtLeast15x = version_compare($this->getPluginManager()->pluginGetApiVersion(), '1.5.0', '>=');

        $config = Registry::get('config');
        if ($config['CREATE_DEFAULT_EMAIL_ADDRESSES']) {
            if ($isAtLeast15x) {
                createDefaultMailAccounts(get_user_domain_id($identity->admin_id), $identity->email, $alias, MT_ALIAS_FORWARD, $aliasId);
            } else {
                client_mail_add_default_accounts(get_user_domain_id($identity->admin_id), $identity->email, $alias, 'alias', $aliasId);
            }
        }

        // Bypass default workflow by committing changes and redirecting
        Database::getInstance()->commit();
        send_request();
        $alias = decode_idna($alias);
        write_log(sprintf("DomainAutoApproval: The '%s' domain alias has been automatically approved.", $alias), E_USER_NOTICE);
        write_log(
            $isAtLeast15x
                ? sprintf('A new `%s` domain alias has been created by %s', $alias, $identity->admin_name)
                : sprintf('A new `%s` domain alias has been created by: %s', $alias, $identity->admin_name),
            E_USER_NOTICE
        );
        set_page_message(tr('Domain alias successfully created.'), 'success');
        redirectTo('domains_manage.php');
    }
}
