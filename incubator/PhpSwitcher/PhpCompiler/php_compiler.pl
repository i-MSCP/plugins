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

# This script allows to fetch, configure, compile and install a set of PHP versions on Debian/Ubuntu.
# Compilation is made using a dedicated Makefile which define specific targets for each PHP version and
# by applying a set of patch pulled from Debian packages.

use strict;
use warnings;

# PLEASE CHANGE THIS PATH ACCORDING YOUR INSTALLATION LAYOUT IF NEEDED
use lib '/var/www/imscp/engine/PerlLib';

use File::Basename;
use File::Spec;
use iMSCP::Debug;
use iMSCP::Execute;
use iMSCP::Dir;
use iMSCP::Getopt;
use iMSCP::Bootstrapper;

$ENV{'LANG'} = 'C.UTF-8';
umask 022;

# Build directory ( default: /usr/local/src/phpswitcher )
my $BUILDDIR = '/usr/local/src/phpswitcher';

# Base installation directory ( default; /opt )
my $INSTALLDIR = '/opt';

# PhpSwitcher compiler maintenance directory
my $MAINTDIR = '/var/www/imscp/gui/plugins/PhpSwitcher/PhpCompiler/phpswitcher';

# Map short PHP versions to PHP upstream source URLs
# If a new version has been released, just update the related url below.
my %UPSTREAMSRCURLS = (
    'php-5.2' => 'http://museum.php.net/php5/php-5.2.9.tar.gz',
    'php-5.3' => 'http://museum.php.net/php5/php-5.3.29.tar.gz',
    'php-5.4' => 'http://de1.php.net/get/php-5.4.39.tar.gz/from/this/mirror',
    'php-5.5' => 'http://de1.php.net/get/php-5.5.23.tar.gz/from/this/mirror',
    'php-5.6' => 'http://de1.php.net/get/php-5.6.7.tar.gz/from/this/mirror'
);

# PLEASE, DO NOT EDIT ANYTHING BELOW THIS LINE

newDebug('phpswitcher-php-compiler.log');

# Parse command line options
iMSCP::Getopt->parseNoDefault(sprintf("Usage: perl %s [OPTION...] PHP_VERSION...", basename($0)) . qq {

PhpSwitcher ( PHP compiler )

This script allows to configure, compile and install upstream PHP versions on Debian/Ubuntu distributions. This is done by using a dedicated Makefile which defines specific targets for each PHP version, and by applying a set of patches pulled from Debian packages.

Configuration options for each PHP version are identical to those used in Debian package excepted the fact that all extensions are configured statically.

PHP VERSIONS:
 Available PHP versions are: 'php-5.2', 'php-5.3', 'php-5.4', 'php-5.5', 'php-5.6' or 'all' for all versions.

OPTIONS:
 -b,    --builddir      Build directory ( /usr/local/src/phpswitcher ).
 -i,    --installdir    Base installation directory ( /opt ).
 -v,    --verbose       Enable verbose mode.},
 'builddir|b=s' => sub { setOptions(@_); },
 'installdir|i=s' => sub { setOptions(@_); },
 'verbose|v' => sub { setVerbose(@_); }
);

iMSCP::Bootstrapper->getInstance()->boot(
    { 'nolock' => 'yes', 'norequirements' => 'yes', 'nodatabase' => 'yes', 'config_readonly' => 'yes' }
);

my @phpVersions = ();

eval {
    if( grep { lc $_ eq 'all' } @ARGV ) {
        @ARGV = (keys %UPSTREAMSRCURLS);
    }

    for my $phpVersion(@ARGV) {
        $phpVersion = lc $phpVersion;

        if($phpVersion =~ /^php-5\.[2-6]$/) {
            my $phpLongVersion;

            for my $url(values %UPSTREAMSRCURLS) {
                ($phpLongVersion) = $url =~ /($phpVersion\.\d+)\.tar.gz/;
                last if $phpLongVersion;
            }

            if(defined $phpLongVersion) {
                push @phpVersions, $phpLongVersion;
                @phpVersions = sort @phpVersions;
            } else {
                die("An error occurred: Unable to find PHP version\n");
            }
        } else {
            die(sprintf("Invalid PHP version parameter: %s\n", $phpVersion));
        }
    }
};

if($@ || ! @phpVersions) {
    print STDERR "\n$@\n" if $@;
    iMSCP::Getopt->showUsage();
}

installBuildDependencies();

if(iMSCP::Dir->new( dirname => $BUILDDIR  )->make( { mode => 0755 } )) {
    fatal('Unable to create build directory');
}

for my $phpVersion (@phpVersions) {
    debug(sprintf('Processing the  %s PHP version', $phpVersion));

    fetchUpstreamSource($phpVersion);
    copyMaintDir($phpVersion);

    my $srcDir = $BUILDDIR . '/' . $phpVersion;
    chdir File::Spec->join($BUILDDIR . '/' . $phpVersion) or fatal(sprintf('Unable to change dir to %s', $srcDir));

    patchUpstreamSource($phpVersion);

    configure($phpVersion);
    #compile($phpVersion);
    #install($phpVersion);

    print output(sprintf('%s has been successfully installed on your system', $phpVersion), 'ok');
}

# Subroutines

# Set command line options
sub setOptions
{
    my ($option, $value) = @_;

    if($option eq 'builddir') {
        debug(sprintf('Build directory set to %s', $value));
        $BUILDDIR = $value;
    } elsif($option eq 'installdir') {
        if(-d $value) {
            debug(sprintf('Base installation directory set to %s', $value));
            $INSTALLDIR = $value;
        } else {
            die('Directory speficied by the --installdir directory must exists.');
        }
    }
}

