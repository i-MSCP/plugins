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

namespace ServerProvisioning\Controller\Account;

use ServerProvisioning\Controller\Account;

/**
 * Class Sanity
 *
 * @package ServerProvisioning\Account
 */
class Sanity extends Account
{
	/**
	 * Create new account
	 *
	 * @param array $data Account data
	 * @return array Response
	 */
	public function create(array $data)
	{





		/*
		$errors = array();

		// Required data

		if (!is_string($data['username']) || !isValidDomainName($data['username'])) {
			$errors[] = array("resource" => 'User', "field" => 'username', "code" => "invalid");
		}

		if (!is_string($data['password']) || checkPasswordSyntax($data['password'])) {
			$errors[] = array("resource" => 'User', "field" => 'username', "code" => "invalid");
		}

		if (!is_string($data['email']) || !chk_email($data['email'])) {
			$errors[] = array("resource" => 'User', "field" => 'username', "code" => "invalid");
		}

		if (isset($data['firstname']) && !is_string($data['firstname'])) {
			$errors[] = array("resource" => 'User', "field" => 'firstname', "code" => "invalid");
		} else {
			$data['firstname'] = null;
		}

		// Optional data

		if (isset($data['lastname']) && !is_string($data['lastname'])) {
			$errors[] = array("resource" => 'User', "field" => 'lastname', "code" => "invalid"
			);
		} else {
			$data['lastname'] = null;
		}

		if (isset($data['firm']) && !is_string($data['firm'])) {
			$errors[] = array("resource" => 'User', "field" => 'firm', "code" => "invalid");
		} else {
			$data['firm'] = null;
		}

		if (isset($data['zipcode']) && !is_string($data['zipcode'])) {
			$errors[] = array("resource" => 'User', "field" => 'zipcode', "code" => "invalid");
		} else {
			$data['zipcode'] = null;
		}

		if (isset($data['city']) && !is_string($data['city'])) {
			$errors[] = array("resource" => 'User', "field" => 'city', "code" => "invalid");
		} else {
			$data['city'] = null;
		}

		if (isset($data['state']) && !is_string($data['state'])) {
			$errors[] = array("resource" => 'User', "field" => 'state', "code" => "invalid");
		} else {
			$data['state'] = null;
		}

		if (isset($data['country']) && !is_string($data['country'])) {
			$errors[] = array("resource" => 'User', "field" => 'country', "code" => "invalid");
		} else {
			$data['country'] = null;
		}

		if (isset($data['phone']) && !is_string($data['phone'])) {
			$errors[] = array("resource" => 'User', "field" => 'phone', "code" => "invalid");
		} else {
			$data['phone'] = null;
		}

		if (isset($data['fax']) && !is_string($data['fax'])) {
			$errors[] = array("resource" => 'User', "field" => 'fax', "code" => "invalid");
		} else {
			$data['fax'] = null;
		}

		if (isset($data['gender']) && !is_string($data['gender'])) {
			$errors[] = array("resource" => 'User',"field" => 'gender', "code" => "invalid");
		} else {
			$data['gender'] = null;
		}

		if (!is_string($data['domain_name']) || !isValidDomainName($data['username'])) {
			$errors[] = array("resource" => 'User', "field" => 'username', "code" => "invalid");
		}

		// Normalize account name
		$data['account_name'] = encode_idna($data['account_name']);

		// Normalize domain name
		$data['domain_name'] = encode_idna($data['domain_name']);


		if (!isset($response['code'])) {
			if (!is_string($data['package_name'])) {
				$errors[] = array("resource" => 'User', "field" => 'username', "code" => "invalid");
			} elseif ($data['package_name'] !== '') {
				$package = new Package();
				$response = $package->read($data);

				if ($response['code'] == 200) {
					if ($response['status'] == '1') {
						$data = array_merge($data, $response);
					} else {
						$response = array(
							'code' => '403', 'message' => 'Package exists but is not available for purchasing'
						);
					}
				}
			} else {
				if (!is_string($data['php'])) {
					$errors[] = array("resource" => 'User', "field" => 'username', "code" => "invalid");
				}

				if (!is_string($data['cgi'])) {
					$errors[] = array("resource" => 'User', "field" => 'username', "code" => "invalid");
				}

				if (!is_string($data['subdomains'])) {
					$errors[] = array("resource" => 'User', "field" => 'username', "code" => "invalid");
				}

				if (!is_string($data['domain_aliases'])) {
					$errors[] = array("resource" => 'User', "field" => 'username', "code" => "invalid");
				}

				if (!is_string($data['mail_accounts'])) {
					$errors[] = array("resource" => 'User', "field" => 'username', "code" => "invalid");
				}

				if (!is_string($data['ftp_accounts'])) {
					$errors[] = array("resource" => 'User', "field" => 'username', "code" => "invalid");
				}

				if (!is_string($data['sql_databases'])) {
					$errors[] = array("resource" => 'User', "field" => 'username', "code" => "invalid");
				}

				if (!is_string($data['sql_users'])) {
					$errors[] = array("resource" => 'User', "field" => 'username', "code" => "invalid");
				}

				if (!is_string($data['monthly_traffic'])) {
					$errors[] = array("resource" => 'User', "field" => 'username', "code" => "invalid");
				}

				if (!is_string($data['disk_quota'])) {
					$errors[] = array("resource" => 'User', "field" => 'username', "code" => "invalid");
				}

				if (!is_string($data['backup']) || !in_array($data['backup'], array('dmn', 'sql', 'full', 'no'))) {
					$errors[] = array("resource" => 'User', "field" => 'username', "code" => "invalid");
				}

				if (!is_string($data['custom_dns_records']) || !in_array($data['custom_dns_records'], array('yes', 'no'))) {
					$errors[] = array("resource" => 'User', "field" => 'username', "code" => "invalid");
				}

				if (!is_string($data['software_installer']) || !in_array($data['software_installer'], array('yes', 'no'))) {
					$errors[] = array("resource" => 'User', "field" => 'username', "code" => "invalid");
				}

				if (!is_string($data['php_editor']) || !in_array($data['php_editor'], array('yes', 'no'))) {
					$errors[] = array("resource" => 'User', "field" => 'username', "code" => "invalid");
				}

				if (!is_string($data['external_mail_server']) || !in_array($data['external_mail_server'], array('yes', 'no'))) {
					$errors[] = array("resource" => 'User', "field" => 'username', "code" => "invalid");
				}

				if (!is_string($data['web_folder_protection']) || !in_array($data['web_folder_protection'], array('yes', 'no'))) {
					$errors[] = array("resource" => 'User', "field" => 'username', "code" => "invalid");
				}
			}
		}
		*/


		return ($errors)
			? array('code' => '422', 'message' => 'Validation Failed', 'errors' => $errors) : parent::create($data);
	}

