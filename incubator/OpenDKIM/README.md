# i-MSCP OpenDKIM plugin v2.0.0

Provides DomainKeys Identified Mail (DKIM) service through MILTER.

## Introduction

DKIM provides a way for senders to confirm their identity when sending email by
adding a cryptographic signature to the headers of the message.

## Requirements

- i-MSCP Serie ≥ 1.4.x

## Installation

1. Upload the plugin through the plugin management interface
2. Install the plugin through the plugin management interface

## Update

1. Be sure that all requirements as stated in the requirements section are met
2. Backup your plugin configuration file if needed
3. Upload the plugin through the plugin management interface

### Restore you plugin configuration file if needed

1. Restore your plugin configuration file (compare it with the new version
   first)
2. Update the plugin list through the plugin management interface

## Configuration

See [Configuration file](config.php)

When changing a configuration parameter in the plugin configuration file, don't
forget to trigger a plugin list update, else you're changes will not be token
into account.

## Testing

### Internal DKIM test

You can check on the command line if OpenDKIM is working for your domain by
running the following command:

```
opendkim-testkey -d example.com -s mail -vvv
```

The result should look similar like this one:

```
opendkim-testkey: checking key 'mail._domainkey.example.com'
opendkim-testkey: key not secure
opendkim-testkey: key OK
```

Note that the 'key not secure' does not indicate an error. It is an expected
consequence of not using DNSSSEC.

You can also query your DNS server to check the TXT record for your domain:

```
dig -t txt mail._domainkey.example.com
```

### External DKIM test

Open the link below and send a mail from the domain for which you activated
OpenDKIM to the random mail address shown on that page.

```
http://dkimvalidator.com
```

Once you have sent the mail, click on the 'View Results' button and verify the
DKIM information section.

## Plugin deactivation

If you deactivate the plugin through the plugin management interface, all
current key files will be deleted. This necessarily means that all keys will be
renewed on plugin re-activation. This is by design and that is the expected
behavior. Not doing this could lead to orphaned keys if a domain is deleted
while the plugin is deactivated.

Bear in mind that unlike a common idea, plugins do not need to be deactivated
before upgrading i-MSCP. Generally speaking, a plugin must be pre-deactivated
only when that is clearly stated in the i-MSCP errata file.

## License

    i-MSCP  OpenDKIM plugin
    Copyright (C) 2013-2017 Laurent Declercq <l.declercq@nuxwin.com>
    Copyright (C) 2013-2016 Rene Schuster <mail@reneschuster.de>
    Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>
    
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; version 2 of the License
    
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
