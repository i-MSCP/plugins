# i-MSCP Monitorix plugin v1.1.0

Plugin providing a lightweight system monitoring tool for Linux/UNIX servers.

## Requirements

* i-MSCP versions >= 1.1.19 ( Plugin API 0.2.14 )
* Monitorix version >= 3.5.0

### Debian / Ubuntu packages

* rrdtool
* libmime-lite-perl
* libhttp-server-simple-perl
* libconfig-general-perl
* librrds-perl

You can install these packages by running the following commands:

```
# aptitude update
# aptitude install rrdtool libmime-lite-perl libhttp-server-simple-perl libconfig-general-perl librrds-perl
```

#### Monitorix package

1. Download the monitorix package from http://www.monitorix.org/downloads.html
2. Install the package by running the following command:

```
# dpkg -i monitorix*.deb
# apt-get install -f
```

## Installation

1. Be sure that all requirements as stated in the requirements section are meets
2. Upload the plugin through the plugin mangement interface
3. Install the plugin

## Update

1. Be sure that all requirements as stated in the requirements section are meets
2. Backup your plugin configuration file if needed
3. Upload the plugin through the plugin management interface
4. Restore your plugin configuration file if needed ( compare it with the new version first )
5. Update the plugin list through the plugin management interface

## Configuration

See [Configuration file](../Monitorix/config.php)
 
## Translation

You can translate this plugin by copying the [l10n/en_GB.php](l10n/en_GB.php) language file, and by translating all the
array values inside the new file.

Feel free to post your language files in our forum for intergration in a later release. You can also fork the plugin
repository and do a pull request if you've a github account.

**Note:** File encoding must be UTF-8.

## License

```
Copyright (C) Sascha Bay <info@space2place.de>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

See [LICENSE](LICENSE)

## Authors and contributors

* Laurent Declercq <l.declercq@nuxwin.com>
* Sascha Bay <info@space2place.de>
