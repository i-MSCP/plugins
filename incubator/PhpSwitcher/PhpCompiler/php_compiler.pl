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
#

use strict;
use warnings;
use lib '/var/www/imscp/engine/PerlLib';
use File::Basename;
use File::Spec;
use iMSCP::Bootstrapper;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::Getopt;
use iMSCP::Service;

use version;

umask 022;

$ENV{'LANG'} = 'C.UTF-8';

# Quilt common configuration
$ENV{'QUILT_PUSH_ARGS'} = '--color=no';
$ENV{'QUILT_DIFF_ARGS'} = '--no-timestamps --no-index -p ab --color=auto';
$ENV{'QUILT_REFRESH_ARGS'} = '--no-timestamps --no-index -p ab';
$ENV{'QUILT_DIFF_OPTS'} = '-p';

# PhpSwitcher compiler maintenance directory
my $MAINTENANCE_DIR = '/var/www/imscp/gui/plugins/PhpSwitcher/PhpCompiler/phpswitcher';

# Map short PHP versions to last known PHP versions
my %SHORT_TO_LONG_VERSION = (
#    '5.2' => '5.2.17',
#    '5.3' => '5.3.29',
#    '5.4' => '5.4.39',
#    '5.5' => '5.5.23',
    '5.6' => '5.6.7'
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
my $INSTALL_DIR = '/opt/phpswitcher';

newDebug('phpswitcher-php-compiler.log');

# Parse command line options
iMSCP::Getopt->parseNoDefault(sprintf("\nUsage: perl %s [OPTION...] PHP_VERSION...", basename($0)) . qq {

PHP Compiler

This script allows to download, configure, compile and install one or many PHP versions on Debian/Ubuntu distributions in one step. Work is made by applying a set of patches which were pulled from the php5 Debian source package, and by using a dedicated Makefile file which defines specific targets for each PHP version.

PHP VERSIONS:
 Supported PHP versions are: } . ( join ', ', map { 'php' . $_ } keys %SHORT_TO_LONG_VERSION ) . qq {

 You can either specify one or many PHP versions or 'all' for all versions.

OPTIONS:
 -b,    --builddir      Build directory ( /usr/local/src/phpswitcher ).
 -i,    --installdir    Base installation directory ( /opt/phpswitcher ).
 -d,    --download-only Download only.
 -f,    --force-last    Force use of the last available PHP versions.
 -v,    --verbose       Enable verbose mode.},
 'builddir|b=s' => sub { setOptions(@_); },
 'installdir|i=s' => sub { setOptions(@_); },
 'download-only|d' => \ my $DOWNLOAD_ONLY,
 'force-last|f' => \ my $FORCE_LAST,
 'verbose|v' => sub { setVerbose(@_); }
);

# Bootstrap i-MSCP backend
iMSCP::Bootstrapper->getInstance()->boot(
    { 'mode' => 'backend', 'nolock' => 'yes', 'nokeys' => 'yes', 'nodatabase' => 'yes', 'config_readonly' => 'yes' }
);

my @sVersions = ();

eval {
    if(grep { lc $_ eq 'all' } @ARGV) {
        @sVersions = keys %SHORT_TO_LONG_VERSION;
    } else {
        for my $sVersion(@ARGV) {
            $sVersion = lc $sVersion;

            if($sVersion =~ /^php(5\.[2-6])$/) {
                push @sVersions, $1;
            } else {
                die(sprintf("Invalid PHP version parameter: %s\n", $sVersion));
            }
       }
    }

    @sVersions = sort @sVersions;
};

if($@ || !@sVersions) {
    print STDERR "\n$@\n" if $@;
    iMSCP::Getopt->showUsage();
}

installBuildDep() unless $DOWNLOAD_ONLY;

for my $sVersion(@sVersions) {
    print output(sprintf('Processing PHP %s version', $sVersion), 'info');

    next unless (my $lVersion = getPhpLongVersion($sVersion));

    downloadAndExtractSource($lVersion);

    unless($DOWNLOAD_ONLY) {
        my $srcDir = File::Spec->join($BUILD_DIR, "php-$lVersion");
        chdir $srcDir or fatal(sprintf('Unable to change dir to %s', $srcDir));
        undef $srcDir,

        applyDebianPatches($lVersion);
        configure($lVersion);
        compile($lVersion);
        install($lVersion);

        print output(sprintf('PHP %s has been successfully installed', $lVersion), 'ok');
    }
}

sub setOptions
{
    my ($option, $value) = @_;

    if($option eq 'builddir') {
        print output(sprintf('Build directory set to %s', $value), 'info');
        $BUILD_DIR = $value;
    } elsif($option eq 'installdir') {
        if(-d $value) {
            print output(sprintf('Base installation directory set to %s', $value), 'info');
            $INSTALL_DIR = $value;
        } else {
            die("Directory speficied by the --installdir option must exists.\n");
        }
    }
}

sub installBuildDep
{
    print output(sprintf('Installing build dependencies...'), 'info');

    my ($stdout, $stderr);
    (execute('apt-get -y build-dep php5 && apt-get -y install shtool quilt', \$stdout, \$stderr) == 0) or fatal(
       sprintf("An error occurred while installing build dependencies: %s\n", $stderr)
    );
    debug($stdout) if $stdout;

    print output(sprintf('Build dependencies were successfully installed'), 'ok');
}

sub getPhpLongVersion
{
    my $sVersion = shift;
    my $lVersion = $SHORT_TO_LONG_VERSION{$sVersion};
    my $versionIsAlive = (version->parse($sVersion) > version->parse('5.3'));
    my ($tiny) = $lVersion =~ /\.(\d+)$/;

    if($FORCE_LAST) {
        print output(sprintf('Scanning PHP site for last PHP %s.x version...', $sVersion), 'info');
    } else {
        print output(sprintf('Scanning PHP site for PHP %s version...', $lVersion), 'info');
    }

    my $foundUrl;
    my $ret = 0;

    # At first, we scan the museum. This covers the versions which are end of life and the versions which were moved
    # since the last release of this script.
    do {
        my $url = sprintf('http://museum.php.net/php5/php-%s.tar.gz', "$sVersion.$tiny");
        my ($stdout, $stderr);
        $ret = execute("wget -t 3 -T 3 --spider $url", \$stdout, \$stderr);
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
                $ret = execute("wget -t 3 -T 3 --spider $url", \$stdout, \$stderr);
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

sub downloadAndExtractSource
{
    my $lVersion = shift;
    my ($sVersion) = $lVersion =~ /^(\d\.\d)/;
    my $archPath = File::Spec->join($BUILD_DIR, "php-$lVersion.tar.gz");
    my $srcPath = File::Spec->join($BUILD_DIR, "php-$lVersion");

    unless(-f $archPath) {
        print output(sprintf('Donwloading php-%s source archive into %s...', $lVersion, $BUILD_DIR), 'info');

        if(iMSCP::Dir->new( dirname => $BUILD_DIR  )->make( { mode => 0755 } )) {
            fatal(sprintf('Unable to create the %s build directory', $BUILD_DIR));
        }

        my ($stdout, $stderr);
        (
            execute(
                "wget --dns-timeout=3 --connect-timeout=3 -O $archPath $LONG_VERSION_TO_URL{$lVersion}",
                \$stdout,
                \$stderr
            ) == 0
        ) or fatal(
            sprintf("An error occurred while downloading the php-%s source archive\n: %s", $lVersion, $stderr)
        );
        debug($stdout) if $stdout;

        print output(sprintf('php-%s source archive successfully downloaded', $lVersion), 'ok');
    } else {
        print output(sprintf('php-%s source archive already present - skipping download', $lVersion), 'ok');
    }

    unless($DOWNLOAD_ONLY) {
        print output(sprintf('Extracting php-%s source archive into %s ...', $lVersion, $srcPath), 'info');

        # Remove previous directory if any
        iMSCP::Dir->new( dirname =>  $srcPath )->remove();

        my ($stdout, $stderr);
        (execute("tar -xzf $archPath -C $BUILD_DIR/", \$stdout, \$stderr) == 0) or fatal(
            sprintf("An error occurred while extracting the php-%s source archive: %s\n", $lVersion, $stderr)
        );
        debug($stdout) if $stdout;

        print output(sprintf('php-%s source archive successfully extracted into %s', $lVersion, $BUILD_DIR), 'ok');
    }
}

sub applyDebianPatches
{
    my $lVersion = shift;
    my ($sVersion) = $lVersion =~ /^(\d\.\d)/;

    print output(sprintf('Applying Debian patches on php-%s source...', $lVersion), 'info');

    # Make quilt aware of patches location
    $ENV{'QUILT_PATCHES'} = "/var/www/imscp/gui/plugins/PhpSwitcher/PhpCompiler/phpswitcher/php-$sVersion";

    my ($stdout, $stderr);
    (execute("quilt push -a", \$stdout, \$stderr) == 0) or fatal(sprintf(
        sprintf("An error occurred while applying Debian patches on php-%s source: %s\n", $lVersion, $stderr)
    ));
    debug($stdout) if $stdout;

    print output(sprintf('Debian patches successfully applied on php-%s source', $lVersion), 'ok');
}

sub configure
{
    my $lVersion = shift;
    my ($sVersion) = $lVersion =~ /^(\d\.\d)/;
    my $target = 'configure-php' . $sVersion . '-stamp';
    my $installDir = File::Spec->join($INSTALL_DIR, "php$sVersion");

    print output(sprintf('Executing the %s make target for php-%s...', $target, $lVersion), 'info');

    $ENV{'PHPSWITCHER_BUILD_OPTIONS'} = "prefix=$installDir";

    my $stderr;
    (execute("make -f $MAINTENANCE_DIR/Makefile $target", undef, \$stderr) == 0) or fatal(
        sprintf("An error occurred during php-%s configuration process: %s\n", $lVersion, $stderr)
    );

    print output(sprintf('%s make target successfully executed for php-%s', $target, $lVersion), 'ok');
}

sub compile
{
    my $lVersion = shift;
    my ($sVersion) = $lVersion =~ /^(\d\.\d)/;
    my $target = 'build-php' . $sVersion . '-stamp';

    print output(sprintf('Executing the %s make target for php-%s...', $target, $lVersion), 'info');

    my $stderr;
    (execute("make -f $MAINTENANCE_DIR/Makefile $target", undef, \$stderr) == 0) or fatal(
        sprintf("An error occurred during php-%s compilation process: %s\n", $lVersion, $stderr)
    );

    print output(sprintf('%s make target successfully executed for php-%s', $target, $lVersion), 'ok');
}

sub install
{
    my $lVersion = shift;
    my ($sVersion) = $lVersion =~ /^(\d\.\d)/;
    my $target = 'install-php' . $sVersion . '-stamp';
    my $installDir = File::Spec->join($INSTALL_DIR, "php$sVersion");

    print output(sprintf('Executing the %s make target for php-%s...', $target, $lVersion), 'info');

    # Remove previous directory if any
    iMSCP::Dir->new( dirname =>  $installDir )->remove();

    my $stderr;
    (execute("make -f $MAINTENANCE_DIR/Makefile $target", undef, \$stderr) == 0) or fatal(
        sprintf("An error occurred during php-%s installation process: %s\n", $lVersion, $stderr)
    );

    print output(sprintf('%s make target successfully executed for php-%s', $target, $lVersion), 'ok');
}

1;
