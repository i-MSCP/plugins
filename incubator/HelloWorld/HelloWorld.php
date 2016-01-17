<?php
/**
 * i-MSCP HelloWorld plugin
 * Copyright (C) 2010-2016 by Laurent Declercq <l.declercq@nuxwin.com>
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
 * Hello World Plugin
 */
class iMSCP_Plugin_HelloWorld extends iMSCP_Plugin_Action
{
	/**
	 * Register event listeners
	 *
	 * @param iMSCP_Events_Manager_Interface $eventsManager
	 * @return void
	 */
	public function register(iMSCP_Events_Manager_Interface $eventsManager)
	{
		$eventsManager->registerListener(iMSCP_Events::onLoginScriptStart, $this, -500);
	}

	/**
	 * onLoginScriptStart event listener
	 * @return void
	 */
	public function onLoginScriptStart()
	{
		// Say Hello World on the login page
		set_page_message('i-MSCP HelloWorld plugin says: Hello World', 'info');
	}
}
