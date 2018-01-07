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

use iMSCP_Events as Events;

/**
 * Class ComponentTimer
 * @package DebugBar\Component
 */
class ComponentTimer implements ComponentInterface
{
    /**
     * @var string Component unique identifier
     */
    const IDENTIFIER = 'Timer';

    /**
     * @var array Listened events
     */
    protected $listenedEvents = [
        Events::onLoginScriptStart,
        Events::onLostPasswordScriptStart,
        Events::onAdminScriptStart,
        Events::onResellerScriptStart,
        Events::onClientScriptStart,
        Events::onLoginScriptEnd,
        Events::onLostPasswordScriptEnd,
        Events::onAdminScriptEnd,
        Events::onResellerScriptEnd,
        Events::onClientScriptEnd
    ];

    /**
     * @var int Priority
     */
    protected $priority = -97;

    /**
     * @var float Times
     */
    protected $times = [];

    /**
     * iMSCP_Plugin_DebugBar_Component_Timer constructor.
     */
    public function __construct()
    {
        $this->handleActions();
    }

    /**
     * Catch all listener methods to avoid to declare all of them
     *
     * @param  string $listenerMethod Listener method name
     * @param  array $arguments Enumerated array containing listener method
     *                          arguments
     * @return void
     */
    public function __call($listenerMethod, $arguments)
    {
        if (!in_array($listenerMethod, $this->listenedEvents)) {
            return;
        }

        switch ($listenerMethod) {
            case Events::onLoginScriptStart:
            case Events::onLostPasswordScriptStart:
            case Events::onAdminScriptStart:
            case Events::onResellerScriptStart:
            case Events::onClientScriptStart:
                $this->startComputeTime();
                break;
            default:
                $this->stopComputeTime();
        }
    }

    /**
     * Starts to compute time
     *
     * Computes time elapsed between the begin of the request and the begin of the
     * script. Stores the result in milliseconds. Also reset the timer if asked by
     * user.
     *
     * @return void
     */
    protected function startComputeTime()
    {
        $this->times['startScript'] = microtime(true) * 1000;
    }

    /**
     * Stops to compute time
     *
     * Computes time elapsed between the begin of the request and the end of the
     * script. Stores the result in milliseconds.
     *
     * @return void
     */
    protected function stopComputeTime()
    {
        $this->times['endScript'] = microtime(true) * 1000;
    }

    /**
     * Returns component unique identifier
     *
     * @return string Component unique identifier
     */
    public function getIdentifier()
    {
        return self::IDENTIFIER;
    }

    /**
     * Returns list of listened events
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
     * Returns menu tab for the DebugBar
     *
     * The content for tab is the time elapsed since the request begin
     *
     * @return string
     */
    public function getTab()
    {
        return round($this->times['endScript'] - ($_SERVER['REQUEST_TIME_FLOAT'] * 1000), 2) . ' ms';
    }

    /**
     * Returns content panel for the DebugBar
     *
     * @return string
     */
    public function getPanel()
    {
        $xhtml = '<h4>Custom Timers</h4>';
        $xhtml .= "Current script (Initialization steps excluded): " .
            round($this->times['endScript'] - $this->times['startScript'], 2) . ' ms<br>';

        if (isset($this->times['custom']) && count($this->times['custom'])) {
            foreach ($this->times['custom'] as $name => $time) {
                $xhtml .= '' . $name . ': ' . $time . ' ms<br>';
            }
        }

        if (isset($_SESSION['user_type'])) {
            switch ($_SESSION['user_type']) {
                case 'user':
                    $currentScriptLevel = 'client';
                    break;
                default:
                    $currentScriptLevel = $_SESSION['user_type'];
            }
        } else {
            $currentScriptLevel = 'noLevel';
        }

        $currentScriptName = basename($_SERVER['SCRIPT_FILENAME']);

        // Getting the overall time elapsed in milliseconds for the current script
        // (included bootstrap, initialization time...)
        $_SESSION['iMSCPdebug_Time'][$currentScriptLevel][$currentScriptName]['times'][] =
            round($this->times['endScript'] - ($_SERVER['REQUEST_TIME_FLOAT'] * 1000), 2);

        $xhtml .= '<h4>Overall Timers</h4>';

        foreach ($_SESSION['iMSCPdebug_Time'] as $scriptLevel => $scriptNames) {
            // Current script level highlighting
            if ($scriptLevel == $currentScriptLevel) {
                $scriptLevel = '<strong>' . $scriptLevel . '</strong>';
            }

            $xhtml .= $scriptLevel . '<br>';
            $xhtml .= '<div class="pre">';

            foreach ($scriptNames as $scriptName => $times) {
                // Current script name highlighting
                if ($scriptName == $currentScriptName) {
                    $scriptName = '<strong>' . $scriptName . '</strong>';
                }

                $xhtml .= $scriptName . '<br>';
                $xhtml .= '<div class="pre">';

                foreach ($times as $time) {
                    $xhtml .= 'Avg: ' . $this->_computeAverageTime($time) . ' ms / ' . count($time) . ' requests<br>';
                    $xhtml .= 'Min: ' . round(min($time), 2) . ' ms<br>';
                    $xhtml .= 'Max: ' . round(max($time), 2) . ' ms<br>';
                }

                $xhtml .= '</div>';
            }

            $q = http_build_query(array_merge($_GET, ['debug_bar_action' => 'reset_timer_data' ]));
            $xhtml .= '<br>';
            $xhtml .= "<div class=\"buttons\"><a href=\"?$q\" type=\"submit\" class=\"link_as_button\">Reset</a></div>";
            $xhtml .= '</div>';
        }

        return $xhtml;
    }

    /**
     * Computes average time for a set of requests
     *
     * @param array $array
     * @param int $precision
     * @return float
     */
    protected function _computeAverageTime(array $array, $precision = 2)
    {
        if (!is_array($array)) {
            return 'ERROR in method _computeAverageTime(): this is a not array.';
        }

        foreach ($array as $value) {
            if (!is_numeric($value)) {
                return "ERROR in method _computeAverageTime(): the array contains one or more non-numeric values";
            }
        }

        return round(array_sum($array) / count($array), $precision);
    }

    /**
     * Returns component icon path
     *
     * @return string
     */
    public function getIconPath()
    {
        return '/DebugBar/themes/default/assets/images/timer.png';
    }

    /**
     * Sets a time mark identified with given name
     *
     * @param string $name Time mark unique identifier
     * @return void
     */
    public function mark($name)
    {
        if (isset($this->times['custom'][$name])) {
            $this->times['custom'][$name] =
                ((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000) - $this->times['custom'][$name];
            return;
        }

        $this->times['custom'][$name] = (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000;
    }

    /**
     * Reset timer data userland cache if needed
     */
    protected function handleActions()
    {
        if (!isset($_GET['debug_bar_action'])
            || $_GET['debug_bar_action'] !== 'reset_timer_data'
        ) {
            return;
        }

        unset($_SESSION['iMSCPdebug_Time']);
        set_page_message(tr('Timer has been reset.'), 'success');
        redirectTo($_SERVER['HTTP_REFERER']);
    }
}
