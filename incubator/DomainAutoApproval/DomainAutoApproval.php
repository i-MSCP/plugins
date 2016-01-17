<?php
/**
 * i-MSCP DomainAutoApproval plugin
 * Copyright (C) 2012-2016 Laurent Declercq <l.declercq@nuxwin.com>
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
 * Class iMSCP_Plugin_DomainAutoApproval
 */
class iMSCP_Plugin_DomainAutoApproval extends iMSCP_Plugin_Action
{
	/**
	 * Register a callback for the given event(s)
	 *
	 * @param iMSCP_Events_Manager_Interface $eventsManager
	 * @return void
	 */
	public function register(iMSCP_Events_Manager_Interface $eventsManager)
	{
		# We register this listener with low priority to let any other plugin which listen on the same event a chance
		# to act before the redirect
		$eventsManager->registerListener(iMSCP_Events::onAfterAddDomainAlias, $this, -99);
	}

	/**
	 * onAfterAddDomainAlias listener
	 *
	 * @throws iMSCP_Exception
	 * @throws iMSCP_Exception_Database
	 * @param iMSCP_Events_Event $event
	 * @throws Exception
	 */
	public function onAfterAddDomainAlias(iMSCP_Events_Event $event)
	{
		$userIdentity = iMSCP_Authentication::getInstance()->getIdentity();

		if ($userIdentity->admin_type == 'user') {
			$disallowedDomains = (array)$this->getConfigParam('ignored_domains', array());
			$domainAliasNameAscii = $event->getParam('domainAliasName');

			# Only domain aliases which are not listed in the ignored_domains list are auto-approved
			if (!in_array(decode_idna($domainAliasNameAscii), $disallowedDomains)) {
				$username = decode_idna($userIdentity->admin_name);

				$approvalRule = $this->getConfigParam('approval_rule', true);
				$userAccounts = (array)$this->getConfigParam('user_accounts', array());

				if ($approvalRule) {
					# Only domain aliases added by user accounts which are listed in the user_accounts list are
					# auto-approved
					if (!in_array($username, $userAccounts)) {
						$username = false;
					}
				} elseif (in_array($username, $userAccounts)) {
					# Only domain aliases added by user accounts which are not listed in the user_accounts list are
					# auto-approved
					$username = false;
				}

				if ($username !== false) {
					$db = iMSCP_Database::getInstance();

					try {
						$db->beginTransaction();

						$domainAliasId = $event->getParam('domainAliasId');

						exec_query(
							'UPDATE domain_aliasses SET alias_status = ? WHERE alias_id = ?',
							array('toadd', $domainAliasId)
						);

						if (iMSCP_Registry::get('config')->CREATE_DEFAULT_EMAIL_ADDRESSES) {
							if ($userIdentity->email) {
								client_mail_add_default_accounts(
									get_user_domain_id($userIdentity->admin_id), $userIdentity->email,
									$domainAliasNameAscii, 'alias', $domainAliasId
								);
							}
						}

						$db->commit();

						send_request();

						$domainAliasName = decode_idna($domainAliasNameAscii);
						$username = decode_idna($username);

						write_log(
							sprintf('DomainAutoApproval: The %s domain alias has been auto-approved', $domainAliasName),
							E_USER_NOTICE
						);

						write_log(
							sprintf(
								'DomainAutoApproval: %s scheduled addition of domain alias: %s',
								$username,
								$domainAliasName
							),
							E_USER_NOTICE
						);

						set_page_message(tr('Domain alias successfully scheduled for addition.'), 'success');
						redirectTo('domains_manage.php');
					} catch (iMSCP_Exception $e) {
						$db->rollBack();
						throw $e;
					}
				}
			}
		}
	}
}
