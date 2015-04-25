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
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use FindBin qw($Bin);
use lib '/var/www/imscp/engine/PerlLib';
use File::Basename;
use File::Spec;
use iMSCP::Bootstrapper;
use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::Getopt;
use iMSCP::Service;
use version;

umask 022;

$ENV{'LANG'} = 'C.UTF-8';

# Bootstrap i-MSCP backend
iMSCP::Bootstrapper->getInstance()->boot(
    { 'mode' => 'backend', 'nolock' => 'yes', 'nokeys' => 'yes', 'nodatabase' => 'yes', 'config_readonly' => 'yes' }
);

# Common build dependencies
# Build dependencies were pulled from many Debian control files.
# Note: Packages are installed only if available.
my @BUILD_DEPS = (
    'autoconf',
    'autoconf2.59', # Needed for older PHP versions such as those from the 5.2.x branch
    'automake',
    'automake1.11',
    'bison',
    'chrpath',
    'firebird-dev',
    'firebird2.1-dev',
    'firebird2.5-dev',
    'flex',
    'freetds-dev',
    'language-pack-de',
    'libapparmor-dev',
    'libbz2-dev',
    'libc-client-dev',
    'libc-client2007e-dev',
    'libcurl-dev',
    'libcurl4-openssl-dev',
    'libdb-dev',
    'libedit-dev',
    'libenchant-dev',
#    'libevent-dev',
    'libexpat1-dev',
    'libfreetype6-dev',
    'libgcrypt11-dev',
    'libgd-dev',
    'libgd2-dev',
    'libgd2-xpm-dev',
    'libxpm-dev',
    'libglib2.0-dev',
    'libgmp3-dev',
    'libicu-dev',
    'libjpeg-dev',
    'libkrb5-dev',
    'libldap2-dev',
    'libmagic-dev',
    'libmcrypt-dev',
    'libmhash-dev',
    'libncurses5-dev',
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
    'libtidy-dev',
    'libtool',
    'libvpx-dev',
    'libwrap0-dev',
    'libxml2-dev',
    'libxmltok1-dev',
    'libxslt1-dev',
    'locales-all',
    'netbase',
    'netcat-traditional',
    'quilt',
    're2c',
    'systemtap-sdt-dev',
    'unixodbc-dev',
    'zlib1g-dev',
    'build-essential',
    'shtool',
    'wget',
    'libsqlite0-dev',
    'lemon'
);

# Conditional build dependencies
# Only needed for PHP versions older than 5.3 since for newest versions, we are using MySQL native driver ( mysqlnd )
my %CONDITIONAL_BUILD_DEPS = (
    'mysql' => [ 'libmysqlclient-dev', 'libmysqlclient15-dev' ],
    'mariadb' => [ 'libmariadbclient-dev' ],
    'percona' => [ 'libmysqlclient-dev' ]
);

# Map short PHP versions to long PHP versions ( last known tiny PHP versions )
my %SHORT_TO_LONG_VERSION = (
    '4.4' => '4.4.9',
    '5.2' => '5.2.17',
    '5.3' => '5.3.29',
    '5.4' => '5.4.40',
    '5.5' => '5.5.24',
    '5.6' => '5.6.8'
);

# URL patterns for PHP archives
my @URL_PATTERNS = (
    'http://de1.php.net/distributions/php-%s.tar.gz',
    'http://de2.php.net/distributions/php-%s.tar.gz',
    'http://uk1.php.net/distributions/php-%s.tar.gz',
    'http://uk3.php.net/distributions/php-%s.tar.gz',
    'http://us1.php.net/distributions/php-%s.tar.gz',
    'http://us2.php.net/distributions/php-%s.tar.gz',
    'http://us3.php.net/distributions/php-%s.tar.gz',
    'http://php.net/distributions/php-%s.tar.gz'
);

# Map long PHP versions to upstream version URLs
my %LONG_VERSION_TO_URL = ();

# Default values for non-boolean command line options
my $BUILD_DIR = '/usr/local/src/phpswitcher';
my $INSTALL_DIR = '/opt';
my $PARALLEL_JOBS = 4;
my $DEV_MODE = 'install';

