#!/usr/bin/make -f

# i-MSCP PhpSwitcher plugin
# Copyright (C) 2014-2015 Laurent Declercq <l.declercq@nuxwin.com>
#
# This library is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 2.1 of the License, or (at your option) any later version.
#
# This library is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this library; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA

# Set this flag to 'yes' if you want to disable all modifications breaking abi
# compatibility to upstream
PHP5_COMPAT=no

# enable dpkg build flags
DPKG_EXPORT_BUILDFLAGS = 1
include /usr/share/dpkg/buildflags.mk

DEB_HOST_GNU_TYPE    ?= $(shell dpkg-architecture -qDEB_HOST_GNU_TYPE)
DEB_BUILD_GNU_TYPE   ?= $(shell dpkg-architecture -qDEB_BUILD_GNU_TYPE)
DEB_HOST_ARCH        ?= $(shell dpkg-architecture -qDEB_HOST_ARCH)
DEB_HOST_ARCH_OS     ?= $(shell dpkg-architecture -qDEB_HOST_ARCH_OS)
DEB_HOST_MULTIARCH   ?= $(shell dpkg-architecture -qDEB_HOST_MULTIARCH)
export DEB_HOST_MULTIARCH

ifeq ($(DEB_HOST_ARCH),$(filter $(DEB_HOST_ARCH),amd64 armel armhf i386 ia64 powerpc))
  CONFIGURE_DTRACE_ARGS = --enable-dtrace
else
  CONFIGURE_DTRACE_ARGS = --disable-dtrace
endif

PROG_SENDMAIL = /usr/sbin/sendmail
ifeq (,$(findstring noopt,$(PHPSWITCHER_BUILD_OPTIONS)))
  CFLAGS += -O2
else
  CFLAGS += -O0
endif
#CFLAGS += -Wall -fsigned-char -fno-strict-aliasing
CFLAGS += -fsigned-char -fno-strict-aliasing
# LFS support
ifneq (yes,$(PHP5_COMPAT))
  CFLAGS += $(shell getconf LFS_CFLAGS)
endif

# Enable IEEE-conformant floating point math on alphas (not the default)
ifeq (alpha-linux-gnu,$(DEB_HOST_GNU_TYPE))
  CFLAGS += -mieee
endif

# Enable producing of debugging information
#CFLAGS += -g

# some other helpful (for readability at least) shorthand variables
PHPIZE_BUILDDIR = lib/php/build

# support new (>= 2.2) and older versions of libtool
LIBTOOL_DIRS = /usr/share/libtool/config /usr/share/libtool
LTMAIN = $(firstword $(wildcard $(foreach d,$(LIBTOOL_DIRS),$d/ltmain.sh)))
LTMAIN_DIR = $(dir $(LTMAIN))

ifneq (,$(filter parallel=%,$(PHPSWITCHER_BUILD_OPTIONS)))
	NUMJOBS = $(patsubst parallel=%,%,$(filter parallel=%,$(PHPSWITCHER_BUILD_OPTIONS)))
	MAKEFLAGS += -j$(NUMJOBS)
endif

ifneq (,$(filter prefix=%,$(PHPSWITCHER_BUILD_OPTIONS)))
	PREFIX = $(patsubst prefix=%,%,$(filter prefix=%,$(PHPSWITCHER_BUILD_OPTIONS)))
else
	PREFIX = /usr/local
endif

MYSQL_CONGIG = \
	--with-mysql=shared,/usr \
	--with-mysqli=shared,/usr/bin/mysql_config \
	--with-pdo-mysql=shared,/usr

MYSQLND_CONFIG = \
	--enable-mysqlnd=shared \
	--with-mysql=shared,mysqlnd \
	--with-mysqli=shared,mysqlnd \
	--with-pdo-mysql=shared,mysqlnd

