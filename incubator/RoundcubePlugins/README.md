## i-MSCP RoundcubePlugins plugin v0.0.1

Plugin allows to use Roundcube Plugins with i-MSCP

**Included Roundcube Plugins**

	- archive
	- contextmenu
	- emoticons
	- managesieve
	- newmail_notifier
	- pop3fetcher
	- zipdownload

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

### REQUIREMENTS

	Plugin compatible with i-MSCP versions >= 1.1.0.rc4.7
	
	Dovecot 2 is required if you want to use the Roundcube plugin managesieve.
	
### INSTALLATION

**1.** Install needed Debian/Ubuntu packages if not already done

**Debian Wheezy**

	Only necessary if you want to use the Roundcube Plugin managesieve.
	# aptitude update
	# aptitude install dovecot-sieve dovecot-managesieved
	
**2.** Get the plugin from github

	# cd /usr/local/src
	# git clone git://github.com/i-MSCP/plugins.git

**3.** Create new Plugin archive

	# cd plugins
	# tar cvzf RoundcubePlugins.tar.gz RoundcubePlugins
	
**4.** Plugin upload and installation

	- Login into the panel as admin and go to the plugin management interface
	- Upload the RoundcubePlugins plugin archive
	- Activate the plugin

### UPDATE

**1.** Get the plugin from github

	# cd /usr/local/src
	# git clone git://github.com/i-MSCP/plugins.git

**2.** Create new Plugin archive

	# cd plugins
	# tar cvzf RoundcubePlugins.tar.gz RoundcubePlugins

**3.** Backup your current plugin config

	- plugins/RoundcubePlugins/config.php
	
**4.** Plugin upload and update

	- Login into the panel as admin and go to the plugin management interface
	- Upload the RoundcubePlugins plugin archive
	- Force plugin re-installation

### AUTHORS AND CONTRIBUTORS

 - Rene Schuster <mail@reneschuster.de> (Author)
 - Sascha Bay <info@space2place.de> (Contributor)

**Thank you for using this plugin.**
