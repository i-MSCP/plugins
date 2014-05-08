<?php
/**
 * i-MSCP KaziWhmcs plugin
 * Copyright (C) 2014 Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 */

/***********************************************************************************************************************
 * Functions
 */

/**
 * Login
 *
 * @param string $username Username
 * @param string $password Password
 * @return int Reseller user unique identifier
 */
function whmcs_login($username, $password)
{
    do_session_timeout();

    $authentication = iMSCP_Authentication::getInstance();

    init_login($authentication->getEvents());

    $authResult = $authentication
        ->setUsername($username)
        ->setPassword($password)
        ->authenticate();

    if (!$authResult->isValid()) {
        if (($messages = $authResult->getMessages())) {
            die('KaziWhmcs: ' . implode(' - ', $authResult->getMessages()));
        } else {
            die('KaziWhmcs: Unable to login');
        }
    } elseif ($authResult->getIdentity()->admin_type != 'reseller') {
        die('KaziWhmcs: Wrong user type. Only resellers can use the KaziWhmcs API');
    }

    return $_SESSION['user_id'];
}

/**
 * Get hosting plan properties
 *
 * @param int $resellerId Reseller unique identifier
 * @param string $hpName Hosting plan name
 * @return array Hosting plan properties
 */
function whmcs_getHostingPlanProps($resellerId, $hpName)
{
    $cfg = iMSCP_Registry::get('config');

    if ($cfg['HOSTING_PLANS_LEVEL'] == 'admin') {
        $q = 'SELECT props FROM hosting_plans WHERE name = ?';
        $p = array($hpName);
    } else {
        $q = 'SELECT props FROM hosting_plans WHERE name = ? AND reseller_id = ?';
        $p = array($hpName, $resellerId);
    }

    $stmt = exec_query($q, $p);

    if ($stmt->rowCount()) {
        $row = $stmt->fetchRow();
        return $row['props'];
    }

    die(sprintf("KaziWhmcs: The '%s' hosting plan doesn't exists", $hpName));
}

/**
 * Get first IP address of the given reseller
 *
 * @param int $resellerId Reseller unique identifier
 * @return string
 */
function whmcs_getResellerIP($resellerId)
{
    $stmt = exec_query('SELECT reseller_ips FROM reseller_props WHERE reseller_id = ?', $resellerId);

    if ($stmt->rowCount()) {
        $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

        $resellerIps = explode(';', $row['reseller_ips']);

        if (!empty($resellerId)) {
            return $resellerIps[0];
        } else {
            die("KaziWhmcs: Unable to retrieve reseller's address IP");
        }
    }

    die("KaziWhmcs: Reseller doesn't have any IP address");
}

/**
 * Create new customer account
 *
 * @param int $resellerId Reseller unique identifier
 * @param array $hostingPlanProperties Hosting plan properties
 * @param string $resellerIp Reseller IP address
 * @return void
 */
