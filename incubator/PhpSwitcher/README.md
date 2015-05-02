# i-MSCP PhpSwitcher plugin v1.0.0

## Introduction

This plugin allows to setup additional PHP versions for your customers.

## Requirements

* i-MSCP version >= 1.2.3
* i-MSCP Apache Fcgid httpd server implementation

## Installation

1. Be sure that all requirements as stated in the requirements section are meets
2. Upload the plugin through the plugin management interface
3. Install the plugin through the plugin management interface
4. Compile and install additional PHP versions ( see below to know how )
5. Register additional PHP versions into the PhpSwitcher interface ( see below to know how )

## Update

1. Be sure that all requirements as stated in the requirements section are meets
2. Upload the plugin through the plugin management interface
3. Update the plugin list through the plugin management interface

**Note:** Prior any update attempt, do not forget to read the [update_errata.md](update_errata.md) file.

## Setup new PHP versions

At first, you must download, configure, compile and install the PHP versions which you want to make available for your
customers. You can either do the job manually, or use the PHP compiler ( **recommended** ) that is shipped with this
plugin ( see below ).

### PHP compiler

The PHP compiler is a Perl script that allows to download, configure, compile and install additional PHP versions in one
step. The script is located in the **PhpSwitcher/PhpCompiler** directory.

For instance, if you want to install the **php5.2** version, you can run the script as follow:
 
```shell
# cd  /var/www/imscp/gui/plugins/PhpSwitcher/PhpCompiler
# perl php_compiler.pl php5.2
```

Or if you want install any other PHP version ( or all ) supported by this script, you can run it as follow:

```shell
# cd  /var/www/imscp/gui/plugins/PhpSwitcher/PhpCompiler
# perl php_compiler.pl %version%
```

Supported Parameters|Version(s)
--------------------|---------
php4.4              |4.4
php5.2              |5.2
php5.3              |5.3
php5.4              |5.4
php5.5              |5.5
php5.6              |5.6
all                 |4.4, 5.2, 5.3, 5.4, 5.5, 5.6

By default, the script will build new PHP versions into the **/usr/local/src/phpswitcher** directory and install them in
the **/opt/phpswitcher** subtree but you can change this behavior by using command line options.

To get more information about available command line options, you can run:

```shell
# cd  /var/www/imscp/gui/plugins/PhpSwitcher/PhpCompiler
# perl php_compiler.pl --help
```

#### Supported PHP versions

The versions supported by the PHP compiler are the last which were available when this plugin version has been released.
This means that by default, the PHP versions provided by this script can be lower than the last that have been released
on the PHP site. In such case, you can use the **--force-last** command line option which tells the PHP compiler to
download the last released versions. However, you must be aware that the PHP compiler could fail to apply the needed
patches ( see below ) on these versions. In such a case, you should create a ticket on our bug tracker using the
provided output.

Supported PHP versions are: **php4.4**, **php5.2**, **php5.3**, **php5.4**, **php5.5** and **php5.6**.

**Warning:** Even if supported, it is not recommended to install PHP versions that have reached their end of life. You
should really think before providing those versions since they can cause several security issues.

#### SSL support for PHP 4.4

SSL support for php-4.4 is provided with a self-compiled OpenSSL library ( openssl-0.9.8zf ), which is installed in
the same subtree ( eg. /opt/phpswitcher/php4.4 ).

##### Changes made on PHP versions

The PHP versions provided by the PHP compiler are almost identical to those which are provided by the Debian team.

For each PHP version, a set of patches is applied on upstream source before compiling them. The patches include the
following changes:

- Multiarch support
- Usage of libtool as provided by Debian instead of the bundled version
- Any patch that fix a bug, a security issue or an FTBFS issue

The majority of the applied patches were pulled from the Debian php5 source package and adjusted when needed, while some
other were created to resolve FTBFS issues. Patch which were not pulled from Debian php5 sources package are prefixed
with the **nxw_** prefix.

To resume here, a PHP version that is compiled and installed using the PHP compiler is more secure and more appropriate
for use on Debian systems than a versions which is compiled manually.

##### Enabled extensions

PHP extensions which are enabled are the same that are enabled in PHP versions that are provided by Debian.

**Notes:**

