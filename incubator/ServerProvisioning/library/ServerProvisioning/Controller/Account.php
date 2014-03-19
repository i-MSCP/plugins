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

use Exception;
use iMSCP_Events as Events;
use iMSCP_Events_Manager as EventsManager;
use iMSCP_PHPini as PhpEditor;

/**
 * Class Account
 *
 *  API calls:
 *
 * Create:    POST   http(s)://<panel.tld>/<base_endpoint>/accounts
 * Update:    PUT    http(s)://<panel.tld>/<base_endpoint>/accounts/<account_name>
 * Suspend:   PUT    http(s)://<panel.tld>/<base_endpoint>/accounts/<account_name>/suspend
 * Unsuspend: PUT    http(s)://<panel.tld>/<base_endpoint>/accounts/<account_name>/unsuspend
 * Delete:    DELETE http(s)://<panel.tld>/<base_endpoint>/accounts/<account_name>
 *
 * @package ServerProvisioning\Controller
 */
class Account extends AbstractController
{
	/**
	 * Create account
	 *
	 * Only resellers can create new account
	 *
	 * @param array $data
	 */
	public function create(array $data)
	{
		try {
			$resellerId = $this->identity->admin_id;

			$packageStr = implode(
				';',
				array(
					'', '', $data['subdomains'], $data['domain_aliases'], $data['mail_accounts'], $data['ftp_accounts'],
					$data['sql_databases'], $data['sql_users'], $data['monthly_traffic'], $data['disk_quota']
				)
			);

			if (reseller_limits_check($resellerId, $packageStr)) {
				$this->db->beginTransaction();

				EventsManager::getInstance()->dispatch(
					Events::onBeforeAddDomain,
					array(
						'domainName' => $data['domain_name'],
						'createdBy' => $resellerId,
						'customerId' => $data['customer_id'],
						'customerEmail' =>  $data['email']
					)
				);

				// Get PHP editor default permissions
				$phpEditorPermissions = PhpEditor::getInstance()->getClPerm();

				$timestamp = time();
				$domainIpId = ''; // TODO

				// Create customer
				exec_query(
					'
						INSERT INTO admin (
							admin_name,
							admin_pass,
							admin_type,
							domain_created,
							created_by,
							fname,
							lname,
							firm,
							zip,
							city,
							state,
							country,
							email,
							phone,
							fax,
							street1,
							street2,
							customer_id,
							gender
						) VALUES (
							?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
						)
					',
					$data['account_name'],
					cryptPasswordWithSalt($data['password']),
					'user',
					$timestamp,
					$data['reseller_id'],
					$data['firstname'],
					$data['lastname'],
					$data['firm'],
					$data['zipcode'],
					$data['city'],
					$data['state'],
					$data['country'],
					$data['email'],
					$data['phone'],
					$data['fax'],
					$data['street_1'],
					$data['street_2'],
					$data['customer_id'],
					$data['gender']
				);

				$accountId = $this->db->lastInsertId();

				// create domain
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
					$accountId,
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
					$domainIpId,
					$data['disk_quota'],
					0, $data['php'],
					$data['cgi'],
					$data['backup'],
					$data['custom_dns_records'],
					$data['software_installer'],
					$data['php_editor'],
					$phpEditorPermissions['phpiniAllowUrlFopen'],
					$phpEditorPermissions['phpiniDisplayErrors'],
					$phpEditorPermissions['phpiniDisableFunctions'],
					$data['external_mail_server'],
					$data['web_folder_protection'],
					$data['mail_quota']
				);

				$domainId = $this->db->lastInsertId();

				// Create htuser for statistics
				exec_query(
					'INSERT INTO htaccess_users (dmn_id, uname, upass, status) VALUES(?, ?, ?, ?)',
					array(
						$domainId,
						$data['domain_name'],
						cryptPasswordWithSalt($data['password']),
						'toadd'
					)
				);

				$htuserId = $this->db->lastInsertId();

				// Create htgroup for statistic access
				exec_query(
					'INSERT INTO htaccess_group (dmn_id, ugroup, members, status) VALUES (?, ?, ?, ?)',
					array(
						$domainId,
						$this->imscpConfig['AWSTATS_GROUP_AUTH'],
						$htuserId,
						'toadd'
					)
				);

				// Create default addresses if needed
				client_mail_add_default_accounts($domainId, $data['email'], $data['domain_name']);

				$this->db->commit();

				EventsManager::getInstance()->dispatch(
					Events::onAfterAddDomain,
					array(
						'domainName' => $data['domain_name'],
						'createdBy' => $resellerId,
						'customerId' => $accountId,
						'customerEmail' => $data['email'],
						'domainId' => $domainId
					)
				);

				send_request();
				$response = array('code' => 201, 'message' => 'Account successfully scheduled for creation');
			} else {
				$response = array('code' => 422, 'message' => 'Limits reached');
			}
		} catch (Exception $e) {
			$this->db->rollBack();

			if ($e->getCode() == '23000') {
				$response = array('code' => 409, 'message' => sprintf('Account already exists'));
			} else {
				throw $e;
			}
		}

