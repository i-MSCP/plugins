# i-MSCP OpenDKIM plugin v1.0.2

Plugin which provides an OpenDKIM implementation for i-MSCP.

## Requirements

* i-MSCP version >= 1.2.3

### Debian / Ubuntu packages

* opendkim
* opendkim-tools

You can install these packages by running the following command:

```
# aptitude update && aptitude install opendkim opendkim-tools
```

**Note:** For Debian Squeeze, Ubuntu Lucid and Precise, you must install these packages from the backports repositories.
Refer to your distribution documentation for further details.

## Installation

1. Upload the plugin through the plugin management interface
2. Install the plugin through the plugin management interface

## Update

1. Be sure that all requirements as stated in the requirements section are meets
2. Backup your plugin configuration file if needed
3. Upload the plugin through the plugin management interface
4. Restore your plugin configuration file if needed ( compare it with the new version first )
5. Update the plugin list through the plugin management interface

## Configuration

See [Configuration file](../OpenDKIM/config.php)

**Note:** When changing a configuration parameter in the plugin configuration file, do not forget to trigger plugin
change by updating the plugin list through the plugin management interface.

## Testing

### Internal DKIM test

You can check on the command line if OpenDKIM is working for your domain by running the following command:

```
# opendkim-testkey -d example.com -s mail -vvv
```

The result should look similar like this one:

```
opendkim-testkey: checking key 'mail._domainkey.example.com'
opendkim-testkey: key not secure
opendkim-testkey: key OK
```

**note:** The 'key not secure' does not indicate an error. It is an expected consequence of not using DNSSSEC.

You can also query your DNS server to check the TXT DKIM record for your domain:

```
# dig -t txt mail._domainkey.example.com
```

### External DKIM test

Open the link below and send a mail from the domain for which you activated OpenDKIM to the random mail address shown on
that page.

```
http://www.brandonchecketts.com/emailtest.php
```

Once you have sent the mail, click on the 'View Results' button and verify the DKIM information section. You should get
a result similar to this:

```
DKIM Information:

DKIM Signature

Message contains this DKIM Signature:
DKIM-Signature: v=1; a=rsa-sha256; c=simple/simple; d=example.com;
	s=mail; t=1385558914;
	bh=fdkeB/A0FkbVP2k4J4pNPoeWH6vqBm9+b0C3OY87Cw8=;
	h=Date:From:To:Subject:From;
	b=ZtWi/eDZtQ0RDv60FCDf4c+G9gqhFH3r6RPCw9vr400auTH0PnkOwt2BuLNpv4Uh4
	 wjBHhFnIqt+t/c9/DLCC8envKmnzco8BATgXl5I5HHLxDcGMFYlwHDgOLXcCKXOXA5
	 15oFPlimBrwZXnq3XOJCwopZmUmZZhUyYT8pZO9k=

Signature Information:
v= Version:         1
a= Algorithm:       rsa-sha256
c= Method:          simple/simple
d= Domain:          example.com
s= Selector:        mail
q= Protocol:        
bh=                 fdkeB/A0FkbVP2k4J4pNPoeWH6vqBm9+b0C3OY87Cw8=
h= Signed Headers:  Date:From:To:Subject:From
b= Data:            ZtWi/eDZtQ0RDv60FCDf4c+G9gqhFH3r6RPCw9vr400auTH0PnkOwt2BuLNpv4Uh4
	 wjBHhFnIqt+t/c9/DLCC8envKmnzco8BATgXl5I5HHLxDcGMFYlwHDgOLXcCKXOXA5
	 15oFPlimBrwZXnq3XOJCwopZmUmZZhUyYT8pZO9k=
Public Key DNS Lookup

Building DNS Query for mail._domainkey.example.com
Retrieved this publickey from DNS: v=DKIM1; k=rsa; p=MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDN+HbTA3/7KoENKhMr6qRO0cFeaDX1NSD5Xe7zkGhkvOnajIrhycu0XyxzHLTTSbFLq9juJmUbPmP9OVj44o0p/NqoLQ9oWjfkcM+7nq+S4QYGoM7h+SMcxjFm05mo0LdessYi/Sw5z6x87nMkLD/wQViDvctss4srrPTr/hqD+wIDAQAB
Validating Signature

result = pass
Details:  
```

## License

```
i-MSCP  OpenDKIM plugin
Copyright (C) 2013-2016 Laurent Declercq <l.declercq@nuxwin.com>
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
