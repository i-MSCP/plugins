<?php
/**
 * i-MSCP PhpSwitcher plugin
 * Copyright (C) 2014-2015 Laurent Declercq <l.declercq@nuxwin.com>
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

namespace PhpSwitcher;

use iMSCP_Config_Handler_File as ConfigHandlerFile;
use iMSCP_Database as Database;
use iMSCP_Events as Events;
use iMSCP_Events_Aggregator as EventManager;
use iMSCP_Exception_Database as DatabaseException;
use iMSCP_Plugin_Manager as PluginManager;
use iMSCP_Plugin_PhpSwitcher as PhpSwitcher;
use iMSCP_pTemplate as TemplateEngine;
use iMSCP_Registry as Registry;
use PDO;

/**
 * Send Json response
 *
 * @param int $statusCode HTTPD status code
 * @param array $data JSON data
 * @return void
 */
function sendJsonResponse($statusCode = 200, array $data = array())
{
	header('Cache-Control: no-cache, must-revalidate');
	header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	header('Content-type: application/json');

	switch ($statusCode) {
		case 400:
			header('Status: 400 Bad Request');
			break;
		case 404:
			header('Status: 404 Not Found');
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

	echo json_encode($data);
	exit;
}

/**
 * Get domain data
 *
 * @throws DatabaseException
 * @return array
 */
function getDomainData()
{
	$httpdConfig = new ConfigHandlerFile(Registry::get('config')->CONF_DIR . '/apache/apache.data');
	$iniLevel = $httpdConfig['INI_LEVEL'];

	// Per user mean only main domain
	$query = "
		SELECT
			domain_name, domain_status AS domain_status, IFNULL(version_id, 0) AS version_id, 'dmn' AS domain_type
		FROM
			domain AS t1
		LEFT JOIN
			php_switcher_version_admin USING(domain_name)
		WHERE
			domain_admin_id = :admin_id
		AND
			domain_status <> :domain_status
	";

	# Per domain or per site means also domain aliases
	if ($iniLevel == 'per_domain' || $iniLevel == 'per_site') {
		$query .= "
			UNION
			SELECT
				CONCAT(t1.subdomain_name, '.', t2.domain_name) AS domain_name, t1.subdomain_status AS domain_status,
				IFNULL(t3.version_id, 0) AS version_id, 'sub' AS domain_type
			FROM
				subdomain AS t1
			INNER JOIN
				domain AS t2 USING(domain_id)
			LEFT JOIN
				php_switcher_version_admin AS t3 ON(t3.domain_name = CONCAT(t1.subdomain_name, '.', t2.domain_name))
			WHERE
				t2.domain_admin_id  = :admin_id
			AND
				t1.subdomain_url_forward = 'no'
			AND
				t1.subdomain_status <> :domain_status
		";
	}

	# Per site also mean any subdomain
	if ($iniLevel == 'per_site') {
		$query .= "
			UNION
			SELECT
				t1.alias_name AS domain_name, t1.alias_status AS domain_status, IFNULL(t3.version_id, 0) AS version_id,
				'als' AS domain_type
			FROM
				domain_aliasses AS t1
			INNER JOIN
				domain AS t2 USING(domain_id)
			LEFT JOIN
				php_switcher_version_admin AS t3 ON(t3.domain_name = t1.alias_name)
			WHERE
				t2.domain_admin_id = :admin_id
			AND
				t1.url_forward = 'no'
			AND
				t1.alias_status <> :domain_status
			UNION
			SELECT
				CONCAT(t1.subdomain_alias_name, '.', t2.alias_name) AS domain_name,
				t1.subdomain_alias_status AS domain_status, IFNULL(t4.version_id, 0) AS version_id,
				'subals' AS domain_type
			FROM
				subdomain_alias AS t1
			INNER JOIN
				domain_aliasses t2 USING(alias_id)
			INNER JOIN
				domain AS t3 USING(domain_id)
			LEFT JOIN
				php_switcher_version_admin AS t4 ON(t4.domain_name = CONCAT(t1.subdomain_alias_name, '.', t2.alias_name))
			WHERE
				domain_admin_id = :admin_id
			AND
				subdomain_alias_url_forward = 'no'
			AND
				subdomain_alias_status <> :domain_status
		";
	}

	$stmt = exec_query($query,  array('admin_id' => intval($_SESSION['user_id']), 'domain_status' => 'todelete'));

	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Set PHP version
 *
 * @return void
 */
function changePhpVersion()
{
	if (isset($_POST['version_id']) && isset($_POST['domain_name']) && isset($_POST['domain_type'])) {
		$adminId = intval($_SESSION['user_id']);
		$versionId = intval($_POST['version_id']);
		$fullDomainName = clean_input($_POST['domain_name']);
		$domainType = clean_input($_POST['domain_type']);

		$db = Database::getRawInstance();

		try {
			$db->beginTransaction();

			switch ($domainType) {
				case 'dmn':
					$domainName = $fullDomainName;
					$query = "UPDATE domain SET domain_status = ? WHERE domain_admin_id = ? AND domain_name = ?";
					break;
				case 'sub':
					list($domainName) = explode('.', $fullDomainName);
					$query = "
						UPDATE
							subdomain
						INNER JOIN
							domain USING(domain_id)
						SET
							subdomain_status = ?
						WHERE
							domain_admin_id = ?
						AND
							subdomain_name = ?
					";
					break;
				case 'als';
					$domainName = $fullDomainName;
					$query = "
						UPDATE
							domain_aliasses
						INNER JOIN
							domain USING(domain_id)
						SET
							alias_status = ?
						WHERE
							domain_admin_id = ?
						AND
							alias_name = ?
					";
					break;
				case 'subals':
					list($domainName) = explode('.', $fullDomainName);
					$query = "
						UPDATE
							subdomain_alias
						INNER JOIN
							domain_aliasses USING(alias_id)
						INNER JOIN
							domain USING(domain_id)
						SET
							subdomain_alias_status = ?
						WHERE
							domain_admin_id = ?
						AND
							subdomain_alias_name = ?
					";
					break;
				default:
					sendJsonResponse(400, array('message' => tr('Bad request.')));
					exit;
			}

			$stmt = exec_query($query, array('tochange', $adminId, $domainName));

			if ($stmt->rowCount()) {
				if (!$versionId) {
					exec_query(
						'DELETE FROM php_switcher_version_admin WHERE admin_id = ? AND domain_name = ?',
						array($adminId, $fullDomainName)
					);
				} else {
					exec_query(
						'
							INSERT INTO php_switcher_version_admin (version_id, admin_id, domain_name, domain_type)
							SELECT
								php_switcher_version.version_id, ?, ?, ?
							FROM
								php_switcher_version
							WHERE
								php_switcher_version.version_id = ?
							AND
								php_switcher_version.version_status = ?
							ON DUPLICATE KEY UPDATE
								version_id = php_switcher_version.version_id
					',
						array($adminId, $fullDomainName, $domainType, $versionId, 'ok')
					);
				}

				$db->commit();

				send_request();
				write_log('%s updated its PHP version.', decode_idna($_SESSION['user_logged']), E_USER_NOTICE);
				set_page_message(tr('PHP version successfully scheduled for update. Please be patient.'), 'success');
				sendJsonResponse(200);
			}
		} catch (DatabaseException $e) {
			$db->rollBack();
			sendJsonResponse(500, array('message' => tr('An unexpected error occurred: %s', true, $e->getMessage())));
		}
	}

	sendJsonResponse(400, array('message' => tr('Bad request.')));
}

/**
 * Generate page
 *
 * @param TemplateEngine $tpl
 */
function generatePage($tpl)
{
	$domainsData = getDomainData();
	$phpVersions = array('PHP ' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION . '.' . PHP_RELEASE_VERSION => 0);

	$stmt = execute_query('SELECT version_name, version_id FROM php_switcher_version');

	if ($stmt->rowCount()) {
		$phpVersions = array_merge($phpVersions, $stmt->fetchAll(PDO::FETCH_KEY_PAIR));
	}

	if(!defined('SORT_NATURAL')) {
		ksort($phpVersions, SORT_NATURAL);
	} else {
		uksort($phpVersions, 'strnatcmp');
	}

	foreach ($domainsData as $domainData) {
		$tpl->assign(array(
			'DOMAIN_NAME_UNICODE' => tohtml(decode_idna($domainData['domain_name'])),
			'DOMAIN_NAME' => tohtml($domainData['domain_name'], 'htmlAttr'),
			'DOMAIN_TYPE' => tohtml($domainData['domain_type'], 'htmlAttr'),
			'DOMAIN_STATUS' => tohtml(translate_dmn_status($domainData['domain_status'])),
			'PHP_VERSION_DISABLED' => ($domainData['domain_status'] != 'ok') ? ' disabled="disabled"' : '',
			'CURRENT_PHP_VERSION_ID' => intval($domainData['version_id'])
		));

		# Build php_version select for the current domain
		foreach ($phpVersions as $phpVersionName => $phpVersionId) {
			$tpl->assign(
				array(
					'PHP_VERSION_ID' => tohtml($phpVersionId, 'htmlAttr'),
					'PHP_VERSION_NAME' => tohtml($phpVersionName),
					'PHP_VERSION_SELECTED' => ($phpVersionId == $domainData['version_id']) ? ' selected="selected"' : ''
				)
			);

			$tpl->parse('PHP_VERSION_OPTION', '.php_version_option');
		}


		$tpl->parse('DOMAIN_PHP_VERSION', '.domain_php_version');
		$tpl->assign('PHP_VERSION_OPTION', '');
	}
}

/***********************************************************************************************************************
 * Main
 */

EventManager::getInstance()->dispatch(Events::onClientScriptStart);
check_login('user');

if (customerHasFeature('php')) {
	if (is_xhr()) {
		changePhpVersion();
	}

	/** @var PluginManager $pluginManager */
	$pluginManager = Registry::get('pluginManager');
	/** @var PhpSwitcher $phpSwitcher */
	$phpSwitcher = $pluginManager->pluginGet('PhpSwitcher');

	$tpl = new TemplateEngine();
	$tpl->define_dynamic(array(
		'layout' => 'shared/layouts/ui.tpl',
		'page' => '../../plugins/PhpSwitcher/themes/default/view/client/page.tpl',
		'page_message' => 'layout',
		'phpinfo_th' => 'page',
		'domain_php_version' => 'page',
		'phpinfo_header' => 'domain_php_version',
		'phpinfo_footer' => 'domain_php_version',
		'phpinfo_body' => 'domain_php_version',
		'phpinfo_js' => 'page',
		'php_version_option' => 'domain_php_version'
	));

	if (!$phpSwitcher->getConfigParam('phpinfo', true)) {
		$tpl->assign(array(
			'PHPINFO_HEADER' => '',
			'PHPINFO_FOOTER' => '',
			'PHPINFO_BODY' => '',
			'PHPINFO_JS' => '',
		));
	}

	EventManager::getInstance()->registerListener('onGetJsTranslations', function ($e) {
		/** @var $e \iMSCP_Events_Event */
		$e->getParam('translations')->PhpSwitcher = array(
			'datatable' => getDataTablesPluginTranslations(false),
			'close' => tr('Close', true),
			'error' => tr('An unexpected error occurred.', true)
		);
	});

	$tpl->assign(array(
		'TR_PAGE_TITLE' => tr('Client / Settings / PHP Switcher'),
		'TR_HINT' => tr('For each domain listed below, you can choose the PHP version you want use by selecting it.<br>Be aware that domains which are redirected on a specific URL are discarded.'),
		'TR_DOMAIN_NAME' => tr('Domain name'),
		'TR_VERSION' => tr('PHP version'),
		'TR_VERSION_INFO' => tr('PHP version info'),
		'TR_SHOW_INFO' => tr('Show info'),
		'TR_STATUS' => tr('Domain status')
	));

	generateNavigation($tpl);
	generatePageMessage($tpl);
	generatePage($tpl);

	$tpl->parse('LAYOUT_CONTENT', 'page');
	EventManager::getInstance()->dispatch(Events::onClientScriptEnd, array('templateEngine' => $tpl));
	$tpl->prnt();
} else {
	showNotFoundErrorPage();
}