		return $response;
	}

	/**
	 * Read account
	 *
	 * @param array $data
	 * @return array nameserver data
	 */
	public function read(array $data)
	{
		$stmt = exec_query(
			'
				SELECT
					admin_name as account_name,
					email,
					fname as firstname,
					lname as lastname,
					firm,
					zip as zipcode,
					city,
					state,
					country,
					phone,
					fax,
					street1,
					street2,
					customer_id
					gender,
					domain_php as php,
					domain_cgi as cgi,
					domain_subd_limit as subdomains,
					domain_alias_limit as domain_aliases,
					domain_mailacc_limit as mail_accounts,
					domain_ftpacc_limit as ftp_accounts,
					domain_sqld_limit as sql_databases,
					domain_sqlu_limit as sql_users,
					domain_traffic_limit as monthly_traffic,
					domain_disk_limit as disk_quota,
					allowbackup as backup,
					domain_dns as custom_dns_records,
					domain_software_allowed as software_installer,
					phpini_perm_system as php_editor,
					external_mail as external_mail_server,
					web_folder_protection,
					domain_status as status
				FROM
					admin
				INNER JOIN
					domain on(domain_admin_id = admin_id)
				WHERE
					admin_id = ?
				AND
					created_by = ?
			',
			array($data['account_id'], $this->identity->admin_id)
		);

		if (!$stmt->rowCount()) {
			$response = array('code' => 404, 'message' => 'Account not found');
		} else {
			$response['account'] = $stmt->fetchRow();
		}

		return $response;
	}

	/**
	 * Change account password
	 *
	 * @param array $data
	 */
	public function update(array $data)
	{
		$stmt = exec_query(
			'UPDATE admin SET admin_pass = ? WHERE admin_name = ? AND created_by = ?',
			array(
				cryptPasswordWithSalt($data['password']), $data['account_name'], $this->identity->admin_id,
			)
		);

		if(!$stmt->rowCount()) {
			$response = array('code' => '404', 'message' => 'Account not found');
		} else {
			$response = array('code' => '200', 'message' => 'Account password successfully updated');
		}

		return $response;
	}

	/**
	 * Suspend account
	 *
	 * @param array $data
	 * @return array
	 */
	public function suspend(array $data)
	{
		$stmt = exec_query(
			'SELECT admin_id FROM admin WHERE admin_name = ? AND created_by = ?',
			array($data['account_name'], $this->identity->admin_id)
		);

		if($stmt->rowCount()) {
				change_domain_status($data['account_id'], 'deactivate');
				$response = array('code' => '200', 'message' => 'Account succcessfully schceduled for suspension');
		} else {
			$response = array('code' => '404', 'message' => 'Account not found');
		}

		return $response;
	}

	/**
	 * Unsuspend account
	 *
	 * @param array $data
	 * @return array
	 */
	public function unsuspend(array $data)
	{
		$stmt = exec_query(
			'SELECT admin_id FROM admin WHERE admin_name = ? AND created_by = ?',
			array($data['account_name'], $this->identity->admin_id)
		);

		if($stmt->rowCount()) {
			change_domain_status($data['account_id'], 'deactivate');
			$response = array('code' => '200', 'message' => 'Account succcessfully schceduled for suspension');
		} else {
			$response = array('code' => '404', 'message' => 'Account not found');
		}

		return $response;
	}

	/**
	 * Delete account
	 *
	 * @param array $data
	 * @return $response
	 */
	public function delete(array $data)
	{
		$stmt = exec_query('SELECT admin_id FROM admin WHERE admin_name = ?', $data['account_name']);

		if($stmt->rowCount()) {
			if(deleteCustomer($data['account_name'], $this->identity->admin_id)) {
				$response = array('code' => '200', 'message' => 'Account successfully scheduled for deletion');
			} else {
				$response = array('code' => '404', 'message' => 'Account not found');
			}
		} else {
			$response = array('code' => '404', 'message' => 'Account not found');
		}

		return $response;
	}

	/**
	 * Return array describing payload requirements
	 *
	 * @param string $apiFunction
	 * @return array
	 */
	protected function getPayloadRequirements($apiFunction)
	{
		static $req = array(
			'create' => array(
				'account_name',
				'password',
				'email',
				'firstname',
				'lastname',
				'firm',
				'zipcode',
				'city',
				'state',
				'country',
				'phone',
				'fax',
				'street1',
				'street2',
				'customer_id',
				'gender',
				'domain_name',
				'hosting_plan',
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
				'external_mail_server',
				'web_folder_protection'
			),
			'read' => array('account_name'),
			'update' => array('account_name'),
			'delete' => array('account_name'),
			'suspsend' => array('account_name'),
			'unsuspend' => array('account_name'),
			'change_password' => array('account_name', 'password')
		);

		return $req[$apiFunction];
	}
}
