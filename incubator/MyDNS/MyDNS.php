<?php
/**
 * i-MSCP MyDNS Plugin
 * Copyright (C) 2010-2013 by Laurent Declercq
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
 * @subpackage  MyDNS
 * @copyright   2010-2013 by Laurent Declercq
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Class iMSCP_Plugin_MyDNS
 */
class iMSCP_Plugin_MyDNS extends iMSCP_Plugin_Action
{
	/**
	 * Process plugin installation
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function install(iMSCP_Plugin_Manager $pluginManager)
	{
		try {
			$this->createTables();
		} catch (iMSCP_Exception_Database $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Process plugin uninstallation
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Plugin_Manager $pluginManager
	 * @return void
	 */
	public function uninstall(iMSCP_Plugin_Manager $pluginManager)
	{
		try {
			$this->dropTables();
		} catch (iMSCP_Exception_Database $e) {
			throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		}
	}

	/**
	 * Register a callback for the given event(s).
	 *
	 * @param iMSCP_Events_Manager_Interface $controller
	 */
	public function register(iMSCP_Events_Manager_Interface $controller)
	{
		$controller->registerListener(
			array(
				iMSCP_Events::onAdminScriptStart,
				iMSCP_Events::onClientScriptStart,
				iMSCP_Events::onResellerScriptStart,

			),
			$this
		);
	}

	/**
	 * onClientScriptStart listener
	 *
	 * @return void
	 */
	public function onAdminScriptStart()
	{
		$this->setupNavigation('admin');
	}

	/**
	 * onResellerScriptStart listener
	 *
	 * @return void
	 */
	public function onResellerScriptStart()
	{
		$this->setupNavigation('reseller');
	}

	/**
	 * onClientScriptStart listener
	 *
	 * @return void
	 */
	public function onClientScriptStart()
	{
		$this->setupNavigation('client');
	}

	/**
	 * Get routes
	 *
	 * @return array
	 */
	public function getRoutes()
	{
		$pluginRootDir = PLUGINS_PATH . '/' . $this->getName();

		return array(
			'/admin/mydns/overview' => $pluginRootDir . '/frontend/admin/overview.php',
			'/admin/mydns/nameservers' => $pluginRootDir . '/frontend/admin/nameservers.php',
			'/admin/mydns/zones' => $pluginRootDir . '/frontend/admin/zones.php',
			'/reseller/mydns/overview' => $pluginRootDir . '/frontend/reseller/overview.php',
			'/reseller/mydns/nameservers' => $pluginRootDir . '/frontend/reseller/nameservers.php',
			'/reseller/mydns/zones' => $pluginRootDir . '/frontend/reseller/zones.php',
			'/client/mydns/overview' => $pluginRootDir . '/frontend/client/overview.php',
			'/client/mydns/zones' => $pluginRootDir . '/frontend/client/nameservers.php',
			'/client/mydns/nameservers' => $pluginRootDir . '/frontend/client/zones.php'
		);
	}

	/**
	 * Internal router
	 *
	 * @param array $urlComponents Associative array containing components of the URL that are present
	 * @param string &$actionScript Action script path
	 * @return bool
	 */
	public function route($urlComponents, &$actionScript)
	{
		if (strpos($urlComponents['path'], '/mydns/api/') === 0) {
			$actionScript = PLUGINS_PATH . '/' . $this->getName() . '/api.php';

			return true;
		}

		return false;
	}

	/**
	 * Setup plugin navigation
	 *
	 * @param string $uiLevel Current UI level
	 * @return void
	 */
	protected function setupNavigation($uiLevel)
	{
		if (iMSCP_Registry::isRegistered('navigation')) {
			/** @var Zend_Navigation $navigation */
			$navigation = iMSCP_Registry::get('navigation');
			$navigation->addPage(
				array(
					'label' => 'MyDNS',
					'uri' => "/$uiLevel/mydns/overview",
					'class' => 'custom_link',
					'order' => 2,
					'pages' => array(
						array(
							'label' => tohtml(tr('Overview')),
							'uri' => "/$uiLevel/mydns/overview",
							'title_class' => 'custom_link'
						),
						array(
							'label' => tohtml(tr('Name Servers')),
							'uri' => "/$uiLevel/mydns/nameservers",
							'title_class' => 'custom_link'
						),
						array(
							'label' => tohtml(tr('Zones')),
							'uri' => "/$uiLevel/mydns/zones",
							'title_class' => 'custom_link'
						)
					)
				)
			);
		}
	}

