# Update to version 2.0.0

## New default configuration values

This new version comes with new default configuration values which better fit
with common usage of SpamAssassin in shared hosting environments and cover the
case where other i-MSCP plugin providing identical features could be
concurrently installed.

### Default policy for SPAM rejection

In previous versions, the default policy for SPAM messages was to reject them.
Doing this in shared hosting environments is really a bad practice as there can
always have false-positive results. A message could be seen as a SPAM when it
is not.

The common rule that dictate how SPAM must be handled by ISPs is that the
decision must be left to end-users. Therefore, the new default policy is to
reject SPAM which score is higher than `15`. That is a good compromise and
greatly mitigates case of false-positive results.

### DKIM, SPF SpamAssassin plugins and RBL checks
 
Both DKIM and SPF SpamAssassin plugins and the SpamAssassin RBL checks are now
disabled by default, covering case where administrator already make usage of
other i-MSCP plugins:

- i-MSCP PolicydSPF plugin: Same as the SPF SpamAssassin plugin
- i-MSCP OpenDKIM plugin: Same as the DKIM SpamAssassin plugin
- i-MSCP Postscreen  plugin: Same as the RBL checks provided by SpamAssassin
- i-MSCP PolicydWeight plugin: Same as the RBL checks provided by SpamAssassin

Excepted the i-MSCP Postscreen plugin usage case, best would be to de-install
the i-MSCP plugins listed above and enable their SpamAssassin counterparts.

## Scores for blacklist and whitelist

Scores for both blacklist and whitelist where resetted to SpamAssassin default
values which fit better for the purpose of that feature.

## SpamAssassin user preferences -- Implementation details

In previous versions, the user preferences were been reset each time the plugin
was being reconfigured, which was a bad implementation. User preferences should
not be resetted when that is not explicitly requested by the administrator.

To solve this problem, the plugin configuration file has been fully rewritten,
now providing the administrator with an additional `enforce ` configuration
parameter that allows to force usage of a specific plugin.

In enforced mode, end-users won't be able to act on the plugin through their
user preferences and existing user preferences for that plugin are deleted.

Note that the `enforce` configuration parameter is only provided for some
plugins.

To resume, the new policy is as follows:

- When a plugin is being enabled, never reset user preferences, excepted if in
  `enforced` mode.
- When a plugin is being disabled, never reset user preferences. This don't
  pose any problem because the SpamAssassin plugin will be fully disabled.

## SPAMD connection mode

In previous versions, connection to SPAMD(8p) was established through TCP.
Starting with this new version, connection is now established through UDS
(Unix Domain Socket).

This is a boost-performance change as the network stack is no longer involved.
