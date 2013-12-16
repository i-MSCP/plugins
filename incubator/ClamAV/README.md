## i-MSCP ClamAV plugin v0.0.1

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

	Plugin compatible with i-MSCP versions >= 1.1.0.rc4.7
	
### Existing milter configurations

	This plugin will not check for an existing milter configuration in the Postfix main.cf file.
	If you need to add an extra milter, please ask in our forum!

### INSTALLATION

**1.** Install needed Debian/Ubuntu packages if not already done

**1.1** Install the clamav packages

	# aptitude update
	# aptitude install clamav clamav-base clamav-daemon clamav-freshclam clamav-milter
	
**1.2** Stop the clamav-freshclam daemon if it is running

	# service clamav-freshclam stop
	
**1.3** Update the virus database

	# freshclam
	
**1.4** Start the clamav-freshclam daemon

	# service clamav-freshclam start
	
**1.5** Restart the clamav-daemon

	# service clamav-daemon restart
	
**2.** Get the plugin from github

	# cd /usr/local/src
	# git clone git://github.com/i-MSCP/plugins.git

**3.** Create new Plugin archive

	# cd plugins
	# tar cvzf ClamAV.tar.gz ClamAV
	
**4.** Plugin upload and installation

	- Login into the panel as admin and go to the plugin management interface
	- Upload the ClamAV plugin archive
	- Install the plugin

### UPDATE

**1.** Get the plugin from github

	# cd /usr/local/src
	# git clone git://github.com/i-MSCP/plugins.git

**2.** Create new Plugin archive

	# cd plugins
	# tar cvzf ClamAV.tar.gz ClamAV

**3.** Backup your current plugin config

	- plugins/ClamAV/config.php
	
**4.** Plugin upload and update

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
