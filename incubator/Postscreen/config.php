<?php
/**
 * i-MSCP Postscreen plugin
 * @copyright 2015-2016 Laurent Declercq <l.declercq@nuxwin.com>
 * @copyright 2013-2016 Rene Schuster <mail@reneschuster.de>
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
    // For the Postscreen options please check man postscreen or
    // visit the online documentation: http://www.postfix.org/postscreen.8.html

    // Pregreet test (default: enforce)
    //
    // See http://www.postfix.org/POSTSCREEN_README.html#pregreet
    // Possible values: ignore, enforce, drop
    'postscreen_greet_action'              => 'enforce',

    // Postscreen dnsbl sites (default: zen.spamhaus.org*3, bl.mailspike.net*2, b.barracudacentral.org*2, 
    //      bl.spameatingmonkey.net, bl.spamcop.net, spamtrap.trblspam.com, dnsbl.sorbs.net=127.0.0.[2;3;6;7;10], 
    //      ix.dnsbl.manitu.net, bl.blocklist.de, list.dnswl.org=127.0.[0..255].0*-1, list.dnswl.org=127.0.[0..255].1*-2, 
    //      list.dnswl.org=127.0.[0..255].[2..3]*-3, iadb.isipp.com=127.0.[0..255].[0..255]*-2, 
    //      iadb.isipp.com=127.3.100.[6..200]*-2, wl.mailspike.net=127.0.0.[17;18]*-1, wl.mailspike.net=127.0.0.[19;20]*-2 )
    //
    // See http://www.postfix.org/POSTSCREEN_README.html#dnsbl
    // See http://www.postfix.org/postconf.5.html#postscreen_dnsbl_sites
    'postscreen_dnsbl_sites'               => array(
        'zen.spamhaus.org*3',
        'bl.mailspike.net*2',
        'b.barracudacentral.org*2',
        'bl.spameatingmonkey.net',
        'bl.spamcop.net',
        'dnsbl.sorbs.net=127.0.0.[2;3;6;7;10]',
        'ix.dnsbl.manitu.net',
        'bl.blocklist.de',
        'list.dnswl.org=127.0.[0..255].0*-1',
        'list.dnswl.org=127.0.[0..255].1*-2',
        'list.dnswl.org=127.0.[0..255].[2..3]*-3',
        'iadb.isipp.com=127.0.[0..255].[0..255]*-2',
        'iadb.isipp.com=127.3.100.[6..200]*-2',
        'wl.mailspike.net=127.0.0.[17;18]*-1',
        'wl.mailspike.net=127.0.0.[19;20]*-2'
    ),

    // Postscreen dnsbl threshold (default: 3)
    //
    // See http://www.postfix.org/postconf.5.html#postscreen_dnsbl_threshold
    'postscreen_dnsbl_threshold'           => '3',

    // Postscreen dnsbl whitelist threshold (default: -1)
    //
    // Allow "good" clients to skip tests with the postscreen_dnsbl_whitelist_threshold feature 
    // This feature is available in Postfix 2.11 and later.
    // See http://www.postfix.org/postconf.5.html#postscreen_dnsbl_whitelist_threshold
    'postscreen_dnsbl_whitelist_threshold' => '-1',

    // Postscreen dnsbl action (default: enforce)
    //
    // See http://www.postfix.org/postconf.5.html#postscreen_dnsbl_action
    // Possible values: options: ignore, enforce, drop
    'postscreen_dnsbl_action'              => 'enforce',

    // Permanent white/blacklist (default: permit_mynetworks, cidr:/etc/postfix/postscreen_access.cidr)
    // 
    // See http://www.postfix.org/postconf.5.html#postscreen_access_list
    'postscreen_access_list'               => array(
        'permit_mynetworks',
        'cidr:/etc/postfix/postscreen_access.cidr'
    ),

    // Postscreen blacklist action (default: enforce)
    //
    // See http://www.postfix.org/postconf.5.html#postscreen_blacklist_action
    // Possible values: ignore, enforce, drop
    'postscreen_blacklist_action'          => 'enforce'
);
