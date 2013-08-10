## i-MSCP Monitorix plugin v0.0.1

Plugin provides lightweight system monitoring tool for Linux/UNIX servers.

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

	Plugin compatible with i-MSCP versions >= 1.1.0.rc3
	installation of monitorix:
	* vi /etc/apt/sources.list
	* add deb http://apt.izzysoft.de/ubuntu generic universe
	* add the GPG key to your system: curl http://apt.izzysoft.de/izzysoft.asc 2>/dev/null | apt-key add -
	* apt-get update
	* apt-get install monitorix
	* disable the buildin webserver of montitorix (<httpd_builtin>)
	* check the logfile paths in the /etc/monitorix.conf
	
### INSTALLATION AND UPDATE

**1.** Backup your current plugins/Monitorix/config.php

**2.** Get the plugin

	# cd /usr/local/src
	# git clone git://github.com/i-MSCP/plugins.git

**3.** Copy the plugin directory into the gui/plugins directory of your i-MSCP installation.

	# cp -fR plugins/Monitorix /var/www/imscp/gui/plugins

**4.** Set permissions by running:

	# perl /var/www/imscp/engine/setup/set-gui-permissions.pl

**5.** Go to the panel plugins interface, update the plugin list and activate the plugin.

### CONFIGURATION OF THE OPENDKIM DAEMON

You can set the port in the file plugins/Monitorix/config.php
Set the colors of the graphs
Activate the graphic you want to see

### AUTHORS AND CONTRIBUTORS

Sascha Bay <info@space2place.de> (Author)

**Thank you for using this plugin.**
