<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2016 Rene Schuster <mail@reneschuster.de>
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
$roundcubeDbName = quoteIdentifier(iMSCP_Registry::get('config')->DATABASE_NAME . '_roundcube');
return array(
    'up'   => "
        CREATE TABLE IF NOT EXISTS $roundcubeDbName.collected_contacts (
          `contact_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          `changed` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
          `del` tinyint(1) NOT NULL DEFAULT '0',
          `name` varchar(128) NOT NULL DEFAULT '',
          `email` text NOT NULL,
          `firstname` varchar(128) NOT NULL DEFAULT '',
          `surname` varchar(128) NOT NULL DEFAULT '',
          `vcard` longtext NULL,
          `words` text NULL,
          `user_id` int(10) UNSIGNED NOT NULL,
          PRIMARY KEY(`contact_id`),
          CONSTRAINT `user_id_fk_collected_contacts` FOREIGN KEY (`user_id`)
             REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
          INDEX `user_collected_contacts_index` (`user_id`,`del`)
) /*!40000 ENGINE=INNODB */ /*!40101 CHARACTER SET utf8 COLLATE utf8_general_ci */;
    ",
    'down' => "
        DROP TABLE IF EXISTS $roundcubeDbName.collected_contacts;
    "
);
