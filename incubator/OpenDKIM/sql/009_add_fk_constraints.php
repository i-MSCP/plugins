<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by i-MSCP Team
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
 * @subpackage  OpenDKIM
 * @copyright   Sascha Bay <info@space2place.de>
 * @copyright   Rene Schuster <mail@reneschuster.de>
 * @author      Sascha Bay <info@space2place.de>
 * @author      Rene Schuster <mail@reneschuster.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

return array(
	'up' => '
		ALTER TABLE opendkim ADD CONSTRAINT admin_id FOREIGN KEY (admin_id) REFERENCES admin (admin_id)
		ON DELETE CASCADE;

		ALTER TABLE opendkim ADD CONSTRAINT domain_id FOREIGN KEY (domain_id) REFERENCES domain (domain_id)
		ON DELETE CASCADE;

		ALTER TABLE opendkim ADD CONSTRAINT alias_id FOREIGN KEY (alias_id) REFERENCES domain_aliasses (alias_id)
		ON DELETE CASCADE;
	',
	'down' => '
		ALTER TABLE opendkim DROP FOREIGN KEY admin_id;
		ALTER TABLE opendkim DROP INDEX admin_id;
		ALTER TABLE opendkim DROP FOREIGN KEY domain_id;
		ALTER TABLE opendkim DROP INDEX domain_id;
		ALTER TABLE opendkim DROP FOREIGN KEY alias_id;
		ALTER TABLE opendkim DROP INDEX alias_id;
	'
);
