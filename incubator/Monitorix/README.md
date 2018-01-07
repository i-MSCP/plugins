# i-MSCP Monitorix plugin v1.2.3

Plugin providing a lightweight system monitoring tool for Linux/UNIX servers.

## Requirements

* i-MSCP Serie 1.3.x, Serie 1.4.x
* Monitorix version >= 3.5.0

### Debian / Ubuntu packages

* rrdtool, libmime-lite-perl, libhttp-server-simple-perl, libconfig-general-perl, librrds-perl

You can install these packages by running the following commands:

    # apt-get update
    # apt-get --no-install-recommends install rrdtool libmime-lite-perl libhttp-server-simple-perl libconfig-general-perl librrds-perl

#### Monitorix package

1. Download the monitorix package from http://www.monitorix.org/downloads.html
2. Install the package by running the following commands: `dpkg -i monitorix*.deb`

## Installation

1. Be sure that all requirements as stated in the requirements section are met
2. Upload the plugin through the plugin management interface
3. Install the plugin through the plugin management interface

## Update

1. Be sure that all requirements as stated in the requirements section are met
2. Backup your plugin configuration file if needed
3. Upload the plugin through the plugin management interface
4. Restore your plugin configuration file if needed (compare it with the new version first)
5. Update the plugin list through the plugin management interface

## Configuration

See [Configuration file](../Monitorix/config.php)

**Note:** When changing a configuration parameter in the plugin configuration file, do not forget to trigger plugin
change by updating the plugin list through the plugin management interface.
 
## Troubleshootings

### Nginx failing to start due to Monitorix httpd daemon that listens on same port (8080)

When deactivating or uninstalling this plugin, the monitorix package is still installed, meaning that the default
Monitorix configuration parameters apply. This can lead to an Nginx start failure because the Monitorix httpd daemon
also listens on the port 8080. To solve this this issue, you must either deinstall the monitorix package, or edit the 
`/etc/monitorix/monitorix.conf` file manually to disable the monitorix httpd daemon:

    <httpd_builtin>
        enabled = y
        ...
    </httpd_builtin>

should become

    <httpd_builtin>
        enabled = n
        ...
    </httpd_builtin>

Once done, you should be able to start the Nginx service.
 
## Translation

You can translate this plugin by copying the [l10n/en_GB.php](l10n/en_GB.php) language file, and by translating all the
array values inside the new file.

Feel free to post your language files in our forum for intergration in a later release. You can also fork the plugin
repository and do a pull request if you've a github account. Note that the file encoding must be UTF-8, else it won't be
accepted.

## License

    i-MSCP Monitorix plugin
    Copyright (C) 2013-2017 Laurent Declercq <l.declercq@nuxwin.com>
    Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>
    
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; version 2 of the License
    
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

See [LICENSE](LICENSE)
