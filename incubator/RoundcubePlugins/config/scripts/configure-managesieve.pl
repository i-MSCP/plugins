#!/usr/bin/env perl

package Plugin::RoundcubePlugins::Configure::Managesieve;

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2017 Laurent Declercq <l.declercq@nuxwin.com>
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License
# as published by the Free Software Foundation; either version 2
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

# Configuration script for the Roundcube managesieve plugin

use strict;
use warnings;
use Cwd qw/ realpath /;
use FindBin;
use lib
    realpath( "$FindBin::Bin/../../../../../engine/PerlLib" ),
    realpath( "$FindBin::Bin/../../../../../engine/PerlVendor" );
use File::Basename qw/ basename /;
use iMSCP::Bootstrapper;
use iMSCP::Debug qw/ debug getMessageByType /;
use iMSCP::Dir;
use iMSCP::Execute qw/ executeNoWait /;
use iMSCP::File;
use iMSCP::Getopt;
use iMSCP::Service;
use iMSCP::TemplateParser qw/ getBloc replaceBloc process /;
use POSIX qw / locale_h /;
use Servers::po;

setlocale( LC_MESSAGES, "C.UTF-8" );

$ENV{'LANG'} = 'C.UTF-8';
$ENV{'PATH'} = '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin';

iMSCP::Getopt->verbose( 1 );
iMSCP::Bootstrapper->getInstance()->boot(
    {
        nokeys          => 1,
        nodatabase      => 1,
        config_readonly => 1,
        nolock          => 1
    }
)->lock( "/var/lock/" . basename( $0, '.pl' ) . '.lock' );
iMSCP::Getopt->debug( 0 );

my $action = shift or die 'Action missing';

if ( !iMSCP::Service->getInstance()->hasService( 'dovecot' ) ) {
    die( 'dovecot service not found' );
}

if ( $action eq 'pre-configure' ) {
    local $ENV{'DEBIAN_FRONTEND'} = 'noninteractive';

    my $stderr = '';
    executeNoWait( [ 'apt-get', 'update' ], \&_std, sub { $stderr .= $_[0] } ) == 0 or die(
        sprintf( "Couldn't update APT index: %s", $stderr || 'Unknown error' )
    ) == 0 or die( sprintf( "Couldn't update APT index: %s", $stderr || 'Unknown error' ));
    executeNoWait(
        [
            'apt-get', '-o', 'DPkg::Options::=--force-confnew', '-o', 'DPkg::Options::=--force-confmiss',
            '--assume-yes', '--auto-remove', '--no-install-recommends', '--purge', '--quiet', 'install',
            'dovecot-sieve', 'dovecot-managesieved'
        ],
        \&_std,
        sub { $stderr .= $_[0] }
    ) == 0 or die( sprintf( "Couldn't install distribution packages: %s", $stderr || 'Unknown error' ));
    undef $stderr;
} elsif ( grep($action eq $_, 'configure', 'deconfigure') ) {
    &_configureDovecot;
    iMSCP::Service->getInstance()->restart( 'dovecot' );
}

sub _configureDovecot
{
    my $poSrv = Servers::po->factory();

    iMSCP::Dir->new( dirname => "$poSrv->{'config'}->{'DOVECOT_CONF_DIR'}/imscp.d" )->make();

    my $file = iMSCP::File->new(
        filename => "$poSrv->{'config'}->{'DOVECOT_CONF_DIR'}/imscp.d/imscp_managesieve.conf"
    );

    if ( -f $file->{'filename'} ) {
        $file->delFile() == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ));
    }

    return unless $action eq 'pre-configure';

    $file->set( <<'EOT' );
plugin {
    sieve = file:~/sieve;active=~/.dovecot.sieve
}

protocol lda {
    mail_plugins = $mail_plugins sieve
}

EOT
    $file->save() == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ));
}

sub _std
{
    debug( $_[0] ) if $_[0] =~ s///;
}

1;
