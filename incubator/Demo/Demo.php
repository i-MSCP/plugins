<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010 - 2013 by Laurent Declercq
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
 * @subpackage  Demo
 * @copyright   Copyright (C) 2010 - 2013 by Laurent Declercq
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * iMSCP_Plugins_Demo class.
 *
 * This plugin allow to setup an i-MSCP demo server.
 *
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  Demo
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 */
class iMSCP_Plugin_Demo extends iMSCP_Plugin_Action
{
	/**
	 * @var array Events
	 */
	protected $events = array();

	/**
	 * Disabled actions
	 *
	 * @var array
	 */
	protected $disabledActions = array();

	/**
	 * Register listeners on the event manager
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Events_Manager_Interface $eventsManager
	 * @return void
	 */
	public function register(iMSCP_Events_Manager_Interface $eventsManager)
	{
		/** @var iMSCP_Plugin_Manager $pluginManager */
		$pluginManager = iMSCP_Registry::get('pluginManager');

		if ($pluginManager->getStatus($this->getName()) == 'enabled') {
			$events = $this->getConfigParam('disabled_actions', array());

			if(is_array($events)) {
				$this->disabledActions = $events;

				if (($userAccounts = $this->getConfigParam('user_accounts', array()))) {
					if(is_array($userAccounts)) {
						if(!empty($userAccounts)) {
							$events[] = iMSCP_Events::onLoginScriptEnd;
							$events[] = iMSCP_Events::onBeforeEditUser;
							$events[] = iMSCP_Events::onBeforeDeleteUser;
							$events[] = iMSCP_Events::onBeforeDeleteCustomer;
						}
					} else {
						throw new iMSCP_Plugin_Exception('User accounts should be provided as array.');
					}
				}

				$this->events = array_unique($events);

				if(!empty($this->events)) {
					$eventsManager->registerListener($this->events, $this, 999);
				}
			} else {
				throw new iMSCP_Plugin_Exception('Disabled actions should be provided as array.');
			}
		} else {
			$eventsManager->registerListener(iMSCP_Events::onBeforeInstallPlugin, $this);
		}
	}

	/**
	 * Provide default listener implementation
	 *
	 * @param string $listener Litener
	 * @param array $arguments Enumerated array containing listener arguments (always an iMSCP_Events_Description object)
	 * @return void
	 */
	public function __call($listener, $arguments)
	{
		set_page_message(tr('This action is not permitted in demo version.'), 'warning');

		if (isset($_SERVER['HTTP_REFERER'])) {
			redirectTo($_SERVER['HTTP_REFERER']);
		} else {
			redirectTo('index.php');
		}
	}

	/**
	 * onBeforeInstallPlugin listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeInstallPlugin($event)
	{
		if($event->getParam('pluginName') == $this->getName()) {
			/** @var iMSCP_Config_Handler_File $cfg */
			$cfg = iMSCP_Registry::get('config');

