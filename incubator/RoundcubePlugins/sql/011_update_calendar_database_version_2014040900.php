<?php
/**
 * i-MSCP - internet Multi Server Control Panel
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

$roundcubeDbName = quoteIdentifier(iMSCP_Registry::get('config')->DATABASE_NAME . '_roundcube');

# We must check column existence before add it due error made at commit 6c17b88771f0586ec0949c838b091dc0f4b1d4cc, in
# which that column has been added in an existent migration file instead of a new one.
$addColumn = function ($dbName, $table, $column, $def) {
    $table = quoteIdentifier($table);
    $stmt = exec_query("SHOW COLUMNS FROM $dbName.$table LIKE ?", $column);
    if (!$stmt->rowCount()) {
        return sprintf('ALTER TABLE %s.%s ADD %s %s;', $dbName, $table, quoteIdentifier($column), $def);
    }
    return '';
};

return array(
    'up'   => $addColumn($roundcubeDbName, 'events', 'status', 'VARCHAR(32) NOT NULL AFTER sensitivity') . " 
        REPLACE INTO $roundcubeDbName.system (name, value) VALUES ('calendar-database-version', '2014040900');
    ",
    'down' => "
        ALTER TABLE $roundcubeDbName.events DROP status;
        REPLACE INTO $roundcubeDbName.system (name, value) VALUES ('calendar-database-version', '2013051600');
    "
);
