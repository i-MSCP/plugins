# i-MSCP RoundcubePlugins plugin v3.0.0

Provides several plugins for the Roundcube webmail

## Requirements

- i-MSCP ≥ 1.5.3 version (Plugin API 1.5.1)
- Roundcube ≥ 1.3.0

## Installation

1. Be sure that all requirements are met
2. Upload and activate the plugin through the plugin management interface

## Update

1. Be sure that all requirements are met
2. Upload the plugin through the plugin management interface

## Configuration

See the [plugin configuration](../RoundcubePlugins/config.php) file for a fast
overview.

### Warning

When changing a configuration parameter in the plugin configuration file, you
need not forget to trigger a plugin list update through the plugin management
interface, else your changes won't be taken into account.

### Roundcube plugin definitions

This plugin make it possible to install any Roundcube plugin by adding
Roundcube plugin definitions in the
[plugin configuration file](../RoundcubePlugins/config.php).

#### Basic Roundcube plugin definition

A basic Roundcube plugin definition looks as follows:

```php
'plugins_definitions' => [
    'name' => [
        'enabled' => true
    ]
]
```

Here we provide a definition for the `name` Roundcube plugin and we tell that
it must be enabled. Such a section should be sufficient for plugins that are
provided with the Roundcube distribution, and which don't require further
configuration.

#### Roundcube plugin made available through the composer PHP dependency manager

For the Roundcube plugins which are provided as composer packages, that is,
through the composer PHP dependency manager, we need to go one step further to
tell the plugin how to get and install them. This is done by adding a
`composer` section in the plugin definition as follows:

```php
'plugins_definitions' => [
    'name' => [
        'enabled'  => true,
        'composer' => [
            'require' => [
                'vendor/name' => '~1.0.0'
            ]
        ]
    ]
]
```

