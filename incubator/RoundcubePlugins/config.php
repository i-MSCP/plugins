<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2013-2016 Rene Schuster <mail@reneschuster.de>
 * Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>
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

return array(
	// Adds the originating ip address for the emails sent via roundcube
	'additional_message_headers_plugin' => 'yes', // YES to enable (default), NO to disable

	// Archive plugin adds a new button to the Roundcube toolbar
	// to move messages to an (user selectable) archive folder.
	'archive_plugin' => 'no', // NO to disable (default), YES to enable

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
	'managesieve_plugin' => 'no', // NO to disable (default), YES to enable

	'managesieve_config' => array(
		// Enables separate management interface for vacation responses (out-of-office)
		// 0 - no separate section,
		// 1 - add Vacation section (default),
		// 2 - add Vacation section, but hide Filters section (no additional managesieve filters)
		'managesieve_vacation' => '1', 
		// The name of the script which will be used when there's no user script
		'managesieve_script_name' => 'managesieve' // default: managesieve
	),

	// Can notify on new mails by focusing browser window and changing favicon, 
	// playing a sound and displaying desktop notification (using webkitNotifications feature).
	'newmail_notifier_plugin' => 'yes', // YES to enable (default), NO to disable

	'newmail_notifier_config' => array(
		// Enables basic notification
		'newmail_notifier_basic' => 'true', // TRUE to enable (default), FALSE to disable
		// Enables sound notification
		'newmail_notifier_sound' => 'false', // TRUE to enable, FALSE to disable (default)
		// Enables desktop notification
		'newmail_notifier_desktop' => 'false' // TRUE to enable, FALSE to disable (default)
	),

	// Roundcube inline odf viewer.
	'odfviewer_plugin' => 'yes', // YES to enable (default), NO to disable

	// Password Plugin for Roundcube to change the mail user password
	'password_plugin' => 'yes', // YES to enable (default), NO to disable
	
	'password_config' => array(
		// Determine whether current password is required to change password.
		'password_confirm_current' => 'true', // TRUE to enable (default), FALSE to disable
		// Require the new password to be a certain length.
		// Set to blank to allow passwords of any length
		'password_minimum_length' => '6', // Default value is '6'
		// Require the new password to contain a letter and punctuation character
		'password_require_nonalpha' => 'false', // TRUE to enable, FALSE to disable (default)
		// Enables forcing new users to change their password at their first login.
		'password_force_new_user' => 'true' // TRUE to enable (default), FALSE to disable
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

	// Rouncube reCAPTCHA plugin
	// Logs failed login attempts and requires users to go through a reCAPTCHA 
	// verification process when the number of failed attempts go too high.
	// Keys can be obtained from http://www.google.com/recaptcha/
	'rcguard_plugin' => 'no', // YES to enable, NO to disable (default)

	'rcguard_config' => array(
		// Public key for reCAPTCHA
		'recaptcha_publickey' => '',
		// Private key for reCAPTCHA
		'recaptcha_privatekey' => '',
		// Number of failed logins before reCAPTCHA is shown
		'failed_attempts' => '3',
		// Release IP after how many minutes (after last failed attempt)
		'expire_time' => '30',
		// Use HTTPS for reCAPTCHA
		'recaptcha_https' => 'false' // TRUE to enable, FALSE to disable (default)
	),

	// Task management plugin for Roundcube.
	'tasklist_plugin' => 'yes', // YES to enable (default), NO to disable

	// Detect VCard attachments and show a button to add them to address book
	'vcard_attachments_plugin' => 'yes', // YES to enable (default), NO to disable

	// Adds an option to download all attachments of a message in one zip file.
	'zipdownload_plugin' => 'yes' // YES to enable (default), NO to disable
);