- db4 extension is disabled for PHP versions older than 5.3 due to incompatibility with the Berkeley Database Libraries
versions that are shipped with Debian >= wheezy and Ubuntu >= Precise. This will be solved in later release by using a
self-compiled db4 library
- Almost all extensions are compiled as shared module. See the [PHP configuration](README.md#php-configuration) section
for more details.

#### Build dependencies

The PHP compiler installs the build dependencies for you but in the case where a package that is needed isn't available
on your system, the PHP compiler will go ahead and thus, the configuration process will fail.

#### Parallel Execution ( GNU make )

For faster compilation, the parallel exuction feature that is provided by GNU make is enabled when possible. This
feature allows to execute many recipes simultaneously. By default, 2 recipes are executed at once. On some systems where
the resources are poor, you could have to lower this value. This can be achieved using the **--parallel-jobs** command
line option which takes a number as value:

For instance:

```shell
# cd  /var/www/imscp/gui/plugins/PhpSwitcher/PhpCompiler
# perl php_compiler.pl --parallel-jobs 1 php5.2
```

will tell GNU make to not run more than 1 recipe at once.

See [GNU Make - Parallel Execution](https://www.gnu.org/software/make/manual/html_node/Parallel.html) for further details.

## Configuration

### PHP configuration

This section is only relevant if you have installed additional PHP versions using the PHP compiler.

First, it is important to note that it is useless to try to edit a PHP .ini file that is located under the **/etc/php5**
directory for a PHP version which has been installed by the PHP compiler. Indeed, the .ini files located under that
directory are only relevant for the PHP versions which are provided by your distribution.

For the same reasons, it is useless to try to enable or disable a PHP module using the command line tools
( php5enmod/php5dismod ) which are provided by your distribution. Those tools only operate on the .ini files that are
provided by your distribution.

By default, the PHP compiler installs additional PHP versions in its own subtree which is **/opt/phpswitcher**. Thus, if
you want to modify any file related to a PHP version which has been installed by the PHP compiler, you must look in that
subtree. The following layout apply for the PHP .ini files:

- The default php.ini file is located at **/opt/phpswitcher/\<php_version\>/etc/php/php.ini**
- Additional .ini files if any are located in the **/opt/phpswitcher/\<php_version\>/etc/php/conf.d** directory
- PHP .ini files for i-MSCP customers are located under the **/var/www/fcgi/\<domain.tld\>/php5** directory

##### PHP extensions ( modules )

For convenience, most of PHP extensions are compiled as shared modules by the PHP compiler. When installing a new PHP
version, the PHP compiler create a specific .ini file that enable most of available PHP extensions. This file is located
at **/opt/phpswitcher/\<php_version\>/etc/php/conf.d/modules.ini**.

Here, a single .ini file is used for ease. This is not as in Debian where an .ini file is created for each modules. To
enable/disable a specific module, you must just edit the **/opt/phpswitcher/\<php_version\>/etc/php/conf.d/modules.ini**
file and then, restart the Web server.

##### Pecl extensions

If you need to install a Pecl extension for a specific PHP version, you must not use the **pecl** nor the **phpize*
scripts which are shipped with your distribution. Instead, you must use those which are provided in the directory of the
PHP version you want operate on.

For instance, if you want install the **FileInfo** Pecl extension which was not yet integrated in php-5.2, you must
process as follow:

```shell
# /opt/phpswitcher/php5.2/bin/pecl install fileinfo
# echo 'extension = fileinfo.so' >> /opt/phpswitcher/php5.2/etc/php/conf.d/modules.ini 
```

Once done, you must reload the Web server as follow

```shell
# service apache2 reload
```

### PHP info files

For each PHP version that is registered through the PhpSwitcher admin interface ( see below ), a static PHP info file is
generated, which allows your customers to get information ( phpinfo ) via their own PhpSwitcher interface. This feature,
if not desired, can be easily disabled by editing the plugin configuration file and by updating the plugin list through
the plugin management interface.

Be aware that because the phpinfo files are static, you must re-generate them each time you made a configuration change
for a specific PHP version ( eg. when you enable or disable a PHP/Pecl extension ). This task can be done through the
PhpSwitcher admin interface.

### Registering a PHP version in PhpSwitcher

Once you have compiled and installed a new PHP version, you need to register it into the PhpSwitcher plugin to make it
available for your customers. This task must be done as follow:

1. Login into the panel as administrator and go to the PhpSwitcher interface ( settings section )
2. Create a new PHP version as follow:

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
</table>

Once it's done and if all goes well, your customers should be able to switch to this new PHP version using their own
PhpSwitcher interface, which is available in the **Domains** section.

**Note:** You must of course adjust the parameters above according the PHP version that you want to add.

## Translation

You can translate this plugin by copying the [l10n/en_GB.php](l10n/en_GB.php) language file, and by translating all the
array values inside the new file.

Feel free to forward us your translation files for integration in a later release. You can also fork the plugin
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
