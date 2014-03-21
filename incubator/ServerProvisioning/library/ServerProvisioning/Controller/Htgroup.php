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
 * Class Htgroup
 *
 * @package ServerProvisioning\Controller
 */
class Htgroup extends AbstractController
{
	/**
	 * Create Htgroup
	 *
	 * @param $data Htgroup data
	 * return array Response data
	 */
	public function create($data)
	{
		$htgroup = $this->read($data);

		if($htgroup['code'] == 404) {
			exec_query(
				'
					INSERT INTO htaccess_group(
						dmn_id, ugroup, members, status
					) VALUE(
						?, ?, ?, ?
					)
				',
				array(
					$data['domain_id'],
					$data['htgroup_name'],
					$data['htgroup_members'],
					'toadd'
				)
			);

			$data = array(
				'code' => '201',
				'message' => 'Htgroup successfully scheduled for creation',
				'htgroup_name' => $data['htgroup_name']
			);
		} else {
			$data = array(
				'code' => '409',
				'message' => 'Htgroup already exists',
				'htgroup_name' => $data['htgroup_name']
			);
		}

		return $data;
	}

	/**
	 * Read Htgroup
	 *
	 * @param $data Htgroup data
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
	 * Update Htgroup
	 *
	 * @param $data Htgroup data
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
	 * Delete Htgroup
	 *
	 * @param $data Htgroup data
	 * return array Response data
	 */
	public function delete($data)
	{
		$htgroup = $this->read($data);

		if($htgroup['code'] == 200) {
			if($htgroup['htgroup_status'] == 'ok') {
				exec_query(
					'UPDATE htaccess_group SET status = ? WHERE ugroup = ?',
					array(
						'todelete',
						$htgroup['htgroup_name']
					)
				);

				$data = array(
					'code' => '200',
					'message' => 'Htuser successfully scheduled for deletion',
				);
			} else {
				$data = array(
					'code' => '422',
					'message' => 'Htgroup status is not ok',
					'htgroup_name' => $htgroup['htgroup_name'],
					'htgroup_status' => $htgroup['htgroup_status']
				);
			}
		} else {
			$data = $htgroup;
		}

		return $data;
	}

	/**
	 * Get Htgroup colletion
	 *
	 * @param array $data Htgroup data
	 * @return array Response data
	 */
	public function collection($data)
	{
		$domain = new Domain();
		$domain = $domain->read($data);

		if($domain['code'] == '200') {
			$stmt = exec_query('SELECT id, ugroup, status FROM htaccess_groups WHERE dmn_id = ?', $domain['domain_id']);

			$htgroups = array();

			if($stmt->rowCount()) {
				while($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
					$htgroups[] = array(
						'htgroup_id' => $row['id'],
						'domain_id' => $domain['domain_id'],
						'htgroup_name' => $row['ugroup'],
						'htgroup_members' => $row['members'],
						'htgroup_status' => $row['status'],
					);
				}
			}

			$data = array(
				'code' => '200',
				'htgroup' => $htgroups
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
	 * @return array Requirements
	 */
	protected function getPayloadRequirements($action)
	{
		$req = array(
			'create' => array(
				'htgroup_name',
				'htgroup_members'
			),
			'read' => array(
				'htgroup_name'
			),
			'update' => array(
				'htgroup_name',
				'htgroup_members'
			),
			'delete' => array(
				'htgroup_name'
			),
			'collection' => array(
				'domain_id'
			)
		);

		return $req[$action];
	}
}
