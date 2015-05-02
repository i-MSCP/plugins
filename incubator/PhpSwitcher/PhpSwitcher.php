<?php
/**
 * i-MSCP PhpSwitcher plugin
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
 * Class iMSCP_Plugin_PhpSwitcher
 */
class iMSCP_Plugin_PhpSwitcher extends iMSCP_Plugin_Action
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
	 * @param $eventManager iMSCP_Events_Manager_Interface $eventManager
	 */
	public function register(iMSCP_Events_Manager_Interface $eventManager)
	{
		$eventManager->registerListener(
			array(
				iMSCP_Events::onBeforeInstallPlugin,
				iMSCP_Events::onBeforeUpdatePlugin,
				iMSCP_Events::onBeforeEnablePlugin
			),
			array($this, 'checkRequirements')
		);

		$eventManager->registerListener(
			array(iMSCP_Events::onAdminScriptStart, iMSCP_Events::onClientScriptStart), array($this, 'setupNavigation')
		);

		$eventManager->registerListener(
			array(iMSCP_Events::onAfterDeleteDomainAlias, iMSCP_Events::onAfterDeleteSubdomain),
			array($this, 'afterDeleteDomain')
		);
	}

	/**
	 * Check requirements
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function checkRequirements(iMSCP_Events_Event $event)
	{
		if ($event->getParam('pluginName') == $this->getName()) {
			$config = iMSCP_Registry::get('config');

			if ($config['HTTPD_SERVER'] != 'apache_fcgid') {
				set_page_message(tr('This plugin require the apache fcgid i-MSCP server implementation.'), 'error');
				$event->stopPropagation();
			}
		}
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
		} catch (iMSCP_Plugin_Exception $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
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
		$db = iMSCP_Database::getRawInstance();

		try {
			$db->beginTransaction();

			$stmt = execute_query('SELECT domain_name, domain_type FROM php_switcher_version_admin');

			if ($stmt->rowCount()) {
				$this->scheduleDomainsChange($stmt->fetchAll(PDO::FETCH_KEY_PAIR));
			}

			$db->commit();
		} catch (iMSCP_Exception_Database $e) {
			$db->rollBack();
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Plugin deactivation
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function disable(iMSCP_Plugin_Manager $pluginManager)
	{
		$db = iMSCP_Database::getRawInstance();

		try {
			$db->beginTransaction();

			$stmt = execute_query('SELECT domain_name, domain_type FROM php_switcher_version_admin');

			if ($stmt->rowCount()) {
				$this->scheduleDomainsChange($stmt->fetchAll(PDO::FETCH_KEY_PAIR));
			}

			$db->commit();
		} catch (iMSCP_Exception_Database $e) {
			$db->rollBack();
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
		} catch (iMSCP_Plugin_Exception $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Get routes
	 *
	 * @return array
	 */
	public function getRoutes()
	{
		$pluginDir = $this->getPluginManager()->pluginGetDirectory() . '/' . $this->getName();

		$routes = array(
			'/admin/phpswitcher' => $pluginDir . '/frontend/admin/phpswitcher.php',
			'/client/phpswitcher' => $pluginDir . '/frontend/client/phpswitcher.php',
		);

		if ($this->getConfigParam('phpinfo', true)) {
			$routes['/client/phpswitcher/phpinfo'] = $pluginDir . '/frontend/client/phpinfo.php';
		}

		return $routes;
	}

	/**
	 * Setup plugin navigation
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function setupNavigation(iMSCP_Events_Event $event)
	{
		$eventName = $event->getName();

		if (iMSCP_Registry::isRegistered('navigation')) {
			/** @var Zend_Navigation $navigation */
			$navigation = iMSCP_Registry::get('navigation');

			if ($eventName == 'onAdminScriptStart' && ($page = $navigation->findOneBy('uri', '/admin/settings.php'))) {
				$page->addPage(
					array(
						'label' => tr('PHP Switcher'),
						'uri' => '/admin/phpswitcher',
						'title_class' => 'settings',
						'order' => 8
					)
				);
			} elseif (
				$eventName == 'onClientScriptStart' && customerHasFeature('php') &&
				($page = $navigation->findOneBy('uri', '/client/domains_manage.php'))
			) {
				$page->addPage(
					array(
						'label' => tr('PHP Switcher'),
						'uri' => '/client/phpswitcher',
						'title_class' => 'domains'
					)
				);
			}
		}
	}

	/**
	 * Schedule change for all given domains
	 *
	 * @throw iMSCP_Exception_Database
	 * @param array $domainData Domain data
	 * @return void
	 */
	public function scheduleDomainsChange(array $domainData)
	{
		$domains = array();
		$subdomains = array();
		$aliases = array();
		$subAliases = array();

		foreach ($domainData as $domainName => $domainType) {
			switch ($domainType) {
				case 'dmn':
					$domains[] = quoteValue($domainName);
					break;
				case 'sub':
					list($domainName) = explode('.', $domainName);
					$subdomains[] = quoteValue($domainName);
					break;
				case 'als':
					$aliases[] = quoteValue($domainName);
					break;
				case 'subals':
					list($domainName) = explode('.', $domainName);
					$subAliases[] = quoteValue($domainName);
			}
		}

		foreach (
			array(
				'dmn' => $domains, 'sub' => $subdomains, 'als' => $aliases, 'subals' => $subAliases
			) as $domainType => $domains
		) {
			if (!empty($domains)) {
				switch ($domainType) {
					case 'dmn':
						$query = "
							UPDATE
								domain
							SET
								domain_status = 'tochange'
							WHERE
								domain_name IN(" . implode(',', $domains) . ")
							AND
								domain_status = 'ok'
						";
						break;
					case 'sub':
						$query = "
							UPDATE
								subdomain
							SET
								subdomain_status = 'tochange'
							WHERE
								subdomain_name IN(" . implode(',', $domains) . ")
							AND
								subdomain_status = 'ok'
						";
						break;
					case 'als':
						$query = "
							UPDATE
								domain_aliasses
							SET
								alias_status = 'tochange'
							WHERE
								alias_name IN(" . implode(',', $domains) . ")
							AND
								alias_status = 'ok'
						";
						break;
					case 'subals':
						$query = "
							UPDATE
								subdomain_alias
							SET
								subdomain_alias_status = 'tochange'
							WHERE
								subdomain_alias_name IN(" . implode(', ', $domains) . ")
							AND
								subdomain_alias_status = 'ok'
						";
				}

				if (isset($query)) {
					execute_query($query);
				}
			}
		}
	}

	/**
	 * Event listener responsible to delete PHP version data which belongs to a deleted domain (als,sub,alssub)
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function afterDeleteDomain(iMSCP_Events_Event $event)
	{
		$domainType = $event->getParam('type', 'als');

		if (in_array($domainType, array('sub', 'alssub'))) {
			if ($domainType == 'sub') {
				exec_query(
					"
						DELETE FROM
							t1
						USING
							php_switcher_version_admin AS t1
						JOIN
							subdomain AS t2 ON (t2.subdomain_id = 3)
						JOIN
							domain AS t3 USING(domain_id)
						WHERE
							t1.domain_name = CONCAT(t2.subdomain_name, '.', t3.domain_name)
					",
					$event->getParam('subdomainId')
				);
			} else {
				exec_query(
					"
						DELETE FROM
							t1
						USING
							php_switcher_version_admin AS t1
						JOIN
							subdomain_alias AS t2 ON(t2.subdomain_alias_id = ?)
						JOIN
							domain_aliasses AS t3 USING(alias_id)
						WHERE
							t1.domain_name = CONCAT(t2.subdomain_alias_name, '.', t3.alias_name)
					",
					$event->getParam('subdomainId')
				);
			}
		} else {
			exec_query(
				'DELETE FROM php_switcher_version_admin WHERE domain_name = ?', $event->getParam('domainAliasName')
			);
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
