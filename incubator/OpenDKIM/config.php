<?php
/**
 * i-MSCP OpenDKIM plugin
 * Copyright (C) 2013-2016 Laurent Declercq <l.declercq@nuxwin.com>
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

return array(
	// OpenDKIM listening port (default: 12345)
	// Warning: Don't use a port lower than 1000 nor greater than 65535
	'opendkim_port' => '12345',

	// OpenDKIM canonicalization method (default: simple)
	//
	// Canonicalization method(s) to be used when signing messages. When verifying, the message's DKIM-Signature: header
	// field specifies the canonicalization method. The recognized values are relaxed and simple as defined by the DKIM
	// specification. The value may include two different canonicalizations separated by a slash ("/") character, in
	// which case the first will be applied to the header and the second to the body.
	//
	// Possible values: simple, relaxed, simple/relaxed or relaxed/simple
	'opendkim_canonicalization' => 'simple',

	// Trusted hosts (default: 127.0.0.1, localhost)
	//
	// List of host which must be trusted by OpenDKIM
	'opendkim_trusted_hosts' => array(
		'127.0.0.1',
		'localhost'
	)
);
