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
 * Get OpenDKIM status for the given customer
 *
 * - If all items statuses are 'ok', we return 'ok' status
 * - If there is one status denoting an error, we return it (the first found),
 *   else, we return the task status (again the first found)
 *
 * @param int $adminId
 * @return string
 */
function getOpendkimStatus($adminId)
{
    $stmt = exec_query(
        "SELECT opendkim_status FROM opendkim WHERE admin_id = ? AND opendkim_status <> 'ok' LIMIT 1", $adminId
    );

    if (!$stmt->rowCount()) {
        return 'ok';
    }

    $status = $stmt->fetchRow(PDO::FETCH_COLUMN);
    $stmt = exec_query(
        "
            SELECT opendkim_status
            FROM opendkim WHERE admin_id = ?
            AND opendkim_status NOT IN ('ok', 'toadd', 'todelete', 'tochange') LIMIT 1
        ",
        $adminId
    );

    return $stmt->fetchRow(PDO::FETCH_COLUMN) ?: $status;
}

/**
 * Send Json response
 *
 * @param int $statusCode HTTP status code
 * @param array $data JSON data
 * @return void
 */
function sendJsonResponse($statusCode = 200, array $data = [])
{
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Content-type: application/json');

    switch ($statusCode) {
        case 202:
            header('Status: 202 Accepted');
            break;
        case 400:
            header('Status: 400 Bad Request');
            break;
        case 404:
            header('Status: 404 Not Found');
            break;
        case 409:
            header('Status: 409 Conflict');
            break;
        case 500:
            header('Status: 500 Internal Server Error');
            break;
        case 501:
            header('Status: 501 Not Implemented');
            break;
        default:
            header('Status: 200 OK');
    }

    exit(json_encode($data));
}

/**
 * Schedule renewal of all DKIM key that belong to the given customer
 *
 * @return void
 */
function renewKeys()
{
    if (!isset($_POST['admin_name'])) {
        sendJsonResponse(400, ['message' => tr('Bad request.')]);
    }

    $adminName = strval($_POST['admin_name']);
    try {
        $stmt = exec_query(
            "
            SELECT admin_id
            FROM opendkim AS t1
            JOIN admin AS t2 USING(admin_id)
            WHERE t2.admin_name = ?
            AND t2.created_by = ?
            AND t2.admin_status = 'ok'
            LIMIT 1
        ",
            [encode_idna($adminName), $_SESSION['user_id']]
        );

        if (!$stmt->rowCount()) {
            sendJsonResponse(400, ['message' => tr('Bad request.')]);
        }

        exec_query(
            "UPDATE opendkim SET opendkim_status = 'tochange' WHERE admin_id = ? AND is_subdomain <> 1",
            $stmt->fetchRow(PDO::FETCH_COLUMN)
        );
        send_request();
        write_log(
            sprintf('OpenDKIM keys for the %s customer were scheduled for renewal.', $adminName), E_USER_NOTICE
        );
        sendJsonResponse(200, ['message' => tr('DKIM keys for the %s customer were scheduled for renewal.', $adminName)]);
    } catch (Exception $e) {
        write_log(
            sprintf(
                "OpenDKIM: Couldn't schedule renewal of DKIM keys for the %s customer: %s",
                $adminName,
                $e->getMessage()
            ),
            E_USER_ERROR
        );
        sendJsonResponse(500, ['message' => tr('An unexpected error occurred. Please contact your administrator.')]);
    }
}

/**
 * Activate OpenDKIM for the given customer
 *
 * @throws DatabaseException
 */
