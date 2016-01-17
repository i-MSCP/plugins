<?php
/**
 * i-MSCP RawPasswd plugin
 * Copyright (C) 2015-2016 Laurent Declercq <l.declercq@nuxwin.com>
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
 * Class iMSCP_Plugin_RawPasswd
 */
class iMSCP_Plugin_RawPasswd extends iMSCP_Plugin_Action
{
	/**
	 * Register a callback for the given event(s)
	 *
	 * @param $eventManager iMSCP_Events_Manager_Interface $eventManager
	 */
	public function register(iMSCP_Events_Manager_Interface $eventManager)
	{
		$eventManager->registerListener(
			array(
				iMSCP_Events::onAfterAddUser,
				iMSCP_Events::onAfterEditUser,
				iMSCP_Events::onAfterAddDomain
			),
			$this
		);
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
			$this->migrateDb('down');
		} catch (iMSCP_Plugin_Exception $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * onAfterAddUser listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onAfterAddUser($event)
	{
		$userId = $event->getParam('userId', false);

		if ($userId && isset($_POST['password'])) {
			exec_query('UPDATE admin set admin_rawpasswd = ? WHERE admin_id = ?', array(
				clean_input($_POST['password']), $userId
			));
		}
	}

	/**
	 * onAfterEditUser listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onAfterEditUser($event)
	{
		$userId = $event->getParam('userId', false);

		if ($userId && isset($_POST['password'])) {
			exec_query('UPDATE admin set admin_rawpasswd = ? WHERE admin_id = ?', array(
				clean_input($_POST['password']), $userId
			));
		}
	}

	/**
	 * onAfterAddDomain listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onAfterAddDomain($event)
	{
		$userId = $event->getParam('customerId', false);

		if ($userId && isset($_POST['userpassword'])) {
			exec_query('UPDATE admin set admin_rawpasswd = ? WHERE admin_id = ?', array(
				clean_input($_POST['userpassword']), $userId
			));
		}
	}
}
