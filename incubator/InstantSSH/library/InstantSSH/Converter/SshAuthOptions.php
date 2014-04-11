<?php

namespace InstantSSH\Converter;

use iMSCP_Exception;

/**
 * Class SshAuthOptions
 *
 * Class allowing to convert an authentication options string to an array of authentication option and vice-versa.
 *
 * @package InstantSSH\Converter
 */
class SshAuthOptions
{
	/**
	 * Convert an authentication options string to array
	 *
	 * @throws iMSCP_Exception
	 * @param string $options Authentication options
	 * @return array
	 */
	static public function toArray($options)
	{
		if (!is_string($options)) {
			throw new iMSCP_Exception('String expected');
		}

		$optionsArr = array();

		while ($options != '') {
			if (preg_match('/^((?i)[a-z0-9-]+=")/', $options, $m)) { // Value option
				$option = strtolower($m[1]);
				$optionValue = '';
				$options = substr($options, strlen($option));

				// Parse option value
				while ($options != '') {
					if ($options[0] == '"')
						break;

					if ($options[0] == '\\' && (isset($options[1]) && $options[1] == '"')) {
						$options = substr($options, 2);
						$optionValue .= '"';
						continue;
					}

					$optionValue .= $options[0];
					$options = substr($options, 1);
				};

				if ($options == '') { // End quote not found and options string is empty (Missing end quote)
					throw new iMSCP_Exception('Invalid authentication options provided');
				}

				$option = substr($option, 0, -2);

				if ($option == 'environment') {
					$optionsArr['environment'][] = $optionValue;
				} else {
					$optionsArr[$option] = $optionValue;
				}

				$options = substr($options, 1);
			} elseif (preg_match('/^((?i)[a-z0-9-]+)/', $options, $m)) { // Boolean option
				$option = $m[1];
				$options = substr($options, strlen($option));
				$optionsArr[$option] = true;
			} else {
				throw new iMSCP_Exception('Invalid authentication options provided');
			}

			// Skip the comma, and move to the next option (or break out if there are no more).
			if ($options == '')
				break;
			if ($options[0] != ',' || !isset($options[1]) || $options[1] == ',')
				throw new iMSCP_Exception('Invalid authentication options provided');

			$options = substr($options, 1);
		}

		return $optionsArr;
	}

	/**
	 * Convert an array of authentication options to string
	 *
	 * @throws iMSCP_Exception
	 * @param array $options Authentication options
	 * @return string
	 */
	static public function toString($options)
	{
		if (is_array($options)) {
			$tmpOptions = array();

			foreach ($options as $key => $value) {
				if (is_array($value)) {
					foreach ($value as $v) {
						$tmpOptions[] = $key . '="' . addcslashes($v, '"') . '"';
					}
				} elseif (!is_bool($value)) {
					$tmpOptions[] = $key . '="' . addcslashes($value, '"') . '"';
				} else {
					$tmpOptions[] = $options;
				}
			}

			return implode(',', $tmpOptions);
		} else {
			throw new iMSCP_Exception('Array expected');
		}
	}
}
