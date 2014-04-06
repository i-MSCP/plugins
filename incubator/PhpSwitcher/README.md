## PhpSwitcher v0.0.8 plugin for i-MSCP

Plugin allowing to provide many PHP versions to customers.

### LICENSE

 i-MSCP PhpSwitcher plugin
 Copyright (C) 2014 Laurent Declercq <l.declercq@nuxwin.com>

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

 See [LGPL v2.1](http://www.gnu.org/licenses/lgpl-2.1.txt "LGPL v2.1")

### REQUIREMENTS

* i-MSCP >= 1.1.2
* i-MSCP Fcgid httpd server implementation (apache_fcgid)

#### Memcached support (Optional)

Debian / Ubuntu packages to install in case you want enable memcached support (recommended)

- memcached
- libcache-memcached-fast-perl
- php5-memcached

### INTRODUCTION

This plugin allow to setup many PHP versions, which can be used by your customers. This plugin do not compile, nor
install any PHP version itself. Those steps must be done by the administrator (see below).

**Note:** At this moment, this plugin only support the i-MSCP Fcgid httpd server implementation but in near future,
the PHP5-FPM implementations will be also supported.

### INSTALLATION

1. Login into the panel as admin and go to the plugin management interface
2. Upload the **PhpSwitcher** plugin archive
3. Click on the **Update Plugins** button
4. Activate the plugin

#### Memcached Support

In order, to enable memcached support, you must:

1. Install the needed packages (see the requirements section above)
2. Edit the **plugins/PhpSwitcher/config.php** configuration file to enable memcached support
3. Login into the panel interface as admin and go to the plugin management interface
4. Click on the **Update Plugins** button in the plugin management interface

##### Memcached configuration parameters

<table>
	<tr>
		<th>Parameter</th>
		<th>Value</th>
		<th>Description</th>
	</tr>
	<tr>
		<td>enabled</td>
		<td>boolean (default false)</td>
		<td>Allow to enable or disable memcached support</td>
	</tr>
	<tr>
		<td>hostname</td>
		<td>string (default 127.0.0.1)</td>
		<td>Memcached server hostname (Either an IP or hostname)</td>
	</tr>
	<tr>
		<td>port</td>
		<td>integer (default 11211)</td>
		<td>Memcached server port</td>
	</tr>
</table>

### UPDATE

1. Backup your current config file **plugins/PhpSwitcher/config.php**
2. Login into the panel as admin and go to the plugin management interface
3. Upload the **PhpSwitcher** plugin archive
4. Restore your **plugins/PhpSwitcher/config.php** (compare it with new config file first)
5. Click on the **Update Plugins** button in the plugin management interface

### SETUP NEW PHP VERSION

At first, you must get, compile and install the PHP version which you want make available for your customers. For
instance, if you want add PHP5.3 as a FastCGI application (Fcgid), you can follow the following steps on Debian Wheezy
(X86_64 arch):

#### Creating build environment

	# cd /usr/local/src
	# mkdir -p php_buildenv/php53 && cd php_buildenv/php53
	# mkdir -p /opt/php-fcgid/5.3
	# apt-get update && apt-get install build-essential

#### Installing needed libraries

	# apt-get build-dep php5
	# apt-get install libfcgi-dev libfcgi0ldbl libjpeg62-dbg libmcrypt-dev libssl-dev libc-client2007e \
	libc-client2007e-dev libpq5

##### Needed on X86_64 arch only

	# ln -s /usr/lib/libc-client.a /usr/lib/x86_64-linux-gnu/libc-client.a

#### Fetching PHP sources

	# wget http://de.php.net/get/php-5.3.28.tar.bz2/from/this/mirror -O php.tar.bz2
	# tar xjf php.tar.bz2
	# cd php-5.3.28

#### Configuration

	# ./configure \
	--prefix=/opt/php-fcgid/5.3 \
	--with-config-file-scan-dir=/opt/php-fcgid/5.3/conf.d \
	--with-pdo-pgsql \
	--with-zlib-dir \
	--with-freetype-dir \
	--enable-mbstring \
	--with-libxml-dir=/usr \
	--enable-soap \
	--enable-calendar \
	--with-curl \
	--with-mcrypt \
	--with-zlib \
	--with-gd \
	--with-pgsql \
	--disable-rpath \
	--enable-inline-optimization \
	--with-bz2 \
	--with-zlib \
	--enable-sockets \
	--enable-sysvsem \
	--enable-sysvshm \
	--enable-pcntl \
	--enable-mbregex \
	--enable-exif \
	--enable-bcmath \
	--with-mhash \
	--enable-zip \
	--with-pcre-regex \
	--with-mysql=mysqlnd \
	--with-pdo-mysql=mysqlnd \
	--with-mysqli=mysqlnd \
	--with-mysql-sock=/var/run/mysqld/mysqld.sock \
	--with-jpeg-dir=/usr \
	--with-png-dir=/usr \
	--enable-gd-native-ttf \
	--with-openssl \
	--with-libdir=/lib/x86_64-linux-gnu \
	--enable-ftp \
	--with-imap \
	--with-imap-ssl \
	--with-kerberos \
	--with-gettext \
	--with-xmlrpc \
	--with-xsl \
	--enable-cgi

**Note:** If you need more modules, you must tune the configuration options and install needed libraries.

#### Compilation and installation

	# make
	# make install

#### Checking

Test your php binary by running the following command:

	# /opt/php-fcgid/5.3/bin/php-cgi -v

which should give a result such as:

	PHP 5.3.28 (cgi-fcgi) (built: Feb 20 2014 18:02:14)
	Copyright (c) 1997-2013 The PHP Group
	Zend Engine v2.3.0, Copyright (c) 1998-2013 Zend Technologies

#### Registration through PhpSwitcher

1. **Login into the panel as administrator and go to the PhpSwitcher interface (settings section)**
2. **Create a new PHP version with the following parameters:**

<table>
	<tr>
		<th>Parameter</th>
		<th>Value</th>
		<th>Description</th>
	</tr>
	<tr>
		<td>Name</td>
		<td>PHP5.3 (Fcgid)</td>
		<td>This is the unique name for the new PHP version</td>
	</tr>
	<tr>
		<td>PHP binary path</td>
		<td>/opt/php-fcgid/5.3/bin/php-cgi</td>
		<td>This is the path of the PHP binary</td>
	</tr>
	<tr>
		<td>PHP configuration directory</td>
		<td>/var/www/fcgi</td>
		<td>This is the directory in which customers's configuration files will be stored</td>
	</tr>
</table>

Once it's done and if all goes well, your customers should be able to switch to this new PHP version using their own
PhpSwitcher interface, which is available in the **Domains** section.

#### Troubleshootings

If you are running MariaDB on your server and if you encounter some problems while trying to install PHP build packages,
you must temporary switch to the MySQL version as provided by your distribution. This can be done easily by using the
i-MSCP installer.

### AUTHORS AND CONTRIBUTORS

 * Laurent Declercq <l.declercq@nuxwin.com> (Author)

**Thank you for using this plugin.**
