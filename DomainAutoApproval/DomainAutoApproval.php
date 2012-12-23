<?php
/**
 * i-MSCP - Domain Auto-Approval plugin
 * Copyright (C) Laurent Declercq <l.declercq@nuxwin.com>
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
 *
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  DomainAutoApproval
 * @copyright   Copyright (C) Laurent Declercq <l.declercq@nuxwin.com>
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * DomainAutoApproval plugin class
 *
 * Plugin allowing auto-approval of new domain aliases
 */
class iMSCP_Plugin_DomainAutoApproval extends iMSCP_Plugin_Action
{
	/**
	 * Register a callback for the given event(s).
	 *
	 * @param iMSCP_Events_Manager_Interface $controller
	 */
	public function register(iMSCP_Events_Manager_Interface $controller)
	{
		$controller->registerListener(iMSCP_Events::onAfterAddDomainAlias, $this);
	}

	/**
	 * Implements the onAfterAddDomainAlias listener method.
	 *
	 * @param iMSCP_Events_Event $event
	 * @throws iMSCP_Plugin_Exception
	 */
	public function onAfterAddDomainAlias(iMSCP_Events_Event $event)
	{
		$domainId = $event->getParam('domainId');
		$domainAliasId = $event->getParam('domainAliasId');
		$domains = $this->getConfigParam('domains');

		$query = 'SELECT `domain_name` FROM `domain` WHERE `domain_id` = ?';
		$stmt = exec_query($query, $domainId);
		$domainName = $stmt->fields['domain_name'];

		if (is_array($domains)) {
			if (in_array($domainName, $domains)) {
				/** @var $cfg iMSCP_Config_Handler_File */
				$cfg = iMSCP_Registry::get('config');
				$query = 'UPDATE `domain_aliasses` SET `alias_status` = ? WHERE `alias_id` = ? AND `domain_id` = ?';
				exec_query($query, array($cfg->ITEM_ADD_STATUS, $domainAliasId, $domainId));

				update_reseller_c_props(get_reseller_id($domainId));

				$admin_login = $_SESSION['user_logged'];
				$domainAliasName = $event->getParam('domainAliasName');

				send_request();
				write_log("$admin_login: domain alias scheduled for addition: $domainAliasName", E_USER_NOTICE);
				set_page_message(tr('Alias scheduled for addition.'), 'success');
				redirectTo('domains_manage.php');
			}
		} else {
			throw new iMSCP_Plugin_Exception(
				"DomainAutoApproval plugin: The 'domains' setting must be an array containing domain account names.");
		}
	}
}
