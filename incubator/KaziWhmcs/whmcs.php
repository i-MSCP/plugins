<?php

/***********************************************************************************************************************
 * Functions
 */

/**
 * Delete the given customer account
 *
 * @param int $resellerId Reseller unique identifier
 * @param string $domainName Customer's main domain name
 * @return void
 */
function whmcs_deleteUser($resellerId, $domainName)
{
    $domainNameAscii = encode_idna($domainName);

    $stmt = exec_query('SELECT domain_id, domain_name FROM domain WHERE domain_name = ?', $domainNameAscii);

    if ($stmt->rowCount()) {
        $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

        $_SESSION['user_logged'] = 'WHMCS'; // Fake user
        $_SESSION['user_id'] = $resellerId;

        try {
            deleteCustomer($row['domain_id'], true);
            exit('success');
        } catch (iMSCP_Exception $e) {
            exit(sprintf("Error: Unable to delete the '%s' account:", $domainName, $e->getMessage()));
        }
    }

    exit(sprintf("Error: The %s customer account doesn't exist", $domainName));
}

/**
 * Activate the given customer account
 *
 * @param string $domainName Customer's main domain name
 * @return void
 */
function whmcs_enableUser($domainName)
{
    $domainNameAscii = encode_idna($domainName);

    $stmt = exec_query('SELECT domain_id, domain_name FROM domain WHERE domain_name = ?', $domainNameAscii);

    if ($stmt->rowCount()) {
        $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

        $_SESSION['user_logged'] = 'WHMCS'; // Fake user

        try {
            change_domain_status($row['domain_id'], 'activate');
            exit('success');
        } catch (iMSCP_Exception $e) {
            exit(sprintf('Error: Unable to activate customer account: %s', $e->getMessage()));
        }
    }

    exit(sprintf("Error: The %s customer account doesn't exist", $domainName));
}

/**
 * Deactivate the given customer account
 *
 * @param string $domainName Customer's main domain name
 * @return void
 */
function whmcs_disableUser($domainName)
{
    $domainNameAscii = encode_idna($domainName);

    $stmt = exec_query('SELECT domain_id, domain_name FROM domain WHERE domain_name = ?', $domainNameAscii);

    if ($stmt->rowCount()) {
        $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

        $_SESSION['user_logged'] = 'WHMCS'; // Fake user

        try {
            change_domain_status($row['domain_id'], 'deactivate');
            exit('success');
        } catch (iMSCP_Exception $e) {
            exit(sprintf('Error: Unable to activate customer account: %s', $e->getMessage()));
        }
    }

    exit(sprintf("Error: The %s customer account doesn't exist", $domainName));
}

/**
 * Create new customer account
 *
 * @param int $resellerId Reseller unique identifier
 * @param array $hostingPlanProperties Hosting plan properties
 * @param string $resellerIp Reseller IP address
 */
