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

/**
 * Interface for i-MSCP DebugBar components
 */
interface iMSCP_Plugin_DebugBar_Component_Interface
{
	/**
	 * Returns component unique identifier
	 *
	 * @abstract
	 * @return string
	 */
	public function getIdentifier();

	/**
	 * Returns component tab
	 *
	 * @abstract
	 * @return string
	 */
	public function getTab();

	/**
	 * Returns component panel
	 *
	 * @abstract
	 * @return string
	 */
	public function getPanel();

	/**
	 * Returns component icon path
	 *
	 * @abstract
	 * @return string
	 */
	public function getIconPath();

	/**
	 * Returns listened event(s).
	 *
	 * @abstract
	 * @return array|string
	 */
	public function getListenedEvents();

	/**
	 * Get component priority
	 *
	 * @return int
	 */
	public function getPriority();
}
