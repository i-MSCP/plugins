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

$imscpDb = quoteIdentifier(iMSCP_Registry::get('config')->DATABASE_NAME);
$saDb = quoteIdentifier(iMSCP_Registry::get('config')->DATABASE_NAME . '_spamassassin');

return array(
    'up' => "
        -- Remove unwanted user preferences
        -- 
        -- 1. Delete the `use_auto_whitelist' user preference as the AWL plugin
        -- is either enabled for all users or fully disabled, and also because
        -- the `use_auto_whitelist' user preference is not settable through the
        -- Roundcube `sauserprefs plugin'.
        --
        -- 2. Delete Blacklist/Whitelist score preferences as the defaults fit
        -- better for the purpose of that feature
        DELETE FROM $saDb.userpref
        WHERE preference IN ('use_auto_whitelist', 'score USER_IN_BLACKLIST', 'score USER_IN_WHITELIST');

        -- Add missing preferences
        INSERT IGNORE INTO $saDb.userpref
            (username, preference, value)
        VALUES
            ('\$GLOBAL', 'ok_locales', 'all'),
            ('\$GLOBAL', 'ok_languages', 'all');

        -- New default values for SA plugins
        UPDATE $saDb.userpref SET value = '1' WHERE username = '\$GLOBAL' AND preference = 'skip_rbl_checks';
        UPDATE $saDb.userpref SET value = '0' WHERE username = '\$GLOBAL' AND preference = 'use_bayes';
        UPDATE $saDb.userpref SET value = '0' WHERE username = '\$GLOBAL' AND preference = 'use_pyzor';
        UPDATE $saDb.userpref SET value = '0' WHERE username = '\$GLOBAL' AND preference = 'use razor2';
        UPDATE $saDb.userpref SET value = '0' WHERE username = '\$GLOBAL' AND preference = 'use_dcc';

        -- Remove any orphaned user preference
        DELETE u FROM $saDb.userpref u
        WHERE u.username <> '\$GLOBAL'
        AND u.username NOT IN(SELECT m.mail_addr FROM $imscpDb.mail_users m WHERE mail_pass <> '_no_');
    "
);
