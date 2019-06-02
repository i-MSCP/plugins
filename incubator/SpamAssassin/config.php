<?php
/**
 * i-MSCP SpamAssassin plugin
 * Copyright (C) 2015-2019 Laurent Declercq <l.declercq@nuxwin.com>
 * Copyright (C) 2013-2016 Rene Schuster <mail@reneschuster.de>
 * Copyright (C) 2013-2018 Sascha Bay <info@space2place.de>
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
    // SPAMASSASSIN(1p) - extensible email filter used to identify spam
    'spamassassin'   => [
        // List of required distribution packages
        'dist_packages'       => [
            'spamassassin'
        ],
        // Unix user
        'user'                => 'debian-spamd',
        // Unix group
        'group'               => 'debian-spamd',
        // Homedir
        'homedir'             => '/var/lib/spamassassin',
        // Service name
        'service'             => 'spamassassin',
        // SPAMD(8p) - daemonized version of spamassassin
        'spamd'               => [
            // Daemon options
            //
            // Available placeholders:
            //  - {SA_USER}    : SpamAssassin unix user
            //  - {SA_GROUP}   : SpamAssassin unix group
            //  - {SA_HOMEDIR} : SpamAssassin homedir
            'options' => '--max-children=5 --sql-config --nouser-config --username={SA_USER} --groupname={SA_GROUP}'
                . ' --helper-home-dir={SA_HOMEDIR} --socketpath=/var/run/spamassassin.sock --socketowner={SA_USER}'
                . ' --socketgroup={SA_GROUP} --socketmode=0666'
        ],
        // Global user preferences
        // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Conf.html#USER-PREFERENCES
        'user_preferences'    => [
            // Scoring options
            'required_score'              => '5.0',
            // Basic message tagging options
            'rewrite_header'              => 'Subject *****SPAM*****',
            'report_safe'                 => '1',
            // Language options
            'ok_locales'                  => 'all',
            // Network test options
            'skip_rbl_checks'             => '0', # Turn off the DNSeval plugin
            'dns_available'               => 'yes',
            // Learning options
            'use_learner'                 => '1',
            'use_bayes'                   => '1',
            'use_bayes_rules'             => '1',
            'bayes_auto_learn'            => '1',
            'bayes_sql_override_username' => '$GLOBAL'
            //
            // You can add your own user preferences below
            //
        ],
        // Administrator settings
        // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Conf.html#ADMINISTRATOR-SETTINGS
        'admin_settings'      => [
            // Settings for the the bayes_store_module
            // See https://svn.apache.org/repos/asf/spamassassin/branches/3.4/sql/README.bayes
            'bayes_store_module'           => 'Mail::SpamAssassin::BayesStore::MySQL',
            'bayes_sql_dsn'                => '{SA_DSN}',
            'bayes_sql_username'           => '{SA_DATABASE_USER}',
            'bayes_sql_password'           => '{SA_DATABASE_PASSWORD}',
            // Load users' score files from an SQL database
            // See https://svn.apache.org/repos/asf/spamassassin/branches/3.4/sql/README 
            'user_scores_dsn'              => '{SA_DSN}',
            'user_scores_sql_username'     => '{SA_DATABASE_USER}',
            'user_scores_sql_password'     => '{SA_DATABASE_PASSWORD}',
            // Custom SQL query for per-user (mail account) user preferences.
            // If no user preferences are found for the mail account, the
            // global user preferences will be used, and if no global user
            // preferences are found, the default user preferences, as set in
            // the SpamAssassin configuration files, or hardcoded, will be used.
            // See https://wiki.apache.org/spamassassin/UsingSQL
            'user_scores_sql_custom_query' => "SELECT `preference`, `value` FROM _TABLE_ WHERE `username` IN(_USERNAME_, '\$GLOBAL') ORDER BY `username` ASC",
            //
            // You can add your own administrator settings below
            //
        ],
        'shell_commands'      => [
            'configure'   => [
                <<<EOT
/usr/bin/mysql -e 'CREATE DATABASE IF NOT EXISTS `{DATABASE_NAME}`'
/usr/bin/mysql '{SA_DATABASE_NAME}' < /usr/share/doc/spamassassin/sql/userpref_mysql.sql
/usr/bin/mysql '{DATABASE_NAME}' < /usr/share/doc/spamassassin/sql/bayes_mysql.sql
EOT
            ],
            'deconfigure' => [
                <<<EOT
/usr/bin/mysql '{SA_DATABASE_NAME}' -e 'DROP TABLE IF EXISTS `userpref`,`bayes_expire`,`bayes_global_vars`,`bayes_seen`,`bayes_token`,`bayes_vars`'
/usr/bin/mysql -e 'DROP DATABASE IF EXISTS `{DATABASE_NAME}`'
EOT
            ]
        ],
        // SA-UPDATE(1p) - SpamAssassin rule updates
        'sa-update'           => [
            // Enable or disable automatic rule updates
            // Warning: You shouldn't disable automatic rule updates.
            'enabled'     => true,
            // Path to GPG key
            'gpg_path'    => '/usr/share/spamassassin/GPG.KEY',
            // Sleep range for rule updates
            // Only relevant for 3rd-party ruleset
            'sleep_range' => 601,
            // SA 3rd-party rulesets
            'rulesets'    => [
                // Heinlein ruleset
                // See https://www.heinlein-support.de/blog/news/aktuelle-spamassassin-regeln-von-heinlein-support/
                'heinlein-support' => [
                    'enabled' => false,
                    'channel' => 'spamassassin.heinlein-support.de'
                ],
                // ZMI_GERMAN ruleset
                // See http://sa.zmi.at/
                'zmi_german'       => [
                    'enabled' => false,
                    'channel' => 'sa.zmi.at',
                    'gpg_id'  => '0xAEC28AD940F74481',
                    'gpg_uri' => 'https://sa.zmi.at/sa-update-german/GPG.KEY'
                ]
            ]
        ],

        // SpamAssassin plugin definitions
        //
        // For the description of the configuration parameters, look at the
        // plugin configuration definition template inside the plugin README.md
        // file.
        'plugins_definitions' => [
            // AWL plugin - Normalize scores via auto-whitelist
            // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_AWL.html
            'Mail::SpamAssassin::Plugin::AWL'             => [
                'enabled'          => false,
                'load_file'        => '/etc/spamassassin/v310.pre',
                'user_preferences' => [
                    'user_awl_sql_override_username'    => '$GLOBAL',
                    'auto_whitelist_distinguish_signed' => '1',
                    'use_auto_whitelist'                => '1'
                ],
                'admin_settings'   => [
                    'user_awl_dsn'          => '{SA_DSN}',
                    'user_awl_sql_username' => '{SA_DATABASE_USER}',
                    'user_awl_sql_password' => '{SA_DATABASE_PASSWORD}'
                ],
                'shell_commands'   => [
                    'configure'   => [
                        <<<EOT
/usr/bin/mysql '{SA_DATABASE_NAME}' < /usr/share/doc/spamassassin/sql/awl_mysql.sql
EOT
                    ],
                    'deconfigure' => [
                        <<<EOT
/usr/bin/mysql '{SA_DATABASE_NAME}' -e 'DROP TABLE IF EXISTS `awl`
EOT
                    ],
                ],
                'cronjobs'         => [
                    'sa-awl-clean-db' => [
                        'COMMAND' => 'perl {PLUGIN_DIRS}/SpamAssassin/cronjobs/sa-awl-clean-db >/dev/null 2>&1'
                    ]
                ],
                'conflicts' => [
                    'Mail::SpamAssassin::Plugin::AWL::TxRep'
                ]
            ],

            // TxRep plugin - Normalize scores with sender reputation records
            // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_TxRep.html
            'Mail::SpamAssassin::Plugin::TxRep'             => [
                'enabled'          => false,
                'load_file'        => '/etc/spamassassin/v341.pre',
                'user_preferences' => [
                    'use_txrep' => '1',
                    'user_awl_sql_override_username'    => '$GLOBAL',
                ],
                'admin_settings'   => [
                    'user_awl_dsn'          => '{SA_DSN}',
                    'user_awl_sql_username' => '{SA_DATABASE_USER}',
                    'user_awl_sql_password' => '{SA_DATABASE_PASSWORD}'
                ],
                'shell_commands'   => [
                    'configure'   => [
                        <<<EOT
/usr/bin/mysql '{SA_DATABASE_NAME}' < /usr/share/doc/spamassassin/sql/txrep_mysql.sql
EOT
                    ],
                    'deconfigure' => [
                        <<<EOT
/usr/bin/mysql '{SA_DATABASE_NAME}' -e 'DROP TABLE IF EXISTS `txrep`
EOT
                    ],
                ],
                'cronjobs'         => [
                    'sa-awl-clean-db' => [
                        'COMMAND' => 'perl {PLUGIN_DIRS}/SpamAssassin/cronjobs/sa-awl-clean-db >/dev/null 2>&1'
                    ]
                ],
                'conflicts' => [
                    'Mail::SpamAssassin::Plugin::AWL::AWL'
                ]
            ],

            // Bayes plugin -- determine spammishness using a Bayesian classifier
            // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_Bayes.html
            'Mail::SpamAssassin::Plugin::Bayes'           => [
                'enabled'          => false,
                'load_file'        => '/etc/spamassassin/v320.pre',
                'user_preferences' => [
                    'bayes_auto_learn_threshold_nonspam' => '0.1',
                    'bayes_auto_learn_threshold_spam'    => '12.0',
                ],
                'cronjobs'         => [
                    'sa-bayes-sa-learn' => [
                        'MINUTE'  => '0',
                        'HOUR'    => '*/12',
                        'COMMAND' => 'nice -n 10 ionice -c2 -n5 perl {PLUGIN_DIRS}/SpamAssassin/cronjobs/sa-bayes-sa-learn >/dev/null 2>&1'
                    ],
                    'sa-bayes-clean-db' => [
                        'COMMAND' => 'perl {PLUGIN_DIRS}/SpamAssassin/cronjobs/sa-bayes-clean-db >/dev/null 2>&1'
                    ]
                ]
            ],

            // DCC plugin -- perform DCC check of messages
            // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_DCC.html
            // WARNING: You must first install DCC which is not provided by
            // default. See https://www.dcc-servers.net/dcc/INSTALL.html
            'Mail::SpamAssassin::Plugin::DCC'             => [
                'enabled'          => false,
                'load_file'        => '/etc/spamassassin/v310.pre',
                'user_preferences' => [
                    'use_dcc' => '1'
                ]
            ],

            // DKIM plugin - perform DKIM verification tests
            // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_DKIM.html
            // You shouldn't enable this plugin if you make use of the i-MSCP OpenDKIM plugin.
            'Mail::SpamAssassin::Plugin::DKIM'            => [
                'enabled'   => false,
                'load_file' => '/etc/spamassassin/v312.pre'
            ],

            // DecodeShortURLs 3rd-party plugin -- Expand shortened URLs
            // See https://github.com/smfreegard/DecodeShortURLs
            'Mail::SpamAssassin::Plugin::DecodeShortURLs' => [
                'enabled'        => false,
                'load_file'      => '/etc/spamassassin/00_imscp.cf',
                'shell_commands' => [
                    'configure'   => [
                        <<<EOT
cp {PLUGINS_DIR}/SpamAssassin/plugins/spamassassin/DecodeShortURLs/DecodeShortURLs.pm /etc/spamassassin
cp {PLUGINS_DIR}/SpamAssassin/plugins/spamassassin/DecodeShortURLs/DecodeShortURLs.cf /etc/spamassassin
EOT
                    ],
                    'deconfigure' => [
                        <<<EOT
[ ! -f /etc/spamassassin/DecodeShortURLs.pm ] || rm /etc/spamassassin/DecodeShortURLs.pm
[ ! -f /etc/spamassassin/DecodeShortURLs.cf ] || rm /etc/spamassassin/DecodeShortURLs.cf
EOT
                    ]
                ]
            ],

            // iXhash2 3rd-party plugin - perform iXhash2 check of messages
            // See http://mailfud.org/iXhash2/
            'Mail::SpamAssassin::Plugin::iXhash2'         => [
                'enabled'        => false,
                'load_file'      => '/etc/spamassassin/00_imscp.cf',
                'shell_commands' => [
                    'configure'   => [
                        <<<EOT
cp {PLUGINS_DIR}/SpamAssassin/plugins/spamassassin/iXhash2/iXhash2.pm /etc/spamassassin
cp {PLUGINS_DIR}/SpamAssassin/plugins/spamassassin/iXhash2/iXhash2.cf /etc/spamassassin
EOT
                    ],
                    'deconfigure' => [
                        <<<EOT
[ ! -f /etc/spamassassin/iXhash2.pm ] || rm /etc/spamassassin/iXhash2.pm
[ ! -f /etc/spamassassin/iXhash2.cf ] || rm /etc/spamassassin/iXhash2.cf
EOT
                    ]
                ]
            ],

            // Pyzor plugin -- perform Pyzor check of messages
            // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_Pyzor.html
            'Mail::SpamAssassin::Plugin::Pyzor'           => [
                'enabled'          => false,
                'load_file'        => '/etc/spamassassin/v310.pre',
                'dist_packages'    => [
                    'pyzor'
                ],
                'user_preferences' => [
                    'use_pyzor' => 1
                ],
                'shell_commands'   => [
                    'configure'   => [
                        '[ -d "{SA_HOMEDIR}/.pyzor" ] || /bin/su --login {SA_USER} --shell /bin/sh --command "/usr/bin/pyzor ping"'
                    ],
                    'deconfigure' => [
                        '[ ! -d "{SA_HOMEDIR}/.pyzor" ] || rm -R {SA_HOMEDIR}/.pyzor',
                    ]
                ]
            ],

            // Razor2 plugin -- perform Razor check of messages
            // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_Razor2.html
            'Mail::SpamAssassin::Plugin::Razor2'          => [
                'enabled'          => false,
                'load_file'        => '/etc/spamassassin/v310.pre',
                'dist_packages'    => [
                    'razor'
                ],
                'cronjobs'         => [
                    'sa-razor-discover' => [
                        'MINUTE'  => '@weekly',
                        'COMMAND' => 'perl {PLUGIN_DIRS}/SpamAssassin/cronjobs/sa-razor-discover >/dev/null 2>&1'
                    ]
                ],
                'user_preferences' => [
                    'use_razor2' => 1
                ],
                'shell_commands'   => [
                    'configure'   => [
                        '[ -d "{SA_HOMEDIR}/.razor" ] || /bin/su --login {SA_USER} --shell /bin/sh --command "/usr/bin/razor-admin -create"',
                        '[ -d "{SA_HOMEDIR}/.razor/identity" ] || /bin/su --login {SA_USER} --shell /bin/sh --command "/usr/bin/razor-admin -register"'
                    ],
                    'deconfigure' => [
                        '[ ! -d "{SA_HOMEDIR}/.razor" ] || rm -R {SA_HOMEDIR}/.razor'
                    ]
                ]
            ],

            // Rule2XSBody plugin - speed up SpamAssassin by compiling regexps
            // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_Rule2XSBody.html
            'Mail::SpamAssassin::Plugin::Rule2XSBody'     => [
                'enabled'   => false,
                'load_file' => '/etc/spamassassin/sa-compile.pre'
            ],

            // Mail::SpamAssassin::Plugin::SpamCop - perform SpamCop reporting of messages
            // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_SpamCop.html
            'Mail::SpamAssassin::Plugin::SpamCop'         => [
                'enabled'   => false,
                'load_file' => '/etc/spamassassin/v310.pre'
            ],

            // SPF plugin -- perform SPF verification tests
            // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_SPF.html
            //
            // Warning: You shouldn't enable that plugin if you make use of the i-MSCP PolicydSPF
            // plugin.
            'Mail::SpamAssassin::Plugin::SPF'             => [
                'enabled'   => false,
                'load_file' => '/etc/spamassassin/init.pre'
            ],

            // TextCat - TextCat language guesser
            // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_TextCat.html
            'Mail::SpamAssassin::Plugin::TextCat'         => [
                'enabled'   => false,
                'load_file' => '/etc/spamassassin/v310.pre',
            ],

            // DNSEVAL - look up URLs against DNS blocklists
            // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_DNSEval.html
            'Mail::SpamAssassin::Plugin::DNSEval'         => [
                'enabled'   => false,
                'load_file' => '/etc/spamassassin/v320.pre'
            ],

            // URIDNSBL - look up URLs against DNS blocklists
            // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_URIDNSBL.html
            'Mail::SpamAssassin::Plugin::URIDNSBL'        => [
                'enabled'          => false,
                'load_file'        => '/etc/spamassassin/init.pre',
                'user_preferences' => [
                    'skip_uribl_checks' => 1
                ]
            ],

            //
            // You can add your own plugin definitions below. A documented
            // template for plugin definitions is provided in the plugin
            // README.md file.
            //
        ]
    ],

    // SPAMASS_MILTER(8) - sendmail milter for passing emails through SpamAssassin
    'spamass-milter' => [
        // List of required distribution packages
        'dist_packages'                  => [
            'spamc', 'spamass-milter'
        ],
        // service name
        'service'                        => 'spamass-milter',
        // Policy for SPAM rejection
        //
        // - If set to -1, SPAM messages are always rejected.
        // - If set to 15, SPAM messages are only rejected when the score is
        //   equal or greater than 15. SPAM messages below that score are
        //   tagged as SPAM.
        // - If set to a value higher than 1000, SPAM messages are never
        //   rejected. SPAM messages are tagged as SPAM.
        //
        // Generally speaking, an ISP shouldn't automatically remove SPAM
        // messages. Decision should be left to end-user, hence the default
        // value that is a good compromise.
        'spam_reject_policy'             => 15,
        // Ignores messages if the sender has authenticated via SMTP AUTH.
        //
        // If set to TRUE messages from authenticated senders (SASL) will
        // passthrough without being scanned.
        'ignore_auth_sender_msgs'        => false,
        // Ignore messages from listed networks
        //
        // Messages from listed networks will passthrough without being
        // scanned.
        //
        // An array where each element can be either:
        // - An IP address: nnn.nnn.nnn.nnn
        // - A CIDR network: nnn.nnn.nnn.nnn/nn
        // - A network/netmask pair: nnn.nnn.nnn.nnn/nnn.nnn.nnn.nnn
        // For instance: '127.0.0.1', '172.16.12.0/24', '10.0.0.0/255.0.0.0'
        'networks'                       => [],
        // Daemon options
        //
        // You can pass your own options to SPAMASS_MILTER(8), including
        // options for SPAMC(1) as described in SPAMASS_MILTER(8).
        //
        // Don't add the -I, -i and -r SPAMASS_MILTER(8) options as these are
        // managed through the named options above.
        'options'                        => '-e localhost -f -u spamass-milter -- --max-size=2048000 --socket=/var/run/spamassassin.sock',
        // Daemon socket path
        'socket_path'                    => '/var/spool/postfix/spamass/spamass.sock',
        // Daemon socket owner
        'socket_owner'                   => 'postfix:postfix',
        // Daemon socket mode
        'socket_mode'                    => '0660',
        // MILTER server endpoint (default: unix:/spamass/spamass.sock)
        //
        // The SpamAssassin MILTER (mail filter) application server endpoint
        'postfix_milter_server_endpoint' => 'unix:/spamass/spamass.sock',
        // MILTER connect timeout (default 10s)
        //
        // The time limit for connecting to the SpamAssassin MILTER (mail
        // filter) application, and for negotiating protocol options.
        //
        // Specify a non-zero time value (an integral value plus an
        // optional one-letter suffix that specifies the time unit).
        //
        // Available time units are in order:
        //  - s (seconds),
        //  - m (minutes),
        //  - h (hours),
        //  - d (days),
        //  - w (weeks).
        //
        // The default time unit is s (seconds).
        // See http://www.postfix.org/postconf.5.html#milter_connect_timeout
        'postfix_milter_connect_timeout' => '10s',
        // Milter default action (default accept)
        //
        // The default action to be taken when the SpamAssassin MILTER
        //(mail filter) application is unavailable or mis-configured.
        // Specify one of the following:
        //
        // accept, reject, tempfail or quarantine.
        //
        // Warning: Only relevant with Postfix >= 3 as older versions don't
        // support per MILTER application settings. For older Postfix,
        // versions, the value will be the default one, that is: tempfail
        //
        // See http://www.postfix.org/postconf.5.html#milter_default_action
        // for accepted values.
        'postfix_milter_default_action'  => 'accept'
    ]
];
