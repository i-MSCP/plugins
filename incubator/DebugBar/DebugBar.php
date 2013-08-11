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
 * @subpackage  DebugBar
 * @copyright   2010-2013 by i-MSCP Team
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/**
 * DebugBar Plugin.
 *
 * This plugin is a development helper for i-MSCP, which provides many debug information. The
 * plugin comes with a bunch of component, each providing a particular set of debug information.
 * A component can be or not an event listener that listens on one or more events throw in the
 * application work flow.
 *
 * For now, the DebugBar plugin come with the followings components:
 *
 *  - Version : i-MSCP version, list of all PHP extensions available.
 *
 *  - Variables : Contents of $_GET, $_POST, $_COOKIE, $_FILES and $_SESSION and $_ENV variables.
 *
 *  - Timer : Timing information of current request, time spent in level script ;
 *    support custom timers. Also average, min and max time for requests.
 *
 *  - Files : Number and size of files included with complete list.
 *
 *  - Memory : Peak memory usage, memory usage of Level scripts and the whole application ; support for custom memory
 *             markers.
 *
 *  - Database : Full listing of SQL queries and the time for each.
 *
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  DebugBar
 * @author      Laurent Declercq <l.declercq@nuxwin.com>
 */
class iMSCP_Plugin_DebugBar extends iMSCP_Plugin_Action
{
	/**
	 * @var iMSCP_Events_Event
	 */
	protected $event;

	protected $knownComponents = array('Database', 'Files', 'Memory', 'Timer', 'Variables', 'Version');

	/**
	 * @var iMSCP_Plugin_DebugBar_Component_Interface[]
	 */
	protected $components = array();

	/**
	 * @var array Listened events
	 */
	protected $listenedEvents = array(
		iMSCP_Events::onLoginScriptEnd,
		iMSCP_Events::onLostPasswordScriptEnd,
		iMSCP_Events::onAdminScriptEnd,
		iMSCP_Events::onResellerScriptEnd,
		iMSCP_Events::onClientScriptEnd,
		iMSCP_Events::onExceptionToBrowserEnd
	);

	/**
	 * Register a callback for the given event(s)
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param iMSCP_Events_Manager_Interface $controller
	 */
	public function register(iMSCP_Events_Manager_Interface $controller)
	{
		if(!is_xhr()) {
			$components = $this->getConfigParam('components');

			if($components) {
				if(is_array($components)) {
					$priority = 998;

					foreach ($components as $component) {
						if(in_array($component, $this->knownComponents)) {
							require_once 'Component/' . $component . '.php';
							$componentClass = "iMSCP_Plugin_DebugBar_Component_$component";
							$component = new $componentClass();

							if (!$component instanceof iMSCP_Plugin_DebugBar_Component_Interface) {
								throw new iMSCP_Plugin_Exception(
									'Any DebugBar component must implement the iMSCP_Plugin_DebugBar_Component_Interface interface.'
								);
							} elseif ($component instanceof iMSCP_Events_Listeners_Interface) {
								$controller->registerListener($component->getListenedEvents(), $component, $priority);
								$priority--;
							}

							$this->components[] = $component;
						}
					}

					$controller->registerListener($this->getListenedEvents(), $this, 999);
				} else {
					throw new iMSCP_Plugin_Exception(
						'DebugBar plugin: compoenents parameter must be an array containing list of DeburBar component'
					);
				}
			}
		}
	}

	/**
	 * Catch all calls for listener methods of this class to avoid to declarate them since they do same job
	 *
	 * @throws iMSCP_Plugin_Exception
	 * @param string $listenerMethod Listener method
	 * @param array $arguments Enumerated array containing listener method arguments (always an iMSCP_Events_Description object)
	 */
	public function __call($listenerMethod, $arguments)
	{
		if (!in_array($listenerMethod, $this->listenedEvents)) {
			throw new iMSCP_Plugin_Exception('Unknown listener method.');
		}

		$this->event = $arguments[0];
		$this->buildDebugBar();
	}

	/**
	 * Returns list of listeneds events
	 *
	 * @return array
	 */
	public function getListenedEvents()
	{
		return $this->listenedEvents;
	}

	/**
	 * Builds the Debug Bar and adds it to the repsonse
	 *
	 * @return void
	 */
	protected function buildDebugBar()
	{
		$xhtml = '<div>';

		/** @var $component iMSCP_Plugin_DebugBar_Component_Interface */
		foreach ($this->components as $component) {
			if (($tab = $component->getTab()) != '') {
				$xhtml .= '<span class="iMSCPdebug_span clickable" onclick="iMSCPdebugPanel(\'iMSCPdebug_' . $component->getIdentifier() . '\');">';
				$xhtml .= '<img src="' . $component->getIcon() . '" style="vertical-align:middle" alt="' . $component->getIdentifier() . '" title="' . $component->getIdentifier() . '" /> ';
				$xhtml .= $tab . '</span>';
			}

			if (($panel = $component->getPanel()) != '') {
				$xhtml .= '<div id="iMSCPdebug_' . $component->getIdentifier() . '" class="iMSCPdebug_panel">' . $panel . '</div>';
			}
		}

		$xhtml .= '<span class="iMSCPdebug_span iMSCPdebug_last clickable" id="iMSCPdebug_toggler" onclick="iMSCPdebugSlideBar()">&#171;</span>';
		$xhtml .= '</div>';

		/** @var $templateEngine iMSCP_pTemplate */
		$templateEngine = $this->event->getParam('templateEngine');
		$response = $templateEngine->getLastParseResult();
		$response = preg_replace('@(</head>)@i', $this->buildHeader() . PHP_EOL . '$1', $response);
		$response = str_ireplace('</body>', '<div id="iMSCPdebug_debug">' . $xhtml . '</div></body>', $response);
		$templateEngine->replaceLastParseResult($response);
	}

