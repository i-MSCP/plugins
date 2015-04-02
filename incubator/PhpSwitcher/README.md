# i-MSCP PhpSwitcher plugin v1.0.0

## Introduction

This plugin allows to setup many PHP versions, which can be used by your customers.

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

1. Backup your current config file **plugins/PhpSwitcher/config.php**
2. Login into the panel as admin and go to the plugin management interface
3. Upload the **PhpSwitcher** plugin archive
4. Restore your **plugins/PhpSwitcher/config.php** (compare it with new config file first)
5. Click on the **Update Plugins** button in the plugin management interface

## Plugin configuration

### Setup new PHP version

At first, you must download, configure, compile and install the PHP version which you want make available for your
customers. You can either process manually or by using the PHP compiler which is shipped with the plugin ( see below ).

#### PHP compiler

The PHP compiler is a script that allows to download, configure, compile and install one or many PHP versions in one step.
The script is available in the **PhpSwitcher/PhpCompiler** directory.

For instance, if you want to install the **php-5.3** version, you can run the script as follow:
 
 ```shell
 # perl /var/www/imscp/gui/plugins/PhpSwitcher/PhpCompiler/php_compiler.pl php-5.3
 ```

Or if you want install all PHP versions which can be compiled by this script, you can run it as follow:

```shell
# perl /var/www/imscp/gui/plugins/PhpSwitcher/PhpCompiler/php_compiler.pl all
```

Supported PHP versions are: **php-5.2**, **php-5.3**, **php-5.4**, **php-5.5** and **php-5.6**.

By default, the script will build new PHP versions into the **/usr/local/src/phpswitcher** directory and install them in
the **/opt/phpswitcher** directory but you can change this behavior by using command line options.

To get more information about available command line options, you can run:

```shell
# perl /var/www/imscp/gui/plugins/PhpSwitcher/PhpCompiler/php_compiler.pl --help
```

#### Registration through PhpSwitcher

1. Login into the panel as administrator and go to the PhpSwitcher interface ( settings section )
2. Create a new PHP version with the following parameters:

<table>
	<tr>
		<th>Parameter</th>
		<th>Value</th>
		<th>Description</th>
	</tr>
	<tr>
		<td>Name</td>
		<td>PHP-5.3 (Fcgid)</td>
		<td>This is the unique name for the new PHP version</td>
	</tr>
	<tr>
		<td>PHP binary path</td>
		<td>/opt/phpswitcher/php-5.3/bin/php-cgi</td>
		<td>This is the path of the PHP binary</td>
	</tr>
	<tr>
		<td>PHP configuration directory</td>
		<td>/var/www/fcgi</td>
		<td>This is the directory in which customers's PHP configuration files will be stored</td>
	</tr>
</table>

Once it's done and if all goes well, your customers should be able to switch to this new PHP version using their own
PhpSwitcher interface, which is available in the **Domains** section.

### Memcached Support

In order, to enable memcached support, you must:

1. Be sure that all requirements as stated in the requirements section are meets
2. Backup your plugin configuration file if needed
3. Upload the plugin through the plugin management interface
4. Restore your plugin configuration file if needed ( compare it with the new version first )
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

 See [LGPL v2.1](http://www.gnu.org/licenses/lgpl-2.1.txt "LGPL v2.1")

## Author

 * Laurent Declercq <l.declercq@nuxwin.com>