# Parse command line options
iMSCP::Getopt->parseNoDefault(sprintf("\nUsage: perl %s [OPTION...] PHP_VERSION...", basename($0)) . qq {

PHP Compiler

This script allows to download, configure, compile and install one or many PHP versions on Debian/Ubuntu distributions in one step. Work is done by applying a set of patches which were pulled from the php5 Debian source package, and by using a dedicated Makefile file which defines specific targets for each PHP version.

PHP VERSIONS:
 Supported PHP versions are: } . ( join ', ', sort map { 'php' . $_ } keys %SHORT_TO_LONG_VERSION ) . qq {

 You can either specify one or many PHP versions or 'all' for all versions.

OPTIONS:
 -b,    --builddir <DIR>    Build directory ( /usr/local/src/phpswitcher ).
 -i,    --installdir <DIR>  Base installation directory ( /opt ).
 -d,    --download-only     Download only.
 -f,    --force-last        Force use of the last available PHP versions.
 -j,    --parallel-jobs <N> Number of parallel jobs for make ( default: 4 ).
 -d,    --dev-mode <MODE>   Development mode ( patch, configure, build )
 -v,    --verbose           Enable verbose mode.},
 'builddir|b=s' => sub { setOptions(@_); },
 'installdir|i=s' => sub { setOptions(@_); },
 'download-only|d' => \ my $DOWNLOAD_ONLY,
 'force-last|f' => \ my $FORCE_LAST,
 'parallel-jobs|j=i' => sub { setOptions(@_); },
 'dev-mode|d=s' => sub { setOptions(@_); },
 'verbose|v' => sub { setVerbose(@_); }
);

my @sVersions = ();

eval {
    if(grep { lc $_ eq 'all' } @ARGV) {
        @sVersions = keys %SHORT_TO_LONG_VERSION;
    } else {
        for my $sVersion(@ARGV) {
            ($sVersion) = lc($sVersion) =~ /^php(\d\.\d)$/;

            if($sVersion && exists $SHORT_TO_LONG_VERSION{$sVersion}) {
                push @sVersions, $sVersion;
            } else {
                die("Invalid PHP version\n");
            }
       }
    }

    @sVersions = sort @sVersions;
};

if($@ || !@sVersions) {
    print STDERR "\n$@" if $@;
    iMSCP::Getopt->showUsage();
}

installBuildDeps() unless $DOWNLOAD_ONLY;

for my $sVersion(@sVersions) {
    print output(sprintf('Processing PHP %s version', $sVersion), 'info');

    next unless (my $lVersion = getLongVersion($sVersion));

    downloadSource($sVersion, $lVersion);

    unless($DOWNLOAD_ONLY) {
        my $srcDir = File::Spec->join($BUILD_DIR, "php-$lVersion");
        chdir $srcDir or fatal(sprintf('Unable to change dir to %s', $srcDir));
        undef $srcDir;

        applyPatches($sVersion, $lVersion);
        install($sVersion, $lVersion) unless $DEV_MODE eq 'patch';

        print output(sprintf('PHP %s has been successfully processed', $lVersion), 'ok');
    }
}

my $srvMngr = iMSCP::Service->getInstance();
exit $srvMngr->reload('apache2') unless $DOWNLOAD_ONLY || ! $srvMngr->isRunning('apache2');

sub setOptions
{
    my ($option, $value) = @_;

    if($option eq 'builddir') {
        $BUILD_DIR = $value;
        print output(sprintf('Build directory has been set to %s', $value), 'info');
    } elsif($option eq 'installdir') {
        if(-d $value) {
            $INSTALL_DIR = $value;
            print output(sprintf('Base installation directory has been set to %s', $value), 'info');
        } else {
            die("Directory speficied by the --installdir option must exists.\n");
        }
    } elsif($option eq 'parallel-jobs') {
        $PARALLEL_JOBS = $value;
    } elsif($option eq 'dev-mode') {
        if($value ~~ [ 'patch', 'configure', 'build' ]) {
            $DEV_MODE = $value;
        } else {
            die("Invalid argument for the --dev-mode option.\n");
        }
    }
}

sub installBuildDeps
{
    print output('Installing build dependencies...', 'info');

    (my $sqlServer) = $main::imscpConfig{'SQL_SERVER'} =~ /^(mysql|mariadb|percona)/;

    if($sqlServer) {
        @BUILD_DEPS = (@BUILD_DEPS, @{$CONDITIONAL_BUILD_DEPS{$sqlServer}});
    } else {
        fatal('Unable to find your i-MSCP SQL server implementation');
    }

    # Filter packages which are not available since the build dependencies list is a mix of packages
    # that were pulled from different Debian php5 package control files
    my ($stdout, $stderr);
    (execute("apt-cache --generate pkgnames", \$stdout, \$stderr) < 2) or fatal(sprintf(
        'An error occurred while installing build dependencies: Unable to filter list of packages to install: %s',
        $stderr
    ));

    @BUILD_DEPS = sort grep { $_ ~~ @BUILD_DEPS } split /\n/, $stdout;

    # Install packages
    (execute("apt-get -y --force-yes --no-install-recommends install @BUILD_DEPS", undef, \$stderr) == 0) or fatal(
        sprintf("An error occurred while installing build dependencies: %s", $stderr)
    );

    # Fix possible "can not be used when making a shared object; recompile with –fPIC ... libc-client.a..." compile time
    # error. This error occurs when using the --with--pic option and when the /usr/lib/x86_64-linux-gnu/libc-client.a
    # symlink to /usr/lib/libc-client.a has been created manually.
    unlink '/usr/lib/x86_64-linux-gnu/libc-client.a' if -s '/usr/lib/x86_64-linux-gnu/libc-client.a';

    print output(sprintf('Build dependencies have been successfully installed'), 'ok');
}

