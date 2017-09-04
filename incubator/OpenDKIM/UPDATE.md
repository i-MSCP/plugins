# Version 2.0.0

This new version comes with major changes and improvements such as support for
DKIM ADSP extension, signing of mail sent through subdomains...

## DKIM ADSP ()Author Domain Signing Practices) extension

First, if you don't known what is DKIM ADSP, you should have a look at
[Author Domain Signing Practices](https://en.wikipedia.org/wiki/Author_Domain_Signing_Practices).

For each domain and subdomain a DKIM ADSP DNS resource record is generated. The
default signing practice is set to `discardable`, meaning that any mail that is
not signed should be discarded. You can change the signing practice through the
plugin configuration file.

## Plugin working level

Starting with this new version, it is now possible to choose the plugin working
level through the `plugin_working_level` plugin configuration parameter. There
are actually two working levels which are `admin` and `reseller`.

When it works at the `admin` level, the plugin activates OpenDKIM for all
customers automatically. In this working level, resellers can only trigger DKIM
keys renewal through their own management interface. This is the new default
behavior.

When it works at `reseller` level, the plugin doesn't activate OpenDKIM
automatically for customers. Resellers must enable the OpenDKIM feature
manually for all of their customers. This is the historical (pre-version 2.0.0)
behavior.

## Signing of messages sent through subdomains

Messages sent through subdomains are now also signed. However, to prevent
customers from having to add a DKIM (signature) DNS resource record for each
subdomain, they are signed using the signature of the parent domain. This is
perfectly valid and supported.

For subdomains, only the ADSP DNS resource record must be added.
