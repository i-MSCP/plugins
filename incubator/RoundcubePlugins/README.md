# i-MSCP RoundcubePlugins plugin v1.2.3

Plugin allows to use Roundcube Plugins with i-MSCP.

## Requirements

* i-MSCP version >= 1.2.3
* Dovecot ( Only needed if you want use the managesieve Roundcube plugin )
* Roundcube >= 1.1.0

### Debian / Ubuntu packages

The following package are required only if you want install the managesieve Roundcube plugin.

* dovecot-sieve
* dovecot-managesieved

You can install these packages by running the following commands:

```
# apt-get update
# apt-get install dovecot-sieve dovecot-managesieved
```

**Note:** Prior running the command above, be sure that you're using the i-MSCP Dovecot server implementation.

## Installation

1. Be sure that all requirements as stated in the requirements section are meets
2. Upload the plugin through the plugin management interface
3. Install the plugin through the plugin management interface

## Update

1. Be sure that all requirements as stated in the requirements section are meets
2. Backup your plugin configuration file if needed
3. Upload the plugin through the plugin management interface
4. Restore your plugin configuration file if needed ( compare it with the new version first )
5. Update the plugin list through the plugin management interface

## Roundcube plugins list

### additional_message_headers

This plugin adds an header identifying the originating IP for the mails sent via the webmail.

### Archive

This plugin adds a new button to the Roundcube toolbar to move messages to an ( user selectable ) archive folder. The
button will only appear after the user configured the archive folder.

### Calendar

This plugin provide cCalendar feature for roundcube

### ContextMenu

This plugin adds a context menus to the message list, folder list and address book. It allow to mark mails as
read/unread, delete, reply and forward mails.

### dkimstatus

This plugin displays the DKIM Signature status of each mail in Roundcube.

### emoticons

This plugin allows to inserts nice smileys and other emoticons when the appropriate text representations e.g. :-) are
discovered in the mail text.

### LogonPage

This plugin allows to display additional information ( HTML code block ) at logon page.

#### Configuration

Put your content into the file config-templates/logon_page/logon_page.html It will be parsed by Roundcube templates
engine, so you can use all template features ( tags ).

### ManageSieve

This plugin add support for  managesieve protocol and allows the users to manage their sieve mail rules.

**Note:** A default Spam sieve rule will be created after the user opened the Filters configuration in Roundcube.

### NewMailNotifier

This plugin allow to notify on new mails by focusing browser window and changing favicon, playing a sound and displaying
desktop notification ( using webkitNotifications feature ).

### OdfViewer

This plugin adds support for inline ODF viewer.

### Password

This plugin adds the option to change the password in Roundcube.

### PdfViewer

This plugin adds support for inline PDF viewer.

### Pop3Fetcher

This plugin allows to add pop3 accounts and automatically fetch emails from them.

## Rcguard

This plugin logs failed login attempts and requires users to go through
a reCAPTCHA verification process when the number of failed attempts go
too high. This provides protection against automated attacks.

Failed attempts are logged by IP and stored within MySQL. IPs are also
released after a certain amount of time.

### TaskList

This plugin add support for task management.

### VCard attachments

Detect VCard attachments and show a button to add them to address book

### ZipDownload

This plugin adds an option to download all attachments of a message in one zip file.

## Configuration

See [Configuration file](../RoundcubePlugins/config.php)

**Note:** When changing a configuration parameter in the plugin configuration file, do not forget to trigger plugin
change by updating the plugin list through the plugin interface.

## License

```
i-MSCP - internet Multi Server Control Panel
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

### Authors

* Rene Schuster <mail@reneschuster.de>
* Sascha Bay <info@space2place.de>
