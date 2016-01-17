# i-MSCP Postgrey plugin v1.1.0

Plugin which allows to run postgrey policy server for Postfix.

## Introduction

Posgtrey policy server, which implements greylisting, is a spam filtering method that rejects email from external
servers on the first try. Spammers don't usually retry sending their messages, whereas legitimate mail servers do.

Homepage: http://postgrey.schweikert.ch/

## Requirements

* i-MSCP version >= 1.2.3
* i-MSCP Postfix server implementation

## Debian / Ubuntu packages

* postgrey

You can install this package by running the following command:

```
# aptitude update && aptitude install postgrey
```

## Installation

1. Be sure that all requirements as stated in the requirements section are meets
2. Upload the plugin through the plugin management interface
3. Activate the plugin through the plugin management interface

## Update

1. Be sure that all requirements as stated in the requirements section are meets
2. Backup your plugin configuration file if needed
3. Upload the plugin through the plugin management interface
4. Restore your plugin configuration file if needed ( compare it with the new version first )
5. Update the plugin list through the plugin management interface

## Configuration

See [Configuration file](../Postgrey/config.php)

**Note:** When changing a configuration parameter in the plugin configuration file, do not forget to trigger plugin
change by updating the plugin list through the plugin management interface.

## License

```
i-MSCP Postgrey plugin
Copyright (C) 2015-2016 Laurent Declercq <l.declercq@nuxwin.com>

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
