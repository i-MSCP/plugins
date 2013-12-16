<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2013 by i-MSCP Team
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
	// Port for the OpenDKIM daemon (don't use ports lower than 1000 and greater than 65535)
	'opendkim_port' => '12345',

	/* Select the canonicalization method(s) to be used when signing messages. When verifying, 
	 * the message's DKIM-Signature: header field specifies the canonicalization method. 
	 * The recognized values are relaxed and simple as defined by the DKIM specification. 	
	 * The value may include two different canonicalizations separated by a slash ("/") character, 
	 * in which case the first will be applied to the header and the second to the body.
	 * allowed values: simple (default), relaxed, simple/relaxed, relaxed/simple  */
	'opendkim_canonicalization' => 'simple',

	// Add domains which should be considered as trusted hosts by OpenDKIM
	'opendkim_trusted_hosts' => array(
		'127.0.0.1',
		'localhost'
	)
);
