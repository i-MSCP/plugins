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

namespace ServerProvisioning\Controller\User;

use ServerProvisioning\Controller\User;

/**
 * Class Sanity
 *
 * @package ServerProvisioning\Controller\User
 */
class Sanity extends User
{
	/**
	 * Create user
	 *
	 * @param array $data User data
	 * @return array Response
	 */
	public function create(array $data)
	{
		$errors = $this->checkData($data);

		return ($errors)
			? array('code' => '422', 'message' => 'Validation Failed', 'errors' => $errors) : parent::create($data);
	}


	/**
	 * Update user
	 *
	 * @param array $data User data
	 * @return array Response
	 */
	public function update(array $data)
	{
		$errors = $this->checkData($data);

		return ($errors)
			? array('code' => '422', 'message' => 'Validation Failed', 'errors' => $errors) : parent::update($data);
	}

	/**
	 * Create user
	 *
	 * @param array $data User data
	 * @return array Array which contain errors descriptions if any
	 */
	protected function checkData(array $data)
	{
		$errors = array();

		if (!is_string($data['username']) || !validates_username($data['username'])) {
			$errors[] = array("resource" => 'User', "field" => 'username', "code" => "invalid");
		}

		if (!is_string($data['password']) || checkPasswordSyntax($data['password'])) {
			$errors[] = array("resource" => 'User', "field" => 'password', "code" => "invalid");
		}

		if (!is_string($data['email']) || !chk_email($data['email'])) {
			$errors[] = array("resource" => 'User', "field" => 'email', "code" => "invalid");
		}

		if (isset($data['firstname']) && !is_string($data['firstname'])) {
			$errors[] = array("resource" => 'User', "field" => 'firstname', "code" => "invalid");
		} else {
			$data['firstname'] = null;
		}

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

		return $errors;
	}
}
