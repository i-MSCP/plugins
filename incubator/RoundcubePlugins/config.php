<?php
/**
 * i-MSCP - internet Multi Server Control Panel
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
 *
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  RoundcubePlugins
 * @copyright   Rene Schuster <mail@reneschuster.de>
 * @copyright   Sascha Bay <info@space2place.de>
 * @author      Rene Schuster <mail@reneschuster.de>
 * @author      Sascha Bay <info@space2place.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

return array(
	// Adds the originating ip address for the emails sent via roundcube
	'additional_message_headers_plugin' => 'yes', // YES to enable (default), NO to disable

	// Archive plugin adds a new button to the Roundcube toolbar
	// to move messages to an (user selectable) archive folder.
	'archive_plugin' => 'no', // YES to enable (default), NO to disable

	// Calendar plugin is a full calendar for Roundcube.
	'calendar_plugin' => 'yes', // YES to enable (default), NO to disable

	// Adds context menus to the message list, folder list and address book. 
	// Possibility to mark mails as read/unread, delete, reply and forward mails.
	'contextmenu_plugin' => 'yes', // YES to enable (default), NO to disable

	// Displays the DKIM Signature status of each mail in Roundcube.
	'dkimstatus_plugin' => 'yes', // YES to enable (default), NO to disable

	// Emoticons inserts nice smileys and other emoticons when the appropriate 
	// text representations e.g. :-) are discovered in the mail text.
	'emoticons_plugin' => 'yes', // YES to enable (default), NO to disable

	// Allows to display additional information (HTML code block) at logon page.
	// Configuration: Put your content into the file config-templates/logon_page/logon_page.html
	// It will be parsed by Roundcube templates engine, so you can use all template features (tags).
	'logon_page_plugin' => 'yes', // YES to enable (default), NO to disable

	// Uses the managesieve protocol and allows the user to manage his sieve mail rules.
	// A default Spam sieve rule will be created after the user opened the Filters configuration in Roundcube.
	// This plugin only works with po server dovecot.
	'managesieve_plugin' => 'no', // YES to enable (default), NO to disable

	// The name of the script which will be used when there's no user script
	'managesieve_script_name' => 'managesieve', // default: managesieve

	// Can notify on new mails by focusing browser window and changing favicon, 
	// playing a sound and displaying desktop notification (using webkitNotifications feature).
	'newmail_notifier_plugin' => 'yes', // YES to enable (default), NO to disable

	'newmail_notifier_config' => array(
		// Enables basic notification
		'newmail_notifier_basic' => 'true', // TRUE to enable (default), FALSE to disable
		//  Enables sound notification
		'newmail_notifier_sound' => 'false', // TRUE to enable, FALSE to disable (default)
		// Enables desktop notification
		'newmail_notifier_desktop' => 'false' // TRUE to enable, FALSE to disable (default)
	),

	// Roundcube inline pdf viewer.
	'pdfviewer_plugin' => 'yes', // YES to enable (default), NO to disable

	// Plugin pop3fetcher allows to add pop3 accounts and automatically fetch emails from them.
	'pop3fetcher_plugin' => 'yes', // YES to enable (default), NO to disable

	// Define how often the pop3 accounts should be checked.
	'pop3fetcher_cronjob' => array( // See man CRONTAB(5) for allowed values
		'minute' => '*/15',
		'hour' => '*',
		'day' => '*',
		'month' => '*',
		'dweek' => '*'
	),

	// Task management plugin for Roundcube.
	'tasklist_plugin' => 'yes', // YES to enable (default), NO to disable

	// Adds an option to download all attachments of a message in one zip file.
	'zipdownload_plugin' => 'yes', // YES to enable (default), NO to disable
	
	// i-MSCP mail password changer
	'imscp_pw_changer' => 'yes' // YES to enable (default), NO to disable
);
