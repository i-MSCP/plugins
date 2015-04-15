#!/usr/bin/perl
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

use strict;
use warnings;

use lib '/var/www/imscp/engine/PerlLib';
use iMSCP::Debug;
use iMSCP::Bootstrapper;
use iMSCP::Execute;

$ENV{'LANG'} = 'C.UTF-8';

# Bootstrap i-MSCP backend
iMSCP::Bootstrapper->getInstance()->boot(
	{ 'mode' => 'backend', 'nolock' => 'yes', 'nokeys' => 'yes', 'nodatabase' => 'yes', 'config_readonly' => 'yes' }
);

my $simulate = shift;
my @packages = (
	'autoconf',
	'automake',
	'automake1.11',
	'apache2-dev',
	'apache2-prefork-dev',
	'bison',
	'chrpath',
	'debhelper',
	'dh-apache2',
	'dh-systemd',
	'dpkg-dev',
	'firebird-dev',
	'firebird2.1-dev',
	'firebird2.5-dev',
	'flex',
	'freetds-dev',
	'hardening-wrapper',
	'language-pack-de',
	'libapparmor-dev',
	'libapr1-dev',
	'libbz2-dev',
	'libc-client-dev',
	'libc-client2007e-dev',
	'libcurl-dev',
	'libcurl4-openssl-dev',
	'libdb-dev',
	'libedit-dev',
	'libenchant-dev',
	'libevent-dev',
	'libexpat1-dev',
	'libfreetype6-dev',
	'libgcrypt11-dev',
	'libgd-dev',
	'libgd2-dev',
	'libgd2-xpm-dev',
	'libglib2.0-dev',
	'libgmp3-dev',
	'libicu-dev',
	'libjpeg-dev',
	'libjpeg62-dev',
	'libkrb5-dev',
	'libldap2-dev',
	'libmagic-dev',
	'libmcrypt-dev',
	'libmhash-dev',
	'libmysqlclient-dev',
	'libmysqlclient15-dev',
	'libonig-dev',
	'libpam0g-dev',
	'libpcre3-dev',
	'libpng-dev',
	'libpng12-dev',
	'libpq-dev',
	'libpspell-dev',
	'libqdbm-dev',
	'librecode-dev',
	'libsasl2-dev',
	'libsnmp-dev',
	'libsqlite3-dev',
	'libssl-dev',
	'libsystemd-daemon-dev',
	'libtidy-dev',
	'libtool',
	'libvpx-dev',
	'libwrap0-dev',
	'libxml2-dev',
	'libxmltok1-dev',
	'libxslt1-dev',
#	'locales-all',
	'mysql-server',
	'netcat-traditional',
	'quilt',
	're2c',
	'systemtap-sdt-dev',
	'unixodbc-dev',
	'virtual-mysql-server',
	'zlib1g-dev',
	'build-essential',
	'shtool',
	'gcc',
	'g++',
	'libc6-dev',
	'libc-dev',
	'make',
	'dpkg-dev',
	'autotools-dev'
);

my ($stdout, $stderr);
my $rs = execute("apt-cache --generate pkgnames", \$stdout, \$stderr);
error(sprintf('An error occurred while filtering packages to remove: %s', $stderr));
@packages = sort grep { $_ ~~ @packages } split /\n/, $stdout;
$rs = execute("aptitude purge --without-recommends -y" . ( ($simulate) ? ' -s' : ' ' ) . "@packages", undef, $stderr);
error($stderr) if $stderr && $rs;
exit $rs;
