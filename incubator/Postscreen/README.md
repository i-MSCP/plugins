# i-MSCP Postscreen plugin v1.2.0

Provides Postscreen daemon for Postfix.

## Introduction

The Postfix postscreen(8) daemon provides additional protection against mail
server overload. One postscreen(8) process handles multiple inbound SMTP
connections, and decides which clients may talk to a Postfix SMTP server
process.

By keeping spambots away, postscreen(8) leaves more SMTP server processes
available for legitimate clients, and delays the onset of server overload
conditions.

## Warning

Before installing this plugin, you're greatly encouraged to read the Postscreen
howto for Postfix which is available at [Postfix Postscreen Howto](http://www.postfix.org/POSTSCREEN_README.html)

Be in mind that when using Postscreen, the mail clients must not submit mails
through the port 25. This is also true when not using the Postscreen plugin
anyway. Indeed, the port 25 should be used by SMTP servers only. Mail clients
should be configured to submit mails through the submission port which is 587,
or eventually but not recommended, the port 465 for SSL connection.

## Requirements

- i-MSCP Serie ≥ 1.4.x

## Installation

1. Be sure that all requirements as stated in the requirements section are met
2. Upload the plugin through the plugin management interface
3. Edit the plugin configuration file according your needs
4. Activate the plugin through the plugin management interface

## Update

1. Be sure that all requirements as stated in the requirements section are met
2. Backup your plugin configuration file if needed
3. Upload the plugin through the plugin management interface

### Restore you plugin configuration file if needed

1. Restore your plugin configuration file (compare it with the new version first)
2. Update the plugin list through the plugin management interface

## Configuration

See [Configuration file](config.php)

When changing a configuration parameter in the plugin configuration file, don't
forget to trigger a plugin list update, else you're changes will not be token
into account.

## License

    i-MSCP Postscreen plugin
    Copyright (C) 2013-2017 Laurent Declercq <l.declercq@nuxwin.com>
    Copyright (C) 2013-2017 Rene Schuster <mail@reneschuster.de>
    
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; version 2 of the License
    
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
