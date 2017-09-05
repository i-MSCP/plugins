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

use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventManager;
use iMSCP_Plugin_OpenDKIM as OpenDKIM;
use iMSCP_pTemplate as TemplateEngine;
use iMSCP_Registry as Registry;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Generate page
 *
 * @param $tpl TemplateEngine
 * @return void
 */
function opendkim_generatePage(TemplateEngine $tpl)
{
    $stmt = exec_query(
        "
            SELECT t1.domain_name, t1.opendkim_status, t2.domain_dns, t2.domain_text
            FROM opendkim AS t1
            LEFT JOIN domain_dns AS t2 ON(
                t2.domain_id = t1.domain_id
                AND t2.domain_dns NOT LIKE '\\_adsp%'
                AND t2.alias_id = IFNULL(t1.alias_id, 0)
                AND t2.owned_by = 'OpenDKIM_Plugin'
            ) WHERE t1.admin_id = ?
            AND t1.is_subdomain <> 1
        ",
        $_SESSION['user_id']
    );

    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
        if ($row['opendkim_status'] == 'ok') {
            $statusIcon = 'ok';
        } elseif ($row['opendkim_status'] == 'disabled') {
            $statusIcon = 'disabled';
        } elseif (in_array($row['opendkim_status'], ['toadd', 'tochange', 'todelete'])) {
            $statusIcon = 'reload';
        } else {
            $statusIcon = 'error';
        }

        if ($row['domain_text'] !== NULL) {
            if (strpos($row['domain_dns'], ' ') !== false) {
                list($dnsName, $ttl) = explode(' ', $row['domain_dns']);
                if (substr($dnsName, -1) != '.') {
                    $dnsName .= ".{$row['domain_name']}.";
                }
            } else {
                $dnsName = $row['domain_dns'];
                $ttl = tr('Default');
            }
        } else {
            $dnsName = tr('N/A');
            $ttl = tr('N/A');
        }

        $tpl->assign([
            'DNS_STATUS'    => tohtml(translate_dmn_status($row['opendkim_status'])),
            'STATUS_ICON'   => tohtml($statusIcon, 'htmlAttr'),
            'DNS_ZONE_NAME' => tohtml(decode_idna($row['domain_name'])),
            'DNS_NAME'      => tohtml($dnsName),
            'DNS_TTL'       => tohtml($ttl)
        ]);

        if (NULL != $row['domain_text']) {
            $tpl->assign([
                'DNS_RDATA'   => tohtml($row['domain_text'], 'htmlAttr'),
                'DKIM_KEY_NA' => ''
            ]);
            $tpl->parse('DKIM_KEY_TO_CLIPBOARD', 'dkim_key_to_clipboard');
        } else {
            $tpl->assign('DKIM_KEY_TO_CLIPBOARD', '');
            $tpl->parse('DKIM_KEY_NA', 'dkim_key_na');
        }

        $tpl->parse('DKIM_KEY_DNS_ENTRY', '.dkim_key_dns_entry');
    }

    $stmt = exec_query(
        "
            SElECT t1.domain_name, t1.opendkim_status, t2.domain_dns, t2.domain_text
            FROM opendkim AS t1
            JOIN domain_dns AS t2 ON(
                t2.domain_id = t1.domain_id
                AND t2.alias_id = IFNULL(t1.alias_id, 0)
                AND t2.domain_dns LIKE '\\_adsp%'
                AND t2.owned_by = 'OpenDKIM_Plugin'
            )
            WHERE
            t1.admin_id = ?
            AND t1.is_subdomain <> 1
            
        ",
        $_SESSION['user_id']
    );

    if (!$stmt->rowCount()) {
        $tpl->assign('DKIM_ADSP_DNS', '');
        return;
    }

    while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
        if ($row['opendkim_status'] == 'ok') {
            $statusIcon = 'ok';
        } elseif ($row['opendkim_status'] == 'disabled') {
            $statusIcon = 'disabled';
        } elseif (in_array($row['opendkim_status'], ['toadd', 'tochange', 'todelete'])) {
            $statusIcon = 'reload';
        } else {
            $statusIcon = 'error';
        }

        if ($row['domain_text'] !== NULL) {
            if (strpos($row['domain_dns'], ' ') !== false) {
                list($dnsName, $ttl) = explode(' ', $row['domain_dns']);
                if (substr($dnsName, -1) != '.') {
                    $dnsName .= ".{$row['domain_name']}.";
                }
            } else {
                $dnsName = $row['domain_dns'];
                $ttl = tr('Default');
            }
        } else {
            $dnsName = tr('N/A');
            $ttl = tr('N/A');
        }

        $tpl->assign([
            'DNS_STATUS'    => tohtml(translate_dmn_status($row['opendkim_status'])),
            'DNS_ZONE_NAME' => tohtml(decode_idna($row['domain_name'])),
            'DNS_NAME'      => tohtml($dnsName),
            'DNS_TTL'       => tohtml($ttl),
            'STATUS_ICON'   => tohtml($statusIcon, 'htmlAttr')
        ]);

        if (NULL != $row['domain_text']) {
            $tpl->assign('DNS_RDATA', tohtml($row['domain_text'], 'htmlAttr'));
            $tpl->assign('DKIM_ADSP_NA', '');
            $tpl->parse('DKIM_ADSP_TO_CLIPBOARD', 'dkim_adsp_to_clipboard');
        } else {
            $tpl->assign('DKIM_ADSP_TO_CLIPBOARD', '');
            $tpl->parse('DKIM_ADSP_NA', 'dkim_adsp_na');
        }

        $tpl->parse('DKIM_ADSP_DNS_ENTRY', '.dkim_adsp_dns_entry');
    }
}

/***********************************************************************************************************************
 * Main
 */

check_login('user');
EventManager::getInstance()->dispatch(Events::onClientScriptStart);
OpenDKIM::customerHasOpenDKIM($_SESSION['user_id']) or showBadRequestErrorPage();

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'                 => 'shared/layouts/ui.tpl',
    'page'                   => '../../plugins/OpenDKIM/themes/default/view/client/opendkim.phtml',
    'page_message'           => 'layout',
    'dkim_key_dns_entry'     => 'page',
    'dkim_key_to_clipboard'  => 'dkim_key_dns_entry',
    'dkim_key_na'            => 'dkim_key_dns_entry',
    'dkim_adsp_dns'          => 'page',
    'dkim_adsp_dns_entry'    => 'dkim_adsp_dns',
    'dkim_adsp_to_clipboard' => 'dkim_adsp_dns_entry',
    'dkim_adsp_na'           => 'dkim_adsp_dns_entry'
]);

$tpl->assign([
    'TR_PAGE_TITLE'          => tohtml(tr('Client / Mail / DKIM DNS Records')),
    'OPENDKIM_ASSET_VERSION' => tourl(Registry::get('pluginManager')->pluginGetInfo('OpenDKIM')['build'])
]);

generateNavigation($tpl);
opendkim_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventManager::getInstance()->dispatch(Events::onClientScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();
