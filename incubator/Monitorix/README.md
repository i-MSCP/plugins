## i-MSCP Monitorix plugin v0.0.3

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

	Plugin compatible with i-MSCP versions >= 1.1.0

### INSTALLATION

	Installation of monitorix:

	Debian:
	- Download package from: http://www.monitorix.org/downloads.html
	- wget http://www.monitorix.org/monitorix_(version)-izzy1_all.deb
	- apt-get install rrdtool libmime-lite-perl libhttp-server-simple-perl libhttp-server-simple-perl libconfig-general-perl
	- dpkg -i monitorix_(version)-izzy1_all.deb

	Ubuntu:
	- vi /etc/apt/sources.list
	- add deb http://apt.izzysoft.de/ubuntu generic universe
	- add the GPG key to your system: curl http://apt.izzysoft.de/izzysoft.asc 2>/dev/null | apt-key add -
	- apt-get update && apt-get install monitorix
	- disable the buildin webserver of montitorix (<httpd_builtin>)
	- check the logfile paths in the /etc/monitorix.conf

	- Login into the panel as admin and go to the plugin management interface
	- Upload the Monitorix plugin archive
	- Activate the plugin

### UPDATE

	- Backup your current plugins/Monitorix/config.php file
	- Login into the panel as admin and go to the plugin management interface
	- Upload the Monitorix plugin archive
	- Restore your plugins/Monitorix/config.php file (check for any change)
	- Update the plugin list through the plugin interface

### CONFIGURATION OF MONITORIX

 See the plugins/Monitorix/config.php file.

### AUTHORS AND CONTRIBUTORS

 * Sascha Bay <info@space2place.de> (Author)

**Thank you for using this plugin.**
