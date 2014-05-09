<?php
/**
 * i-MSCP ImscpBoxBilling plugin
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
function imscpboxbilling_login($username, $password)
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
            die('ImscpBoxBilling: ' . implode(' - ', $authResult->getMessages()));
        } else {
            die('ImscpBoxBilling: Unable to login');
        }
    } elseif ($authResult->getIdentity()->admin_type != 'reseller') {
        die('ImscpBoxBilling: Wrong user type. Only resellers can use the ImscpBoxBilling API');
    }

    register_shutdown_function(function() { iMSCP_Authentication::getInstance()->unsetIdentity(); });

    return $authResult->getIdentity()->admin_id;
}

/**
 * Get hosting plan properties
 *
 * @param string $hpName Hosting plan name
 * @return array Hosting plan properties
 */
function imscpboxbilling_getHostingPlanProps($hpName)
{
    $resellerId = iMSCP_Authentication::getInstance()->getIdentity()->admin_id;

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

    die(sprintf("ImscpBoxBilling: The '%s' hosting plan doesn't exists", $hpName));
}

/**
 * Get first IP address of the given reseller
 *
 * @return string
 */
function imscpboxbilling_getResellerIP()
{
    $resellerId = iMSCP_Authentication::getInstance()->getIdentity()->admin_id;

    $stmt = exec_query('SELECT reseller_ips FROM reseller_props WHERE reseller_id = ?', $resellerId);

    if ($stmt->rowCount()) {
        $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

        $resellerIps = explode(';', $row['reseller_ips']);

        if (!empty($resellerId)) {
            return $resellerIps[0];
        } else {
            die("ImscpBoxBilling: Unable to retrieve reseller's address IP");
        }
    }

    die("ImscpBoxBilling: Reseller doesn't have any IP address");
}

/**
 * Create new customer account
 *
 * @param array $hostingPlanProperties Hosting plan properties
 * @param string $resellerIp Reseller IP address
 * @return void
 */
function imscpboxbilling_createAccount($hostingPlanProperties, $resellerIp)
{
    $resellerId = iMSCP_Authentication::getInstance()->getIdentity()->admin_id;

    if (isset($_POST['admin_name']) && isset($_POST['admin_pass']) && isset($_POST['domain']) && isset($_POST['email'])) {
        $email = clean_input($_POST['email']);

        if (!chk_email($email)) {
            die(sprintf("ImscpBoxBilling: '%s' is not a valid email address", $email));
        }

        $adminPassword = clean_input($_POST['admin_pass']);

        $cfg = iMSCP_Registry::get('config');

        $cfg['PASSWD_STRONG'] = 0; // WHMCS must manage this

        if (!checkPasswordSyntax($adminPassword)) {
            die(sprintf("ImscpBoxBilling: '%s' is not a valid password", $adminPassword));
        }

        $domainNameUtf8 = decode_idna(strtolower(clean_input($_POST['domain'])));

        if ($domainNameUtf8 && isValidDomainName($domainNameUtf8)) {
            $domainNameAscii = encode_idna($domainNameUtf8);

            if (!imscp_domain_exists($domainNameAscii, $resellerId) && $domainNameAscii != $cfg['BASE_SERVER_VHOST']) {
                $domainExpire = 0;
                $adminUsername = encode_idna(strtolower(clean_input($_POST['admin_name'])));
                $adminPassword = clean_input($_POST['admin_pass']);

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
                $fax = (isset($_POST['fax'])) ? clean_input($_POST['fax']) : '';;
                $street1 = (isset($_POST['street1'])) ? clean_input($_POST['street1']) : '';
                $street2 = (isset($_POST['street2'])) ? clean_input($_POST['street2']) : '';

                if (!$cfg['WEB_FOLDER_PROTECTION']) {
                    $webFolderProtection = 'no';
                }

                $db = iMSCP_Database::getInstance();

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
                            admin_name, admin_pass, admin_type, domain_created, created_by, fname, lname, firm, zip,
                            city, state, country, email, phone, fax, street1, street2, customer_id, gender, admin_status
                          ) VALUES (
                            ?, ?, 'user', unix_timestamp(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                         )
                        ",
                        array(
                            $adminUsername, cryptPasswordWithSalt($adminPassword), $resellerId, $firstName, $lastName,
                            $firm, $zip, $city, $state, $country, $email, $phone, $fax, $street1, $street2, $customerId,
                            'U', 'toadd'
                        )
                    );

                    $adminId = $db->insertId();

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

                    $domainId = $db->insertId();

                    // Save php.ini if exist
                    if ($phpEditorFeature == 'yes') {
                        /* @var $phpini iMSCP_PHPini */
                        $phpini = iMSCP_PHPini::getInstance();

                        // Fill it with the custom values - other take from default
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
                        array(
                            $domainId, $domainNameAscii, cryptPasswordWithSalt($adminPassword,
                            generateRandomSalt(true)), 'toadd'
                        )
                    );

                    $htuserId = $db->insertId();

                    exec_query(
                        'INSERT INTO htaccess_groups (dmn_id, ugroup, members, status) VALUES (?, ?, ?, ?)',
                        array($domainId, $cfg['WEBSTATS_GROUP_AUTH'], $htuserId, 'toadd')
                    );

                    // Create default addresses if needed
                    if ($cfg['CREATE_DEFAULT_EMAIL_ADDRESSES']) {
                        client_mail_add_default_accounts($domainId, $email, $domainNameAscii);
                    }

                    // Send welcome mail to user
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
                        sprintf(
                            "ImscpBoxBilling: A new customer account '%s' has been added through BoxBilling",
                            $domainNameUtf8
                        ),
                        E_USER_NOTICE
                    );

                    exit('success');
                } catch (Exception $e) {
                    $db->rollBack();
                    die(sprintf("ImscpBoxBilling: Unable to create the '%s' customer account; %s", $e->getMessage()));
                }
            } else {
                die(sprintf("ImscpBoxBilling: Domain '%s' already exists or is not allowed", $domainNameUtf8));
            }
        } else {
            die(sprintf("ImscpBoxBilling: Domain '%s' is not valid", $domainNameUtf8));
        }
    }
}

