## i-MSCP Demo plugin v1.0.2

Plugin allowing to create an i-MSCP Demo server in few minutes.

### Introduction

The demo plugin allow to setup an i-MSCP demo server in few minutes. It allow to:

1. Display a dialog box on the login page to allow the users to choose the account they want use to login
2. Protect some users accounts against deletion and password modification
3. Provide an actions list that must be disabled

### Requirements

Plugin compatible with i-MSCP versions >= 1.1.0

### Installation

1. Login into the panel as admin and go to the plugin management interface
2. Upload the plugin archive
3. Edit the plugin configuration (see below for available configuration parameters)
4. Activate the plugin
5. Protect the plugin

### Update

1. Backup your current plugins/Demo/config.php file
2. Unprotect the plugin
3. Login into the panel as admin and go to the plugin management interface
4. Upload the plugin archive
5. Restore your plugins/Demo/config.php file (compare it with the new version first)
6. Update the plugin list through the plugin interface
4. Protect the plugin

### Configuration

#### Modal dialog box on login page

The dialog box is only shown if you provide a set of configuration parameters that describe user accounts. The plugin
configuration file contains a simple PHP associative array (See the sample below).

To describe an user account, you must add a new section like below in the configuration file:

	...
		'user_accounts' => array(
			array(
				'label' => 'Administrator 1',
				'username' => 'admin1',
				'password' => 'admin1',
				'protected' => true
			)
		)
	...

**Note:** User accounts are shown in dialog box only if they exists in the i-MSCP database.

#### Protection against deletion and password modification (Demo users)

If an user account has the protected option set to TRUE (as above), it will be protected against deletion, and password
modification.

#### Disabled actions

The plugin allows to disable some actions such as addFtp, EditFtp, DeleteFtp. The action names are same as event names
dispatched in i-MSCP code. Only the onBefore actions are supported since the others are not really relevant in the demo
plugin context. You can see all integrated events by reading the iMSCP_Events class.

To disable one or more actions, you must add a new section like below in the plugin configuration file:

	...
			'disabled_actions' => array(
			'onBeforeAddFtp',
			'onBeforeEditFtp',
			'onBeforeDeleteFtp',
			'onBeforeAddSqlUser',
			'onBeforeEditSqlUser',
			'onBeforeDeleteSqlUser',
			'onBeforeAddSqlDb',
			'onBeforeDeleteSqlDb'
		)
	...

#### Configuration file sample

	// Configuration file sample for the demo plugin

	return array(
		//	List of user accounts that will be available via select box on login page. If an user account is protected, it
		//	will be imposible to remove it. Also, its password will be protected  against modification.
		'user_accounts' => array(
			array(
				'label' => 'Administrator 1',
				'username' => 'admin1',
				'password' => 'admin1',
				'protected' => true
			),
			array(
				'label' => 'Administrator 2',
				'username' => 'admin2',
				'password' => 'admin2',
				'protected' => true
			),
			array(
				'label' => 'Administrator 3',
				'username' => 'admin3',
				'password' => 'admin3',
				'protected' => true
			),
			array(
				'label' => 'Reseller 1',
				'username' => 'reseller1',
				'password' => 'reseller1',
				'protected' => true
			),
			array(
				'label' => 'Reseller 2',
				'username' => 'reseller2',
				'password' => 'reseller2',
				'protected' => true
			),
			array(
				'label' => 'Reseller 3',
				'username' => 'reseller3',
				'password' => 'reseller3',
				'protected' => true
			),
			array(
				'label' => 'Customer 1',
				'username' => 'domain1.tld',
				'password' => 'domain1',
				'protected' => true
			),
			array(
				'label' => 'Customer 2',
				'username' => 'domain2.tld',
				'password' => 'domain2',
				'protected' => true
			),
			array(
				'label' => 'Customer 3',
				'username' => 'domain3.tld',
				'password' => 'domain3',
				'protected' => true
			)
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
			'onBeforeAddExternalMailServer',
			'onBeforeChangeDomainStatus'
		)
	);

### License

	i-MSCP Demo plugin
	Copyright (C) 2012-2014 Laurent Declercq <l.declercq@nuxwin.com>

	This library is free software; you can redistribute it and/or
 	modify it under the terms of the GNU Lesser General Public
	License as published by the Free Software Foundation; either
	version 2.1 of the License, or (at your option) any later version.

	This library is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
	Lesser General Public License for more details.

	You should have received a copy of the GNU Lesser General Public
	License along with this library; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA

 See [LGPL v2.1](http://www.gnu.org/licenses/lgpl-2.1.txt "LGPL v2.1")

### Author

 * Laurent Declercq <l.declercq@nuxwin.com>
