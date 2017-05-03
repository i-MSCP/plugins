# i-MSCP Postscreen plugin v1.1.2

Plugin that provides Postscreen daemon for Postfix.

## Introduction

The Postfix Postscreen daemon provides additional protection against mail server overload. Postscreen process handles
multiple inbound SMTP connections and decides which clients may communicate with the Postfix SMTP server process. By
keeping spambots away, Postscreen leaves more SMTP server processes available for legitimate clients, and mitigate
server overload.

## Warning

Before installing this plugin, you're greatly encouraged to read the Postscreen howto for Postfix which is available at
[Postfix Postscreen Howto](http://www.postfix.org/POSTSCREEN_README.html "Postfix Postscreen Howto")

Be in mind that when using Postscreen, the mail clients must not submit mails through the port 25. This is also true
when not using the Postscreen plugin anyway. Indeed, the port 25 should be used by SMTP servers only. Mail clients
should be configured to submit mails through the submission port which is 587, or eventually but not recommended, the
port 465 for SSL connection.

## Requirements

* i-MSCP 1.3.x Serie or 1.4.x Serie
* i-MSCP Postfix server implementation
* Postfix version >= 2.8

For people that are using a distribution that doesn't provides Postfix version >= 2.8, you must update the postfix
package using the backports repository. Please refer to the documentation of your distribution documentation further details.

## Installation

1. Be sure that all requirements as stated in the requirements section are met
2. Upload the plugin through the plugin management interface
3. Install the plugin through the plugin management interface

## Update

1. Be sure that all requirements as stated in the requirements section are met
2. Backup your plugin configuration file if needed
3. Upload the plugin through the plugin management interface
4. Restore your plugin configuration file if needed (compare it with the new version first)
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