function activateOpenDKIM()
{
    if (!isset($_POST['admin_name'])) {
        sendJsonResponse(400, ['message' => tr('Bad request.')]);
    }

    $adminName = clean_input($_POST['admin_name']);
    $db = Database::getInstance();

    try {
        $stmt = exec_query(
            "
                SELECT t1.admin_id, t2.domain_id, t2.domain_name
                FROM admin AS t1
                JOIN domain AS t2 ON(t2.domain_admin_id = t1.admin_id)
                WHERE t1.admin_name = ?
                AND t1.created_by = ?
                AND t1.admin_status = 'ok'
                AND t1.admin_id NOT IN (SELECT DISTINCT admin_id FROM opendkim)
            ",
            [encode_idna($adminName), $_SESSION['user_id']]
        );

        if (!$stmt->rowCount()) {
            sendJsonResponse(400, ['message' => tr('Invalid customer.')]);
        }

        $row = $stmt->fetchRow(PDO::FETCH_ASSOC);
        $adminId = $row['admin_id'];

        $db->beginTransaction();

        // Add entry for main domain name
        exec_query(
            "INSERT IGNORE INTO opendkim (admin_id, domain_id, domain_name, opendkim_status) VALUES (?, ?, ?, 'toadd')",
            [$adminId, $row['domain_id'], $row['domain_name']]
        );

        # Add entries for subdomains (sub)
        exec_query(
            "
                INSERT IGNORE INTO opendkim (admin_id, domain_id, domain_name, is_subdomain, opendkim_status)
                SELECT domain_admin_id, t1.domain_id, CONCAT(t1.subdomain_name, '.', t2.domain_name), 1, 'toadd'
                FROM subdomain AS t1
                JOIN domain AS t2 ON(t2.domain_id = t1.domain_id)
                WHERE t1.domain_id = ?
                AND t1.subdomain_status <> 'todelete'
            ",
            $row['domain_id']
        );

        // Add entries for domain aliases
        exec_query(
            "
                INSERT IGNORE INTO opendkim (admin_id, domain_id, alias_id, domain_name, opendkim_status)
                SELECT ?, domain_id, alias_id, alias_name, 'toadd'
                FROM domain_aliasses
                WHERE domain_id = ?
                AND alias_status <> 'todelete'
            ",
            [$adminId, $row['domain_id']]
        );

        # Add entries for subdomains (alssub)
        exec_query(
            "
                INSERT IGNORE INTO opendkim (
                    admin_id, domain_id, alias_id, domain_name, is_subdomain, opendkim_status
                ) SELECT ?, t2.domain_id, t2.alias_id, CONCAT(t1.subdomain_alias_name, '.', t2.alias_name), 1, 'toadd'
                FROM subdomain_alias AS t1
                JOIN domain_aliasses AS t2 ON(t2.alias_id = t1.alias_id)
                WHERE t2.domain_id = ?
                AND t1.subdomain_alias_status <> 'todelete'
            ",
            [$adminId, $row['domain_id']]
        );

        $db->commit();
        send_request();
        write_log(sprintf('OpenDKIM has been activated for the %s customer.', $adminName), E_USER_NOTICE);
        sendJsonResponse(200, ['message' => tr('OpenDKIM will be activated for the %s customer.', $adminName)]);
    } catch (Exception $e) {
        $db->rollBack();
        write_log(
            sprintf("OpenDKIM: Couldn't activate OpenDKIM for the %s customer: %s", $adminName, $e->getMessage()),
            E_USER_ERROR
        );
        sendJsonResponse(500, ['message' => tr('An unexpected error occurred. Please contact your administrator.')]);
    }
}

/**
 * Deactivate OpenDKIM for the given customer
 *
 * @return void
 */
function deactivateOpenDKIM()
{
    if (!isset($_POST['admin_name'])) {
        sendJsonResponse(400, ['message' => tr('Bad request.')]);
    }

    $adminName = clean_input($_POST['admin_name']);

    try {
        $stmt = exec_query(
            "
                SELECT admin_id
                FROM admin
                WHERE admin_name = ?
                AND created_by = ?
                AND admin_status = 'ok'
                AND admin_id IN (SELECT DISTINCT admin_id FROM opendkim)
                AND admin_id NOT IN(SELECT DISTINCT admin_id FROM opendkim WHERE opendkim_status <> 'ok')
            ",
            [encode_idna($adminName), $_SESSION['user_id']]
        );

        if ($stmt->fetchRow(PDO::FETCH_COLUMN) < 1) {
            sendJsonResponse(400, ['message' => tr('Bad request.')]);
        }

        exec_query(
            "UPDATE opendkim SET opendkim_status = 'todelete' WHERE admin_id = ?", $stmt->fetchRow(PDO::FETCH_COLUMN)
        );
        send_request();
        write_log(sprintf('OpenDKIM has been deactivate for the %s customer.', $adminName), E_USER_NOTICE);
        sendJsonResponse(200, ['message' => tr('OpenDKIM will be deactivated for the %s customer.', $adminName)]);
    } catch (Exception $e) {
        write_log(
            sprintf("OpenDKIM: Couldn't deactivate OpenDKIM for the %s customer: %s", $adminName, $e->getMessage()),
            E_USER_ERROR
        );
        sendJsonResponse(500, ['message' => tr('An unexpected error occurred. Please contact your administrator.')]);
    }
}