/**
 * Suspend the given customer account
 *
 * @param string $domainName Customer's main domain name
 * @return void
 */
function imscpboxbilling_suspendAccount($domainName)
{
    $domainNameAscii = encode_idna($domainName);

    $stmt = exec_query('SELECT domain_admin_id FROM domain WHERE domain_name = ?', $domainNameAscii);

    if ($stmt->rowCount()) {
        $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

        try {
            change_domain_status($row['domain_admin_id'], 'deactivate');

            write_log(
                sprintf(
                    "ImscpBoxBilling: The '%s' customer account has been suspended through BoxBilling", $domainName
                ),
                E_USER_NOTICE
            );

            exit('success');
        } catch (Exception $e) {
            die(
                sprintf(
                    "ImscpBoxBilling: Unable to suspend the '%s' customer account: %s", $domainName, $e->getMessage()
                )
            );
        }
    }

    die(sprintf("ImscpBoxBilling: The '%s' customer account doesn't exists", $domainName));
}

/**
 * Unsuspend the given customer account
 *
 * @param string $domainName Customer's main domain name
 * @return void
 */
function imscpboxbilling_unsuspendAccount($domainName)
{
    $domainNameAscii = encode_idna($domainName);

    $stmt = exec_query('SELECT domain_admin_id FROM domain WHERE domain_name = ?', $domainNameAscii);

    if ($stmt->rowCount()) {
        $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

        try {
            change_domain_status($row['domain_admin_id'], 'activate');

            write_log(
                sprintf(
                    "ImscpBoxBilling: The '%s' customer account has been un-suspended through BoxBilling", $domainName
                ),
                E_USER_NOTICE
            );

            exit('success');
        } catch (Exception $e) {
            die(
                sprintf(
                    "ImscpBoxBilling: Unable to unsuspend the '%s' customer account; %s",
                    $domainName,
                    $e->getMessage()
                )
            );
        }
    }

    die(sprintf("ImscpBoxBilling: The '%s' customer account doesn't exists", $domainName));
}

/**
 * Cancel the given customer account
 *
 * @param string $domainName Customer's main domain name
 * @return void
 */
function imscpboxbilling_cancelAccount($domainName)
{
    $domainNameAscii = encode_idna($domainName);

    $stmt = exec_query('SELECT domain_admin_id FROM domain WHERE domain_name = ?', $domainNameAscii);

    if ($stmt->rowCount()) {
        $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

        try {
            deleteCustomer($row['domain_admin_id'], true);

            write_log(
                sprintf(
                    "ImscpBoxBilling: The '%s' customer account has been deleted through BoxBilling", $domainName
                ),
                E_USER_NOTICE
            );

            exit('success');
        } catch (Exception $e) {
            die(
                sprintf(
                    "ImscpBoxBilling: Unable to cancel the '%s' customer account; %s",
                    $domainName,
                    $e->getMessage()
                )
            );
        }
    }

    die(sprintf("ImscpBoxBilling: The '%s' customer account doesn't exists", $domainName));
}

