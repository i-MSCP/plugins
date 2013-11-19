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
 * @subpackage  Postscreen
 * @copyright   2010-2013 by i-MSCP Team
 * @author      Rene Schuster <mail@reneschuster.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

return array(	
	// For the different Postscreen options please check man postscreen or
	// visit the online documentation:  http://www.postfix.org/postscreen.8.html
	
	
	// Pregreet test  http://www.postfix.org/POSTSCREEN_README.html#pregreet
	// 
	// http://www.postfix.org/postconf.5.html#postscreen_dnsbl_action
	'postscreen_greet_action' => 'enforce', // options: ignore, enforce (default), drop
	
	
	// DNSBL tests  http://www.postfix.org/POSTSCREEN_README.html#dnsbl
	// 
	// http://www.postfix.org/postconf.5.html#postscreen_dnsbl_sites
	'postscreen_dnsbl_sites' => array(
		'zen.spamhaus.org*2',
		'dnsbl-1.uceprotect.net*1',
		'bl.spamcop.net*1',
		'list.dnswl.org=127.0.[0..255].[1..3]*-2'
	),
	
	// http://www.postfix.org/postconf.5.html#postscreen_dnsbl_threshold
	'postscreen_dnsbl_threshold' => '3',
	
	// http://www.postfix.org/postconf.5.html#postscreen_dnsbl_action
	'postscreen_dnsbl_action' => 'enforce', // options: ignore, enforce (default), drop
	
	
	// Permanent white/blacklist
	// 
	// http://www.postfix.org/postconf.5.html#postscreen_access_list 
	'postscreen_access_list' => array(
		'permit_mynetworks',
		'cidr:/etc/postfix/postscreen_access.cidr'
	),
	
	// http://www.postfix.org/postconf.5.html#postscreen_blacklist_action
	'postscreen_blacklist_action' => 'enforce', // options: ignore, enforce (default), drop
	
	
	// Patch Mailgraph to count and also show the Postscreen rejects on the graphs
	'patch_mailgraph' => 'yes', // YES to enable (default), NO to disable
	
	// Policyd-weight is not necessary anymore and could be disabled.
	// The main functionality of policyd-weight (RBL checks) is integrated in Postscreen (postscreen_dnsbl_sites).
	'disable_policyd-weight' => 'yes', // YES to enable (default), NO to disable
	
	// Postgrey is also not necessary anymore, because you eliminate up to 90% of the spam with Postscreen.
	'disable_postgrey' => 'yes' // YES to enable (default), NO to disable
);