	/**
	 * Returns xhtml header for the Debug Bar
	 *
	 * @return string
	 */
	protected function buildHeader()
	{
		$collapsed = isset($_COOKIE['iMSCPdebugCollapsed']) ? $_COOKIE['iMSCPdebugCollapsed'] : 0;

		$backgroundColor = array(
			'black' => '#181818',
			'red' => '#691c1c',
			'blue' => '#303882',
			'green' => '#1c6923',
			'yellow' => '#918142'
		);

		$color = $backgroundColor[layout_getUserLayoutColor($_SESSION['user_id'])];

		return ('
            <style type="text/css" media="screen">
                #iMSCPdebug_debug h4 {margin:0.5em;font-weight:bold;}
                #iMSCPdebug_debug strong {font-weight:bold;}
                #iMSCPdebug_debug { font: 1em Geneva, Arial, Helvetica, sans-serif; position:fixed; bottom:5px; left:0px; color:#fff; z-index: 255;}
                #iMSCPdebug_debug a {color:red;}
                #iMSCPdebug_debug span {color:#fff;}
                #iMSCPdebug_debug p {margin:0;}
                #iMSCPdebug_debug ol {margin:10px 0px; padding:0 25px}
                #iMSCPdebug_debug li {margin:0 0 10px 0;}
                #iMSCPdebug_debug .clickable { cursor:pointer }
                #iMSCPdebug_toggler { font-weight:bold; background:' . $color . '; }
                .iMSCPdebug_span { border: 1px solid #ccc; border-right:0px; background:' . $color .'; padding: 6px 5px; }
                .iMSCPdebug_last { border: 1px solid #ccc; }
                .iMSCPdebug_panel { text-align:left; position:absolute;bottom:21px;width:600px; max-height:400px; overflow:auto; display:none; background:' . $color . '; padding:0.5em; border: 1px solid #ccc; }
                .iMSCPdebug_panel .pre {font: 1em Geneva, Arial, Helvetica, sans-serif; margin:0 0 0 22px}
                #iMSCPdebug_exception { border:1px solid #000;display: block; }
            </style>
            <script type="text/javascript">
                if (typeof jQuery == "undefined") {
                    var scriptObj = document.createElement("script");
                    scriptObj.src = "../themes/default/js/jquery.js";
                    scriptObj.type = "text/javascript";
                    var head=document.getElementsByTagName("head")[0];
                    head.insertBefore(scriptObj,head.firstChild);
                }
                var iMSCPdebugLoad = window.onload;
                window.onload = function(){
                    if (iMSCPdebugLoad) {
                        iMSCPdebugLoad();
                    }
                    //jQuery.noConflict();
                    iMSCPdebugCollapsed();
                };

                function iMSCPdebugCollapsed() {
                    if (' . $collapsed . ' == 1) {
                        iMSCPdebugPanel();
                        jQuery("#iMSCPdebug_toggler").html("&#187;");
                        return jQuery("#iMSCPdebug_debug").css("left", "-"+parseInt(jQuery("#iMSCPdebug_debug").outerWidth()-jQuery("#iMSCPdebug_toggler").outerWidth()+1)+"px");
                    }
                }

                function iMSCPdebugPanel(name) {
                    jQuery(".iMSCPdebug_panel").each(function(i){
                        if(jQuery(this).css("display") == "block") {
                            jQuery(this).slideUp();
                        } else {
                            if (jQuery(this).attr("id") == name)
                                jQuery(this).slideDown();
                            else
                                jQuery(this).slideUp();
                        }
                    });
                }

                function iMSCPdebugSlideBar() {
                    if (jQuery("#iMSCPdebug_debug").position().left >= 0) {
                        document.cookie = "iMSCPdebugCollapsed=1;expires=;path=/";
                        iMSCPdebugPanel();
                        jQuery("#iMSCPdebug_toggler").html("&#187;");
                        return jQuery("#iMSCPdebug_debug").animate({left:"-"+parseInt(jQuery("#iMSCPdebug_debug").outerWidth()-jQuery("#iMSCPdebug_toggler").outerWidth()+1)+"px"}, "normal", "swing");
                    } else {
                        document.cookie = "iMSCPdebugCollapsed=0;expires=;path=/";
                        jQuery("#iMSCPdebug_toggler").html("&#171;");
                        return jQuery("#iMSCPdebug_debug").animate({left:"0px"}, "normal", "swing");
                    }
                }

                function iMSCPdebugToggleElement(name, whenHidden, whenVisible){
                    if(jQuery(name).css("display")=="none"){
                        jQuery(whenVisible).show();
                        jQuery(whenHidden).hide();
                    } else {
                        jQuery(whenVisible).hide();
                        jQuery(whenHidden).show();
                    }
                    jQuery(name).slideToggle();
                }
            </script>');
	}
}
