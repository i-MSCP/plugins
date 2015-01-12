## i-MSCP ClamAV plugin v1.0.0

Plugin allows to use ClamAV with i-MSCP.

### LICENSE

Copyright (C) Sascha Bay <info@space2place.de> and Rene Schuster <mail@reneschuster.de>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

See [GPL v2](http://www.gnu.org/licenses/gpl-2.0.html "GPL v2")

### REQUIREMENTS

	- i-MSCP versions >= 1.1.0
	- See installation section for required software packages.
	
### Existing milter configurations

	This plugin will not check for an existing milter configuration in the Postfix main.cf file.
	If you need to add an extra milter, please ask in our forum!

### INSTALLATION

**1. Install the needed Debian / Ubuntu packages**

Installation of clamav packages:

	# aptitude update
	# aptitude install clamav clamav-base clamav-daemon clamav-freshclam clamav-milter
	
Stop the clamav-freshclam daemon if it is running:

	# service clamav-freshclam stop
	
Update the virus database:

	# freshclam
	
Start the clamav-freshclam daemon:

	# service clamav-freshclam start
	
Restart the clamav-daemon:

	# service clamav-daemon restart
	
**2. Get the plugin from Plugin Store**

http://i-mscp.net/filebase/index.php/Filebase/
	
**3. Plugin upload and installation**

	- Login into the panel as admin and go to the plugin management interface
	- Upload the ClamAV plugin archive
	- Install the plugin

### UPDATE

**1. Get the plugin from Plugin Store**

http://i-mscp.net/filebase/index.php/Filebase/

**2. Backup your current plugin config**

	- plugins/ClamAV/config.php
	
**3. Plugin upload and update**

	- Login into the panel as admin and go to the plugin management interface
	- Upload the ClamAV plugin archive
	- Update the plugin list

### CONFIGURATION

For the different configuration options please check the plugin config file.

	# plugins/ClamAV/config.php
	
After you made your config changes, don't forget to update the plugin list.

	- Login into the panel as admin and go to the plugin management interface
	- Update the plugin list
	
### TESTING

**Eicar-Test-Signature**

Send yourself a mail from another account (e.g. gmail or any other freemailer) with the following content in the message:
	
	X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*
	
Note that this should be reproduced in one line, without whitespace or line breaks.
	
### AUTHORS

 - Sascha Bay <info@space2place.de>
 - Rene Schuster <mail@reneschuster.de>

**Thank you for using this plugin.**
