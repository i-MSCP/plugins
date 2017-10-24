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

use iMSCP_Registry as Registry;

$pluginDir = Registry::get('config')['PLUGINS_DIR'] . '/RoundcubePlugins';

return [
    // Roundcube plugin definitions
    // See the README.md file inside the plugin archive for
    // further details.
    'plugins' => [
        // Part of RoundCube distribution
        'additional_message_headers' => [
            'enabled' => true,
            'config'  => [
                'file'       => 'config.inc.php',
                'parameters' => [
                    'additional_message_headers' => [
                        'X-Remote-Browser'   => "{\$_SERVER['HTTP_USER_AGENT']}",
                        'X-Originating-IP'   => "[{\$_SERVER['REMOTE_ADDR']}]",
                        'X-RoundCube-Server' => "{\$_SERVER['SERVER_ADDR']}",
                    ]
                ]
            ]
        ],

        // Part of RoundCube distribution
        'archive'                    => [
            'enabled' => true
        ],

        // Part of Kolab distribution
        'calendar'                   => [
            'enabled'  => true,
            'git'      => [
                'repository' => 'https://git.kolab.org/diffusion/RPK/roundcubemail-plugins-kolab.git',
                'target_dir' => "$pluginDir/roundcube-plugins/kolab"
            ],
            'composer' => [
                'repositories' => [
                    'type' => 'path',
                    'url'  => "$pluginDir/roundcube-plugins/kolab/plugins/calendar",
                ],
                'require'      => [
                    'kolab/calendar' => '^3.3.0'
                ]
            ],
            'config'   => [
                'file'       => 'config.inc.php',
                'parameters' => [
                    'calendar_driver'       => 'database',
                    'calendar_default_view' => 'agendaDay'
                ]
            ]
        ],
        /*
                'contextmenu'      => [
                    'enabled' => false,
                ],
        */
        // Part of RoundCube distribution
        'emoticons'                  => [
            'enabled' => true,
            'config'  => [
                'file'       => 'config.inc.php',
                'parameters' => [
                    'emoticons_display' => false,
                    'emoticons_compose' => true
                ]
            ]
        ],

        // Part of Kolab distribution
        'logon_page'                 => [
            'enabled'  => true,
            'git'      => [
                'repository' => 'https://git.kolab.org/diffusion/RPK/roundcubemail-plugins-kolab.git',
                'target_dir' => "$pluginDir/roundcube-plugins/kolab"
            ],
            'composer' => [
                'repositories' => [
                    'type' => 'path',
                    'url'  => "$pluginDir/roundcube-plugins/kolab/plugins/logon_page"
                ],
                'require'      => [
                    'kolab/logon_page' => '^3.3.0'
                ]
            ]
        ],

        /*
                // Part of RoundCube distribution
                'managesieve'      => [
                    'enabled' => false,
                    'config'  => [
                        'file'       => 'config.inc.php',
                        'parameters' => [
                            'managesieve_vacation'    => '1',
                            'managesieve_script_name' => 'managesieve'
                        ]
                    ]
                ],
        */

        // Part of RoundCube distribution
        'newmail_notifier'           => [
            'enabled' => true,
            'config'  => [
                'file'       => 'config.inc.php',
                'parameters' => [
                    'newmail_notifier_basic'           => true,
                    'newmail_notifier_sound'           => true,
                    'newmail_notifier_desktop'         => true,
                    'newmail_notifier_desktop_timeout' => 10
                ]
            ]
        ],

        // Part of Kolab distribution
        'odfviewer'                  => [
            'enabled'  => true,
            'git'      => [
                'repository' => 'https://git.kolab.org/diffusion/RPK/roundcubemail-plugins-kolab.git',
                'target_dir' => "$pluginDir/roundcube-plugins/kolab"
            ],
            'composer' => [
                'repositories' => [
                    'type' => 'path',
                    'url'  => "$pluginDir/roundcube-plugins/kolab/plugins/odfviewer"
                ],
                'require'      => [
                    'kolab/odfviewer' => '^3.3.0'
                ]
            ]
        ],

        // Part of Kolab distribution
        'pdfviewer'                  => [
            'git'      => [
                'repository' => 'https://git.kolab.org/diffusion/RPK/roundcubemail-plugins-kolab.git',
                'target_dir' => "$pluginDir/roundcube-plugins/kolab"
            ],
            'enabled'  => true,
            'composer' => [
                'repositories' => [
                    'type' => 'path',
                    'url'  => "$pluginDir/roundcube-plugins/kolab/plugins/pdfviewer"
                ],
                'require'      => [
                    'kolab/pdfviewer' => '^3.3.0'
                ]
            ]
        ],

        // Part of RoundCube distribution
        'password'                   => [
            'enabled' => false,
            'config'  => [
                'file'       => 'config.inc.php',
                'parameters' => [
                    'password_confirm_current'  => true,
                    'password_minimum_length'   => 6,
                    'password_require_nonalpha' => false,
                    'password_force_new_user'   => false
                ]
            ]
        ],

        /*
        'rcguard' => [
            'enabled' => false,
            'config'  => [
                'file'       => 'config.inc.php',
                'parameters' => [
                    'recaptcha_publickey'  => '',
                    'recaptcha_privatekey' => '',
                    'failed_attempts'      => 3,
                    'expire_time'          => 30,
                    'recaptcha_https'      => false
                ]
            ]
        ],
        */

        'tasklist'          => [
            'enabled'  => true,
            'git'      => [
                'repository' => 'https://git.kolab.org/diffusion/RPK/roundcubemail-plugins-kolab.git',
                'target_dir' => "$pluginDir/roundcube-plugins/kolab"
            ],
            'composer' => [
                'repositories' => [
                    'type' => 'path',
                    'url'  => "$pluginDir/roundcube-plugins/kolab/plugins/tasklist"
                ],
                'require'      => [
                    'kolab/tasklist' => '^3.3.0'
                ]
            ],
            'config'   => [
                'file'       => 'config.inc.php',
                'parameters' => [
                    'tasklist_driver'     => 'database',
                    'tasklist_sort_col'   => '',
                    'tasklist_sort_order' => 'asc',
                ]
            ]
        ],

        // Part of rc distribution
        'vcard_attachments' => [
            'enabled' => true,
        ],

        // Part of rc distribution
        'zipdownload'       => [
            'enabled' => true,
            'config'  => [
                'file'       => 'config.inc.php',
                'parameters' => [
                    'zipdownload_attachments' => 1,
                    'zipdownload_selection'   => false,
                    'zipdownload_charset'     => 'ISO-8859-1'
                ]
            ]
        ]
    ]
];
