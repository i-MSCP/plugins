# Version 2.0.0

## DKIM ADSP (Author Domain Signing Practices) extension

First, if you don't known what is DKIM ADSP, you should have a look at
[Author Domain Signing Practices](https://en.wikipedia.org/wiki/Author_Domain_Signing_Practices).

For each domain and subdomain a DKIM ADSP DNS resource record is added. The
default signing practice is set to `discardable`, meaning that any mail that is
not signed should be discarded. You can change the signing practice through the
plugin configuration file.

Note that currently the signing practice is set globally. This will change in
later version.

## DKIM keys display

DKIM Keys are not longer displayed through textarea html tags. They are now
displayed through tooltips which are shown when the mouse pass over a button
that allows the customer to easily copy the DKIM keys into the clipboard.

## DKIM keys renewal

Resellers can now trigger renewal of their customers's DKIM keys through their
OpenDKIM interface.

## DKIM support for subdomains

In previous version, subdomains were not supported. At least, messages sent
from them were not signed. Starting with this version messages sent from a
subdomain are signed using the DKIM key of its parent domain. This is perfectly
valid and supported

For subdomains, only the ADSP DNS resource record must be added.

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

