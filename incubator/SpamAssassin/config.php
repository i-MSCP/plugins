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

    // Enable/Disable Rule2XSBody plugin (default: yes)
    //
    // When set to yes, the site-wide parts of the SpamAssassin ruleset are
    // compiled into native code using sa-compile and the SA Rule2XSBody plugin
    // is enabled.
    //
    // See SA-COMPILE(1p) for further details
    //
    'sa_compile'                => 'yes',

    // Enable or disable bayesian filtering (default: yes)
    //
    // possible values: yes, no
    'use_bayes'                 => 'yes',

    // Enable or disable site-wide Bayesian filter (default: no)
    //
    // When set to yes 'yes', global bayes database is enabled.
    // This allow to share the bayesian database with all users.
    // Possible values: yes, no
    'site_wide_bayes'           => 'no',

    // Cronjob for sa-learn (default: run every 12 hours)
    // See man CRONTAB(5) for allowed values
    'cronjob_bayes_sa-learn'    => array(
        'minute' => '0',
        'hour'   => '*/12',
        'day'    => '*',
        'month'  => '*',
        'dweek'  => '*'
    ),

    // Cronjob for cleaning up of bayes database (default: run once per day)
    // See man CRONTAB(5) for allowed values
    'cronjob_clean_bayes_db'    => array(
        'minute' => '@daily',
        'hour'   => '',
        'day'    => '',
        'month'  => '',
        'dweek'  => ''
    ),

    // Enable or disable AWL (auto whitelist) (default: no)
    //
    // Possible values: yes, no
    'use_auto-whitelist'        => 'no',

    // Cron job for cleanup of AWL (auto whitelist) database (default: once per day)
    // See man CRONTAB(5) for allowed values
    'cronjob_clean_awl_db'      => array(
        'minute' => '@daily',
        'hour'   => '',
        'day'    => '',
        'month'  => '',
        'dweek'  => ''
    ),

    // Enable or disable Razor2 (default: yes)
    //
    // Possible values: yes, no
    'use_razor2'                => 'yes',

    // Enable or disable Pyzor (default: yes)
    //
    // Possible values: yes, no
    'use_pyzor'                 => 'yes',

    // Enable or disable DCC - Distributed Checksum Clearinghouse (default: no)
    //
    // You must first install DCC which is not provided by default
    //
    // Possible values: yes, no
    'use_dcc'                   => 'no',

    // Enable or disable RBL checks (default: no)
    //
    // Possible values: yes, no
    // Note: You shouldn't enable this feature if you already use PolicydWeight
    // or Postscreen plugins.
    'use_rbl_checks'            => 'no',

    // use_lang_check plugin (default: no)
    // This plugin will try to guess the language used in the message text.
    // Possible values: yes, no
    'use_lang_check'            => 'no',

    //
    //// 3rd party SpamAssassin rules
    //

    // Heinlein Support SpamAssassin rules (default: yes)
    //
    // Latest SpamAssassin rules directly from the Heinlein Hosting live
    // systems. Heinlein Support is a German ISP company and specialized on
    // mail servers. The founder and owner Peer Heinlein has written several
    // books about Dovecot and Postfix.
    //
    // For further details check the link below:
    // https://www.heinlein-support.de/blog/news/aktuelle-spamassassin-regeln-von-heinlein-support/
    'heinlein-support_sa-rules' => 'yes',

    //
    //// 3rd party SpamAssassin plugins
    //

    // DecodeShortURLs plugin (default: yes)
    //
    // See https://github.com/smfreegard/DecodeShortURLs for further details.
    // Possible values: yes, no
    'DecodeShortURLs'           => 'yes',

    // iXhash2 plugin (default: yes)
    //
    // See http://mailfud.org/iXhash2/ for further details.
    // Possible value: yes, no
    'iXhash2'                   => 'yes',

    //
    //// Roundcube plugins
    //

    // markasjunk2 plugin (default: yes)
    //
    // The markasjunk2 roundcube plugin adds a new button to the mailbox
    // toolbar, which allow the users to mark the selected messages as
    // Junk/Not Junk, optionally detaching original messages from spam reports
    // if the message is not junk and learning the bayesian database with
    // junk/not junk.
    //
    // Possible value: yes, no
    'markasjunk2'               => 'yes',

    // sauserprefs plugin (default: yes)
    //
    // The SAUserPrefs Roundcube plugin adds a 'Spam' tab to the 'Settings' in
    // Roundcube, which allow the users to change their SpamAssassin preferences.
    //
    // SpamAssassin user preference are stored inside the i-MSCP SpamAssassin
    // database.
    //
    // Possible values: yes, no
    'sauserprefs'               => 'yes',

    // Protected SA user preferences
    // (default: {headers}, use_razor1, bayes_auto_learn_threshold_nonspam, bayes_auto_learn_threshold_spam)
    //
    // Any user preference listed in that configuration parameter will be
    // protected against overriding by users. See Check
    // webmail/plugins/sauserprefs/config.inc.php for list of available options.
    //
    // WARNING: Don't change anything if you don't know what you are doing.
    'sauserprefs_dont_override' => array(
        '{headers}',
        'use_razor1',
        'bayes_auto_learn_threshold_nonspam',
        'bayes_auto_learn_threshold_spam'
    ),

    //
    // SPAMASS_MILTER(8) configuration
    //

    'spamassMilter_config' => array(
        // Reject spam (default: -1)
        //
        // If set to '-1', mails are always rejected when they are detected as SPAM.
        // If set to '15', mails are only rejected when the score is equal or greater then 15.
        // Mails below that score are not rejected but tagged as SPAM.
        //
        // If you don't want to reject any mails, then use a value higher than '1000'.
        //
        // Note: Rejecting SPAM is supported because the checks are done totally legal
        // before the MTA accepts the mails (before-queue filter with spamass-milter).
        'reject_spam'          => '-1',

        // Check mails if the sender has authenticated via SMTP AUTH (default: yes)
        // If set to 'yes', all outgoing mails of authenticated senders are scanned.
        'check_smtp_auth'      => 'yes',

        // Don't scan listed networks (default: empty array)
        //
        // Mails will be passed through without being scanned if the originating IP is listed
        // in networks. Networks is a comma-separated list, where each element can be either an
        // IP address (nnn.nnn.nnn.nnn), a CIDR network (nnn.nnn.nnn.nnn/nn), or a network/netmask
        // pair (nnn.nnn.nnn.nnn/nnn.nnn.nnn.nnn).
        //
        // For example: networks => array('127.0.0.1', '172.16.12.0/24', '10.0.0.0/255.0.0.0')
        'networks'             => array(),

        // SPAMASS_MILTER(8) configuration options (default: -e -f -u spamass-milter)
        // You can pass your own options to SPAMASS_MILTER(8), including flags for
        // SPAMC(1) as described in SPAMASS_MILTER(8)
        // WARNING: Don't change anything if you don't know what you are doing.
        'spamassMilterOptions' => '-e -f -u spamass-milter',

        // SPAMASS_MILTER(8) socket path (default: /var/spool/postfix/spamass/spamass.sock)
        // WARNING: Don't change anything if you don't know what you are doing.
        'spamassMilterSocket'  => '/var/spool/postfix/spamass/spamass.sock'
    ),

    //
    // SPAMD(8p) configuration
    //

    // SPAMD(8p) configuration options (default:--max-children=5 --sql-config --nouser-config --username=debian-spamd --port=783 --helper-home-dir=/var/lib/spamassassin)
    // WARNING: Don't change anything if you don't know what you are doing.
    'spamassassinOptions'  => '--max-children=5 --sql-config --nouser-config --username=debian-spamd --port=783 --helper-home-dir=/var/lib/spamassassin'
);
