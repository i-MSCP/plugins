<?php
/**
 * i-MSCP RoundcubePlugins plugin
 * Copyright (C) 2019 Laurent Declercq <l.declercq@nuxwin.com>
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

$config = iMSCP_Registry::get('config');

return [
    // Roundcube plugin definitions
    //
    // See the README.md file inside the plugin archive for
    // further details.
    'plugin_definitions' => [
        // Add additional headers to or remove them from outgoing messages
        // See https://github.com/roundcube/roundcubemail/tree/master/plugins/additional_message_headers
        'additional_message_headers' => [
            'enabled' => false,
            'config'  => [
                'include_file' => __DIR__ . '/config/included/additional_message_headers.php'
            ]
        ],

        // Adds a button to move the selected messages to an archive folder
        // See https://github.com/roundcube/roundcubemail/tree/master/plugins/archive
        'archive'                    => [
            'enabled' => false
        ],

        // Provide calendaring features
        // See https://plugins.roundcube.net/packages/kolab/calendar
        'calendar'                   => [
            'enabled'  => false,
            'composer' => [
                'require' => [
                    'kolab/calendar' => '~3.3.0'
                ]
            ],
            'config'   => [
                'parameters' => [
                    'calendar_driver'       => 'database',
                    'calendar_default_view' => 'agendaDay'
                ],
            ]
        ],

        // Creates context menus for various parts of Roundcube using commands
        // from the toolbars
        // See https://github.com/JohnDoh/Roundcube-Plugin-Context-Menu
        'contextmenu'                => [
            'enabled'  => false,
            'composer' => [
                'require' => [
                    'johndoh/contextmenu' => '~2.3.0'
                ]
            ]
        ],

        // Adds emoticons support
        // See https://github.com/roundcube/roundcubemail/tree/master/plugins/emoticons
        'emoticons'                  => [
            'enabled' => false,
            'config'  => [
                'parameters' => [
                    'emoticons_display' => false,
                    'emoticons_compose' => true
                ]
            ]
        ],

        // Adds a possibility to manage Sieve scripts (incoming mail filters)
        // See https://github.com/roundcube/roundcubemail/tree/master/plugins//managesieve
        // Require: i-MSCP Dovecot server implementation
        'managesieve'                => [
            'enabled' => false,
            'config'  => [
                'parameters' => [
                    'managesieve_port'      => 4190,
                    'managesieve_auth_type' => 'PLAIN',
                    'managesieve_default'   => "{$config['CONF_DIR']}/dovecot/sieve.default",
                    'managesieve_vacation'  => 1
                ]
            ]
        ],

        // Provide message learning (spam/ham) through Roundcube
        // See https://github.com/johndoh/roundcube-markasjunk2.git
        // Warning: You need first install the i-MSCP SpamAssassin plugin
        'markasjunk2'                => [
            'enabled'  => false,
            'composer' => [
                'require' => [
                    'johndoh/markasjunk2' => '^1.11'
                ]
            ],
            'config'   => [
                'parameters' => [
                    'markasjunk2_learning_driver' => 'dir_learn',
                    'markasjunk2_move_spam'       => true,
                    'markasjunk2_move_ham'        => true,
                    'markasjunk2_spam_dir'        => "{$config['GUI_ROOT_DIR']}/plugins/SpamAssassin/sa-learn",
                    'markasjunk2_ham_dir'         => "{$config['GUI_ROOT_DIR']}/plugins/SpamAssassin/sa-learn",
                    'markasjunk2_filename'        => '%u__%t__',
                ]
            ]
        ],

        // Provide notifications for new emails
        // See https://github.com/roundcube/roundcubemail/tree/master/plugins/newmail_notifier
        'newmail_notifier'           => [
            'enabled' => false,
            'config'  => [
                'parameters' => [
                    'newmail_notifier_basic'           => true,
                    'newmail_notifier_sound'           => true,
                    'newmail_notifier_desktop'         => true,
                    'newmail_notifier_desktop_timeout' => 10
                ]
            ]
        ],

        // Provide a real-time Web chat based on JSXC
        // See https://github.com/jsxc/jsxc.roundcube
        // WARNING: You have to fullfit the JSXC requirements and add the
        // required parameters before enabling that plugin.
        'jsxc'                       => [
            'enabled'  => false,
            'composer' => [
                'repositories' => [
                    [
                        'type' => 'vcs',
                        'url'  => 'https://github.com/jsxc/jsxc.roundcube.git'
                    ]
                ],
                'require'      => [
                    'jsxc/jsxc' => '^3.0'
                ]
            ],
            'config'   => [
                'parameters' => [
                    'jsxc' => [
                        // Add your parameters there...
                    ]
                ]
            ]
        ],

        // Provide a way to write custom content on login page
        // See https://git.kolab.org/diffusion/RPK/
        'logon_page'                 => [
            'enabled'  => false,
            'composer' => [
                'repositories' => [
                    [
                        'type'    => 'path',
                        'url'     => "{$config['GUI_ROOT_DIR']}/data/persistent/plugins/RoundcubePlugins/roundcubemail-plugins-kolab/plugins/logon_page",
                        'options' => [
                            'symlink' => false
                        ]
                    ]
                ],
                'require'      => [
                    'kolab/logon_page' => '^3.4'
                ]
            ],
            'config'   => [
                'script'      => __DIR__ . '/config/scripts/configure-kolab.pl',
                'script_argv' => [
                    'preconfigure' => [
                        'roundcubemail-plugins-kolab-3.4.5'
                    ]
                ]
            ]
        ],

        // Provide a viewer for the open document format (odt, odp, ods)
        // See https://git.kolab.org/diffusion/RPK/
        'odfviewer'                  => [
            'enabled'  => false,
            'composer' => [
                'repositories' => [
                    [
                        'type'    => 'path',
                        'url'     => "{$config['GUI_ROOT_DIR']}/data/persistent/plugins/RoundcubePlugins/roundcubemail-plugins-kolab/plugins/odfviewer",
                        'options' => [
                            'symlink' => false
                        ]
                    ]
                ],
                'require'      => [
                    'kolab/odfviewer' => '^3.4'
                ]
            ],
            'config'   => [
                'script'      => __DIR__ . '/config/scripts/configure-kolab.pl',
                'script_argv' => [
                    'preconfigure' => [
                        'roundcubemail-plugins-kolab-3.4.5'
                    ]
                ]
            ]
        ],

        // Provide the password change feature
        // See https://github.com/roundcube/roundcubemail/tree/master/plugins/password
        'password'                   => [
            'enabled' => false,
            'config'  => [
                'parameters' => [
                    'password_minimum_length' => 6,
                    'password_algorithm'      => 'sha512-crypt',
                    'password_query'          => "UPDATE `{$config['DATABASE_NAME']}`.`mail_users` SET `mail_pass` = %P WHERE `mail_addr` = %u",
                    'password_crypt_rounds'   => 5000,
                    'password_crypt_hash'     => 'sha512',
                    'password_idn_ascii'      => true
                ]
            ]
        ],

        // Provide a viewer for the portable document format (pdf)
        // See https://git.kolab.org/diffusion/RPK/
        'pdfviewer'                  => [
            'enabled'  => false,
            'composer' => [
                'repositories' => [
                    [
                        'type'    => 'path',
                        'url'     => "{$config['GUI_ROOT_DIR']}/data/persistent/plugins/RoundcubePlugins/roundcubemail-plugins-kolab/plugins/pdfviewer",
                        'options' => [
                            'symlink' => false
                        ]
                    ]
                ],
                'require'      => [
                    'kolab/pdfviewer' => '^3.4'
                ]
            ],
            'config'   => [
                'script'      => __DIR__ . '/config/scripts/configure-kolab.pl',
                'script_argv' => [
                    'preconfigure' => [
                        'roundcubemail-plugins-kolab-3.4.5'
                    ]
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

        // Provide SpamAssassin user preferences management through Roundcube
        // See https://github.com/johndoh/roundcube-sauserprefs
        // WARNING: You need first install the i-MSCP SpamAssassin plugin,
        //          else, installation of that plugin will fail.
        'sauserprefs'                => [
            'enabled'  => false,
            'composer' => [
                'require' => [
                    'johndoh/sauserprefs' => '^1.17'
                ]
            ],
            'config'   => [
                'parameters' => [
                    'sauserprefs_db_dsnw'            => 'mysql://{DB_USER}:{DB_PASSWD}@{DB_HOST}:{DB_PORT}/{DB_NAME}',
                    // Please don't remove the default 'use_razor1' entry.
                    'sauserprefs_dont_override'      => [
                        'use_razor1'
                    ],
                    'sauserprefs_default_prefs'      => [
                        'required_score'                     => '5',
                        'rewrite_header Subject'             => '*****SPAM*****',
                        'ok_languages'                       => 'all',
                        'ok_locales'                         => 'all',
                        'fold_headers'                       => '1',
                        'add_header all Level'               => '_STARS(*)_',
                        'use_razor1'                         => '0',
                        'use_razor2'                         => '0',
                        'use_pyzor'                          => '0',
                        'use_dcc'                            => '0',
                        'use_bayes'                          => '0',
                        'skip_rbl_checks'                    => '1',
                        'report_safe'                        => '1',
                        'bayes_auto_learn'                   => '1',
                        'bayes_auto_learn_threshold_nonspam' => '0.1',
                        'bayes_auto_learn_threshold_spam'    => '12.0',
                        'use_bayes_rules'                    => '1'
                    ],
                    'sauserprefs_bayes_delete_query' => [
                        'DELETE FROM `bayes_seen` WHERE `id` IN (SELECT `id` FROM `bayes_vars` WHERE `username` = %u);',
                        'DELETE FROM `bayes_token` WHERE `id` IN (SELECT `id` FROM `bayes_vars` WHERE `username` = %u);',
                        'DELETE FROM `bayes_vars` WHERE `username` = %u;'
                    ]
                ],
                'script'     => __DIR__ . '/config/scripts/configure-sauserprefs.pl'
            ]
        ],

        // Provide the task management feature
        // See https://plugins.roundcube.net/packages/kolab/tasklist
        'tasklist'                   => [
            'enabled'  => false,
            'composer' => [
                'require' => [
                    'kolab/tasklist' => '~3.3.0'
                ]
            ],
            'config'   => [
                'parameters' => [
                    'tasklist_driver'     => 'database',
                    'tasklist_sort_col'   => '',
                    'tasklist_sort_order' => 'asc'
                ]
            ]
        ],

        // Detects vCard attachments and allows to add them to address book
        // See https://github.com/roundcube/roundcubemail/tree/master/plugins/vcard_attachments
        'vcard_attachments'          => [
            'enabled' => false
        ],

        // Adds an option to download all attachments to a message in one zip
        // file
        // See https://github.com/roundcube/roundcubemail/tree/master/plugins/zipdownload
        'zipdownload'                => [
            'enabled' => false,
            'config'  => [
                'parameters' => [
                    'zipdownload_attachments' => 1,
                    'zipdownload_selection'   => false,
                    'zipdownload_charset'     => 'UTF-8'
                ]
            ]
        ],

        //
        // Add your own Roundcube plugin definitions below
        //
    ]
];
