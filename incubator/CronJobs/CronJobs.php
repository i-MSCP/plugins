<?php
/**
 * i-MSCP CronJobs plugin
 * Copyright (C) 2014-2015 Laurent Declercq <l.declercq@nuxwin.com>
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
 * Class iMSCP_Plugin_CronJobs
 */
class iMSCP_Plugin_CronJobs extends iMSCP_Plugin_Action
{
	/**
	 * @var array Cron permmissions for the current user
	 */
	protected $cronPermissions;

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
		$loader->registerNamespace($pluginName, __DIR__ . '/frontend/library/' . $pluginName);

		l10n_addTranslations(__DIR__ . '/l10n', 'Array', $pluginName);
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
				iMSCP_Events::onBeforeUpdatePlugin,
				iMSCP_Events::onBeforeEnablePlugin,
				iMSCP_Events::onAfterUninstallPlugin,
				iMSCP_Events::onBeforeUnlockPlugin,
				iMSCP_Events::onAdminScriptStart,
				iMSCP_Events::onResellerScriptStart,
				iMSCP_Events::onClientScriptStart,
				iMSCP_Events::onAfterChangeDomainStatus,

			),
			$this
		);

		$eventManager->registerListener(
			array(
				iMSCP_Events::onAfterDeleteUser,
				iMSCP_Events::onAfterDeleteCustomer
			),
			array($this, 'deleteCronPermissions')
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
	 * OnBeforeUpdate event listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @çeturn void
	 */
	public function onBeforeUpdatePlugin(iMSCP_Events_Event $event)
	{
		if ($event->getParam('pluginName') == $this->getName()) {
			$pluginManager = $this->getPluginManager();

			if ($pluginManager->pluginIsKnown('InstantSSH') && $pluginManager->pluginIsInstalled('InstantSSH')) {
				$info = $pluginManager->pluginGetInfo('InstantSSH');

				if (version_compare($info['version'], '3.2.0', '<')) {
					set_page_message(tr(
						'InstantSSH plugin version >= %s is required. Please update the InstantSSH plugin.', '3.2.0'
					));

					$event->stopPropagation();
				}
			}
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
			$this->clearTranslations();
			$this->migrateDb('up');
		} catch (Exception $e) {
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
			$this->migrateDb('down');
		} catch (Exception $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * onAfterUninstallPlugin listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onAfterUninstallPlugin($event)
	{
		if ($event->getParam('pluginName') == $this->getName()) {
			$pluginManager = $this->getPluginManager();

			if ($pluginManager->pluginIsKnown('InstantSSH') && $pluginManager->pluginIsInstalled('InstantSSH')) {
				$this->getPluginManager()->pluginUnlock('InstantSSH');
			}
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
		if ($event->getParam('pluginName') == $this->getName()) {
			$pluginManager = $this->getPluginManager();

			if ($pluginManager->pluginIsKnown('InstantSSH') && $pluginManager->pluginIsInstalled('InstantSSH')) {
				$info = $pluginManager->pluginGetInfo('InstantSSH');

				if (version_compare($info['version'], '3.2.0', '>=')) {
					$this->getPluginManager()->pluginLock('InstantSSH');
				}
			}
		}
	}

	/**
	 * onBeforeUnlockPlugin listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeUnlockPlugin(iMSCP_Events_Event $event)
	{
		if (
			$event->getParam('pluginName') == 'InstantSSH' &&
			$this->getPluginManager()->pluginGetStatus($this->getName()) != 'touninstall'
		) {
			$event->stopPropagation();
		}
	}

	/**
	 * onAfterChangeDomainStatus listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onAfterChangeDomainStatus(iMSCP_Events_Event $event)
	{
		$customerId = $event->getParam('customerId');
		$action = $event->getParam('action');

		if ($action === 'activate') {
			exec_query(
				'UPDATE cron_jobs SET cron_job_status = ? WHERE cron_job_admin_id = ?', array('toenable', $customerId)
			);
		} else {
			exec_query(
				'UPDATE cron_jobs SET cron_job_status = ? WHERE cron_job_admin_id = ?', array('todisable', $customerId)
			);
		}
	}

	/**
	 * Delete cron job permissions ( including cron jobs ) of any user which is being deleted ( reseller, customer )
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function deleteCronPermissions(iMSCP_Events_Event $event)
	{
		if (($userId = $event->getParam('userId', false))) {
			exec_query('DELETE FROM cron_permissions WHERE cron_permission_admin_id = ?', $userId);
		} else {
			$userId = $event->getParam('customerId', false);

			exec_query(
				'UPDATE cron_permissions SET cron_permission_status = ? WHERE cron_permission_admin_id = ?',
				array('todelete', $userId)
			);

			exec_query(
				"UPDATE cron_jobs SET cron_job_status = ? WHERE cron_job_admin_id = ?", array('todelete', $userId)
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
					cron_permission_id AS item_id, cron_permission_status AS status,
					CONCAT('Cron permssions for: ', admin_name, '( ', admin_type, ' )') as item_name,
					'cron_permissions' AS `table`, 'cron_permission_status' AS `field`
				FROM
					cron_permissions
				INNER JOIN
					admin ON(admin_id = cron_permission_admin_id)
				WHERE
					cron_permission_status NOT IN(:ok, :toadd, :tochange, :suspended, :todelete)
				UNION
				SELECT
					cron_job_id AS item_id, cron_job_status AS status, 'cron job' AS item_name,
					'cron_jobs' AS `table`, 'cron_job_status' AS `field`
				FROM
					cron_jobs
				WHERE
					cron_job_status NOT IN(
						:ok, :disabled, :suspended, :toadd, :tochange, :toenable, :todisable, :tosuspend, :todelete
					)
			",
			array(
				'ok' => 'ok', 'disabled' => 'disabled', 'suspended' => 'suspended', 'toadd' => 'toadd',
				'tochange' => 'tochange', 'toenable' => 'toenable', 'todisable' => 'todisable',
				'tosuspend' => 'tosuspend', 'todelete' => 'todelete'
			)
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
		if ($table === 'cron_permissions' && $field === 'cron_permission_status') {
			exec_query("UPDATE $table SET $field = ? WHERE cron_permission_id = ?", array('tochange', $itemId));
		} elseif ($table === 'cron_jobs' && $field === 'cron_job_status') {
			exec_query("UPDATE $table SET $field = ? WHERE cron_job_id = ?", array('tochange', $itemId));
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
							COUNT(cron_permission_id)
						FROM
							cron_permissions
						WHERE
							cron_permission_status IN (:toadd, :tochange, :todelete)
					) + (
						SELECT
							COUNT(cron_job_id)
						FROM
							cron_jobs
						WHERE
							cron_job_status IN (:toadd, :tochange, :toenable, :tosuspend, :todisable, :todelete)
					)
				) AS cnt
			',
			array(
				'toadd' => 'toadd', 'tochange' => 'tochange', 'toenable' => 'toenable', 'tosuspend' => 'tosuspend',
				'todisable' => 'todisable', 'todelete' => 'todelete'
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
		$pluginDir = $this->getPluginManager()->pluginGetDirectory() . '/' . $this->getName();

		return array(
			'/admin/cronjobs_permissions' => $pluginDir . '/frontend/admin/cronjobs_permissions.php',
			'/admin/cronjobs' => $pluginDir . '/frontend/admin/cronjobs.php',
			'/reseller/cronjobs_permissions' => $pluginDir . '/frontend/reseller/cronjobs_permissions.php',
			'/client/cronjobs' => $pluginDir . '/frontend/client/cronjobs.php'
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
	 * onResellerScriptStart event listener
	 *
	 * @return void
	 */
	public function onResellerScriptStart()
	{
		$this->setupNavigation('reseller');
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
	 * Get cron job permissions for the given user
	 *
	 * @param int $adminId User unique identifier
	 * @return array
	 */
	public function getCronPermissions($adminId)
	{
		if (null === $this->cronPermissions) {
			$stmt = exec_query(
				'
					SELECT
						cron_permission_id, cron_permission_type, cron_permission_max, cron_permission_frequency,
						COUNT(cron_job_id) AS cron_permission_cnb_cron_jobs
					FROM
						cron_permissions
					LEFT JOIN
						cron_jobs ON(cron_permission_id = cron_job_permission_id)
					WHERE
						cron_permission_admin_id = ?
					AND
						cron_permission_status = ?
				',
				array(intval($adminId), 'ok')
			);

			if ($stmt->rowCount()) {
				$this->cronPermissions = $stmt->fetchRow(PDO::FETCH_ASSOC);
			} else {
				$this->cronPermissions = array();
			}
		}

		return $this->cronPermissions;
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

			if ($uiLevel == 'admin') {
				if ($page = $navigation->findOneBy('uri', '/admin/settings.php')) {
					$page->addPage(
						array(
							'label' => tr('Cron job permissions'),
							'uri' => '/admin/cronjobs_permissions',
							'title_class' => 'settings',
							'order' => 9
						)
					);
				}

				if ($page = $navigation->findOneBy('uri', '/admin/system_info.php')) {
					$page->addPage(
						array(
							'label' => tr('Cron jobs'),
							'uri' => '/admin/cronjobs',
							'title_class' => 'tools',
							'order' => 9
						)
					);
				}
			} elseif ($uiLevel == 'reseller' && ($page = $navigation->findOneBy('uri', '/reseller/users.php'))) {
				$self = $this;

				$page->addPage(
					array(
						'label' => tr('Cron job permissions'),
						'uri' => '/reseller/cronjobs_permissions',
						'title_class' => 'settings',
						'order' => 3,
						'privilege_callback' => array(
							'name' => function () use ($self) {
								$cronPermissions = $self->getCronPermissions(intval($_SESSION['user_id']));
								return (bool)($cronPermissions['cron_permission_id'] !== null);
							}
						)
					)
				);
			} elseif ($uiLevel == 'client' && ($page = $navigation->findOneBy('uri', '/client/webtools.php'))) {
				$self = $this;
				$page->addPage(
					array(
						'label' => tr('Cron jobs'),
						'uri' => '/client/cronjobs',
						'title_class' => 'tools',
						'order' => 3,
						'privilege_callback' => array(
							'name' => function () use ($self) {
								$cronPermissions = $self->getCronPermissions(intval($_SESSION['user_id']));
								return (bool)($cronPermissions['cron_permission_id'] !== null);
							}
						)
					)
				);
			}
		}
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

		if ($translator->hasCache()) {
			$translator->clearCache($this->getName());
		}
	}
}
