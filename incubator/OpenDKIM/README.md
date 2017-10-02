# i-MSCP OpenDKIM plugin v2.0.1

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

## Plugin working level

It is possible to choose the plugin working level through the
`plugin_working_level` plugin configuration parameter. There are actually two
working levels which are `admin` and `reseller`.

When it works at the `admin` level, the plugin activates OpenDKIM for all
customers automatically. In this working level, resellers can only trigger
renewal of DKIM keys through their own management interface.

When it works at `reseller` level, the plugin doesn't activate OpenDKIM
automatically for customers. Resellers must enable the OpenDKIM feature
manually for all of their customers.

## Usage of an external DNS server

If you make use of an external DNS server (not the one managed by i-MSCP), you
must not forget to add the DKIM and ADSP DNS resource records in the zone of
your domain. 

Each domain has one DKIM and one ADSP DNS resource records and each subdomain has
one ADSP resource record.

## Testing

### Internal DKIM test

You can check on the command line if OpenDKIM is working for your domain by
running the following command:

```
opendkim-testkey -d example.com -s mail -vvv
```

The result should look similar like this one:

```
root@jessie32:/etc/opendkim# opendkim-testkey -d weird.test.bbox.nuxwin.com -s mail -vvv
opendkim-testkey: using default configfile /etc/opendkim.conf
opendkim-testkey: checking key 'mail._domainkey.weird.test.bbox.nuxwin.com'
opendkim-testkey: key not secure
opendkim-testkey: key OK
```

Note that the `key not secure` message doesn't indicate an error. It is the
expected consequence of not using DNSSSEC.

You can also query your DNS server to check the TXT record for your domain:

```
dig -t txt mail._domainkey.example.com
```

### External DKIM test

