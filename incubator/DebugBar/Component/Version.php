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
 * Version component for the i-MSCP DebugBar Plugin
 *
 * Provides version information about i-MSCP and also information about all PHP extensions loaded.
 */
class iMSCP_Plugin_DebugBar_Component_Version implements iMSCP_Plugin_DebugBar_Component_Interface
{
	/**
	 * @var string Component unique identifier
	 */
	const IDENTIFIER = 'Version';

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
	 * Get component priority
	 *
	 * @return int
	 */
	public function getPriority()
	{
		return $this->priority;
	}

	/**
	 * Gets menu tab for the Debugbar
	 *
	 * @return string
	 */
	public function getTab()
	{
		$version = iMSCP_Registry::get('config')->Version;
		return ' ' . $version . ' / PHP ' . phpversion();
	}

	/**
	 * Gets content panel for the Debugbar
	 *
	 * @return string
	 */
	public function getPanel()
	{
		/** @var iMSCP_Plugin_Manager $pluginManager */
		$pluginManager = iMSCP_Registry::get('pluginManager');

		$version = iMSCP_Registry::get('config')->Version;
		$pluginInfo = $pluginManager->pluginGetInfo('DebugBar');

		$panel = "<h4>i-MSCP DebugBar v{$pluginInfo['version']}</h4>" .
			'<p>©2010-2015 <a href="http://www.i-mscp.net">i-MSCP Team</a><br />' .
			'Author: <a href="mailto:' . $pluginInfo['email'] . '">' . $pluginInfo['author'] . '</a><br />' .
			'Includes images from the <a href="http://www.famfamfam.com/lab/icons/silk/">Silk Icon set</a> by Mark James</p>';
		$panel .= '<h4>i-MSCP ' . $version . ' / PHP ' . phpversion() . ' with extensions:</h4>';
		$extensions = get_loaded_extensions();
		natcasesort($extensions);
		$panel .= "<pre>\t" . implode(PHP_EOL . "\t", $extensions) . '</pre>';
		return $panel;
	}

	/**
	 * Returns component icon path
	 *
	 * @return string
	 */
	public function getIconPath()
	{
		return '/DebugBar/themes/default/assets/images/version.png';
	}

	/**
	 * Returns list of listened events
	 *
	 * @return array
	 */
	public function getListenedEvents()
	{
		return array();
	}
}
