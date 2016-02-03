# i-MSCP PolicydSPF plugin v1.0.0

Plugin which allows to run postfix-policyd-spf-perl for Postfix.

## Introduction

postfix-policyd-spf-perl is a basic Postfix policy engine for Sender Policy Framework (SPF) checking. It is implemented in pure Perl and uses Mail::SPF.

## Requirements

* i-MSCP version >= 1.2.3
* i-MSCP Postfix server implementation

## Debian / Ubuntu packages

* postfix-policyd-spf-perl

You can install this package by running the following command:

```
# aptitude update && aptitude install postfix-policyd-spf-perl
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

See [Configuration file](../PolicydSPF/config.php)

**Note:** When changing a configuration parameter in the plugin configuration file, do not forget to trigger plugin
change by updating the plugin list through the plugin management interface.

## License

```
i-MSCP PolicydSPF plugin
Copyright (C) 2016 Ninos Ego <me@ninosego.de>

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

* Ninos Ego <me@ninosego.de>
