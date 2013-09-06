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
 * @subpackage  AdminerSQL
 * @copyright   2010-2013 by i-MSCP Team
 * @author      Sascha Bay <info@space2place.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  AdminerSQL
 * @author      Sascha Bay <info@space2place.de>
 */
class iMSCP_Plugin_AdminerSQL extends iMSCP_Plugin_Action
{
	/**
	 * @var array Routes
	 */
	protected $routes = array();

	/**
	 * Register a callback for the given event(s).
	 *
	 * @param iMSCP_Events_Manager_Interface $controller
	 */
	public function register(iMSCP_Events_Manager_Interface $controller)
	{
		$controller->registerListener(
			array(
				iMSCP_Events::onBeforeActivatePlugin,
				iMSCP_Events::onBeforePluginsRoute,
				iMSCP_Events::onAdminScriptStart,
				iMSCP_Events::onResellerScriptStart,
				iMSCP_Events::onClientScriptStart
			),
			$this
		);
	}

	/**
	 * onBeforeActivatePlugin event listener
	 *
	 * @param iMSCP_Events_Event $event
	 */
	public function onBeforeActivatePlugin($event)
	{
		/** @var iMSCP_Config_Handler_File $cfg */
		$cfg = iMSCP_Registry::get('config');
		
		if($event->getParam('action') == 'install') {
			if($cfg->Version != 'Git Master' && $cfg->Version <= 20130723) {
				set_page_message(
					tr('Your i-MSCP version is not compatible with this plugin. Try with a newer version'), 'error'
				);
				
				$event->stopPropagation(true);
			}
		}
	}
	
	/**
	 * Implements the onBeforePluginsRoute event
	 *
	 * @return void
	 */
	public function onBeforePluginsRoute()
	{
		$pluginName = $this->getName();

		$this->routes = array(
			'/adminer/adminer.php' => PLUGINS_PATH . '/' . $pluginName . '/frontend/adminer.php',
			'/adminer/editor.php' => PLUGINS_PATH . '/' . $pluginName . '/frontend/editor.php'
		);
	}
	
	/**
	 * Implements the onAdminScriptStart event
	 *
	 * @return void
	 */
	public function onAdminScriptStart()
	{
		$this->setupNavigation();
	}

	/**
	 * Implements the onResellerScriptStart event
	 *
	 * @return void
	 */
	public function onResellerScriptStart()
	{
		$this->setupNavigation();
	}
	
	/**
	 * Implements the onClientScriptStart event
	 *
	 * @return void
	 */
	public function onClientScriptStart()
	{
		$this->setupNavigation();
	}

	/**
	 * Get routes
	 *
	 * @return array
	 */
	public function getRoutes()
	{
		return $this->routes;
	}

	/**
	 * Inject AdminerSQL links into the navigation object
	 */
	protected function setupNavigation()
	{
		if (iMSCP_Registry::isRegistered('navigation')) {
			/** @var Zend_Navigation $navigation */
			$navigation = iMSCP_Registry::get('navigation');
			
			if (($page = $navigation->findOneBy('uri', '/admin/system_info.php'))) {
				$page->addPage(
					array(
						'label' => tohtml(tr('AdminerSQL')),
						'uri' => '/adminer/adminer.php',
						'target' => '_blank'
					)
				);
				$page->addPage(
					array(
						'label' => tohtml(tr('EditorSQL')),
						'uri' => '/adminer/editor.php',
						'target' => '_blank'
					)
				);
			}
			
			if (($page = $navigation->findOneBy('uri', '/reseller/index.php'))) {
				$page->addPage(
					array(
						'label' => tohtml(tr('AdminerSQL')),
						'uri' => '/adminer/adminer.php',
						'target' => '_blank'
					)
				);
				$page->addPage(
					array(
						'label' => tohtml(tr('EditorSQL')),
						'uri' => '/adminer/editor.php',
						'target' => '_blank'
					)
				);
			}
			
			if (($page = $navigation->findOneBy('uri', '/client/sql_manage.php'))) {
				$page->addPage(
					array(
						'label' => tohtml(tr('AdminerSQL')),
						'uri' => '/adminer/editor.php',
						'target' => '_blank'
					)
				);
			}
		}
	}
}
