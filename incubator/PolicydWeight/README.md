# i-MSCP PolicydWeight plugin v1.0.0

Plugin which allows to run policyd-weight policy daemon for Postfix.

## Introduction

policyd-weight is a Perl policy daemon for the Postfix MTA intended to eliminate forged envelope senders and HELOs (i.e.
in bogus mails). It allows you to score DNSBLs (RBL/RHSBL), HELO, MAIL FROM and client IP addresses before any queuing
is done. It allows you to REJECT messages which have a score higher than allowed, providing improved blocking of spam
and virus mails. policyd-weight caches the most frequent client/sender combinations (SPAM as well as HAM) to reduce the
number of DNS queries.

Homepage: http://www.policyd-weight.org/

## Requirements

* i-MSCP version >= 1.2.3
* i-MSCP Postfix server implementation

## Debian / Ubuntu packages

* policyd-weight

You can install this package by running the following command:

```
# aptitude update && aptitude install policyd-weight
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

See [Configuration file](../PolicydWeight/config.php)

**Note:** When changing a configuration parameter in the plugin configuration file, do not forget to trigger plugin
change by updating the plugin list through the plugin management interface.

### policyd-weight

This plugin doesn't handle policy-weight configuration options. To configure policyd-weight, you must edit the
/etc/policyd-weight configuration.

## License

```
i-MSCP PolicydWeight plugin
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
