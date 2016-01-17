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
 * Class iMSCP_Plugin_DebugBar
 */
class iMSCP_Plugin_DebugBar extends iMSCP_Plugin_Action
{
	/**
	 * @var iMSCP_Events_Event
	 */
	protected $event;

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
	 * @param iMSCP_Events_Manager_Interface $eventsManager
	 */
	public function register(iMSCP_Events_Manager_Interface $eventsManager)
	{
		if(!is_xhr()) { // Do not act on AJAX request
			$components = $this->getConfigParam('components');

			if($components) {
				if(is_array($components)) {
					foreach ($components as $component) {
						require_once 'Component/' . $component . '.php';
						$componentClass = "iMSCP_Plugin_DebugBar_Component_$component";
						$component = new $componentClass();

						if (!$component instanceof iMSCP_Plugin_DebugBar_Component_Interface) {
							throw new iMSCP_Plugin_Exception(
								'Any DebugBar component must implement the iMSCP_Plugin_DebugBar_Component_Interface interface.'
							);
						} else {
							$events = $component->getListenedEvents();

							if(!empty($events)) {
								$eventsManager->registerListener($events, $component, $component->getPriority());
							}
						}

						$this->components[] = $component;
					}

					$eventsManager->registerListener($this->getListenedEvents(), $this, -100);
				} else {
					throw new iMSCP_Plugin_Exception(
						'DebugBar plugin: components parameter must be an array containing list of DeburBar components'
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
		if (in_array($listenerMethod, $this->getListenedEvents())) {
			$this->event = $arguments[0];
			$this->buildDebugBar();
		}
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
				$xhtml .= '<img src="' . $component->getIconPath() . '" width="16" height="16" style="vertical-align:middle" alt="' . $component->getIdentifier() . '" title="' . $component->getIdentifier() . '" /> ';
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
			'black' => '#000000',
			'red' => '#5a0505',
			'blue' => '#151e72',
			'green' => '#055a0d',
			'yellow' => '#85742f'
		);

		$color = isset($_SESSION['user_id'])
			? $backgroundColor[layout_getUserLayoutColor($_SESSION['user_id'])] : '#000000';

		return ('
            <style type="text/css" media="screen">
                #iMSCPdebug_debug h4 {margin:0.5em;font-weight:bold;}
                #iMSCPdebug_debug strong {font-weight:bold;}
                #iMSCPdebug_debug { font: 1em Geneva, Arial, Helvetica, sans-serif; position:fixed; bottom:5px; left:0; color:#fff; z-index: 255;}
                #iMSCPdebug_debug a {color:red;}
                #iMSCPdebug_debug span {color:#fff;}
                #iMSCPdebug_debug p {margin:0;}
                #iMSCPdebug_debug ol {margin:10px 0; padding:0 25px}
                #iMSCPdebug_debug li {margin:0 0 10px 0;}
                #iMSCPdebug_debug .clickable { cursor:pointer }
                #iMSCPdebug_toggler { font-weight:bold; background:' . $color . '; }
                .iMSCPdebug_span { border: 1px solid #ccc; border-right:0; background:' . $color .'; padding: 6px 5px; }
                .iMSCPdebug_last { border: 1px solid #ccc; }
                .iMSCPdebug_panel { text-align:left; position:absolute;bottom:21px;width:600px; max-height:400px; overflow:auto; display:none; background:' . $color . '; padding:0.5em; border: 1px solid #ccc; }
                .iMSCPdebug_panel .pre {font: 1em Geneva, Arial, Helvetica, sans-serif; margin:0 0 0 22px}
                #iMSCPdebug_exception { border:1px solid #000;display: block; }
            </style>
            <script type="text/javascript">
                if (typeof jQuery == "undefined") {
                    var scriptObj = document.createElement("script");
                    scriptObj.src = "/themes/default/js/jquery.js";
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
                    jQuery(".iMSCPdebug_panel").each(function(i) {
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
