<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP Team
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
 * @author      Sascha Bay <info@space2place.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate page
 *
 * @param $tpl iMSCP_pTemplate
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
				domain_dns.domain_id = opendkim.domain_id AND domain_dns.alias_id = IFNULL(opendkim.alias_id, 0)
			)
			WHERE
				admin_id = ?
			AND
				(owned_by = ? OR owned_by IS NULL)
		',
		array($_SESSION['user_id'], 'OpenDKIM_Plugin')
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

			$tpl->assign(
				array(
					'DOMAIN_NAME' => decode_idna($row['domain_name']),
					'DOMAIN_KEY' => ($row['domain_text'])
							? tohtml($row['domain_text']) : tr('Generation in progress...'),
					'OPENDKIM_ID' => $row['opendkim_id'],
					'DNS_NAME' => ($row['domain_dns'])
							? tohtml(decode_idna($row['domain_dns'])) . '.' . tohtml(decode_idna($row['domain_name'])) . '.'
							: tr('n/a'),
					'KEY_STATUS' => translate_dmn_status($row['opendkim_status']),
					'STATUS_ICON' => $statusIcon
				)
			);

			$tpl->parse('DOMAINKEY_ITEM', '.domainkey_item');
		}
	} else {
		$tpl->assign('CUSTOMER_LIST', '');
		set_page_message(tr('No domain with OpenDKIM support has been found.'), 'info');
	}
}

/***********************************************************************************************************************
 * Main
 */

iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

check_login('user');

if (iMSCP_Plugin_OpenDKIM::customerHasOpenDKIM($_SESSION['user_id'])) {
	$tpl = new iMSCP_pTemplate();
	$tpl->define_dynamic(
		array(
			'layout' => 'shared/layouts/ui.tpl',
			'page' => '../../plugins/OpenDKIM/frontend/client/opendkim.tpl',
			'page_message' => 'layout',
			'customer_list' => 'page',
			'domainkey_item' => 'customer_list'
		)
	);

	$tpl->assign(
		array(
			'TR_PAGE_TITLE' => tr('Customers / OpenDKIM'),
			'THEME_CHARSET' => tr('encoding'),
			'ISP_LOGO' => layout_getUserLogo(),
			'TR_DOMAIN_NAME' => tr('Domain'),
			'TR_DOMAIN_KEY' => tr('OpenDKIM domain key'),
			'TR_DNS_NAME' => tr('Name'),
			'TR_KEY_STATUS' => tr('Status')
		)
	);

	generateNavigation($tpl);
	opendkim_generatePage($tpl);
	generatePageMessage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');

	iMSCP_Events_Aggregator::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

	$tpl->prnt();
} else {
	showBadRequestErrorPage();
}