# Install build dependencies for PHP5.x
sub installBuildDependencies
{
    debug(sprintf('Installing build dependencies'));

    my ($stdout, $stderr);
    (execute('apt-get build-dep php5 && apt-get install quilt', \$stdout, \$stderr) == 0) or fatal(
       sprintf("An unexpected error occurred during installation of build dependencies: %s\n", $stderr)
    );

    debug($stdout) if $stdout;
    print output('Installing build dependencies', 'ok');
}

# Fetch the given PHP upstream source
sub fetchUpstreamSource
{
    my $phpVersion = shift;

    my ($phpShortVersion) = $phpVersion =~ /(php-5\.[2-6])/;
    my $archPath = File::Spec->join($BUILDDIR . '/' . $phpVersion . '.tar.gz');

    debug(sprintf('Fetching %s upstream source into %s', $phpVersion, $BUILDDIR));

    unless(-f $archPath) {
        my ($stdout, $stderr);
        (execute("wget -O $archPath $UPSTREAMSRCURLS{$phpShortVersion}", \$stdout, \$stderr) == 0) or fatal(
            sprintf("An unexpected error occurred during fetch of %s upstream sources\n: %s", $phpVersion, $stderr)
        );

        debug($stdout) if $stdout;
        print output(sprintf('Fetching upstream source from %s', $UPSTREAMSRCURLS{$phpShortVersion}), 'ok');
    } else {
        print output(sprintf('%s archive already here - skipping', $phpVersion), 'ok');
    }

    debug(sprintf('Extracting %s upstream source into %s', $phpVersion, $BUILDDIR));

    # Remove previous directory if any
    iMSCP::Dir->new( dirname =>  $BUILDDIR . '/' . $phpVersion)->remove();

    my ($stdout, $stderr);
    (execute("tar -xzf $archPath -C $BUILDDIR/", \$stdout, \$stderr) == 0) or fatal(
        sprintf("An unexpected error occurred during extraction of upstream sources: %s\n", $stderr)
    );

    debug($stdout) if $stdout;
    print output(sprintf('Extracting %s upstream source', $phpVersion), 'ok');
}

# Copy the PhpSwitcher PHP compiler maintenance directory
sub copyMaintDir
{
    my $phpVersion = shift;

    my ($phpShortVersion) = $phpVersion =~ /(php-5\.[2-6])/;
    my $destDir = File::Spec->join($BUILDDIR . '/' . $phpVersion);

    debug(sprintf('Copying phpswitcher maintenance directory into %s', $destDir));

    my ($stdout, $stderr);
    (execute("cp -r $MAINTDIR  $destDir/", \$stdout, \$stderr) == 0) or fatal(sprintf(
        "An error occurred during copy of phpswitcher maintenance directory into %s: %s\n", $destDir, $stderr
    ));

    debug($stdout) if $stdout;
    print output(sprintf('Copying phpswitcher maintenance directory into %s', $destDir), 'ok');
}

# Apply Debian patches on PHP upstream source
sub patchUpstreamSource
{
    my $phpVersion = shift;

    my ($phpShortVersion) = $phpVersion =~ /(php-5\.[2-6])/;

    debug(sprintf('Applying Debian patches on %s upstream source', $phpVersion));

    $ENV{'QUILT_PATCHES'} = "phpswitcher/$phpShortVersion";
    $ENV{'QUILT_PUSH_ARGS'} = '--color=auto';
    $ENV{'QUILT_DIFF_ARGS'} = '--no-timestamps --no-index -p ab --color=auto';
    $ENV{'QUILT_REFRESH_ARGS'} = '--no-timestamps --no-index -p ab';
    $ENV{'QUILT_DIFF_OPTS'} = '-p';

    my ($stdout, $stderr);
    (execute("quilt push -a", \$stdout, \$stderr) == 0) or fatal(sprintf(
        sprintf("An error occurred while applying Debian patches on PHP upstream source: %s\n", $stderr)
    ));

    debug($stdout) if $stdout;
    print output(sprintf('Applying Debian patches on %s upstream source', $phpVersion), 'ok');
}

# Configure the given PHP version
sub configure
{
    my $phpVersion = shift;

    my ($phpShortVersion) = $phpVersion =~ /(php-5\.[2-6])/;

    debug(sprintf('Executing the configure-$phpShortVersion-stamp target for %s', $phpShortVersion, $phpVersion));

    my $stderr;
    (execute("make -f $MAINTDIR/Makefile configure-$phpShortVersion-stamp", undef, \$stderr) == 0) or fatal(
        sprintf("An error occurred during %s configuration process: %s\n", $phpVersion, $stderr)
    );
}

# Compile the given PHP version
sub compile
{
    my $phpVersion = shift;

    my ($phpShortVersion) = $phpVersion =~ /(php-5\.[2-6])/;

    debug(sprintf('Executing the build-$phpShortVersion-stamp target for %s', $phpShortVersion, $phpVersion));

    my $stderr;
    (execute("make -f $MAINTDIR/Makefile build--$phpShortVersion-stamp", undef, \$stderr) == 0) or fatal(
        sprintf("An error occurred during %s compilation process: %s\n", $phpVersion, $stderr)
    );
}

# Install the given PHP version
sub install
{
    my $phpVersion = shift;

    my ($phpShortVersion) = $phpVersion =~ /(php-5\.[2-6])/;

    debug(sprintf('Executing the install-$phpShortVersion-stamp target for %s', $phpShortVersion, $phpVersion));

    my $stderr;
    (execute("make -f $MAINTDIR/Makefile install-$phpShortVersion-stamp", undef, \$stderr) == 0) or fatal(
        sprintf("An error occurred during %s installation process: %s\n", $phpVersion, $stderr)
    );
}

1;
