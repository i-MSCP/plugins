<?php
/**
 * i-MSCP SpamAssassin plugin
 * Copyright (C) 2015 Laurent Declercq <l.declercq@nuxwin.com>
 * Copyright (C) 2013-2015 Rene Schuster <mail@reneschuster.de>
 * Copyright (C) 2013-2015 Sascha Bay <info@space2place.de>
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
	//
	// Below you can adjust the SpamAssassin configuration.
	//
	// For more information about the different configuration options please check the SpamAssassin documentation at:
	//  http://spamassassin.apache.org/full/3.3.x/doc/ or http://spamassassin.apache.org/full/3.4.x/doc/
	//
	// WARNING: Don't change anything, if you don't know what you are doing.
	//

	// Reject spam (default: yes)
	//
	// If set to 'yes', the mails are rejected when they are detected as SPAM by SpamAssassin.
	// If set to 'no', the mails are not rejected but tagged as SPAM when they are detected by SpamAssassin.
	//
	// Note: Rejecting SPAM is supported because the checks are done totally legal before the MTA accepts the mails
	// (before-queue filter with spamass-milter)
	'reject_spam' => 'yes',

	// Use bayes (default: yes)
	//
	// If set to 'yes', enable usage of the bayes database.
	// If set to 'no', disable usage of the bayes database.
	'use_bayes' => 'yes',

	// Site wide bayes (default: no)
	//
	// If set to 'yes', global bayes database is activated. This allow to share the bayesian database with all users.
	'site_wide_bayes' => 'no',

	// SA learn cronjob for learning about spam/ham (default: run every 12 hours)
	'cronjob_bayes_sa-learn' => array(
		'minute' => '*',
		'hour' => '*/12',
		'day' => '*',
		'month' => '*',
		'dweek' => '*'
	),

	// SA learn cron job for cleaning the bayesian database (default: run once per day)
	'cronjob_clean_bayes_db' => array(
		'minute' => '@daily',
		'hour' => '',
		'day' => '',
		'month' => '',
		'dweek' => ''
	),

	// Use auto whitelist (default: no)
	//
	// When set to 'yes', enable usage of the Auto-Whitelist (awl) factor.
	// When set to 'no', disable usage of the Auto-Whitelist (awl) factor.
	'use_auto-whitelist' => 'no',

	// Cron job to cleanup the awl database from old entries (default: once per day)
	'cronjob_clean_awl_db' => array( // See man CRONTAB(5) for allowed values
		'minute' => '@daily',
		'hour' => '',
		'day' => '',
		'month' => '',
		'dweek' => ''
	),

	// Razor2 (default: yes)
	//
	// If set to 'yes', enable usage of razor2.
	// If set to 'no', disable usage of razor2.
	'use_razor2' => 'yes',

	// Pyzor (default: yes)
	//
	// If set to 'yes', enable usage of Pyzor.
	// If set to 'no', disable usage of Pyzor.
	'use_pyzor' => 'yes',

	// DCC - Distributed Checksum Clearinghouse (default: no)
	//
	// If set to 'yes', enable usage of DCC.
	// If set to 'no', disable usage of DCC.
	'use_dcc' => 'no',

	// RBL checks (default: no)
	//
	// If set to 'yes', enable RBL checks.
	// If set to 'no', disable RBL check.
	//
	// Note: You shouldn't enable this feature if you're already using PolicydWeight or Postscreen plugins.
	'use_rbl_checks' => 'no',

	// This plugin will try to guess the language used in the message text.
	'use_lang_check' => 'no', // yes, no (default)

	//
	//// 3rd party SpamAssasin plugins
	//

	// DecodeShortURLs plugin (default: yes)
	//
	// If set to 'yes', enable the DecodeShortURLs plugin.
	// If set to 'no', disable the DecodeShortURLs plugin.
	//
	// See https://github.com/smfreegard/DecodeShortURLs for further details.
	'DecodeShortURLs' => 'yes',

	// iXhash2 plugin (default: yes)
	//
	// If set to 'yes', enable the iXhash2 plugin.
	// If set to 'no', disable the iXhash2 plugin.
	//
	// See http://mailfud.org/iXhash2/ for further details.
	'iXhash2' => 'yes',

	//
	//// Roundcube plugins
	//

	// markasjunk2 plugin (default: yes)
	//
	// If set to 'yes', enable the markasjunk2 roundcube plugin.
	// If set to 'no', disable the markasjunk2 roundcube plugin.
	//
	// The markasjunk2 roundcube plugin adds a new button to the mailbox toolbar, which allow the users to mark the
	// selected messages as Junk/Not Junk, optionally detaching original messages from spam reports if the message is
	// not junk and learning the bayesian database with junk/not junk.
	'markasjunk2' => 'yes',

	// sauserprefs plugin (default: yes)
	//
	// If set to 'yes', enable the sauserprefs plugin.
	// If set to 'no', disable sauserprefs plugin.
	//
	// The sauserprefs roundcube plugin adds a 'Spam' tab to the 'Settings' in roundcube, which allow the users to
	// change their SpamAssassin preferences. SA user preference are stored inside the imscp_spamassassin database.
	'sauserprefs' => 'yes',

	// Protected SA user preferences (default: '{headers}', 'use_razor1')
	//
	// Any user preference listed in that configuration parameter will be protected against overriding by users. See
	// Check webmail/plugins/sauserprefs/config.inc.php for list of available options.
	//
	// Don't change anything, if you don't know what you are doing.
	'sauserprefs_dont_override' => "'{headers}', 'use_razor1', 'bayes_auto_learn_threshold_nonspam', 'bayes_auto_learn_threshold_spam'", // default: ""'{headers}', 'use_razor1', 'bayes_auto_learn_threshold_nonspam', 'bayes_auto_learn_threshold_spam'"

	//
	//// SpamAssassin and spamass-milter configuration
	//

	'spamassMilterOptions' => '-e -f -I -u spamass-milter',
	'spamassMilterSocket' => '/var/spool/postfix/spamass/spamass.sock',
	'spamassassinOptions' => '--max-children=5 --sql-config --nouser-config --username=debian-spamd --port=783 --helper-home-dir=/var/lib/spamassassin'
);
