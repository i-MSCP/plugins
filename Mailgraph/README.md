## i-MSCP Mailgraph plugin v0.0.1

Plugin providing statistical graphics for SMTP traffic (Postfix and Sendmail)

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

 - i-MSCP versions >= 1.1.0.rc4
 - mailgraph Debian package

### INSTALLATION AND UPDATE

**1.** Install needed Debian/Ubuntu package if not already done

	# apt-get install mailgraph

**2.** Get the plugin

	# cd /usr/local/src
	# git clone git://github.com/i-MSCP/plugins.git

**3.** Backup your current plugins/Mailgraph/config.php file if any

**4.** Copy the plugin directory into the gui/plugins directory of your i-MSCP installation.

	# cp -fR plugins/Mailgraph /var/www/imscp/gui/plugins

**5.** Set permissions by running:

	# perl /var/www/imscp/engine/setup/set-gui-permissions.pl

**6.** Go to the panel plugins interface, update the plugin list and activate the plugin.

### AUTHORS AND CONTRIBUTORS

 - Sascha Bay <info@space2place.de> (Author)
 - Laurent Declercq <l.declercq@nuxwin.com> (Contributor)

**Thank you for using this plugin.**