			if($cfg->Version != 'Git Master') {
				set_page_message(
					tr('Your i-MSCP version is not compatible with this plugin. Try with a newer version.'), 'error'
				);

				$event->stopPropagation(true);
			}
		} else {
			$this->__call($event->getName(), array($event));
		}
	}

	/**
	 * onBeforeDeactivatePlugin listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeDeactivatePlugin($event)
	{
		if($event->getParam('pluginName') != $this->getName()) {
			$this->__call($event->getName(), array($event));
		}
	}

	/**
	 * onBeforeEditUser listener method
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeProtectPlugin($event)
	{
		if($event->getParam('pluginName') !== $this->getName()) {
			$this->__call($event->getName(), array($event));
		}
	}

	/**
	 * onBeforeEditUser listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeEditUser($event)
	{
		$eventName = $event->getName();

		if ($this->isDisabledAction($eventName)) {
			$this->__call($eventName, array($event));
		} else {
			$this->protectDemoUser($event);
		}
	}

	/**
	 * onBeforeDeleteUser listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeDeleteUser($event)
	{
		$eventName = $event->getName();

		if ($this->isDisabledAction($eventName)) {
			$this->__call($eventName, array($event));
		} else {
			$this->protectDemoUser($event);
		}
	}

	/**
	 * onBeforeDeleteCustomer listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeDeleteCustomer($event)
	{
		$eventName = $event->getName();

		if ($this->isDisabledAction($eventName)) {
			$this->__call($eventName, array($event));
		} else {
			$query = 'SELECT `admin_id` FROM `admin` WHERE `admin_id` = ?';
			$stmt = exec_query($query, $event->getParam('customerId'));

			if ($stmt->rowCount()) {
				$event->setParam('userId', $event->getParam('customerId'));
				$this->protectDemoUser($event);
			}
		}
	}

	/**
	 * Is disabled action?
	 *
	 * @param string $actionName Action name
	 * @return bool TRUE if the given action is disabled, FALSE otherwise
	 */
	public function isDisabledAction($actionName)
	{
		return in_array($actionName, $this->disabledActions);
	}

	/**
	 * Protect demo user / domain accounts against some actions
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	protected function protectDemoUser($event)
	{
		$stmt = exec_query('SELECT `admin_name` FROM `admin` WHERE `admin_id` = ?', $event->getParam('userId'));

		if ($stmt->rowCount()) {
			$username = idn_to_utf8($stmt->fields['admin_name']);
			$foundUser = false;

			foreach ($this->getConfigParam('user_accounts') as $account) {
				if ($account['username'] == $username && (isset($account['protected']) && $account['protected'])) {
					$foundUser = true;
					break;
				}
			}

			if ($foundUser) {
				$this->__call($event->getName(), array($event));
			}
		}
	}

	/**
	 * onLoginScriptEnd listener
	 *
	 * Create a modal dialog to allow users to choose user account they want use to login. Available users are those
	 * defined in plugin configuration. If an user account doesn't exists in database, it is not showed.
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onLoginScriptEnd($event)
	{
		if ($this->getConfigParam('user_accounts') && ($jsCode = $this->_getCredentialsDialog()) != '') {
			/** @var $tpl iMSCP_pTemplate */
			$tpl = $event->getParam('templateEngine');
			$tpl->replaceLastParseResult(
				str_replace('</head>', $jsCode . PHP_EOL . '</head>', $tpl->getLastParseResult())
			);
		}
	}

	/**
	 * Returns modal dialog js code for credentials
	 *
	 * @return string
	 */
	protected function _getCredentialsDialog()
	{
		$credentials = $this->getCredentials();

		if (!empty($credentials)) {
			return '
				<script type="text/javascript">
				/*<![CDATA[*/
					$(document).ready(function() {
						var welcome = ' . json_encode(tr('Welcome to the i-MSCP Demo version')) . ';
						var credentialInfo = ' . json_encode(tr("Please select the account you want use to login and click on the 'Ok' button.")) . ' + "<br /><br />";
						$("<div/>", {"id": "demo", html: "<h2>" + welcome + "</h2>" + credentialInfo}).appendTo("body");
						$("<select/>", {"id": "demo_credentials"}).appendTo("#demo");
						var credentials = ' . json_encode($credentials) . '
						$.each(credentials, function() {
							$("#demo_credentials").append($("<option></option>").val(this.username + " " + this.password).text(this.label));
						})
						$("#demo_credentials").change(function() {
							var credentials = $("#demo_credentials option:selected").val().split(" ");
							$("#uname").val(credentials.shift());
							$("#upass").val(credentials.shift());
						}).trigger("change");
						$("#demo").dialog({
							modal: true, width:"500", autoOpen:true, height:"auto", buttons: { Ok: function(){ $(this).dialog("close"); }},
							title:"i-MSCP Demo"
						});
					});
				/*]]>*/
				</script>
			';
		} else {
			return '';
		}
	}

	/**
	 * Returns credentials to push in select element
	 *
	 * @return array
	 */
	protected function getCredentials()
	{
		$credentials = array();

		foreach ($this->getConfigParam('user_accounts') as $account) {
			if (isset($account['label']) && isset($account['username']) && isset($account['password'])) {
				$stmt = exec_query(
					'SELECT `admin_pass` FROM `admin` WHERE `admin_name` = ?', encode_idna($account['username'])
				);

				if ($stmt->rowCount()) {
					$dbPassword = $stmt->fields['admin_pass'];

					if (
						crypt($account['password'], $dbPassword) == $dbPassword ||
						$dbPassword == md5($account['password'])
					) {
						$credentials[] = array(
							'label' => $account['label'],
							'username' => $account['username'],
							'password' => $account['password']
						);
					}
				}
			}
		}

		return $credentials;
	}
}
