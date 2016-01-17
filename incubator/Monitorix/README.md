# i-MSCP Monitorix plugin v1.2.2

Plugin providing a lightweight system monitoring tool for Linux/UNIX servers.

## Requirements

* i-MSCP version >= 1.2.3
* Monitorix version >= 3.5.0

### Debian / Ubuntu packages

* rrdtool
* libmime-lite-perl
* libhttp-server-simple-perl
* libconfig-general-perl
* librrds-perl

You can install these packages by running the following commands:

```bash
# aptitude update
# aptitude install rrdtool libmime-lite-perl libhttp-server-simple-perl libconfig-general-perl librrds-perl
```

#### Monitorix package

1. Download the monitorix package from http://www.monitorix.org/downloads.html
2. Install the package by running the following commands:

```bash
# dpkg -i monitorix*.deb
```

## Installation

1. Be sure that all requirements as stated in the requirements section are meets
2. Upload the plugin through the plugin management interface
3. Install the plugin through the plugin management interface

## Update

1. Be sure that all requirements as stated in the requirements section are meets
2. Backup your plugin configuration file if needed
3. Upload the plugin through the plugin management interface
4. Restore your plugin configuration file if needed ( compare it with the new version first )
5. Update the plugin list through the plugin management interface

## Configuration

See [Configuration file](../Monitorix/config.php)

**Note:** When changing a configuration parameter in the plugin configuration file, do not forget to trigger plugin
change by updating the plugin list through the plugin management interface.
 
## Translation

You can translate this plugin by copying the [l10n/en_GB.php](l10n/en_GB.php) language file, and by translating all the
array values inside the new file.

Feel free to post your language files in our forum for intergration in a later release. You can also fork the plugin
repository and do a pull request if you've a github account.

**Note:** File encoding must be UTF-8.

## License

```
Copyright (C) 2013-2016 Laurent Declercq <l.declercq@nuxwin.com>
Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

See [LICENSE](LICENSE)

## Authors

* Laurent Declercq <l.declercq@nuxwin.com>
* Sascha Bay <info@space2place.de>
