<?php
/**
 * i-MSCP RoundcubePlugins plugin
 * Copyright (C) 2019 Laurent Declercq <l.declercq@nuxwin.com>
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

// Configuration file included into the Roundcube additional_message_headers
// plugin configuration file.

$config['additional_message_headers']['X-Remote-Browser'] = $_SERVER['HTTP_USER_AGENT'];
$config['additional_message_headers']['X-Originating-IP'] = '[' . $_SERVER['REMOTE_ADDR'] .']';
$config['additional_message_headers']['X-RoundCube-Server'] = $_SERVER['SERVER_ADDR'];
