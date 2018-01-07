<?php
/**
 * i-MSCP Demo plugin
 * Copyright (C) 2012-2017 Laurent Declercq <l.declercq@nuxwin.com>
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

use iMSCP_Events as Events;
use iMSCP_Events_Event as Event;
use iMSCP_Events_Manager_Interface as EventsManagerInterface;
use iMSCP_Plugin_Action as PluginAction;
use iMSCP_Plugin_Exception as PluginException;
use iMSCP_Registry as Registry;

/**
 * Class iMSCP_Plugin_Demo
 */
class iMSCP_Plugin_Demo extends PluginAction
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
     * @param EventsManagerInterface $eventsManager
     * @return void
     */
    public function register(EventsManagerInterface $eventsManager)
    {
        $pluginManager = $this->getPluginManager();
        $pluginName = $this->getName();

        if (!$pluginManager->pluginIsKnown($pluginName) || !$pluginManager->pluginIsEnabled($pluginName)) {
            $eventsManager->registerListener(Events::onBeforeEnablePlugin, $this);
            return;
        }

        $events = $this->getConfigParam('disabled_actions', array());

        if (!is_array($events)) {
            throw new PluginException('The disabled_actions configuration parameter must be an array.');
        }

        $this->disabledActions = $events;

        if (($userAccounts = $this->getConfigParam('user_accounts', array()))) {
            if (!is_array($userAccounts)) {
                throw new PluginException('The user_accounts configuration parameter must be an array.');
            }

            if (!empty($userAccounts)) {
                $eventsManager->registerListener(Events::onLoginScriptEnd, $this, -10);
                $events[] = Events::onBeforeEditUser;
                $events[] = Events::onBeforeDeleteUser;
                $events[] = Events::onBeforeDeleteCustomer;
            }
        }

        $events = array_unique($events);
        if (!empty($events)) {
            $eventsManager->registerListener($events, $this, 999);
        }

        $disabledPages = $events = $this->getConfigParam('disabled_pages', array());

        if (!is_array($disabledPages)) {
            throw new PluginException('The disabled_pages configuration parameter must be an array.');
        }

        $this->disabledPages = $disabledPages;

        if (empty($disabledPages)) {
            return;
        }

        $eventsManager->registerListener(
            array(
                Events::onAdminScriptStart,
                Events::onResellerScriptStart,
                Events::onClientScriptStart,
            ),
            array($this, 'disablePages')
        );
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
        }

        redirectTo('index.php');
    }

    /**
     * onBeforeUpdatePluginList event listener
     *
     * @param Event $event
     * @return void
     */
    public function onBeforeUpdatePluginList(Event $event)
    {
        if ($event->getParam('pluginName') == $this->getName()) {
            return;
        }

        set_page_message(tr('This action is not permitted in demo version.'), 'warning');
        $event->stopPropagation();
    }

    /**
     * onBeforeInstallPlugin event listener
     *
     * @param Event $event
     * @return void
     */
    public function onBeforeInstallPlugin(Event $event)
    {
        set_page_message(tr('This action is not permitted in demo version.'), 'warning');
        $event->stopPropagation();
    }

    /**
     * onBeforeUninstallPlugin event listener
     *
     * @param Event $event
     * @return void
     */
    public function onBeforeUninstallPlugin(Event $event)
    {
        set_page_message(tr('This action is not permitted in demo version.'), 'warning');
        $event->stopPropagation();
    }

    /**
     * onBeforeEnablePlugin event listener
     *
     * @param Event $event
     * @return void
     */
    public function onBeforeEnablePlugin(Event $event)
    {
        $pluginManager = $this->getPluginManager();

        if ($pluginManager->pluginIsEnabled($this->getName())) {
            set_page_message(tr('This action is not permitted in demo version.'), 'warning');
            $event->stopPropagation();
        }
    }

    /**
     * onBeforeDisablePlugin event listener
     *
     * @param Event $event
     * @return void
     */
    public function onBeforeDisablePlugin(Event $event)
    {
        if ($event->getParam('pluginName') == $this->getName()) {
            return;
        }

        set_page_message(tr('This action is not permitted in demo version.'), 'warning');
        $event->stopPropagation();
    }

    /**
     * onBeforeUpdatePlugin event listener
     *
     * @param Event $event
     * @return void
     */
    public function onBeforeUpdatePlugin(Event $event)
    {
        set_page_message(tr('This action is not permitted in demo version.'), 'warning');
        $event->stopPropagation();
    }

    /**
     * onBeforeDeletePlugin event listener
     *
     * @param Event $event
     * @return void
     */
    public function onBeforeDeletePlugin(Event $event)
    {
        set_page_message(tr('This action is not permitted in demo version.'), 'warning');
        $event->stopPropagation();
    }

    /**
     * onBeforeProtectPlugin event listener
     *
     * @param Event $event
     * @return void
     */
    public function onBeforeProtectPlugin(Event $event)
    {
        if ($event->getParam('pluginName') == $this->getName()) {
            return;
        }

        set_page_message(tr('This action is not permitted in demo version.'), 'warning');
        $event->stopPropagation();
    }

    /**
     * onBeforeEditUser listener
     *
     * @param Event $event
     * @return void
     */
    public function onBeforeEditUser(Event $event)
    {
        $eventName = $event->getName();

        if ($this->isDisabledAction($eventName)) {
            $this->__call($eventName, array($event));
            return;
        }

        $this->protectDemoUser($event);
    }

    /**
     * onBeforeDeleteUser listener
     *
     * @param Event $event
     * @return void
     */
    public function onBeforeDeleteUser(Event $event)
    {
        $eventName = $event->getName();

        if ($this->isDisabledAction($eventName)) {
            $this->__call($eventName, array($event));
            return;
        }

        $this->protectDemoUser($event);
    }

    /**
     * onBeforeDeleteCustomer listener
     *
     * @param Event $event
     * @return void
     */
    public function onBeforeDeleteCustomer(Event $event)
    {
        $eventName = $event->getName();

        if ($this->isDisabledAction($eventName)) {
            $this->__call($eventName, array($event));
            return;
        }

        $event->setParam('userId', $event->getParam('customerId'));
        $this->protectDemoUser($event);
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
     * @param Event $event
     * @return void
     */
    public function disablePages(Event $event)
    {
        $requestPage = $_SERVER['SCRIPT_NAME'];

        foreach ($this->disabledPages as $page) {
            if (preg_match("~$page~i", $requestPage)) {
                showNotFoundErrorPage();
            }
        }

        if (!Registry::isRegistered('navigation')) {
            return;
        }

        
        $navigation = Registry::get('navigation');
        
        foreach ($this->disabledPages as $disabledPage) {
            $pages = $navigation->findAllBy('uri', "~$disabledPage~i", true, true);
            foreach ($pages as $page) {
                $navigation->removePage($page, true);
            }
        }
    }

    /**
     * Protect demo user / domain accounts against some actions
     *
     * @param Event $event
     * @return void
     */
    protected function protectDemoUser(Event $event)
    {
        $stmt = exec_query('SELECT admin_name FROM admin WHERE admin_id = ?', $event->getParam('userId'));

        if (!$stmt->rowCount()) {
            return;
        }

        $row = $stmt->fetchRow(PDO::FETCH_ASSOC);
        $username = idn_to_utf8($row['admin_name']);
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

    /**
     * onLoginScriptEnd listener
     *
     * Create a modal dialog to allow users to choose user account they want use to login. Available users are those
     * defined in plugin configuration. If an user account doesn't exists in database, it is not showed.
     *
     * @param Event $event
     * @return void
     */
    public function onLoginScriptEnd(Event $event)
    {
        if (!$this->getConfigParam('user_accounts') || ($jsCode = $this->getCredentialsDialog()) === '') {
            return;
        }

        /** @var $tpl iMSCP_pTemplate */
        $tpl = $event->getParam('templateEngine');
        $tpl->replaceLastParseResult(str_replace('</head>', $jsCode . PHP_EOL . '</head>', $tpl->getLastParseResult()));

    }

    /**
     * Returns modal dialog js code for credentials
     *
     * @return string
     */
    protected function getCredentialsDialog()
    {
        $credentials = $this->getCredentials();

        if (empty($credentials)) {
            return '';
        }

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
                    'SELECT admin_pass FROM admin WHERE admin_name = ?', encode_idna($account['username'])
                );

                if ($stmt->rowCount()) {
                    $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

                    if (crypt($account['password'], $row['admin_pass']) == $row['admin_pass']
                        || $row['admin_pass'] == md5($account['password'])
                    ) {
                        $credentials[] = array(
                            'label'    => $account['label'],
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
