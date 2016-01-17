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

namespace OpenDKIM;

use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventManager;
use iMSCP_Plugin_OpenDKIM as OpenDKIM;
use iMSCP_pTemplate as TemplateEngine;
use PDO;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate page
 *
 * @param $tpl TemplateEngine
 * @return void
 */
function opendkim_generatePage($tpl)
{
	$stmt = exec_query(
		'
			SELECT
				opendkim_id, domain_name, opendkim_status, domain_dns, domain_text
			FROM
				opendkim
			LEFT JOIN domain_dns ON(
					domain_dns.domain_id = opendkim.domain_id
				AND
					domain_dns.alias_id = IFNULL(opendkim.alias_id, 0)
				AND
					owned_by = ?
			)
			WHERE
				admin_id = ?
		',
		array('OpenDKIM_Plugin', $_SESSION['user_id'])
	);

	if ($stmt->rowCount()) {
		while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
			if ($row['opendkim_status'] == 'ok') {
				$statusIcon = 'ok';
			} elseif ($row['opendkim_status'] == 'disabled') {
				$statusIcon = 'disabled';
			} elseif (in_array(
				$row['opendkim_status'],
				array('toadd', 'tochange', 'todelete', 'torestore', 'tochange', 'toenable', 'todisable', 'todelete'))
			) {
				$statusIcon = 'reload';
			} else {
				$statusIcon = 'error';
			}

			if ($row['domain_text']) {
				if (strpos($row['domain_dns'], ' ') !== false) {
					$dnsName = explode(' ', $row['domain_dns']);
					$dnsName = $dnsName[0];
				} else {
					$dnsName = $row['domain_dns'];
				}
			} else {
				$dnsName = '';
			}

			$tpl->assign(array(
				'DOMAIN_NAME' => decode_idna($row['domain_name']),
				'DOMAIN_KEY' => ($row['domain_text']) ? tohtml($row['domain_text']) : tr('Generation in progress.'),
				'OPENDKIM_ID' => $row['opendkim_id'],
				'DNS_NAME' => ($dnsName) ? tohtml($dnsName) : tr('n/a'),
				'KEY_STATUS' => translate_dmn_status($row['opendkim_status']),
				'STATUS_ICON' => $statusIcon
			));

			$tpl->parse('DOMAINKEY_ITEM', '.domainkey_item');
		}
	} else {
		$tpl->assign('CUSTOMER_LIST', '');
		set_page_message(tr('No domain with OpenDKIM support has been found.'), 'static_info');
	}
}

/***********************************************************************************************************************
 * Main
 */

EventManager::getInstance()->dispatch(Events::onClientScriptStart);
check_login('user');

if (OpenDKIM::customerHasOpenDKIM(intval($_SESSION['user_id']))) {
	$tpl = new TemplateEngine();
	$tpl->define_dynamic(array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => '../../plugins/OpenDKIM/themes/default/view/client/opendkim.tpl',
		'page_message' => 'layout',
		'customer_list' => 'page',
		'domainkey_item' => 'customer_list'
	));

	$tpl->assign(array(
		'TR_PAGE_TITLE' => tr('Customers / OpenDKIM'),
		'TR_DOMAIN_NAME' => tr('Domain'),
		'TR_DOMAIN_KEY' => tr('OpenDKIM domain key'),
		'TR_DNS_NAME' => tr('Name'),
		'TR_KEY_STATUS' => tr('Status')
	));

	generateNavigation($tpl);
	opendkim_generatePage($tpl);
	generatePageMessage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');
	EventManager::getInstance()->dispatch(Events::onClientScriptEnd, array('templateEngine' => $tpl));
	$tpl->prnt();
} else {
	showBadRequestErrorPage();
}
