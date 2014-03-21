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
use ServerProvisioning\Domain\Sanity as Domain;

/**
 * Class Htuser
 *
 * @package ServerProvisioning\Controller
 */
class Htuser extends AbstractController
{
	/**
	 * Create Htuser
	 *
	 * @param $data Htuser data
	 * return array Response data
	 */
	public function create($data)
	{
		$htuser = $this->read($data);

		if($htuser['code'] == 404) {
			exec_query(
				'
					INSERT INTO htaccess_users(
						dmn_id, uname, upass, status
					) VALUE(
						?, ?, ?, ?
					)
				',
				array(
					$data['domain_id'],
					$data['htuser_name'],
					cryptPasswordWithSalt($data['htuser_password'], generateRandomSalt(true)),
					'toadd'
				)
			);

			$data = array(
				'code' => '201',
				'message' => 'Htuser successfully scheduled for creation',
				'htuser_name' => $data['htuser_name']
			);
		} else {
			$data = array(
				'code' => '409',
				'message' => 'Htuser already exists',
				'htuser_name' => $data['htuser_name']
			);
		}

		return $data;
	}

	/**
	 * Read Htuser
	 *
	 * @param $data Htuser data
	 * return array Response data
	 */
	public function read($data)
	{
		$stmt = exec_query('SELECT * FROM htaccess_users WHERE uname = ?', $data['htuser_name']);

		if($stmt->rowCount()) {
			$row = $stmt->fetchRow(PDO::FETCH_ASSOC);

			$data = array(
				'code' => '200',
				'htuser_id' => $row['id'],
				'domain_id' => $row['dmn_id'],
				'htuser_name' => $row['uname'],
				'htuser_status' => $row['status']
			);
		} else {
			$data = array(
				'code' => '404',
				'message' => 'Htuser not found'
			);
		}

		return $data;
	}

	/**
	 * Update Htuser
	 *
	 * @param $data Htuser data
	 * return array Response data
	 */
	public function update($data)
	{
		$htuser = $this->read($data);

		if($htuser['code'] == '200') {
			if($htuser['status'] == 'ok') {
				exec_query(
					'UPDATE htaccess_users SET upass = ?, SET status = ? WHERE uname = ?',
					array(
						$htuser['htuser_name'],
						cryptPasswordWithSalt($data['htuser_password'], generateRandomSalt(true)),
						'tochange'
					)
				);

				$this->sendRequest = true;

				$data = array(
					'code' => '200',
					'message' => 'Htuser successfully scheduled for update',
					'htuser_name' => $htuser['htuser_name']
				);
			} else {
				$data = array(
					'code' => '422',
					'Htuser status is not ok',
					'htuser_name' => $htuser['htuser_name'],
					'htuser_status' => $htuser['htuser_status']
				);
			}
		} else {
			$data = $htuser;
		}

		return $data;
	}

	/**
	 * Delete Htuser
	 *
	 * @param $data Htuser data
	 * return array Response data
	 */
	public function delete($data)
	{
		$htuser = $this->read($data);

		if($htuser['code'] == 200) {
			if($htuser['htuser_status'] == 'ok') {
				exec_query(
					'UPDATE htaccess_users SET status = ? WHERE uname = ?',
					array(
						'todelete',
						$htuser['htuser_name']
					)
				);

				$data = array(
					'code' => '200',
					'message' => 'Htuser successfully scheduled for deletion',
				);
			} else {
				$data = array(
					'code' => '422',
					'message' => 'Htuser status is not ok',
					'htuser_name' => $htuser['htuser_name'],
					'htuser_status' => $htuser['htuser_status']
				);
			}
		} else {
			$data = $htuser;
		}

		return $data;
	}

	/**
	 * Get Htuser colletion
	 *
	 * @param array $data Htuser data
	 * @return array Response data
	 */
	public function collection($data)
	{
		$domain = new Domain();
		$domain = $domain->read($data);

		if($domain['code'] == '200') {
			$stmt = exec_query('SELECT id, uname, status FROM htaccess_users WHERE dmn_id = ?', $domain['domain_id']);

			$htusers = array();

			if($stmt->rowCount()) {
				while($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
					$htusers[] = array(
						'htuser_id' => $row['id'],
						'domain_id' => $domain['domain_id'],
						'htuser_name' => $row['uname'],
						'htuser_status' => $row['status']
					);
				}
			}

			$data = array(
				'code' => '200',
				'htusers' => $htusers
			);
		} else {
			$data = $domain;
		}

		return $data;
	}

	/**
	 * Return array describing payload requirements
	 *
	 * @param string $action Action
	 * @return array Response data
	 */
	protected function getPayloadRequirements($action)
	{
		$req = array(
			'create' => array(
				'htuser_name',
				'htuser_password'
			),
			'read' => array(
				'htuser_name'
			),
			'update' => array(
				'htuser_name',
				'htuser_password'
			),
			'delete' => array(
				'htuser_name'
			),
			'collection' => array(
				'domain_id'
			)
		);

		return $req[$action];
	}
}