	/**
	 * Create tables
	 *
	 * @throw iMSCP_Exception_Database
	 * @return void
	 */
	protected function createTables()
	{
		// Create mydns_user
		exec_query(
			'
				CREATE TABLE IF NOT EXISTS `mydns_user`(
					`mydns_user_id` INT(10) UNSIGNED NOT NULL,
					`public_key` VARCHAR(255) NOT NULL,
					`private_key` VARCHAR(255) NOT NULL,
					UNIQUE `mydns_user_id` (`mydns_user_id`),
					CONSTRAINT `user_admin` FOREIGN KEY (`mydns_user_id`)
					 REFERENCES `admin` (`admin_id`) ON DELETE CASCADE ON UPDATE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
			'
		);

		// Create mydns_nameserver table
		execute_query(
			'
				CREATE TABLE IF NOT EXISTS `mydns_nameserver`(
					`mydns_nameserver_id` SMALLINT UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY,
					`mydns_user_id` INT(10) UNSIGNED NOT NULL,
					`name` VARCHAR(127) NOT NULL,
					`ttl` INT(10) UNSIGNED,
					`address` VARCHAR(127) NOT NULL,
					`confdir` VARCHAR (255),
					`datadir` VARCHAR (255),
					KEY `mydns_user_id` (`mydns_user_id`),
					KEY `name` (`name`),
					CONSTRAINT `nameserver_user` FOREIGN KEY (`mydns_user_id`)
					 REFERENCES `mydns_user` (`mydns_user_id`) ON DELETE CASCADE ON UPDATE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
			'
		);

		// Create mydns_zone table
		execute_query(
			'
				CREATE TABLE IF NOT EXISTS `mydns_zone`(
					`mydns_zone_id` INT(10) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY,
					`mydns_user_id` INT(10) UNSIGNED NOT NULL,
					`zone` VARCHAR(255) NOT NULL,
					`mailaddr` VARCHAR(127),
					`serial` INT(10) UNSIGNED NOT NULL DEFAULT 1,
					`refresh` INT(10) UNSIGNED,
					`retry` INT(10) UNSIGNED,
					`expire` INT(10) UNSIGNED,
					`minimum` INT(10) UNSIGNED,
					`ttl` INT(10) UNSIGNED,
					`location` VARCHAR(2) DEFAULT NULL,
					`last_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
					`active` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
					`template` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
					`status` VARCHAR(255) NOT NULL,
					KEY `mydns_user_id` (`mydns_user_id`),
					KEY `zone` (`zone`),
					CONSTRAINT `zone_user` FOREIGN KEY (`mydns_user_id`)
					 REFERENCES `mydns_user` (`mydns_user_id`) ON DELETE CASCADE ON UPDATE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
			'
		);

		// Create mydns_zone_nameserver table
		execute_query(
			'
				CREATE TABLE IF NOT EXISTS `mydns_zone_nameserver` (
					`mydns_zone_id` INT(10) UNSIGNED NOT NULL,
					`mydns_nameserver_id` SMALLINT(5) UNSIGNED NOT NULL,
					UNIQUE KEY `mydns_zone_nameserver_mydns_nameserver_id` (`mydns_zone_id`, `mydns_nameserver_id`),
					CONSTRAINT `zone_nameserver_zone` FOREIGN KEY (`mydns_zone_id`)
					 REFERENCES `mydns_zone` (`mydns_zone_id`) ON DELETE CASCADE ON UPDATE CASCADE,
					CONSTRAINT `zone_nameserver_nameserver` FOREIGN KEY (`mydns_nameserver_id`)
					 REFERENCES `mydns_nameserver` (`mydns_nameserver_id`) ON DELETE CASCADE ON UPDATE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
			'
		);

		// Create mydns_resource_record_type table
		execute_query(
			'
				CREATE TABLE IF NOT EXISTS `mydns_resource_record_type`(
					`mydns_resource_record_type_id` SMALLINT(2) UNSIGNED NOT NULL PRIMARY KEY,
					`name` VARCHAR(10) NOT NULL,
					`description` VARCHAR(55) NULL DEFAULT NULL,
					`reverse` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
					`forward` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1,
					UNIQUE `name` (`name`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
			'
		);

		// Populate mydns_resource_record_type table
		execute_query(
			"
				INSERT IGNORE INTO
					`mydns_resource_record_type` (
						`mydns_resource_record_type_id`, `name`, `description`, `reverse`, `forward`
					)
				VALUES
					(1, 'A', 'Address', 0, 1),
					(2, 'NS', 'Name Server', 1, 1),
					(3, 'CNAME', 'Canonical Name', 1, 1),
					(4, 'SOA', 'Start Of Authority', 0, 0),
					(5, 'PTR', 'Pointer', 1, 0),
					(6, 'MX', 'Mail Exchanger', 0, 1),
					(7, 'TXT', 'Text', 1, 1),
					(8, 'SIG', 'Signature', 0, 0),
					(9, 'KEY', 'Key', 0, 0),
					(10, 'AAAA', 'Address IPv6', 0, 1),
					(11, 'LOC', 'Location', 0, 1),
					(12, 'SRV', 'Service', 0, 1),
					(13, 'NAPTR', 'Naming Authority Pointer', 1, 1),
					(14, 'DNAME', 'Delegation Name', 0, 0),
					(15, 'DS', 'Delegation Signer', 0, 1),
					(16, 'SSHFP', 'Secure Shell Key Fingerprints', 0, 1),
					(17, 'RRSIG', 'Resource Record Signature', 0, 1),
					(18, 'NSEC', 'Next Secure', 0, 1),
					(19, 'DNSKEY', 'DNS Public Key', 0, 1),
					(20, 'NSEC3', 'Next Secure v3', 0, 0),
					(21, 'NSEC3PARAM', 'NSEC3 Parameters', 0, 0),
					(22, 'SPF', 'Sender Policy Framework', 0, 1),
					(23, 'TSIG', 'Transaction Signature', 0, 0),
					(24, 'AXFR', NULL, 0, 0)
			"
		);

		// Create mydns_zone_record table
		execute_query(
			'
				CREATE TABLE IF NOT EXISTS `mydns_zone_record`(
					`mydns_zone_record_id` INT(10) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY,
					`mydns_zone_id` INT(10) UNSIGNED NOT NULL,
					`name` VARCHAR(255) NOT NULL,
					`ttl` INT(10) UNSIGNED NOT NULL DEFAULT 0,
					`type_id` SMALLINT(2) UNSIGNED NOT NULL,
					`address` VARCHAR(512) NOT NULL,
					`weight` SMALLINT UNSIGNED,
					`priority` SMALLINT UNSIGNED,
					`other` VARCHAR(255),
					`location` VARCHAR(2) DEFAULT NULL,
					`timestamp` TIMESTAMP NULL DEFAULT NULL,
					KEY `mydns_zone_id` (`mydns_zone_id`),
					KEY `name` (`name`),
					KEY `address` (`address`),
					CONSTRAINT `zone_record_zone` FOREIGN KEY (`mydns_zone_id`)
					 REFERENCES `mydns_zone` (`mydns_zone_id`) ON DELETE CASCADE ON UPDATE CASCADE,
					CONSTRAINT `zone_record_resource_record_type` FOREIGN KEY (`type_id`)
					 REFERENCES `mydns_resource_record_type` (`mydns_resource_record_type_id`)
					 ON DELETE CASCADE ON UPDATE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
			'
		);

		// Create mydns_permission table
		execute_query(
			'
				CREATE TABLE IF NOT EXISTS `mydns_permission`(
					`mydns_permission_id` INT(10) UNSIGNED AUTO_INCREMENT NOT NULL PRIMARY KEY,
					`mydns_user_id` INT(10) UNSIGNED DEFAULT NULL,
					`permission_name` VARCHAR(50),
					`zone_write` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
					`zone_create` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
					`zone_delete` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
					`zone_record_write` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
					`zone_record_create` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
					`zone_record_delete` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
					`nameserver_write` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
					`nameserver_create` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
					`nameserver_delete` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
					`self_write` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
					`usable_ns` VARCHAR(50),
					KEY `mydns_user_id` (`mydns_user_id`),
					CONSTRAINT `permission_user` FOREIGN KEY (`mydns_user_id`)
					 REFERENCES `mydns_user` (`mydns_user_id`) ON DELETE CASCADE ON UPDATE CASCADE
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
			'
		);

		/*
		// Give all permissions to system administrators

		$stmt = execute_query("SELECT `admin_id` FROM `admin` WHERE `admin_type` = 'admin'");

		$pk = 1;
		while($data = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			exec_query(
				'
					INSERT IGNORE INTO
						`mydns_permission`
					VALUE (
						?, ?, NULL, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 2
					)
				',
				array($pk, $data['admin_id'])
			);

			$pk++;
		}
		*/
	}

	/**
	 * Drop tables
	 *
	 * @throw iMSCP_Exception_Database
	 * @return void
	 */
	protected function dropTables()
	{
		foreach (
			array(
				'mydns_permission', 'mydns_zone_record', 'mydns_resource_record_type', 'mydns_zone_nameserver',
				'mydns_zone', 'mydns_nameserver', 'mydns_user'
			) as $table
		) {
			exec_query("DROP TABLE IF EXISTS $table");
		}
	}
}