function whmcs_CreateAccount($resellerId, $hostingPlanProperties, $resellerIp)
{
    if (isset($_POST['admin_name']) && isset($_POST['admin_pass']) && isset($_POST['domain']) && isset($_POST['email'])) {
        $email = clean_input($_POST['email']);

        if (chk_email($email)) {
            die(sprintf("KaziWhmcs: '%s' is not a valid email", $email));
        }

        $adminPassword = clean_input($_POST['admin_pass']);

        if (!checkPasswordSyntax($adminPassword)) {
            die(sprintf("KaziWhmcs: '%s' is not a valid password", $adminPassword));
        }

        $domainNameUtf8 = decode_idna(strtolower(clean_input($_POST['domain'])));

        if ($domainNameUtf8 && isValidDomainName($domainNameUtf8)) {
            $domainNameAscii = encode_idna($domainNameUtf8);

            $cfg = iMSCP_Registry::get('config');

            if (!imscp_domain_exists($domainNameAscii, $resellerId) && $domainNameAscii != $cfg['BASE_SERVER_VHOST']) {
                $domainExpire = 0;
                $adminUsername = encode_idna(strtolower(clean_input($_POST['admin_name'])));
                $adminPassword = clean_input($_POST['admin_pass']);
                $encryptedAdminPassword = cryptPasswordWithSalt($adminPassword);

                // Features and limits
                list(
                    $phpFeature, $cgiFeature, $nbSubdomains, $nbAliases, $nbMailUsers, $nbFtpUsers, $nbSqlDb,
                    $nbSqlUsers, $monthlyTrafficLimit, $diskspaceLimit, $backupFeature, $dnsFeature,
                    $softwareInstallerFeature, $phpEditorFeature, $phpiniAllowUrlFopen, $phpiniDisplayErrors,
                    $phpiniDisableFunctions, $phpiniPostMaxSize, $phpiniUploadMaxFileSize, $phpiniMaxExecutionTime,
                    $phpiniMaxInputTime, $phpiniMemoryLimit, $extMailServer, $webFolderProtection, $mailQuota
                    ) = explode(
                    ';', $hostingPlanProperties
                );
                $phpFeature = str_replace('_', '', $phpFeature);
                $cgiFeature = str_replace('_', '', $cgiFeature);
                $backupFeature = str_replace('_', '', $backupFeature);
                $dnsFeature = str_replace('_', '', $dnsFeature);
                $softwareInstallerFeature = str_replace('_', '', $softwareInstallerFeature);
                $extMailServer = str_replace('_', '', $extMailServer);
                $webFolderProtection = str_replace('_', '', $webFolderProtection);

                // Personal data
                $customerId = (isset($_POST['customer_id'])) ? clean_input($_POST['customer_id']) : '';
                $firstName = (isset($_POST['fname'])) ? clean_input($_POST['fname']) : '';
                $lastName = (isset($_POST['lname'])) ? clean_input($_POST['lname']) : '';
                $firm = (isset($_POST['firm'])) ? clean_input($_POST['firm']) : '';
                $zip = (isset($_POST['zip'])) ? clean_input($_POST['zip']) : '';
                $city = (isset($_POST['city'])) ? clean_input($_POST['city']) : '';
                $state = (isset($_POST['state'])) ? clean_input($_POST['state']) : '';
                $country = (isset($_POST['country'])) ? clean_input($_POST['country']) : '';

                $phone = (isset($_POST['phone'])) ? clean_input($_POST['phone']) : '';
                $fax = '';
                $street1 = (isset($_POST['street1'])) ? clean_input($_POST['street1']) : '';
                $street2 = (isset($_POST['street2'])) ? clean_input($_POST['street2']) : '';

                if (!$cfg['WEB_FOLDER_PROTECTION']) {
                    $webFolderProtection = 'no';
                }

                $db = iMSCP_Database::getRawInstance();

                try {
                    iMSCP_Events_Aggregator::getInstance()->dispatch(
                        iMSCP_Events::onBeforeAddDomain,
                        array(
                            'domainName' => $domainNameUtf8,
                            'createdBy' => $resellerId,
                            'customerId' => $customerId,
                            'customerEmail' => $email
                        )
                    );

                    $db->beginTransaction();

                    exec_query(
                        "
                          INSERT INTO admin (
                            admin_name, admin_pass, admin_type, domain_created, created_by, fname, lname, firm, zip, city,
                            state, country, email, phone, fax, street1, street2, customer_id, gender, admin_status
                          ) VALUES (
                            ?, ?, 'user', unix_timestamp(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                         )
                        ",
                        array(
                            $adminUsername, $encryptedAdminPassword, $resellerId, $firstName, $lastName, $firm, $zip,
                            $city, $state, $country, $email, $phone, $fax, $street1, $street2, $customerId, 'U', 'toadd'
                        )
                    );

                    $adminId = $db->lastInsertId();

                    exec_query(
                        '
                          INSERT INTO domain (
                            domain_name, domain_admin_id, domain_created, domain_expires, domain_mailacc_limit,
                            domain_ftpacc_limit, domain_traffic_limit, domain_sqld_limit, domain_sqlu_limit, domain_status,
                            domain_alias_limit, domain_subd_limit, domain_ip_id, domain_disk_limit, domain_disk_usage,
                            domain_php, domain_cgi, allowbackup, domain_dns, domain_software_allowed, phpini_perm_system,
                            phpini_perm_allow_url_fopen, phpini_perm_display_errors, phpini_perm_disable_functions,
                            domain_external_mail, web_folder_protection, mail_quota
                          ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                          )
                        ',
                        array(
                            $domainNameAscii, $adminId, time(), $domainExpire, $nbMailUsers, $nbFtpUsers,
                            $monthlyTrafficLimit, $nbSqlDb, $nbSqlUsers, 'toadd', $nbAliases, $nbSubdomains, $resellerIp,
                            $diskspaceLimit, 0, $phpFeature, $cgiFeature, $backupFeature, $dnsFeature,
                            $softwareInstallerFeature, $phpEditorFeature, $phpiniAllowUrlFopen, $phpiniDisplayErrors,
                            $phpiniDisableFunctions, $extMailServer, $webFolderProtection, $mailQuota
                        )
                    );

                    $domainId = $db->lastInsertId();

                    // save php.ini if exist
                    if ($phpEditorFeature == 'yes') {
                        /* @var $phpini iMSCP_PHPini */
                        $phpini = iMSCP_PHPini::getInstance();

                        // fill it with the custom values - other take from default
                        $phpini->setData('phpiniSystem', 'yes');
                        $phpini->setData('phpiniPostMaxSize', $phpiniPostMaxSize);
                        $phpini->setData('phpiniUploadMaxFileSize', $phpiniUploadMaxFileSize);
                        $phpini->setData('phpiniMaxExecutionTime', $phpiniMaxExecutionTime);
                        $phpini->setData('phpiniMaxInputTime', $phpiniMaxInputTime);
                        $phpini->setData('phpiniMemoryLimit', $phpiniMemoryLimit);

                        $phpini->saveCustomPHPiniIntoDb($domainId);
                    }

                    exec_query(
                        'INSERT INTO htaccess_users (dmn_id, uname, upass, status) VALUES (?, ?, ?, ?)',
                        array($domainId, $domainNameAscii, $encryptedAdminPassword, 'toadd')
                    );

                    $htuserId = $db->lastInsertId();

                    exec_query(
                        'INSERT INTO htaccess_groups (dmn_id, ugroup, members, status) VALUES (?, ?, ?, ?)',
                        array($domainId, $cfg['WEBSTATS_GROUP_AUTH'], $htuserId, 'toadd')
                    );

                    // Create default addresses if needed
                    if ($cfg['CREATE_DEFAULT_EMAIL_ADDRESSES']) {
                        client_mail_add_default_accounts($domainId, $email, $domainNameAscii);
                    }

                    // Let's send mail to user
                    send_add_user_auto_msg(
                        $resellerId, $adminUsername, $adminPassword, $email, $firstName, $lastName, tr('Customer', true)
                    );

                    exec_query(
                        'INSERT INTO user_gui_props (user_id, lang, layout) VALUES (?, ?, ?)',
                        array($adminId, $cfg['USER_INITIAL_LANG'], $cfg['USER_INITIAL_THEME'])
                    );

                    update_reseller_c_props($resellerId);

                    $db->commit();

                    iMSCP_Events_Aggregator::getInstance()->dispatch(
                        iMSCP_Events::onAfterAddDomain,
                        array(
                            'domainName' => $domainNameUtf8,
                            'createdBy' => $resellerId,
                            'customerId' => $adminId,
                            'customerEmail' => $email,
                            'domainId' => $domainId
                        )
                    );

                    send_request();

                    write_log(
                        sprintf("KaziWhmcs: A new customer account '%s' has been added through WHMCS", $domainNameUtf8),
                        E_USER_NOTICE
                    );

                    exit('success');
                } catch (Exception $e) {
                    $db->rollBack();
                    die(sprintf("KaziWhmcs: Unable to create the '%s' customer account: %s", $e->getMessage()));
                }
            } else {
                die(sprintf("KaziWhmcs: Domain '%s' already exists or is not allowed", $domainNameUtf8));
            }
        } else {
            die(sprintf("KaziWhmcs: Domain '%s' is not valid", $domainNameUtf8));
        }
    }
}

/**
 * Suspend the given customer account
 *
 * @param string $domainName Customer's main domain name
 * @return void
 */
function whmcs_SuspendAccount($domainName)
{
    $domainNameAscii = encode_idna($domainName);

    $stmt = exec_query('SELECT domain_id FROM domain WHERE domain_name = ?', $domainNameAscii);

    if ($stmt->rowCount()) {
        $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

        //$_SESSION['user_logged'] = 'WHMCS'; // Fake user

        try {
            change_domain_status($row['domain_id'], 'deactivate');

            write_log(
                sprintf("KaziWhmcs: The '%s' customer account has been suspended through WHMCS", $domainName),
                E_USER_NOTICE
            );

            exit('success');
        } catch (Exception $e) {
            die(sprintf("KaziWhmcs: Unable to suspend the '%s' customer account: %s", $domainName, $e->getMessage()));
        }
    }

    die(sprintf("KaziWhmcs: The '%s' customer account doesn't exists", $domainName));
}

/**
 * Unsuspend the given customer account
 *
 * @param string $domainName Customer's main domain name
 * @return void
 */
function whmcs_UnsuspendAccount($domainName)
{
    $domainNameAscii = encode_idna($domainName);

    $stmt = exec_query('SELECT domain_id FROM domain WHERE domain_name = ?', $domainNameAscii);

    if ($stmt->rowCount()) {
        $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

        //$_SESSION['user_logged'] = 'WHMCS'; // Fake user

        try {
            change_domain_status($row['domain_id'], 'activate');

            write_log(
                sprintf("KaziWhmcs: The '%s' customer account has been un-suspended through WHMCS", $domainName),
                E_USER_NOTICE
            );

            exit('success');
        } catch (Exception $e) {
            die(sprintf("KaziWhmcs: Unable to unsuspend the '%s' customer account: %s", $domainName, $e->getMessage()));
        }
    }

    die(sprintf("KaziWhmcs: The '%s' customer account doesn't exists", $domainName));
}

/**
 * Terminate the given customer account
 *
 * @param int $resellerId Reseller unique identifier
 * @param string $domainName Customer's main domain name
 * @return void
 */
function whmcs_TerminateAccount($resellerId, $domainName)
{
    $domainNameAscii = encode_idna($domainName);

    $stmt = exec_query('SELECT domain_id FROM domain WHERE domain_name = ?', $domainNameAscii);

    if ($stmt->rowCount()) {
        $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

        //$_SESSION['user_logged'] = 'WHMCS'; // Fake user
        //$_SESSION['user_id'] = $resellerId;

        try {
            deleteCustomer($row['domain_id'], true);

            write_log(
                sprintf("KaziWhmcs: The '%s' customer account has been deleted through WHMCS", $domainName),
                E_USER_NOTICE
            );

            exit('success');
        } catch (Exception $e) {
            die(sprintf("KaziWhmcs: Unable to terminate the '%s' customer account: %s", $domainName, $e->getMessage()));
        }
    }

    die(sprintf("KaziWhmcs: The '%s' customer account doesn't exists", $domainName));
}

/***********************************************************************************************************************
 * Main
 */

// Disable compression information
if (iMSCP_Registry::isRegistered('bufferFilter')) {
    /** @var iMSCP_Filter_Compress_Gzip $filter */
    $filter = iMSCP_Registry::get('bufferFilter');
    $filter->compressionInformation = false;
}

if (isset($_POST['action'])) {
    $action = clean_input($_POST['action']);

    if (isset($_POST['reseller_name']) && isset($_POST['reseller_pass'])) {
        $resellerId = whmcs_login(clean_input($_POST['reseller_name']), clean_input($_POST['reseller_pass']));

        switch ($action) {
            case 'create':
                if (isset($_POST['hp_name'])) {
                    whmcs_CreateAccount(
                        $resellerId,
                        whmcs_getHostingPlanProps($resellerId, clean_input($_POST['hp_name'])),
                        whmcs_getResellerIP($resellerId)
                    );
                }
                break;
            case 'suspend':
                if (isset($_POST['domain'])) {
                    whmcs_SuspendAccount(clean_input($_POST['domain']));
                }
                break;
            case 'unsuspend':
                if (isset($_POST['domain'])) {
                    whmcs_UnsuspendAccount(clean_input($_POST['domain']));
                }
                break;
            case 'terminate':
                if (isset($_POST['domain'])) {
                    whmcs_TerminateAccount($resellerId, clean_input($_POST['domain']));
                }
        }
    }
}

die('Bad request');
