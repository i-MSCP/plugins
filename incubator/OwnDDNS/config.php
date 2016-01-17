<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2016 Sascha Bay <info@space2place.de>
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
 *
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  OwnDDNS
 * @copyright   Sascha Bay <info@space2place.de>
 * @author      Sascha Bay <info@space2place.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

return array(
	'debug' => FALSE, // TRUE to enable debuging. Public script writes to the i-MSCP admin log

	'use_base64_encoding' => FALSE, // TRUE to enable base64 encoding for receiving data

	'max_allowed_accounts' => '5', // Default when activating a new customer

	'max_accounts_lenght' => '30', // Max. lenght of the subdomain name

	'update_repeat_time' => '5', // Minutes between updates
	
	'update_ttl_time' => '60', // Seconds for TTL DNS updates
	
	'account_name_blacklist' => array('ftp', 'imap', 'mail', 'ns1', 'pop', 'pop3', 'relay', 'smtp', 'www') // Blacklist for account names
);