COMMON_CONFIG = \
	--build=$(DEB_BUILD_GNU_TYPE) \
	--host=$(DEB_HOST_GNU_TYPE) \
	--prefix=$(PREFIX) \
	--with-config-file-path=$(PREFIX)/etc/php \
	--with-config-file-scan-dir=$(PREFIX)/etc/php/conf.d \
	--disable-debug \
	--disable-rpath \
	--disable-static \
	--enable-bcmath \
	--enable-calendar \
	--enable-ctype \
	--enable-exif \
	--enable-ftp \
	--enable-gd-native-ttf \
	--enable-intl=shared \
	--enable-mbstring \
	--enable-pcntl \
	--enable-pdo=shared \
	--enable-shmop \
	--enable-soap \
	--enable-sockets \
	--enable-sysvmsg \
	--enable-sysvsem \
	--enable-sysvshm \
	--enable-wddx \
	--enable-zip \
	--with-curl=shared,/usr \
	--with-db4 \
	--with-bz2 \
	--with-enchant=shared,/usr \
	--with-freetype-dir=shared,/usr \
	--with-gettext \
	--with-gd=shared  \
	--with-gmp=shared,/usr \
	--with-iconv \
	--with-imap=shared,/usr \
	--with-imap-ssl \
	--with-jpeg-dir=shared,/usr \
	--with-kerberos=/usr \
	--with-layout=GNU \
	--with-libedit=shared \
	--with-libevent-dir=/usr \
	--with-libxml-dir=/usr \
	--with-ldap=shared,/usr \
	--with-ldap-sasl=/usr \
	--with-onig=/usr \
	--with-openssl=/usr \
	--with-pcre-regex=/usr \
	--with-mcrypt=shared,/usr \
	--with-mhash=yes \
	--with-mssql=shared,/usr \
	--with-mysql-sock=/var/run/mysqld/mysqld.sock \
	--with-pdo-dblib=shared,/usr \
	--with-pdo-odbc=shared,unixODBC,/usr \
	--with-pdo-pgsql=shared,/usr/bin/pg_config \
	--with-pdo-sqlite=shared,/usr \
	--with-pgsql=shared,/usr PGSQL_INCLUDE=`pg_config --includedir` \
	--with-pic \
	--with-png-dir=shared,/usr \
	--with-pspell=shared,/usr \
	--with-qdbm=shared/usr \
	--with-recode=shared,/usr \
	--with-regex=php \
	--with-snmp=shared,/usr \
	--with-sqlite3=shared,/usr \
	--with-tidy=shared,/usr \
	--with-unixODBC=shared,/usr \
	--with-vpx-dir=shared,/usr \
	--with-xmlrpc=shared \
	--with-xpm-dir=shared,/usr/X11R6 \
	--with-xsl=shared,/usr \
	--with-zlib \
	--with-zlib-dir=/usr \
	--without-gdbm \
	--without-mm \
	--without-t1lib \
	$(CONFIGURE_DTRACE_ARGS)

ifneq ($(DEB_HOST_ARCH),$(filter $(DEB_HOST_ARCH),hurd-i386 m68k hppa ppc64))
  COMMON_CONFIG += --with-interbase=shared,/usr --with-pdo-firebird=shared,/usr
else
  COMMON_CONFIG += --without-interbase --without-pdo-firebird
endif

prepared: prepared-stamp
prepared-stamp:
	./buildconf --force
	touch prepared-stamp

configure: configure-php5.2-stamp configure-php5.3-stamp configure-php5.4-stamp configure-php5.5-stamp configure-php5.6-stamp
configure-php5.6-stamp: prepared-stamp
	if [ -d php5.6-build ]; then rm -rf php5.6-build; fi
	-mkdir php5.6-build
	cd php5.6-build && \
	CFLAGS="$(CFLAGS)" PROG_SENDMAIL="$(PROG_SENDMAIL)" ../configure \
		$(COMMON_CONFIG) \
		$(MYSQLND_CONFIG) \
		--enable-cgi
	cd php5.6-build && \
	cp ../Zend/zend_ini_scanner.c ../Zend/zend_language_scanner.c \
	   ../Zend/zend_ini_parser.h ../Zend/zend_language_parser.h \
	   ../Zend/zend_ini_parser.c ../Zend/zend_language_parser.c \
	   Zend/
	touch configure-php5.6-stamp

build: build-php5.2-stamp build-php5.3-stamp build-php5.4-stamp build-php5.5-stamp build-php5.6-stamp
build-php5.6-stamp: configure-php5.6-stamp
	cd php5.6-build && $(MAKE)
	touch build-php5.6-stamp

install: install-php5.2-stamp install-php5.3-stamp install-php5.4-stamp install-php5.5-stamp install-php5.6-stamp
install-php5.6-stamp: build-php5.6-stamp
	cd php5.6-build && $(MAKE) install
	mkdir -p $(PREFIX)/etc/php/conf.d
	cp -f /usr/share/misc/config.guess $(PREFIX)/$(PHPIZE_BUILDDIR)/config.guess
	cp -f /usr/share/misc/config.sub $(PREFIX)/$(PHPIZE_BUILDDIR)/config.sub
	cp -f /usr/share/aclocal/libtool.m4 $(PREFIX)/$(PHPIZE_BUILDDIR)/libtool.m4
	cp -f $(LTMAIN_DIR)ltmain.sh $(PREFIX)/$(PHPIZE_BUILDDIR)/ltmain.sh
	cp -f /usr/bin/shtool $(PREFIX)/$(PHPIZE_BUILDDIR)/shtool
	touch install-php5.6-stamp

unprepared:
	rm -f prepared-stamp

clean: unprepared
	rm -f configure-php5.6-stamp build-php5.6-stamp install-php5.6-stamp
	rm -rf php5.6-build

.PHONY: build clean install configure
