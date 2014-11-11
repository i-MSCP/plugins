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
		$pluginName = $this->getName();

		/** @var Zend_Loader_StandardAutoloader $loader */
		$loader = Zend_Loader_AutoloaderFactory::getRegisteredAutoloader('Zend_Loader_StandardAutoloader');
		$loader->registerNamespace($pluginName, __DIR__ . '/library/' . $pluginName);
		unset($loader);
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
				iMSCP_Events::onBeforeInstallPlugin,
				iMSCP_Events::onBeforeUpdatePlugin,
				iMSCP_Events::onBeforeEnablePlugin,
				iMSCP_Events::onAdminScriptStart,
				iMSCP_Events::onClientScriptStart,
				iMSCP_Events::onAfterChangeDomainStatus
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
		} catch(Exception $e) {
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
		$this->checkCompat($event);
	}

	/**
	 * Plugin update
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @param string $fromVersion Version from which plugin update is initiated
	 * @param string $toVersion Version to which plugin is updated
	 * @return void
	 */
	public function update(iMSCP_Plugin_Manager $pluginManager, $fromVersion, $toVersion)
	{
		try {
			/** @var Zend_Translate $translator */
			$translator = iMSCP_Registry::get('translator');

			if($translator->hasCache()) {
				$translator->clearCache($this->getName());
			}

			$this->migrateDb('up');
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
		if($pluginManager->getPluginStatus($this->getName()) != 'toinstall') {
			try {
				$this->checkDefaultAuthOptions();

				$allowedSshAuthOptions = $this->getConfigParam('allowed_ssh_auth_options', array());

				if(!in_array(\InstantSSH\Validate\SshAuthOptions::ALL, $allowedSshAuthOptions)) {
					// Normalize options for comparaison
					$allowedSshAuthOptions = array_change_key_case(array_flip($allowedSshAuthOptions), CASE_LOWER);

					$db = iMSCP_Database::getInstance();

					try {
						$db->beginTransaction();

						$stmt = exec_query(
							'SELECT ssh_user_id, ssh_user_auth_options FROM instant_ssh_users WHERE ssh_user_status <> ?',
							'todelete'
						);

						if($stmt->rowCount()) {
							while($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
								// Convert authentication options string to array
								$sshAuthOptionsOld = array_change_key_case(
									\InstantSSH\Converter\SshAuthOptions::toArray($row['ssh_auth_options']), CASE_LOWER
								);

								# Remove any authentication option which is no longer allowed
								$sshAuthOptionNew = array_intersect_key($sshAuthOptionsOld, $allowedSshAuthOptions);

								if($sshAuthOptionNew !== $sshAuthOptionsOld) {
									exec_query(
										'UPDATE instant_ssh_users SET ssh_user_auth_options = ? WHERE ssh_user_id = ?',
										array(
											\InstantSSH\Converter\SshAuthOptions::toString($sshAuthOptionNew),
											$row['ssh_user_id']
										)
									);
								}
							}
						}

						$db->commit();
					} catch(iMSCP_Exception_Database $e) {
						$db->rollBack();
						throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
					}
				}
			} catch(Exception $e) {
				throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
			}
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
			/** @var Zend_Translate $translator */
			$translator = iMSCP_Registry::get('translator');

			if($translator->hasCache()) {
				$translator->clearCache($this->getName());
			}

			$this->migrateDb('down');
		} catch(Exception $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
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
		exec_query(
			'UPDATE instant_ssh_users SET ssh_users_status = ? WHERE ssh_user_admin_id = ?',
			array(($event->getParam('action') == 'activate') ? 'toenable' : 'todisable', $event->getParam('customerId'))
		);
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
				ssh_permission_id AS item_id, ssh_permission_status AS status,
				CONCAT('SSH permssions for: ', admin_name, '( ', admin_type, ' )') as item_name,
				'instant_ssh_permissions' AS `table`, 'ssh_permission_status' AS `field`
			FROM
				instant_ssh_permissions
			INNER JOIN
				admin ON(admin_id = ssh_permission_admin_id)
			WHERE
				ssh_permission_status NOT IN(:ok, :toadd, :tochange, :todelete)
			UNION
			SELECT
				ssh_user_id AS item_id, ssh_user_status AS status, ssh_user_name AS item_name,
				'instant_ssh_users' AS `table`, 'ssh_user_status' AS `field`
			FROM
				instant_ssh_users
			WHERE
				ssh_user_status NOT IN(:ok, :disabled, :toadd, :tochange, :toenable, :todisable, :todelete)
			",
			array(
				'ok' => 'ok', 'disabled' => 'disabled', 'toadd' => 'toadd', 'tochange' => 'tochange',
				'toenable' => 'toenable', 'todisable' => 'todisable', 'todelete' => 'todelete'
			)
		);

		if($stmt->rowCount()) {
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
		if($table === 'instant_ssh_permissions' && $field === 'ssh_permission_status') {
			exec_query("UPDATE $table SET $field = ? WHERE ssh_permission_id = ?", array('tochange', $itemId));
		} elseif($table === 'instant_ssh_users' && $field === 'ssh_user_status') {
			exec_query("UPDATE $table SET $field = ? WHERE ssh_user_id = ?", array('tochange', $itemId));
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
			'
				SELECT
				(
					(
						SELECT
							COUNT(ssh_permission_id)
						FROM
							instant_ssh_permissions
						WHERE
							ssh_permission_status IN (:toadd, :tochange, :todelete)
					) + (
						SELECT
							COUNT(ssh_user_id)
						FROM
							instant_ssh_users
						WHERE
							ssh_user_status IN (:toadd, :tochange, :toenable, :todisable, :todelete)
					)
				) AS cnt
			',
			array(
				'toadd' => 'toadd', 'tochange' => 'tochange', 'toenable' => 'toenable', 'todisable' => 'todisable',
				'todelete' => 'todelete'
			)
		);

		$row = $stmt->fetchRow(PDO::FETCH_ASSOC);

		return $row['cnt'];
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
			'/client/ssh_users' => PLUGINS_PATH . '/' . $pluginName . '/frontend/client/ssh_users.php'
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
	 * Get SSH permissions for the given customer
	 *
	 * @param int $customerId Customer unique identifier
	 * @return array
	 */
	public function getCustomerPermissions($customerId)
	{
		if(null === $this->customerSshPermissions) {
			$stmt = exec_query(
				'
					SELECT
						ssh_permission_id, ssh_permission_max_users, ssh_permission_auth_options,
						COUNT(ssh_user_id) AS ssh_permission_cnb_users
					FROM
						instant_ssh_permissions
					LEFT JOIN
						instant_ssh_users ON(ssh_user_permission_id = ssh_permission_id)
					WHERE
						ssh_permission_admin_id = ?
					AND
						ssh_permission_status = ?
				',
				array(intval($customerId), 'ok')
			);

			if($stmt->rowCount()) {
				$this->customerSshPermissions = $stmt->fetchRow(PDO::FETCH_ASSOC);
			} else {
				$this->customerSshPermissions = array(
					'ssh_permission_id' => null,
					'ssh_permission_max_users' => -1,
					'ssh_permission_auth_options' => 0,
					'ssh_permission_cnb_users' => 0
				);
			}
		}

		return $this->customerSshPermissions;
	}

	/**
	 * Check plugin compatibility
	 *
	 * @param iMSCP_Events_Event $event
	 */
	protected function checkCompat($event)
	{
		if($event->getParam('pluginName') == $this->getName()) {
			if(version_compare($event->getParam('pluginManager')->getPluginApiVersion(), '0.2.13', '<')) {
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
	 * @throws iMSCP_Plugin_Exception in case default auth options are invalid
	 * @return void
	 */
	protected function checkDefaultAuthOptions()
	{
		$defaulltAuthOptions = $this->getConfigParam('default_ssh_auth_options', '');

		if(is_string($defaulltAuthOptions)) {
			$allowedSshAuthOptions = $this->getConfigParam('allowed_ssh_auth_options', array());

			if(is_array($allowedSshAuthOptions)) {
				if($defaulltAuthOptions != '') {
					$validator = new \InstantSSH\Validate\SshAuthOptions();

					if(!$validator->isValid($defaulltAuthOptions)) {
						$messages = implode(', ', $validator->getMessages());
						throw new iMSCP_Plugin_Exception(tr('Invalid default authentication options: %s', $messages));
					}

					if(!in_array(\InstantSSH\Validate\SshAuthOptions::ALL, $allowedSshAuthOptions)) {
						// Normalize options for comparaison
						$allowedSshAuthOptions = array_map('strtolower', $allowedSshAuthOptions);

						// Convert default authentication options string to array
						$sshAuthOptionsOld = array_change_key_case(
							\InstantSSH\Converter\SshAuthOptions::toArray($defaulltAuthOptions), CASE_LOWER
						);

						# Remove any authentication option which is not allowed
						$sshAuthOptionNew = array_intersect_key($sshAuthOptionsOld, array_flip($allowedSshAuthOptions));

						if($sshAuthOptionNew !== $sshAuthOptionsOld) {
							throw new iMSCP_Plugin_Exception(
								tr(
									'Any authentication options defined in the default_ssh_auth_options ' .
									'parameter must be also defined in the allowed_ssh_auth_options parameter.'
								)
							);
						}
					}
				}
			} else {
				throw new iMSCP_Plugin_Exception(tr('allowed_ssh_auth_options parameter must be an array.'));
			}
		} else {
			throw new iMSCP_Plugin_Exception(tr('default_ssh_auth_options parameter must be a string.'));
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
		if(!is_xhr() && iMSCP_Registry::isRegistered('navigation')) {
			/** @var Zend_Navigation $navigation */
			$navigation = iMSCP_Registry::get('navigation');

			if($uiLevel == 'admin' && ($page = $navigation->findOneBy('uri', '/admin/settings.php'))) {
				$page->addPage(
					array(
						'label' => tr('SSH permissions'),
						'uri' => '/admin/ssh_permissions',
						'title_class' => 'settings',
						'order' => 8
					)
				);
			} elseif($uiLevel == 'client' && ($page = $navigation->findOneBy('uri', '/client/domains_manage.php'))) {
				$self = $this;

				$page->addPage(
					array(
						'label' => tr('SSH users'),
						'uri' => '/client/ssh_users',
						'title_class' => 'users',
						'privilege_callback' => array(
							'name' => function () use ($self) {
								$sshPermissions = $self->getCustomerPermissions(intval($_SESSION['user_id']));
								return (bool)($sshPermissions['ssh_permission_id'] !== null);
							}
						)
					)
				);
			}
		}
	}
}