function whmcs_createUser($resellerId, $hostingPlanProperties, $resellerIp)
{
    $db = iMSCP_Database::getRawInstance();
    $cfg = iMSCP_Registry::get('config');

    $createDefaultMail = true;

    $pure_user_pass = urldecode($_POST['admin_pass']);
    $adminName = encode_idna(urldecode($_POST['admin_name']));
    $adminEncryptedPassword = cryptPasswordWithSalt($pure_user_pass);
    $adminType = 'user';
    $createdBy = $resellerId;
    $firstName = (isset($_POST['fname'])) ? clean_input($_POST['fname']) : '';
    $lastName = (isset($_POST['lname'])) ? clean_input($_POST['lname']) : '';
    $firm = (isset($_POST['firm'])) ? clean_input($_POST['firm']) : '';
    $zip = (isset($_POST['zip'])) ? clean_input($_POST['zip']) : '';
    $city = (isset($_POST['city'])) ? clean_input($_POST['city']) : '';
    $state = (isset($_POST['state'])) ? clean_input($_POST['state']) : '';
    $country = (isset($_POST['country'])) ? clean_input($_POST['country']) : '';
    $email = (isset($_POST['email'])) ? clean_input($_POST['email']) : '';
    $phone = (isset($_POST['phone'])) ? clean_input($_POST['phone']) : '';
    $fax = '';
    $street1 = (isset($_POST['street1'])) ?clean_input($_POST['street1']) : '';
    $street2 = (isset($_POST['street2'])) ? clean_input($_POST['street2']) : '';
    $customerId = (isset($_POST['customer_id'])) ? clean_input($_POST['customer_id']) : '';
    $gender = 'U';
    $domainName = (isset($_POST['domain'])) ? encode_idna($_POST['domain']) : '';

    $stmt = exec_query(
        'SELECT COUNT(*) AS cnt FROM mail_users WHERE mail_addr IN ( ?, ? ,?)',
        array("webmaster@$domainName", "abuse@$domainName", "postmaster@$domainName")
    );

    $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

    if ($row['cnt'] != 0) {
        $createDefaultMail = false;
    }

    try {
        $db->beginTransaction();

        exec_query(
            '
			  INSERT INTO admin (
				  admin_name, admin_pass, admin_type, domain_created, created_by, fname, lname, firm, zip, city, state,
				  country, email, phone, fax, street1, street2, customer_id, gender
			  ) VALUES (
				  ?, ?, ?, unix_timestamp(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
			  )
            ',
            array(
                $adminName, $adminEncryptedPassword, $adminType, $createdBy, $firstName, $lastName, $firm, $zip, $city,
                $state, $country, $email, $phone, $fax, $street1, $street2, $customerId, $gender
            )
        );

        $domainAdminId = $db->lastInsertId();
        $domainCreatedId = $resellerId;
        $domainCreated = time();
        $domainExpire = 0;
        $domainMailUsersLimit = $hostingPlanProperties['hp_mail'];
        $domainFtpUsersLimit = $hostingPlanProperties['hp_ftp'];
        $domainMonthlyTrafficLimit = $hostingPlanProperties['hp_traff'];
        $domainSqlDbLimit = $hostingPlanProperties['hp_sql_db'];
        $domainSqlUserLimit = $hostingPlanProperties['hp_sql_user'];
        $domainSubdomainLimit = $hostingPlanProperties['hp_sub'];
        $domainAliasesLimit = $hostingPlanProperties['hp_als'];
        $domainIpId = $resellerIp;
        $domainDiskLimit = $hostingPlanProperties['hp_disk'];
        $domainDiskUsage = 0;
        $domainPhpFeature = preg_replace('/\_/', '', $hostingPlanProperties['hp_php']);
        $domainCgiFeature = preg_replace('/\_/', '', $hostingPlanProperties['hp_cgi']);
        $domainBackupFeature = preg_replace('/\_/', '', $hostingPlanProperties['hp_backup']);
        $domainDnsFeature = preg_replace('/\_/', '', $hostingPlanProperties['hp_dns']);
        $domainSoftwareInstallerFeature = preg_replace('/\_/', '', $hostingPlanProperties['hp_allowsoftware']);
        $domainPhpEditorFeature = $hostingPlanProperties['phpini_system'];
        $phpiniPermAllowUrlFopen = $hostingPlanProperties['phpini_al_allow_url_fopen'];
        $phpiniPermDisplayErrors = $hostingPlanProperties['phpini_al_display_errors'];
        $phpiniPermDisableFunctions = $hostingPlanProperties['phpini_al_disable_functions'];

        exec_query(
            '
			  INSERT INTO domain (
                domain_name, domain_admin_id, domain_created_id, domain_created, domain_expires, domain_mailacc_limit,
                domain_ftpacc_limit, domain_traffic_limit, domain_sqld_limit, domain_sqlu_limit, domain_status,
                domain_subd_limit, domain_alias_limit, domain_ip_id, domain_disk_limit, domain_disk_usage, domain_php,
                domain_cgi, allowbackup, domain_dns, domain_software_allowed, phpini_perm_system,
                phpini_perm_register_globals, phpini_perm_allow_url_fopen, phpini_perm_display_errors,
                phpini_perm_disable_functions
              ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
              )
            ',
            array(
                $domainName, $domainAdminId, $domainCreatedId, $domainCreated, $domainExpire, $domainMailUsersLimit,
                $domainFtpUsersLimit, $domainMonthlyTrafficLimit, $domainSqlDbLimit, $domainSqlUserLimit, 'toadd',
                $domainSubdomainLimit, $domainAliasesLimit, $domainIpId, $domainDiskLimit, $domainDiskUsage,
                $domainPhpFeature, $domainCgiFeature, $domainBackupFeature, $domainDnsFeature,
                $domainSoftwareInstallerFeature, $domainPhpEditorFeature, $phpiniPermAllowUrlFopen,
                $phpiniPermDisplayErrors, $phpiniPermDisableFunctions
            )
        );

        $domainId = $db->lastInsertId();

        if ($domainPhpEditorFeature == 'yes') {
            $phpini = iMSCP_PHPini::getInstance();

            $phpini->setData('phpiniSystem', 'yes');
            $phpini->setData('phpiniPostMaxSize', $hostingPlanProperties['phpini_post_max_size']);
            $phpini->setData('phpiniUploadMaxFileSize', $hostingPlanProperties['phpini_upload_max_filesize']);
            $phpini->setData('phpiniMaxExecutionTime', $hostingPlanProperties['phpini_max_execution_time']);
            $phpini->setData('phpiniMaxInputTime', $hostingPlanProperties['phpini_max_input_time']);
            $phpini->setData('phpiniMemoryLimit', $hostingPlanProperties['phpini_memory_limit']);

            $phpini->saveCustomPHPiniIntoDb($domainId);
        }

        exec_query(
            'INSERT INTO htaccess_users (dmn_id, uname, upass, status) VALUES (?, ?, ?, ?)',
            array(
                $domainId, $domainName, crypt_user_pass_with_salt($pure_user_pass), $cfg->ITEM_ADD_STATUS
            )
        );

        $adminId = $db->lastInsertId();

        exec_query(
            'INSERT INTO htaccess_groups (dmn_id, ugroup, members, status) VALUES (?, ?, ?, ?)',
            array(
                $domainId, $cfg['AWSTATS_GROUP_AUTH'], $adminId, 'toadd'
            )
        );

        // Create default addresses if needed
        if ($cfg->CREATE_DEFAULT_EMAIL_ADDRESSES && $createDefaultMail) {
            $_SESSION['user_email'] = $email;
            client_mail_add_default_accounts($domainId, $email, $domainName);
        } else {
            exec_query(
                'UPDATE mail_users SET domain_id = ? WHERE mail_addr IN ( ?, ? ,?)',
                array($domainId, "webmaster@$domainName", "abuse@$domainName", "postmaster@$domainName"));
        }

        exec_query(
            'INSERT INTO user_gui_props (user_id, lang, layout) VALUES (?, ?, ?)',
            array($domainAdminId, $cfg['USER_INITIAL_LANG'], $cfg['USER_INITIAL_THEME']));

        send_request();

        write_log("WHCMS: add user: $domainName (for domain $domainName)", E_USER_NOTICE);
        write_log("WHCMS: add domain: $domainName", E_USER_NOTICE);

        update_reseller_c_props($resellerId);

        $db->commit();
    } catch (iMSCP_Exception_Database $e) {
        $db->rollBack();
        exit(sprintf("Error: Unable to create customer: %s", $e->getMessage()));
    }

    exit('success');
}

/**
 * Get first IP address of the given reseller
 *
 * @param int $resellerId Reseller unique identifier
 * @return string
 */
function whmcs_getResellerIP($resellerId)
{
    $stmt = exec_query('SELECT * FROM reseller_props WHERE reseller_id = ?', $resellerId);

    if ($stmt->rowCount()) {
        $row = $stmt->fetchRow(PDO::FETCH_ASSOC);

        $resellerIps = explode(';', $row['reseller_ips']);

        if (!empty($resellerId)) {
            return $resellerIps[0];
        } else {
            exit("Error: Unable to retrieve reseller's address IP");
        }
    }

    exit("Error: reseller does not have any IP address");
}

/**
 * Get hosting plan properties
 *
 * @param int $resellerId Reseller unique identifier
 * @return array
 */
function whmcs_getHostingPlan($resellerId)
{
    $cfg = iMSCP_Registry::get('config');

    $hpName = urldecode($_POST['hpName']);

    if ($cfg['HOSTING_PLANS_LEVEL'] == 'admin') {
        $query = "SELECT * FROM hosting_plans WHERE `name` = ?";
        $param = array($hpName);
    } else {
        $query = "SELECT * FROM hosting_plans WHERE name = ? AND reseller_id = ?";
        $param = array($hpName, $resellerId);
    }

    $stmt = exec_query($query, $param);

    if ($stmt->rowCount()) {
        $row = $stmt->fetchRow();

        return array_combine(
            array(
                'hp_php', 'hp_cgi', 'hp_sub', 'hp_als', 'hp_mail', 'hp_ftp', 'hp_sql_db', 'hp_sql_user', 'hp_traff',
                'hp_disk', 'hp_backup', 'hp_dns', 'hp_allowsoftware', 'phpini_system', 'phpini_al_register_globals',
                'phpini_al_allow_url_fopen', 'phpini_al_display_errors', 'phpini_al_disable_functions',
                'phpini_post_max_size', 'phpini_upload_max_filesize', 'phpini_max_execution_time',
                'phpini_max_input_time',
                'phpini_memory_limit'
            ),
            array_pad(explode(';', $row['props']), 23, 'no')
        );
    }

    exit("Error: No such hosting plan named $hpName");
}

/**
 * Login
 *
 * @param string $username Username
 * @param string $password Password
 * @return int
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
            exit('Error: ' . format_message($authResult->getMessages()));
        } else {
            exit('Error: Unable to login');
        }
    }

    return $_SESSION['user_id'];
}

/***********************************************************************************************************************
 * Main
 */

if (isset($_POST['action'])) {
    $action = clean_input($_POST['action']);

    if (isset($_POST['super_user']) && isset($_POST['super_pass'])) {
        $resellerId = whmcs_login(
            encode_idna(clean_input($_POST['super_user'])), clean_input($_POST['super_pass'])
        );

        switch ($action) {
            case 'create':
                $hpProperties = whmcs_getHostingPlan($resellerId);
                $resellerIpAddress = whmcs_getResellerIP($resellerId);
                whmcs_createUser($resellerId, $hpProperties, $resellerIpAddress);
                break;
            case 'Terminate':
                if (isset($_POST['domain'])) {
                    whmcs_deleteUser($resellerId, clean_input($_POST['domain']));
                }
                break;
            case 'Suspend':
                if (isset($_POST['domain'])) {
                    whmcs_disableUser(clean_input($_POST['domain']));
                }
                break;
            case 'Unsuspend':
                if (isset($_POST['domain'])) {
                    whmcs_enableUser(clean_input($_POST['domain']));
                }
        }
    }
}

exit('Error: Bad request');
