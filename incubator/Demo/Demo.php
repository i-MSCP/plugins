<?php
/**
 * i-MSCP Demo plugin
 * Copyright (C) 2012-2016 Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */

/**
 * Class iMSCP_Plugin_Demo
 */
class iMSCP_Plugin_Demo extends iMSCP_Plugin_Action
{
	/**
	 * @var array Disabled actions
	 */
	protected $disabledActions = array();

	/**
	 * @var array Disabled pages
	 */
	protected $disabledPages = array();

	/**
	 * Register listeners on the event manager
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Events_Manager_Interface $eventsManager
	 * @return void
	 */
	public function register(iMSCP_Events_Manager_Interface $eventsManager)
	{
		$pluginManager = $this->getPluginManager();

		$pluginName = $this->getName();

		if($pluginManager->pluginIsKnown($pluginName) && $pluginManager->pluginIsEnabled($pluginName)) {
			$events = $this->getConfigParam('disabled_actions', array());

			if(is_array($events)) {
				$this->disabledActions = $events;

				if(($userAccounts = $this->getConfigParam('user_accounts', array()))) {
					if(is_array($userAccounts)) {
						if(!empty($userAccounts)) {
							$eventsManager->registerListener(onLoginScriptEnd, $this, -10);

							$events[] = iMSCP_Events::onBeforeEditUser;
							$events[] = iMSCP_Events::onBeforeDeleteUser;
							$events[] = iMSCP_Events::onBeforeDeleteCustomer;
						}
					} else {
						throw new iMSCP_Plugin_Exception('The user_accounts configuration parameter must be an array.');
					}
				}

				$events = array_unique($events);

				if(!empty($events)) {
					$eventsManager->registerListener($events, $this, 999);
				}
			} else {
				throw new iMSCP_Plugin_Exception('The disabled_actions configuration parameter must be an array.');
			}

			$disabledPages = $events = $this->getConfigParam('disabled_pages', array());

			if(is_array($disabledPages)) {
				$this->disabledPages = $disabledPages;

				if(!empty($disabledPages)) {
					$eventsManager->registerListener(
						array(
							iMSCP_Events::onAdminScriptStart,
							iMSCP_Events::onResellerScriptStart,
							iMSCP_Events::onClientScriptStart,
						),
						array($this, 'disablePages')
					);
				}
			} else {
				throw new iMSCP_Plugin_Exception('The disabled_pages configuration parameter must be an array.');
			}
		} else {
			$eventsManager->registerListener(iMSCP_Events::onBeforeEnablePlugin, $this);
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

		if(isset($_SERVER['HTTP_REFERER'])) {
			redirectTo($_SERVER['HTTP_REFERER']);
		} else {
			redirectTo('index.php');
		}
	}

	/**
	 * onBeforeUpdatePluginList event listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeUpdatePluginList(iMSCP_Events_Event $event)
	{
		if($event->getParam('pluginName') !== $this->getName()) {
			set_page_message(tr('This action is not permitted in demo version.'), 'warning');
			$event->stopPropagation();
		}
	}

	/**
	 * onBeforeInstallPlugin event listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeInstallPlugin()
	{
		set_page_message(tr('This action is not permitted in demo version.'), 'warning');
		$event->stopPropagation();
	}

	/**
	 * onBeforeUninstallPlugin event listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeUninstallPlugin()
	{
		set_page_message(tr('This action is not permitted in demo version.'), 'warning');
		$event->stopPropagation();
	}

	/**
	 * onBeforeEnablePlugin event listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeEnablePlugin(iMSCP_Events_Event $event)
	{
		$pluginManager = $this->getPluginManager();

		if($pluginManager->pluginIsEnabled($this->getName())) {
			set_page_message(tr('This action is not permitted in demo version.'), 'warning');
			$event->stopPropagation();
		}
	}

	/**
	 * onBeforeDisablePlugin event listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeDisablePlugin(iMSCP_Events_Event $event)
	{
		if($event->getParam('pluginName') !== $this->getName()) {
			set_page_message(tr('This action is not permitted in demo version.'), 'warning');
			$event->stopPropagation();
		}
	}

	/**
	 * onBeforeUpdatePlugin event listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeUpdatePlugin(iMSCP_Events_Event $event)
	{
		set_page_message(tr('This action is not permitted in demo version.'), 'warning');
		$event->stopPropagation();
	}

	/**
	 * onBeforeDeletePlugin event listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeDeletePlugin(iMSCP_Events_Event $event)
	{
		set_page_message(tr('This action is not permitted in demo version.'), 'warning');
		$event->stopPropagation();
	}

	/**
	 * onBeforeProtectPlugin event listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeProtectPlugin(iMSCP_Events_Event $event)
	{
		if($event->getParam('pluginName') !== $this->getName()) {
			set_page_message(tr('This action is not permitted in demo version.'), 'warning');
			$event->stopPropagation();
		}
	}

	/**
	 * onBeforeEditUser listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeEditUser(iMSCP_Events_Event $event)
	{
		$eventName = $event->getName();

		if($this->isDisabledAction($eventName)) {
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
	public function onBeforeDeleteUser(iMSCP_Events_Event $event)
	{
		$eventName = $event->getName();

		if($this->isDisabledAction($eventName)) {
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
	public function onBeforeDeleteCustomer(iMSCP_Events_Event $event)
	{
		$eventName = $event->getName();

		if($this->isDisabledAction($eventName)) {
			$this->__call($eventName, array($event));
		} else {
			$event->setParam('userId', $event->getParam('customerId'));
			$this->protectDemoUser($event);
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
	 * disablePages event listener
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function disablePages($event)
	{
		$requestPage = $_SERVER['SCRIPT_NAME'];

		foreach($this->disabledPages as $page) {
			if(preg_match("~$page~i", $requestPage)) {
				showNotFoundErrorPage();
			}
		}

		if(iMSCP_Registry::isRegistered('navigation')) {
			/** @var Zend_Navigation $navigation */
			$navigation = iMSCP_Registry::get('navigation');

			foreach($this->disabledPages as $page) {
				$pages = $navigation->findAllBy('uri', "~$page~i", true, true);

				foreach($pages as $page) {
					$navigation->removePage($page, true);
				}
			}
		}
	}

	/**
	 * Protect demo user / domain accounts against some actions
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	protected function protectDemoUser(iMSCP_Events_Event $event)
	{
		$stmt = exec_query('SELECT admin_name FROM admin WHERE admin_id = ?', $event->getParam('userId'));

		if($stmt->rowCount()) {
			$username = idn_to_utf8($stmt->fields['admin_name']);
			$foundUser = false;

			foreach($this->getConfigParam('user_accounts') as $account) {
				if($account['username'] == $username && (isset($account['protected']) && $account['protected'])) {
					$foundUser = true;
					break;
				}
			}

			if($foundUser) {
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
	public function onLoginScriptEnd(iMSCP_Events_Event $event)
	{
		if($this->getConfigParam('user_accounts') && ($jsCode = $this->getCredentialsDialog()) != '') {
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
	protected function getCredentialsDialog()
	{
		$credentials = $this->getCredentials();

		if(!empty($credentials)) {
			$title = json_encode(tr('i-MSCP Demo'));
			$welcomeMsg = json_encode(tr('Welcome to the i-MSCP Demo version'));
			$credentialInfo = json_encode(
				tr("Please select the account you want use to login and click on the 'Ok' button.")
			);
			$credentials = json_encode($credentials);

			return <<<EOF
<script>
	$(document).ready(function() {
		var welcome = $welcomeMsg;
		var credentialInfo = $credentialInfo + "<br><br>";
		$("<div>", { "id": "demo", html: "<h2>" + welcome + "</h2>" + credentialInfo }).appendTo("body");
		$("<select>", { "id": "demo_credentials" }).appendTo("#demo");
		var credentials = $credentials;
		$.each(credentials, function() {
			$("#demo_credentials").append(
				$("<option>").val(this.username + " " + this.password).text(this.label)
			);
		});
		$("#demo_credentials").change(function() {
			var credentials = $("#demo_credentials option:selected").val().split(" ");
			$("#uname").val(credentials.shift());
			$("#password,#upass").val(credentials.shift());
		}).trigger("change");
		$("#demo").dialog({
			modal: true,
			width: 500,
			autoOpen: true,
			height: "auto",
			buttons: {
				Ok: function() {
					$(this).dialog("close");
				}
			},
			title: $title
		});
	});
</script>
EOF;
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

		foreach($this->getConfigParam('user_accounts') as $account) {
			if(isset($account['label']) && isset($account['username']) && isset($account['password'])) {
				$stmt = exec_query(
					'SELECT admin_pass FROM admin WHERE admin_name = ?', encode_idna($account['username'])
				);

				if($stmt->rowCount()) {
					$dbPassword = $stmt->fields['admin_pass'];

					if(
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
