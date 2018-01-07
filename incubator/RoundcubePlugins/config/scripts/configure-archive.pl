#!/usr/bin/env perl

package Plugin::RoundcubePlugins::Configure::Archive;

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
use iMSCP::Servers::Po;

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
    exit;
}

if ( grep($action eq $_, 'configure', 'deconfigure') ) {
    &_configureDovecot;
    iMSCP::Service->getInstance()->restart( 'dovecot' );
}

sub _configureDovecot
{
    my $poSrv = iMSCP::Servers::Po->factory();

    iMSCP::Dir->new( dirname => "$poSrv->{'config'}->{'DOVECOT_CONF_DIR'}/imscp.d" )->make();

    my $file = iMSCP::File->new(
        filename => "$poSrv->{'config'}->{'DOVECOT_CONF_DIR'}/imscp.d/imscp_archive.conf"
    );

    if ( -f $file->{'filename'} ) {
        $file->delFile() == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ));
    }

    return unless $action eq 'configure';

    $file->set( <<'EOT' );
namespace inbox {
    mailbox Archive {
        auto = subscribe
        special_use = \Archive
    }
}
EOT
    $file->save() == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ));

    iMSCP::Service->getInstance()->reload( 'dovecot' ) if iMSCP::Service->getInstance()->hasService( 'dovecot' );
}

1;
