<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2013 by i-MSCP Team
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
 * @package     iMSCP_Plugin
 * @subpackage  DebugBar_Component
 * @copyright   2010-2013 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Base class for DebugBar components
 *
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  DebugBar_Component
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 */
abstract class iMSCP_Plugin_DebugBar_Component implements iMSCP_Plugin_DebugBar_Component_Interface
{
	/**
	 * Transforms data into human readable format
	 *
	 * @param array $values Values to humanize
	 * @return string
	 */
	protected function _humanize($values)
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
				$retVal .= $key . ' => ' . $this->_humanize($value);
			} elseif (is_object($value)) {
				$retVal .= $key . ' => ' . get_class($value) . ' Object()<br />';
			} elseif (is_null($value)) {
				$retVal .= $key . ' => NULL<br />';
			}
		}

		return $retVal . '</div>';
	}
}
