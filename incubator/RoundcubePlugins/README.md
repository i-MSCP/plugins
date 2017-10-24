# i-MSCP RoundcubePlugins plugin v3.0.0

Provides plugins for the Roundcube Webmail Suite.

## Requirements

- i-MSCP 1.5.x Serie (version >= 1.5.2)
- Dovecot (Only needed if you want use the managesieve Roundcube plugin)
- Roundcube >= 1.3.x

### Debian / Ubuntu packages

The following packages are required if you want to install the managesieve
plugin for the Roundcube Webmail.

- dovecot-sieve
- dovecot-managesieved

You can install these packages by running the following commands:

```
# apt-get update
# apt-get install --no-install-recommends dovecot-sieve dovecot-managesieved
```

Prior executing the command above, be sure that you're using the i-MSCP Dovecot
server implementation.

## Installation

1. Be sure that all requirements as stated in the requirements section are met
2. Upload the plugin through the plugin management interface
3. Install the plugin through the plugin management interface

Note that plugin installation can take up several minutes.

## Update

1. Be sure that all requirements as stated in the requirements section are met
2. Backup your plugin configuration file if needed
3. Upload the plugin through the plugin management interface
4. Restore your plugin configuration file if needed
5. Update the plugin list through the plugin management interface

Note that plugin update can take up several minutes.

## Configuration

See[Configuration file](../RoundcubePlugins/config.php)

When changing a configuration parameter in the plugin configuration file, do
not forget to trigger a plugin list update through the plugin management
interface, else your changes won't be taken into account.

## Roundcube plugin definitions

This plugin make it possible to install any Roundcube plugin by adding plugin
definitions in the plugin [configuration file](../RoundcubePlugins/config.php).

### Basic Roundcube plugin definition

A basic Roundcube plugin definition looks as follows:

```php
        'calendar' => [
            'enabled' => true
        ],
```

Here we provide a definition for the `calendar` Roundcube plugin and we tell
the plugin that it must be activated.

### Installing a Roundcube plugin through the PHP dependency manager (Composer)

Most of time, you will have to go one step further by telling the plugin how to
get and install the Roundcube plugin. This can be done by adding a composer
section as follows


```php
        'calendar' => [
            'enabled' => true
            'composer' => [
                'repositories' => [
                    'type' => 'path',
                    'url'  => "$pluginDir/roundcube-plugins/kolab/plugins/calendar",
                ],
                'require'      => [
                    'kolab/calendar' => '^3.3.0'
                ]
            ]
        ]
```

Basically, a `composer` section is what you would put in a
[composer.json](https://getcomposer.org/doc/04-schema.md) file to make the PHP
dependency manager (Composer) able to download and install a PHP dependency,
here, a Roundcube plugin.

In the example above, we define a `Path` repository for the `kolab/calendar`
Roundcube plugin and we require a non-breaking update using the `^` operator.

You can learn further about composer by looking at the official
[documentation](https://getcomposer.org/doc/).

### Roundcube plugin configuration

One step further can be needed if you want to override default Roundcube
plugin parameters. To to so, you can add a `config` section that tells the
plugin the name of the Roundcube plugin configuration file name and the
parameters that must be overridden. All this can be done as follows:

```php
        'calendar' => [
            'enabled' => true
            'composer' => [
                'repositories' => [
                    'type' => 'path',
                    'url'  => "$pluginDir/roundcube-plugins/kolab/plugins/calendar"
                ],
                'require'      => [
                    'kolab/calendar' => '^3.3.0'
                ]
            ],
            'config' => [
                'file' => 'config.inc.php',
                'parameters' => [
                    'calendar_driver' => 'database',
                    'calendar_default_view' => 'agendaDay'
                ]
            ]
        ]
```

Basically, if you want to override a Roundcube plugin configuration parameter,
you must not edit its configuration file directly. Instead, you must process as
above. This is needed because i-MSCP will not save your changes on
reconfiguration/update.

## List of default Roundcube plugin definitions

- `additional_message_headers`: Add additional headers to or remove them from outgoing messages.
- `archive`: Adds a button to move the selected messages to an archive folder.
- `calendar`: Provide calendaring features.
- `contextmenu`: Creates context menus for various parts of Roundcube using commands from the toolbars.
- `emoticons`: Adds emoticons support.
- `logon_page`: Logon screen additions.
- `managesieve`: Adds a possibility to manage Sieve scripts (incoming mail filters).
- `newmail_notifier`: Provide notifications for new email.
- `odfviewer`: Open Document Viewer plugin.
- `pdfviewer`: Inline PDF viewer plugin.
- `password`: Password Change for Roundcube.
- `rcguard`: Enforces reCAPTCHA for users that have too many failed logins
- `tasklist`: Task management plugin.
- `vcard_attachments`: Detects vCard attachments and allows to add them to address book...
- `zipdownload`: Adds an option to download all attachments to a message in one zip file...

## License

    i-MSCP - internet Multi Server Control Panel
    Copyright (C) 2017 Laurent Declercq <l.declercq@nuxwin.com>
    
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; version 2 of the License
    
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

See [LICENSE](LICENSE)
