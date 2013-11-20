## i-MSCP RoundcubePlugins plugin v0.0.1

Plugin allows to use Roundcube Plugins with i-MSCP

If you install this plugin manually, make sure it is installed in
gui/plugins/ - if the folder is called different it will not work!

### INCLUDED ROUNDCUBE PLUGINS

**archive**

	Archive plugin adds a new button to the Roundcube toolbar to move messages to an (user selectable) archive folder.
	The button will only appear after the user configured the Archive folder on the Roundcube setting 'Special Folders'.

**contextmenu**

	Adds context menus to the message list, folder list and address book. 
	Possibility to mark mails as read/unread, delete, reply and forward mails.

**emoticons**

	Emoticons inserts nice smileys and other emoticons when the appropriate 
	text representations e.g. :-) are discovered in the mail text.

**managesieve**

	Allows the user to manage his sieve mail rules. Uses the Managesieve protocol.
	For this plugin you need Dovecot 2 on your system (please check INSTALLATION section).

**newmail_notifier**

	Can notify on new mails by focusing browser window and changing favicon, 
	playing a sound and displaying desktop notification (using webkitNotifications feature).

**pop3fetcher**

	Plugin pop3fetcher allows to add pop3 accounts and automatically fetch emails from them.

**zipdownload**

	Adds an option to download all attachments of a message in one zip file.
	

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
