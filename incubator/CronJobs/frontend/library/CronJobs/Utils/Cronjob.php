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
 * @package Cronjobs\Utils
 */
final class Cronjob
{
	/**
	 *  Disallow instantiation
	 */
	private function __construct()
	{
	}

	/**
	 * Validates a cron command
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
	public static function validate($email, $minute, &$hour, &$dmonth, &$month, &$dweek, $user, $command, $type)
	{
		$errMsgs = array();

		if(in_array($type, array('url', 'jailed', 'full'))) {
			try {
				self::validateNotificationEmail($email);
			} catch(CronjobException $e) {
				$errMsgs[] = $e->getMessage();
			}

			if(in_array(
				$minute,
				array('@reboot', '@yearly', '@annually', '@monthly', '@weekly', '@daily', '@midnight', '@hourly')
			)) {
				try {
					self::validateCommand($user, $command, $type);
					$hour = $dmonth = $month = $dweek = '';
				} catch(CronjobException $e) {
					$errMsgs[] = $e->getMessage();
				}
			} else {
				foreach(
					array(
						'minute' => $minute, 'hour' => $hour, 'dmonth' => $dmonth, 'month' => $month, 'dweek' => $dweek
					) as $attrName => $attrValue
				) {
					try {
						self::validateField($attrName, $attrValue);
					} catch(CronjobException $e) {
						$errMsgs[] = $e->getMessage();
					}
				}

				try {
					self::validateCommand($user, $command, $type);
				} catch(CronjobException $e) {
					$errMsgs[] = $e->getMessage();
				}
			}

			if(!empty($errMsgs)) {
				throw new CronjobException(implode("<br>", $errMsgs));
			}
		} else {
			throw new CronjobException(tr('Invalid cron job type: %s', true, $type));
		}
	}

	/**
	 * Validate a cron notification email
	 *
	 * @throws CronjobException if the email is not valid
	 * @param string $email
	 * @return void
	 */
	protected static function validateNotificationEmail($email)
	{
		if($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
			throw new CronjobException(tr('Invalid notification email'));
		}
	}

	/**
	 * Validates a date/time field
	 *
	 * @throws CronjobException if the given date/time field  is not valid
	 * @param string $name  Date/Time field name
	 * @param string $value Date/Time field value
	 * @return void
	 */
	protected static function validateField($name, $value)
	{
		if($value === '') {
			throw new CronjobException(tr("Value for the %s field cannot be empty.", true, "<strong>$name</strong>"));
		}

		$pattern = '';
		$step = '[1-9]?[0-9]';
		$months = 'jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec';
		$days = 'mon|tue|wed|thu|fri|sat|sun';
		$namesArr = array();

		switch($name) {
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

		if($value != '*' && !preg_match($longPattern, $value)) {
			throw new CronjobException(tr("Invalid value given for the %s field.", true, "<strong>$name</strong>"));
		} else {
			// Test whether the user provided a meaningful order inside a range
			$testArr = explode(',', $value);
			foreach($testArr as $testField) {
				if(
					$pattern &&
					preg_match('/^(((' . $pattern . ')-(' . $pattern . '))(\/' . $step . ')?)+$/', $testField)
				) {
					$compare = explode('-', $testField);
					$compareSlash = explode('/', $compare['1']);

					if(count($compareSlash) == 2) {
						$compare['1'] = $compareSlash['0'];
					}

					// See if digits or names are being given
					$left = array_search(mb_strtolower($compare['0']), $namesArr);
					$right = array_search(mb_strtolower($compare['1']), $namesArr);

					if(!$left) {
						$left = $compare['0'];
					}

					if(!$right) {
						$right = $compare['1'];
					}

					// now check the values
					if(intval($left) > intval($right)) {
						throw new CronjobException(
							tr("Invalid value for the %s field.", true, "<strong>$name</strong>")
						);
					}
				}
			}
		}
	}

	/**
	 * Validates a cron job command
	 *
	 * @throws CronjobException if the user or command is no valid
	 * @param string $user User under which the cron command must be executed
	 * @param string $command Cron command
	 * @param string $type Cron job type
	 * @çeturn void
	 */
	protected static function validateCommand($user, $command, $type)
	{
		if($user !== '') {
			if(!posix_getgrnam($user)) {
				throw new CronjobException(tr('User must be a valid unix user.', true));
			}
		} else {
			throw new CronjobException(tr('User field cannot be empty.', true));
		}

		if($command !== '') {
			if($type === 'url') {
				try {
					$httpUri = HttpUri::fromString($command);

					if(!$httpUri->valid($command)) {
						throw new CronjobException(tr('Command must be a valid HTTP URL.', true));
					} elseif($httpUri->getUsername() || $httpUri->getPassword()) {
						throw new CronjobException(
							tr('Url must not contain any username/password for security reasons.', true)
						);
					}
				} catch(\Exception $e) {
					throw new CronjobException(tr('Command must be a valid HTTP URL.', true));
				}
			}
		} else {
			throw new CronjobException(tr("Command cannot be empty.", true));
		}
	}
}
