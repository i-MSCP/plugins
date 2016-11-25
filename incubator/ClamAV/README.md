#  i-MSCP ClamAV plugin v1.2.0

Plugin that allows to use ClamAV with i-MSCP.

## Requirements

* i-MSCP Serie 1.3.x
* i-MSCP Postfix server implementation

### Debian / Ubuntu packages

* clamav
* clamav-base
* clamav-daemon
* clamav-freshclam
* clamav-milter
* curl
* gnupg
* rsync

You can install these packages by running the following commands:

```bash
# apt-get update
# apt-get install clamav clamav-base clamav-daemon clamav-freshclam clamav-milter curl gnupg rsync
# service clamav-freshclam stop
# freshclam
# service clamav-freshclam start
# service clamav-daemon start
```

## Installation

1. Be sure that all requirements as stated in the requirements section are met
2. Upload the plugin through the plugin management interface
3. Activate the plugin through the plugin management interface

## Update

1. Be sure that all requirements as stated in the requirements section are met
2. Backup your plugin configuration file if needed
3. Upload the plugin through the plugin management interface
4. Restore your plugin configuration file if needed (compare it with the new version first)
5. Update the plugin list through the plugin management interface

## Configuration

See [Configuration file](../ClamAV/config.php)

**Note:** When changing a configuration parameter in the plugin configuration file, do not forget to trigger plugin
change by updating the plugin list through the plugin management interface.

## 3rd party ClamAV Unofficial Signatures - [clamav-unofficial-sigs](https://github.com/extremeshok/clamav-unofficial-sigs)

The [clamav-unofficial-sigs](https://github.com/extremeshok/clamav-unofficial-sigs) script provides a simple way to download, test, and update third-party signature databases 
provided by [Sanesecurity](http://sanesecurity.com), [Foxhole](http://sanesecurity.com/foxhole-databases), OITC, [Scamnailer](http://www.scamnailer.info), [BOFHLAND](http://clamav.bofhland.org), [CRDF](https://threatcenter.crdf.fr), Porcupine, [SecuriteInfo](https://www.securiteinfo.com), [MalwarePatrol](https://www.malwarepatrol.net),  [Yara-Rules Project](https://github.com/Yara-Rules/rules), etc.

**Note:** If you already have this or another script that downloads 3rd party ClamAV signatures, then please uninstall or remove it before using this plugin with [clamav-unofficial-sigs](https://github.com/extremeshok/clamav-unofficial-sigs) activated.

## Eicar-Test-Signature

Send a mail with the following content to one of your i-MSCP mail accounts:

```
X5O!P%@AP[4\PZX54(P^)7CC)7}$EICAR-STANDARD-ANTIVIRUS-TEST-FILE!$H+H*
```

Be aware that the EICAR signature above must be added on a line, without whitespace nor line break.

### Expected result in /var/log/mail.log

```bash
root@precise:/etc/postfix# tail -fn0 /var/log/mail.log
Feb 20 03:26:16 precise postfix/smtpd[19062]: connect from unknown[192.168.5.100]
Feb 20 03:26:16 precise postfix/smtpd[19062]: Anonymous TLS connection established from unknown[192.168.5.100]: TLSv1.2 with cipher ECDHE-RSA-AES128-GCM-SHA256 (128/128 bits)
Feb 20 03:26:16 precise postfix/smtpd[19062]: 0A618260345: client=unknown[192.168.5.100], sasl_method=CRAM-MD5, sasl_username=nuxwin@domain.tld
Feb 20 03:26:16 precise postfix/cleanup[19158]: 0A618260345: message-id=<54E6A8BF.3080504@domain.tld>
Feb 20 03:26:16 precise clamav-milter[18878]: Message from <nuxwin@domain.tld> to <nuxwin@domain.tld> infected by Eicar-Test-Signature
Feb 20 03:26:16 precise postfix/cleanup[19158]: 0A618260345: milter-reject: END-OF-MESSAGE from unknown[192.168.5.100]: 5.7.1 Blocked by ClamAV - FOUND VIRUS: Eicar-Test-Signature; from=<nuxwin@domain.tld> to=<nuxwin@domain.tld> proto=ESMTP helo=<[192.168.5.100]>

```

## License

```
i-MSCP ClamAV plugin
Copyright (C) 2014-2016 Laurent Declercq <l.declercq@nuxwin.com>
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

## Authors

* Laurent Declercq <l.declercq@nuxwin.com> 
* Rene Schuster <mail@reneschuster.de>
* Sascha Bay <info@space2place.de>
