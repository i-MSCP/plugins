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

namespace CronJobs;

use Zend_Escaper_Escaper as Escaper;

/**
 * Class CommonFunctions
 *
 * @package CronJobs
 */
class CommonFunctions
{
	/** @var Escaper */
	private static $escaper;

	/**
	 * Dissallow instantiation
	 */
	private function __construct()
	{

	}

	/**
	 * Init escaper
	 *
	 * @param string $escaperEncoding Escaper encoding
	 */
	public static function initEscaper($escaperEncoding = 'utf-8')
	{
		static::$escaper = new Escaper($escaperEncoding);
	}

	/**
	 * Render the given template
	 *
	 * @param $tplFile
	 * @return string
	 */
	public static function renderTpl($tplFile)
	{
		ob_start();
		include($tplFile);
		return ob_get_clean();
	}

	/**
	 * Send Json response
	 *
	 * @param int $statusCode HTTP status code
	 * @param array $data JSON data
	 * @return void
	 */
	public static function sendJsonResponse($statusCode = 200, array $data = array())
	{
		header('Cache-Control: no-cache, must-revalidate');
		header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		header('Content-type: application/json');

		switch($statusCode) {
			case 202:
				header('Status: 202 Accepted');
				break;
			case 400:
				header('Status: 400 Bad Request');
				break;
			case 404:
				header('Status: 404 Not Found');
				break;
			case 409:
				header('Status: 409 Conflict');
				break;
			case 500:
				header('Status: 500 Internal Server Error');
				break;
			case 501:
				header('Status: 501 Not Implemented');
				break;
			default:
				header('Status: 200 OK');
		}

		exit(json_encode($data));
	}

	/**
	 * Escape a string for the HTML Body context where there are very few characters
	 * of special meaning. Internally this will use htmlspecialchars().
	 *
	 * @param string $string
	 * @return string
	 */
	public static function escapeHtml($string)
	{
		return static::$escaper->escapeHtml($string);
	}

	/**
	 * Escape a string for the HTML Attribute context. We use an extended set of characters
	 * to escape that are not covered by htmlspecialchars() to cover cases where an attribute
	 * might be unquoted or quoted illegally (e.g. backticks are valid quotes for IE).
	 *
	 * @param string $string
	 * @return string
	 */
	public static function escapeHtmlAttr($string)
	{
		return self::$escaper->escapeHtmlAttr($string);
	}

	/**
	 * Escape a string for the Javascript context. This does not use json_encode(). An extended
	 * set of characters are escaped beyond ECMAScript's rules for Javascript literal string
	 * escaping in order to prevent misinterpretation of Javascript as HTML leading to the
	 * injection of special characters and entities. The escaping used should be tolerant
	 * of cases where HTML escaping was not applied on top of Javascript escaping correctly.
	 * Backslash escaping is not used as it still leaves the escaped character as-is and so
	 * is not useful in a HTML context.
	 *
	 * @param string $string
	 * @return string
	 */
	public static function escapeJs($string)
	{
		return self::$escaper->escapeJs($string);
	}

	/**
	 * Escape a string for the URI or Parameter contexts. This should not be used to escape
	 * an entire URI - only a subcomponent being inserted. The function is a simple proxy
	 * to rawurlencode() which now implements RFC 3986 since PHP 5.3 completely.
	 *
	 * @param string $string
	 * @return string
	 */
	public static function escapeUrl($string)
	{
		return self::$escaper->escapeUrl($string);
	}

	/**
	 * Escape a string for the CSS context. CSS escaping can be applied to any string being
	 * inserted into CSS and escapes everything except alphanumerics.
	 *
	 * @param string $string
	 * @return string
	 */
	public static function escapeCss($string)
	{
		return self::$escaper->escapeCss($string);
	}
}

CommonFunctions::initEscaper();
