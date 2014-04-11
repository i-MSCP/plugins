<?php
/**
 * i-MSCP InstantSSH plugin
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
 */

namespace InstantSSH\Validate;

use Zend_Config;
use Zend_Validate_Abstract as ValidateAbstract;

/**
 * Class KeyOptions
 *
 * Class allowing to validate SSH authentication options (see man authorized_keys).
 *
 * @package InstantSSH\Validate
 */
class SshAuthOptions extends ValidateAbstract
{
	const ALL = 'All';
	const CERT_AUTHORITY = 'cert-authority';
	const COMMAND = 'command';
	const ENVIRONMENT = 'environment';
	const FROM = 'from';
	const NO_AGENT_FORWARDING = 'no-agent-forwarding';
	const NO_PORT_FORWARDING = 'no-port-forwarding';
	const NO_PTY = 'no-pty';
	const NO_USER_RC = 'no-user-rc';
	const NO_X11_FORWARDING = 'no-X11-forwarding';
	const PERMITOPEN = 'permitopen';
	const PRINCIPALS = 'principals';
	const TUNNEL = 'tunnel';

	const ALREADY_DEFINED = 'authOptionAlreadyDefined';
	const DISABLED = 'authOptionDisabled';
	const INVALID = 'authOptionInvalid';
	const INVALID_VALUE = 'authOptionInvalidValue';
	const MISSING_VALUE = 'authOptionMissingValue';
	const MISSING_END_QUOTE = 'authOptionMissinEndQuote';
	const UNKNOWN_MALFORMED = 'authOptionUnknown';

	/**
	 * @var array Boolean options
	 */
	protected $_booleanOptions = array(
		self::CERT_AUTHORITY,
		self::NO_AGENT_FORWARDING,
		self::NO_PORT_FORWARDING,
		self::NO_PTY,
		self::NO_USER_RC,
		self::NO_X11_FORWARDING,
		self::PRINCIPALS
	);

	/**
	 * @var array value options
	 */
	protected $_valueOptions = array(
		self::COMMAND,
		self::ENVIRONMENT,
		self::FROM,
		self::PERMITOPEN,
		self::PRINCIPALS,
		self::TUNNEL
	);

	/**
	 * @var array Validation failure message template definitions
	 */
	protected $_messageTemplates = array(
		self::ALREADY_DEFINED => "The '%value%' authentication option cannot be defined multiple times",
		self::DISABLED => "The '%value%' authentication option is valid but has been disabled on this system",
		self::INVALID => "Invalid authentication option given. String expected",
		self::INVALID_VALUE => "The '%value%' authentication option value is invalid",
		self::MISSING_VALUE => "Value is missing for the '%value%' authentication option",
		self::MISSING_END_QUOTE => "Missing end quote for the '%value%' authentication option",
		self::UNKNOWN_MALFORMED => "Unknown or malformed authentication option has been detected"
	);

	/**
	 * Options which are accepted by validation
	 *
	 * @var array
	 */
	protected $_options = array();

	/**
	 * Constructor
	 *
	 * @param array|string|Zend_Config $options
	 * @return void
	 */
	public function __construct($options = array())
	{
		if ($options instanceof Zend_Config) {
			$options = $options->toArray();
		} else if (!is_array($options)) {
			$options = func_get_args();
			$temp['auth_option'] = array_shift($options);
			$options = $temp;
		}

		if (!array_key_exists('auth_option', $options)) {
			$options['auth_option'] = self::ALL;
		}

		$this->setOption($options['auth_option']);
	}

	/**
	 * Returns a list of accepted key options
	 *
	 * @return array
	 */
	public function getOptions()
	{
		return $this->_options;
	}

	/**
	 * Set options which are accepted by validation
	 *
	 * @param string|array $option Option to allow for validation
	 * @return SshAuthOptions Provides a fluid interface
	 */
	public function setOption($option)
	{
		$this->_options = array();

		return $this->addOption($option);
	}

	/**
	 * Adds an option to be accepted by validation
	 *
	 * @param string|array $option Option to allow for validation
	 * @return SshAuthOptions Provides a fluid interface
	 */
	public function addOption($option)
	{
		if (is_string($option)) {
			$option = array($option);
		}

		foreach ($option as $opt) {
			if (defined('self::' . strtoupper(str_replace('-', '_', $opt))) && !in_array($opt, $this->_options)) {
				if ($opt == self::ALL) {
					$this->_options = array_merge($this->_booleanOptions, $this->_valueOptions);
					break;
				} else {
					$this->_options[] = $opt;
				}
			}
		}

		return $this;
	}

