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
 * Class iMSCP_Plugin_DebugBar_Component_OPcache
 */
class iMSCP_Plugin_DebugBar_Component_Cache implements iMSCP_Plugin_DebugBar_Component_Interface
{
	/**
	 * @var int Priority
	 */
	protected $priority = -98;

	/**
	 * @var array Cache info
	 */
	protected $cacheInfo = array();

	/**
	 * @var string Component unique identifier
	 */
	const IDENTIFIER = 'Cache';

	public function __construct()
	{
		$this->cacheInfo = $this->getCacheInfo();
		$this->handleActions();
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
	 * Returns component tab
	 *
	 * @return string
	 */
	public function getTab()
	{
		return 'Cache';
	}

	/**
	 * Returns component panel
	 *
	 * @return string
	 */
	public function getPanel()
	{
		$info = $this->cacheInfo;

		if(!empty($info)) {
			$panel = "<p><strong>Name:</strong> {$info['name']}</p>";
			$panel .= "<p><strong>Status:</strong> {$info['status']}</p>";
			$panel .= '<p><strong>Memory consumption:</strong> ' . bytesHuman($info['memory_usage']['used']) . ' (' . tr('Used') . ') - '
				. bytesHuman($info['memory_usage']['free']) . ' (' . tr('Free') . ')</p>';
			$panel .= '<br>';
			$panel .= '<div class="buttons"><a href="?debug_bar_action=reset_cache" type="submit" class="link_as_button">Reset Cache</a></div>';
		} else {
			$panel = "Cache info are not available or cache type is not supported.";
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
		return '/DebugBar/themes/default/assets/images/cache.png';
	}

	/**
	 * Returns listened event(s).
	 *
	 * @return array|string
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
	 * Return cache info
	 *
	 * @return array
	 */
	protected function getCacheInfo()
	{
		$info = array();

		if(function_exists('opcache_get_status')) {
			$tinfo = opcache_get_status();

			$info['name'] = 'OPcache';
			$info['status'] = ($tinfo['opcache_enabled']) ? tr('Enabled') : tr('Disabled');
			$info['memory_usage'] = array(
				'used' => $tinfo['memory_usage']['used_memory'],
				'free' => $tinfo['memory_usage']['free_memory']
			);

			$info['reset_function'] = 'opcache_reset';
		}

		return $info;
	}

	/**
	 * Handle actions
	 *
	 * @throws iMSCP_Exception
	 */
	protected function handleActions()
	{
		if(!empty($this->cacheInfo) && isset($_GET['debug_bar_action'])) {
			$action = clean_input($_GET['debug_bar_action']);

			switch($action) {
				case 'reset_cache':
					if(opcache_reset()) {
						set_page_message(tr('Cache has been successfully reseted.'), 'success');
						redirectTo($_SERVER['HTTP_REFERER']);
					} else {
						set_page_message(tr('An unexpected error occured. Cache has not been reseted.'), 'error');
						redirectTo($_SERVER['HTTP_REFERER']);
					}
				break;
			}
		}
	}
}
