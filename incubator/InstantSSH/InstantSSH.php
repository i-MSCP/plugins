<?php
/**
 * i-MSCP InstantSSH plugin
 * Copyright (C) 2014 Laurent Declercq <l.declercq@nuxwin.com>
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
 * Class iMSCP_Plugin_InstantSSH
 */
class iMSCP_Plugin_InstantSSH extends iMSCP_Plugin_Action
{
	/**
	 * @var array customer SSH permissions
	 */
	protected $customerSshPermissions;

	/**
	 * Plugin initialization
	 *
	 * @return void
	 */
	public function init()
	{
		$pluginDirectory = iMSCP_Registry::get('pluginManager')->getPluginDirectory();

		// Set include path
		set_include_path(
			get_include_path() .
			PATH_SEPARATOR . $pluginDirectory . '/InstantSSH/library' .
			PATH_SEPARATOR . $pluginDirectory . '/InstantSSH/library/vendor/phpseclib'
		);
	}

	/**
	 * Register event listeners
	 *
	 * @param $eventManager iMSCP_Events_Manager_Interface $eventManager
	 * @return void
	 */
	public function register(iMSCP_Events_Manager_Interface $eventManager)
	{
		$eventManager->registerListener(
			array(
				iMSCP_Events::onBeforeInstallPlugin, iMSCP_Events::onBeforeUpdatePlugin,
				iMSCP_Events::onBeforeEnablePlugin, iMSCP_Events::onAdminScriptStart,
				iMSCP_Events::onClientScriptStart, iMSCP_Events::onAfterChangeDomainStatus
			),
			$this
		);
	}

	/**
	 * onBeforeInstallPlugin listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeInstallPlugin($event)
	{
		$this->checkCompat($event);
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
			$this->checkDefaultAuthOptions();
			$this->migrateDb('up');
		} catch (iMSCP_Plugin_Exception $e) {
			throw new iMSCP_Plugin_Exception(sprintf('Unable to install: %s', $e->getMessage()), $e->getCode(), $e);
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
		$this->checkCompat($event);
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
		} catch (iMSCP_Plugin_Exception $e) {
			throw new iMSCP_Plugin_Exception(tr('Unable to update: %s', $e->getMessage()), $e->getCode(), $e);
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
		$this->checkCompat($event);
	}

	/**
	 * Plugin activation
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function enable(iMSCP_Plugin_Manager $pluginManager)
	{
		if ($pluginManager->getPluginStatus($this->getName()) != 'toinstall') {
			require_once 'InstantSSH/Converter/SshAuthOptions.php';
		}

		try {
			$this->checkDefaultAuthOptions();

			$allowedSshAuthOptions = $this->getConfigParam('allowed_ssh_auth_options', array());

			if (!in_array(InstantSSH\Validate\SshAuthOptions::ALL, $allowedSshAuthOptions)) {
				$db = iMSCP_Database::getInstance();

				try {
					$db->beginTransaction();

					$stmt = exec_query(
						'SELECT ssh_key_id, ssh_auth_options FROM instant_ssh_keys WHERE ssh_key_status <> ?', 'todelete'
					);

					if ($stmt->rowCount()) {
						while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
							$sshAuthOptionsOld = \InstantSSH\Converter\SshAuthOptions::toArray($row['ssh_auth_options']);

							# Remove any ssh authentication option which is no longer allowed
							$sshAuthOptionNew = array_filter(
								$sshAuthOptionsOld, function ($sshAuthOption) use ($allowedSshAuthOptions) {
								return in_array($sshAuthOption, $allowedSshAuthOptions);
							});

							if ($sshAuthOptionNew !== $sshAuthOptionsOld) {
								exec_query(
									'UPDATE instant_ssh_keys SET ssh_auth_options = ? WHERE ssh_key_id ) ?',
									array(
										$row['ssh_key_id'],
										\InstantSSH\Converter\SshAuthOptions::toString($sshAuthOptionNew)
									)
								);
							}
						}
					}

					$db->commit();
				} catch (iMSCP_Exception_Database $e) {
					$db->rollBack();
					throw $e;
				}
			}
		} catch (iMSCP_Exception $e) {
			throw new iMSCP_Plugin_Exception(tr('Unable to enable: %s', $e->getMessage()), $e->getCode(), $e);
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
			throw new iMSCP_Plugin_Exception(tr('Unable to uninstall: %s', $e->getMessage()), $e->getCode(), $e);
		}
	}

	/**
	 * onAfterChangeDomainStatus listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onAfterChangeDomainStatus($event)
	{
		$customerId = $event->getParam('customerId');
		$action = $event->getParam('action');

		if ($action == 'activate') {
			exec_query(
				'UPDATE instant_ssh_keys SET ssh_key_status = ? WHERE ssh_key_admin_id = ?',
				array('toenable', $customerId)
			);
		} else {
			exec_query(
				'UPDATE instant_ssh_keys SET ssh_key_status = ? WHERE ssh_key_admin_id = ?',
				array('todisable', $customerId)
			);
		}
	}

	/**
	 * Get plugin item with error status
	 *
	 * @return array
	 */
	public function getItemWithErrorStatus()
	{
		$stmt = exec_query(
			"
			SELECT
				ssh_key_id AS item_id, ssh_key_status AS status, ssh_key_name AS item_name,
				'instant_ssh_keys' AS `table`, 'ssh_key_status' AS `field`
			FROM
				instant_ssh_keys
			WHERE
				ssh_key_status NOT IN(?, ?, ?, ?, ?, ?, ?)
			",
			array('ok', 'disabled', 'toadd', 'tochange', 'toenable', 'todisable', 'todelete')
		);

		if ($stmt->rowCount()) {
			return $stmt->fetchAll(PDO::FETCH_ASSOC);
		}

		return array();
	}