	/**
	 * Returns true if and only if $value meets the validation requirements
	 *
	 * If $value fails validation, then this method returns false, and getMessages() will return an array of messages
	 * that explain why the validation failed.
	 *
	 * @param string $options
	 * @return boolean
	 */
	public function isValid($value)
	{
		$this->_setValue($value);

		if (!is_string($value)) {
			$this->_error(self::INVALID, $value);
			return false;
		}

		if ($value === '')
			return true;

		$booleanOptions = $this->_booleanOptions;
		$valueOptions = $this->_valueOptions;
		$allowedOptions = $this->_options;
		$seen = array();
		$options = $value;

		while ($options != '') {
			// Boolean options validation
			foreach ($booleanOptions as $booleanOption) {
				if (stripos($options, $booleanOption) === 0) {
					if (in_array($booleanOption, $allowedOptions)) {
						if (!in_array($booleanOption, $seen)) {
							$options = substr($options, strlen($booleanOption));
							$seen[] = $booleanOption;
							goto next_option;
						}

						$this->_error(self::ALREADY_DEFINED, $booleanOption);
						goto bad_option;
					} else {
						$this->_error(self::DISABLED, $booleanOption);
						goto bad_option;
					}
				}
			}

			# Value options validation
			foreach ($valueOptions as $valueOption) {
				$option = $valueOption . '="';

				if (stripos($options, $option) === 0) {
					if (!in_array($valueOption, $allowedOptions)) {
						$this->_error(self::DISABLED, $option);
						goto bad_option;
					}

					if (in_array($valueOption, $seen)) {
						$this->_error(self::ALREADY_DEFINED, $valueOption);
						goto bad_option;
					}

					$optionValue = '';
					$options = substr($options, strlen($option)); // Move to next part of value option

					// Parse option value
					while ($options != '') {
						if ($options[0] == '"') // End quote
							break;

						if ($options[0] == '\\' && (isset($options[1]) && $options[1] == '"')) { // quote within quotes
							$options = substr($options, 2);
							$optionValue .= '"';
							continue;
						}

						$optionValue .= $options[0];
						$options = substr($options, 1);
					}

					if ($options == '') { // End quote not found and options string is empty (Missing end quote)
						$this->_error(self::MISSING_END_QUOTE, $valueOption);
						goto bad_option;
					}

					switch ($valueOption) {
						case 'command':
							if ($optionValue == '') {
								$this->_error(self::MISSING_VALUE, $valueOption);
								goto bad_option;
							}
							$seen[] = $valueOption;
							break;
						case 'environment':
							if ($optionValue == '') {
								$this->_error(self::MISSING_VALUE, $valueOption);
								goto bad_option;
							} elseif (!preg_match('/^[a-z_]+?[a-z0-9_]*=(:?[^[:cntrl:]\n"]|")+$/i', $optionValue)) {
								$this->_error(self::INVALID_VALUE, $valueOption);
								goto bad_option;
							}
							break;
						case 'from':
							if ($optionValue == '') {
								$this->_error(self::MISSING_VALUE, $valueOption);
								goto bad_option;
							}
							// TODO pattern-list validation
							$seen[] = $valueOption;
							break;
						case 'tunnel':
							if ($optionValue == '') {
								$this->_error(self::MISSING_VALUE, $valueOption);
								goto bad_option;
							}
							if (!preg_match('/^"[0-9]+"$/D', $value)) {
								$this->_error(self::INVALID_VALUE, $valueOption);
								goto bad_option;
							}
							$seen[] = $valueOption;
							break;
						case 'permitopen':
							if ($optionValue == '') {
								$this->_error(self::MISSING_VALUE, $valueOption);
								goto bad_option;
							}
							// TODO host:port... validation
							$seen[] = $valueOption;
							break;
						case 'principals':
							if ($optionValue == '') {
								$this->_error(self::MISSING_VALUE, $valueOption);
								goto bad_option;
							}
							// TODO principals validation
							$seen[] = $valueOption;
							break;
					}

					$options = substr($options, 1);
					goto next_option;
				}
			}

			next_option:
			// Skip the comma, and move to the next option (or break out if there are no more).
			if ($options == '')
				break;
			if ($options[0] != ',' || !isset($options[1]) || $options[1] == ',')
				goto bad_option;

			$options = substr($options, 1);
		}

		return true;

		bad_option:
		if (!$this->_messages)
			$this->_error(self::UNKNOWN_MALFORMED);

		return false;
	}
}
