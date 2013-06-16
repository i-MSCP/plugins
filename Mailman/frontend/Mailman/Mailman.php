<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2013 by i-MSCP Team
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
 *
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  Mailman
 * @copyright   2010-2013 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * Mailman Plugin.
 *
 *
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  Mailman
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 */
class iMSCP_Plugin_Mailman extends iMSCP_Plugin_Action
{
	/**
	 * Register a callback for the given event(s).
	 *
	 * @param iMSCP_Events_Manager_Interface $controller
	 */
	public function register(iMSCP_Events_Manager_Interface $controller)
	{
		$controller->registerListener(iMSCP_Events::onAdminScriptStart, $this);
	}

	/**
	 * Implements the onAdminScriptStart event
	 *
	 * @return void
	 */
	public function onAdminScriptStart()
	{
		$this->injectMailmanLinks();

		if(isset($_REQUEST['plugin']) && $_REQUEST['plugin'] == 'mailman') {
			$this->handleRequest();
		}
	}

	/**
	 * Inject Mailman links into the navigation object
	 */
	protected function injectMailmanLinks()
	{
		iMSCP_Registry::get('navigation')->findOneBy('uri', '/admin/settings.php')->addPages(
			array(
				array(
					'label' => tohtml('Mailman'),
					'uri' => '/admin/settings.php?plugin=mailman',
					'title_class' => 'plugin'
				),
				array(
					'label' => tohtml('Mailman'),
					'uri' => '/admin/settings.php?plugin=mailman&action=edit',
					'title_class' => 'plugin',
					'visible' => 0
				),
				array(
					'label' => tohtml('Mailman'),
					'uri' => '/admin/settings.php?plugin=mailman&action=delete',
					'title_class' => 'plugin',
					'visible' => 0
				)
			)
		);
	}

	/**
	 * Handle Mailman plugin requests
	 */
	protected function handleRequest()
	{
		if(isset($_REQUEST['plugin']) && $_REQUEST['plugin'] == 'mailman') {
			// Load mailman action script
			require_once PLUGINS_PATH . '/Mailman/admin/mailman.php';
			exit;
		}
	}
}
