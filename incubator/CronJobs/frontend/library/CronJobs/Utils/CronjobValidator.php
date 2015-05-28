<?php
/**
 * i-MSCP CronJobs plugin
 * Copyright (C) 2014-2015 Laurent Declercq <l.declercq@nuxwin.com>
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
use Exception;
use iMSCP_Exception as iMSCPException;
use Zend_Uri_Http as HttpUri;

/**
 * Class CronjobValidator
 *
 * Part of code in the validateField() function has been borrowed to the WoltLab project and
 * is copyrighted as follow:
 *
 * @author Alexander Ebert
 * @copyright 2001-2014 WoltLab GmbH
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 *
 * Part of code in the validateFrequency() function has been borrowed to the ISPConfig project and
 * is copyrighted as follow:
 *
 * Copyright (c) 2007, Till Brehm, projektfarm Gmbh
 * Modified 2009, Marius Cramer, pixcept KG
 * All rights reserved.
 *
 * @package Cronjobs\Utils
 */
final class CronjobValidator
{
	/**
	 * @var array Timedate shortcuts map
	 */
	static $timedateShortcutsMap = array(
		'@reboot' => null,
		'@yearly' => array('0', '0', '1', '1', '*'),
		'@annually' => array('0', '0', '1', '1', '*'),
		'@monthly' => array('0', '0', '1', '*', '*'),
		'@weekly' => array('0', '0', '*', '*', '0'),
		'@daily' => array('0', '0', '*', '*', '*'),
		'@midnight' => array('0', '0', '*', '*', '*'),
		'@hourly' => array('0', '*', '*', '*', '*')
	);

	/**
	 * @var array fields translation map
	 */
	static $fieldsTranslationMap = array(
		'minute' => 'Minute',
		'hour' => 'Hour',
		'dmonth' => 'Day of month',
		'month' => 'Month',
		'dweek' => 'Day of week',
		'user' => 'User',
		'command' => 'Command'
	);

	/**
	 *  Disallow instantiation
	 */
	private function __construct()
	{
	}

	/**
	 * Validates a cron job
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
	 * @param string $type Cron job type ( url, jaild or full )
	 * @param int $minTimeInterval Minimum time interval ( in minutes ) between each cron job execution
	 * @return void
	 */
	public static function validate(
		$email, $minute, &$hour, &$dmonth, &$month, &$dweek, $user, $command, $type, $minTimeInterval = 1
	) {
		$minTimeInterval = intval($minTimeInterval);
		$timedateShortcut = '';
		$errMsgs = array();

		if(in_array($type, array('url', 'jailed', 'full'), true)) {
			try {
				self::validateNotificationEmail($email);
			} catch(CronjobException $e) {
				$errMsgs[] = $e->getMessage();
			}

			if(isset(self::$timedateShortcutsMap[$minute])) {
				$timedateShortcut = $minute;

				if($timedateShortcut != '@reboot') {
					list($minute, $hour, $dmonth, $month, $dweek) = self::$timedateShortcutsMap[$minute];
				}
			}

			if($timedateShortcut != '@reboot') {
				foreach(
					array(
						'minute' => $minute, 'hour' => $hour, 'dmonth' => $dmonth, 'month' => $month, 'dweek' => $dweek
					) as $attrName => $attrValue
				) {
					try {
						self::validateField($attrName, $attrValue, $minTimeInterval);
					} catch(CronjobException $e) {
						$errMsgs[] = $e->getMessage();
					}
				}
			}

			try {
				self::validateCommand($user, $command, $type);
			} catch(CronjobException $e) {
				$errMsgs[] = $e->getMessage();
			}

			$errMsgs = array_unique($errMsgs);

			if(!empty($errMsgs)) {
				throw new CronjobException(implode("<br>", $errMsgs));
			}
		} else {
			throw new CronjobException(tr('Invalid cron job type: %s.', $type));
		}

		if($timedateShortcut != '') {
			$hour = $dmonth = $month = $dweek = '';
		}
	}

