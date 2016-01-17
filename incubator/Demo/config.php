<?php
/**
 * i-MSCP Demo plugin
 * Copyright (C) 2012-2016 Laurent Declercq <l.declercq@nuxwin.com>
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

// Configuration file sample for the demo plugin

return array(
	// List of user accounts which have to be available in select box on login page. Those users must exists.
	// If an user account is protected, it will be impossible to edit it or remove it.
	'user_accounts' => array(
		array(
			'label' => 'Administrator',
			'username' => 'admin1',
			'password' => 'admin1',
			'protected' => true
		),
		array(
			'label' => 'Reseller',
			'username' => 'reseller1',
			'password' => 'reseller1',
			'protected' => true
		),
		array(
			'label' => 'Client',
			'username' => 'domain1.tld',
			'password' => 'domain1',
			'protected' => true
		),
	),

	// List of actions that must be totally disabled. Each action must be prefixed by 'onBefore'
	//
	// Important consideration:
	// Even if you add the 'onBeforeDeactivatePlugin' in the list below, you'll still able to deactivate this plugin.
	// The only way to protect this plugin against deactivation is to protect it using the plugin protection feature.
	'disabled_actions' => array(
		'onBeforeEditAdminGeneralSettings',
		'onBeforeAddUser',
		'onBeforeEditUser',
		'onBeforeDeleteUser',
		'onBeforeDeleteCustomer',
		'onBeforeAddFtp',
		'onBeforeEditFtp',
		'onBeforeDeleteFtp',
		'onBeforeAddSqlUser',
		'onBeforeEditSqlUser',
		'onBeforeDeleteSqlUser',
		'onBeforeAddSqlDb',
		'onBeforeDeleteSqlDb',
		'onBeforeUpdatePluginList',
		'onBeforeInstallPlugin',
		'onBeforeUninstallPlugin',
		'onBeforeEnablePlugin',
		'onBeforeDisablePlugin',
		'onBeforeUpdatePlugin',
		'onBeforeDeletePlugin',
		'onBeforeProtectPlugin',
		'onBeforeAddDomain',
		'onBeforeEditDomain',
		'onBeforeAddSubdomain',
		'onBeforeEditSubdomain',
		'onBeforeDeleteSubdomain',
		'onBeforeAddDomainAlias',
		'onBeforeEditDomainAlias',
		'onBeforeDeleteDomainAlias',
		'onBeforeAddMail',
		'onBeforeEditMail',
		'onBeforeDeleteMail',
		'onBeforeAddMailCatchall',
		'onBeforeAddExternalMailServer',
		'onBeforeChangeDomainStatus'
	),

	// List of pages to which access must be fully disabled
	//
	// This parameter allow to set a list of pages ( each of them defined using either a string or regexp ) which
	// must be disabled. This is needed when a page doesn't trigger an event allowing to stop sensible actions.
	// For instance, the software installer pages doesn't trigger any events. Thus, they must be fully disabled.
	//
	// Note: Only the pages from the admin, reseller and client levels can be disabled through this parameter.
	'disabled_pages' => array(
		'^/admin/software.*',
		'^/reseller/software.*',
		'^/client/software.*'
	)
);