sub getLongVersion
{
    my $sVersion = shift;
    my $lVersion = $SHORT_TO_LONG_VERSION{$sVersion};
    my $versionIsAlive = (version->parse($sVersion) > version->parse('5.3'));
    my ($tiny) = $lVersion =~ /(\d+)$/;

    if($FORCE_LAST) {
        print output(sprintf('Scanning PHP site for last PHP %s.x version...', $sVersion), 'info');
    } else {
        print output(sprintf('Scanning PHP site for PHP %s version...', $lVersion), 'info');
    }

    my $foundUrl;
    my $ret = 0;

    # At first, we scan the PHP museum. This covers the versions which are end of life and the versions which were moved
    # since the last release of this script.
    do {
        my $url = sprintf('http://museum.php.net/php5/php-%s.tar.gz', "$sVersion.$tiny");
        my ($stdout, $stderr);
        $ret = execute("wget --spider $url", \$stdout, \$stderr);
        debug($stdout) if $stdout;
        debug($stderr) if $stderr;
        unless($ret) {
            $foundUrl = $url;
            $tiny++;
        }
    } while($ret == 0 && $versionIsAlive && $FORCE_LAST);

    # We scan PHP mirrors if needed
    if($versionIsAlive && $FORCE_LAST || !$foundUrl) {
        for my $urlPattern(@URL_PATTERNS) {
            do {
                my $url = sprintf($urlPattern, "$sVersion.$tiny");
                my ($stdout, $stderr);
                $ret = execute("wget --spider $url", \$stdout, \$stderr);
                debug($stdout) if $stdout;
                debug($stderr) if $stderr;
                unless($ret) {
                    $foundUrl = $url;
                    $tiny++;
                }
            } while($ret == 0 && $FORCE_LAST);

            next if $ret != 0 && $ret != 8;
            last if defined $foundUrl || $ret == 8;
        }
    }

    if($foundUrl) {
        $tiny--;
        $lVersion = "$sVersion.$tiny";
        $LONG_VERSION_TO_URL{$lVersion} = $foundUrl;
        print output(sprintf('Found php-%s archive at %s', $lVersion, $LONG_VERSION_TO_URL{$lVersion}), 'info');
    } else {
        print output(sprintf('Could not find any valid URL for PHP %s - Skipping', $sVersion), 'error');
        $lVersion = '';
    }

    $lVersion;
}

sub downloadSource
{
    my ($sVersion, $lVersion) = @_;

    my $phpArchPath = File::Spec->join($BUILD_DIR, "php-$lVersion.tar.gz");
    my $phpSrcPath = File::Spec->join($BUILD_DIR, "php-$lVersion");

    unless(-f $phpArchPath) {
        print output(sprintf('Donwloading php-%s archive into %s...', $lVersion, $BUILD_DIR), 'info');

        (iMSCP::Dir->new( dirname => $BUILD_DIR  )->make( { mode => 0755 } ) == 0) or fatal(sprintf(
            'Unable to create the %s build directory: %s', $BUILD_DIR, getLastError()
        ));

        my ($stdout, $stderr);
        (
            execute("wget -t 1 -O $phpArchPath $LONG_VERSION_TO_URL{$lVersion}", \$stdout, \$stderr) == 0
        ) or fatal(sprintf(
            "An error occurred while downloading the php-%s archive: %s", $lVersion, $stderr
        ));

        print output(sprintf('php-%s archive has been successfully downloaded', $lVersion), 'ok');
    } else {
        print output(sprintf('php-%s archive is already present - skipping download', $lVersion), 'info');
    }

    unless($DOWNLOAD_ONLY) {
        print output(sprintf('Extracting php-%s archive into %s ...', $lVersion, $phpSrcPath), 'info');

        if($DEV_MODE eq 'install') {
            # Remove previous directory if any
            (iMSCP::Dir->new( dirname => $phpSrcPath )->remove() == 0) or fatal(sprintf(
                'Unable to remove the %s directory: %s', $phpSrcPath, getLastError()
            ));
        }

        unless(-d "$BUILD_DIR/php-$lVersion") {
            my ($stdout, $stderr);
            (execute("tar -xzf $phpArchPath -C $BUILD_DIR/", \$stdout, \$stderr) == 0) or fatal(sprintf(
                "An error occurred while extracting the php-%s archive: %s", $lVersion, $stderr
            ));

            print output(sprintf('php-%s archive has been successfully extracted into %s', $lVersion, $BUILD_DIR), 'ok');

            if($sVersion eq '4.4') {
                my $sslArchPath = "$Bin/php$sVersion/openssl-0.9.8zf.tar.gz";
                my $sslSrcPath = File::Spec->join($phpSrcPath, "openssl-0.9.8zf");

                print output(sprintf('Extracting OpenSSL (0.9.8zf) archive into %s ...', $phpSrcPath), 'info');

                my ($stdout, $stderr);
                (execute("tar -xzf $sslArchPath -C $phpSrcPath/", \$stdout, \$stderr) == 0) or fatal(sprintf(
                    "An error occurred while extracting the OpenSSL (0.9.8zf) archive: %s", $stderr
                ));

                print output(
                    sprintf('OpenSSL (0.9.8zf) archive has been successfully extracted into %s', $phpSrcPath), 'ok'
                );
            }
        }
    }
}

