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
use ServerProvisioning\Controller\Package;

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
	 * @return array
	 */
	public function create(array $data)
	{
		if (!is_string($data['account_name']) || !isValidDomainName($data['account_name'])) {
			$response = array('code' => '422', 'message' => 'Wrong account name');
		} elseif (!is_string($data['password']) || checkPasswordSyntax($data['password'])) {
			$response = array('code' => '422', 'message' => 'Wrong password');
		} elseif (!is_string($data['email']) || !chk_email($data['email'])) {
			$response = array('code' => '422', 'message' => 'Wrong email');
		} elseif (!is_string($data['firstname'])) {
			$response = array('code' => '422', 'message' => 'Wrong firstname');
		} elseif (!is_string($data['lastname'])) {
			$response = array('code' => '422', 'message' => 'Wrong lastname');
		} elseif (!is_string($data['firm'])) {
			$response = array('code' => '422', 'message' => 'Wrong firm');
		} elseif (!is_string($data['zipcode'])) {
			$response = array('code' => '422', 'message' => 'Wrong zipcode');
		} elseif (!is_string($data['city'])) {
			$response = array('code' => '422', 'message' => 'Wrong city');
		} elseif (!is_string($data['state'])) {
			$response = array('code' => '422', 'message' => 'Wrong state');
		} elseif (!is_string($data['country'])) {
			$response = array('code' => '422', 'message' => 'Wrong country');
		} elseif (!is_string($data['phone'])) {
			$response = array('code' => '422', 'message' => 'Wrong phone');
		} elseif (!is_string($data['fax'])) {
			$response = array('code' => '422', 'message' => 'Wrong fax');
		} elseif (!is_string($data['customer_id'])) {
			$response = array('code' => '422', 'message' => 'Wrong customer ID');
		} elseif (!is_string($data['gender'])) {
			$response = array('code' => '422');
		} elseif (!is_string($data['domain_name']) || !isValidDomainName($data['username'])) {
			$response = array('code' => '422', 'message' => 'Wrong domain name');
		}

		if (!isset($response['code'])) {
			// Normalize account name
			$data['account_name'] = encode_idna($data['account_name']);
			// Normalize domain name
			$data['domain_name'] = encode_idna($data['domain_name']);

			if (!is_string($data['package_name'])) {
				$response = array('code' => '422', 'message' => 'Wrong package name');
			} elseif ($data['package_name'] !== '') {
				$package = new Package();
				$response = $package->read($data);

				if ($response['code'] == 200) {
					$data = array_merge($data, $response);
				}
			} else {
				if (!is_string($data['php'])) {
					$response = array('code' => '400');
				} elseif (!is_string($data['cgi'])) {
					$response = array('code' => '400', 'message' => 'Wrong request');
				} elseif (!is_string($data['subdomains'])) {
					$response = array('code' => '422', 'message' => 'Wrong subdomains limit');
				} elseif (!is_string($data['domain_aliases'])) {
					$response = array('code' => '422', 'message' => 'Wrong domain aliases limit');
				} elseif (!is_string($data['mail_accounts'])) {
					$response = array('code' => '422', 'message' => 'Wrong mail accounts limit');
				} elseif (!is_string($data['ftp_accounts'])) {
					$response = array('code' => '422', 'message' => 'Wrong ftp acocunts limit');
				} elseif (!is_string($data['sql_databases'])) {
					$response = array('code' => '422', 'message' => 'Wrong sql databases limit');
				} elseif (!is_string($data['sql_users'])) {
					$response = array('code' => '422', 'message' => 'Wrong sql users limit');
				} elseif (!is_string($data['monthly_traffic'])) {
					$response = array('code' => '422', 'message' => 'Wrong monthly traffic limit');
				} elseif (!is_string($data['disk_quota'])) {
					$response = array('code' => '422', 'message' => 'Wrong disk quota limit');
				} elseif (!is_string($data['backup']) || !in_array($data['backup'], array('dmn', 'sql', 'full', 'no'))) {
					$response = array('code' => '422');
				} elseif (
					!is_string($data['custom_dns_records']) || !in_array($data['custom_dns_records'], array('yes', 'no'))
				) {
					$response = array('code' => '400');
				} elseif (
					!is_string($data['software_installer']) || !in_array($data['software_installer'], array('yes', 'no'))
				) {
					$response = array('code' => '400');
				} elseif (!is_string($data['php_editor']) || !in_array($data['php_editor'], array('yes', 'no'))) {
					$response = array('code' => '400');
				} elseif (
					!is_string($data['external_mail_server']) ||
					!in_array($data['external_mail_server'], array('yes', 'no'))
				) {
					$response = array('code' => '400');
				} elseif (
					!is_string($data['web_folder_protection']) ||
					!in_array($data['web_folder_protection'], array('yes', 'no'))
				) {
					$response = array('code' => '400');
				}
			}
		}

		return (isset($response) && $response['code'] != '200') ? $response : parent::create($data);
	}

	/**
	 * Update account (change password only)
	 *
	 * # TODO allow to upgrade/downgrade package
	 *
	 * @param $data
	 * @return array
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
	 * Only the reseller of the given account is allowed to suspend it
	 *
	 * @param array $data
	 * @return array
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
	 * Only the reseller of the given account is allowed to unsuspend it
	 *
	 * @param array $data
	 * @return array
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
	 * Only the reseller of the given account is allowed to remove it
	 *
	 * @param array $data
	 * @return $response
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
