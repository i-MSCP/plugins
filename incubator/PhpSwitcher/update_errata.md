# Update errata

## Update to version 1.0.0

### New features

#### PHP compiler

A PHP compiler has been added. This is a Perl script which allows to download, configure, compile and install one or
many PHP versions in one step. That is now the recommended way to install or update PHP versions for use with this plugin.

See the [README.md](README.md#php-compiler) for further details.

#### Per user, per domain and per site PHP version

It is now possible to setup a specific PHP version either per user, per domain or per site, depending on the PHP INI
level in use. If you want to allow your customers to set per site PHP version, you must ensure that the PHP INI level
of the Fcgid httpd implementation is set to **per_site**. You can reconfigure the PHP INI level by running the following
command:

```shell
# perl /var/www/imscp/engine/setup/imscp-setup -dar httpd
```

#### PHP info ( phpinfo )

Links to phpinfo were added in the client interface. This allows your customers to get information ( phpinfo ) about the
PHP version currently in use for their sites. PHP info are provided through static phpinfo files which are generated
when you add or change a PHP version, or when you explicitely ask to re-generate them.

You can always disable this feature by editing the plugin configuration file, and by updating the plugin list through
the plugin management interface.

**Note:** If you disable or enable a PHP module, as stated in the [README.md](README.md##php-extensions--modules-)
file, do not forget to re-generate the PHP info file through the PhpSwitcher admin interface.

### Removed features

#### Memcached support

Memcached support has been removed due to security issues ( possible cache poisoning ). Thus, the backend side of the
plugin has been fully rewritten to improve overall performances.

#### PHP version parameters

It is no longer possible to setup a specific PHP configuration directory for the PHP ini files. The directory is now
defined by the Fcgid httpd server implementation that is provided by i-MSCP ( **/var/www/fcgi** by default ).