Go to [dkimvalidator.com](http://dkimvalidator.com) and send a mail from the
domain for which you activated OpenDKIM to the random mail address. Once you
have sent the mail, wait few seconds and then, click on the `View Results`
button. You should get a result similar to:

#### Original Message

```
Received: from jessie32.bbox.nuxwin.com (xxx-xxx-xx-xx.abo.bbox.fr [xxx.xxx.xx.xx])
	by relay-4.us-west-2.relay-prod (Postfix) with ESMTPS id 9B57F160208
	for <4GC3jd0ag5t798@dkimvalidator.com>; Sat,  2 Sep 2017 18:29:48 +0000 (UTC)
Received: from panel.bbox.nuxwin.com (jessie32.bbox.nuxwin.com.local [127.0.0.1])
	(Authenticated sender: testing@sub1.weird.test.bbox.nuxwin.com)
	by jessie32.bbox.nuxwin.com (Postfix) with ESMTPA id F3E645FC6A
	for <4GC3jd0ag5t798@dkimvalidator.com>; Sat,  2 Sep 2017 20:30:21 +0200 (CEST)
DKIM-Filter: OpenDKIM Filter v2.9.2 jessie32.bbox.nuxwin.com F3E645FC6A
DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/simple;
	d=weird.test.bbox.nuxwin.com; s=mail; t=1504377022;
	i=@sub1.weird.test.bbox.nuxwin.com;
	bh=g3zLYH4xKxcPrHOD18z9YfpQcnk/GaJedfustWU5uGs=;
	h=Date:From:To:Subject:From;
	b=HdAyJ/C0tBH5UkzZSGXo2ESZ6+8tCr1O/LC3REVuyRg1TSB/bYGDAv/H05+nJSisD
	 fExsy/Irnjjz5bVNSUq0nB8mPoHaTMibh9mWAC/Q23WDsu9j9vprH5TGw0k91UUuur
	 XQm2anEaugJtvEpCSdOf3CMHlxUF9M/oMti+Bm0N/aoqsvu1vRZHazQH4PUMd+Thyq
	 PtnEx4ZPQaU/f1HOdZTi7c4KjwWHoLDdQ1mNAwknUMjm5hsw2MGIIW0ecumNqzzKZH
	 vIFhX75q2Hw03rByI5paaUrf6bAEozOmQghDTzz+07pn/aYhoK+jNYMEvev/F8pRqz
	 596UbuEZYMC1w==
MIME-Version: 1.0
Content-Type: text/plain; charset=US-ASCII;
 format=flowed
Content-Transfer-Encoding: 7bit
Date: Sat, 02 Sep 2017 20:30:21 +0200
From: testing@sub1.weird.test.bbox.nuxwin.com
To: 4GC3jd0ag5t798@dkimvalidator.com
Subject: test
Message-ID: <f19501d4fd766b3da1db7b8223a05b71@sub1.weird.test.bbox.nuxwin.com>
X-Sender: testing@sub1.weird.test.bbox.nuxwin.com
User-Agent: Roundcube Webmail/1.2.5

test
```

#### DKIM Information

```
DKIM Signature

Message contains this DKIM Signature:
DKIM-Filter: OpenDKIM Filter v2.9.2 jessie32.bbox.nuxwin.com F3E645FC6A
DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/simple;
	d=weird.test.bbox.nuxwin.com; s=mail; t=1504377022;
	i=@sub1.weird.test.bbox.nuxwin.com;
	bh=g3zLYH4xKxcPrHOD18z9YfpQcnk/GaJedfustWU5uGs=;
	h=Date:From:To:Subject:From;
	b=HdAyJ/C0tBH5UkzZSGXo2ESZ6+8tCr1O/LC3REVuyRg1TSB/bYGDAv/H05+nJSisD
	 fExsy/Irnjjz5bVNSUq0nB8mPoHaTMibh9mWAC/Q23WDsu9j9vprH5TGw0k91UUuur
	 XQm2anEaugJtvEpCSdOf3CMHlxUF9M/oMti+Bm0N/aoqsvu1vRZHazQH4PUMd+Thyq
	 PtnEx4ZPQaU/f1HOdZTi7c4KjwWHoLDdQ1mNAwknUMjm5hsw2MGIIW0ecumNqzzKZH
	 vIFhX75q2Hw03rByI5paaUrf6bAEozOmQghDTzz+07pn/aYhoK+jNYMEvev/F8pRqz
	 596UbuEZYMC1w==

Signature Information:
v= Version:         1
a= Algorithm:       rsa-sha256
c= Method:          relaxed/simple
d= Domain:          weird.test.bbox.nuxwin.com
s= Selector:        mail
q= Protocol:        
bh=                 g3zLYH4xKxcPrHOD18z9YfpQcnk/GaJedfustWU5uGs=
h= Signed Headers:  Date:From:To:Subject:From
b= Data:            HdAyJ/C0tBH5UkzZSGXo2ESZ6+8tCr1O/LC3REVuyRg1TSB/bYGDAv/H05+nJSisD
	 fExsy/Irnjjz5bVNSUq0nB8mPoHaTMibh9mWAC/Q23WDsu9j9vprH5TGw0k91UUuur
	 XQm2anEaugJtvEpCSdOf3CMHlxUF9M/oMti+Bm0N/aoqsvu1vRZHazQH4PUMd+Thyq
	 PtnEx4ZPQaU/f1HOdZTi7c4KjwWHoLDdQ1mNAwknUMjm5hsw2MGIIW0ecumNqzzKZH
	 vIFhX75q2Hw03rByI5paaUrf6bAEozOmQghDTzz+07pn/aYhoK+jNYMEvev/F8pRqz
	 596UbuEZYMC1w==
Public Key DNS Lookup

Building DNS Query for mail._domainkey.weird.test.bbox.nuxwin.com
Retrieved this publickey from DNS: v=DKIM1; h=sha256; k=rsa; s=email; p=MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAsGfEnQP49L7DrUvR8/cPOciHvATQkxXTgBm4qUcNFFDLnT6s45xsmU068RTED/QGJWaeL2eQcd7c5p7dlUdqVugYSfB+aDjOJuvPIr3P/jiISt6HERoBafu7pkc2mj92S70Xq3Jyx6lgIhMe63UGKyeyuBJHB7Nm3KHHiZFqH7AWtQwgBpMqa7LKPj4OCIELZ+G8SO8OMAkytLndDf40lABXXHsyjFSTaOPb27BStTLBmZT58AwPtSHTZ7+8hz+reHZDUXMos96SiwcvxOepDMSBMMdKpAI7iu+v86F+ewaMRllAogVIAFZb68DgjAUvk6fwDE4mwGyGk1y7QdKVEwIDAQAB
Validating Signature

result = pass
Details: 
```

#### SPF Information

```
Using this information that I obtained from the headers

Helo Address = jessie32.bbox.nuxwin.com
From Address = testing@sub1.weird.test.bbox.nuxwin.com
From IP      = xxx.xxx.xx.xx
SPF Record Lookup

Looking up TXT SPF record for sub1.weird.test.bbox.nuxwin.com
Found the following namesevers for sub1.weird.test.bbox.nuxwin.com: 
Retrieved this SPF Record: zone updated 20170902 (TTL = 43897)
Using local nameserver for SPF resolution.  This will probably be cached!
Result: pass (Mechanism 'include:weird.test.bbox.nuxwin.com' matched)

Result code: pass
Local Explanation: sub1.weird.test.bbox.nuxwin.com: Sender is authorized to use 'testing@sub1.weird.test.bbox.nuxwin.com' in 'mfrom' identity (mechanism 'include:weird.test.bbox.nuxwin.com' matched)
spf_header = Received-SPF: pass (sub1.weird.test.bbox.nuxwin.com: Sender is authorized to use 'testing@sub1.weird.test.bbox.nuxwin.com' in 'mfrom' identity (mechanism 'include:weird.test.bbox.nuxwin.com' matched)) receiver=dkimvalidator.com; identity=mailfrom; envelope-from="testing@sub1.weird.test.bbox.nuxwin.com"; helo=jessie32.bbox.nuxwin.com; client-ip=xxx.xxx.xx.xx
```

Regarding the SpamAssassin information at bottom, you can ignore them as the
SpamAssassin installation used is not able to validate DKIM signatures when
DKIM ADSP extension is involved. 

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