	/**
	 * Validate a cron notification email
	 *
	 * @throws CronjobException if the email is not valid
	 * @param string $email Email for cron notifications
	 * @return void
	 */
	protected static function validateNotificationEmail($email)
	{
		if($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
			throw new CronjobException(tr('Invalid notification email.'));
		}
	}

	/**
	 * Validates a timedate field
	 *
	 * @throws CronjobException if the given date/time field  is not valid
	 * @param string $fieldName Date/Time field name
	 * @param string $fieldValue Date/Time field value
	 * @param int $minTimeInterval Minimum time interval ( in minutes ) between each cron job execution
	 * @return void
	 */
	protected static function validateField($fieldName, $fieldValue, $minTimeInterval)
	{
		if($fieldValue === '') {
			throw new CronjobException(
				tr("Value for the '%s' field cannot be empty.", tr(self::$fieldsTranslationMap[$fieldName]))
			);
		}

		$pattern = '';
		$step = '[1-9]?[0-9]';
		$months = 'jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec';
		$days = 'mon|tue|wed|thu|fri|sat|sun';
		$namesArr = array();

		switch($fieldName) {
			// check if minute field is a valid minute or a list of valid minutes
			case 'minute':
				$pattern = '[ ]*(\b[0-5]?[0-9]\b)[ ]*';
				break;
			// check if hour field is a valid hour or a list of valid hours
			case 'hour':
				$pattern = '[ ]*(\b[01]?[0-9]\b|\b2[0-3]\b)[ ]*';
				break;
			// check if dmonth field is a valid day of month or a list of valid days of month
			case 'dmonth':
				$pattern = '[ ]*(\b[01]?[1-9]\b|\b2[0-9]\b|\b3[01]\b)[ ]*';
				break;
			// check if month field is a valid month or a list of valid months
			case 'month':
				$digits = '[ ]*(\b[0-1]?[0-9]\b)[ ]*';
				$namesArr = explode('|', $months);
				$pattern = '(' . $digits . ')|([ ]*(' . $months . ')[ ]*)';
				break;
			// check if dweek field is a valid day of week or a list of valid days of week
			case 'dweek':
				$digits = '[ ]*(\b[0]?[0-7]\b)[ ]*';
				$namesArr = explode('|', $days);
				$pattern = '(' . $digits . ')|([ ]*(' . $days . ')[ ]*)';
				break;
		}

		// Perform the actual regex pattern matching
		$range = '(((' . $pattern . ')|(\*\/' . $step . ')?)|(((' . $pattern . ')-(' . $pattern . '))(\/' . $step . ')?))';

		$longPattern = '/^' . $range . '(,' . $range . ')*$/i';

		if($fieldValue != '*' && !preg_match($longPattern, $fieldValue)) {
			throw new CronjobException(
				tr("Invalid value for the '%s' field.", tr(self::$fieldsTranslationMap[$fieldName]))
			);
		} else {
			// Test whether the user provided a meaningful order inside a range
			$testArr = explode(',', $fieldValue);
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

					// Now check the values
					if(intval($left) > intval($right)) {
						throw new CronjobException(
							tr("Invalid value for the '%s' field.", tr(self::$fieldsTranslationMap[$fieldName]))
						);
					}
				}
			}
		}

		if($minTimeInterval > 1) {
			self::validateFrequency($fieldName, $fieldValue, $minTimeInterval);
		}
	}

	/**
	 * Validate cron job frequency
	 *
	 * @throws CronjobException|iMSCPException
	 * @param string $fieldName Date/Time field name
	 * @param string $fieldValue Date/Time field value
	 * @param int $minTimeInterval Minimum interval between each cron job execution
	 * @return void
	 */
	protected static function validateFrequency($fieldName, $fieldValue, $minTimeInterval)
	{
		$maxEntries = 0;
		$minEntries = 0;
		$inMinutes = 1;

		switch($fieldName) {
			case 'minute':
				$maxEntries = 59;
				break;
			case 'hour':
				$maxEntries = 23;
				$inMinutes = 60;
				break;
			case 'dmonth':
				$maxEntries = 31;
				$minEntries = 1;
				$inMinutes = 1440;
				break;
			case 'month':
				$maxEntries = 12;
				$minEntries = 1;
				$inMinutes = 1440 * 28; // Not exactly but enough
				break;
			case 'dweek':
				$maxEntries = 7;
				$inMinutes = 1440;
				break;
		}

		$usedTimes = array();
		$timeList = explode(',', $fieldValue);

		foreach($timeList as $entry) {
			if(preg_match('~^(((\d+)(\-(\d+))?)|\*)(\/([1-9]\d*))?$~', $entry, $matches)) {
				$loopStep = 1;
				$loopFrom = $minEntries;
				$loopTo = $maxEntries;

				// Calculate used values
				if($matches[1] != '*') {
					$loopFrom = $matches[3];
					$loopTo = $matches[3];

					if(isset($matches[4])) {
						$loopTo = $matches[5];
					}
				}

				if(isset($matches[7])) {
					$loopStep = $matches[7];
				}

				// Loop through values to set used times
				for($time = $loopFrom; $time <= $loopTo; $time = ($time + $loopStep)) {
					$usedTimes[] = $time;
				}
			} else {
				throw new iMSCPException(tr('Parsing error.'));
			}
		}

		sort($usedTimes);
		$usedTimes = array_unique($usedTimes);

		$prevTime = $minFreq = -1;
		$curtime = 0;

		foreach($usedTimes as $curtime) {
			if($prevTime != -1) {
				$freq = $curtime - $prevTime;

				if($minFreq == -1 || $freq < $minFreq) {
					$minFreq = $freq;
				}
			}

			$prevTime = $curtime;
		}

		// Check last against first ( needed because e.g. dweek 1,4,7 has diff 1 not 3
		$prevTime = $usedTimes[0];
		$freq = ($prevTime - $minEntries) + ($maxEntries - $curtime) + 1;

		if($minFreq == -1 || $freq < $minFreq) {
			$minFreq = $freq;
		}

		if($minFreq > 0 && $minFreq <= $maxEntries) {
			$minFreq = $minFreq * $inMinutes;

			if($minFreq < $minTimeInterval) {
				throw new CronjobException(
					tr(
						"You're exceeding the allowed limit of %s minutes, which is the minimum interval time between each cron job execution.",
						true,
						$minTimeInterval
					)
				);
			}
		}
	}

	/**
	 * Validates a cron job command
	 *
	 * @throws CronjobException if the user or command is no valid
	 * @param string|null $user User under which the cron command must be executed ( NULL to skip user check )
	 * @param string $command Cron command
	 * @param string $type Cron job type
	 * @çeturn void
	 */
	protected static function validateCommand($user, $command, $type)
	{
		if(null !== $user) {
			if($user !== '') {
				if(!posix_getgrnam($user)) {
					throw new CronjobException(tr('User must be a valid UNIX user.'));
				}
			} else {
				throw new CronjobException(
					tr("Value for the '%s' field cannot be empty.", tr(self::$fieldsTranslationMap['user']))
				);
			}
		}

		if($command !== '') {
			if($type == 'url') {
				try {
					$httpUri = HttpUri::fromString($command);

					if(!$httpUri->valid($command)) {
						throw new CronjobException(tr('Command must be a valid HTTP URL.'));
					} elseif($httpUri->getUsername() || $httpUri->getPassword()) {
						throw new CronjobException(
							tr('Url must not contain any username/password for security reasons.')
						);
					}
				} catch(Exception $e) {
					throw new CronjobException(tr('Command must be a valid HTTP URL.'));
				}
			}
		} else {
			throw new CronjobException(
				tr("Value for the '%s' field cannot be empty.", tr(self::$fieldsTranslationMap['command']))
			);
		}
	}
}
