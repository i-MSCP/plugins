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

$imscpDb = iMSCP_Registry::get('config')['DATABASE_NAME'];
$saDb = iMSCP_Registry::get('config')['DATABASE_NAME'] . '_spamassassin';

//
// Remove orphaned bayesian data if any
//

return [
    'up' => "
        DELETE `t1`
        FROM `$saDb`.`bayes_token` AS `t1`
        JOIN `$saDb`.`bayes_vars` AS `t2` USING(`id`)
        WHERE `t2`.`username` <> '\$GLOBAL'
        AND NOT EXISTS(
            SELECT 1
            FROM `$imscpDb`.`mail_users` AS `t3`
            WHERE `t3`.`mail_addr` = `t2`.`username`
            AND `t3`.`mail_pass` <> '_no_'
        );

        DELETE `t1`
        FROM `$saDb`.`bayes_seen` AS `t1`
        JOIN `$saDb`.`bayes_vars` AS `t2` USING(`id`)
        WHERE `t2`.`username` <> '\$GLOBAL'
        AND NOT EXISTS(
            SELECT 1
            FROM `$imscpDb`.`mail_users` AS `t3`
            WHERE `t3`.`mail_addr` = `t2`.`username`
            AND `t3`.`mail_pass` <> '_no_'
        );

        DELETE `t1`
        FROM `$saDb`.`bayes_vars` AS `t1`
        WHERE t1.username <> '\$GLOBAL'
        AND NOT EXISTS(
            SELECT 1
            FROM $imscpDb.mail_users AS `t2`
            WHERE `t2`.`mail_addr` = `t1`.`username`
            AND `t2`.`mail_pass` <> '_no_'
        )
    "
];
