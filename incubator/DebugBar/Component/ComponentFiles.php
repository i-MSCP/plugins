<?php
/**
 * i-MSCP DebugBar Plugin
 * Copyright (C) 2010-2017 by Laurent Declercq
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

namespace DebugBar\Component;

use iMSCP_Events as Events;
use iMSCP_Events_Event as Event;

/**
 * Class ComponentFiles
 * @package DebugBar\Component
 */
class ComponentFiles implements ComponentInterface
{
    /**
     * @var string Component unique identifier
     */
    const IDENTIFIER = 'Files';

    /**
     * @var array Listened events
     */
    protected $listenedEvents = [
        Events::onBeforeLoadTemplateFile
    ];

    /**
     * @var int Priority
     */
    protected $priority = -99;

    /**
     * Stores included files
     *
     * @var
     */
    protected $includedFiles = [];

    /**
     * Store loaded template files
     *
     * @var array
     */
    protected $loadedTemplateFiles = [];

    /**
     * Implements onLoadTemplateFile listener method
     *
     * @param Event $event
     * @return void
     */
    public function onBeforeLoadTemplateFile(Event $event)
    {
        $this->loadedTemplateFiles[] = realpath($event->getParam('templatePath'));
    }

    /**
     * Returns listened events
     *
     * @return array
     */
    public function getListenedEvents()
    {
        return $this->listenedEvents;
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
     * Returns component unique identifier
     *
     * @return string Component unique identifier
     */
    public function getIdentifier()
    {
        return self::IDENTIFIER;
    }

    /**
     * Returns component tab
     *
     * @return string
     */
    public function getTab()
    {
        return count($this->_getIncludedFiles()) + count($this->loadedTemplateFiles) . ' files loaded';
    }

    /**
     * Returns list of included files
     *
     * @return array
     */
    protected function _getIncludedFiles()
    {
        $this->includedFiles = get_included_files();
        natsort($this->includedFiles);
        return $this->includedFiles;
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
     * Returns list of loaded template files
     *
     * @return array
     */
    protected function _getLoadedTemplateFiles()
    {
        return $this->loadedTemplateFiles;
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
}
