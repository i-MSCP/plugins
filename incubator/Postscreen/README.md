## i-MSCP Postscreen plugin v0.0.1

Plugin allows to use Postscreen with Postfix on i-MSCP.

For more information please visit: http://www.postfix.org/POSTSCREEN_README.html

If you install this plugin manually, make sure it is installed in
gui/plugins/ - if the folder is called different it will not work!

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

	Plugin compatible with i-MSCP versions >= 1.1.0.rc4.7

	Postfix version >= 2.8

### INSTALLATION

**1.** Get the plugin from github

	# cd /usr/local/src
	# git clone git://github.com/i-MSCP/plugins.git

**2.** Create new Plugin archive

	# cd plugins
	# tar cvzf Postscreen.tar.gz Postscreen
	
**3.** Plugin upload and installation

	- Login into the panel as admin and go to the plugin management interface
	- Upload the Postscreen plugin archive
	- Install the plugin

### UPDATE

**1.** Get the plugin from github

	# cd /usr/local/src
	# git clone git://github.com/i-MSCP/plugins.git

**2.** Create new Plugin archive

	# cd plugins
	# tar cvzf Postscreen.tar.gz Postscreen

**3.** Backup your current plugin config

	- plugins/Postscreen/config.php
	
**4.** Plugin upload and update

	- Login into the panel as admin and go to the plugin management interface
	- Upload the Postscreen plugin archive
	- Update the plugin list

### CONFIGURATION

For the different configuration options please check the plugin config file.

	# plugins/Postscreen/config.php
	
After you made your config changes, don't forget to to update the plugin list.

	- Login into the panel as admin and go to the plugin management interface
	- Update the plugin list
	
### AUTHORS AND CONTRIBUTORS

 - Rene Schuster <mail@reneschuster.de> (Author)

**Thank you for using this plugin.**
