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
 * Database component for the i-MSCP DebugBar Plugin
 *
 * Provide debug information about all queries made during script execution and their execution time.
 */
class iMSCP_Plugin_DebugBar_Component_Database implements iMSCP_Plugin_DebugBar_Component_Interface
{
	/**
	 * @var string Component unique identifier
	 */
	const IDENTIFIER = 'Database';

	/**
	 * @var int Priority
	 */
	protected $priority = 100;

	/**
	 * @var array Listened events
	 */
	protected $_listenedEvents = array(
		iMSCP_Events::onBeforeQueryExecute,
		iMSCP_Events::onAfterQueryExecute
	);

	/**
	 * @var int Total time elapsed
	 */
	protected $_totalTimeElapsed = 0;

	/**
	 * @var array queries and their execution time
	 */
	protected $_queries = array();

	/**
	 * @var int Query index
	 */
	protected $_queryIndex = 0;

	/**
	 * Implements the onBeforeQueryExecute listener
	 *
	 * @param  iMSCP_Database_Events_Database $event
	 * @return void
	 */
	public function onBeforeQueryExecute($event)
	{
		$this->_queries[$this->_queryIndex]['time'] = microtime(true);
		$this->_queries[$this->_queryIndex]['queryString'] = $event->getQueryString();
	}

	/**
	 * Implements the onafterQueryExecute listener
	 *
	 * @return void
	 */
	public function onAfterQueryExecute()
	{
		$this->_queries[$this->_queryIndex]['time'] = ((microtime(true)) - $this->_queries[$this->_queryIndex]['time']);
		$this->_totalTimeElapsed += $this->_queries[$this->_queryIndex]['time'];
		$this->_queryIndex++;
	}

	/**
	 * Returns component unique identifier
	 *
	 * @return string
	 */
	public function getIdentifier()
	{
		return self::IDENTIFIER;
	}

	/**
	 * Returns listened events
	 *
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
		return (count($this->_queries)) . ' queries in ' . round($this->_totalTimeElapsed * 1000, 2) . ' ms';
	}

	/**
	 * Returns the component panel
	 *
	 * @return string
	 */
	public function getPanel()
	{
		$xhtml = '<h4>Database queries and their execution time</h4><ol>';

		foreach ($this->_queries as $query) {
			$xhtml .= '<li><strong>[' . round($query['time'] * 1000, 2) . ' ms]</strong> '
				. htmlspecialchars($query['queryString']) . '</li>';
		}

		return $xhtml . '</ol>';
	}

	/**
	 * Returns component icon path
	 *
	 * @return string
	 */
	public function getIconPath()
	{
		return '/DebugBar/themes/default/assets/images/database.png';
	}
}
