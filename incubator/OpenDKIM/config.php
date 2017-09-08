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
    // OpenDKIM ADSP (Author Domain Signing Practices) extension (default: true)
    //
    // When enabled, an ADSP DNS resource record is added for all domains and
    // subdomains for which OpenDKIM is enabled.
    //
    // Possible values: true (enabled), false (disabled)
    // See https://en.wikipedia.org/wiki/Author_Domain_Signing_Practices
    // Related parameter: opendkim_adsp_signing_practice
    'opendkim_adsp'                  => true,

    // OpenDKIM ADSP action
    //
    // Action to be taken when an ADSP check against a message with no valid
    // author signature results in the message being deemed suspicious and
    // discardable. Possible values are "discard" (accept the message but
    // throw it away), "reject" (bounce the message) or "none". If set to
    // "none", discardable messages will still be delivered.
    //
    // Note that this parameter doesn't depend on the `opendkim_adsp' parameter.
    // Note also that this parameter is only relevant with OpenDKIM versions
    // older than 2.10.0 as the ADSP support has been discontinued.
    //
    // Possible values: discard, reject, none
    'opendkim_adsp_action'           => 'reject',

    // OpenDKIM ADSP No Such Domain (default: true)
    //
    // If true, requests rejection of messages that are determined to be from
    // nonexistent domains according to the author domain signing practises
    // (ADSP) test.
    //
    // Note that this parameter doesn't depend on the `opendkim_adsp' parameter.
    // Note also that this parameter is only relevant with OpenDKIM versions
    // older than 2.10.0 as the ADSP support has been discontinued.
    //
    // Possible values: true (enabled), false (disabled)
    'opendkim_adsp_no_such_domain'   => true,

    // OpenDKIM ADSP signing practice (default: discardable)
    //
    // Allows to select author signing practice for domains.
    //
    // Note that this parameter depends on the `opendkim_adsp' parameter.
    //
    // Possible values: unknown, all, discardable
    // See https://tools.ietf.org/html/rfc5617#section-4.2.1
    'opendkim_adsp_signing_practice' => 'discardable',

    // OpenDKIM canonicalization method (default: relaxed/simple)
    //
    // Canonicalization method(s) to be used when signing messages. When
    // verifying, the message's DKIM-Signature header field specifies the
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

    // OpenDKIM configuration directory (default: /etc/opendkim)
    'opendkim_confdir'               => '/etc/opendkim',

    // OpenDKIM DNS resource records TTL (default: 60)
    //
    // Possible value: Time in seconds
    'opendkim_dns_records_ttl'       => 60,

    // OpenDKIM group (default: opendkim)
    'opendkim_group'                 => 'opendkim',

    // OpenDKIM key size (default: 2048)
    //
    // See https://tools.ietf.org/html/rfc6376#section-3.3.3
    // Be aware that keys longer than 2048 bits may not be supported by all verifiers.
    // Minimum key length is 1024 bits.
    'opendkim_keysize'               => 2048,

    // OpenDKIM operating mode (default: sv)
    //
    // Possible values: s (signer), v (verifier), sv (signer and verifier)
    //
    // Note: If you use SpamAssassin with its DKIM plugin, it is recommended to
    // set the operating mode to 's' (signer). SpamAssassin DKIM plugin will
    // act as verifier.
    'opendkim_operating_mode'        => 'sv',

    // OpenDKIM rundir (default: %postfix_rundir%/opendkim)
    'opendkim_rundir'                => '%postfix_rundir%/opendkim',

    // OpenDKIM socket (default: local:%opendkim_rundir%/opendkim.sock)
    //
    // Possible values:
    //  'local:%opendkim_rundir%/opendkim.sock' for UDS (recommended)
    //  'inet:12345@localhost' for connection through TCP
    'opendkim_socket'                => 'local:%opendkim_rundir%/opendkim.sock',

    // Trusted hosts (default: 127.0.0.1, ::1, localhost, Registry::get('config')['SERVER_HOSTNAME'])
    'opendkim_trusted_hosts'         => [
        '127.0.0.1',
        '::1',
        'localhost',
        Registry::get('config')['SERVER_HOSTNAME']
    ],

    // OpenDKIM user (default: opendkim)
    'opendkim_user'                  => 'opendkim',

    // Plugin working level (default: admin)
    //
    // Possible values:
    //  admin   : DKIM feature is automatically activated for all customers.
    //  reseller: DKIM feature is activated by resellers for customer of their
    //            choice. This is the historical behavior (prior version 2.0.0).
    'plugin_working_level'           => 'admin',

    // Postfix smtpd milter for OpenDKIM (default: unix:/var/run/opendkim/opendkim.sock)
    //
    // Possible values:
    //  'unix:/var/run/opendkim/opendkim.sock' for connection through UDS
    //  'inet:localhost:12345' for connection through TCP
    'postfix_milter_socket'          => 'unix:/var/run/opendkim/opendkim.sock',

    // Postfix run directory (default: /var/spool/postfix/var/run)
    'postfix_rundir'                 => "{$postfixConfig['POSTFIX_QUEUE_DIR']}/var/run"
];