	/**
	 * Set status of the given plugin item to 'tochange'
	 *
	 * @param string $table Table name
	 * @param string $field Status field name
	 * @param int $itemId item unique identifier
	 * @return void
	 */
	public function changeItemStatus($table, $field, $itemId)
	{
		if ($table === 'instant_ssh_keys' && $field === 'ssh_key_status') {
			exec_query("UPDATE $table SET $field = ? WHERE ssh_key_id = ?", array('tochange', $itemId));
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
			'SELECT COUNT(ssh_key_id) AS cnt FROM instant_ssh_keys WHERE ssh_key_status IN (?, ?, ?, ?, ?)',
			array('toadd', 'tochange', 'toenable', 'todisable', 'todelete')
		);

		return $stmt->fields['cnt'];
	}

	/**
	 * Get routes
	 *
	 * @return array An array which map routes to action scripts
	 */
	public function getRoutes()
	{
		$pluginName = $this->getName();

		return array(
			'/admin/ssh_permissions' => PLUGINS_PATH . '/' . $pluginName . '/frontend/admin/ssh_permissions.php',
			'/client/ssh_keys' => PLUGINS_PATH . '/' . $pluginName . '/frontend/client/ssh_keys.php',
		);
	}

	/**
	 * onAdminScriptStart event listener
	 *
	 * @return void
	 */
	public function onAdminScriptStart()
	{
		$this->setupNavigation('admin');
	}

	/**
	 * onAdminScriptStart event listener
	 *
	 * @return void
	 */
	public function onClientScriptStart()
	{
		$this->setupNavigation('client');
	}

	/**
	 * Check plugin compatibility
	 *
	 * @param iMSCP_Events_Event $event
	 */
	protected function checkCompat($event)
	{
		if ($event->getParam('pluginName') == $this->getName()) {
			if (version_compare($event->getParam('pluginManager')->getPluginApiVersion(), '0.2.8', '<')) {
				set_page_message(
					tr('Your i-MSCP version is not compatible with this plugin. Try with a newer version.'), 'error'
				);

				$event->stopPropagation();
			}
		}
	}

