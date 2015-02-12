# i-MSCP Mailman plugin v0.0.3

Plugin which allows to manage mailing-lists through i-MSCP using Mailman.

## Requirements

* i-MSCP versions >= 1.1.11 ( plugin API >= 0.2.10 )

### Debian / Ubuntu packages

* mailman

You can install this package by running the following commands:

```
# aptitude update && aptitude install mailman
# newlist mailman
# service mailman restart
```

## Installation

1. Be sure that all requirements as stated in the requirements section are meets
2. Upload the plugin through the plugin management interface
3. Install the plugin

## Update

1. Be sure that all requirements as stated in the requirements section are meets
2. Backup your plugin configuration file if needed
3. Upload the plugin through the plugin management interface
4. Restore your plugin configuration file if needed ( compare it with the new version first )
5. Update the plugin list through the plugin management interface

## Known bugs

* [Debian Related - wrong permissions, causes archiving to fail](http://bugs.debian.org/cgi-bin/bugreport.cgi?bug=603904 "Wrong permissions, causes archiving to fail")

## License

```
i-MSCP Mailman plugin
Copyright (C) 2013-2015 Laurent Declercq <l.declercq@nuxwin.com>

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

see [LICENSE](LICENSE)

## Sponsors

The development of this plugin has been sponsored by:

* [IP-Projects GmbH & Co. KG](https://www.ip-projects.de/ "IP-Projects GmbH & Co. KG")
* [Retail Service Management](http://www.retailservicesystems.com "Retail Service Management")

## Author

* Laurent Declercq <l.declercq@nuxwin.com>
