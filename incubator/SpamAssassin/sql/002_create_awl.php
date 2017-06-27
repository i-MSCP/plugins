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
        CREATE TABLE IF NOT EXISTS $saDb.awl (
            `username` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
            `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
            `ip` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
            `count` int(11) NOT NULL DEFAULT '0',
            `totscore` float NOT NULL DEFAULT '0',
            `signedby` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
            `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`username`,`email`,`signedby`,`ip`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
    "
);
