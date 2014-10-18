<?php
/**
 * i-MSCP CronJobs plugin
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
 * Class iMSCP_Plugin_CronJobs
 */
class iMSCP_Plugin_CronJobs extends iMSCP_Plugin_Action
{
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
				iMSCP_Events::onBeforeDisablePlugin,
				iMSCP_Events::onAdminScriptStart,
				iMSCP_Events::onResellerScriptStart,
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
			try {

			} catch (iMSCP_Exception $e) {
				throw new iMSCP_Plugin_Exception(tr('Unable to enable: %s', $e->getMessage()), $e->getCode(), $e);
			}
		}
	}

	/**
	 * onBeforeDisablePlugin event listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeDisablePlugin($event)
	{
		if ($event->getParam('pluginName') === 'InstantSSH') {
			/** @var iMSCP_Plugin_Manager $pluginManager */
			$pluginManager = iMSCP_Registry::get('pluginManager');

			if($pluginManager->isPluginInstalled($this->getName())) {
				$stmt = exec_query(
					'SELECT COUNT(cron_permission_id) AS cnt FROM cron_permissions WHERE cron_permission_type = ?',
					'jailed'
				);
				$row = $stmt->fetchRow(PDO::FETCH_ASSOC);

				if($row['cnt']) {
					set_page_message(
						tr('InstantSSH plugin is required by the %s plugin. You cannot disable it', $this->getName()),
						'error'
					);

					$event->stopPropagation();
				}
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
			exec_query(
				'UPDATE cron_permissions SET cron_permission_status = ?', 'todelete'
			);
		} catch (iMSCP_Exception $e) {
			throw new iMSCP_Plugin_Exception(tr('Unable to uninstall: %s', $e->getMessage()), $e->getCode(), $e);
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
			$this->migrateDb('down');
		} catch (iMSCP_Plugin_Exception $e) {
			throw new iMSCP_Plugin_Exception(tr('Unable to delete: %s', $e->getMessage()), $e->getCode(), $e);
		}
	}

	/**
	 * onAfterChangeDomainStatus listener
	 *
	 * When a customer account is being activated, we schedule reactivation of cron feature
	 * When a customer account is being deactivated, we schedule deactivation of cron feature
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
				'UPDATE cron_jobs SET cron_job_status = ? WHERE cron_job_admin_id = ?',
				array('toenable', $customerId)
			);
		} else {
			exec_query(
				'UPDATE cron_jobs SET cron_job_status = ? WHERE cron_job_admin_id = ?',
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
				cron_permission_id AS item_id, cron_permission_status AS status,
				CONCAT('Cron permssions for: ', admin_name, '( ', admin_type, ' )') as item_name,
				'cron_permissions' AS `table`, 'cron_permission_status' AS `field`
			FROM
				cron_permissions
			INNER JOIN
				admin ON(admin_id = cron_permission_admin_id)
			WHERE
				cron_permission_status NOT IN(:ok, :toadd, :tochange, :todelete)
			UNION
			SELECT
				cron_job_id AS item_id, cron_job_status AS status, 'cron job' AS item_name,
				'cron_jobs' AS `table`, 'cron_job_status' AS `field`
			FROM
				cron_jobs
			WHERE
				cron_job_status NOT IN(:ok, :disabled, :toadd, :tochange, :toenable, :todisable, :todelete)
			",
			array(
				'ok' => 'ok', 'disabled' => 'disabled', 'toadd' => 'toadd', 'tochange' => 'tochange',
				'toenable' => 'toenable', 'todisable' => 'todisable', 'todelete' => 'todelete'
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
							cron_job_status IN (:toadd, :tochange, :toenable, :todisable, :todelete)
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
			'/admin/cron/permissions' => PLUGINS_PATH . '/' . $pluginName . '/frontend/admin/cron_permissions.php',
			'/reseller/cron/permissions' => PLUGINS_PATH . '/' . $pluginName . '/frontend/reseller/cron_permissions.php',
			'/client/cron/jobs' => PLUGINS_PATH . '/' . $pluginName . '/frontend/client/cron_jobs.php'
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
	 * Check plugin compatibility
	 *
	 * @param iMSCP_Events_Event $event
	 */
	protected function checkCompat($event)
	{
		if ($event->getParam('pluginName') == $this->getName()) {
			if (version_compare($event->getParam('pluginManager')->getPluginApiVersion(), '0.2.11', '<')) {
				set_page_message(
					tr('Your i-MSCP version is not compatible with this plugin. Try with a newer version.'), 'error'
				);

				$event->stopPropagation();
			}
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
						'label' => tr('Cron Permissions'),
						'uri' => '/admin/cron/permissions',
						'title_class' => 'settings',
						'order' => 9
					)
				);
			} elseif ($uiLevel == 'reseller' && ($page = $navigation->findOneBy('uri', '/reseller/users.php'))) {
				//$self = $this;

				$page->addPage(
					array(
						'label' => tr('Cron Permissions'),
						'uri' => '/reseller/cron/permissions',
						'title_class' => 'settings',
						'order' => 7,
						//'privilege_callback' => array(
						//	'name' => function () use ($self) {
						//			$cronPermissions = $self->getResellerPermissions($_SESSION['user_id']);
						//			return (bool)($cronPermissions['cron_permission_id'] !== null);
						//		}
						//)
					)
				);
			} elseif ($uiLevel == 'client' && ($page = $navigation->findOneBy('uri', '/client/webtools.php'))) {
				//$self = $this;

				$page->addPage(
					array(
						'label' => tr('Cron Jobs'),
						'uri' => '/client/cron/jobs',
						'title_class' => 'tools',
						//'privilege_callback' => array(
						//	'name' => function () use ($self) {
						//			$cronPermissions = $self->getCustomerPermissions($_SESSION['user_id']);
						//			return (bool)($cronPermissions['cron_permission_id'] !== null);
						//		}
						//)
					)
				);
			}
		}
	}
}
