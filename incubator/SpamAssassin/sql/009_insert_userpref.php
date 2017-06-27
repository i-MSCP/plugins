<?php
/**
 * i-MSCP SpamAssassin plugin
 * Copyright (C) 2015-2017 Laurent Declercq <l.declercq@nuxwin.com>
 * Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>
 * Copyright (C) 2013-2016 Rene Schuster <mail@reneschuster.de>
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

$saDb = quoteIdentifier(iMSCP_Registry::get('config')->DATABASE_NAME . '_spamassassin');

return array(
    'up' => "
        INSERT IGNORE INTO $saDb.userpref
            (`username`, `preference`, `value`)
        VALUES
            ('\$GLOBAL', 'required_score', '5'),
            ('\$GLOBAL', 'rewrite_header Subject', '*****SPAM*****'),
            ('\$GLOBAL', 'report_safe', '1'),
            ('\$GLOBAL', 'use_bayes', '1'),
            ('\$GLOBAL', 'use_bayes_rules', '1'),
            ('\$GLOBAL', 'bayes_auto_learn', '1'),
            ('\$GLOBAL', 'bayes_auto_learn_threshold_nonspam', '0.1'),
            ('\$GLOBAL', 'bayes_auto_learn_threshold_spam', '12.0'),
            ('\$GLOBAL', 'use_auto_whitelist', '0'),
            ('\$GLOBAL', 'skip_rbl_checks', '1'),
            ('\$GLOBAL', 'use_razor2', '0'),
            ('\$GLOBAL', 'use_pyzor', '0'),
            ('\$GLOBAL', 'use_dcc', '0'),
            ('\$GLOBAL', 'score USER_IN_BLACKLIST', '10'),
            ('\$GLOBAL', 'score USER_IN_WHITELIST', '-6');
    "
);
