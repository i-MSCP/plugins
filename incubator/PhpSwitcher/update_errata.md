# Update errata

## Update to version 1.0.0

### Memcached support

Memcached support has been removed due to security issues ( cache poisoning ). Thus, the plugin ( backend side ) has
been fully rewritten to improve overall performances.

### Plugin configuration file

The plugin configuration file has been removed. Thus, it is useless to restore your previous configuration file.

### PHP version parameter

It is no longer possible to setup a specific PHP configuration directory for the PHP ini files. The directory is now
defined by the Fcgid httpd server implementation that is provided by i-MSCP ( **/var/www/fcgi** by default ).

### PHP compiler

A PHP compiler has been added. This is a Perl script which allow to download, configure, compile and install one or many
PHP version in one step. That is now the recommended way to install/update PHP versions for use with this plugin.

See the [README.md](README.md) for further details.
