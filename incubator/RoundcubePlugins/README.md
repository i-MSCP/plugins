# i-MSCP RoundcubePlugins plugin v3.0.0

Provides Roundcube plugins through PHP dependency manager (Composer).

## Requirements

- i-MSCP 1.5.x Serie (version ≥ 1.5.2)
- Roundcube ≥ 1.3.x

## Installation

1. Be sure that all requirements are met
2. Upload the plugin through the plugin management interface
3. Activate the plugin through the plugin management interface

Note that depending on connection speed, installation can take up several
minutes.

## Update

1. Be sure that all requirements are met
2. Backup your plugin configuration file if needed
3. Upload the plugin through the plugin management interface
4. Restore your plugin configuration file if needed
5. Update the plugin list through the plugin management interface

Note that depending on connection speed, update can take up several minutes.

## Configuration

See the [configuration](../RoundcubePlugins/config.php) file for a fast overview.

When changing a configuration parameter in the plugin configuration file, you
must not forget to trigger a plugin list update through the plugin management
interface, else your changes won't be taken into account.

### Roundcube plugin definitions

This plugin make it possible to install any Roundcube plugin by adding plugin
definitions in the plugin [configuration file](../RoundcubePlugins/config.php).

#### Basic Roundcube plugin definition

A basic Roundcube plugin definition looks as follows:

```php
'name' => [
    'enabled' => true
]
```

Here we provide a definition for the `name` plugin and we tell that it must be
enabled.

#### Installing a Roundcube plugin through the PHP dependency manager (Composer)

Most of time, you will have to go one step further by telling how to get and
install the plugin. This can be done by adding a `composer` section as follows:

```php
'name' => [
    'enabled'  => true,
    'composer' => [
        'require' => [
            'vendor/name' => '~1.0.0'
        ]
    ]
]
```

Basically, a `composer` section is what you would put in a
[composer.json](https://getcomposer.org/doc/04-schema.md) file to make the PHP
dependency manager (Composer) able to download and install a PHP dependency,
here, a Roundcube plugin.

In the example above, we require the `vendor/name` composer package.

You can learn further about composer by looking at the official
[documentation](https://getcomposer.org/doc/).

#### Roundcube plugins not available through plugins.roundcube.net nor through packagist.org

Not all Roundcube plugins are available through official
[Roundcube plugin](https://plugins.roundcube.net/) repository, or through
[packagist.org](https://packagist.org) repository. In such a case you can
define your own repository. For instance:

```php
'name' => [
    'enabled'  => true,
    'composer' => [
        'repositories' => [
            'type' => 'vcs',
            'url'  => 'https://domain.tld/vendor/name.git'
        ],
        'require'      => '^1.0'
    ]
]
```

Note that the repository must conform to composer requirements.

Read [Composer repositories](https://getcomposer.org/doc/05-repositories.md)
for more details about supported repository types.

#### Installing a Roundcube plugin from a Git repository that hold many plugins

Roundcube plugins are not always maintained through their own repository and
therefore, they are not made publicly available through the PHP dependency
manager. You can still install these plugins as follows:

Let's take the example of the Kolab `odfviewer` Roundcube plugin that is made
available through the [Kolab Roundcube plugins](https://git.kolab.org/diffusion/RPK/)
repository:

##### Cloning the repository

```shell

git clone -b roundcubemail-plugins-kolab-3.3.3 --single-branch --depth 1 \
https://git.kolab.org/diffusion/RPK/roundcubemail-plugins-kolab.git roundcubemail-plugins-kolab-3.3.3
```

#### Adding plugin definition

```php
'odfviewer' => [
    'enabled'  => true,
    'composer' => [
        'repositories' => [
            [
                'type'    => 'path',
                'url'     => '/usr/local/src/roundcubemail-plugins-kolab-3.3.3/plugins/odfviewer',
                'options' => [
                    'symlink' => false
                ]
            ]
        ],
        'require'      => [
            'kolab/odfviewer' => '3.3.3'
        ]
    ]
]
```

See [PATH repository](https://getcomposer.org/doc/05-repositories.md#path) for
more details about the composer `Path` repository.

#### Roundcube plugin configuration

You can override default Roundcube plugin configuration as follows:

```php
'name' => [
    ...
    'config'  => [
        'file'         => 'config.inc.php',
        'include_file' => '/foo/bar/baz.php',
        'parameters' => [
            'param1' => 'value',
            'param2' => 'value'
        ]
    ]
]
```

where:

- `file` is an OPTIONAL parameter that allows to override default plugin
configuration template filename
- `include_file` is an OPTIONAL parameter that allows to provide the path of a file
that will be included in the Roundcube plugin configuration file. Note that the
file must be readable by the control panel user (eg: vu2000). A good place for
that file could be the `<PERSISTENT_PATH>/plugins/RoundcubePlugins` directory.
- `parameters`: is an OPTIONAL parameter that allows to provide plugin parameters.
Those parameters will be added at the end of the plugin configuration file

To resume, if you want to override a Roundcube plugin configuration parameter,
you must not edit its configuration file directly. Instead, you must process as
above. This is needed because the i-MSCP Roundcube package doesn't save your
changes on reconfiguration/update.

##### Configuration script

Sometime a plugin can require more configuration works. For that purpose, you
can provide your own configuration script as follows

```php
'name' => [
    ...
    'config'  => [
        ...
        'script' => '/foo/bar/baz.pl'
    ]
]
```
In the above example, we provide the `/foo/bar/baz.pl` Perl script. That script
will be executed automatically with the current action passed as first
argument:

- On Roundcube plugin pre-configuration: `/foo/bar/baz.pl pre-configure`
- On Roundcube plugin configuration: `/foo/bar/baz.pl configure`
- On Roundcube pre-deconfiguration: `/foo/bar/baz.pl pre-deconfigure`
- On Roundcube deconfiguration: `/foo/bar/baz.pl deconfigure`

Note that the script must provides a correct
[shebang](https://en.wikipedia.org/wiki/Shebang_(Unix)).

For instance:

- `#!/usr/bin/env perl` for a Perl script
- `#!/usr/bin/env php` for a PHP script
- `#!/bin/sh` for a shell script
- ...

See [configure-managesieve.pl](config/scripts/configure-managesieve.pl) for an
example.

## List of default Roundcube plugin definitions

- `additional_message_headers`: Add additional headers to or remove them from outgoing messages.
- `archive`: Adds a button to move the selected messages to an archive folder.
- `calendar`: Provide calendaring features.
- `contextmenu`: Creates context menus for various parts of Roundcube using commands from the toolbars.
- `emoticons`: Adds emoticons support.
- `managesieve`: Adds a possibility to manage Sieve scripts (incoming mail filters).
- `newmail_notifier`: Provide notifications for new emails.
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
