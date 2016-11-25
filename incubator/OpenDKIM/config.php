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
    // OpenDKIM Socket (default: local:/var/spool/postfix/opendkim/opendkim.sock)
    //
    // Possible values:
    //  'local:/var/spool/postfix/opendkim/opendkim.sock' for connection through UNIX socket
    //  'inet:12345@localhost' for connection through TCP socket
    'OpenDKIM_Socket'           => 'local:/var/spool/postfix/opendkim/opendkim.sock',

    // Postfix smtpd milter for OpenDKIM (default: unix:/opendkim/opendkim.sock)
    //
    // Possible values:
    //  'unix:/opendkim/opendkim.sock' for connection through UNIX socket
    //  'inet:localhost:12345' for connection through TCP socket
    'PostfixMilterSocket'       => 'unix:/opendkim/opendkim.sock',

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
    'opendkim_trusted_hosts'    => array(
        '127.0.0.1',
        'localhost'
    )
);
