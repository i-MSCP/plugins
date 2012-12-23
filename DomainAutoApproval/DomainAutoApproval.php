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
	 * @var string Initial item ordered status value
	 */
	protected $initialOrderedStatusValue = '';

	/**
	 * Register a callback for the given event(s).
	 *
	 * @param iMSCP_Events_Manager_Interface $controller
	 */
	public function register(iMSCP_Events_Manager_Interface $controller)
	{
		$controller->registerListener(
			array(iMSCP_Events::onBeforeAddDomainAlias, iMSCP_Events::onAfterAddDomainAlias), $this
		);
	}

	/**
	 * Implements the onBeforeAddDomainAlias listener method.
	 *
	 * @throws iMSCP_Plugin_Exception in case the domains config setting is wrong
	 */
	public function onBeforeAddDomainAlias()
	{
		$domains = $this->getConfigParam('domains'); # List of domain names for which auto-approval is enabled

		if (is_array($domains)) {
			$domainName = $_SESSION['user_logged'];

			if (in_array(decode_idna($domainName), $domains)) {
				/** @var $cfg iMSCP_Config_Handler_File */
				$cfg = iMSCP_Registry::get('config');

				# Overrides status to force scheduling of domain addition
				$this->initialOrderedStatusValue = $cfg->ITEM_ORDERED_STATUS;
				$cfg->ITEM_ORDERED_STATUS = $cfg->ITEM_ADD_STATUS;
			}
		} else {
			throw new iMSCP_Plugin_Exception(
				"DomainAutoApproval plugin: The 'domains' setting must be an array containing domain account names."
			);
		}
	}

	/**
	 * Implements the onAfterAddDomainAlias listener method.
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onAfterAddDomainAlias(iMSCP_Events_Event $event)
	{
		if($this->initialOrderedStatusValue) {
			/** @var $cfg iMSCP_Config_Handler_File */
			$cfg = iMSCP_Registry::get('config');
			# Overrides status to force scheduling of domain addition
			$cfg->ITEM_ORDERED_STATUS = $this->initialOrderedStatusValue;

			$domainAlias = decode_idna($event->getParam('domainAliasName'));
			write_log("DomainAutoApproval plugin: Domain alias '$domainAlias' has been auto-approved", E_USER_NOTICE);
		}
	}
}
