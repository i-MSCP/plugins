# i-MSCP PhpSwitcher plugin v1.0.0

## Introduction

This plugin allows to setup additional PHP versions which can be used by your customers.

**Note:** At this moment, this plugin only support the i-MSCP Fcgid httpd server implementation but in near future,
the PHP5-FPM implementations will be also supported.

## Requirements

* i-MSCP version >= 1.2.3
* i-MSCP Fcgid httpd server implementation ( apache_fcgid )

### Memcached support ( Optional )

Debian / Ubuntu packages to install in case you want enable memcached support ( recommended )

- memcached
- libcache-memcached-fast-perl
- php5-memcached

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

## Setup new PHP version

At first, you must download, configure, compile and install the PHP version which you want make available for your
customers. You can either do the job manually, or by using the PHP compiler ( recommended ) that is shipped with this
plugin ( see below ).

### PHP compiler

The PHP compiler is a Perl script that allows to download, configure, compile, install additional PHP versions in one
step. The script is located in the **PhpSwitcher/PhpCompiler** directory.

For instance, if you want to install the **php-5.3** version, you can run the script as follow:
 
```shell
# perl /var/www/imscp/gui/plugins/PhpSwitcher/PhpCompiler/php_compiler.pl php-5.3
```

Or if you want install all PHP versions which can be compiled by this script, you can run it as follow:

```shell
# perl /var/www/imscp/gui/plugins/PhpSwitcher/PhpCompiler/php_compiler.pl all
```

By default, the script will build new PHP versions into the **/usr/local/src/phpswitcher** directory and install them in
the **/opt/phpswitcher** subtree but you can change this behavior by using command line options.

To get more information about available command line options, you can run:

```shell
# perl /var/www/imscp/gui/plugins/PhpSwitcher/PhpCompiler/php_compiler.pl --help
```

#### Supported PHP versions

Supported PHP versions are: **php-5.2**, **php-5.3**, **php-5.4**, **php-5.5** and **php-5.6**.

The versions supported by the PHP compiler are the last versions which were available when this plugin version has been
released. This means that by default, the PHP versions provided by this script can be lower than the last released
versions on the PHP site. In such case, you can use the **--force-last** command line option which tells the
PHP compiler to download the last released versions. However, you must be aware that the PHP compiler could fail to
apply the set of Debian patches on new versions. In such a case, you should create a ticket on our bug tracker using the
output that is provided by the PHP compiler.

## Configuration

### Registering a PHP version in PhpSwitcher

1. Login into the panel as administrator and go to the PhpSwitcher interface ( settings section )
2. Create a new PHP version with that kind of parameters:

<table>
	<tr>
		<th>Parameter</th>
		<th>Value</th>
		<th>Description</th>
	</tr>
	<tr>
		<td>PHP version</td>
		<td>PHP5.3 (Fcgid)</td>
		<td>This is an unique name for the new PHP version</td>
	</tr>
	<tr>
		<td>PHP binary path</td>
		<td>/opt/phpswitcher/php5.3/bin/php-cgi</td>
		<td>This is the path of the PHP binary</td>
	</tr>
	<tr>
		<td>PHP configuration directory path</td>
		<td>/var/www/fcgi</td>
		<td>This is the base directory in which customers's PHP configuration files are stored</td>
	</tr>
</table>

Once it's done and if all goes well, your customers should be able to switch to this new PHP version using their own
PhpSwitcher interface, which is available in the **Domains** section.

**Note:** You must of course adjust the parameters above according the PHP version you want to add.

### PHP configuration

This section is only relevant if you have installed additional PHP versions using the PHP compiler ( see above ).

First, it is important to note that it is useless to try to edit a PHP .ini file which is located under the **/etc/php5**
directory. Indeed, .ini files located under that directory are only relevant for PHP versions which are provided by
your distribution. For the same reasons, it is useless to try to enable or disable a PHP module using the command line
tools ( php5enmod/php5dismod ) which are provided by your distribution. Those tools only operate on .ini files that are
provided by your distribution.

By default, the PHP compiler installs additional PHP versions in it own subtree which is **/opt/phpswitcher/**. Thus, if
you want modify any file related to a PHP version which has been installed by the PHP compiler, you must look in that
subtree. The following layout apply for PHP .ini files:

- The default php.ini file is located at **/opt/phpswitcher/<php_version>/etc/php/php.ini**
- Additional .ini files if any are loaded from the **/opt/phpswitcher/<php_version>/etc/php/conf.d** directory
- PHP .ini files for i-MSCP customers are located at **/var/www/fcgi/<domain.tld>/php5**

##### PHP extensions ( modules )

For better performances and further convenience, most of PHP extensions are compiled as shared modules by the PHP
compiler. When installing a new PHP version, the PHP compiler create a specific .ini file in which all available PHP
modules are enabled. This file is is located at **/opt/phpswitcher/<php_version>/etc/conf.d/modules.ini**.

Here, a single file is used for ease. This is not as in Debian where an .ini file is created for each modules. To
disable a specific module, you must just comment out the related line in the modules.ini file and restart Apache2.

### Memcached Support

In order, to enable memcached support, you must:

1. Be sure that all requirements as stated in the requirements section are meets
2. Enable memcached support by editing the plugin configuration file ( see below for available parameters )
5. Update the plugin list through the plugin management interface

#### Memcached configuration parameters

<table>
	<tr>
		<th>Parameter</th>
		<th>Value</th>
		<th>Description</th>
	</tr>
	<tr>
		<td>enabled</td>
		<td>boolean ( default FALSE )</td>
		<td>Allow to enable or disable memcached support</td>
	</tr>
	<tr>
		<td>hostname</td>
		<td>string ( default 127.0.0.1 )</td>
		<td>Memcached server hostname ( Either an IP or hostname )</td>
	</tr>
	<tr>
		<td>port</td>
		<td>integer ( default 11211 )</td>
		<td>Memcached server port</td>
	</tr>
</table>

## Translation

You can translate this plugin by copying the [l10n/en_GB.php](l10n/en_GB.php) language file, and by translating all the
array values inside the new file.

Feel free to post your language files in our forum for intergration in a later release. You can also fork the plugin
repository and do a pull request if you've a github account.

**Note:** File encoding must be UTF-8.

## License

```
i-MSCP PhpSwitcher plugin
Copyright (C) 2014-2015 Laurent Declercq <l.declercq@nuxwin.com>

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
```

 See [LICENSE](LICENSE)

## Author

* Laurent Declercq <l.declercq@nuxwin.com>
