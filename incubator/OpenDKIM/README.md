## i-MSCP OpenDKIM plugin v0.0.1

Plugin allows to manage OpenDKIM with i-MSCP.

If you install this plugin manually, make sure it is installed in
gui/plugins/ - if the folder is called different it will not work!

### LICENSE

Copyright (C) Sascha Bay <info@space2place.de>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

See [GPL v2](http://www.gnu.org/licenses/gpl-2.0.html "GPL v2")

### REQUIREMENTS

	Plugin compatible with i-MSCP versions >= 1.1.0.rc4
	installation of opendkim (apt-get install opendkim)
	installation of opendkim-tools (apt-get install opendkim-tools)
	
### Existing milter configurations
	This plugin will not check an existing milter configuration in the postfix main.cf.
	If you need to add an extra milter, ask in out forum!
	
### INSTALLATION AND UPDATE

**1.** Backup your current plugins/OpenDKIM/config.php

**2.** Get the plugin

	# cd /usr/local/src
	# git clone git://github.com/i-MSCP/plugins.git

**3.** Copy the plugin directory into the gui/plugins directory of your i-MSCP installation.

	# cp -fR plugins/OpenDKIM /var/www/imscp/gui/plugins

**4.** Set permissions by running:

	# perl /var/www/imscp/engine/setup/set-gui-permissions.pl

**5.** Go to the panel plugins interface, update the plugin list and activate the plugin.

### CONFIGURATION OF THE OPENDKIM DAEMON

You can set the port in the file plugins/OpenDKIM/config.php
Use min. 4 and not more as 5 digits and not greater 65535
Default and fallback port is: 12345

You can set trusted domains in the config.php. As default 127.0.0.1 and localhost are added

### AUTHORS AND CONTRIBUTORS

 - Sascha Bay <info@space2place.de> (Author)
 - Rene Schuster <mail@reneschuster.de> (Contributor)

**Thank you for using this plugin.**
