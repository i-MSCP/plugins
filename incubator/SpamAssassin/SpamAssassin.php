<?php
/**
 * i-MSCP SpamAssassin plugin
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
		} catch (iMSCP_Exception_Database $e) {
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
		// Only there to tell the plugin manager that this plugin can be uninstalled
	}

	/**
	 * Plugin enable
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function enable(iMSCP_Plugin_Manager $pluginManager)
	{
		try {
			$this->addSpamAssassinServicePort();
		} catch (iMSCP_Exception_Database $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Plugin disable
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function disable(iMSCP_Plugin_Manager $pluginManager)
	{
		try {
			$this->removeSpamAssassinServicePort();
		} catch (iMSCP_Exception_Database $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
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
		} catch (iMSCP_Exception_Database $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Add SpamAssassin service port
	 *
	 * @return void
	 */
	protected function addSpamAssassinServicePort()
	{
		$dbConfig = iMSCP_Registry::get('dbConfig');
		$pluginConfig = $this->getConfig();

		preg_match("/port=([0-9]+)/", $pluginConfig['spamassassinOptions'], $spamAssassinPort);

		if (!isset($dbConfig['PORT_SPAMASSASSIN'])) {
			$dbConfig['PORT_SPAMASSASSIN'] = $spamAssassinPort[1] . ';tcp;SPAMASSASSIN;1;127.0.0.1';
		} else {
			$this->removeSpamAssassinServicePort();
			$dbConfig['PORT_SPAMASSASSIN'] = $spamAssassinPort[1] . ';tcp;SPAMASSASSIN;1;127.0.0.1';
		}
	}

	/**
	 * Remove SpamAssassin service port
	 *
	 * @return void
	 */
	protected function removeSpamAssassinServicePort()
	{
		$dbConfig = iMSCP_Registry::get('dbConfig');
		unset($dbConfig['PORT_SPAMASSASSIN']);
	}
}
