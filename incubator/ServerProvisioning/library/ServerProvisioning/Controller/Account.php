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

use iMSCP_Plugin_Exception as Exception;

/**
 * Class Account
 *
 *  API calls:
 *
 * Create:    POST   http(s)://<panel.tld>/<base_endpoint>/accounts
 * Update:    PUT    http(s)://<panel.tld>/<base_endpoint>/accounts/:account_name
 * Suspend:   PUT    http(s)://<panel.tld>/<base_endpoint>/accounts/:account_name/suspend
 * Unsuspend: PUT    http(s)://<panel.tld>/<base_endpoint>/accounts/:account_name/unsuspend
 * Delete:    DELETE http(s)://<panel.tld>/<base_endpoint>/accounts/:account_name
 *
 * @package ServerProvisioning\Controller
 */
class Account extends AbstractController
{
	/**
	 * Create customer account
	 *
	 * @param array $data Account data
	 * @return array Response
	 */
	public function create(array $data)
	{
		$resellerId = $this->identity->admin_id;

		$packageStr = implode(
			';',
			array(
				'', '', $data['subdomains'], $data['domain_aliases'], $data['mail_accounts'], $data['ftp_accounts'],
				$data['sql_databases'], $data['sql_users'], $data['monthly_traffic'], $data['disk_quota']
			)
		);

		if (reseller_limits_check($resellerId, $packageStr)) {
			try {
				$this->db->beginTransaction();

				// Create user
				$user = new User();

				if ($user->checkPayload('create', $data)) {
					$response = $user->create($data);

					if ($response['code'] != 201) {
						return $response;
					}

					$data['user_id'] = $response['user_id'];
				}

				// Create customer main domain
				$domain = new Domain();

				if ($domain->checkPayload('create', $data)) {
					$response = $domain->create($data);

					if ($response['code'] != 201) {
						return $response;
					}

					$data['domain_id'] = $response['domain_id'];
				}

				// Create htuser fpr statistic access
				$htuser = new Htuser();

				if ($htuser->checkPayload('create', $data)) {
					$data['password'] = cryptPasswordWithSalt($data['password']);

					$response = $htuser->create($data);

					if ($response['code'] != 201) {
						return $response;
					}

					$data['htuser_id'] = $response['htuser_id'];
				}

				// Create htgroup for statistic access
				$htgroup = new Htgroup();

				if ($htgroup->checkPayload('create', $data)) {
					$data['group_name'] = $this->imscpConfig['AWSTATS_GROUP_AUTH'];

					$response = $htuser->$htgroup($data);

					if ($response['code'] != 201) {
						return $response;
					}
				}

				// Create default addresses if needed
				client_mail_add_default_accounts($data['domain_id'], $data['email'], $data['domain_name']);

				$this->db->commit();
			} catch (Exception $e) {
				$this->db->rollBack();

				if($e->getCode() == '23000') {
					return array('code' => '409', 'Account already exist');
				}
			}
		} else {
			return array('code' => 403, 'message' => 'Limit reached');
		}

		return array('code' => '201', 'message' => 'Account successfully scheduled for creation');
	}

	/**
	 * Read customer account
	 *
	 * @param array $data Account data
	 * @return array Response
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
					admin_name = ?
				AND
					created_by = ?
			',
			array($data['account_name'], $this->identity->admin_id)
		);

		if (!$stmt->rowCount()) {
			$response = array('code' => 404, 'message' => 'Account not found');
		} else {
			$response['account'] = $stmt->fetchRow();
		}

		return $response;
	}

	/**
	 * Update customer account (password change only)
	 *
	 * @param array $data Account data
	 * @return array Response
	 */
	public function update(array $data)
	{
		$stmt = exec_query(
			'UPDATE admin SET admin_pass = ? WHERE admin_name = ? AND created_by = ?',
			array(
				cryptPasswordWithSalt($data['password']), $data['account_name'], $this->identity->admin_id,
			)
		);

		if (!$stmt->rowCount()) {
			$response = array('code' => '404', 'message' => 'Account not found');
		} else {
			$response = array('code' => '200', 'message' => 'Account password successfully updated');
		}

		return $response;
	}

	/**
	 * Suspend customer account
	 *
	 * @param array $data Account data
	 * @return array Response
	 */
	public function suspend(array $data)
	{
		$stmt = exec_query(
			'SELECT admin_id FROM admin WHERE admin_name = ? AND created_by = ?',
			array($data['account_name'], $this->identity->admin_id)
		);

		if ($stmt->rowCount()) {
			change_domain_status($data['account_id'], 'deactivate');
			$response = array('code' => '200', 'message' => 'Account succcessfully scheduled for deactivation');
		} else {
			$response = array('code' => '404', 'message' => 'Account not found');
		}

		return $response;
	}

	/**
	 * Unsuspend customer account
	 *
	 * @param array $data Account data
	 * @return array Response
	 */
	public function unsuspend(array $data)
	{
		$stmt = exec_query(
			'SELECT admin_id FROM admin WHERE admin_name = ? AND created_by = ?',
			array($data['account_name'], $this->identity->admin_id)
		);

		if ($stmt->rowCount()) {
			change_domain_status($data['account_id'], 'deactivate');
			$response = array('code' => '200', 'message' => 'Account succcessfully scheduled for activation');
		} else {
			$response = array('code' => '404', 'message' => 'Account not found');
		}

		return $response;
	}

	/**
	 * Delete customer account
	 *
	 * @param array $data Account data
	 * @return array Response
	 */
	public function delete(array $data)
	{
		$stmt = exec_query('SELECT admin_id FROM admin WHERE admin_name = ?', $data['account_name']);

		if ($stmt->rowCount()) {
			if (deleteCustomer($data['account_name'], $this->identity->admin_id)) {
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
	 * @param array $data Account data
	 * @return array Response
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
			'update' => array('account_name', 'password'),
			'suspsend' => array('account_name'),
			'unsuspend' => array('account_name'),
			'delete' => array('account_name'),
		);

		return $req[$apiFunction];
	}
}
