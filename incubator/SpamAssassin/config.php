<?php
/**
 * i-MSCP SpamAssassin plugin
 * Copyright (C) 2015-2017 Laurent Declercq <l.declercq@nuxwin.com>
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
    // SPAMD(8p) service configuration
    'spamd'          => array(
        // SPAMD(8p) unix user homedir
        //
        // Possible value: string
        'homedir' => '/var/lib/spamassassin',

        // Options passed-in to SPAMD(8p)
        //
        // Available placeholders:
        //  - {SPAMD_USER}: Replaced by SPAMD(8p) unix user
        //  - {SPAMD_GROUP}: Replaced by SPAMD(8p) unix user group
        //  - {SPAMD_HOMEDIR}: Replaced by SPAMD(8p) unix user homedir
        //
        // Possible value: string
        'options' => '--max-children=5 --sql-config --nouser-config --username={SPAMD_USER} --groupname={SPAMD_GROUP}'
            . ' --helper-home-dir={SPAMD_HOMEDIR} --socketpath=/var/run/spamassassin.sock --socketowner={SPAMD_USER}'
            . ' --socketgroup={SPAMD_GROUP} --socketmode=0666'
    ),

    // SPAMASS_MILTER(8) service configuration
    'spamass_milter' => array(
        // Policy for SPAM rejection
        //
        // - If set to -1, SPAM messages are always rejected.
        // - If set to 15, SPAM messages are only rejected when the score is equal or
        //   greater then 15. SPAM messages below that score are tagged as SPAM.
        // - If set to a value higher than 1000, SPAM messages are never
        //   rejected. SPAM messages are tagged as SPAM.
        //
        // Generally speaking, an ISP shouldn't automatically remove SPAM
        // messages. Decision should be left to end-user, hence the default
        // value that is a good compromise.
        //
        // Possible value: integer
        'spam_reject_policy'      => 15,

        // Ignores messages if the sender has authenticated via SMTP AUTH.
        //
        // Message from authenticated senders (SASL) will passthrough without
        // being scanned.
        //
        // Possible value: true, false
        'ignore_auth_sender_msgs' => false,

        // Ignore messages from listed networks
        //
        // Messages from listed networks will passthrough without being
        // scanned.
        //
        // Array where each element can be either:
        // - An IP address: nnn.nnn.nnn.nnn
        // - A CIDR network: nnn.nnn.nnn.nnn/nn
        // - A network/netmask pair: nnn.nnn.nnn.nnn/nnn.nnn.nnn.nnn
        // For instance: '127.0.0.1', '172.16.12.0/24', '10.0.0.0/255.0.0.0'
        //
        // Possible value: array
        'networks'                => array(),

        // Options passed-in to SPAMASS_MILTER(8)
        //
        // You can pass your own options to SPAMASS_MILTER(8), including flags
        // for SPAMC(1) as described in SPAMASS_MILTER(8)
        //
        // Don't add the -I, -i and -r SPAMASS_MILTER(8) options as these are managed
        // through the named options above.
        //
        // Possible value: string
        'options'                 => '-e localhost -f -u spamass-milter -- --socket=/var/run/spamassassin.sock',

        // SPAMASS_MILTER(8) socket path
        //
        // Possible value: string
        'socket_path'             => '/var/spool/postfix/spamass/spamass.sock',

        // SPAMASS_MILTER(8) socket owner
        //
        // Possible value: string
        'socket_owner'            => 'postfix:postfix',

        // SPAMASS_MILTER(8) socket mode
        //
        // Possible value: string
        'socket_mode'             => '0666'
    ),

    // SpamAssassin configuration options
    'spamassassin'   => array(
        // AWL plugin -- Normalize scores via auto-whitelist
        // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_AWL.html
        //
        // Note that this plugin is either enabled for all users or fully
        // disabled.
        'AWL'                      => array(
            // Possible values: true, false
            'enabled'          => true,

            // SA configuration files in which the plugin must be loaded.
            //
            // Possible value: string
            'config_file'      => '/etc/spamassassin/v310.pre',

            // Cronjob for cleaning up of AWL database
            // See man CRONTAB(5) for allowed values
            'cronjob_clean_db' => array(
                'minute' => '@daily',
                'hour'   => '',
                'day'    => '',
                'month'  => '',
                'dweek'  => ''
            )
        ),

        // Bayes plugin -- determine spammishness using a Bayesian classifier
        // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_Bayes.html
        'Bayes'                    => array(
            // Possible values: true, false
            'enabled'          => true,

            // Enforced mode
            //
            // Setting the value to TRUE will prevent users to act on that
            // plugin through their user preferences. Bayes SA plugin usage
            // will be enforced for all users.
            //
            // Possible value: true, false
            'enforced'         => false,

            // SA configuration file in which the plugin must be loaded.
            //
            // Possible value: string
            'config_file'      => '/etc/spamassassin/v320.pre',

            // Enable/Disable site-wide SpamAssassin Bayesian classifier
            //
            // When set to TRUE, the site-wide bayes database is enabled.
            // This allow to share the bayesian database with all users.
            //
            // Note that setting the value to TRUE will prevent users to act
            // on some aspects of that plugin through their user preferences.
            //
            // Possible values: true, false
            'site_wide'        => false,

            // Cronjob for sa-learn
            // See man CRONTAB(5) for allowed values
            'cronjob_sa_learn' => array(
                'minute' => '0',
                'hour'   => '*/12',
                'day'    => '*',
                'month'  => '*',
                'dweek'  => '*'
            ),

            // Cronjob for cleaning up of bayes database
            // See man CRONTAB(5) for allowed values
            'cronjob_clean_db' => array(
                'minute' => '@daily',
                'hour'   => '',
                'day'    => '',
                'month'  => '',
                'dweek'  => ''
            )
        ),

        // DCC plugin -- perform DCC check of messages
        // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_DCC.html
        //
        // You must first install DCC which is not provided by default.
        // See https://www.dcc-servers.net/dcc/INSTALL.html
        'DCC'                      => array(
            // Possible values: true, false
            'enabled'     => false,

            // Enforced mode
            //
            // Setting the value to TRUE will prevent users to act on that
            // plugin through their user preferences. DCC SA plugin usage
            // will be enforced for all users.
            //
            // Possible value: true, false
            'enforced'    => false,

            // SA configuration files in which the plugin must be loaded.
            //
            // Possible value: string
            'config_file' => '/etc/spamassassin/v310.pre'
        ),

        // DKIM plugin - perform DKIM verification tests
        // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_DKIM.html
        //
        // This plugin is either enabled for all users or fully disabled.
        //
        // You shouldn't enable that plugin if you also use the i-MSCP OpenDKIM plugin.
        'DKIM'                     => array(
            // Possible values: true, false
            'enabled'     => false,

            // SA configuration file in which the plugin must be loaded.
            //
            // Possible value: string
            'config_file' => '/etc/spamassassin/v312.pre'
        ),

        // DecodeShortURLs plugin -- Expand shortened URLs
        // See https://github.com/smfreegard/DecodeShortURLs
        //
        // Note that this plugin is either enabled for all users or fully disabled.
        'DecodeShortURLs'          => array(
            // Possible values: true, false
            'enabled' => true
        ),

        // Heinlein Support SpamAssassin ruleset
        // See https://www.heinlein-support.de/blog/news/aktuelle-spamassassin-regeln-von-heinlein-support/
        'heinlein_support_ruleset' => array(
            // Possible value: true, false
            'enabled'     => true,

            // Cronjob sleep timer (in seconds)
            // Possible value: integer
            'sleep_timer' => 600,

            // sa-update channel
            //
            // Possible value: string
            'channel'     => 'spamassassin.heinlein-support.de'
        ),

        // iXhash2 SpamAssassin plugin - perform iXhash2 check of messages
        // See http://mailfud.org/iXhash2/
        //
        // Note that this plugin is either enabled for all users or fully
        // disabled.
        'iXhash2'                  => array(
            // Possible values: true, false
            'enabled' => true
        ),

        // Pyzor plugin -- perform Pyzor check of messages
        // https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_Pyzor.html
        'Pyzor'                    => array(
            // Possible values: true, false
            'enabled'     => true,

            // Enforced mode
            //
            // Setting the value to TRUE will prevent users to act on that
            // plugin through their user preferences. Pyzor SA plugin usage
            // will be enforced for all users.
            //
            // Possible value: true, false
            'enforced'    => false,

            // SA configuration file in which the plugin must be loaded.
            //
            // Possible value: string
            'config_file' => '/etc/spamassassin/v310.pre'
        ),

        // Razor2 plugin -- perform Razor check of messages
        // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_Razor2.html
        'Razor2'                   => array(
            // Possible values: true, false
            'enabled'     => true,

            // Enforced mode
            //
            // Setting the value to TRUE will prevent users to act on that
            // plugin through their user preferences. Razor2 SA plugin usage
            // will be enforced for all users.
            //
            // Possible value: true, false
            'enforced'    => false,

            // SA configuration file in which the plugin must be loaded.
            //
            // Possible value: string
            'config_file' => '/etc/spamassassin/v310.pre'
        ),

        // Rule2XSBody plugin -- speed up SpamAssassin by compiling regexps
        // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_Rule2XSBody.html
        'Rule2XSBody'              => array(
            // Possible values: true, false
            'enabled'     => true,

            // SA configuration file in which the plugin must be loaded.
            //
            // Possible value: string
            'config_file' => '/etc/spamassassin/sa-compile.pre'
        ),

        // SPF plugin -- perform SPF verification tests
        // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_SPF.html
        //
        // Note that this plugin is either enabled for all users or fully
        // disabled.
        //
        // You shouldn't enable that plugin if you also use the PolicydSPF
        // i-MSCP plugin.
        'SPF'                      => array(
            // Possible values: true, false
            'enabled'     => false,

            // SA configuration file in which the plugin must be loaded.
            //
            // Possible value: string
            'config_file' => '/etc/spamassassin/init.pre'
        ),

        // TextCat plugin -- language guesser
        // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_TextCat.html
        'TextCat'                  => array(
            // possible values: true, false
            'enabled'     => true,

            // SA configuration files in which the plugin must be loaded.
            //
            // Possible value: string
            'config_file' => '/etc/spamassassin/v310.pre'
        ),

        // RBL checks (DNSEval and URIDNSBL SA plugins)
        // https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_DNSEval.html
        // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_URIDNSBL.html
        //
        // You shouldn't enable those plugins if you already use the i-MSCP
        // PolicydWeight and/or Postscreen plugins.
        'rbl_checks'               => array(
            // Possible value: true, false
            'enabled'  => false,

            // Enforced mode
            //
            // Setting the value to TRUE will prevent users to disable/enable
            // RBL checks through their user preferences. RBL checks will be
            // enforced for all users.
            //
            // Possible value: true, false
            'enforced' => false
        )
    ),

    // Roundcube configuration
    //
    // Only relevant if you use the Roundcube Webmail
    'roundcube'      => array(
        // MarkAsJunk2 Roundcube plugin
        // See https://github.com/JohnDoh/Roundcube-Plugin-Mark-as-Junk-2
        //
        // Make users able to mark their mails as SPAM|HAM for SA learning
        // Requires the SA Bayes plugin
        'markasjunk2' => array(
            // Possible value: true, false
            'enabled' => true
        ),

        // SAUserPrefs Roundcube plugin
        // See https://github.com/JohnDoh/Roundcube-Plugin-SpamAssassin-User-Prefs-SQL
        //
        // Make users able to customize SpamAssassin behavior through their own
        // user preferences
        'sauserprefs' => array(
            // Possible values: true, false
            'enabled'                   => true,

            // Protected SA user preferences
            //
            // Users won't be able to acts on preferences listed in
            // that configuration parameter.
            //
            // See the sauserprefs plugin documentation for further details.
            //
            // Possible value: array
            'sauserprefs_dont_override' => array(
                // razor1 support is officially deprecated.
                // There is no reason to show it in plugin.
                'use_razor1'
            )
        )
    )
);
