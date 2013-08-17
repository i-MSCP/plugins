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
 * @copyright   2010-2013 by i-MSCP Team
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
 * @param iMSCP_Plugin_Manager $pluginManager
 * @param int $userId
 * @return void
 */
function opendkim_generateActivatedDomains($tpl, $userId)
{
	/** @var $cfg iMSCP_Config_Handler_File */
	$cfg = iMSCP_Registry::get('config');

	$query = "
		SELECT
			`t2`.*
		FROM
			`domain` AS `t1`
		LEFT JOIN
			`opendkim` AS `t2` ON(`t1`.`domain_id` = `t2`.`domain_id`)
		WHERE
			`t1`.`domain_admin_id` = ?
		AND
			`t2`.`alias_id` = '0'
		ORDER BY
			`domain_name` ASC
	";
	
	
	$stmt = exec_query($query, $userId);
	
	if ($stmt->rowCount()) {
		
		while ($data = $stmt->fetchRow()) {
			$query = "
				SELECT
					`t1`.*, `t2`.*
				FROM
					`opendkim` AS `t1`
				LEFT JOIN
					`domain_dns` AS `t2` 
						ON(
							`t1`.`domain_id` = `t2`.`domain_id` 
						AND
							`t1`.`alias_id` = `t2`.`alias_id`
						AND 
							`t2`.`domain_dns` = CONCAT('mail._domainkey.', `t1`.`domain_name`)
						)
				WHERE
					`t1`.`domain_id` = ?
				ORDER BY
					`t1`.`domain_id` ASC, `t1`.`alias_id` ASC
			";
			$stmt2 = exec_query($query, $data['domain_id']);
			
			if ($stmt2->rowCount()) {
				while ($data2 = $stmt2->fetchRow()) {
					$tpl->assign(
						array(
							'OPENDKIM_DOMAIN_NAME' => decode_idna($data2['domain_name']),
							'OPENDKIM_DOMAIN_KEY' => ($data2['domain_text']) ? $data2['domain_text'] : tr('No OpenDKIM domain key in your dns table available. Please refresh this site'),
							'OPENDKIM_id' => $data2['opendkim_id'],
							'OPENDKIM_KEY_STATUS' => $data2['opendkim_status']
						)
					);

					$tpl->parse('OPENDKIM_DOMAINKEY_ITEM', '.opendkim_domainkey_item');
				}
			} else {
				$tpl->assign(
					array(
						'OPENDKIM_DOMAINKEY_ITEM' => ''
					)
				);
			}
			
			$tpl->assign(
				array(
					'TR_OPENDKIM_DOMAIN' => tr('OpenDKIM domain entries')
				)
			);
			
			$tpl->parse('OPENDKIM_CUSTOMER_ITEM', '.opendkim_customer_item');
			
			$tpl->assign(
					array(
						'OPENDKIM_DOMAINKEY_ITEM' => ''
					)
				);
		}
		
		$tpl->assign(
			array(
				'OPENDKIM_NO_CUSTOMER_ITEM' => ''
			)
		);
		
		$tpl->parse('OPENDKIM_CUSTOMER_LIST', 'opendkim_customer_list');
	} else {
		$tpl->assign(
			array(
				'OPENDKIM_CUSTOMER_LIST' => '',
				'SCROLL_PREV' => '',
				'SCROLL_PREV_GRAY' => '',
				'SCROLL_NEXT' => '',
				'SCROLL_NEXT_GRAY' => '',
			)
		);
	}
}

/***********************************************************************************************************************
 * Main
 */

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptStart);

/** @var $cfg iMSCP_Config_Handler_File */
$cfg = iMSCP_Registry::get('config');

check_login('user', $cfg->PREVENT_EXTERNAL_LOGIN_CLIENT);

$tpl = new iMSCP_pTemplate();
$tpl->define_dynamic(
	array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => '../../plugins/OpenDKIM/frontend/client/opendkim.tpl',
		'page_message' => 'layout',
		'opendkim_customer_list' => 'page',
		'opendkim_customer_item' => 'page',
		'opendkim_domainkey_item' => 'page',
		'opendkim_no_customer_item' => 'page'
	)
);

$tpl->assign(
	array(
		'TR_PAGE_TITLE' => tr('Domains / OpenDKIM'),
		'THEME_CHARSET' => tr('encoding'),
		'ISP_LOGO' => layout_getUserLogo(),
		'TR_OPENDKIM_DOMAIN_NAME' => tr('Domain'),
		'TR_OPENDKIM_NO_DOMAIN' => tr('OpenDKIM domain entries'),
		'OPENDKIM_NO_DOMAIN' => tr('No domain for OpenDKIM support activated'),
		'TR_OPENDKIM_DOMAIN_KEY' => tr('OpenDKIM domain key'),
		'TR_OPENDKIM_KEY_STATUS' => tr('Status'),
		'TR_PREVIOUS' => tr('Previous'),
		'TR_NEXT' => tr('Next')
	)
);

generateNavigation($tpl);

opendkim_generateActivatedDomains($tpl, $_SESSION['user_id']);

generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');

iMSCP_Events_Manager::getInstance()->dispatch(iMSCP_Events::onClientScriptEnd, array('templateEngine' => $tpl));

$tpl->prnt();
