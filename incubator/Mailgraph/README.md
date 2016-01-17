# i-MSCP Mailgraph plugin v1.0.1

Plugin which provides statistical graphics for SMTP traffic ( Postfix and Sendmail )

## Requirements

* i-MSCP version >= 1.2.3

### Debian / Ubuntu package

* mailgraph

You can install this package by running the following command:

```
# aptitude install mailgraph
```

## Installation

1. Upload the plugin through the plugin management interface
2. Activate the plugin through the plugin management interface

## Update

1. Be sure that all requirements as stated in the requirements section are meets
2. Backup your plugin configuration file if needed
3. Upload the plugin through the plugin management interface
4. Restore your plugin configuration file if needed ( compare it with the new version first )
5. Update the plugin list through the plugin management interface

## Configuration

See [Configuration file](../Mailgraph/config.php)

**Note:** When changing a configuration parameter in the plugin configuration file, do not forget to trigger plugin
change by updating the plugin list through the plugin management interface.

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

See [GPL v2](http://www.gnu.org/licenses/gpl-2.0.html "GPL v2")

## Authors

* Laurent Declercq <l.declercq@nuxwin.com>
* Sascha Bay <info@space2place.de>
