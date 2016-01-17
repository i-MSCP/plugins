# i-MSCP Demo plugin v1.1.1

Plugin which allows to create an i-MSCP Demo server in few minutes.

## Introduction

The demo plugin allows to setup an i-MSCP demo server in few minutes. It allow to:

1. Display a dialog box on the login page to allow the users to choose the account they want use to login
2. Protect some users accounts against deletion and password modification
3. Provide an actions list that must be disabled

## Requirements

* i-MSCP version >= 1.2.3

## Installation

1. Upload the plugin through the plugin management interface
2. Edit the plugin configuration (see below for available configuration parameters)
3. Activate the plugin through the plugin management interface
4. Protect the plugin through the plugin management interface

## Update

1. Backup your plugin configuration file if needed
2. Unprotect the plugin
4. Upload the plugin through the plugin management interface
5. Restore your plugin configuration file if needed ( compare it with the new version first )
6. Update the plugin list through the plugin management interface
4. Protect the plugin through the plugin management interface

## Configuration

### Modal dialog box on login page

The dialog box is only shown if you provide a set of configuration parameters that describe user accounts. The plugin
configuration file contains a simple PHP associative array (See the sample below).

To describe an user account, you must add a new section like below in the configuration file:

```php
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
```
**Note:** User accounts are shown in dialog box only if they exists in the i-MSCP database.

### Protection against deletion and password modification (Demo users)

If an user account has the protected option set to TRUE (as above), it will be protected against deletion, and password
modification.

### Disabled actions

The plugin allows to disable some actions such as addFtp, EditFtp, DeleteFtp. The action names are same as event names
dispatched in i-MSCP code. Only the onBefore actions are supported since the others are not really relevant in the demo
plugin context. You can see all integrated events by reading the iMSCP_Events class.

To disable one or more actions, you must add a new section like below in the plugin configuration file:

```php
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
```

### Disabled pages

The plugin also allows to disable a specific list of pages, each of them defined using either a string or regexp. This
is needed when a page doesn't trigger any events allowing to stop sensible actions. For instance, the software installer
pages doesn't trigger any events. Thus, the only way to disable the sensible actions is to fully disable those pages.

```php
...
	'disabled_pages' => array(
		'^/admin/software.*',
		'^/reseller/software.*',
		'^/client/software.*'
	)
...
```

### Configuration file sample

```php
// Configuration file sample for the demo plugin

return array(
	//	List of user accounts that will be available via select box on login page. If an user account is protected, it
	//	will be imposible to remove it. Also, its password will be protected  against modification.
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
```

**Note:** When changing a configuration parameter in the plugin configuration file, do not forget to trigger plugin
change by updating the plugin list through the plugin management interface.

## License

```
i-MSCP Demo plugin
Copyright (C) 2012-2016 Laurent Declercq <l.declercq@nuxwin.com>

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
```

See [LICENSE](LICENSE)

## Author

* Laurent Declercq <l.declercq@nuxwin.com>
