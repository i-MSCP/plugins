# i-MSCP SpamAssassin plugin v1.0.7

Plugin which allows to use SpamAssassin with i-MSCP.

## Requirements

* i-MSCP version >= 1.2.3
* SpamAssassin 3.3.x or 3.4.x
* Roundcube >= 1.1.0 (Optional)

### Debian / Ubuntu packages 

* spamassassin
* spamass-milter
* libmail-dkim-perl
* libnet-ident-perl
* libencode-detect-perl
* pyzor
* razor

You can install these packages by running the following commands:

```
# apt-get update
# apt-get install spamassassin spamass-milter libmail-dkim-perl libnet-ident-perl libencode-detect-perl pyzor razor
```

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

## Configuration

See [Configuration file](../SpamAssassin/config.php)

**Note:** When changing a configuration parameter in the plugin configuration file, do not forget to trigger plugin
change by updating the plugin list through the plugin management interface.

## 3rd party SpamAssassin rules

### Heinlein Support SpamAssassin rules

Latest SpamAssassin rules directly from the Heinlein Hosting live systems.
Heinlein Support is a German ISP company and specialized on mail servers. 
The founder and owner [Peer Heinlein](https://de.wikipedia.org/wiki/Peer_Heinlein "Peer Heinlein") has written several [books](https://portal.dnb.de/opac.htm?method=simpleSearch&query=123703522) about Dovecot and Postfix.
For further details check the [blog](https://www.heinlein-support.de/blog/news/aktuelle-spamassassin-regeln-von-heinlein-support/ "Aktuelle SpamAssassin-Regeln von Heinlein Support") entry.

## 3rd party SpamAssassin plugins

### DecodeShortURLs

The [DecodeShortURLs](https://github.com/smfreegard/DecodeShortURLs "DecodeShortURLs") plugin looks for URLs shortened
by a list of URL shortening services and upon finding a matching URL will connect using to the shortening service and do
an HTTP HEAD lookup and retrieve the location header which points to the actual shortened URL, it then adds this URL to
the list of URIs extracted by SpamAssassin which can then be accessed by other plugins, such as URIDNSBL.

### iXhash2

[iXhash2](http://mailfud.org/iXhash2/ "iXhash2") is an unofficial improved version of 
[iXhash](http://www.ixhash.net/ "iXhash") plugin for SpamAssassin, adding async DNS lookups for performance and removing
unneeded features. It's fully compatible with the iXhash 1.5.5 implementation.

## Included Roundcube Plugins

### markasjunk2

If enabled in the config.php file, the Roundcube plugin markasjunk2 adds a new button to the mailbox toolbar to mark the
selected messages as 'Junk'/'Not Junk' and will also learn the bayesian database. It will also detach original messages
from spam reports if the message is not junk.

### sauserprefs

If enabled in the config.php file, the Roundcube plugin sauserprefs adds a 'Spam' tab to the 'Settings' to allow the
users to change their SpamAssassin preferences which are stored in the imscp_spamassassin database. The SpamAssassin
preferences shown in Roundcube will vary depending the changes you make in the config.php file.

Roundcube user config: Settings -> Spam

#### Move Spam into Junk folder

If you want to move Spam massages into the users Junk folder, you will need the Roundcube Plugin managesieve, which is
included in the I-MSCP Plugin RoundcubePlugins.

#### SpamAssassin user preferences

The default SpamAssassin user preferences are stored in the table **userpref** of the **imscp_spamassassin** database.

#### Global SpamAssassin preferences

These are the $GLOBAL default values which will be imported during plugin installation.

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
		<td>1</td>
	</tr>
	<tr>
		<td>$GLOBAL</td>
		<td>use_bayes_rules</td>
		<td>1</td>
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
		<td>use_auto_whitelist</td>
		<td>0</td>
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
	<tr>
		<td>$GLOBAL</td>
		<td>score USER_IN_BLACKLIST</td>
		<td>10</td>
	</tr>
	<tr>
		<td>$GLOBAL</td>
		<td>score USER_IN_WHITELIST</td>
		<td>-6</td>
	</tr>
</table>


The $GLOBAL values will be used as long as the user has no specific entries in the table. If you want to change some
$GLOBAL options, please do that directly in the database.

#### Per-Domain SpamAssassin preferences

You could also specify domain specific entries, which will be used as default only for that domain, until the user has
no individual entry in the userpref table. Here are some examples for the domain **example.com**:

<table>
	<tr>
		<th>username</th>
		<th>preference</th>
		<th>value</th>
	</tr>
	<tr>
		<td>%example.com</td>
		<td>required_score</td>
		<td>8</td>
	</tr>
	<tr>
		<td>%example.com</td>
		<td>rewrite_header Subject</td>
		<td>[ SPAM ]</td>
	</tr>
	<tr>
		<td>%example.com</td>
		<td>report_safe</td>
		<td>0</td>
	</tr>
</table>

#### Per-User SpamAssassin preferences

If you enabled the Roundcube Plugin **sauserprefs** in the config.php file, then the user can change his SpamAssassin
preferences under Roundcube -> Settings -> Spam.

The user preferences will also be stored in the **userpref** table with the mail address as username.

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

Be aware that the **GTUBE** signature above must be added on a line, without whitespace nor line break.

## License

Copyright (C) 2015-2016 Laurent Declercq <l.declercq@nuxwin.com>
Copyright (C) 2013-2016 Rene Schuster <mail@reneschuster.de>
Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

See [LICENSE](LICENSE)

## Authors

* Laurent Declercq <l.declercq@nuxwin.com>
* Rene Schuster <mail@reneschuster.de>
* Sascha Bay <info@space2place.de>
