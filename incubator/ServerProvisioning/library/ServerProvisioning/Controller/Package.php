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

use PDO;

/**
 * Class Account
 *
 *  API calls:
 *
 * Create:     POST   http://<panel.tld>/<api_endpoint>/packages
 * Read:       GET    http://<panel.tld>/<api_endpoint>/packages/:package_name
 * Update:     PUT    http://<panel.tld>/<api_endpoint>/packages/:package_name
 * Delete:     DELETE http://<panel.tld>/<api_endpoint>/packages/:package_name
 * Collection: GET    http://<panel.tld>/<api_endpoint>/packages
 *
 * @package ServerProvisioning\Api
 */
class Package extends AbstractController
{
	/**
	 * Create package
	 *
	 * @param array $data Package data
	 * @return array Response
	 */
	public function create(array $data)
	{
		// TODO
	}

	/**
	 * Read package
	 *
	 * @param array $data Package data
	 * @return array Response
	 */
	public function read(array $data)
	{
		$response = array();

		if($this->imscpConfig['HOSTING_PLANS_LEVEL'] == 'admin') {
			$q = 'SELECT * FROM hosting_plans WHERE name = ?';
			$p = $data['package_name'];
		} else {
			$q = 'SELECT * FROM hosting_plans WHERE name = ? AND reseller_id = ?';
			$p = array($data['package_name'], $data['reseller_id']);
		}

		$stmt = exec_query($q, $p);

		if($stmt->rowCount()) {
			$package = $stmt->fetchRow(PDO::FETCH_ASSOC);
			$response['package_name'] = $package['name'];
			$response['description'] = $package['description'];
			$response['status'] = $package['status'];

			list(
				$response['php'],
				$response['cgi'],
				$response['subdomains'],
				$response['domain_aliases'],
				$response['mail_accounts'],
				$response['ftp_accounts'],
				$response['sql_databases'],
				$response['sql_users'],
				$response['monthly_traffic'],
				$response['disk_quota'],
				$response['backup'],
				$response['custom_dns_records'],
				$response['software_installer'],
				$response['php_editor'],
				$response['allow_url_fopen'],
				$response['php_display_errors'],
				$response['php_disable_functions'],
				$response['php_post_max_size'],
				$response['php_upload_max_filesize'],
				$response['php_max_execution_time'],
				$response['php_max_input_time'],
				$response['php_memory_limit'],
				$response['external_mail_server'],
				$response['web_folder_protection']
			) = explode(
				';', $package['props']
			);

			$response['code'] = 200;
		} else {
			$response['code'] =  404;
			$response['message'] =  'Package not found';
		}

		return $response;
	}

	/**
	 * Update package
	 *
	 * @param array $data Package data
	 * @return array Response
	 */
	public function update(array $data)
	{
		// TODO
	}

	/**
	 * Delete package
	 *
	 * @param array $data Package data
	 * @return array Response
	 */
	public function delete(array $data)
	{
		if($this->imscpConfig['HOSTING_PLANS_LEVEL'] != 'admin') {
			$stmt = exec_query(
				'DELETE FROM hosting_plans WHERE name = ? AND reseller_id = ?',
				array($data['package_name'], $this->identity->admin_id)
			);

			if($stmt->rowCount()) {
				$response = array('code' => '200', 'Package successfully deleted');
			} else {
				$response = array('code' => '404', 'Package not found');
			}
		} else {
			$response = array('code' => '403');
		}

		return $response;
	}

	/**
	 * Get package collection
	 *
	 * @param array $data Package data
	 * @return array Response
	 */
	public function collection(array $data)
	{
		$stmt = exec_query('SELECT * FROM hosting_plan WHERE reseller_id = ?', $this->identity->admin_id);

		if($stmt->rowCount()) {
			$packages = array();

			while($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
				$package = array();
				$package['package_name'] = $row['name'];
				$package['description'] = $row['description'];
				$package['status'] = $row['status'];

				list(
					$package['php'],
					$package['cgi'],
					$package['subdomains'],
					$package['domain_aliases'],
					$package['mail_accounts'],
					$package['ftp_accounts'],
					$package['sql_databases'],
					$package['sql_users'],
					$package['monthly_traffic'],
					$package['disk_quota'],
					$package['backup'],
					$package['custom_dns_records'],
					$package['software_installer'],
					$package['php_editor'],
					$package['allow_url_fopen'],
					$package['php_display_errors'],
					$package['php_disable_functions'],
					$package['php_post_max_size'],
					$package['php_upload_max_filesize'],
					$package['php_max_execution_time'],
					$package['php_max_input_time'],
					$package['php_memory_limit'],
					$package['external_mail_server'],
					$package['web_folder_protection']
				) = explode(
					';', $row['props']
				);

				$packages[] = $package;
			}

			$response = array('code' => '200', 'packages' => $packages);
		} else {
			$response = array('code' => '200', 'packages' => array());
		}

		return $response;
	}

	/**
	 * Return array describing payload requirements
	 *
	 * @param string $action Action
	 * @return array
	 */
	protected function getPayloadRequirements($action)
	{
		static $req = array(
			'create' => array(
				'package_name',
				'description',
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
				'status'
			),
			'read' => array('package_name'),
			'udpate' => array(
				'package_name',
				'description',
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
				'status'
			),
			'delete' => array('package_name')
		);

		return $req[$action];
	}
}