The `composer` section above is a PHP transcription of what we would have
inserted in the Roundcube [composer.json](https://getcomposer.org/doc/04-schema.md)
file to ask the composer PHP dependency manager to install the `name` composer
package.

Basically put, we tell the composer PHP dependency manager that we want install
the `vendor/name` composer package, in a version greater or equal to the version
`1.0.0` but less than the version `1.1.0`.

You can learn further about the composer PHP dependency manager by looking at
the official [documentation](https://getcomposer.org/doc/).

##### VCS repository

Sometime, the composer packages are not made available through the default
composer repositories, that is the Roundcube and Packagist composer
repositories. In such a case, you can add a composer `repositories`
section that declares the VCS repositories to use. For instance:

```php
'plugins_definitions' => [
    'name' => [
        'enabled'  => true,
        'composer' => [
            'repositories' => [
                [
                    'type' => 'vcs',
                    'url'  => 'https://domain.tld/vendor/name.git'
                ]
            ],
            'require' => [
                'vendor/name' => '~1.0.0'
            ]
        ]
    ]
]
```

##### Path repository

Composer doesn't support monolithic VCS repositories, that is, repositories
which hold several composer packages. In such a case, you can still install
those plugins through the composer PHP dependency manager, using a `path`
repository:

Let's take the example of the Kolab `odfviewer` Roundcube plugin that is made
available through the monolithic
[Kolab Roundcube plugins](https://git.kolab.org/diffusion/RPK/) VCS repository:

###### Cloning the repository

First, you need to clone the repository to make it available locally. A good
place for it would be the `/var/www/imscp/gui//data/persistent/plugins/RoundcubePlugins`
directory which is persistent:

```shell
cd /var/www/imscp/gui//data/persistent/plugins/RoundcubePlugins
git clone https://git.kolab.org/diffusion/RPK/roundcubemail-plugins-kolab.git
cd roundcubemail-plugins-kolab.git
git checkout roundcubemail-plugins-kolab-3.4.5
```

Note: Of course, a pre-configuration script would fit better for such a task.
See the [configure-kolab.pl](config/scripts/configure-managesieve.pl) script
for an example.

###### Defining the `path` repository

Once you have cloned the repository, you need add the `path` repository in your
Roundcube plugin definition as follows:

```php
'plugins_definitions' => [
    'odfviewer' => [
        'enabled'  => true,
        'composer' => [
            'repositories' => [
                [
                    'type'    => 'path',
                    'url'     => '/var/www/imscp/gui//data/persistent/plugins/RoundcubePlugins/roundcubemail-plugins-kolab/plugins/odfviewer',
                    'options' => [
                        'symlink' => false
                    ]
                ]
            ],
            'require'      => [
                'kolab/odfviewer' => '^3.4'
            ]
        ]
    ]
]
```

See the [PATH repository](https://getcomposer.org/doc/05-repositories.md#path)
documentation for more details about the composer `path` repository.

#### Roundcube plugin configuration

Most of the Roundcube plugins are configurable. You can configure them through
this plugin by adding a `config` section in your Roundcube plugin definition.
For instance:

```php
'plugins_definitions' => [
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
)
```

where:

- `file` is an OPTIONAL parameter that allows overriding of default plugin
configuration template filename.
- `include_file` is an OPTIONAL parameter that allows to provide the path of a
file that will be included in the final Roundcube plugin configuration file.
Note that the file must be readable by the control panel user (eg: vu2000). A good place for
that file could be the `/var/www/imscp/gui/data/persistent/plugins/RoundcubePlugins`
directory.
- `parameters`: is an OPTIONAL parameter that allows to provide plugin parameters.
Those parameters will be inserted at the end of the plugin configuration file.

To resume, if you want to override a Roundcube plugin configuration parameter,
you **MUST** never edit its configuration file directly. Instead, you must
process as above. This is needed because the i-MSCP Roundcube package doesn't
save your changes on reconfiguration/update.

##### Configuration scripts

Sometime, Roundcube plugins need further configurations steps, such as the
pre-installation of some distribution packages and so on... For that purpose,
you can provide your own configuration script which will be automatically
executed by the plugin backend:

```php
'plugins_definitions' => [
    'name' => [
        ...
        'config'  => [
            ...
            'script' => '/foo/bar/baz.pl'
        ]
    ]
]
```

The `/foo/bar/configure-baz.pl` Perl script provided in the above example would
be executed at different stages, with the current stage passed-in as first
argument. Different stages are:

- On the Roundcube plugin `preconfiguration` stage (before execution of composer)
- On the Roundcube plugin `configuration` stage (after execution of composer)
- On the Roundcube predeconfiguration `stage` (before execution of composer)
- On the Roundcube `deconfiguration` stage (after execution of composer)

You can also pass additional arbitrary parameters to the configuration scripts,
for the different stages, as follows:

```php
'plugins_definitions' => [
    'name' => [
        ...
        'config'  => [
            ...
            'script' => '/foo/bar/baz.pl',
            'script_argv' => [
                'preconfigure' => [
                    'foo', 'bar'
                ],
                'configure' => [
                    'baz', 'foo'
                ],
                'predeconfigure' => [
                    'foo', 'bar'
                ],
                'deconfigure' => [
                    'baz', 'foo'
                ],
            ]
        ]
    ]
]
```

Note that the configuration scripts must provides a correct
[shebang](https://en.wikipedia.org/wiki/Shebang_(Unix)).

For instance:

- `#!/usr/bin/env perl` for a Perl script
- `#!/usr/bin/env php` for a PHP script
- `#!/bin/sh` for a shell script
- ...

See the [configure-managesieve.pl](config/scripts/configure-managesieve.pl) script
for an example.

## List of default Roundcube plugin definitions

- `additional_message_headers`: Add additional headers to or remove them from
outgoing messages.
- `archive`: Adds a button to move the selected messages to an archive folder.
- `calendar`: Provide calendaring features.
- `contextmenu`: Creates context menus for various parts of Roundcube using
commands from the toolbars.
- `emoticons`: Adds emoticons support.
- `jsxc`: Provide real-time web chat based on JSXC.
- `logon_page`: Make it possible to write custom content on Roundcube login
page.
- `managesieve`: Adds a possibility to manage Sieve scripts (incoming mail
filters).
- `newmail_notifier`: Provide notifications for new emails.
- `odfviewer`: Open Document Format (odt, odp, ods) Viewer.
- `password`: Password Change for Roundcube.
- `pdfviewer`: Portable Document Format (pdf) viewer.
- `rcguard`: Enforces reCAPTCHA for users that have too many failed logins
- `tasklist`: Task management plugin.
- `vcard_attachments`: Detects vCard attachments and allows to add them to
address book...
- `zipdownload`: Adds an option to download all attachments to a message in
one zip file...

Note that none of these plugins are enabled by default. You have to enable them
by editing the config.php plugin configuration file and updating the plugin list
through the plugin management interface. This is done like this because
requirements are not always same for all hosting providers.

## License

    i-MSCP - internet Multi Server Control Panel
    Copyright (C) 2017-2019 Laurent Declercq <l.declercq@nuxwin.com>
    
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; version 2 of the License
    
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

See [LICENSE](LICENSE)
