#!/usr/bin/perl
#
# i-MSCP SpamAssassin plugin
# Copyright (C) 2015-2019 Laurent Declercq <l.declercq@nuxwin.com>
# Copyright (C) 2013-2016 Rene Schuster <mail@reneschuster.de>
# Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>
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

# Script for SpamAssassin Bayesian learning.

use strict;
use warnings;
use FindBin;
use lib "$FindBin::Bin/../../../../engine/PerlLib", "$FindBin::Bin/../../../../engine/PerlVendor";
use iMSCP::Boolean;
use iMSCP::Bootstrapper;
use iMSCP::Debug qw/ newDebug setDebug setVerbose /;
use iMSCP::Execute 'execute';
use JSON;
use POSIX 'locale_h';

eval {
    @{ENV}{qw/ LANG PATH /} = (
        'C.UTF-8',
        '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin'
    );
    delete $ENV{'LANGUAGE'};

    setlocale( LC_MESSAGES, 'C.UTF-8' );
    newDebug( 'spamassassin-plugin-bayes-sa-learn.log' );
    setDebug( iMSCP::Getopt->debug( TRUE ));
    setVerbose( iMSCP::Getopt->verbose( TRUE ));

    iMSCP::Bootstrapper->getInstance()->lock(
        '/var/lock/spamassassin-plugin-bayes-sa-learn.lock', TRUE
    ) or exit;

    iMSCP::Bootstrapper->getInstance()->boot( {
        config_readonly => TRUE,
        nodatabase      => TRUE,
        nokeys          => TRUE,
        nolock          => TRUE,
        norequirements  => TRUE
    } );

    getPluginBackendInstance->bayesSaLearn() == 0 or die( getMessageByType(
        'error', { amount => 1, remove => TRUE }
    ) || 'Unknown error' );

    my ( $stdout, $stderr );
    my $saLearnDir = "$::imscpConfig{'GUI_ROOT_DIR'}/plugins/SpamAssassin/sa-learn";
    for my $message ( <$saLearnDir/*> ) {
        my ( $username, $learningMode ) = /^\Q$saLearnDir\E\/(.*)__(spam|ham)__.*/ or next;
        execute(
            [
                '/usr/bin/sa-learn',
                '--no-sync',
                "--$learningMode",
                '-u', $username,
                $message
            ],
            \$stdout,
            \$stderr
        ) == 0 or die(
            $stderr || 'Unknown error'
        );
        debug( $stdout ) if $stdout;
    }

    # Synchronize the database and the journal once per training session
    execute(
        [ '/usr/bin/sa-learn', '--sync' ],
        \$stdout,
        \$stderr
    ) == 0 or die( $stderr || 'Unknown error' );
    debug( $stdout ) if $stdout;
};
if ( $@ ) {
    error( $@ );
    exit 1;
}

1;
__END__
