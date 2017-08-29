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

use iMSCP_Database as Database;
use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventManager;
use iMSCP_Exception_Database as DatabaseException;
use iMSCP_pTemplate as TemplateEngine;
use iMSCP_Registry as Registry;

/***********************************************************************************************************************
 * Functions
 */

/**
 * Activate OpenDKIM for the given customer
 *
 * @throws DatabaseException
 * @param int $customerId Customer unique identifier
 */
function opendkim_activate($customerId)
{
    $stmt = exec_query(
        "
            SELECT domain_id, domain_name FROM domain INNER JOIN admin ON(admin_id = domain_admin_id)
            WHERE admin_id = ? AND created_by = ? AND admin_status = 'ok'
        ",
        [$customerId, $_SESSION['user_id']]
    );

    if (!$stmt->rowCount()) {
        showBadRequestErrorPage();
    }

    $row = $stmt->fetchRow(PDO::FETCH_ASSOC);
    $db = Database::getInstance();

    try {
        $db->beginTransaction();

        exec_query(
            "INSERT INTO opendkim (admin_id, domain_id, domain_name, opendkim_status) VALUES (?, ?, ?, 'toadd')",
            [$customerId, $row['domain_id'], $row['domain_name']]
        );
        exec_query(
            "
                INSERT INTO opendkim (
                    admin_id, domain_id, alias_id, domain_name, opendkim_status
                ) SELECT ?, domain_id, alias_id, alias_name, 'toadd'
                FROM domain_aliasses
                WHERE domain_id = ?
                AND alias_status = 'ok'
            ",
            [$customerId, $row['domain_id']]
        );

        $db->commit();
        send_request();
        set_page_message(tr('OpenDKIM support scheduled for activation. This can take few seconds.'), 'success');
    } catch (DatabaseException $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Deactivate OpenDKIM for the given customer
 *
 * @param int $customerId Customer unique identifier
 * @return void
 */
function opendkim_deactivate($customerId)
{
    $stmt = exec_query(
        "SELECT COUNT(admin_id) FROM admin WHERE admin_id = ? AND created_by = ? AND admin_status = 'ok'",
        [$customerId, $_SESSION['user_id']]
    );

    if ($stmt->fetchRow(PDO::FETCH_COLUMN) < 1) {
        showBadRequestErrorPage();
    }

    exec_query("UPDATE opendkim SET opendkim_status = 'todelete' WHERE admin_id = ?", $customerId);
    send_request();
    set_page_message(tr('OpenDKIM support scheduled for deactivation. This can take few seconds.'), 'success');
}

/**
 * Generate customer list for which OpenDKIM can be activated
 *
 * @param $tpl TemplateEngine
 * @return void
 */
function _opendkim_generateCustomerList(TemplateEngine $tpl)
{
    $stmt = exec_query(
        "
            SELECT admin_id, admin_name
            FROM admin
            WHERE created_by = ?
            AND admin_status = 'ok'
            AND admin_id NOT IN (SELECT admin_id FROM opendkim)
            ORDER BY admin_name ASC
        ",
        $_SESSION['user_id']
    );

    if (!$stmt->rowCount()) {
        $tpl->assign('SELECT_LIST', '');
        return;
    }

    while ($row = $stmt->fetchRow(PDO::FETCH_ASSOC)) {
        $tpl->assign([
            'SELECT_VALUE' => tohtml($row['admin_id'], 'htmlAttr'),
            'SELECT_NAME'  => tohtml(decode_idna($row['admin_name'])),
        ]);
        $tpl->parse('SELECT_ITEM', '.select_item');
    }
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl
 * @return void
 */
function opendkim_generatePage(TemplateEngine $tpl)
{
    _opendkim_generateCustomerList($tpl);

    $rowsPerPage = Registry::get('config')['DOMAIN_ROWS_PER_PAGE'];

    if (isset($_GET['psi']) && $_GET['psi'] == 'last') {
        unset($_GET['psi']);
    }

    $startIndex = isset($_GET['psi']) ? intval($_GET['psi']) : 0;
    $rowCount = exec_query(
        '
            SELECT COUNT(admin_id)
            FROM admin
            JOIN opendkim USING(admin_id)
            WHERE created_by = ? AND alias_id IS NULL
        ',
        $_SESSION['user_id']
    )->fetchRow(PDO::FETCH_COLUMN);

    if (!$rowCount) {
        $tpl->assign('CUSTOMER_LIST', '');
        set_page_message(tr('No customer with OpenDKIM support has been found.'), 'static_info');
        return;
    }

    $stmt = exec_query(
        "
            SELECT admin_name, admin_id
            FROM admin
            JOIN opendkim USING(admin_id)
            WHERE created_by = ?
            AND alias_id IS NULL
            ORDER BY admin_id ASC LIMIT $startIndex, $rowsPerPage
        ",
        $_SESSION['user_id']
    );

    while ($row = $stmt->fetchRow()) {
        $stmt2 = exec_query(
            "
                SELECT opendkim_id, domain_name, opendkim_status, domain_dns, domain_text
                FROM opendkim AS t1
                LEFT JOIN domain_dns AS t2 ON(
                    t2.domain_id = t1.domain_id
                    AND t2.domain_dns NOT LIKE '\\_adsp%'
                    AND t2.alias_id = IFNULL(t1.alias_id, 0)
                    AND t2.owned_by = 'OpenDKIM_Plugin'
                ) WHERE t1.admin_id = ?
            ",
            $row['admin_id']
        );

        if ($stmt2->rowCount()) {
            while ($row2 = $stmt2->fetchRow()) {
                if ($row2['opendkim_status'] == 'ok') {
                    $statusIcon = 'ok';
                } elseif ($row2['opendkim_status'] == 'disabled') {
                    $statusIcon = 'disabled';
                } elseif (in_array($row2['opendkim_status'], ['toadd', 'tochange', 'todelete'])) {
                    $statusIcon = 'reload';
                } else {
                    $statusIcon = 'error';
                }

                if ($row2['domain_text']) {
                    if (strpos($row2['domain_dns'], ' ') !== false) {
                        $dnsName = explode(' ', $row2['domain_dns']);
                        $dnsName = $dnsName[0];
                    } else {
                        $dnsName = $row2['domain_dns'];
                    }
                } else {
                    $dnsName = '';
                }

                $tpl->assign([
                    'KEY_STATUS'  => translate_dmn_status($row2['opendkim_status']),
                    'STATUS_ICON' => $statusIcon,
                    'DOMAIN_NAME' => tohtml(decode_idna($row2['domain_name'])),
                    'DOMAIN_KEY'  => ($row2['domain_text'])
                        ? tohtml($row2['domain_text']) : tohtml(tr('Generation in progress.')),
                    'DNS_NAME'    => ($dnsName) ? tohtml($dnsName) : tohtml(tr('n/a')),
                    'OPENDKIM_ID' => tohtml($row2['opendkim_id'])
                ]);

                $tpl->parse('KEY_ITEM', '.key_item');
            }
        }

        $tpl->assign([
            'TR_CUSTOMER'   => tohtml(tr('OpenDKIM entries for customer: %s', decode_idna($row['admin_name']))),
            'TR_DEACTIVATE' => tohtml(tr('Deactivate OpenDKIM')),
            'CUSTOMER_ID'   => tohtml($row['admin_id'])
        ]);
        $tpl->parse('CUSTOMER_ITEM', '.customer_item');
        $tpl->assign('KEY_ITEM', '');
    }

    $prevSi = $startIndex - $rowsPerPage;

    if ($startIndex == 0) {
        $tpl->assign('SCROLL_PREV', '');
    } else {
        $tpl->assign([
            'SCROLL_PREV_GRAY' => '',
            'PREV_PSI'         => $prevSi
        ]);
    }

    $nextSi = $startIndex + $rowsPerPage;

    if ($nextSi + 1 > $rowCount) {
        $tpl->assign('SCROLL_NEXT', '');
        return;
    }

    $tpl->assign([
        'SCROLL_NEXT_GRAY' => '',
        'NEXT_PSI'         => $nextSi
    ]);
}

/***********************************************************************************************************************
 * Main
 */

check_login('reseller');
EventManager::getInstance()->dispatch(Events::onResellerScriptStart);
resellerHasCustomers() or showBadRequestErrorPage();

if (isset($_REQUEST['action'])) {
    $action = clean_input($_REQUEST['action']);

    if (isset($_REQUEST['admin_id'])) {
        $customerId = intval($_REQUEST['admin_id']);

        switch ($action) {
            case 'activate':
                opendkim_activate($customerId);
                break;
            case 'deactivate';
                opendkim_deactivate($customerId);
                break;
            default:
                showBadRequestErrorPage();
        }

        redirectTo('opendkim.php');
    }

    showBadRequestErrorPage();
}

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'           => 'shared/layouts/ui.tpl',
    'page'             => '../../plugins/OpenDKIM/themes/default/view/reseller/opendkim.tpl',
    'page_message'     => 'layout',
    'select_list'      => 'page',
    'select_item'      => 'select_list',
    'customer_list'    => 'page',
    'customer_item'    => 'customer_list',
    'key_item'         => 'customer_item',
    'scroll_prev_gray' => 'customer_list',
    'scroll_prev'      => 'customer_list',
    'scroll_next_gray' => 'customer_list',
    'scroll_next'      => 'customer_list'
]);
$tpl->assign([
    'TR_PAGE_TITLE'           => tohtml(tr('Customers / OpenDKIM')),
    'TR_SELECT_NAME'          => tohtml(tr('Select a customer')),
    'TR_ACTIVATE_ACTION'      => tohtml(tr('Activate OpenDKIM for this customer')),
    'TR_DOMAIN_NAME'          => tohtml(tr('Domain Name')),
    'TR_DOMAIN_KEY'           => tohtml(tr('OpenDKIM domain key')),
    'TR_STATUS'               => tohtml(tr('Status')),
    'TR_DNS_NAME'             => tohtml(tr('Name')),
    'DEACTIVATE_DOMAIN_ALERT' => tojs(tr('Are you sure you want to deactivate OpenDKIM for this customer?')),
    'TR_PREVIOUS'             => tohtml(tr('Previous')),
    'TR_NEXT'                 => tohtml(tr('Next'))
]);

generateNavigation($tpl);
opendkim_generatePage($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventManager::getInstance()->dispatch(Events::onResellerScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();
