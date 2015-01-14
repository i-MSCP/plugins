# i-MSCP ClamAV plugin v1.0.0

Plugin which allows to use ClamAV with i-MSCP.

## Requirements

* i-MSCP versions >= 1.1.22 ( Plugin API 0.2.16 )

### Debian / Ubuntu packages

* clamav
* clamav-base
* clamav-daemon
* clamav-freshclam
* clamav-milter

You can install these packages by running the following commands:

```
# aptitude update
# aptitude install clamav clamav-base clamav-daemon clamav-freshclam clamav-milter
# service clamav-freshclam stop
# freshclam
# service clamav-freshclam start
# service clamav-daemon start
```

## Installation

1. Be sure that all requirements as stated in the requirements section are meets
2. Upload the plugin through the plugin management interface
3. Activate the plugin

## Update

1. Be sure that all requirements as stated in the requirements section are meets
2. Backup your plugin configuration file if needed
3. Upload the plugin through the plugin management interface
4. Restore your plugin configuration file if needed ( compare it with the new version first )
5. Update the plugin list through the plugin management interface

## Configuration

See [Configuration file](../ClamAV/config.php)

## Eicar-Test-Signature

Send a mail with the following content to one of your i-MSCP mail accounts  by using another mail account:

```
X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*
```

Be aware that the EICAR signature above must be added on a line, without whitespace nor line break.

## License

```
Copyright (C) 2013-2015 Sascha Bay <info@space2place.de> and Rene Schuster <mail@reneschuster.de>

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

* Laurent Declercq <l.declercq@nuxwin.com> ( Contributor )
* Rene Schuster <mail@reneschuster.de> ( Author )
* Sascha Bay <info@space2place.de> ( Author )