/**
 * Search customer for which OpenDKIM is not activated yet
 *
 * @return void
 */
function searchCustomer()
{
    if (!isset($_GET['term'])) {
        sendJsonResponse(400, ['message' => tr('Bad request.')]);
    }

    try {
        $stmt = exec_query(
            "
                SELECT admin_name
                FROM admin
                WHERE admin_name LIKE ?
                AND created_by = ?
                AND admin_status = 'ok'
                AND admin_id NOT IN(SELECT DISTINCT admin_id FROM opendkim)
            ",
            [clean_input($_GET['term']) . '%', $_SESSION['user_id']]
        );
        sendJsonResponse(200, ($stmt->rowCount()) ? $stmt->fetchAll(PDO::FETCH_COLUMN) : []);
    } catch (Exception $e) {
        write_log(sprintf("OpenDKIM: Couldn't search customer: %s", $e->getMessage()), E_USER_ERROR);
        sendJsonResponse(500, ['message' => tr('An unexpected error occurred. Please contact your administrator.')]);
    }
}

/**
 * Get list of customer for which OpenDKIM is activated
 *
 * @return void
 */
function getCustomerList()
{
    try {
        // Filterable / order-able columns
        $cols = ['admin_id', 'admin_name'];
        $nbCols = count($cols);
        $idxCol = 'admin_id';
        $table = 'opendkim'; /* DB table to use */

        /* Paging */
        $limit = '';
        if (isset($_GET['iDisplayStart'])
            && isset($_GET['iDisplayLength'])
            && $_GET['iDisplayLength'] !== '-1'
        ) {
            $limit = 'LIMIT ' . intval($_GET['iDisplayStart']) . ', ' . intval($_GET['iDisplayLength']);
        }

        /* Ordering */
        $order = '';
        if (isset($_GET['iSortCol_0'])
            && isset($_GET['iSortingCols'])
        ) {
            $order = 'ORDER BY ';
            for ($i = 0; $i < intval($_GET['iSortingCols']); $i++) {
                if ($_GET['bSortable_' . intval($_GET["iSortCol_$i"])] === 'true') {
                    $sortDir = (isset($_GET["sSortDir_$i"])
                        && in_array($_GET["sSortDir_$i"], ['asc', 'desc'])
                    ) ? $_GET['sSortDir_' . $i] : 'asc';
                    $order .= $cols[intval($_GET["iSortCol_$i"])] . ' ' . $sortDir . ', ';
                }
            }

            $order = substr_replace($order, '', -2);
            if ($order == 'ORDER BY') {
                $order = '';
            }
        }

        /* Filtering */
        $where = "WHERE admin_type = 'user' AND created_by = ?";
        if (isset($_GET['sSearch'])
            && $_GET['sSearch'] !== ''
        ) {
            $where .= 'AND (';
            for ($i = 0; $i < $nbCols; $i++) {
                if (!isset($cols[$i])) {
                    continue;
                }

                $where .= $cols[$i] . ' LIKE ' . quoteValue('%' . $_GET['sSearch'] . '%') . ' OR ';
            }

            $where = substr_replace($where, '', -3);
            $where .= ')';
        }

        /* Individual column filtering */
        for ($i = 0; $i < $nbCols; $i++) {
            if (isset($cols[$i])
                && isset($_GET["bSearchable_$i"])
                && $_GET["bSearchable_$i"] === 'true'
                && $_GET["sSearch_$i"] !== ''
            ) {
                $where .= "AND $cols[$i] LIKE " . quoteValue('%' . $_GET['sSearch_' . $i] . '%');
            }
        }

        /* Get data to display */
        /** @var \iMSCP_Database_ResultSet $rResult */
        $rResult = exec_query(
            '
                SELECT SQL_CALC_FOUND_ROWS DISTINCT ' . implode(', ', $cols) . "
                FROM $table AS t1
                INNER JOIN admin USING(admin_id)
                $where $order $limit
            ",
            $_SESSION['user_id']
        );

        /* Data set length after filtering */
        /** @var \iMSCP_Database_ResultSet $resultFilterTotal */
        $filteredTotal = execute_query('SELECT FOUND_ROWS()')->fetchRow(PDO::FETCH_COLUMN);

        /* Total data set length */
        /** @var \iMSCP_Database_ResultSet $resultTotal */
        $total = exec_query(
            "
                SELECT COUNT(DISTINCT $idxCol)
                FROM $table
                INNER JOIN admin USING(admin_id)
                WHERE admin_type = 'user'
                AND created_by = ?
            ",
            $_SESSION['user_id']
        )->fetchRow(PDO::FETCH_COLUMN);

        /* Output */
        $output = [
            'sEcho'                => intval($_GET['sEcho']),
            'iTotalRecords'        => $total,
            'iTotalDisplayRecords' => $filteredTotal,
            'aaData'               => []
        ];

        $trDeactivate = tr('Deactivate');
        $trRenewKeys = tr('Renew keys');
        $adminId = 0;

        while ($data = $rResult->fetchRow()) {
            $row = [];

            for ($i = 0; $i < $nbCols; $i++) {
                if ($cols[$i] == 'admin_id') {
                    $adminId = $data[$cols[$i]];
                    continue;
                }

                $row[$cols[$i]] = tohtml(decode_idna($data[$cols[$i]]));
            }

            if (($status = getOpendkimStatus($adminId)) == 'ok') {
                $row['status'] = translate_dmn_status($status);
                $row['actions'] = <<<EOF
<span title="$trRenewKeys" data-action="renew_keys" data-admin-name="{$data['admin_name']}"
       class="icon i_reload clickable">$trRenewKeys</span>
EOF;
                if (Registry::get('pluginManager')
                        ->pluginGet('OpenDKIM')->getConfigParam('plugin_working_level', 'reseller') == 'reseller'
                ) {
                    $row['actions'] .= <<<EOF

<span title="$trDeactivate" data-action="deactivate"data-admin-name="{$data['admin_name']}"
       class="icon i_delete clickable">$trDeactivate</span>
EOF;
                }
            } else {
                if ((isset($_SESSION['user_logged_type']) && $_SESSION['user_logged_type'] == 'admin')
                    || in_array($status, ['toadd', 'todelete', 'tochange'])
                ) {
                    $row['status'] = tohtml(tr('Task(s) in progress...'));
                } else {
                    $row['status'] = tohtml(tr('An unexpected error occurred. Please contact your administrator.'));
                }

                $row['actions'] = tohtml(tr('N/A'));
            }

            $output['aaData'][] = $row;
        }

        sendJsonResponse(200, $output);
    } catch (Exception $e) {
        write_log(sprintf('OpenDKIM: Could not get customer list: %s', $e->getMessage()), E_USER_ERROR);
        sendJsonResponse(500, ['message' => tr('An unexpected error occurred. Please contact your administrator.')]);
    }
}

