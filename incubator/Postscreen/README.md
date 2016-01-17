# i-MSCP Postscreen plugin v1.0.0

Plugin which allows to use Postscreen daemon for Postfix with i-MSCP.

## Introduction

The Postfix Postscreen daemon provides additional protection against mail server overload. One Postscreen process
handles multiple inbound SMTP connections, and decides which clients may talk to a Postfix SMTP server process. By
keeping spambots away, Postscreen leaves more SMTP server processes available for legitimate clients, and delays the
onset of server overload conditions.

## Warning

Before installing this plugin, you're greatly encouraged to read the Postscreen howto for Postfix which is available at
[Postfix Postscreen Howto](http://www.postfix.org/POSTSCREEN_README.html "Postfix Postscreen Howto")

In any case, be aware that if you use this plugin, your clients must no longer submit mails via SMTP service on TCP port
25 because it is used by the Postscreen daemon. Instead your clients must submit mails via SUBMISSION service on TCP port
587 or via SMTPS service on TCP port 465 which both require client authentication.

## Requirements

* i-MSCP version >= 1.2.3
* i-MSCP Postfix server implementation
* Postfix version >= 2.8

For those that are using a distribution which doesn't provides Postfix version >= 2.8, you must update the postfix
package using backports repository. Refer to your distro documentation for further details.

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

See [Configuration file](../Postscreen/config.php)

**Note:** When changing a configuration parameter in the plugin configuration file, do not forget to trigger plugin
change by updating the plugin list through the plugin management interface.

## License

```
Copyright (C) 2013-2016 Laurent Declercq <l.declercq@nuxwin.com>
Copyright (C) 2013-2016 Rene Schuster <mail@reneschuster.de>

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
* Rene Schuster <mail@reneschuster.de>
