## Mailman v0.0.1 plugin for i-MSCP

Plugin allowing to manage mailing-lists through i-MSCP (using Mailman).

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

### Sponsors

Development of this plugin has been sponsored by [Retail Service Management](http://www.retailservicesystems.com "Retail Service Management")

### REQUIREMENTS

Plugin compatible with i-MSCP versions >= 1.1.0.rc2.4 (Not yet released)

For now, it's assumed that you are using Mailman as provided by the
Debian/Ubuntu mailman package.

### INSTALLATION

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

**5.** Create new Plugin archive

	# tar cvzf Mailman.tar.gz plugins/Mailman

**3.** Plugin upload and installation

	- Login into the panel as admin and go to the plugin management interface
	- Upload the Mailman plugin archive
	- Activate the plugin

### UPDATE

**1.** Get the plugin from github

	# cd /usr/local/src
	# git clone git@github.com:i-MSCP/plugins.git

**2.** Create new Plugin archive

	# tar cvzf Mailman.tar.gz plugins/Mailman

**3.** Plugin upload and update

	- Login into the panel as admin and go to the plugin management interface
	- Upload the Mailman plugin archive
	- Force plugin re-installation

### AUTHORS AND CONTRIBUTORS

### KNOWN BUGS

 - [Debian Related - wrong permissions, causes archiving to fail](http://bugs.debian.org/cgi-bin/bugreport.cgi?bug=603904 "Wrong permissions, causes archiving to fail")

Laurent Declercq <l.declercq@nuxwin.com> (Author)

**Thank you for using this plugin.**
