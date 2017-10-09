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

namespace DebugBar\Component;

use iMSCP_Events as Events;

/**
 * Class ComponentMemory
 * @package DebugBar\Component
 */
class ComponentMemory implements ComponentInterface
{
    /**
     * @var string Component unique identifier
     */
    const IDENTIFIER = 'Memory';

    /**
     * @var array Listened events
     */
    protected $listenedEvents = [
        Events::onLoginScriptStart,
        Events::onLoginScriptEnd,
        Events::onLostPasswordScriptStart,
        Events::onLostPasswordScriptEnd,
        Events::onAdminScriptStart,
        Events::onAdminScriptEnd,
        Events::onResellerScriptStart,
        Events::onResellerScriptEnd,
        Events::onClientScriptStart,
        Events::onClientScriptEnd
    ];

    /**
     * @var int Priority
     */
    protected $priority = -96;

    /**
     * @var array memory peak usage
     */
    protected $memory = [];

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
                $this->startComputeMemory();
                break;
            default:
                $this->stopComputeMemory();
        }
    }

    /**
     * Start to compute memory
     *
     * @return void
     */
    protected function startComputeMemory()
    {
        if (function_exists('memory_get_peak_usage')) {
            $this->memory['startScript'] = memory_get_peak_usage();
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
            $this->memory['endScript'] = memory_get_peak_usage();
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

        if (isset($this->memory['user'][$name])) {
            $this->memory['user'][$name] = memory_get_peak_usage() - $this->memory['user'][$name];
        } else {
            $this->memory['user'][$name] = memory_get_peak_usage();
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
        return $this->memory['whole'] = bytesHuman(memory_get_peak_usage(true)) . ' / ' .
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
        $panel .= "<pre>\t<strong>Script:</strong> "
            . bytesHuman($this->memory['endScript'] - $this->memory['startScript']) . PHP_EOL;
        $panel .= "\t<strong>Whole Application:</strong> " . $this->memory['whole'] . PHP_EOL . "</pre>";

        if (isset($this->memory['user']) && count($this->memory['user'])) {
            $panel .= "<pre>";

            foreach ($this->memory['user'] as $key => $value) {
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
}
