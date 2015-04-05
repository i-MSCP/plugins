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
PHPIZE_BUILDDIR = lib/php5/build

# support new (>= 2.2) and older versions of libtool
LIBTOOL_DIRS = /usr/share/libtool/config /usr/share/libtool
LTMAIN = $(firstword $(wildcard $(foreach d,$(LIBTOOL_DIRS),$d/ltmain.sh)))
LTMAIN_DIR = $(dir $(LTMAIN))

ifeq ($(LTMAIN_DIR), /usr/share/libtool/)
LIBTOOL_CONFLICTS:=libtool (>= 2.2)
else ifeq ($(LTMAIN_DIR), /usr/share/libtool/config/)
LIBTOOL_CONFLICTS:=libtool (<< 2.2)
else
LIBTOOL_CONFLICTS:=$(error "could not resolve path to ltmain.sh")
endif

ifneq (,$(filter parallel=%,$(PHPSWITCHER_BUILD_OPTIONS)))
	NUMJOBS = $(patsubst parallel=%,%,$(filter parallel=%,$(PHPSWITCHER_BUILD_OPTIONS)))
	MAKEFLAGS += -j$(NUMJOBS)
endif

ifneq (,$(filter prefix=%,$(PHPSWITCHER_BUILD_OPTIONS)))
	PREFIX = $(patsubst prefix=%,%,$(filter prefix=%,$(PHPSWITCHER_BUILD_OPTIONS)))
else
	PREFIX=/opt
endif

COMMON_CONFIG= --build=$(DEB_BUILD_GNU_TYPE) \
		--host=$(DEB_HOST_GNU_TYPE) \
		--prefix=$(PREFIX) \
		--disable-debug \
		--disable-rpath \
		--disable-static \
		--enable-bcmath \
		--enable-calendar \
		--enable-ctype \
		--enable-exif \
		--enable-fastcgi \
		--enable-force-cgi-redirect \
		--enable-ftp \
		--enable-mbstring \
		--enable-mysqlnd \
		--enable-pcntl \
		--enable-pdo \
		--enable-shmop \
		--enable-soap \
		--enable-sockets \
		--enable-sysvmsg \
		--enable-sysvsem \
		--enable-sysvshm \
		--enable-wddx \
		--enable-zip \
		--with-db4 \
		--with-bz2 \
		--with-gettext \
		--with-iconv \
		--with-imap=/usr \
		--with-imap-ssl \
		--with-kerberos=/usr \
		--with-layout=GNU \
		--with-libxml-dir=/usr \
		--with-mhash=yes \
		--with-mysql-sock=/var/run/mysqld/mysqld.sock \
		--with-onig=/usr \
		--with-openssl=/usr \
		--with-pcre-regex=/usr \
		--with-mysql=mysqlnd \
		--with-mysqli=mysqlnd \
		--with-pdo-mysql=mysqlnd \
		--with-pic \
		--with-qdbm=/usr \
		--with-regex=php \
		--with-system-tzdata \
		--with-zlib \
		--without-gdbm \
		--without-mm \
		--without-mssql \
		--without-pdo-sqlite \
		--without-sqlite3 \
		--without-sybase-ct \
		$(CONFIGURE_DTRACE_ARGS)

prepared: prepared-stamp
prepared-stamp:
	./buildconf --force
	touch prepared-stamp

configure: configure-php5.2-stamp configure-php5.3-stamp configure-php5.4-stamp configure-php5.5-stamp configure-php5.6-stamp
configure-php5.6-stamp: prepared-stamp
	if [ -d php5.6-build ]; then rm -rf php-5.6-build; fi
	-mkdir php5.6-build
	cd php5.6-build && \
	CFLAGS="$(CFLAGS)" PROG_SENDMAIL="$(PROG_SENDMAIL)" ../configure \
		$(COMMON_CONFIG)
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
	cp -f /usr/share/misc/config.guess $(PREFIX)/$(PHPIZE_BUILDDIR)/config.guess
	cp -f /usr/share/misc/config.sub $(PREFIX)/$(PHPIZE_BUILDDIR)/config.sub
	cp -f /usr/share/aclocal/libtool.m4 $(PREFIX)/$(PHPIZE_BUILDDIR)/libtool.m4
	cp -f $(LTMAIN_DIR)ltmain.sh $(PREFIX)/$(PHPIZE_BUILDDIR)/ltmain.sh
	cp -f /usr/bin/shtool $(PREFIX)/$(PHPIZE_BUILDDIR)/shtool
	touch install-php5.6-stamp

unprepared:
	rm -f prepared-stamp

clean: unprepared
	rm -f configure-php5.6-stamp build-php5.6-stamp
	rm -f install-stamp
	rm -rf php5.6-build

.PHONY: build clean install configure
