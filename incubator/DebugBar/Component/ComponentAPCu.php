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

/**
 * Class ComponentAPCu
 * @package DebugBar\Component
 */
class ComponentAPCu implements ComponentInterface
{
    /**
     * @var string Component unique identifier
     */
    const IDENTIFIER = 'APCu';

    /**
     * @var int Priority
     */
    protected $priority = -98;

    /**
     * @var array Cache info
     */
    protected $cacheInfo;

    /**
     * ComponentAPCu constructor.
     */
    public function __construct()
    {
        if (!empty($this->getCacheInfo())) {
            $this->handleActions();
        }
    }

    /**
     * Return cache info
     *
     * @return array|null
     */
    protected function getCacheInfo()
    {
        if ($this->cacheInfo !== NULL) {
            return $this->cacheInfo;
        }

        if (!extension_loaded('apcu')
            || !ini_get('apc.enabled')
        ) {
            return NULL;
        }

        $mem = apcu_sma_info();
        $memTotal = $mem['num_seg'] * $mem['seg_size'];
        $memFree = $mem['avail_mem'];
        $memUsed = $memTotal - $memFree;

        return $this->cacheInfo = [
            'name'    => 'APCu',
            'version' => phpversion('apcu'),
            'memory'  => [
                'total' => $memTotal,
                'used'  => $memUsed,
                'free'  => $memFree
            ]
        ];
    }

    /**
     * Reset APCu userland cache if needed
     */
    protected function handleActions()
    {
        if (!isset($_GET['debug_bar_action'])
            || $_GET['debug_bar_action'] !== 'reset_apcu_cache'
        ) {
            return;
        }

        apcu_clear_cache();
        set_page_message(tr('APCu userland cache has been reset.'), 'success');
        redirectTo($_SERVER['HTTP_REFERER']);
    }

    /**
     * Returns listened event(s)
     *
     * @return array|string
     */
    public function getListenedEvents()
    {
        return [];
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
        if (empty($this->getCacheInfo())) {
            return '';
        }

        return $this->getIdentifier();
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
     * Returns component panel
     *
     * @return string
     */
    public function getPanel()
    {
        $info = $this->getCacheInfo();

        if (empty($info)) {
            return '';
        }

        $panel = "<p><strong>Name:</strong> {$info['name']} ({$info['version']})</p>";
        $panel .= "<br>";
        $panel .= '<p><strong>Memory total:</strong> ' . bytesHuman($info['memory']['total']) . '</p>';
        $panel .= '<p><strong>Memory used:</strong> ' . bytesHuman($info['memory']['used']) . '</p>';
        $panel .= '<p><strong>Memory free:</strong> ' . bytesHuman($info['memory']['free']) . '</p>';
        $panel .= '<br>';
        $panel .= '<div class="buttons"><a href="?debug_bar_action=reset_apcu_cache" type="submit" class="link_as_button">Reset Cache</a></div>';
        return $panel;
    }

    /**
     * Returns component icon path
     *
     * @return string
     */
    public function getIconPath()
    {
        return '/DebugBar/themes/default/assets/images/cache.png';
    }
}