/***********************************************************************************************************************
 * Main
 */

check_login('reseller');
EventManager::getInstance()->dispatch(Events::onResellerScriptStart);
resellerHasCustomers() or showBadRequestErrorPage();

if (isset($_REQUEST['action'])) {
    if (is_xhr()) {
        switch (clean_input($_REQUEST['action'])) {
            case 'get_customer_list':
                getCustomerList();
                break;
            case 'search_customer':
                searchCustomer();
                break;
            case 'activate':
                if (!Registry::get('pluginManager')
                        ->pluginGet('OpenDKIM')->getConfigParam('plugin_working_level', 'reseller') == 'reseller') {
                    showBadRequestErrorPage();
                }

                activateOpenDKIM();
                break;
            case 'deactivate':
                if (!Registry::get('pluginManager')
                        ->pluginGet('OpenDKIM')->getConfigParam('plugin_working_level', 'reseller') == 'reseller') {
                    showBadRequestErrorPage();
                }

                deactivateOpenDKIM();
                break;
            case 'renew_keys':
                renewKeys();
                break;
            default:
                sendJsonResponse(400, ['message' => tr('Bad request.')]);
        }
    }

    showBadRequestErrorPage();
}

$tpl = new TemplateEngine();
$tpl->define_dynamic([
    'layout'       => 'shared/layouts/ui.tpl',
    'page'         => '../../plugins/OpenDKIM/themes/default/view/reseller/opendkim.phtml',
    'page_message' => 'layout',
]);
$tpl->assign('TR_PAGE_TITLE', tohtml(tr('Reseller / Customers / OpenDKIM')));
$tpl->isResellerWorkingLevel = Registry::get('pluginManager')
        ->pluginGet('OpenDKIM')->getConfigParam('plugin_working_level', 'reseller') == 'reseller';

generateNavigation($tpl);
generatePageMessage($tpl);

$tpl->parse('LAYOUT_CONTENT', 'page');
EventManager::getInstance()->dispatch(Events::onResellerScriptEnd, ['templateEngine' => $tpl]);
$tpl->prnt();
