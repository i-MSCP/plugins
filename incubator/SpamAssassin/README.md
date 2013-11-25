## i-MSCP SpamAssassin plugin v0.0.1

Plugin allows to use SpamAssassin with i-MSCP.

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

	Plugin compatible with i-MSCP versions >= 1.1.0.rc4.7

### Existing milter configurations

	This plugin will not check for an existing milter configuration in the postfix main.cf file.
	If you need to add an extra milter, please ask in our forum!
	
### INSTALLATION

**1.** Install needed Debian/Ubuntu packages if not already done

**Debian Wheezy**

1.1 Installation of spamassasin packages

	# aptitude update
	# aptitude install spamassassin spamass-milter libmail-dkim-perl libnet-ident-perl libencode-detect-perl
	
1.2 Optional install pyzor and razor
 
	# aptitude install pyzor razor
	
**Debian Squeeze**

	Add the backports of Debian Squezze to your /etc/apt/sources.list: 
	deb http://backports.debian.org/debian-backports squeeze-backports main contrib non-free
	
1.1 Installation of spamassasin packages

	# aptitude update
	# aptitude -t squeeze-backports install spamassassin
	# aptitude install spamass-milter libmail-dkim-perl libnet-ident-perl libencode-detect-perl
	
1.2 Configuration of spamassasin

	# groupadd -g 5001 debian-spamd
	# useradd -u 5001 -g debian-spamd -s /bin/sh -d /var/lib/spamassassin debian-spamd
	# mkdir /var/lib/spamassassin
	# chown spamd:spamd /var/lib/spamassassin

1.3 Optional install pyzor and razor

	# aptitude install pyzor razor
	
**2.** Get the plugin from github

	# cd /usr/local/src
	# git clone git://github.com/i-MSCP/plugins.git

**3.** Create new Plugin archive

	# cd plugins
	# tar cvzf SpamAssassin.tar.gz SpamAssassin
	
**4.** Plugin upload and installation

	- Login into the panel as admin and go to the plugin management interface
	- Upload the SpamAssassin plugin archive
	- Activate the plugin

### UPDATE

**1.** Get the plugin from github

	# cd /usr/local/src
	# git clone git://github.com/i-MSCP/plugins.git

**2.** Create new Plugin archive

	# cd plugins
	# tar cvzf SpamAssassin.tar.gz SpamAssassin

**3.** Backup your current plugin config

	- plugins/SpamAssassin/config.php
	
**4.** Plugin upload and update

	- Login into the panel as admin and go to the plugin management interface
	- Upload the SpamAssassin plugin archive
	- Force plugin re-installation

### CONFIGURATION

For the different configuration options please check the plugin config file.

	# plugins/SpamAssassin/config.php

#### 3rd party SpamAssasin Plugins

**DecodeShortURLs**

The [DecodeShortURLs](https://github.com/smfreegard/DecodeShortURLs "DecodeShortURLs") plugin looks for URLs shortened by a list of URL shortening services and upon finding a matching URL will connect using to the shortening service and do an HTTP HEAD lookup and retrieve the location header which points to the actual shortened URL, it then adds this URL to the list of URIs extracted by SpamAssassin which can then be accessed by other plugins, such as URIDNSBL.

**iXhash2**

[iXhash2](http://mailfud.org/iXhash2/ "iXhash2") is an unofficial improved version of [iXhash](http://www.ixhash.net/ "iXhash") plugin for SpamAssassin, adding async DNS lookups for performance and removing unneeded features. It's fully compatible with the iXhash 1.5.5 implementation.	

#### Included Roundcube Plugins

**markasjunk2**

If enabled in the config.php file, the Roundcube plugin markasjunk2 adds a new button to the mailbox toolbar to mark the selected messages as 'Junk'/'Not Junk' and will also learn the bayesian database. 
It will also detach original messages from spam reports if the message is not junk.

**sauserprefs**

If enabled in the config.php file, the Roundcube plugin sauserprefs adds a 'Spam' tab to the 'Settings' to allow the users to change their SpamAssassin preferences which are stored in the imscp_spamassassin database.
The SpamAssassin preferences shown in Roundcube will vary depending the changes you make in the config.php file.

Roundcube user config: Settings -> Spam

#### Move Spam into Junk folder

If you want to move Spam massages into the users Junk folder, you will need the Roundcube Plugin managesieve, which is included in the I-MSCP Plugin RoundcubePlugins.

#### SpamAssassin user preferences

The default SpamAssassin user preferences are stored in the table **userpref** of the **imscp_spamassassin** database.

##### Global SpamAssassin preferences

These are the $GLOBAL default values which will be imported during plugin installation.
	
| username         | preference                         | value                  |
+ ---------------- |:----------------------------------:| ----------------------:|
| $GLOBAL          | required_score                     | 5                      |
| $GLOBAL          | rewrite_header Subject             | *****SPAM*****         |
| $GLOBAL          | report_safe                        | 1                      |
| $GLOBAL          | use_bayes                          | 1                      |
| $GLOBAL          | use_bayes_rules                    | 1                      |
| $GLOBAL          | bayes_auto_learn                   | 1                      |
| $GLOBAL          | bayes_auto_learn_threshold_nonspam | 0.1                    |
| $GLOBAL          | bayes_auto_learn_threshold_spam    | 12.0                   |
| $GLOBAL          | use_auto_whitelist                 | 0                      |
| $GLOBAL          | skip_rbl_checks                    | 1                      |
| $GLOBAL          | use_razor2                         | 0                      |
| $GLOBAL          | use_pyzor                          | 0                      |
| $GLOBAL          | use_dcc                            | 0                      |
| $GLOBAL          | score USER_IN_BLACKLIST            | 10                     |
| $GLOBAL          | score USER_IN_WHITELIST            | -6                     |

The $GLOBAL values will be used as long as the user has no specific entries in the table.
If you want to change some $GLOBAL options, please do that directly in the database.

##### Per-Domain SpamAssassin preferences

You could also specify domain specific entries, which will be used as default only for that domain,
until the user has no individual entry in the userpref table.

Here are some examples for the domain **example.com**:

| username         | preference                         | value                  |
+ ---------------- |:----------------------------------:| ----------------------:|
| %example.com     | required_score                     | 8                      |
| %example.com     | rewrite_header Subject             | [ SPAM ]               |
| %example.com     | report_safe                        | 0                      |
	

##### Per-User SpamAssassin preferences

If you enabled the Roundcube Plugin **sauserprefs** in the plugins/SpamAssassin/config.php file,
then the user can change his SpamAssassin preferences under Roundcube -> Settings -> Spam.

The user preferences will also be stored in the **userpref** table with the mail address as username.

| username         | preference                         | value                  |
+ ---------------- |:----------------------------------:| ----------------------:|
| user@example.com | required_score                     | 6                      |
| user@example.com | rewrite_header Subject             | [SPAM-_HITS_]          |


### TESTING

**GTUBE (Generic Test for Unsolicited Bulk Email)**

Send yourself a mail from another account (e.g. gmail or any other freemailer) with the following content in the message.
	
	XJS*C4JDBQADN1.NSBN3*2IDNEN*GTUBE-STANDARD-ANTI-UBE-TEST-EMAIL*C.34X
	
Note that this should be reproduced in one line, without whitespace or line breaks.
	
### AUTHORS AND CONTRIBUTORS

 - Sascha Bay <info@space2place.de> (Author)
 - Rene Schuster <mail@reneschuster.de> (Contributor)

**Thank you for using this plugin.**
