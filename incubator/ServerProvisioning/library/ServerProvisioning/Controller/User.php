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
use iMSCP_Events_Manager as EventsManager;
use iMSCP_Exception;
use PDO;

/**
 * Class User
 *
 * @package ServerProvisioning\Controller
 */
class User extends AbstractController
{
	/**
	 * Create user
	 *
	 * @param array $data User data
	 * @return array Response
	 */
	public function create(array $data)
	{
		EventsManager::getInstance()->dispatch(Events::onBeforeAddUser);

		$password = cryptPasswordWithSalt($data['password']);

		try {
			$this->db->beginTransaction();

			exec_query(
				'
					INSERT INTO admin (
						admin_name, admin_pass, admin_type, domain_created, created_by, fname, lname, firm, zip, city,
						state, country, email, phone, fax, street1, street2, customer_id, gender, admin_status
					) VALUES (
						?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
					)
				',
				$data['username'], $password, $data['user_type'], time(), $this->identity->admin_id, $data['firstname'],
				$data['lastname'], $data['firm'], $data['zipcode'], $data['city'], $data['state'], $data['country'],
				$data['email'], $data['phone'], $data['fax'], $data['street_1'], $data['street_2'], $data['customer_id'],
				$data['gender'], 'toadd'
			);

			$userId = $this->db->insertId();

			exec_query(
				'REPLACE INTO user_gui_props (user_id, lang, layout) VALUES (?, ?, ?)',
				array($userId, $this->imscpConfig['USER_INITIAL_LANG'], $this->imscpConfig['USER_INITIAL_THEME'])
			);

			$this->db->commit();

			EventsManager::getInstance()->dispatch(Events::onAfterAddUser);

			if ($data['send_welcome_mail']) {
				send_add_user_auto_msg(
					$this->identity->admin_id, $data['username'], $password, $data['email'], $data['firstname'],
					$data['lastname'], tr('Administrator', true)
				);
			}

			write_log("{$_SESSION['user_logged']} created new user: {$data['username']}", E_USER_NOTICE);

			return array(
				'code' => 201,
				'message' => 'User successfully created',
				'user_id' => $userId
			);
		} catch (iMSCP_Exception $e) {
			$this->db->rollBack();

			if($e->getCode() == '23000') {
				return array('code' => '409', 'Account already exist');
			} else {
				throw $e;
			}
		}
	}

	/**
	 * Read user
	 *
	 * @param array $data Account data
	 * @return array Response
	 */
	public function read(array $data)
	{
		$stmt = exec_query('SELECT * FROM admin WHERE admin_name = ?', $data['username']);

		if ($stmt->rowCount()) {
			$row = $stmt->fetchRow(PDO::FETCH_ASSOC);

			$response = array(
				'code' => 200,
				'user_id' => $row['admin_id'],
				'username' => $row['admin_name'],
				'password' => $row['admin_pass'],
				'email' => $row['email'],
				'firstname' => $row['fname'],
				'lastname' => $row['lname'],
				'firm' => $row['firm'],
				'zipcode' => $row['zipcode'],
				'city' => $row['city'],
				'state' => $row['state'],
				'country' => $row['country'],
				'phone' => $row['phone'],
				'fax' => $row['fax'],
				'street1' => $row['street1'],
				'street2' => $row['street2'],
				'gender' => $row['gender'],
				'user_type' => $row['admin_type']
			);
		} else {
			$response = array(
				'code' => '404',
				'User not found'
			);
		}

		return $response;
	}

	/**
	 * Update user
	 *
	 * @param array $data Account data
	 * @return array Response
	 */
	public function update(array $data)
	{
		$response = $this->read($data);

		if ($response['code'] == '200') {
			if(isset($data['password'])) {
				$data['password'] = cryptPasswordWithSalt($data['password']);
			}

			$data = array_merge($response, $data); // This should allow PATCH request

			EventsManager::getInstance()->dispatch(Events::onBeforeEditUser, array('userId' => $data['user_id']));

			exec_query(
				'
					UDPATE
						admin
					SET
						admin_pass = ?, fname = ?, lname = ?, firm = ?, zip = ?, city = ?, state = ?, country = ?,
						email = ?, phone = ?, fax = ?, street1 = ?, street2 = ?, customer_id= ?, gender= ?
					WHERE
						admin_id = ?
				',
				$data['username'], $data['password'], $data['lastname'], $data['firm'], $data['zipcode'], $data['city'],
				$data['state'], $data['country'], $data['email'], $data['phone'], $data['fax'], $data['street_1'],
				$data['street_2'], $data['customer_id'], $data['gender'], $data['user_id']
			);

			EventsManager::getInstance()->dispatch(Events::onAfterEditUser, array('userId' => $data['user_id']));

			$response = array(
				'code' => '200',
				'message' => 'User successfully updated',
				'user_id' => $data['user_id']
			);
		}

		return $response;
	}

	/**
	 * Delete user
	 *
	 * @param array $data User data
	 * @return array Response
	 */
	public function delete(array $data)
	{
		$response = $this->read($data);

		if ($response['code'] == '200') {
			if ($response['user_id']) {
				if ($response['user_type'] != 'user') {
					$q = 'DELETE FROM admin WHERE admin_id = ?';
				} else {
					$q = "UPDATE admin SET status = 'todelete' WHERE admin_id = ?";
				}

				EventsManager::getInstance()->dispatch(Events::onBeforeDeleteUser, array('userId' => $data['user_id']));

				try {

					exec_query($q, $data['user_id']);
					exec_query('DELETE FROM user_gui_props WHERE user_id = ?');

					EventsManager::getInstance()->dispatch(Events::onAfterDeleteUser, array('userId' => $data['user_id']));

					$response = array(
						'code' => '200',
						'message' => 'User successfully deleted'
					);
				} catch(Exception $e) {
					$this->db->rollBack();
				}
			} else {
				$response = array(
					'code' => '403',
					'message' => 'You cannot delete the super admin user'
				);
			}
		}

		return $response;
	}

	/**
	 * Return array describing payload requirements
	 *
	 * @param string $action Action
	 * @return array Response
	 */
	protected function getPayloadRequirements($action)
	{
		static $req = array(
			'create' => array(
				'username',
				'password',
				'email',
				'user_type'
			),
			'read' => array(
				'username'
			),
			'update' => array(
				'username',
				'password',
				'email',
				'user_type'
			),
			'suspsend' => array(
				'username'
			),
			'unsuspend' => array(
				'username'
			),
			'delete' => array('username'
			)
		);

		return $req[$action];
	}
}