	/**
	 * Check default SSH authentication options
	 *
	 * @throw iMSCP_Plugin_Exception in case default key options are invalid
	 * @return void
	 */
	protected function checkDefaultAuthOptions()
	{
		require_once 'InstantSSH/Validate/SshAuthOptions.php';
		require_once 'InstantSSH/Converter/SshAuthOptions.php';

		$defaulltAuthOptions = $this->getConfigParam('default_ssh_auth_options', '');

		if (is_string($defaulltAuthOptions)) {
			$allowedSshAuthOptions = $this->getConfigParam('allowed_ssh_auth_options', array());

			if (is_array($allowedSshAuthOptions)) {
				if ($defaulltAuthOptions != '') {
					$validator = new InstantSSH\Validate\SshAuthOptions();

					if (!$validator->isValid($defaulltAuthOptions)) {
						$messages = implode(', ', $validator->getMessages());
						throw new iMSCP_Plugin_Exception(
							sprintf('Invalid default authentication options: %s', $messages)
						);
					}

					$sshAuthOptionsOld = \InstantSSH\Converter\SshAuthOptions::toArray($defaulltAuthOptions);

					if (!in_array(InstantSSH\Validate\SshAuthOptions::ALL, $allowedSshAuthOptions)) {
						# Remove any ssh authentication option which is not allowed
						$sshAuthOptionNew = array_filter(
							$sshAuthOptionsOld, function ($sshAuthOption) use ($allowedSshAuthOptions) {
							return in_array($sshAuthOption, $allowedSshAuthOptions);
						});

						if ($sshAuthOptionNew !== $sshAuthOptionsOld) {
							throw new iMSCP_Plugin_Exception(
								'Any authentication options appearing in the default_ssh_auth_options parameter must ' .
								'be specified in the allowed_ssh_auth_options parameter.'
							);
						}
					}
				}
			} else {
				throw new iMSCP_Plugin_Exception('allowed_ssh_auth_options parameter must be an array.');
			}
		} else {
			throw new iMSCP_Plugin_Exception('default_ssh_auth_options parameter must be a string.');
		}
	}

	/**
	 * Setup plugin navigation
	 *
	 * @param string $uiLevel UI level
	 * @return void
	 */
	protected function setupNavigation($uiLevel)
	{
		if (!is_xhr() && iMSCP_Registry::isRegistered('navigation')) {
			/** @var Zend_Navigation $navigation */
			$navigation = iMSCP_Registry::get('navigation');

			if ($uiLevel == 'admin' && ($page = $navigation->findOneBy('uri', '/admin/settings.php'))) {
				$page->addPage(
					array(
						'label' => tr('SSH Permissions'),
						'uri' => '/admin/ssh_permissions',
						'title_class' => 'settings',
						'order' => 8
					)
				);
			} elseif ($uiLevel == 'client' && ($page = $navigation->findOneBy('uri', '/client/profile.php'))) {
				$self = $this;

				$page->addPage(
					array(
						'label' => tr('SSH keys'),
						'uri' => '/client/ssh_keys',
						'title_class' => 'profile',
						'privilege_callback' => array(
							'name' => function () use ($self) {
									$sshPermissions = $self->getCustomerPermissions($_SESSION['user_id']);
									return (bool)($sshPermissions['ssh_permission_max_keys'] > -1);
								}
						)
					)
				);
			}
		}
	}

	/**
	 * Get SSH permissions for the given customer
	 *
	 * @param int $customerId Customer unique identifier
	 * @return int
	 */
	public function getCustomerPermissions($customerId)
	{
		if (null === $this->customerSshPermissions) {
			$stmt = exec_query(
				'
					SELECT
						ssh_permission_id, ssh_permission_max_keys, ssh_permission_auth_options,
						COUNT(ssh_key_id) as ssh_permission_cnb_keys
					FROM
						instant_ssh_permissions
					LEFT JOIN
						instant_ssh_keys USING(ssh_permission_id)
					WHERE
						ssh_permission_admin_id = ?
					GROUP BY
						ssh_permission_id
				',
				intval($customerId)
			);

			if ($stmt->rowCount()) {
				$this->customerSshPermissions = $stmt->fetchRow(PDO::FETCH_ASSOC);
			} else {
				$this->customerSshPermissions = array(
					'ssh_permission_id' => null,
					'ssh_permission_max_keys' => -1,
					'ssh_permission_cnb_keys' => 0,
					'ssh_permission_keys_options' => 0
				);
			}
		}

		return $this->customerSshPermissions;
	}
}
