## Mailman v0.0.1 plugin for i-MSCP

# WARNING: This plugin is still under development. It is not ready for use.

Plugin allowing to manage mailing-lists (using Mailman).

If you install this plugin manually, make sure it is installed in
gui/plugins/ - if the folder is called different it will not work!

### LICENSE

Copyright (C) Laurent Declercq <l.declercq@nuxwin.com>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

See [GPL v2](http://www.gnu.org/licenses/gpl-2.0.html "GPL v2")

### REQUIREMENTS

Plugin compatible with i-MSCP version >= 1.1.0.rc2.4

For now, it's assumed that you are using Mailman as provided by the
Debian/Ubuntu mailman package.

### INSTALLATION AND UPDATE

**1.** Install needed Debian/Ubuntu package if not already done

	# aptitude update && aptitude install mailman

**2.** Create mailman site list if not already done

	# newlist mailman

This is really needed. Without this list, mailman will refuse to start.

**3.** Restart mailman

	# service mailman restart

**4.** Get the plugin from github

	# cd /usr/local/src
	# git clone git@github.com:i-MSCP/plugins.git

**5.** Copy the plugin directory into the i-MSCP gui/plugins directory

	# cp -fR plugins/Mailman /var/www/imscp/gui/plugins

**6.** Set permissions

	# perl /var/www/imscp/engine/setup/set-gui-permissions.pl

**7.** Login into the panel as admin, and activate the plugin through the plugins interface

See the [i-MSCP Wiki](http://wiki.i-mscp.net/doku.php?id=plugins:management "Plugin Management Interface") for more information about i-MSCP plugins management.

### AUTHORS AND CONTRIBUTORS

Laurent Declercq <l.declercq@nuxwin.com> (Author)

**Thank you for using this plugin.**
