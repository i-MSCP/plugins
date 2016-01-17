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
 * Memory component for the i-MSCP DebugBar Plugin
 *
 * Provides debug information about memory consumption.
 */
class iMSCP_Plugin_DebugBar_Component_Memory implements iMSCP_Plugin_DebugBar_Component_Interface
{
	/**
	 * @var string Component unique identifier
	 */
	const IDENTIFIER = 'Memory';

	/**
	 * @var array Listened events
	 */
	protected $_listenedEvents = array(
		iMSCP_Events::onLoginScriptStart,
		iMSCP_Events::onLoginScriptEnd,
		iMSCP_Events::onLostPasswordScriptStart,
		iMSCP_Events::onLostPasswordScriptEnd,
		iMSCP_Events::onAdminScriptStart,
		iMSCP_Events::onAdminScriptEnd,
		iMSCP_Events::onResellerScriptStart,
		iMSCP_Events::onResellerScriptEnd,
		iMSCP_Events::onClientScriptStart,
		iMSCP_Events::onClientScriptEnd,
		iMSCP_Events::onExceptionToBrowserStart,
		iMSCP_Events::onExceptionToBrowserEnd
	);

	/**
	 * @var int Priority
	 */
	protected $priority = -96;

	/**
	 * @var array memory peak usage
	 */
	protected $_memory = array();

	/**
	 * Catchs all listener methods to avoid to declarare all of them
	 *
	 * @throws iMSCP_Plugin_Exception on an unknown listener method
	 * @param  string $listenerMethod Listener method name
	 * @param  array $arguments Enumerated array containing listener method arguments (always an iMSCP_Events_Description object)
	 * @return void
	 */
	public function __call($listenerMethod, $arguments)
	{
		if (in_array($listenerMethod, $this->_listenedEvents)) {
			switch ($listenerMethod) {
				case iMSCP_Events::onLoginScriptStart:
				case iMSCP_Events::onLostPasswordScriptStart:
				case iMSCP_Events::onAdminScriptStart:
				case iMSCP_Events::onResellerScriptStart:
				case iMSCP_Events::onClientScriptStart:
				case iMSCP_Events::onExceptionToBrowserStart:
					$this->startComputeMemory();
					break;
				default:
					$this->stopComputeMemory();
			}
		}
	}

	/**
	 * Sets a memory mark identified with $name
	 *
	 * @param string $name
	 */
	public function mark($name)
	{
		if (!function_exists('memory_get_peak_usage')) {
			return;
		}

		if (isset($this->_memory['user'][$name])) {
			$this->_memory['user'][$name] = memory_get_peak_usage() - $this->_memory['user'][$name];
		} else {
			$this->_memory['user'][$name] = memory_get_peak_usage();
		}
	}

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
	 * Returns list of listened events
	 *
	 * @abstract
	 * @return array
	 */
	public function getListenedEvents()
	{
		return $this->_listenedEvents;
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
		return $this->_memory['whole'] =
				bytesHuman(memory_get_peak_usage(true)) . ' / ' .
				bytesHuman(utils_getPhpValueInBytes(ini_get('memory_limit')));
	}

	/**
	 * Returns the component panel
	 *
	 * @return string
	 */
	public function getPanel()
	{
		$panel = '<h4>Memory Usage</h4>';
		$panel .= "<pre>\t<strong>Script:</strong> " . bytesHuman($this->_memory['endScript'] - $this->_memory['startScript']) . PHP_EOL;
		$panel .= "\t<strong>Whole Application:</strong> " . $this->_memory['whole'] . PHP_EOL . "</pre>";

		if (isset($this->_memory['user']) && count($this->_memory['user'])) {
			$panel .= "<pre>";
			foreach ($this->_memory['user'] as $key => $value) {
				$panel .= "\t<strong>" . $key . ':</strong> ' . bytesHuman($value) . PHP_EOL;
			}
			$panel .= '</pre>';
		}

		return $panel;
	}

	/**
	 * Returns component icon path
	 *
	 * @return string
	 */
	public function getIconPath()
	{
		return '/DebugBar/themes/default/assets/images/memory.png';
	}

	/**
	 * Start to compute memory
	 *
	 * @return void
	 */
	protected function startComputeMemory()
	{
		if (function_exists('memory_get_peak_usage')) {
			$this->_memory['startScript'] = memory_get_peak_usage();
		}
	}

	/**
	 * Stop to compute memory
	 *
	 * @return void
	 */
	protected function stopComputeMemory()
	{
		if (function_exists('memory_get_peak_usage')) {
			$this->_memory['endScript'] = memory_get_peak_usage();
		}
	}
}
