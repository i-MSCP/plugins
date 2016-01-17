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

/** @see iMSCP_Plugin_DebugBar_Component_Interface */
require_once 'Interface.php';

/**
 * Files component for the i-MSCP DebugBar Plugin
 *
 * Provide debug information about all included files.
 */
class iMSCP_Plugin_DebugBar_Component_Files implements iMSCP_Plugin_DebugBar_Component_Interface
{
	/**
	 * @var string Component unique identifier
	 */
	const IDENTIFIER = 'Files';

	/**
	 * @var string Listened event
	 */
	protected $_listenedEvents = iMSCP_Events::onBeforeLoadTemplateFile;

	/**
	 * @var int Priority
	 */
	protected $priority = -99;

	/**
	 * Implements onLoadTemplateFile listener method
	 *
	 * @param iMSCP_Events_Event $event
	 * @return void
	 */
	public function onBeforeLoadTemplateFile($event)
	{
		$this->_loadedTemplateFiles[] = realpath($event->getParam('templatePath'));
	}

	/**
	 * Stores included files
	 *
	 * @var
	 */
	protected $_includedFiles = array();

	/**
	 * Store loaded template files
	 *
	 * @var array
	 */
	protected $_loadedTemplateFiles = array();

	/**
	 * Returns component unique identifier
	 *
	 * @return string Component unique identifier.
	 */
	public function getIdentifier()
	{
		return self::IDENTIFIER;
	}

	/**
	 * Returns listened events
	 *
	 * @return array
	 */
	public function getListenedEvents()
	{
		return $this->_listenedEvents;
	}

	/**
	 * Get component priority
	 *
	 * @return int
	 */
	public function getPriority()
	{
		return $this->priority;
	}

	/**
	 * Returns component tab
	 *
	 * @return string
	 */
	public function getTab()
	{
		return count($this->_getIncludedFiles()) + count($this->_loadedTemplateFiles) . ' ' . $this->getIdentifier();
	}

	/**
	 * Returns the component panel
	 *
	 * @return string
	 */
	public function getPanel()
	{
		$includedPhpFiles = $this->_getIncludedFiles();
		$loadedTemplateFiles = $this->_getLoadedTemplateFiles();

		$xhtml = "<h4>General Information</h4><pre>\t";
		$xhtml .= count($includedPhpFiles) + count($loadedTemplateFiles) . ' Files Included/loaded' . PHP_EOL;
		$size = bytesHuman(array_sum(array_map('filesize', array_merge($includedPhpFiles, $loadedTemplateFiles))));
		$xhtml .= "\tTotal Size: $size</pre>";

		$xhtml .= "<h4>PHP Files</h4><pre>\t" . implode(PHP_EOL . "\t", $includedPhpFiles) . '</pre>';
		$xhtml .= "<h4>Templates Files</h4><pre>\t" . implode(PHP_EOL . "\t", $loadedTemplateFiles) . '</pre>';

		return $xhtml;
	}

	/**
	 * Returns component icon path
	 *
	 * @return string
	 */
	public function getIconPath()
	{
		return '/DebugBar/themes/default/assets/images/files.png';
	}

	/**
	 * Returns list of included files
	 *
	 * @return array
	 */
	protected function _getIncludedFiles()
	{
		$this->_includedFiles = get_included_files();
		sort($this->_includedFiles);

		return $this->_includedFiles;
	}

	/**
	 * Returns list of loaded template files
	 *
	 * @return array
	 */
	protected function _getLoadedTemplateFiles()
	{
		return $this->_loadedTemplateFiles;
	}
}
