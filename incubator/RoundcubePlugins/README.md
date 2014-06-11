## i-MSCP RoundcubePlugins plugin v0.0.6

Plugin allows to use Roundcube Plugins with i-MSCP.

### LICENSE

Copyright (C) Rene Schuster <mail@reneschuster.de> and Sascha Bay <info@space2place.de>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

See [GPL v2](http://www.gnu.org/licenses/gpl-2.0.html "GPL v2")

### INCLUDED ROUNDCUBE PLUGINS

**additional_message_headers**

	Adds a header identifying the originating ip for the mails sent via webmail.
	
**archive**

	Archive plugin adds a new button to the Roundcube toolbar to move messages to an (user selectable) archive folder.
	The button will only appear after the user configured the Archive folder.
	
	Roundcube user config: Settings -> Preferences -> Special Folders -> Archive

**calendar**

	Calendar plugin is a full calendar for Roundcube.
	
	Roundcube user config: Settings -> Preferences -> Calendar
	
**contextmenu**

	Adds context menus to the message list, folder list and address book. 
	Possibility to mark mails as read/unread, delete, reply and forward mails.

**dkimstatus**

	Displays the DKIM Signature status of each mail in Roundcube.

**emoticons**

	Emoticons inserts nice smileys and other emoticons when the appropriate 
	text representations e.g. :-) are discovered in the mail text.

**logon_page**

	Allows to display additional information (HTML code block) at logon page.
	Configuration: Put your content into the file config-templates/logon_page/logon_page.html
	It will be parsed by Roundcube templates engine, so you can use all template features (tags).
	
**managesieve**

	Uses the managesieve protocol and allows the user to manage his sieve mail rules.
	A default Spam sieve rule will be created after the user opened the Filters configuration in Roundcube.
	
	Roundcube user config: Settings -> Filters

**newmail_notifier**

	Can notify on new mails by focusing browser window and changing favicon, 
	playing a sound and displaying desktop notification (using webkitNotifications feature).
	
	Roundcube user config: Settings -> Preferences -> Mailbox View
	
**pdfviewer**
	
	Roundcube inline pdf viewer.
	
**pop3fetcher**

	Plugin pop3fetcher allows to add pop3 accounts and automatically fetch emails from them.
	
	Roundcube user config: Settings -> Preferences -> Other Accounts

**tasklist**
	
	Task management plugin for Roundcube.
	
**zipdownload**

	Adds an option to download all attachments of a message in one zip file.
	
### REQUIREMENTS

	- i-MSCP versions >= 1.1.11
	- Dovecot
	- Roundcube 0.9.x
	- See installation section for required software packages.
	
### INSTALLATION

**1. Install the needed Debian / Ubuntu packages**

Only necessary if you want to use the Roundcube Plugin managesieve.

**Attention:** Debian Squeeze and Ubuntu Lucid have these binaries included with
the dovecot package. So on Squeeze and Lucid you don't need to install anything.
 
	# aptitude update
	# aptitude install dovecot-sieve dovecot-managesieved
	
**2. Get the plugin from Plugin Store**

http://i-mscp.net/filebase/index.php/Filebase/
	
**3. Plugin upload and installation**

	- Login into the panel as admin and go to the plugin management interface
	- Upload the RoundcubePlugins plugin archive
	- Install the plugin

### UPDATE

**1. Get the plugin from Plugin Store**

http://i-mscp.net/filebase/index.php/Filebase/

**2. Backup your current plugin config**

	- plugins/RoundcubePlugins/config.php
	- plugins/RoundcubePlugins/config-templates/logon_page/logon_page.html
	
**3. Plugin upload and update**

	- Login into the panel as admin and go to the plugin management interface
	- Upload the RoundcubePlugins plugin archive
	- Update the plugin list

### CONFIGURATION

For the different configuration options please check the plugin config file.

	# plugins/RoundcubePlugins/config.php
	
After you made your config changes, don't forget to update the plugin list.

	- Login into the panel as admin and go to the plugin management interface
	- Update the plugin list
	
### AUTHORS

 - Rene Schuster <mail@reneschuster.de>
 - Sascha Bay <info@space2place.de>

**Thank you for using this plugin.**
