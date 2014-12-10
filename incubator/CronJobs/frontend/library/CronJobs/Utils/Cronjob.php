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

namespace CronJobs\Utils;

use CronJobs\Exception\CronjobException;
use Zend_Uri_Http as HttpUri;

/**
 * Class Cronjob
 *
 * Note: Most of the code has been borrowed to the WCF framework ( @copyright 2001-2014 WoltLab GmbH )
 *
 * @package Cronjobs\Utils
 */
final class Cronjob
{
	/**
	 *  Disallow instantiation
	 */
	private function __construct() { }

	/**
	 * Validates all cron job attributes
	 *
	 * @throws CronjobException if the given cron job is not valid
	 * @param string $email Cron job notification email
	 * @param string $minute Minute at which the cron job must be executed
	 * @param string $hour Hour at which the cron job must be executed
	 * @param string $dmonth Day of month at which the cron job must be executed
	 * @param string $month Month in which the cron job must be executed
	 * @param string $dweek Day of week at which the cron job must be executed
	 * @param string $user User under which cron job command must be executed
	 * @param string $command Cron job command
	 * @param string $type Cron job type
	 */
	public static function validate($email, $minute, $hour, $dmonth, $month, $dweek, $user, $command, $type)
	{
		self::validateNotificationEmail($email);

		if(in_array($type, array('url', 'jailed', 'full'))) {
			if(
				in_array(
					$minute,
					array('@reboot', '@yearly', '@annually', '@monthly', '@weekly', '@daily', '@midnight', '@hourly')
				)
			) {
				self::validateCommand($user, $command, $type);
			} else {
				self::validateAttribute('minute', $minute);
				self::validateAttribute('hour', $hour);
				self::validateAttribute('dmonth', $dmonth);
				self::validateAttribute('month', $month);
				self::validateAttribute('dweek', $dweek);
				self::validateCommand($user, $command, $type);
			}
		} else {
			throw new CronjobException(tr('Invalid cron job type: %s', true, $type));
		}
	}

	/**
	 * Validate a cron notification email
	 *
	 * @param string $email
	 * @throws CronjobException
	 */
	protected static function validateNotificationEmail($email)
	{
		if($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
			throw new CronjobException(tr('Invalid email for cron notification: %s', true, $email));
		}
	}

	/**
	 * Validates a cron job attribute
	 *
	 * @throws CronjobException if the given cron job attribute value is not valid
	 * @param string $name Cron job attribute name
	 * @param string $value Cron job attribute value
	 */
	protected static function validateAttribute($name, $value)
	{
		if ($value === '') {
			throw new CronjobException(tr("Value for the '%s' cron job attribute cannot be empty", true, $name));
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
			case 'hour':
				$pattern = '[ ]*(\b[01]?[0-9]\b|\b2[0-3]\b)[ ]*';
				break;
			// check if dmonth attribute is a valid day of month or a list of valid days of month
			case 'dmonth':
				$pattern = '[ ]*(\b[01]?[1-9]\b|\b2[0-9]\b|\b3[01]\b)[ ]*';
				break;
			// check if month attribute is a valid month or a list of valid months
			case 'month':
				$digits = '[ ]*(\b[0-1]?[0-9]\b)[ ]*';
				$namesArr = explode('|', $months);
				$pattern = '(' . $digits . ')|([ ]*(' . $months . ')[ ]*)';
				break;
			// check if dweek attribute is a valid day of week or a list of valid days of week
			case 'dweek':
				$digits = '[ ]*(\b[0]?[0-7]\b)[ ]*';
				$namesArr = explode('|', $days);
				$pattern = '(' . $digits . ')|([ ]*(' . $days . ')[ ]*)';
				break;
		}

		// Perform the actual regex pattern matching
		$range = '(((' . $pattern . ')|(\*\/' . $step . ')?)|(((' . $pattern . ')-(' . $pattern . '))(\/' . $step . ')?))';

		$longPattern = '/^' . $range . '(,' . $range . ')*$/i';

		if ($value != '*' && !preg_match($longPattern, $value)) {
			throw new CronjobException(
				tr("Invalid value '%s' given for the '%s' cron job attribute ", true, $value, $name)
			);
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
							tr("Invalid value '%s' given for the '%s' cron job attribute.", true, $value, $name)
						);
					}
				}
			}
		}
	}

	/**
	 * Validates a cron job command
	 *
	 * @throws CronjobException if the cron job user or cron job command is no valid
	 * @param string $user User under which the cron job command should be executed
	 * @param string $command Cron job command
	 * @param string $type Cron job type
	 */
	protected static function validateCommand($user, $command, $type)
	{
		if($user !== '') {
			if(!posix_getgrnam($user)) {
				throw new CronjobException(tr('Cron job user must be a valid unix user.', true));
			}
		} else {
			throw new CronjobException(tr('Cron job user field cannot be empty.', true));
		}

		if($command !== '') {
			if($type == 'url') {
				try {
					$httpUri = HttpUri::fromString($command);

					if(!$httpUri->valid($command)) {
						throw new CronjobException(tr('Command for Url cron job must be a valid HTTP Url.', true));
					} elseif($httpUri->getUsername() || $httpUri->getPassword()) {
						throw new CronjobException(
							tr('Url cron job must not contain any username/password for security reasons.', true)
						);
					}
				} catch(\Exception $e) {
					throw new CronjobException(tr('Command for Url cron job must be a valid HTTP Url.', true));
				}
			}
		} else {
			throw new CronjobException(tr('Cron job command field cannot be empty.', true));
		}
	}
}
