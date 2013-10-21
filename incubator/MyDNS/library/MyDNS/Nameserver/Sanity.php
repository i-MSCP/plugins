<?php
/**
 * i-MSCP MyDNS Plugin
 * Copyright (C) 2010-2013 by Laurent Declercq
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @category    iMSCP
 * @package     MyDNS\Nameserver
 * @copyright   2010-2013 by Laurent Declercq
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

namespace MyDNS\Nameserver;

use MyDNS\Nameserver;

/**
 * Class Sanity
 *
 * Validate nameserver payload.
 *
 * @package MyDNS\Nameserver
 */
class Sanity extends Nameserver
{
	/**
	 * Create nameserver
	 *
	 * @param array $data
	 * @return array
	 */
	public function create(array $data)
	{
		// Check for TTL
		if (!isset($data['ttl'])) {
			$data['ttl'] = '86400'; // TODO get default value from config file
		}

		$this->validateTTL($data['ttl']);

		// Check for name characters
		if (preg_match('/([^a-zA-Z0-9\-\.])/', $data['name'], $m)) {
			$this->errors['name'] = 1;
			$this->errorMessages[] = tr(
				"Nameserver name contains an invalid character - '%s'. Only A-Z, 0-9, . and - are allowed.", $m[1]
			);
		}

		// Check that name is absolute
		if (!preg_match('/\.$/', $data['name'])) {
			$this->setError(
				'name',
				tr('Nameserver name must be a fully-qualified domain name with a dot at the end, such as ns1.example.com.')
			);
		}

		// Check that parts of the name are valid
		$parts = preg_split('/\./', $data['name']);

		foreach ($parts as $part) {
			if (!preg_match('/[a-zA-Z0-9\-]+/', $part)) {
				$this->setError('name', tr('Nameserver name must be a valid host.'));
			} elseif (preg_match('/^[\-]/', $part)) {
				$this->setError('name', tr('Parts of a nameserver name cannot start with a dash.'));
			}
		}

		# Check that export_format is valid (Only bind export format is currently provided)
		if ($data['export_format'] !== 'bind') {
			$this->setError('export_format', tr('Invalid export format.'));
		}

		// Check for IP address
		if (!filter_var($data['address'], FILTER_VALIDATE_IP)) {
			$this->setError('address', tr('Invalid IP address - %s', $data['address']));
		}

		return (!empty($this->errors)) ? $this->throwSanityError() : parent::create($data);
	}

	/**
	 * Update nameserver
	 *
	 * @param array $data
	 * @return array
	 */
	public function update(array $data)
	{
		$nameserver = $this->read($data);

		if ($this->isErrorResponse($nameserver)) {
			return $nameserver;
		}

		// Check for TTL
		if (!isset($data['ttl'])) {
			$data['ttl'] = '86400'; // TODO get default value from config file
		}

		if (isset($data['name'])) {
			// Check for name characters
			if (preg_match('/([^a-zA-Z0-9\-\.])/', $data['name'], $m)) {
				$this->errors['name'] = 1;
				$this->errorMessages[] = tr(
					"Nameserver name contains an invalid character - '%s'. Only A-Z, 0-9, . and - are allowed.", $m[1]
				);
			}

			// Check that name is absolute
			if (!preg_match('/\.$/', $data['name'])) {
				$this->setError(
					'name',
					tr('Nameserver name must be a fully-qualified domain name with a dot at the end, such as ns1.example.com.')
				);
			}

			// Check that parts of the name are valid
			$parts = preg_split('/\./', $data['name']);

			foreach ($parts as $part) {
				if (!preg_match('/[a-zA-Z0-9\-]+/', $part)) {
					$this->setError('name', tr('Nameserver name must be a valid host.'));
				} elseif (preg_match('/^[\-]/', $part)) {
					$this->setError('name', tr('Parts of a nameserver name cannot start with a dash.'));
				}
			}
		}

		# check that export_format is valid (Only bind export format is currently provided)
		if (isset($data['export_format']) && $data['export_format'] !== 'bind') {
			$this->setError('export_format', tr('Invalid export format.'));
		}

		if (isset($data['address'])) {
			// Check for IP address
			if (!filter_var($data['address'], FILTER_VALIDATE_IP)) {
				$this->setError('address', tr('Invalid IP address - %s', $data['address']));
			}
		}

		return (!empty($this->errors)) ? $this->throwSanityError() : parent::update($data);
	}
}
