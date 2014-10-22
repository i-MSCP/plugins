<?php
/**
 * i-MSCP CronJobs plugin
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

namespace Cronjobs\Utils;

use Cronjobs\Exception\CronjobException;

/**
 * Class Cronjob
 * @package Cronjobs\Utils
 *
 * Note: Most of the code is borrowed to the WCF framework ( @copyright	2001-2014 WoltLab GmbH )
 */
final class Cronjob
{
	/**
	 *  Disallow instantiation
	 */
	private function __construct() { }

	/**
	 * Validates all cronjob attributes
	 *
	 * @throws CronjobException if the given cronjob is not valid
	 * @param string $minute Minute at which the cronjob must be executed
	 * @param string $hour Hour at which the cronjob must be executed
	 * @param string $dmonth Day of month at which the cronjob must be executed
	 * @param string $month Month in which the cronjob must be executed
	 * @param string $dweek Day of week at which the cronjob must be executed
	 * @param string $user User under which cronjob command must be executed
	 * @param string $command Cronjob command
	 * @param string $type Cronjob type
	 */
	public static function validate($minute, $hour, $dmonth, $month, $dweek, $user, $command, $type)
	{
		if(in_array($type, array('url', 'jailed', 'full'))) {
			self::validateAttribute('minute', $minute);
			self::validateAttribute('hour', $hour);
			self::validateAttribute('dmonth', $dmonth);
			self::validateAttribute('month', $month);
			self::validateAttribute('dweek', $dweek);
			self::validateCommand($user, $command, $type);
		} else {
			throw new CronjobException('Invalid cronjob type');
		}
	}

	/**
	 * Validates a cronjob attribute
	 *
	 * @throws CronjobException if the given cronjob attribute value is not valid
	 * @param string $name Cronjob attribute name
	 * @param string $value Cronjob attribute value
	 */
	protected static function validateAttribute($name, $value)
	{
		if ($value === '') {
			throw new CronjobException(sprintf("Value for the '%s' cronjob attribute cannot be empty", $name));
		}

		$pattern = '';
		$step = '[1-9]?[0-9]';
		$months = 'jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec';
		$days = 'mon|tue|wed|thu|fri|sat|sun';
		$namesArr = array();

		switch ($name) {
			// check if minute attribute is a valid minute or a list of valid minutes
			case 'minute':
				$pattern = '[ ]*(\b[0-5]?[0-9]\b)[ ]*';
				break;
			// check if hour attribute is a valid hour or a list of valid hours
			case 'cron_job_hour':
				$pattern = '[ ]*(\b[01]?[0-9]\b|\b2[0-3]\b)[ ]*';
				break;
			// check if dmonth attribute is a valid day of month or a list of valid days of month
			case 'cron_job_dmonth':
				$pattern = '[ ]*(\b[01]?[1-9]\b|\b2[0-9]\b|\b3[01]\b)[ ]*';
				break;
			// check if month attribute is a valid month or a list of valid months
			case 'cron_job_month':
				$digits = '[ ]*(\b[0-1]?[0-9]\b)[ ]*';
				$namesArr = explode('|', $months);
				$pattern = '(' . $digits . ')|([ ]*(' . $months . ')[ ]*)';
				break;
			// check if dweek attribute is a valid day of week or a list of valid days of week
			case 'cron_job_dweek':
				$digits = '[ ]*(\b[0]?[0-7]\b)[ ]*';
				$namesArr = explode('|', $days);
				$pattern = '(' . $digits . ')|([ ]*(' . $days . ')[ ]*)';
				break;
		}

		// Perform the actual regex pattern matching
		$range = '(((' . $pattern . ')|(\*\/' . $step . ')?)|(((' . $pattern . ')-(' . $pattern . '))(\/' . $step . ')?))';

		$longPattern = '/^' . $range . '(,' . $range . ')*$/i';

		preg_match($longPattern, $value);
		if ($value != '*' && !preg_match($longPattern, $value)) {
			throw new CronjobException("invalid value '" . $value . "' given for cronjob attribute '" . $name . "'");
		} else {
			// Test whether the user provided a meaningful order inside a range
			$testArr = explode(',', $value);
			foreach ($testArr as $testField) {
				if (
					$pattern &&
					preg_match('/^(((' . $pattern . ')-(' . $pattern . '))(\/' . $step . ')?)+$/', $testField)
				) {
					$compare = explode('-', $testField);
					$compareSlash = explode('/', $compare['1']);

					if (count($compareSlash) == 2) {
						$compare['1'] = $compareSlash['0'];
					}

					// See if digits or names are being given
					$left = array_search(mb_strtolower($compare['0']), $namesArr);
					$right = array_search(mb_strtolower($compare['1']), $namesArr);

					if (!$left) {
						$left = $compare['0'];
					}

					if (!$right) {
						$right = $compare['1'];
					}

					// now check the values
					if (intval($left) > intval($right)) {
						throw new CronjobException(
							sprintf("Invalid value '%s' given for the '%s' cronjob attribute", $value, $name)
						);
					}
				}
			}
		}
	}

	/**
	 * Validates a cronjob command
	 *
	 * @throws CronjobException if the cronjob user or cronjob command is no valid
	 * @param string $user User under which the cronjob command should be executed
	 * @param string $command Cronjob command
	 * @param string $type Cronjob type
	 */
	protected static function validateCommand($user, $command, $type)
	{
		if(function_exists('posix_getgrnam')) {
			if(!posix_getgrnam($user)) {
				throw new CronjobException("User for cronjob 'command' attribute must be a valid unix user");
			}
		}

		if($type == 'url') {
			$uriValidator = new \iMSCP_Validate_Uri();

			if(!$uriValidator->isValid($command)) {
				throw new CronjobException("command for Url cronjob must be a valid URL");
			}
		}
	}
}
