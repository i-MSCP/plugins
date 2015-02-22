<?php
/**
 * i-MSCP AdminerSQL plugin
 * Copyright (C) 2010-2015 by i-MSCP Team
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
 * Class iMSCP_Plugin_AdminerSQL
 */
class iMSCP_Plugin_AdminerSQL extends iMSCP_Plugin_Action
{
	/**
	 * Register a callback for the given event(s)
	 *
	 * @param iMSCP_Events_Manager_Interface $eventsManager
	 */
	public function register(iMSCP_Events_Manager_Interface $eventsManager)
	{
		$eventsManager->registerListener(
			array(
				iMSCP_Events::onBeforeEnablePlugin,
				iMSCP_Events::onAdminScriptStart,
				iMSCP_Events::onClientScriptStart
			),
			$this
		);
	}

	/**
	 * onBeforeEnablePlugin event listener
	 *
	 * @param iMSCP_Events_Event $event
	 */
	public function onBeforeEnablePlugin(iMSCP_Events_Event $event)
	{
		$this->checkCompat($event);
	}

	/**
	 * Implements the onAdminScriptStart event
	 *
	 * @return void
	 */
	public function onAdminScriptStart()
	{
		$this->setupNavigation('admin');
	}

	/**
	 * Implements the onClientScriptStart event
	 *
	 * @return void
	 */
	public function onClientScriptStart()
	{
		$this->setupNavigation('client');
	}

	/**
	 * Inject AdminerSQL links into the navigation object
	 *
	 * @param string $level UI level
	 */
	protected function setupNavigation($level)
	{
		if(iMSCP_Registry::isRegistered('navigation')) {
			/** @var Zend_Navigation $navigation */
			$navigation = iMSCP_Registry::get('navigation');

			switch($level) {
				case 'admin':
					if(($page = $navigation->findOneBy('uri', '/admin/system_info.php'))) {
						$page->addPages(
							array(
								array(
									'label' => tr('AdminerSQL'),
									'uri' => '/adminer/adminer.php',
									'target' => '_blank'
								),
								array(
									'label' => tr('EditorSQL'),
									'uri' => '/adminer/editor.php',
									'target' => '_blank'
								)
							)
						);
					}

					break;
				case 'client':
					if(($page = $navigation->findOneBy('uri', '/client/webtools.php'))) {
						$page->addPage(
							array(
								'label' => tr('AdminerSQL'),
								'uri' => '/adminer/editor.php',
								'target' => '_blank'
							)
						);
					}

					if(($page = $navigation->findOneBy('uri', '/client/sql_manage.php'))) {
						$page->addPage(
							array(
								'label' => tr('AdminerSQL'),
								'uri' => '/adminer/editor.php',
								'target' => '_blank'
							)
						);
					}
			}
		}
	}

	/**
	 * Check plugin compatibility
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	protected function checkCompat(iMSCP_Events_Event $event)
	{
		if($event->getParam('pluginName') == $this->getName()) {
			if(version_compare($event->getParam('pluginManager')->getPluginApiVersion(), '1.0.0', '<')) {
				set_page_message(
					tr('Your i-MSCP version is not compatible with this plugin. Try with a newer version.'), 'error'
				);

				$event->stopPropagation();
			}
		}
	}
}
