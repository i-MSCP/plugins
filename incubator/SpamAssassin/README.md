# i-MSCP SpamAssassin plugin v2.0.2

Provides SpamAssassin spam filter through MILTER.

## Introduction

Apache SpamAssassin is the #1 Open Source anti-spam platform giving system
administrators a filter to classify email and block spam (unsolicited bulk
email).

It uses a robust scoring framework and plug-ins to integrate a wide range of
advanced heuristic and statistical analysis tests on email headers and body
text including text analysis, Bayesian filtering, DNS blocklists, and
collaborative filtering databases.

Apache SpamAssassin is a project of the Apache Software Foundation (ASF).

## Requirements

- i-MSCP Serie ≥ 1.4.x
- Roundcube Webmail (OPTIONAL)

## Installation

1. Be sure that all requirements as stated in the requirements section are met
2. Upload the plugin through the plugin management interface
3. Edit the plugin configuration file according your needs
4. Install the plugin through the plugin management interface

## Update

1. Be sure that all requirements as stated in the requirements section are met
2. Backup your plugin configuration file if needed
3. Upload the plugin through the plugin management interface

### Restore you plugin configuration file if needed

1. Restore your plugin configuration file (compare it with the new version first)
2. Update the plugin list through the plugin management interface

## Configuration

See [Configuration file](config.php)

When changing a configuration parameter in the plugin configuration file, don't
forget to trigger a plugin list update, else you're changes will not be token
into account.

### SpamAssassin plugin statuses

Enabling a SpamAssassin plugin in the plugin configuration file doesn't
necessarily means that this plugin will be active. This is the case for
plugins that can be enforced in the plugin configuration file. For these
plugins, the rules are as follows:

<table>
    <tr>
        <th>Plugin status</th>
        <th>Real state</th>
    </tr>
    <tr>
        <td>Enabled only</td>
        <td>
            The plugin is enabled but not active by default. The activation
            decision is left to end-users which can activate/deactivate the
            plugin through their SpamAssassin preferences.
        </td>
    </tr>
    <tr>
        <td>Enabled and enforced</td>
        <td>
            The plugin is enabled and active by default. End-users cannot act
            on that plugin through their SpamAssassin preferences.
        </td>
    </tr>
    <tr>
        <td>Disabled</td>
        <td>
            The plugin is fully disabled. End-users cannot act on that plugin
            through their SpamAssassin preferences.
        </td>
    </tr>
</table>

For the other plugins, this depend of their nature:

- AWL, DKIM and Rule2XSBody and SPF SpamAssassin plugins are enabled or
  disabled for all users. End-users cannot act on these plugins through
  their SpamAssassin preferences.
- The TextCat SpamAssassin plugin, when enabled, make end-users able to
  change default behavior for language guessing. 


## 3rd-party SpamAssassin ruleset

By default the plugin will setup an additional channel for download of
SpamAssassin ruleset. This channel is provided by Heinlein Support, German ISP
that is specialized in mail servers.
 
