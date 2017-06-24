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
    // SPAMD(8p) service configuration options
    'spamd'          => array(
        'options' => '-m 5 -q -x -u debian-spamd -g debian-spamd -H /var/lib/spamassassin'
            . ' --socketpath=/var/run/spamassassin.sock --socketowner=debian-spamd --socketgroup=debian-spamd'
            . ' --socketmode=0666'
    ),

    // SPAMASS_MILTER(8) service configuration options
    'spamass_milter' => array(
        // Policy for spam rejection
        //
        // If set to -1, mails are always rejected when they are detected as SPAM.
        // If set to 15, mails are only rejected when the score is equal or greater then 15. Mails below that score
        // are not rejected but tagged as SPAM.
        // If you don't want to reject any mails, set a value higher than 1000.
        'spam_reject_policy'      => 15,

        // Ignores messages if the sender has authenticated via SMTP AUTH.
        // Possible value: true, false
        'ignore_auth_sender_msgs' => true,

        // Don't scan listed networks
        //
        // Mails will be passed through without being scanned if the
        // originating IP is listed in networks.
        //
        // Networks is a comma-separated list, where each element can be either:
        // - An IP address: nnn.nnn.nnn.nnn
        // - A CIDR network: nnn.nnn.nnn.nnn/nn
        // - A network/netmask pair: nnn.nnn.nnn.nnn/nnn.nnn.nnn.nnn
        //
        // For instance: '127.0.0.1', '172.16.12.0/24', '10.0.0.0/255.0.0.0'
        'networks'                => array(
            '127.0.0.1'
        ),

        // SPAMASS_MILTER(8) configuration options
        // You can pass your own options to SPAMASS_MILTER(8), including flags for
        // SPAMC(1) as described in SPAMASS_MILTER(8)
        // Warning: Don't add the -I, -i and -r SPAMC(1) options here as these
        // are managed using named options above.
        'options'                 => '-e -f -u spamass-milter -- -U /var/run/spamassassin.sock',

        // Socket path
        'socket_path'             => '/var/spool/postfix/spamass/spamass.sock',

        // Socket owner
        'socket_owner'            => 'postfix:postfix',

        // Socket mode
        'socket_mode'             => '0666'
    ),

    // SpamAssassin configuration options
    'spamassassin'   => array(
        // AWL plugin -- Normalize scores via auto-whitelist
        // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_AWL.html
        'AWL'                      => array(
            // Possible values: true, false
            'enabled'          => false,

            // Configuration file(s) where the plugin must be loaded
            'config_file'      => '/etc/spamassassin/v310.pre',

            // Cronjob for cleaning up of AWL database
            // See man CRONTAB(5) for allowed values
            'cronjob_clean_db' => array(
                'minute' => '@daily',
                'hour'   => '',
                'day'    => '',
                'month'  => '',
                'dweek'  => ''
            ),
        ),

        // Bayes plugin -- determine spammishness using a Bayesian classifier
        // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_Bayes.html
        'Bayes'                    => array(
            // Possible values: true, false
            'enabled'          => true,

            // Configuration file(s) where the plugin must be loaded
            'config_file'      => '/etc/spamassassin/v320.pre',

            // Enable/Disable site-wide SpamAssassin Bayesian classifier
            //
            // When set to yes 'yes', the $GLOBAL bayes database is enabled.
            // This allow to share the bayesian database with all users.
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
        // WARNING: You must first install DCC which is not provided by default.
        // See https://www.dcc-servers.net/dcc/INSTALL.html
        'DCC'                      => array(
            // Possible values: true, false
            'enabled'     => false,

            // Configuration file(s) where the plugin must be loaded
            'config_file' => '/etc/spamassassin/v310.pre'
        ),

        // DKIM plugin - perform DKIM verification tests
        // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_DKIM.html
        // WARNING: You shouldn't enable that plugin if you also use the i-MSCP OpenDKIM plugin.
        'DKIM'                     => array(
            // Possible values: true, false
            'enabled'     => false,

            // Configuration file(s) where the plugin must be loaded
            'config_file' => '/etc/spamassassin/v312.pre'
        ),

        // DecodeShortURLs plugin -- Expand shortened URLs
        // See https://github.com/smfreegard/DecodeShortURLs
        'DecodeShortURLs'          => array(
            // Possible values: true, false
            'enabled' => false
        ),

        // Heinlein Support SpamAssassin ruleset
        // See https://www.heinlein-support.de/blog/news/aktuelle-spamassassin-regeln-von-heinlein-support/
        'heinlein_support_ruleset' => array(
            // Possible value: true, false
            'enabled'     => true,

            // Cronjob sleep timer (in second)
            'sleep_timer' => 600,

            // sa-update channel
            'channel'     => 'spamassassin.heinlein-support.de'
        ),

        // iXhash2 SpamAssasin plugin - perform iXhash2 check of messages
        // See http://mailfud.org/iXhash2/
        'iXhash2'                  => array(
            // Possible values: true, false
            'enabled' => false
        ),

        // Pyzor plugin -- perform Pyzor check of messages
        // https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_Pyzor.html
        'Pyzor'                    => array(
            // Possible values: true, false
            'enabled'     => false,

            // Configuration file(s) where the plugin must be loaded
            'config_file' => '/etc/spamassassin/v310.pre'
        ),

        // Razor2 plugin -- perform Razor check of messages
        // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_Razor2.html
        'Razor2'                   => array(
            // Possible values: true, false
            'enabled'     => false,

            // Configuration file(s) where the plugin must be loaded
            'config_file' => '/etc/spamassassin/v310.pre'
        ),

        // Rule2XSBody plugin -- speed up SpamAssassin by compiling regexps
        // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_Rule2XSBody.html
        'Rule2XSBody'              => array(
            // Possible values: true, false
            'enabled'     => true,

            // Configuration file(s) where the plugin must be loaded
            'config_file' => '/etc/spamassassin/sa-compile.pre'
        ),

        // SPF plugin -- perform SPF verification tests
        // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_SPF.html
        // WARNING: You shouldn't enable that plugin if you also use the PolicydSPF i-MSCP plugin.
        'SPF'                      => array(
            // Possible values: true, false
            'enabled'     => false,

            // Configuration file(s) where the plugin must be loaded
            'config_file' => '/etc/spamassassin/init.pre'
        ),

        // TextCat plugin -- language guesser
        // See https://spamassassin.apache.org/full/3.4.x/doc/Mail_SpamAssassin_Plugin_TextCat.html
        'TextCat'                  => array(
            // possible values: true, false
            'enabled'     => false,

            // Configuration file(s) where the plugin must be loaded
            'config_file' => '/etc/spamassassin/v310.pre'
        ),

        // Enable RBL check
        // WARNING: You shouldn't enable this feature if you already use the i-MSCP PolicydWeight
        // and/or Postscreen plugins.
        'use_rbl_checks'           => array(
            // Possible value: true, false
            'enabled' => false
        )
    ),

    // Roundcube configuration options
    // Only relevant if you use the Roundcube Webmail
    'roundcube'      => array(
        // markasjunk2 Roundcube plugin
        'markasjunk2' => array(
            // Possible value: true, false
            'enabled' => true
        ),

        // sauserprefs Roundcube plugin
        'sauserprefs' => array(
            // Possible values: true, false
            'enabled'                   => true,

            // Protected SA user preferences
            //
            // Any user preference in that configuration parameter will be
            // protected against overriding by users.
            'sauserprefs_dont_override' => array(
                '{headers}',
                'use_razor1',
                'bayes_auto_learn_threshold_nonspam',
                'bayes_auto_learn_threshold_spam'
            )
        )
    )
);