	/**
	 * Read account
	 *
	 * @param array $data Account data
	 * @return array Response
	 */
	public function read(array $data)
	{
		if (!is_string($data['account_name']) || !isValidDomainName($data['account_name'])) {
			$response = array('code' => '422', 'message' => 'Wrong account name');
		} else {
			// Normalize account name
			$data['account_name'] = encode_idna($data['account_name']);
		}

		return (isset($response)) ? $response : parent::suspend($data);
	}

	/**
	 * Update account
	 *
	 * @param array $data Account data
	 * @return array Response
	 */
	public function update($data)
	{
		if (!is_string($data['account_name']) || !isValidDomainName($data['account_name'])) {
			$response = array('code' => '422', 'message' => 'Wrong account name');
		} else if (!is_string($data['password']) || !checkPasswordSyntax($data['password'])) {
			$response = array('code' => '422', 'message' => 'Wrong password');
		} else {
			// Normalize account name
			$data['account_name'] = encode_idna($data['account_name']);
		}

		return (isset($response)) ? $response : parent::update($data);
	}

	/**
	 * Suspend account
	 *
	 * @param array $data Account data
	 * @return array Response
	 */
	public function suspend(array $data)
	{
		if (!is_string($data['account_name']) || !isValidDomainName($data['account_name'])) {
			$response = array('code' => '422', 'message' => 'Wrong account name');
		} else {
			// Normalize account name
			$data['account_name'] = encode_idna($data['account_name']);
		}

		return (isset($response)) ? $response : parent::suspend($data);
	}

	/**
	 * Unsuspend account
	 *
	 * @param array $data Account data
	 * @return array Response
	 */
	public function unsuspend(array $data)
	{
		if (!is_string($data['account_name']) || !isValidDomainName($data['account_name'])) {
			$response = array('code' => '422', 'message' => 'Wrong account name');
		} else {
			// Normalize account name
			$data['account_name'] = encode_idna($data['account_name']);
		}

		return (isset($response)) ? $response : parent::unsuspend($data);
	}

	/**
	 * Delete account
	 *
	 * @param array $data Account data
	 * @return array Response
	 */
	public function delete(array $data)
	{
		if (!is_string($data['account_name']) || !isValidDomainName($data['account_name'])) {
			$response = array('code' => '422', 'message' => 'Wrong account name');
		} else {
			// Normalize account name
			$data['account_name'] = encode_idna($data['account_name']);
		}

		return (isset($response)) ? $response : parent::delete($data);
	}
}
