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
 * Create:     POST   http://<panel.tld>/<api_endpoint>/package
 * Read:       GET    http://<panel.tld>/<api_endpoint>/package/<package_id>
 * Update:     PUT    http://<panel.tld>/<api_endpoint>/package/<package_id>
 * Delete:     DELETE http://<panel.tld>/<api_endpoint>/package/<package_id>
 * Collection: GET    http://<panel.tld>/<api_endpoint>/packages
 *
 * @package ServerProvisioning\Api
 */
class Package extends AbstractController
{
	/**
	 * Create package
	 *
	 * @param  array $data
	 */
	public function create(array $data)
	{
		// TODO
	}

	/**
	 * Read package
	 *
	 * @param  array $data
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
			list(
				$response['php'], $response['cgi'], $response['subdomains'], $response['domain_aliases'],
				$response['mail_accounts'], $response['ftp_accounts'], $response['sql_databases'],
				$response['sql_users'], $response['monthly_traffic'], $response['disk_quota'], $response['backup'],
				$response['custom_dns_records'], $response['software_installer'], $response['php_editor'],
				$response['allow_url_fopen'], $response['php_display_errors'], $response['php_disable_functions'],
				$response['php_post_max_size'], $response['php_upload_max_filesize'], $response['php_max_execution_time'],
				$response['php_max_input_time'], $response['php_memory_limit'], $response['external_mail_server'],
				$response['web_folder_protection']
			) = explode(
				';', $stmt->fetchRow(PDO::FETCH_ASSOC)
			);
		} else {
			$response['code'] =  400;
			$response['message'] =  'Package not found';
		}

		return $response;
	}

	/**
	 * Update package
	 *
	 * @param  array $data
	 */
	public function update(array $data)
	{
		// TODO
	}

	/**
	 * Delete package
	 *
	 * @param  array $data
	 */
	public function delete(array $data)
	{
		// TODO
	}

	/**
	 * Get package collection
	 *
	 * @param  array $data
	 */
	public function collection(array $data)
	{
		// TODO
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
			'read' => array('package_name'),
		);

		return $req[$apiFunction];
	}
}
