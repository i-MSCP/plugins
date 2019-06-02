<?php
/**
 * i-MSCP SpamAssassin plugin
 * Copyright (C) 2015-2019 Laurent Declercq <l.declercq@nuxwin.com>
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

$saDb = iMSCP_Registry::get('config')['DATABASE_NAME'] . '_spamassassin';

return [
    'up' => "
        CREATE TABLE IF NOT EXISTS `$saDb`.`bayes_seen` (
            `id` int(11) NOT NULL DEFAULT '0',
            `msgid` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
            `flag` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
            PRIMARY KEY (`id`,`msgid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci
    "
];