sub applyPatches
{
    my ($sVersion, $lVersion) = @_;

    print output(sprintf('Applying patches on php-%s source...', $lVersion), 'info');

    # Make quilt aware of patches location
    $ENV{'QUILT_PATCHES'} = "$Bin/php$sVersion/patches";

    my $stderr;
    (execute("quilt --quiltrc /dev/null push --fuzz 0 -a -q", undef, \$stderr) ~~ [ 0, 2 ]) or fatal(sprintf(
        'An error occurred while applying patches on php-%s source: %s', $lVersion, $stderr
    ));

    print output(sprintf('Patches have been successfully applied on php-%s source', $lVersion), 'ok');
}

sub install
{
    my ($sVersion, $lVersion) = @_;
    my $target = $DEV_MODE . '-php' . $sVersion;
    my $installDir = File::Spec->join($INSTALL_DIR, "php$sVersion");

    if($sVersion ~~ [ '4.4', '5.2' ]) {
        # Force usage of autoconf2.59 since older PHP versions are not compatible with newest autoconf versions
        $ENV{'PHP_AUTOCONF'} = 'autoconf2.59';
        $ENV{'PHP_AUTOHEADER'} = 'autoheader2.59';
        $ENV{'PHPSWITCHER_BUILD_OPTIONS'} = "parallel=1"; # Parallel jobs don't work well with older PHP versions
    } else {
        $ENV{'PHPSWITCHER_BUILD_OPTIONS'} = "parallel=$PARALLEL_JOBS";
    }

    if($DEV_MODE eq 'install') {
        # Remove previous installation if any
        (iMSCP::Dir->new( dirname => $installDir )->remove() == 0) or fatal(sprintf(
            'Unable to remove %s directory: %s', $installDir, getLastError
        ));
    } else {
        # Execute clean target

        print output(sprintf('Executing the %s make target for php-%s...', 'clean', $lVersion), 'info');

        my $stderr;
        (execute("make -f $Bin/Makefile clean", undef, \$stderr) == 0) or fatal(sprintf(
            'An error occurred while executing the %s make target for php-%s: %s', 'clean', $lVersion, $stderr
        ));

        print output(sprintf('The %s make target has been successfully executed for php-%s', 'clean', $lVersion), 'ok');
    }

    print output(sprintf('Executing the %s make target for php-%s...', $target, $lVersion), 'info');

    my $stderr;
    (execute("make -f $Bin/Makefile PREFIX=$installDir $target", undef, \$stderr) == 0) or fatal(sprintf(
        'An error occurred while executing the %s make target for php-%s: %s', $target, $lVersion, $stderr
    ));

    print output(sprintf('The %s make target has been successfully executed for php-%s', $target, $lVersion), 'ok');

    if($DEV_MODE eq 'install') {
        # Install modules.ini file

        print output(sprintf('Installing PHP modules.ini file for php-%s...', $lVersion), 'info');

        (
            iMSCP::File->new( filename => "$Bin/php$sVersion/modules.ini" )->copyFile(
                "$installDir/etc/php/conf.d", { preserve => 'no' }
            ) == 0
        ) or fatal(sprintf(
            'An error occurred while copying the PHP modules.ini file for php-%s: %s', $lVersion, getLastError
        ));

        print output(sprintf('PHP modules.ini file has been successfully installed for php-%s', $lVersion), 'ok');
    }
}
