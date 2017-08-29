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

use iMSCP_Config_Handler_File as ConfigFile;
use iMSCP_Registry as Registry;

$postfixConfig = new ConfigFile(Registry::get('config')['CONF_DIR'] . '/postfix/postfix.data');

return [
    // Postfix smtpd milter for OpenDKIM (default: unix:/var/run/opendkim/opendkim.sock)
    //
    // Possible values:
    //  'unix:/var/run/opendkim/opendkim.sock' for connection through UDS
    //  'inet:localhost:12345' for connection through TCP
    'postfix_milter_socket'          => 'unix:/var/run/opendkim/opendkim.sock',

    // Postfix run directory (default: /var/spool/postfix/var/run)
    // Can be added in other setting using the %postfix_rundir% placeholder
    'postfix_rundir'                 => "{$postfixConfig['POSTFIX_QUEUE_DIR']}/var/run",

    // OpenDKIM ADSP (Author Domain Signing Practices) extension (default: true)
    'opendkim_adsp_extension'        => true,

    // OpenDKIM ADSP signing practice (default: discardable)
    //
    // Possible values: unknown, all, discardable
    // See https://tools.ietf.org/html/rfc5617#section-4.2.1
    'opendkim_adsp_signing_practice' => 'discardable',

    // OpenDKIM configuration directory (default: /etc/opendkim)
    'opendkim_confdir'               => '/etc/opendkim',

    // OpenDKIM key size (default: 2048)
    //
    // See https://tools.ietf.org/html/rfc6376#section-3.3.3
    // Be aware that keys longer than 2048 bits may not be supported by all verifiers.
    'opendkim_keysize'               => 2048,

    // OpenDKIM rundir (default: %postfix_rundir%/opendkim)
    //
    // Can be added in other setting using the %opendkim_rundir% placeholder
    'opendkim_rundir'                => '%postfix_rundir%/opendkim',

    // OpenDKIM socket (default: local:%opendkim_rundir%/opendkim.sock)
    //
    // Possible values:
    //  'local:%opendkim_rundir%/opendkim.sock' for UDS (recommended)
    //  'inet:12345@localhost' for connection through TCP
    'opendkim_socket'                => 'local:%opendkim_rundir%/opendkim.sock',

    // OpenDKIM user (default: opendkim)
    'opendkim_user'                  => 'opendkim',

    // OpenDKIM group (default: $postfixConfig['POSTFIX_GROUP'])
    'opendkim_group'                 => $postfixConfig['POSTFIX_GROUP'],

    // OpenDKIM canonicalization method (default: relaxed/simple)
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
    'opendkim_canonicalization'      => 'relaxed/simple',

    // Trusted hosts (default: 127.0.0.1, localhost)
    'opendkim_trusted_hosts'         => [
        '127.0.0.1',
        'localhost'
    ]
];
