# i-MSCP SpamAssassin plugin v3.0.0

Provides the SpamAssassin anti-spam solution through Postfix MILTER.

## Introduction

Apache SpamAssassin is the #1 Open Source anti-spam platform giving system
administrators a filter to classify email and block "spam" (unsolicited bulk
email). It uses a robust scoring framework and plug-ins to integrate a wide
range of advanced heuristic and statistical analysis tests on email headers
and body text including text analysis, Bayesian filtering, DNS blocklists,
and collaborative filtering databases.

Apache SpamAssassin is a project of the Apache Software Foundation (ASF).

See [SpamAssassin Top-level README file](http://svn.apache.org/repos/asf/spamassassin/branches/3.4/README)
for further reading.

## Requirements

- i-MSCP 1.5.3 version, plugin API 1.5.1

## Installation

1. Be sure that all requirements are meet
2. Upload the plugin through the plugin management interface
3. Edit the plugin configuration file according your needs
4. Install the plugin through the plugin management interface

## Update

1. Be sure that all requirements as stated in the requirements section are met
2. Backup your plugin configuration file if needed
3. Upload the plugin through the plugin management interface

### Restore you plugin configuration file if needed

1. Restore your plugin configuration file (compare it with the new version first)
2. Update the plugin list through the plugin management interface

## Configuration

See the [plugin configuration file](config.php) for a fast overview.

When changing a configuration parameter in the plugin configuration file, you
must not forget to trigger a plugin list update through the plugin management
interface, else you're changes will not be token into account.

### SpamAssassin plugin definitions

This plugin comes with a predefined set of SpamAssassin plugin definitions. You
can add your own SpamAssassin plugin definitions in the `plugins_definitions`
section of the [plugin configuration file](config.php), using the following
plugin definition template:

```php
// Plugin configuration definition template
//
// Full SpamAssassin plugin name (case-sensitive) such as
// 'Mail::SpamAssassin::Plugin::AWL', 'Mail::SpamAssassin::Plugin::Bayes'...
'Mail::SpamAssassin::Plugin::<PluginName>'   => [
    // Whether or not this plugin need to be enabled (default false).
    'enabled'          => false,

    // The SA configuration file in which the plugin need to be loaded.
    'load_file'      => '',

    // List of required distribution packages (dependencies) for this plugin.
    //
    // This configuration parameter is OPTIONAL.
    'dist_packages'    => [],

    // Plugin global user preferences
    //
    // The global user preferences are stored in database with the '$GLOBAL'
    // username. If no user preferences are found for a mail account, the
    // global user preferences will be used, and if no global user preferences
    // are found, the default preferences, as set in the SpamAssassin
    // configuration files, or hardcoded in plugin, will be used.
    //
    // This configuration parameter is OPTIONAL.
    //
    // Warning: You need to add user preferences that belong to that plugin
    // only.
    'user_preferences' => [],

    // Plugin administrator settings
    //
    // The administrator settings are those on which user won't be able to act.
    //
    //  Available placeholders:
    //  - {PLUGINS_DIR}         : i-MSCP plugins root directory
    //  - {SA_DSN}              : DSN for the database connection
    //  - {SA_DATABASE_USER}    : SpamAssassin SQL user
    //  - {SA_DATABASE_PASSWORD}: SpamAssassin SQL password
    //  - {SPAMD_USER}          : SpamAssassin unix user
    //  - {SPAMD_GROUP}         : SpamAssassin unix group
    //  - {SPAMD_HOMEDIR}       : SpamAssassin homedir
    //
    // Warning: You need to add administrator settings that belong to that
    // plugin only
    'admin_settings' => [],

    // Cronjobs
    //
    // This configuration parameter is OPTIONAL. It should be
    // provided only if the plugin require time-based jobs.
    //
    //  Available placeholders:
    //  - {PLUGINS_DIR}         : i-MSCP plugins root directory
    //  - {SA_DSN}              : DSN for connection to the SpamAssassin database
    //  - {SA_DATABASE_NAME}     : SpamAssassin database name
    //  - {SA_DATABASE_USER}    : SpamAssassin SQL user
    //  - {SA_DATABASE_PASSWORD}: SpamAssassin SQL password
    //  - {SPAMD_USER}          : SpamAssassin unix user
    //  - {SPAMD_GROUP}         : SpamAssassin unix group
    //  - {SPAMD_HOMEDIR}       : SpamAssassin homedir
    //
    'cronjobs'        => [
        // See crontab(5) man-page for allowed values
        'TASKID' => [
            // Optional, default '@daily'
            'MINUTE'  => '',
            // Optional, default '*',
            // Only relevant if minute field isn't set with special string such
            // as @daily, @monthly... 
            'HOUR'    => '',
            // Optional, default '*',
            // Only relevant if minute field isn't set with special string such
            // as @daily, @monthly...
            'DAY'     => '',
            // Optional, default '*',
            // Only relevant if minute field isn't set with special string such
            // as @daily, @monthly...
            'MONTH'   => '',
            // Optional, default '*',
            // Only relevant if minute field isn't set with special string such
            // as @daily, @monthly...
            'DWEEK'   => '',
            // Optional, default 'root'
            'USER'    => '',
            // Required, shell command to execute
            'COMMAND' => ''
        ]
    ],

    // Shell commands to execute for the plugin configuration/deconfiguration
    //
    // This configuration parameter is OPTIONAL. It should be provided only if
    // the plugin require specific configuration, or deconfiguration tasks.
    //
    // Shell commands are executed through command shell interpreter (/bin/sh)
    // which, on newest Debian/Ubuntu distribution default to DASH(1).
    //
    //  Available placeholders:
    //  - {PLUGINS_DIR}         : i-MSCP plugins root directory
    //  - {SA_DSN}              : DSN for the connection to the SpamAssassin database
    //  - {SA_DATABASE_USER}    : SpamAssassin SQL user
    //  - {SA_DATABASE_PASSWORD}: SpamAssassin SQL password
    //  - {SPAMD_USER}          : SpamAssassin unix user
    //  - {SPAMD_GROUP}         : SpamAssassin unix group
    //  - {SPAMD_HOMEDIR}       : SpamAssassin homedir
    'shell_commands'  => [
        // Shell commands to be executed upon configuration phase
        'configure'   => [],
        // Shell commands to be executed upon deconfiguration phase
        'deconfigure' => []
    ],
    
    // Plugins confligs
    //
    // List of plugin which are in conflict with this one. If a conflict is
    // discovered while processing plugins configurations, an error will be
    // raised.
    //
    // This parameter is OPTIONAL.
    'conflicts' => [
        'Mail::SpamAssassin::Plugin::<AnotherPluginName>'
    ]
]
```

## Testing

### GTUBE (Generic Test for Unsolicited Bulk Email)

Send a mail with the following content to one of your i-MSCP mail accounts:

```
XJS*C4JDBQADN1.NSBN3*2IDNEN*GTUBE-STANDARD-ANTI-UBE-TEST-EMAIL*C.34X
```

Be aware that the `GTUBE` signature above must be added on a line, without
whitespace nor line break.

## License

    i-MSCP SpamAssassin plugin
    Copyright (C) 2015-2019 Laurent Declercq <l.declercq@nuxwin.com>
    Copyright (C) 2013-2016 Rene Schuster <mail@reneschuster.de>
    Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>
    
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; version 2 of the License
    
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
