<?php
/**
 * i-MSCP Mailman plugin
 * Copyright (C) 2013-2016 Laurent Declercq <l.declercq@nuxwin.com>
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
 * Mailman Plugin
 */
class iMSCP_Plugin_Mailman extends iMSCP_Plugin_Action
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
	 * Register a callback for the given event(s)
	 *
	 * @param iMSCP_Events_Manager_Interface $eventsManager
	 */
	public function register(iMSCP_Events_Manager_Interface $eventsManager)
	{
		$eventsManager->registerListener(
			array(
				iMSCP_Events::onBeforeInstallPlugin,
				iMSCP_Events::onBeforeUpdatePlugin,
				iMSCP_Events::onClientScriptStart,
				iMSCP_Events::onAfterDeleteCustomer,
				iMSCP_Events::onBeforeAddMail
			),
			$this
		);

		$eventsManager->registerListener(iMSCP_Events::onBeforeAddSubdomain, $this, 99);
	}

	/**
	 * onBeforeInstallPlugin listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeInstallPlugin($event)
	{

	}

	/**
	 * Plugin installation
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager
	 * @return void
	 */
	public function install(iMSCP_Plugin_Manager $pluginManager)
	{
		try {
			$this->migrateDb('up');
		} catch(iMSCP_Plugin_Exception $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * onBeforeUpdatePlugin listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeUpdatePlugin($event)
	{

	}

	/**
	 * Plugin update
	 *
	 * @throws iMSCP_Plugin_Exception When update fail
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function update(iMSCP_Plugin_Manager $pluginManager)
	{
		try {
			$this->clearTranslations();
			$this->migrateDb('up');
		} catch(Exception $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
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
			$this->clearTranslations();
		} catch(Exception $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Plugin deletion
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function delete(iMSCP_Plugin_Manager $pluginManager)
	{
		try {
			$this->clearTranslations();
			$this->migrateDb('down');
		} catch(Exception $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * onBeforeEnablePlugin listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeEnablePlugin($event)
	{

	}

	/**
	 * Get routes
	 *
	 * @return array
	 */
	public function getRoutes()
	{
		return array('/client/mailman.php' => $this->getPluginManager()->pluginGetDirectory() . '/' . $this->getName() . '/frontend/mailman.php');
	}

	/**
	 * onClientScriptStart event listener
	 *
	 * @return void
	 */
	public function onClientScriptStart()
	{
		$this->setupNavigation();
	}

	/**
	 * onBeforeAddSubdomain event listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeAddSubdomain($event)
	{
		if($event->getParam('subdomainType') == 'dmn') {
			$subdomain = $event->getParam('subdomainName');
			$stmt = exec_query('SELECT domain_name FROM domain WHERE domain_id = ?', $event->getParam('parentDomainId'));
			$row = $stmt->fetchRow();

			if($subdomain === 'lists.' . decode_idna($row['domain_name'])) {
				set_page_message(tr('This subdomain is reserved for mailing list usage.'), 'error');
				redirectTo('subdomain_add.php');
			}
		}
	}

	/**
	 * onBeforeAddMail event listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeAddMail($event)
	{
		$stmt = exec_query(
			'SELECT mailman_list_name FROM mailman WHERE mailman_admin_id = ?', intval($_SESSION['user_id'])
		);

		if($stmt->rowCount()) {
			$match = false;
			$mailUsername = strtolower($event->getParam('mailUsername', ''));
			$reservedMailUsernames = array(
				'', '-admin', '-bounces', '-confirm', '-join', '-leave', '-owner', '-request', '-subscribe',
				'-unsubscribe'
			);

			while($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
				foreach($reservedMailUsernames as $reservedMailUsername) {
					$candidate = $row['mailman_list_name'] . $reservedMailUsername;

					if($candidate == $mailUsername) {
						$match = true;
						break;
					}
				}
			}

			if($match) {
				set_page_message(tr('This mail account is already used for mailing list.'), 'error');
				redirectTo('mail_accounts.php');
			}
		}
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
			'UPDATE mailman SET mailman_status = ? WHERE mailman_admin_id = ?',
			array('todelete', $event->getParam('customerId'))
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
					mailman_id AS item_id, mailman_status AS status, mailman_list_name AS item_name,
					'mailman' AS `table`, 'mailman_status' AS field
				FROM
					mailman
				WHERE
					mailman_status NOT IN(?, ?, ?, ?, ?, ?)
			",
			array('ok', 'toadd', 'torestore', 'toenable', 'todisable', 'todelete'));

		if($stmt->rowCount()) {
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		}

		return array();
	}

	/**
	 * Set status of the given plugin entity to 'tochange'
	 *
	 * @param string $table Table name
	 * @param string $field Status field name
	 * @param int $itemId Mailman item unique identifier
	 * @return void
	 */
	public function changeItemStatus($table, $field, $itemId)
	{
		if($table == 'mailman' && $field == 'mailman_status') {
			# We are using the 'toadd' status because the 'tochange' status is used for email and password update only
			exec_query('UPDATE mailman SET mailman_status = ?  WHERE mailman_id = ?', array('toadd', $itemId));
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
			'SELECT COUNT(mailman_id) AS cnt FROM mailman WHERE mailman_status IN (?, ?, ?, ?, ?)',
			array('toadd', 'tochange', 'toenable', 'todisable', 'todelete')
		);

		$row = $stmt->fetchRow();

		return $row['cnt'];
	}

	/**
	 * Setup navigation
	 */
	protected function setupNavigation()
	{
		if(iMSCP_Registry::isRegistered('navigation')) {
			/** @var Zend_Navigation $navigation */
			$navigation = iMSCP_Registry::get('navigation');

			if(($page = $navigation->findOneBy('uri', '/client/mail_accounts.php'))) {
				$page->addPage(
					array(
						'label' => tohtml(tr('Mailing Lists')),
						'uri' => '/client/mailman.php',
						'title_class' => 'email',
						'order' => 3
					)
				);
			}
		}
	}

	/**
	 * Check plugin requirements
	 *
	 * @return bool TRUE if all requirements are meets, FALSE otherwise
	 */
	protected function checkRequirements()
	{
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');

		if(!isset($cfg['MTA_SERVER']) || $cfg['MTA_SERVER'] != 'postfix') {
			set_page_message(tr('Mailman plugin require i-MSCP Postfix server implementation'), 'error');
			return false;
		} elseif(!isset($cfg['HTTPD_SERVER']) || strpos($cfg['HTTPD_SERVER'], 'apache_') === false) {
			set_page_message(tr('Mailman plugin require i-MSCP Apache server implementation'), 'error');
			return false;
		}

		return true;
	}

	/**
	 * Clear translations if any
	 *
	 * @return void
	 */
	protected function clearTranslations()
	{
		/** @var Zend_Translate $translator */
		$translator = iMSCP_Registry::get('translator');

		if($translator->hasCache()) {
			$translator->clearCache($this->getName());
		}
	}
}
