<?php
/**
 * i-MSCP DebugBar Plugin
 * Copyright (C) 2010-2016 by Laurent Declercq
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
 */

/** @see iMSCP_Plugin_DebugBar_Component_Interface */
require_once 'Interface.php';

/**
 * Variables component for the i-MSCP DebugBar Plugin
 *
 * Provides debug information about variables such as $_GET, $_POST...
 */
class iMSCP_Plugin_DebugBar_Component_Variables implements iMSCP_Plugin_DebugBar_Component_Interface
{
	/**
	 * @var string component unique identifier
	 */
	const IDENTIFIER = 'Variables';

	/**
	 * @var int Priority
	 */
	protected $priority = -98;

	/**
	 * Returns component unique identifier
	 *
	 * @return string Component unique identifier.
	 */
	public function getIdentifier()
	{
		return self::IDENTIFIER;
	}

	/**
	 * Returns list of events on which this component listens on
	 *
	 * @return array
	 */
	public function getListenedEvents()
	{
		return array();
	}

	/**
	 * Get component priority
	 *
	 * @return int
	 */
	public function getPriority()
	{
		return $this->priority;
	}

	/**
	 * Returns component tab
	 *
	 * @return string
	 */
	public function getTab()
	{
		return $this->getIdentifier();
	}

	/**
	 * Returns the component panel
	 *
	 * @return string
	 */
	public function getPanel()
	{
		$vars = '<h4>Variables</h4>';

		$vars .= '<h4>$_GET:</h4>'
			. '<div id="iMSCPdebug_get">' . $this->humanize($_GET) . '</div>';

		$vars .= '<h4>$_POST:</h4>'
			. '<div id="iMSCPdebug_post">' . $this->humanize($_POST) . '</div>';

		$vars .= '<h4>$_COOKIE:</h4>'
			. '<div id="iMSCPdebug_cookie">' . $this->humanize($_COOKIE) . '</div>';

		$vars .= '<h4>$_FILES:</h4>'
			. '<div id="iMSCPdebug_file">' . $this->humanize($_FILES) . '</div>';

		$vars .= '<h4>$_SESSION:</h4>'
			. '<div id="iMSCPdebug_session">' . $this->humanize($_SESSION) . '</div>';

		$vars .= '<h4>$_SERVER:</h4>'
			. '<div id="iMSCPdebug_server">' . $this->humanize($_SERVER) . '</div>';

		$vars .= '<h4>$_ENV:</h4>'
			. '<div id="iMSCPdebug_env">' . $this->humanize($_ENV) . '</div>';

		return $vars;
	}

	/**
	 * Returns component icon path
	 *
	 * @return string
	 */
	public function getIconPath()
	{
		return '/DebugBar/themes/default/assets/images/variables.png';
	}

	/**
	 * Transforms data into human readable format
	 *
	 * @param array $values Values to humanize
	 * @return string
	 */
	protected function humanize($values)
	{
		if (is_array($values)) {
			ksort($values);
		}

		$retVal = '<div class="pre">';

		foreach ($values as $key => $value) {
			$key = htmlspecialchars($key);

			if (is_numeric($value)) {
				$retVal .= $key . ' => ' . $value . '<br />';
			} elseif (is_string($value)) {
				$retVal .= $key . ' => \'' . htmlspecialchars($value) . '\'<br />';
			} elseif (is_array($value)) {
				$retVal .= $key . ' => ' . $this->humanize($value);
			} elseif (is_object($value)) {
				$retVal .= $key . ' => ' . get_class($value) . ' Object()<br />';
			} elseif (is_null($value)) {
				$retVal .= $key . ' => NULL<br />';
			}
		}

		return $retVal . '</div>';
	}
}
