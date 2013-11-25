<?php

/**
 * Logon screen additions.
 *
 * Allows to display additional information (HTML code block) on logon page.
 *
 * Configuration: put your content in logon_page.html file. It will be parsed by
 * Roundcube templates engine, so you can use all template features (tags).
 *
 * @version @package_version@
 * @author Aleksander Machniak <machniak@kolabsys.com>
 *
 * Copyright (C) 2012, Kolab Systems AG <contact@kolabsys.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

class logon_page extends rcube_plugin
{
    public $task    = 'login';
    public $noajax  = true;
    public $noframe = true;

    /**
     * Plugin initialization
     */
    public function init()
    {
        $this->add_hook('template_object_loginform', array($this, 'logon_page_content'));
    }

    /**
     * Login form object container handler. The content will be
     * added to the BODY tag, not the container element itself.
     */
    public function logon_page_content($args)
    {
        $file = $this->home . '/logon_page.html';

        if (file_exists($file)) {
            $html = file_get_contents($file);
        }

        if ($html) {
            $rcmail = rcube::get_instance();

            // Parse content with templates engine, so we can use e.g. localization
            $html = $rcmail->output->just_parse($html);

            // Add the content at the end of the BODY
            $rcmail->output->add_footer($html);
        }

        return $arg;
    }
}