/**
 * Update the password of the given customer account
 *
 * @param string $customerName Customer name
 * @param string $newPassword New password
 * @return void
 */
function imscpboxbilling_changePassword($customerName, $newPassword)
{
    $customerNameAscii = encode_idna($customerName);

    $cfg = iMSCP_Registry::get('config');

    $cfg['PASSWD_STRONG'] = 0; // WHMCS must manage this

    if (!checkPasswordSyntax($newPassword)) {
        die(sprintf("ImscpBoxBilling: '%s' is not a valid password", $newPassword));
    }

    $stmt = exec_query(
        'SELECT admin_id FROM admin WHERE admin_name = ? AND created_by = ?',
        array($customerNameAscii, iMSCP_Authentication::getInstance()->getIdentity()->admin_id)
    );

    if ($stmt->rowCount()) {
        $row = $stmt->fetchRow(PDO::FETCH_ASSOC);
        $adminId = $row['admin_id'];

        $db = iMSCP_Database::getInstance();

        try {
            $db->beginTransaction();

            iMSCP_Events_Aggregator::getInstance()->dispatch(
                iMSCP_Events::onBeforeEditUser, array('userId' => $adminId)
            );

            exec_query(
                'UPDATE admin SET admin_pass = ? WHERE admin_id = ?',
                array(cryptPasswordWithSalt($newPassword), $adminId)
            );

            exec_query(
                'UPDATE htaccess_users SET upass = ?, status = ? WHERE dmn_id = ? AND uname = ?',
                array(
                    cryptPasswordWithSalt($newPassword, generateRandomSalt(true)), 'tochange',
                    get_user_domain_id($adminId), $customerNameAscii
                )
            );

            iMSCP_Events_Aggregator::getInstance()->dispatch(
                iMSCP_Events::onAfterEditUser, array('userId' => $adminId)
            );

            $db->commit();

            send_request();

            write_log(
                sprintf(
                    "ImscpBoxBilling: Password of the '%s' customer account has been updated through BoxBilling",
                    $customerName
                ),
                E_USER_NOTICE
            );

            exit('success');
        } catch (Exception $e) {
            $db->rollBack();

            die(
                sprintf(
                    "ImscpBoxBilling: Unable to update password of the '%s' customer account; %s",
                    $customerName,
                    $e->getMessage()
                )
            );
        }
    }

    die(sprintf("ImscpBoxBilling: The '%s' customer account doesn't exists", $customerName));
}

/***********************************************************************************************************************
 * Main
 */

try {
    // Disable compression information (HTML comment)
    if (iMSCP_Registry::isRegistered('bufferFilter')) {
        /** @var iMSCP_Filter_Compress_Gzip $filter */
        $filter = iMSCP_Registry::get('bufferFilter');
        $filter->compressionInformation = false;
    }

    if (isset($_POST['action'])) {
        $action = clean_input($_POST['action']);

        if (isset($_POST['reseller_name']) && isset($_POST['reseller_pass'])) {
            $resellerId = imscpboxbilling_login(
                clean_input($_POST['reseller_name']), clean_input($_POST['reseller_pass'])
            );

            switch ($action) {
                case 'testconnection':
                    exit('success');
                case 'create':
                    if (isset($_POST['hp_name'])) {
                        imscpboxbilling_createAccount(
                            imscpboxbilling_getHostingPlanProps(clean_input($_POST['hp_name'])),
                            imscpboxbilling_getResellerIP()
                        );
                    }
                    break;
                case 'suspend':
                    if (isset($_POST['domain'])) {
                        imscpboxbilling_suspendAccount(clean_input($_POST['domain']));
                    }
                    break;
                case 'unsuspend':
                    if (isset($_POST['domain'])) {
                        imscpboxbilling_unsuspendAccount(clean_input($_POST['domain']));
                    }
                    break;
                case 'cancel':
                    if (isset($_POST['domain'])) {
                        imscpboxbilling_cancelAccount(clean_input($_POST['domain']));
                    }
                    break;
                case 'changepw':
                    if (isset($_POST['admin_name']) && isset($_POST['admin_pass'])) {
                        imscpboxbilling_changePassword(
                            clean_input($_POST['admin_name']), clean_input($_POST['admin_pass'])
                        );
                    }
            }
        }
    }
} catch (Exception $e) {
    header("Status: 500 Internal Server Error");
    die(sprintf('An unexpected error occurred: %s', $e->getMessage()));
}

header("Status: 400 Bad Request");
die('Bad request');
