<?php
/**
 * ServerProvisioning plugin for i-MSCP
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
 *
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  ServerProvisioning
 * @copyright   2014 by Laurent Declercq <l.declercq@nuxwin.com>
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/lgpl-2.1.txt LGPL v2.1
 */

namespace ServerProvisioning\Controller;

use iMSCP_Events as Events;

/**
 * Class Domain
 *
 * @package ServerProvisioning\Controller
 */
class Domain extends AbstractController
{
	/**
	 * Create domain
	 *
	 * @param $data Domain data
	 * @return array Response
	 */
	public function create($data)
	{
		$resellerId = $this->identity->adminId;

		$this->getEventManager()->dispatch(
			Events::onBeforeAddDomain,
			array(
				'domainName' => $data['domain_name'],
				'createdBy' => $resellerId,
				'customerId' => $data['customer_id'],
				'customerEmail' =>  $data['email']
			)
		);

		$timestamp = time();

		exec_query(
			'
				INSERT INTO domain (
					domain_name,
					domain_admin_id,
					domain_created,
					domain_expires,
					domain_mailacc_limit,
					domain_ftpacc_limit,
					domain_traffic_limit,
					domain_sqld_limit,
					domain_sqlu_limit,
					domain_status,
					domain_subd_limit,
					domain_alias_limit,
					domain_ip_id,
					domain_disk_limit,
					domain_disk_usage,
					domain_php,
					domain_cgi,
					allowbackup,
					domain_dns,
					domain_software_allowed,
					phpini_perm_system,
					phpini_perm_allow_url_fopen,
					phpini_perm_display_errors,
					phpini_perm_disable_functions,
					domain_external_mail,
					web_folder_protection,
					mail_quota
				) VALUES (
					?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
				)
			',
			$data['domain_name'],
			$data['user_id'],
			$timestamp,
			0,
			$data['mail_accounts'],
			$data['ftp_accounts'],
			$data['monthly_traffic'],
			$data['sql_databases'],
			$data['sql_users'],
			'toadd',
			$data['subdomains'],
			$data['domain_aliases'],
			$data['domain_ip_id'],
			$data['disk_quota'],
			0,
			$data['php'],
			$data['cgi'],
			$data['backup'],
			$data['custom_dns_records'],
			$data['software_installer'],
			$data['php_editor'],
			$data['allow_url_fopen'],
			$data['php_display_errors'],
			$data['php_disable_functions'],
			$data['external_mail_server'],
			$data['web_folder_protection'],
			$data['mail_quota']
		);

		$domainId = $this->db->insertId();

		$this->getEventManager()->dispatch(
			Events::onAfterAddDomain,
			array(
				'domainName' => $data['domain_name'],
				'createdBy' => $resellerId,
				'customerId' => $data['user_id'],
				'customerEmail' => $data['email'],
				'domainId' => $domainId
			)
		);

		$this->sendRequest = true;

		return array(
			'code' => '201', 'message' => 'Domain successfully created',
			'domain_id' => $domainId

		);
	}

	/**
	 * Read domain
	 *
	 * @param $data Domain data
	 * @return array Response
	 */
	public function read($data)
	{
		$stmt = exec_query('SELECT FROM domain WHERE domain_name = ?', $data['domain_name']);

		if($stmt->rowCount()) {

		}
	}

	/**
	 * Update Domain
	 *
	 * @param $data Domain data
	 * @return array Response
	 */
	public function update($data)
	{
		// TODO
	}

	/**
	 * Delete domain
	 *
	 * @param $data Domain data
	 * @return array Response
	 */
	public function delete($data)
	{
		// TOODO
	}

	/**
	 * Return array describing payload requirements
	 *
	 * @param string $action Action
	 * @return array
	 */
	protected function getPayloadRequirements($action)
	{
		$req = array(
			'create' => array(
				'domain_name',
				'user_id',
				'php',
				'cgi',
				'subdomains',
				'domain_aliases',
				'mail_accounts',
				'ftp_accounts',
				'sql_databases',
				'sql_users',
				'monthly_traffic',
				'disk_quota',
				'backup',
				'custom_dns_records',
				'software_installer',
				'php_editor',
				'allow_url_fopen',
				'php_display_errors',
				'php_disable_functions',
				'php_post_max_size',
				'php_upload_max_filesize',
				'php_max_execution_time',
				'php_max_input_time',
				'php_memory_limit',
				'external_mail_server',
				'web_folder_protection',
			),
			'read' => array('domain_name'),
			'update' => array(),
			'delete' => array('domain_name')
		);

		return $req[$action];
	}
}
