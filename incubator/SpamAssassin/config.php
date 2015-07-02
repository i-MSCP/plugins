<?php
/**
 * i-MSCP SpamAssassin plugin
 * Copyright (C) 2013-2015 Sascha Bay <info@space2place.de>
 * Copyright (C) 2013-2015 Rene Schuster <mail@reneschuster.de>
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
	/* 
	 * Below you can adjust the SpamAssassin configuration.
	 * 
	 * For more information about the different configuration 
	 * options please check the SpamAssassin documentation at:
	 * http://spamassassin.apache.org/full/3.3.x/doc/ or
	 * http://spamassassin.apache.org/full/3.4.x/doc/
	 * 
	 * ATTENTION:
	 * - Don't change anything, if you don't know what you are doing!
	 * - Make sure you know if you need/want the checks or not before enabling them,
	 *   because each SpamAssassin plugin needs resources and also time to be executed.
	 */

	// On this plugin implementation rejecting SPAM is totally legal, because the SPAM check will be done
	// before the MTA accepts the mail message (before-queue filter with spamass-milter).
	//
	// reject_spam = 'YES'  ->  The mail will be rejected after SpamAssassin recognized it as SPAM.	
	// reject_spam = 'NO'   ->  The mail will be tagged as SPAM and delivered to the user mailbox.
	'reject_spam' => 'no', // yes, no (default)

	// This enables the usage of the bayes database. 
	'use_bayes' => 'yes', // yes (default), no

	// If set to 'YES', global bayes database will be activated.
	// This could be used to group all users together to share bayesian filter data.
	'site-wide_bayes' => 'no', // yes, no (default)
	
	// Cronjob to learn the bayesian database with Spam/Ham maila.
	// Normally it is sufficient to run this cronjob twice a day.
	'cronjob_bayes_sa-learn' => array( // See man CRONTAB(5) for allowed values
		'minute' => '*',
		'hour' => '*/12',
		'day' => '*',
		'month' => '*',
		'dweek' => '*'
	),

	// Cronjob to cleanup the bayes database from expired tokens.
	// Normally it is sufficient to run this cronjob on a daily basis.
	'cronjob_clean_bayes_db' => array( // See man CRONTAB(5) for allowed values
		'minute' => '@daily',
		'hour' => '',
		'day' => '',
		'month' => '',
		'dweek' => ''
	),

	// This enables the usage of the Auto-Whitelist (awl) factor.
	'use_auto-whitelist' => 'no', // YES, NO (default)

	// Cronjob to cleanup the awl database from old entries.
	// Normally it is sufficient to run this cronjob on a daily basis.
	'cronjob_clean_awl_db' => array( // See man CRONTAB(5) for allowed values
		'minute' => '@daily',
		'hour' => '',
		'day' => '',
		'month' => '',
		'dweek' => ''
	),

	// Perform Razor check of messages if installed.
	'use_razor2' => 'no', // yes, no (default)

	// Perform Pyzor check of messages if installed.
	'use_pyzor' => 'no', // yes, no (default)

	// Perform DCC (Distributed Checksum Clearinghouse) check of messages if installed.
	'use_dcc' => 'no', // yes, no (default)

	// Whether RBL (Realtime Blackhole List) check should be used or not.
	// You don't need this if you already use Policyd-Weight or the Postscreen Plugin.
	'use_rbl_checks' => 'no', // yes, no (default)

	// This plugin will try to guess the language used in the message text.
	'use_lang_check' => 'no', // yes, no (default)

	/*
	 * 3rd party SpamAssasin Plugins
	 */

	// Plugin DecodeShortURLs - https://github.com/smfreegard/DecodeShortURLs
	'DecodeShortURLs' => 'no', // yes, no (default)

	// Plugin iXhash2 - http://mailfud.org/iXhash2/
	'iXhash2' => 'no', // yes, no (default)

	/*
	 * Roundcube Plugins
	 */

	// Adds a new button to the mailbox toolbar to mark the selected messages as Junk/Not Junk, 
	// optionally detaching original messages from spam reports if the message is not junk and 
	// learning the bayesian database with junk/not junk.
	'markasjunk2' => 'yes', // yes (default), no

	// Adds a 'Spam' tab to the 'Settings' in Roundcube to allow the users to change
	// their SpamAssassin preferences which are stored in the imscp_spamassassin database.
	'sauserprefs' => 'yes', // yes (default), no

	// Don't allow these settings to be overriden by the user.
	// Check webmail/plugins/sauserprefs/config.inc.php for all options.
	// Don't change anything, if you don't know what you are doing!
	'sauserprefs_dont_override' => "'{headers}', 'use_razor1'", // default: "'{headers}', 'use_razor1'"

	/*
	 * SpamAssassin and spamass-milter default configuration
	 */
	 
	// Don't change anything, if you don't know what you are doing!
	'spamassMilterOptions' => '-e -f -I -u spamass-milter',

	// Don't change anything, if you don't know what you are doing!
	'spamassMilterSocket' => '/var/spool/postfix/spamass/spamass.sock',

	// Don't change anything, if you don't know what you are doing!
	'spamassassinOptions' => '--max-children=5 --sql-config --nouser-config --username=debian-spamd --port=783 --helper-home-dir=/var/lib/spamassassin'
);