Heinlein Support founder and owner [Peer Heinlein](https://de.wikipedia.org/wiki/Peer_Heinlein)
has written several [books](https://portal.dnb.de/opac.htm?method=simpleSearch&query=123703522)
about Dovecot and Postfix.

For further details you can have a look at
[Aktuelle SpamAssassin-Regeln von Heinlein Support](https://www.heinlein-support.de/blog/news/aktuelle-spamassassin-regeln-von-heinlein-support/)

## 3rd party SpamAssassin plugins

### DecodeShortURLs

The [DecodeShortURLs](https://github.com/smfreegard/DecodeShortURLs "DecodeShortURLs")
plugin looks for URLs shortened by a list of URL shortening services and upon
finding a matching URL will connect using to the shortening service and do an
HTTP HEAD lookup and retrieve the location header which points to the actual
shortened URL, it then adds this URL to the list of URIs extracted by
SpamAssassin which can then be accessed by other plugins, such as URIDNSBL.

### iXhash2

[iXhash2](http://mailfud.org/iXhash2/ "iXhash2") is an unofficial improved
version of  [iXhash](http://www.ixhash.net/ "iXhash") plugin for SpamAssassin,
adding async DNS lookups for performance and removing unneeded features. It's
fully compatible with the iXhash 1.5.5 implementation.

## Included Roundcube Plugins

### MarkAsJunk2 plugin

If enabled in the plugin configuration file, the `MarkAsJunk2` Roundcube plugin
adds a new button to the mailbox toolbar to mark the selected messages as
'Junk'/'Not Junk' and will also learn the bayesian database. It will also
detach original messages from spam reports if the message is not junk.

### SAUserPrefs plugin

If enabled, the `SAUserPrefs` Roundcube plugin adds a 'Spam' tab to the
'Settings' page to allow the users to change their SpamAssassin preferences
which are stored in the i-MSCP SpamAssassin database.

The SpamAssassin preferences displayed in Roundcube will vary depending the
changes you make in the plugin configuration file.

Roundcube user config: Settings -> Spam

#### Move Spam into Junk folder

If you want to move Spam into the users Junk folder automatically, you will
need to use Dovecot and the `Managesieve` Roundcube plugin that is included
in the i-MSCP RoundcubePlugins plugin.

#### SpamAssassin user preferences

The default SpamAssassin user preferences are stored in the table `userpref` of
the i-MSCP SpamAssassin database.

#### Global SpamAssassin preferences

These are the `$GLOBAL` default values which will be set during plugin
installation.

<table>
    <tr>
        <th>username</th>
        <th>preference</th>
        <th>value</th>
    </tr>
    <tr>
        <td>$GLOBAL</td>
        <td>required_score</td>
        <td>5</td>
    </tr>
    <tr>
        <td>$GLOBAL</td>
        <td>rewrite_header Subject</td>
        <td>*****SPAM*****</td>
    </tr>
    <tr>
        <td>$GLOBAL</td>
        <td>report_safe</td>
        <td>1</td>
    </tr>
    <tr>
        <td>$GLOBAL</td>
        <td>use_bayes</td>
        <td>0</td>
    </tr>
    <tr>
        <td>$GLOBAL</td>
        <td>use_bayes_rules</td>
        <td>0</td>
    </tr>
    <tr>
        <td>$GLOBAL</td>
        <td>bayes_auto_learn</td>
        <td>1</td>
    </tr>
    <tr>
        <td>$GLOBAL</td>
        <td>bayes_auto_learn_threshold_nonspam</td>
        <td>0.1</td>
    </tr>
    <tr>
        <td>$GLOBAL</td>
        <td>bayes_auto_learn_threshold_spam</td>
        <td>12.0</td>
    </tr>
    <tr>
        <td>$GLOBAL</td>
        <td>skip_rbl_checks</td>
        <td>1</td>
    </tr>
    <tr>
        <td>$GLOBAL</td>
        <td>use_razor2</td>
        <td>0</td>
    </tr>
    <tr>
        <td>$GLOBAL</td>
        <td>use_pyzor</td>
        <td>0</td>
    </tr>
    <tr>
        <td>$GLOBAL</td>
        <td>use_dcc</td>
        <td>0</td>
    </tr>
</table>

You shouldn't change the `$GLOBAL` user preferences unless you know what you're
doing. Bear in mind that the plugin could override your changes.

#### Per-User SpamAssassin preferences

If you have enabled the `sauserprefs` Roundcube Plugin, end-users will be able to
change their SpamAssassin preferences under Roundcube -> Settings -> Spam.

The user preferences are stored in the `userpref` table with the mail
address as username.

<table>
    <tr>
        <th>username</th>
        <th>preference</th>
        <th>value</th>
    </tr>
    <tr>
        <td>user@example.com</td>
        <td>required_score</td>
        <td>6</td>
    </tr>
    <tr>
        <td>user@example.com</td>
        <td>rewrite_header Subject</td>
        <td>[SPAM-_HITS_]</td>
    </tr>
</table>

## Testing

### GTUBE (Generic Test for Unsolicited Bulk Email)

Send a mail with the following content to one of your i-MSCP mail accounts:

```
XJS*C4JDBQADN1.NSBN3*2IDNEN*GTUBE-STANDARD-ANTI-UBE-TEST-EMAIL*C.34X
```

Be aware that the `GTUBE` signature above must be added on a line, without
whitespace nor line break.

## License

    i-MSCP SpamAssassin plugin
    Copyright (C) 2015-2017 Laurent Declercq <l.declercq@nuxwin.com>
    Copyright (C) 2013-2016 Rene Schuster <mail@reneschuster.de>
    Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>
    
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; version 2 of the License
    
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
