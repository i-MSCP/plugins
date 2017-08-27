<?php
/**
 * i-MSCP OpenDKIM plugin
 * Copyright (C) 2013-2017 Laurent Declercq <l.declercq@nuxwin.com>
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

return [
    // Postfix smtpd milter for OpenDKIM (default: unix:/opendkim/opendkim.sock)
    //
    // Possible values:
    //  'unix:/var/run/opendkim/opendkim.sock' for connection through UDS
    //  'inet:localhost:12345' for connection through TCP
    'postfix_milter_socket'     => 'unix:/var/run/opendkim/opendkim.sock',

    // Postfix run directory (default: /var/spool/postfix/var/run)
    // Can be added in other setting using the %postfix_rundir% placeholder
    'postfix_rundir'            => '/var/spool/postfix/var/run',

    // Postfix user (default: postfix)
    'postfix_user'              => 'postfix',

    // OpenDKIM configuration directory (default: /etc/opendkim)
    'opendkim_confdir'          => '/etc/opendkim',

    // OpenDKIM rundir (default: %postfix_rundir%/opendkim)
    // Can be added in other setting using the %opendkim_rundir% placeholder
    'opendkim_rundir'           => '%postfix_rundir%/opendkim',

    // OpenDKIM socket (default: local:%opendkim_rundir%/opendkim.sock)
    //
    // Possible values:
    //  'local:%opendkim_rundir%/opendkim.sock' for UDS (recommended)
    //  'inet:12345@localhost' for connection through TCP
    'opendkim_socket'           => 'local:%opendkim_rundir%/opendkim.sock',

    // OpenDKIM user (default: opendkim)
    'opendkim_user'             => 'opendkim',

    // OpenDKIM group (default: opendkim)
    'opendkim_group'            => 'opendkim',

    // OpenDKIM canonicalization method (default: simple)
    //
    // Canonicalization method(s) to be used when signing messages. When
    // verifying, the message's DKIM-Signature: header field specifies the
    // canonicalization method.
    //
    // Possible values are 'relaxed' and 'simple' as defined by the DKIM
    // specification.
    //
    // The value may include two different canonicalizations separated by a
    // slash ("/") character, in which case the first will be applied to the
    // header and the second to the body.
    //
    // Possible values: simple, relaxed, simple/relaxed or relaxed/simple
    'opendkim_canonicalization' => 'simple',

    // Trusted hosts (default: 127.0.0.1, localhost)
    'opendkim_trusted_hosts'    => [
        '127.0.0.1',
        'localhost'
    ]
];
