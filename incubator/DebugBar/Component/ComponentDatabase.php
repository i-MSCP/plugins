<?php
/**
 * i-MSCP DebugBar Plugin
 * Copyright (C) 2010-2017 by Laurent Declercq
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

namespace iMSCP\Plugin\DebugBar\Component;

use iMSCP\Database\Events\Statement;
use iMSCP_Events as Events;

/**
 * Class ComponentDatabase
 * @package DebugBar\Component
 */
class ComponentDatabase implements ComponentInterface
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
    protected $listenedEvents = [
        Events::onBeforeQueryExecute,
        Events::onAfterQueryExecute
    ];

    /**
     * @var int Total time elapsed
     */
    protected $totalTimeElapsed = 0;

    /**
     * @var Statement[]
     */
    protected $statements = [];

    /**
     * Implements the onBeforeQueryExecute listener
     *
     * @param Statement $event
     * @return void
     */
    public function onBeforeQueryExecute(Statement $event)
    {
        $uid = spl_object_hash($event);

        if (isset($this->statements[$uid])) {
            $event->setParam('debug_bar_repeated_stmt', $event->getParam('debug_bar_repeated_stmt') + 1);
            $event->setParam('debug_start_exec_time', microtime(true));
            return;
        }

        $event->setParam('debug_bar_repeated_stmt', 1);
        $this->statements[$uid] = $event;
        $event->setParam('debug_start_exec_time', microtime(true));
    }

    /**
     * Implements the onAfterQueryExecute listener
     *
     * @param Statement $event
     * @return void
     */
    public function onAfterQueryExecute(Statement $event)
    {
        $elapsed = microtime(true) - $event->getParam('debug_start_exec_time');
        $this->totalTimeElapsed += $elapsed;
        $event->setParam('debug_bar_total_exec_time', $event->getParam('debug_bar_total_exec_time', 0) + $elapsed);
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
        return $this->listenedEvents;
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
        return count($this->statements) . ' SQL statements in ' . round($this->totalTimeElapsed * 1000, 2) . ' ms';
    }

    /**
     * Returns the component panel
     *
     * @return string
     */
    public function getPanel()
    {
        $xhtml = '<h4>SQL statements and their execution time</h4><ol>';

        foreach ($this->statements as $uuid => $event) {
            $xhtml .= '<li><strong>'
                . sprintf(
                    '[Executed %d time(s) in %s ms]',
                    $event->getParam('debug_bar_repeated_stmt', 1),
                    round($event->getParam('debug_bar_total_exec_time') * 1000, 2)
                )
                . '</strong><br><br>' . tohtml($event->getStatement()) . '</li>';
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
