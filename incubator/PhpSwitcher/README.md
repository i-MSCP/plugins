## PhpSwitcher v0.0.1 plugin for i-MSCP

WARNING PLUGIN UNDER DEVELOPMENT - DO NOT USE IT

Plugin allowing to add and set a specific PHP version to customers.

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

 See [GPL v2](http://www.gnu.org/licenses/lgpl-2.1.txt "LGPL v2.1")

### REQUIREMENTS

Plugin compatible with i-MSCP >= 1.1.1

### INTRODUCTION

This plugin allow to setup several PHP versions, which can be used by your customers. This plugin do not compile, nor
install any PHP version itself. Those steps must be done by the administrator.

At this moment, this plugin only support the i-MSCP Fcgid server implementation but in near future, all implementations
will be supported.

### INSTALLATION

	- Login into the panel as admin and go to the plugin management interface
	- Upload the PhpSwitcher plugin archive
	- Activate the plugin

### UPDATE

	- Login into the panel as admin and go to the plugin management interface
	- Upload the PhpSwitcher plugin archive
	- Update the plugin list through the plugin interface

### HOWTO SETUP NEW PHP VERSION

At first, you must get, compile and install the PHP version which you want make available for your customers. For instance,
if you want add PHP5.3 as a FastCGI application (Fcgid), you can follow these steps on Debian Wheezy (X86_64 arch):

#### Creating build environment

	# cd /usr/local/src
	# mkdir -p php_buildenv/php53 && cd php_buildenv/php53
	# mkdir -p /opt/php-fcgid/5.3
	# apt-get update && apt-get install build-essential

#### Installing needed libraries

	# apt-get build-dep php5
	# apt-get install libfcgi-dev libfcgi0ldbl libjpeg62-dbg libmcrypt-dev libssl-dev libc-client2007e \
	libc-client2007e-dev libpq5

	# Needed on X86_64 arch only
	# ln -s /usr/lib/libc-client.a /usr/lib/x86_64-linux-gnu/libc-client.a

#### Fetching PHP sources

	# wget http://au1.php.net/get/php-5.3.28.tar.bz2/from/this/mirror -O php.tar.bz2
	# tar jxf php.tar.bz2
	# cd php-5.3.28

#### Configuration

	# ./configure --prefix=/opt/php-fcgid/5.3 --with-pdo-pgsql --with-zlib-dir --with-freetype-dir --enable-mbstring \
	--with-libxml-dir=/usr --enable-soap --enable-calendar --with-curl --with-mcrypt --with-zlib --with-gd \
	--with-pgsql --disable-rpath --enable-inline-optimization --with-bz2 --with-zlib --enable-sockets \
	--enable-sysvsem --enable-sysvshm --enable-pcntl --enable-mbregex --enable-exif --enable-bcmath --with-mhash \
	--enable-zip --with-pcre-regex --with-mysql=mysqlnd --with-pdo-mysql=mysqlnd --with-mysqli=mysqlnd \
	--with-mysql-sock=/var/run/mysqld/mysqld.sock --with-jpeg-dir=/usr --with-png-dir=/usr --enable-gd-native-ttf \
	--with-openssl --with-libdir=/lib/x86_64-linux-gnu --enable-ftp --with-imap --with-imap-ssl --with-kerberos \
	--with-gettext --with-xmlrpc --with-xsl --enable-cgi

**Note:** If you need more modules, you must tune the configuration options and install needed libraries.

#### Compilation and installation

	# make
	# make install

#### Checking

Test your php binary by running the following command:

	# /opt/php-fcgid/5.3/bin/php-cgi -v

which should give the following result:

	PHP 5.3.28 (cgi-fcgi) (built: Feb 20 2014 18:02:14)
	Copyright (c) 1997-2013 The PHP Group
	Zend Engine v2.3.0, Copyright (c) 1998-2013 Zend Technologies

### Registration through PhpSwitcher

	# Login into the panel as administrator and go to the PhpSwitcher interface
	# Create a new PHP version with the following parameters:

		**Name:** PHP5.3 (Fcgid)
		**PHP binary path:** /opt/php-fcgid/5.3/bin/php-cgi
		**PHP configuration directory:** /var/www/fcgi

Once it's done and if all goes well, your customers should be able to switch to this new PHP version using their own
PhpSwitcher interface, which is available in the 'Domains' section.

### TROUBLESHOOTINGS

If you are currently running MariaDB on your server, you'll surely have some package dependencie problems while trying
to install PHP build package dependencies. In such case, you must temporary switch to the MySQL version as provided by
your distribution.

### AUTHORS AND CONTRIBUTORS

 * Laurent Declercq <l.declercq@nuxwin.com> (Author)

**Thank you for using this plugin.**
