<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2017 Laurent Declercq <l.declercq@nuxwin.com>
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
    // adds originating IP address for the emails sent via Roundcube (Default: yes)
    // Possible values: yes, no
    'additional_message_headers_plugin' => 'yes',

    // archive plugin (default: yes)
    // Possible values: yes, no
    'archive_plugin'                    => 'yes',

    // calendar plugin (default: yes)
    // Possible values: yes, no
    'calendar_plugin'                   => 'yes',

    // contextmenu plugin (default: yes) 
    // Possible values: yes, no
    'contextmenu_plugin'                => 'yes',

    // emoticons plugin (default: yes)
    // Possible values: yes, no
    'emoticons_plugin'                  => 'yes',

    // logon_page plugin (default: yes)
    // Possible values: yes, no
    'logon_page_plugin'                 => 'yes',

    // managesieve plugin (default: no)
    // Possible values: yes, no
    'managesieve_plugin'                => 'no',

    // Configuration parameters for the managesieve plugin
    'managesieve_config'                => array(
        // Enables separate management interface for vacation responses (out-of-office) (default: 1)
        // 0 - no separate section,
        // 1 - add Vacation section (default),
        // 2 - add Vacation section, but hide Filters section (no additional managesieve filters)
        'managesieve_vacation'    => '1',

        // The name of the script which will be used when there's no user script (default: managesieve)
        'managesieve_script_name' => 'managesieve'
    ),

    // newmail_notifier plugin (default: yes)
    // Possible values: yes, no
    'newmail_notifier_plugin'           => 'yes',

    // Configuration parameters for the newmail_notifier plugin
    'newmail_notifier_config'           => array(
        // Enables basic notification (default: true)
        // Possible values: true, false
        'newmail_notifier_basic'   => true,

        // Enables sound notification (default: false)
        // Possible values: true, false
        'newmail_notifier_sound'   => false,

        // Enables desktop notification (default: false)
        // Possible values: true, false
        'newmail_notifier_desktop' => false
    ),

    // odfviewer plugin (default: yes)
    // Possible values: yes, no
    'odfviewer_plugin'                  => 'yes',

    // password plugin (default: yes)
    // Possible values: yes, no
    'password_plugin'                   => 'yes',

    // Configuration parameters for the password plugin
    'password_config'                   => array(
        // Determine whether current password is required to change password (default: true)
        // Possible values: true, false
        'password_confirm_current'  => true,

        // Require the new password to be of a certain length (default: 6)
        // Possible values: A number or blank to allow passwords of any length
        'password_minimum_length'   => 6,

        // Require the new password to contain a letter and punctuation character (default: false)
        // Possible values: true, false
        'password_require_nonalpha' => false,

        // Enables forcing new users to change their password at their first login (default: false)
        // Possible values: true, false
        'password_force_new_user'   => false
    ),

    // pdfviewer plugin (default: yes)
    // Possible values: yes, no
    'pdfviewer_plugin'                  => 'yes',

    // rcguard plugin (default: no)
    // Possible values: yes, no
    'rcguard_plugin'                    => 'no',

    // Configuration parameter for the rcguard plugin
    'rcguard_config'                    => array(
        // Public key for reCAPTCHA
        'recaptcha_publickey'  => '',

        // Private key for reCAPTCHA
        'recaptcha_privatekey' => '',

        // Number of failed logins before reCAPTCHA is shown (default: 3)
        'failed_attempts'      => 3,

        // Time in minutes after which new login attempt will be allowed (default: 30)
        'expire_time'          => 30,

        // Use HTTPS for reCAPTCHA (default: false)
        // Possible values: true, false
        'recaptcha_https'      => false
    ),

    // tasklist plugin (default: yes)
    // Possible values: yes, no
    'tasklist_plugin'                   => 'yes',

    // vcard_attachments plugin (default: yes)
    // Possible values: yes, no
    'vcard_attachments_plugin'          => 'yes',

    // zipdownload plugin (default: yes)
    // Possible values: yes, no
    'zipdownload_plugin'                => 'yes'
);
