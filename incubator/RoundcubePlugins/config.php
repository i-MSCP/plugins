<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2017 Laurent Declercq <l.declercq@nuxwin.com>
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

return [
    // Roundcube plugin definitions
    // See the README.md file inside the plugin archive for
    // further details.
    'plugins' => [
        // Add additional headers to or remove them from outgoing messages
        // See https://github.com/roundcube/roundcubemail/tree/master/plugins/additional_message_headers
        'additional_message_headers' => [
            'enabled' => true,
            'config'  => [
                'include_file' => __DIR__ . '/config/included/additional_message_headers.php'
            ]
        ],

        // Adds a button to move the selected messages to an archive folder
        // See https://github.com/roundcube/roundcubemail/tree/master/plugins/archive
        'archive'                    => [
            'enabled' => true
        ],

        // Provide calendaring features
        // See https://plugins.roundcube.net/packages/kolab/calendar
        'calendar'                   => [
            'enabled'  => true,
            'composer' => [
                'require' => [
                    'kolab/calendar' => '~3.3.0'
                ]
            ],
            'config'   => [
                'parameters' => [
                    'calendar_driver'       => 'database',
                    'calendar_default_view' => 'agendaDay'
                ]
            ]
        ],

        // Creates context menus for various parts of Roundcube using commands
        // from the toolbars
        // See https://github.com/JohnDoh/Roundcube-Plugin-Context-Menu
        'contextmenu'                => [
            'enabled'  => true,
            'composer' => [
                'require' => [
                    'johndoh/contextmenu' => '~2.3.0'
                ]
            ]
        ],

        // Adds emoticons support
        // See https://github.com/roundcube/roundcubemail/tree/master/plugins/emoticons
        'emoticons'                  => [
            'enabled' => true,
            'config'  => [
                'parameters' => [
                    'emoticons_display' => false,
                    'emoticons_compose' => true
                ]
            ]
        ],

        // Logon screen additions
        // See https://git.kolab.org/diffusion/RPK/browse/master/plugins/logon_page
        'logon_page'                 => [
            'enabled'  => true,
            'git'      => [
                'repository' => 'https://git.kolab.org/diffusion/RPK/roundcubemail-plugins-kolab.git',
            ],
            'composer' => [
                'repositories' => [
                    'type' => 'path',
                    'url'  => PERSISTENT_PATH . '/plugins/RoundcubePlugins/roundcubemail-plugins-kolab/plugins/logon_page'
                ],
                'require'      => [
                    'kolab/logon_page' => '~3.3.0'
                ]
            ],
            'config'   => [
                'script' => __DIR__ . '/config/scripts/configure-logon-page.pl'
            ]
        ],

        // Adds a possibility to manage Sieve scripts (incoming mail filters)
        // See https://github.com/roundcube/roundcubemail/tree/master/plugins//managesieve
        // Require: i-MSCP Dovecot server implementation
        'managesieve'                => [
            'enabled' => false,
            'config'  => [
                'parameters' => [
                    'managesieve_port'        => 4190,
                    'managesieve_host'        => 'localhost',
                    'managesieve_auth_type'   => 'PLAIN',
                    'managesieve_default'     => __DIR__ . '/scripts/imscp_default.sieve',
                    'managesieve_script_name' => 'managesieve',
                    'managesieve_vacation'    => '1',
                ],
                'script'     => __DIR__ . '/config/scripts/configure-managesieve.pl'
            ]
        ],

        // Provide notifications for new emails
        // See https://github.com/roundcube/roundcubemail/tree/master/plugins/newmail_notifier
        'newmail_notifier'           => [
            'enabled' => true,
            'config'  => [
                'parameters' => [
                    'newmail_notifier_basic'           => true,
                    'newmail_notifier_sound'           => true,
                    'newmail_notifier_desktop'         => true,
                    'newmail_notifier_desktop_timeout' => 10
                ]
            ]
        ],

        // Open Document Viewer plugin
        // See https://git.kolab.org/diffusion/RPK/browse/master/plugins/odfviewer
        'odfviewer'                  => [
            'enabled'  => true,
            'git'      => [
                'repository' => 'https://git.kolab.org/diffusion/RPK/roundcubemail-plugins-kolab.git',
            ],
            'composer' => [
                'repositories' => [
                    'type' => 'path',
                    'url'  => PERSISTENT_PATH . '/plugins/RoundcubePlugins/roundcubemail-plugins-kolab/plugins/odfviewer'
                ],
                'require'      => [
                    'kolab/odfviewer' => '~3.3.0'
                ]
            ]
        ],

        // Inline PDF viewer plugin
        // See https://git.kolab.org/diffusion/RPK/browse/master/plugins/pdfviewer
        'pdfviewer'                  => [
            'git'      => [
                'repository' => 'https://git.kolab.org/diffusion/RPK/roundcubemail-plugins-kolab.git',
            ],
            'enabled'  => true,
            'composer' => [
                'repositories' => [
                    'type' => 'path',
                    'url'  => PERSISTENT_PATH . '/plugins/RoundcubePlugins/roundcubemail-plugins-kolab/plugins/pdfviewer'
                ],
                'require'      => [
                    'kolab/pdfviewer' => '~3.3.0'
                ]
            ]
        ],

        // Password change feature for Roundcube
        // See https://github.com/roundcube/roundcubemail/tree/master/plugins/password
        'password'                   => [
            'enabled' => true,
            'config'  => [
                'parameters' => [
                    'password_confirm_current'  => true,
                    'password_minimum_length'   => 6,
                    'password_require_nonalpha' => false,
                    'password_force_new_user'   => false
                ]
            ]
        ],

        // Enforces reCAPTCHA for users that have too many failed logins
        // See https://github.com/dsoares/rcguard
        'rcguard'                    => [
            'enabled'  => false,
            'composer' => [
                'require' => [
                    'dsoares/rcguard' => '~1.0.0'
                ],
                'config'  => [
                    'parameters' => [
                        'recaptcha_publickey'  => '',
                        'recaptcha_privatekey' => '',
                        'failed_attempts'      => 3,
                        'expire_time'          => 30,
                        'recaptcha_https'      => false
                    ]
                ]
            ]
        ],

        // Task management plugin
        // See https://plugins.roundcube.net/packages/kolab/tasklist
        'tasklist'                   => [
            'enabled'  => true,
            'composer' => [
                'require' => [
                    'kolab/tasklist' => '~3.3.0'
                ]
            ],
            'config'   => [
                'parameters' => [
                    'tasklist_driver'     => 'database',
                    'tasklist_sort_col'   => '',
                    'tasklist_sort_order' => 'asc',
                ]
            ]
        ],

        // Detects vCard attachments and allows to add them to address book
        // See https://github.com/roundcube/roundcubemail/tree/master/plugins/vcard_attachments
        'vcard_attachments'          => [
            'enabled' => true
        ],

        // Adds an option to download all attachments to a message in one zip
        // file
        // See https://github.com/roundcube/roundcubemail/tree/master/plugins/zipdownload
        'zipdownload'                => [
            'enabled' => true,
            'config'  => [
                'parameters' => [
                    'zipdownload_attachments' => 1,
                    'zipdownload_selection'   => false,
                    'zipdownload_charset'     => 'UTF-8'
                ]
            ]
        ]
    ]
];
