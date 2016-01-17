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
	'up' => '
		CREATE TABLE IF NOT EXISTS ownddns (
			ownddns_id int(11) unsigned NOT NULL AUTO_INCREMENT,
			admin_id int(11) unsigned NOT NULL,
			domain_id int(11) unsigned NOT NULL,
			admin_name varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			max_ownddns_accounts int(11) default NULL,
			customer_dns_previous_status varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			ownddns_status varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			PRIMARY KEY (OwnDDNS_id),
			KEY ownddns_id (ownddns_id)
		) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	',
	'down' => '
		DROP TABLE IF EXISTS ownddns
	'
);
