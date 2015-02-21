# i-MSCP Postgrey plugin

Plugin which allow to run postgrey policy server for Postfix with i-MSCP

## Introduction

Posgtrey policy server, which implements greylisting, is a spam filtering method that rejects email from external servers
on the first try. Spammers don't usually retry sending their messages, whereas legitimate mail servers do.

Homepage: http://postgrey.schweikert.ch/

## Requirements

* i-MSCP version >= 1.2.2 ( Plugin API 2.0.17 )
* i-MSCP Postfix server implementation

## Debian / Ubuntu packages

* postgrey

You can install this package by running the following command:

```
# aptitude update && aptitude install postgrey
```

## Installation

1. Upload the plugin through the plugin management interface
2. Enable the plugin

## Update

1. Upload the plugin archive through the plugin management interface

## License

```
i-MSCP InstantSSH plugin
Copyright (C) 2014-2015 Laurent Declercq <l.declercq@nuxwin.com>

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
