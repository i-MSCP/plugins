<?php

namespace MyDNS\Nameserver;

use MyDNS\Nameserver;

/**
 * Class Sanity
 *
 * Validate that nameservers have required params, no invalid chars, etc.
 *
 * @package MyDNS\Nameserver
 */
class Sanity extends Nameserver
{
	/**
	 * Create nameserver
	 *
	 * @param array $params
	 * @return array|void
	 */
	public function create($params)
	{
		// Check for TTL
		if (!isset($this->request['ttl'])) $this->request['ttl'] = '86400';

		$this->validateTTL($this->request['ttl']);

		// Check for name characters
		if (preg_match('/([^a-zA-Z0-9\-\.])/', $this->request['name'], $m)) {
			$this->errors['name'] = 1;
			$this->errorMessages[] = tr(
				"Nameserver name contains an invalid character - '%s'. Only A-Z, 0-9, . and - are allowed.", $m[1]
			);
		}

		// Check that name is absolute
		if (!preg_match('/\.$/', $this->request['name'])) {
			$this->setError(
				'name',
				tr('Nameserver name must be a fully-qualified domain name with a dot at the end, such as ns1.example.com. (notice the dot after .com...)')
			);
		}

		// Check that parts of the name are valid
		$parts = preg_split('/\./', $this->request['name']);

		foreach ($parts as $part) {
			if (!preg_match('/[a-zA-Z0-9\-]+/', $part)) {
				$this->setError('name', tr('Nameserver name must be a valid host.'));
			} elseif (preg_match('/^[\-]/', $part)) {
				$this->setError('name', tr('Parts of a nameserver name cannot start with a dash.'));
			}
		}

		# Check that export_format is valid ((Only bind export format is currently provided)
		if ( $this->request['export_format'] !== 'bind') { # Only bind export format is currently provided
			$this->setError('export_format', tr('Invalid export format.'));
		}

		// Check for IP address
		if (!filter_var($this->request['address'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			$this->setError('address', tr('Invalid IP address - %s', $this->request['address']));
		}

		return (!empty($this->errors)) ? $this->throwSanityError() : parent::update($params);
	}

	/**
	 * Update nameserver
	 *
	 * @param array $params
	 * @return array
	 */
	public function update($params)
	{
		$nameserver = $this->read($params);

		if ($this->isErrorResponse($nameserver)) {
			return $nameserver;
		}

		// Check for TTL
		if (!isset($this->request['ttl'])) $this->request['ttl'] = '86400';

		if(isset($this->request['name'])) {
			// Check for name characters
			if (preg_match('/([^a-zA-Z0-9\-\.])/', $this->request['name'], $m)) {
				$this->errors['name'] = 1;
				$this->errorMessages[] = tr(
					"Nameserver name contains an invalid character - '%s'. Only A-Z, 0-9, . and - are allowed.", $m[1]
				);
			}

			// Check that name is absolute
			if (!preg_match('/\.$/', $this->request['name'])) {
				$this->setError(
					'name',
					tr('Nameserver name must be a fully-qualified domain name with a dot at the end, such as ns1.example.com. (notice the dot after .com...)')
				);
			}

			// Check that parts of the name are valid
			$parts = preg_split('/\./', $this->request['name']);

			foreach ($parts as $part) {
				if (!preg_match('/[a-zA-Z0-9\-]+/', $part)) {
					$this->setError('name', tr('Nameserver name must be a valid host.'));
				} elseif (preg_match('/^[\-]/', $part)) {
					$this->setError('name', tr('Parts of a nameserver name cannot start with a dash.'));
				}
			}
		}

		# check that export_format is valid (Only bind export format is currently provided)
		if (isset($this->request['export_format']) && $this->request['export_format'] !== 'bind') {
			$this->setError('export_format', tr('Invalid export format.'));
		}

		if(isset($this->request['address'])) {
			// Check for IP address
			if (!filter_var($this->request['address'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
				$this->setError('address', tr('Invalid IP address - %s', $this->request['address']));
			}
		}

		return (!empty($this->errors)) ? $this->throwSanityError() : parent::update($params);
	}
}
