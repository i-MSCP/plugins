
## DomainAutoApproval v0.0.4 plugin for i-MSCP

Plugin allowing auto-approval of new domain aliases.

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

Plugin compatible with i-MSCP version >= 1.0.3.0

### INSTALLATION AND UPDATE:

**1.** Backup your current plugins/DomainAutoApproval/config.php file
   if any

**2.** Copy this plugin directory into the gui/plugins diretory of your
   i-MSCP installation.

**3.** Edit the config.php file to add domain names for which you want
   enable/disable auto-approval of domain aliases according the
   value of the 'approval_rule' parameter (See comments in file).

**4.** Set permissions by running:

	# perl /var/www/imscp/engine/setup/set-gui-permissions.pl

**5.** Go to the plugins interface, update the plugin list and activate
   the plugin.

See the [i-MSCP Wiki](http://wiki.i-mscp.net/doku.php?id=plugins:management "Plugin Management Interface") for more information about i-MSCP plugins management.

### AUTHORS AND CONTRIBUTORS

Laurent Declercq <l.declercq@nuxwin.com> (Author)

**Thanks you for using this plugin**
