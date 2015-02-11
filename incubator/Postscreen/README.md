## i-MSCP Postscreen plugin v0.0.5

Plugin allows to use Postscreen with Postfix on i-MSCP.

The Postfix Postscreen daemon provides additional protection against mail 
server overload. One Postscreen process handles multiple inbound SMTP 
connections, and decides which clients may talk to a Postfix SMTP server 
process. By keeping spambots away, Postscreen leaves more SMTP server 
processes available for legitimate clients, and delays the onset of server 
overload conditions.

### LICENSE

Copyright (C) Rene Schuster <mail@reneschuster.de>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

See [GPL v2](http://www.gnu.org/licenses/gpl-2.0.html "GPL v2")

### ATTENTION
	
Please read the [Postfix Postscreen Howto](http://www.postfix.org/POSTSCREEN_README.html "Postfix Postscreen Howto") before installing this plugin.

	If you use this Plugin your MUA clients should not submit mail via SMTP service on TCP port 25 because 
	that is used for the Postscreen daemon with all the checks.
	
	Instead your MUA clients should submit mails via SUBMISSION service on TCP port 587 or via SMTPS service on 
	TCP port 465 which both require client authentication.

### REQUIREMENTS

	- i-MSCP >= 1.2.2
	- Postfix version >= 2.8
	- See installation section for required software packages.

### INSTALLATION

**1. Install the needed Debian Squeeze / Ubuntu Lucid package**

All newer versions of Debian / Ubuntu already have a compatible postfix version.

**Debian Squeeze**

Add the backports of Debian Squeeze to your /etc/apt/sources.list:
	
	deb http://backports.debian.org/debian-backports squeeze-backports main contrib non-free

Installation of newer postfix package:

	# aptitude update
	# aptitude -t squeeze-backports install postfix
	
**Ubuntu Lucid**

Add the backports of Ubuntu Lucid to your /etc/apt/sources.list:

	deb http://archive.ubuntu.com/ubuntu lucid-backports main restricted universe

Installation of newer postfix package:

	# aptitude update
	# aptitude -t lucid-backports install postfix
	
**2. Get the plugin from Plugin Store**

http://i-mscp.net/filebase/index.php/Filebase/
	
**3. Plugin upload and installation**

	- Login into the panel as admin and go to the plugin management interface
	- Upload the Postscreen plugin archive
	- Install the plugin

### UPDATE

**1. Get the plugin from Plugin Store**

http://i-mscp.net/filebase/index.php/Filebase/

**2. Backup your current plugin config**

	- plugins/Postscreen/config.php
	
**3. Plugin upload and update**

	- Login into the panel as admin and go to the plugin management interface
	- Upload the Postscreen plugin archive
	- Update the plugin list

### CONFIGURATION

For the different configuration options please check the plugin config file.

	# plugins/Postscreen/config.php
	
After you made your config changes, don't forget to update the plugin list.

	- Login into the panel as admin and go to the plugin management interface
	- Update the plugin list
	
### AUTHORS

 - Rene Schuster <mail@reneschuster.de>

**Thank you for using this plugin.**
