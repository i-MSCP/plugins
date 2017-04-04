# i-MSCP RoundcubePlugins plugin v2.0.1

Provides many plugins for the Roundcube Webmail.

## Requirements

- i-MSCP 1.3.x Serie (version >= 1.3.1) or 1.4.x Serie
- Dovecot (Only needed if you want use the managesieve Roundcube plugin)
- Roundcube >= 1.1.0

### Debian / Ubuntu packages

The following package are required only if you want install the managesieve plugin for the Roundcube Webmail.

* dovecot-sieve
* dovecot-managesieved

You can install these packages by running the following commands:

```
# apt-get update
# apt-get install --no-install-recommends dovecot-sieve dovecot-managesieved
```

Prior executing the command above, be sure that you're using the i-MSCP Dovecot server implementation.

## Installation

1. Be sure that all requirements as stated in the requirements section are met
2. Upload the plugin through the plugin management interface
3. Install the plugin through the plugin management interface

Note that plugin installation can take up several minutes.

## Update

1. Be sure that all requirements as stated in the requirements section are met
2. Backup your plugin configuration file if needed
3. Upload the plugin through the plugin management interface
4. Restore your plugin configuration file if needed (compare it with the new version first)
5. Update the plugin list through the plugin management interface

Note that plugin update can take up several minutes.

## Plugin configuration

See [Configuration file](../RoundcubePlugins/config.php)

When changing a configuration parameter in the plugin configuration file, do not forget to trigger plugin change by
triggering a plugin list update from the plugin management interface.

## Roundcube Webmail plugin list

### additional_message_headers plugin

This plugin adds an header identifying the originating IP for the mails sent via the Roundcube Webmail.

### archive plugin

This plugin adds a button to the Roundcube Webmail toolbar which allows moving messages to an (user selectable) archive
folder. The button will appears after archive folder configuration.

### calendar plugin

This plugin provides calendar feature for Roundcube Webmail.

### contextmenu plugin

This plugin adds contextual menu to the message list, folder list and address book. It allows to mark mails as
read/unread, delete, reply or forward them.

### emoticons plugin

This plugin parses and display smileys and other emoticons found in body of mails.

### logon_page plugin

This plugin allows to display additional information (HTML code block) at logon page.

#### Configuration

Put your content into the file config-templates/logon_page/logon_page.html It will be parsed by Roundcube Webmail
templates engine, so you can use all template features (tags).

### managesieve plugin

This plugin adds support for managesieve protocol and allows the users to manage their sieve mail rules.

Note that this plugins is disabled by default. You can enable it by updating the plugin configuration file.

A default Spam sieve rule will be created after the user opened the Filters configuration in Roundcube.

### newmail_notifier plugin

This plugin allows notifying user for new mails, by focusing browser window and changing favicon, playing a sound and
displaying a desktop notification.

### odfviewer plugin

This plugin adds support for inline ODF file viewer.

### password plugin

This plugin allows to update email account password from the Roundcube Webmail.

### pdfviewer plugin

This plugin adds support for inline PDF file viewer.

## rcguard plugin

This plugin logs the failed login attempts and requires users to go through a reCAPTCHA verification process when the
maximum number of login attempts has been reached. In other words, this plugin help mitigate dictionary attacks.

Failed attempts are logged by IP and stored in database. IPs are released after a certain amount of time.
### tasklist plugin

This plugin add support for task management.

### vcard_attachments plugin

Detect VCard attachments and show a button to add them to address book

### zipdownload plugin

This plugin adds an option to download all attachments of a message in one zip file.

## License

```
i-MSCP - internet Multi Server Control Panel
Copyright (C) 2017 Laurent Declercq <l.declercq@nuxwin.com>
Copyright (C) 2013-2016 Rene Schuster <mail@reneschuster.de>
Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

See [LICENSE](LICENSE)
