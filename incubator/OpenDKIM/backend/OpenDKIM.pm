=head1 NAME

 Plugin::OpenDKIM

=cut

# i-MSCP OpenDKIM plugin
# Copyright (C) 2013-2017 Laurent Declercq <l.declercq@nuxwin.com>
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

package Plugin::OpenDKIM;

use strict;
use warnings;
use Capture::Tiny; # Preloading is really needed here due to uid/gid change in _addDomain()
use iMSCP::Database;
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::Dir;
use iMSCP::Execute qw/ execute /;
use iMSCP::File;
use iMSCP::Service;
use iMSCP::TemplateParser qw/ getBloc process replaceBloc /;
use iMSCP::Umask;
use iMSCP::Rights;
use iMSCP::SystemUser;
use JSON;
use Servers::mta;
use Text::Balanced qw/ extract_multiple extract_delimited /;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP OpenDKIM plugin.

=head1 PUBLIC METHODS

=over 4

=item uninstall( )

 Perform uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
    my ($self) = @_;

    eval {
        debug( 'Scheduling deletion of all DKIM DNS records' );
        local $self->{'dbh'}->{'RaiseError'} = 1;
        $self->{'dbh'}->do( "DELETE FROM domain_dns WHERE owned_by = 'OpenDKIM_Plugin'" );
        debug( "Removing OpenDKIM  $self->{'config_prev'}->{'opendkim_confdir'} directory" );
        iMSCP::Dir->new( dirname => $self->{'config_prev'}->{'opendkim_confdir'} )->remove();
        $self->_uninstalllDistributionPackages();
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item update( $fromVersion )

 Perform update tasks

 Param string $fromVersion Version from which the plugin is being updated
 Return int 0 on success, other on failure

=cut

sub update
{
    my ($self, $fromVersion) = @_;

    local $@;
    eval {
        $fromVersion = version->parse( $fromVersion );

        return 0 if $fromVersion >= version->parse( '2.0.0' );

        if ( $fromVersion < version->parse( '1.1.0' ) ) {
            debug( 'Processing update routines for versions older than 1.1.0' );

            if ( defined $self->{'config_prev'}->{'opendkim_port'} ) {
                debug( 'Removing Postfix MILTER value from opendkim_port (prev)' );
                my @milterPrevValues = ( qr/\Qinet:localhost:$self->{'config_prev'}->{'opendkim_port'}\E/ );
                Servers::mta->factory()->postconf(
                    (
                        smtpd_milters     => {
                            action => 'remove',
                            values => [ @milterPrevValues ]
                        },
                        non_smtpd_milters => {
                            action => 'remove',
                            values => [ @milterPrevValues ]
                        }
                    )
                ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
            }
        }

        if ( $fromVersion < version->parse( '1.1.1' ) ) {
            debug( 'Processing update routines for versions older than 1.1.1' );
            debug( 'Removing possible opendkim_feature orphaned DNS record entries' );
            local $self->{'dbh'}->{'RaiseError'} = 1;
            $self->{'dbh'}->do( "DELETE FROM domain_dns WHERE owned_by = 'opendkim_feature'" );
        }

        if ( $fromVersion < version->parse( '2.0.0' ) ) {
            debug( 'Processing update routines for versions older than 2.0.0' );

            if ( defined $self->{'config_prev'}->{'PostfixMilterSocket'} ) {
                debug( 'Setting postfix_milter_socket (prev) to PostfixMilterSocket (prev) value for update process' );
                $self->{'config_prev'}->{'postfix_milter_socket'} = $self->{'config_prev'}->{'PostfixMilterSocket'};
            }

            debug( 'Removing old opendkim rundir' );
            iMSCP::Dir->new( dirname => '/var/www/spool/postfix/opendkim' )->remove();
            debug( 'Setting opendkim_adsp (prev) to FALSE for update time. Ensure addition of new ADSP DNS records' );
            $self->{'config_prev'}->{'opendkim_adsp'} = JSON::false;
        }
    };
    if ( $@ ) {
        error( $@ );
        return;
    }

    0;
}

=item change( )

 Perform change tasks

 Return int 0 on success, other on failure

=cut

sub change
{
    my ($self) = @_;

    eval {
        if ( -d "$self->{'config'}->{'opendkim_confdir'}/keys" ) {
            debug( "Fixing permissions on $self->{'config'}->{'opendkim_confdir'}/keys directory" );
            setRights(
                "$self->{'config'}->{'opendkim_confdir'}/keys",
                {
                    user      => $self->{'config'}->{'opendkim_user'},
                    group     => $self->{'config'}->{'opendkim_group'},
                    filemode  => '0600',
                    dirmode   => '0700',
                    recursive => 1
                }
            ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
        }

        local $self->{'dbh'}->{'RaiseError'} = 1;

        if ( $self->{'config'}->{'opendkim_dns_records_ttl'} ne
            $self->{'config_prev'}->{'opendkim_dns_records_ttl'}
        ) {
            # TTL for DNS records has been changed
            debug( "Updating DKIM DNS records TTL" );
            $self->{'dbh'}->do(
                "
                    UPDATE domain_dns SET domain_dns = CONCAT(SUBSTRING_INDEX(domain_dns, ' ', 1), ' ', ?),
                        domain_dns_status = 'tochange'
                    WHERE owned_by = 'OpenDKIM_Plugin'
                ",
                undef, $self->{'config'}->{'opendkim_dns_records_ttl'}
            );
        }

        if ( !$self->{'config'}->{'opendkim_adsp'}
            && $self->{'config_prev'}->{'opendkim_adsp'}
        ) {
            # ADSP extension has been disabled
            debug( 'Removing DKIM ADSP DNS records' );
            $self->{'dbh'}->do(
                "
                    UPDATE domain_dns SET domain_dns_status = 'todelete'
                    WHERE domain_dns LIKE '\\_adsp%'
                    AND owned_by = 'OpenDKIM_Plugin'
                "
            );
            return;
        }

        if ( $self->{'config'}->{'opendkim_adsp'}
            && $self->{'config_prev'}->{'opendkim_adsp'}
            && $self->{'config'}->{'opendkim_adsp_signing_practice'} ne
            $self->{'config_prev'}->{'opendkim_adsp_signing_practice'}
        ) {
            # ADSP signing practice has been changed
            debug( 'Updating DKIM ADSP DNS records' );
            $self->{'dbh'}->do(
                "
                    UPDATE domain_dns SET domain_dns = ?, domain_dns_status = 'tochange'
                    WHERE domain_dns LIKE '\\_adsp%'
                    AND domain_dns_status <> 'todelete'
                    AND owned_by = 'OpenDKIM_Plugin'
                ",
                undef, qq/"dkim=$self->{'config'}->{'opendkim_adsp_signing_practice'}"/
            );
            return;
        }

        if ( $self->{'config'}->{'opendkim_adsp'}
            && !$self->{'config_prev'}->{'opendkim_adsp'}
        ) {
            # ADSP extension has been enabled
            debug( 'Adding DKIM ADSP DNS records' );
            $self->{'dbh'}->do(
                "
                    INSERT IGNORE INTO domain_dns (
                        domain_id, alias_id, domain_dns, domain_class, domain_type, domain_text, owned_by,
                        domain_dns_status
                    ) SELECT
                        domain_id, IFNULL(alias_id, 0), CONCAT('_adsp._domainkey.', domain_name, '. ', ?),
                        'IN', 'TXT', ?, 'OpenDKIM_Plugin', 'toadd'
                    FROM opendkim
                    WHERE opendkim_status <> 'todelete'
                ",
                undef, $self->{'config'}->{'opendkim_dns_records_ttl'},
                qq/"dkim=$self->{'config'}->{'opendkim_adsp_signing_practice'}"/
            );
        }
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item enable( )

 Perform enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
    my ($self) = @_;

    local $@;
    eval {
        $self->_installDistributionPackages() if grep( $_ eq $self->{'action'}, 'install', 'update' );
        $self->_opendkimSetup( 'configure' );
        $self->_postfixSetup( 'configure' );
        $self->_addMissingOpenDKIMEntries();
        local $self->{'dbh'}->{'RaiseError'} = 1;
        $self->{'dbh'}->do(
            "
                UPDATE domain_dns SET domain_dns_status = 'toenable'
                WHERE domain_dns_status <> 'todelete'
                AND owned_by = 'OpenDKIM_Plugin'
            "
        );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item disable( )

 Perform disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
    my ($self) = @_;

    local $@;
    eval {
        local $self->{'dbh'}->{'RaiseError'} = 1;
        $self->{'dbh'}->do(
            "
                UPDATE domain_dns SET domain_dns_status = 'todisable'
                WHERE domain_dns_status <> 'todelete'
                AND owned_by = 'OpenDKIM_Plugin'"
        );
        $self->_postfixSetup( 'deconfigure' );
        $self->_opendkimSetup( 'deconfigure' );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item run( )

 Process OpenDKIM entries

 Return int 0 on success, other on failure

=cut

sub run
{
    my ($self) = @_;

    local $@;
    eval {
        local $self->{'dbh'}->{'RaiseError'} = 1;
        my $sth = $self->{'dbh'}->prepare(
            "
                SELECT opendkim_id, domain_id, IFNULL(alias_id, 0) AS alias_id, domain_name, is_subdomain,
                    opendkim_status
                FROM opendkim
                WHERE opendkim_status IN('toadd', 'tochange', 'todelete')
            "
        );
        $sth->execute();

        while ( my $row = $sth->fetchrow_hashref() ) {
            eval {
                if ( $row->{'opendkim_status'} =~ /^to(?:add|change)$/ ) {
                    $self->_addDomain( $row );
                    $self->{'dbh'}->do(
                        "UPDATE opendkim SET opendkim_status = 'ok' WHERE opendkim_id = ?", undef, $row->{'opendkim_id'}
                    );
                    return;
                }

                $self->_deleteDomain( $row );
                $self->{'dbh'}->do( 'DELETE FROM opendkim WHERE opendkim_id = ?', undef, $row->{'opendkim_id'} );
            };
            if ( $@ ) {
                $self->{'dbh'}->do(
                    'UPDATE opendkim SET opendkim_status = ? WHERE opendkim_id = ?',
                    undef, ( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' ),
                    $row->{'opendkim_id'}
                );
            }
        }

        my $serviceMngr = iMSCP::Service->getInstance();
        # Under Ubuntu 14.04/Trusty Thar, status command always return 0
        $serviceMngr->getProvider()->setPidPattern( 'opendkim' );
        $serviceMngr->reload( 'opendkim' );
    };
    if ( $@ ) {
        $self->{'FORCE_RETVAL'} = 1;
        error( $@ );
        return 1;
    }

    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init( )

 Initialize plugin

 Return Plugin::OpenDKIM, die on failure

=cut

sub _init
{
    my ($self) = @_;

    for ( qw/
        opendkim_adsp opendkim_adsp_action opendkim_adsp_no_such_domain opendkim_adsp_signing_practice
        opendkim_canonicalization opendkim_confdir opendkim_dns_records_ttl opendkim_keysize opendkim_rundir
        opendkim_socket opendkim_user opendkim_group opendkim_trusted_hosts plugin_working_level postfix_rundir
        postfix_milter_socket
        /
    ) {
        defined $self->{'config'}->{$_} or die(
            sprintf( "Missing or undefined `%s' plugin configuration parameter", $_ )
        );

        for my $config( qw/ config config_prev / ) {
            next if ref $self->{$config}->{$_} eq 'ARRAY';
            $self->{$config}->{$_} =~ s/%(.*?)%/$self->{$config}->{$1}/ge;
        }
    }

    $self->{'dbh'} = iMSCP::Database->factory()->getRawDb();
    $self;
}

=item _installDistributionPackages( )
 
 Install OpenDKIM distribution packages

 Return void, die on failure

=cut

sub _installDistributionPackages
{
    debug( 'Installing distribution packages' );
    local $ENV{'DEBIAN_FRONTEND'} = 'noninteractive';
    my $stderr;
    execute( [ 'apt-get', 'update' ], \my $stdout, \$stderr ) == 0 or die(
        sprintf( "Couldn't update APT index: %s", $stderr || 'Unknown error' )
    );
    debug( $stdout ) if $stdout;
    execute(
        [
            'apt-get', '-o', 'DPkg::Options::=--force-confold', '-o', 'DPkg::Options::=--force-confdef',
            '-o', 'DPkg::Options::=--force-confmiss', '--assume-yes', '--auto-remove', '--no-install-recommends',
            '--purge', '--quiet', 'install', 'opendkim', 'opendkim-tools'
        ],
        \$stdout, \$stderr
    ) == 0 or die( sprintf( "Couldn't install distribution packages: %s", $stderr || 'Unknown error' ));
    debug( $stdout ) if $stdout;
}

=item _uninstalllDistributionPackages()

 Uninstall OpenDKIM distribution packages

 Return void, die on failure

=cut

sub _uninstalllDistributionPackages
{
    debug( 'Uninstalling distribution packages' );
    local $ENV{'DEBIAN_FRONTEND'} = 'noninteractive';
    my $stderr;
    execute(
        [ 'apt-get', '--assume-yes', '--auto-remove', '--purge', '--quiet', 'remove', 'opendkim', 'opendkim-tools' ],
        \my $stdout, \$stderr
    ) == 0 or die( sprintf( "Couldn't uninstall distribution packages: %s", $stderr || 'Unknown error' ));
    debug( $stdout ) if $stdout;
}

=item _opendkimSetup( $action )

 Configure or deconfigure OpenDKIM

 Param string $action Action to be performed (configure|deconfigure)
 Return void, die on failure

=cut

sub _opendkimSetup
{
    my ($self, $action) = @_;

    defined $action && grep($_ eq $action, 'configure', 'deconfigure') or die(
        'Missing or invalid $action parameter'
    );

    if ( $action eq 'configure' ) {
        for( $self->{'config'}->{'opendkim_confdir'}, "$self->{'config'}->{'opendkim_confdir'}/keys", ) {
            debug( "Creating $_ OpenDKIM directory" );
            iMSCP::Dir->new( dirname => $_ )->make( {
                user           => $self->{'config'}->{'opendkim_user'},
                group          => $self->{'config'}->{'opendkim_group'},
                mode           => 0750,
                fixpermissions => 1
            } );
        }

        debug( "Creating $self->{'config'}->{'postfix_rundir'} Postfix directory" );
        iMSCP::Dir->new( dirname => $self->{'config'}->{'postfix_rundir'} )->make( {
            user           => $main::imscpConfig{'ROOT_USER'},
            group          => $main::imscpConfig{'ROOT_GROUP'},
            mode           => 0755,
            fixpermissions => 1
        } );

        debug( "Creating $self->{'config'}->{'opendkim_rundir'} OpenDKIM directory" );
        iMSCP::Dir->new( dirname => $self->{'config'}->{'opendkim_rundir'} )->make( {
            user           => $self->{'config'}->{'opendkim_user'},
            group          => $self->{'config'}->{'opendkim_group'},
            mode           => 0750,
            fixpermissions => 1
        } );

        for( qw/ KeyTable SigningTable TrustedHosts / ) {
            debug( "Creating $self->{'config'}->{'opendkim_confdir'}/$_ OpenDKIM file" );
            my $file = iMSCP::File->new( filename => "$self->{'config'}->{'opendkim_confdir'}/$_" );
            $file->set( join( "\n", @{$self->{'config'}->{'opendkim_trusted_hosts'}} ) . "\n" ) if $_ eq 'TrustedHosts';
            $file->save() == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
            $file->mode( 0640 ) == 0 or die( getMessageByType(
                'error', { amount => 1, remove => 1 } ) || 'Unknown error'
            );
            $file->owner( $self->{'config'}->{'opendkim_user'}, $self->{'config'}->{'opendkim_group'} ) == 0 or die(
                getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
            );
        }
    } else {
        debug( "Stopping/Disabling OpenDKIM service" );
        my $serviceMngr = iMSCP::Service->getInstance();
        # Under Ubuntu 14.04/Trusty Thar, status command always return 0
        $serviceMngr->getProvider()->setPidPattern( 'opendkim' );
        $serviceMngr->stop( 'opendkim' );
        $serviceMngr->disable( 'opendkim' );

        iMSCP::Dir->new( dirname => $self->{'config_prev'}->{'opendkim_rundir'} )->remove();

        for( qw/ KeyTable SigningTable TrustedHosts / ) {
            next unless -f "$self->{'config_prev'}->{'opendkim_confdir'}/$_";
            debug( "Removing $self->{'config'}->{'opendkim_confdir'}/$_ OpenDKIM file" );
            iMSCP::File->new(
                filename => "$self->{'config_prev'}->{'opendkim_confdir'}/$_"
            )->delFile() == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
        }
    }

    my $file = iMSCP::File->new( filename => '/etc/default/opendkim' );
    my $fContent = '';

    if ( -f $file->{'filename'} ) {
        $fContent = $file->get();
        defined $fContent or die( sprintf( "Couldn't read %s file", $file->{'filename'} ));
    }

    if ( $action eq 'configure' ) {
        debug( "Updating OpenDKIM /etc/default/opendkim file" );
        # Needed to overrride group in sysvinit script
        my $DAEMON_OPTS = ( !iMSCP::Service->getInstance()->isSystemd() || !-f '/lib/systemd/system/opendkim.service' )
            ? "-u $self->{'config'}->{'opendkim_user'}:$self->{'config'}->{'opendkim_group'}" : '';

        my $cfg = <<"EOF";
# Begin Plugin::OpenDKIM
DAEMON_OPTS="$DAEMON_OPTS"
RUNDIR=$self->{'config'}->{'opendkim_rundir'}
SOCKET=$self->{'config'}->{'opendkim_socket'}
USER=$self->{'config'}->{'opendkim_user'}
GROUP=$self->{'config'}->{'opendkim_group'}
PIDFILE=$self->{'config'}->{'opendkim_rundir'}/\$NAME.pid
EXTRAAFTER=
# Ending Plugin::OpenDKIM
EOF
        if ( getBloc( "# Begin Plugin::OpenDKIM\n", "# Ending Plugin::OpenDKIM\n", $fContent ) ne '' ) {
            $fContent = replaceBloc( "# Begin Plugin::OpenDKIM\n", "# Ending Plugin::OpenDKIM\n", $cfg, $fContent );
        } else {
            $fContent .= $cfg;
        }
    } else {
        $fContent = replaceBloc( "# Begin Plugin::OpenDKIM\n", "# Ending Plugin::OpenDKIM\n", '', $fContent );
    }

    $file->set( $fContent );
    $file->save() == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

    # OpenDKIM Systemd configuration

    if ( iMSCP::Service->getInstance()->isSystemd() ) {
        # Make sure that the Systemd opendkim.service conffile has not been
        # redefined in old fashion way
        if ( -f '/etc/systemd/system/opendkim.service' ) {
            debug( "Removing old fashion /etc/systemd/system/opendkim.service systemd file" );
            iMSCP::File->new( filename => '/etc/systemd/system/opendkim.service' )->delFile() == 0 or die(
                getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
            );
        }

        # Make sure to start with clean setup
        debug( "Removing /etc/systemd/system/opendkim.service.d systemd directory" );
        iMSCP::Dir->new( dirname => '/etc/systemd/system/opendkim.service.d' )->remove();

        if ( $action eq 'configure' ) {
            if ( -x '/lib/opendkim/opendkim.service.generate' ) {
                debug( "Generating OpenDKIM system override.conf file" );
                # Override the default systemd configuration for OpenDKIM by
                # generating the /etc/systemd/system/opendkim.service.d/override.conf
                # and /etc/tmpfiles.d/opendkim.conf files, according changes made in
                # the /etc/default/opendkim file.
                my $stderr;
                execute( '/lib/opendkim/opendkim.service.generate', \my $stdout, \$stderr ) == 0 or die(
                    $stderr || 'Unknown error'
                );
                debug( $stdout ) if $stdout;
            } elsif ( -f '/lib/systemd/system/opendkim.service' ) {
                debug( "Generating OpenDKIM system override.conf file" );
                # Make use of our own systemd override.conf file (Ubuntu 16.04)
                iMSCP::Dir->new( dirname => '/etc/systemd/system/opendkim.service.d' )->make();

                $file = iMSCP::File->new(
                    filename => "$main::imscpConfig{'PLUGINS_DIR'}/OpenDKIM/systemd/override.conf"
                );
                $fContent = $file->get();
                defined $fContent or die( sprintf( "Couldn't read %s file", $file->{'filename'} ));

                $file->set( process(
                    {
                        OPENDKIM_RUNDIR => $self->{'config'}->{'opendkim_rundir'},
                        OPENDKIM_USER   => $self->{'config'}->{'opendkim_user'},
                        OPENDKIM_GROUP  => $self->{'config'}->{'opendkim_group'}
                    },
                    $fContent
                ));

                $file->{'filename'} = '/etc/systemd/system/opendkim.service.d/override.conf';
                $file->save() == 0 or die(
                    getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
                );
            }
        }
    }

    $file = iMSCP::File->new( filename => '/etc/opendkim.conf' );
    $fContent = $file->get();
    defined $fContent or die( sprintf( "Couldn't read %s file", $file->{'filename'} ));

    if ( $action eq 'configure' ) {
        debug( "Updating OpenDKIM configuration" );
        my $cfg = <<"EOF";
Canonicalization    $self->{'config'}->{'opendkim_canonicalization'}
ExternalIgnoreList  $self->{'config'}->{'opendkim_confdir'}/TrustedHosts
InternalHosts       $self->{'config'}->{'opendkim_confdir'}/TrustedHosts
KeyTable            file:$self->{'config'}->{'opendkim_confdir'}/KeyTable
LogWhy              no
MinimumKeyBits      1024
Mode                $self->{'config'}->{'opendkim_operating_mode'}
QueryCache          no
UMask               0117
RequireSafeKeys     yes
SignatureAlgorithm  rsa-sha256
SigningTable        file:$self->{'config'}->{'opendkim_confdir'}/SigningTable
SoftwareHeader      yes
Syslog              yes
SyslogSuccess       yes
EOF
        if ( $self->{'config'}->{'opendkim_adsp_action'} ne 'none' ) {
            $cfg .= "ADSPAction          $self->{'config'}->{'opendkim_adsp_action'}\n";
        }

        if ( $self->{'config'}->{'opendkim_adsp_no_such_domain'} ) {
            $cfg .= "ADSPNoSuchDomain          yes\n";
        } else {
            $cfg .= "ADSPNoSuchDomain          no\n";
        }

        if ( getBloc( "# Begin Plugin::OpenDKIM\n", "# Ending Plugin::OpenDKIM\n", $fContent ) ne '' ) {
            $fContent = replaceBloc( "# Begin Plugin::OpenDKIM\n", "# Ending Plugin::OpenDKIM\n", <<"EOF", $fContent );
# Begin Plugin::OpenDKIM
$cfg
# Ending Plugin::OpenDKIM
EOF
        } else {
            $fContent .= "# Begin Plugin::OpenDKIM\n$cfg# Ending Plugin::OpenDKIM\n";
        }
    } else {
        debug( "Resetting OpenDKIM configuration" );
        $fContent = replaceBloc( "# Begin Plugin::OpenDKIM\n", "# Ending Plugin::OpenDKIM\n", '', $fContent );
    }

    $file->set( $fContent );
    $file->save() == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
    return unless $action eq 'configure';

    $self->_resumeDomainSigningEntries();

    my $serviceTasksSub = sub {
        eval {
            debug( "Enabling/Starting OpenDKIM service" );
            my $serviceMngr = iMSCP::Service->getInstance();
            $serviceMngr->enable( 'opendkim' );
            # Under Ubuntu 14.04/Trusty Thar, status command always return 0
            $serviceMngr->getProvider()->setPidPattern( 'opendkim' );
            $serviceMngr->start( 'opendkim' );
        };
        if ( $@ ) {
            error( $@ );
            return 1;
        }
        0;
    };

    if ( defined $main::execmode && $main::execmode eq 'setup' ) {
        $self->{'eventManager'}->register(
            'beforeSetupRestartServices',
            sub {
                unshift @{$_[0]}, [ $serviceTasksSub, 'OpenDKIM' ];
                0;
            }
        ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
        return;
    }

    $serviceTasksSub->() == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
}

=item _postfixSetup( $action )

 Configure or deconfigure Postfix

 Param string $action Action to perform (configure|deconfigure)
 Return void, die on failure

=cut

sub _postfixSetup
{
    my ($self, $action) = @_;

    defined $action && grep($_ eq $action, 'configure', 'deconfigure') or die(
        'Missing or invalid $action parameter'
    );

    debug( "Removing OpenDKIM configuration for Postfix" );
    my @milterPrevValues = ( qr/\Q$self->{'config_prev'}->{'postfix_milter_socket'}\E/ );
    my $mta = Servers::mta->factory();
    $mta->postconf(
        (
            smtpd_milters     => {
                action => 'remove',
                values => [ @milterPrevValues ]
            },
            non_smtpd_milters => {
                action => 'remove',
                values => [ @milterPrevValues ]
            }
        )
    ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

    if ( $action eq 'deconfigure' ) {
        iMSCP::SystemUser->new()->removeFromGroup(
            $self->{'config'}->{'opendkim_group'}, $mta->{'config'}->{'POSTFIX_USER'}
        ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

        return if defined $main::execmode && $main::execmode eq 'setup';

        # On deconfigure action, we reload postfix immediately, as the
        # OpenDKIM service will become unavailable
        Servers::mta::factory()->reload() == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error'
        );
        return;
    }

    iMSCP::SystemUser->new()->addToGroup(
        $self->{'config'}->{'opendkim_group'}, $mta->{'config'}->{'POSTFIX_USER'}
    ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );

    debug( "Adding OpenDKIM configuration for Postfix" );
    $mta->postconf(
        (
            milter_default_action => {
                action => 'replace',
                values => [ 'tempfail' ]
            },
            smtpd_milters         => {
                action => 'add',
                values => [ $self->{'config'}->{'postfix_milter_socket'} ]
            },
            non_smtpd_milters     => {
                action => 'add',
                values => [ $self->{'config'}->{'postfix_milter_socket'} ]
            }
        )
    ) == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
}

=item _addDomain( \%data )

 Enable OpenDKIM support for the given domain

 Param hashref \%data Domain data
 Return void, die on failure

=cut

sub _addDomain
{
    my ($self, $data) = @_;

    if ( $data->{'opendkim_status'} eq 'tochange' ) {
        # tochange = Key renewal
        $self->_deleteDomain( $data );
    }

    my $txtRecord;

    unless ( $data->{'is_subdomain'} ) {
        debug( "Creating OpenDKIM key directory for the $data->{'domain_name'} domain" );
        iMSCP::Dir->new( dirname => "$self->{'config'}->{'opendkim_confdir'}/keys/$data->{'domain_name'}" )->make( {
            user           => $self->{'config'}->{'opendkim_user'},
            group          => $self->{'config'}->{'opendkim_group'},
            mode           => 0750,
            fixpermissions => 1
        } );

        {
            debug( "Generating DKIM key for the $data->{'domain_name'} domain" );
            local $) = getgrnam( $self->{'config'}->{'opendkim_user'} ) || die( "Couldn't setgid: %s", $! );
            local $> = getpwnam( $self->{'config'}->{'opendkim_group'} ) || die( "Couldn't setuid: %s:", $! );
            local $UMASK = 027;

            my $stderr;
            execute(
                [
                    '/usr/bin/opendkim-genkey', '-a', '-b', $self->{'config'}->{'opendkim_keysize'}, '-h', 'sha256',
                    '-D', "/etc/opendkim/keys/$data->{'domain_name'}", '-r', '-s', 'mail', '-d', $data->{'domain_name'}
                ],
                \my $stdout, \$stderr
            ) == 0 or die ( $stderr || 'Unknown error' );
            debug( $stdout ) if $stdout;
        }

        $self->_addDomainSigningEntry( $data->{'domain_name'} );
        $self->_addDomainKeyEntry( $data->{'domain_name'} );

        my $file = iMSCP::File->new(
            filename => "$self->{'config'}->{'opendkim_confdir'}/keys/$data->{'domain_name'}/mail.txt"
        );
        my $fContent = $file->get();
        defined $fContent or die( sprintf( "Couldn't read %s file", $file->{'filename'} ));

        # Extract all quoted <character-string>s, excluding delimiters
        $_ =~ s/^"(.*)"$/$1/ for my @txtRecordChunks = extract_multiple(
            $fContent, [ sub { extract_delimited( $_[0], '"' ) } ], undef, 1
        );

        $txtRecord = join '', @txtRecordChunks;

        # Split data field into several <character-string>s when <character-string>
        # is longer than 255 bytes, excluding delimiters.
        # See: https://tools.ietf.org/html/rfc4408#section-3.1.3
        if ( length $txtRecord > 255 &&
            # i-MSCP versions prior 1.5.0 (plugin API < 1.5.0) don't support
            # handling of multiple <character-string>s. The backend assumes only one
            # quoted <character-string> and turn it into multiple character-strings
            # when necessary.
            version->parse( "$main::imscpConfig{'PluginApi'}" ) >= version->parse( '1.5.0' )
        ) {
            undef @txtRecordChunks;
            for ( my $i = 0, my $length = length $txtRecord; $i < $length; $i += 255 ) {
                push @txtRecordChunks, substr( $txtRecord, $i, 255 );
            }

            $txtRecord = join ' ', map( qq/"$_"/, @txtRecordChunks );
        } else {
            $txtRecord = qq/"$txtRecord"/;
        }
    }

    return if $data->{'is_subdomain'} && !$self->{'config'}->{'opendkim_adsp'};

    eval {
        local $self->{'dbh'}->{'RaiseError'} = 1;
        $self->{'dbh'}->begin_work();
        unless ( $data->{'is_subdomain'} ) {
            debug( "Adding DKIM DNS record for the $data->{'domain_name'} domain" );
            $self->{'dbh'}->do(
                "
                    INSERT IGNORE INTO domain_dns (
                        domain_id, alias_id, domain_dns, domain_class, domain_type, domain_text, owned_by,
                        domain_dns_status
                    ) VALUES (
                        ?, ?, ?, 'IN', 'TXT', ?, 'OpenDKIM_Plugin', 'toadd'
                    )
                ",
                undef, $data->{'domain_id'}, $data->{'alias_id'},
                qq/mail._domainkey $self->{'config'}->{'opendkim_dns_records_ttl'}/, $txtRecord
            )
        }

        if ( $self->{'config'}->{'opendkim_adsp'} ) {
            debug( "Adding DKIM ADSP DNS record for the $data->{'domain_name'} domain" );
            $self->{'dbh'}->do(
                "
                    INSERT IGNORE INTO domain_dns (
                        domain_id, alias_id, domain_dns, domain_class, domain_type, domain_text, owned_by,
                        domain_dns_status
                    ) VALUES (
                        ?, ?, ?, 'IN', 'TXT', ?, 'OpenDKIM_Plugin', 'toadd'
                    )
                ",
                undef, $data->{'domain_id'}, $data->{'alias_id'},
                qq/_adsp._domainkey.$data->{'domain_name'}. $self->{'config'}->{'opendkim_dns_records_ttl'}/,
                qq/"dkim=$self->{'config'}->{'opendkim_adsp_signing_practice'}"/
            );
        }

        $self->{'dbh'}->commit();
    };
    if ( $@ ) {
        $self->{'dbh'}->rollback();
        die;
    }
}

=item _deleteDomain( \%data )

 Remove openDKIM support for the given domain

 Param hashref \%data Domain data
 Return void, die on failure

=cut

sub _deleteDomain
{
    my ($self, $data) = @_;

    unless ( $data->{'is_subdomain'} || $data->{'opendkim_status'} eq 'tochange' ) {
        debug( "Removing OpenDKIM key directory for the $data->{'domain_name'} domain" );
        iMSCP::Dir->new( dirname => "$self->{'config'}->{'opendkim_confdir'}/keys/$data->{'domain_name'}" )->remove();
        $self->_deleteDomainSigningEntry( $data->{'domain_name'} );
        $self->_deleteDomainKeyEntry( $data->{'domain_name'} );
    }

    local $self->{'dbh'}->{'RaiseError'} = 1;

    if ( $data->{'is_subdomain'} ) {
        debug( "Removing DKIM ADSP DNS record for the $data->{'domain_name'} domain" );
        $self->{'dbh'}->do(
            "
                UPDATE domain_dns SET domain_dns_status = 'todelete'
                WHERE domain_id = ?
                AND alias_id = ?
                AND domain_dns LIKE ?
                AND owned_by = 'OpenDKIM_Plugin'
            ",
            undef, $data->{'domain_id'}, $data->{'alias_id'}, qq/\\_adsp.\\_domainkey.$data->{'domain_name'}.%/
        );
        return;
    }

    debug( "Removing DKIM DNS record for the $data->{'domain_name'} domain" );
    $self->{'dbh'}->do(
        "
            UPDATE domain_dns SET domain_dns_status = 'todelete'
            WHERE domain_id = ? AND alias_id = ? AND owned_by = 'OpenDKIM_Plugin'
        ",
        undef, $data->{'domain_id'}, $data->{'alias_id'}
    );
}

=item _addDomainSigningEntry( $domainName )

 Adds a domain signing entry in the signing table

 Param string $domainName Domain name
 Return void, die on failure

=cut

sub _addDomainSigningEntry
{
    my ($self, $domainName) = @_;

    debug( "Adding OpenDKIM signing entry for the $domainName domain" );
    my $file = iMSCP::File->new( filename => "$self->{'config'}->{'opendkim_confdir'}/SigningTable" );
    my $fContent = $file->get();
    defined $fContent or die( sprintf( "Couldn't read %s file", $file->{'filename'} ));
    $fContent =~ s/^\.?\Q$domainName\E\s[^\n]+\n//gm;
    $fContent .= "$domainName $domainName\n.$domainName .$domainName:\@%\n";
    $file->set( $fContent );
    $file->save() == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
}

=item _deleteDomainSigningEntry( $domainName )

 Deletes a domain signing entry from the signing table

 Param string $domainName Domain name
 Return void, die on failure

=cut

sub _deleteDomainSigningEntry
{
    my ($self, $domainName) = @_;

    debug( "Deleting OpenDKIM signing entry for the $domainName domain" );
    my $file = iMSCP::File->new( filename => "$self->{'config'}->{'opendkim_confdir'}/SigningTable" );
    my $fContent = $file->get();
    defined $fContent or die( sprintf( "Couldn't read %s file", $file->{'filename'} ));
    $file->set( $fContent =~ s/^\.?\Q$domainName\E\s[^\n]+\n//gmr );
    $file->save() == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
}

=item _addDomainKeyEntry( $domainName )

 Adds a domain key entry in the key table

 Param string $domainName Domain name
 Return void, die on failure

=cut

sub _addDomainKeyEntry
{
    my ($self, $domainName) = @_;

    debug( "Adding OpenDKIM key entry for the $domainName domain" );
    my $file = iMSCP::File->new( filename => "$self->{'config'}->{'opendkim_confdir'}/KeyTable" );
    my $fContent = $file->get();
    defined $fContent or die( sprintf( "Couldn't read %s file", $file->{'filename'} ));
    $fContent =~ s/^\.?\Q$domainName\E\s[^\n]+\n//gm;
    $fContent .= "$domainName %:mail:$self->{'config'}->{'opendkim_confdir'}/keys/%/mail.private\n";
    $fContent .= ".$domainName $domainName:mail:$self->{'config'}->{'opendkim_confdir'}/keys/$domainName/mail.private\n";
    $file->set( $fContent );
    $file->save() == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
}

=item _deleteDomainKeyEntry( $domainName )

 Deletes a domain key entry from the key table

 Param string $domainName Domain name
 Return void, die on failure

=cut

sub _deleteDomainKeyEntry
{
    my ($self, $domainName) = @_;

    debug( "Deleting OpenDKIM key entry for the $domainName domain" );
    my $file = iMSCP::File->new( filename => "$self->{'config'}->{'opendkim_confdir'}/KeyTable" );
    my $fContent = $file->get();
    defined $fContent or die( sprintf( "Couldn't read %s file", $file->{'filename'} ));
    $file->set( $fContent =~ s/^.?\Q$domainName\E\s[^\n]+\n//gmr );
    $file->save() == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
}

=item _resumeDomainSigningEntries( )

 Resume domain signing entries in the signing table
 
 Note: Also removes the keys that belong to domains that were removed while the
 plugin was deactivated.

 Return void, die on failure

=cut

sub _resumeDomainSigningEntries
{
    my ($self) = @_;

    return unless -d "$self->{'config_prev'}->{'opendkim_confdir'}/keys" &&
        !iMSCP::Dir->new( dirname => "$self->{'config'}->{'opendkim_confdir'}/keys" )->isEmpty();

    local $self->{'dbh'}->{'RaiseError'} = 1;
    my $domainNames = $self->{'dbh'}->selectcol_arrayref( 'SELECT domain_name FROM opendkim' );

    for my $domainName(
        iMSCP::Dir->new( dirname => "$self->{'config_prev'}->{'opendkim_confdir'}/keys" )->getDirs()
    ) {
        if ( grep($_ eq $domainName, @{$domainNames}) ) {
            $self->_addDomainSigningEntry( $domainName );
            $self->_addDomainKeyEntry( $domainName );
            next;
        }

        debug( "Removing orphaned OpenDKIM key for the $domainName domain" );
        iMSCP::Dir->new( dirname => "$self->{'config_prev'}->{'opendkim_confdir'}/keys/$domainName" )->remove();
    }
}

=item _addMissingOpenDKIMEntries

 Add missing OpenDKIM entries in database

 This covers the case where, depending on context, a domain, domain alias or
 subdomain has been added while the plugin was deactivated.

 Return void, die on failure

=cut

sub _addMissingOpenDKIMEntries
{
    my ($self) = @_;

    local $self->{'dbh'}->{'RaiseError'} = 1;

    my $sth;
    if ( $self->{'config'}->{'plugin_working_level'} eq 'admin' ) {
        $sth = $self->{'dbh'}->prepare(
            "SELECT domain_id, domain_admin_id AS admin_id FROM domain WHERE domain_status <> 'todelete'"
        );
    } else {
        $sth = $self->{'dbh'}->prepare(
            "SELECT admin_id, domain_id FROM opendkim WHERE opendkim_status <> 'todelete' GROUP BY admin_id, domain_id"
        )
    };

    $sth->execute();

    while ( my $row = $sth->fetchrow_hashref() ) {
        eval {
            $self->{'dbh'}->begin_work();
            if ( $self->{'config'}->{'plugin_working_level'} eq 'admin' ) {
                debug( "Adding missing OpenDKIM entries for domains of customer with ID $row->{'admin_id'}" );
                $self->{'dbh'}->do(
                    "
                        INSERT IGNORE INTO opendkim (admin_id, domain_id, domain_name, opendkim_status)
                        SELECT domain_admin_id, domain_id, domain_name, 'toadd'
                        FROM domain
                        WHERE domain_id = ?
                        AND domain_status <> 'todelete'
                    ",
                    undef, $row->{'domain_id'}
                );
            }

            debug( "Adding missing OpenDKIM entries for subdomains (sub) of customer with ID $row->{'admin_id'}" );
            $self->{'dbh'}->do(
                "
                    INSERT IGNORE INTO opendkim (
                        admin_id, domain_id, domain_name, is_subdomain, opendkim_status
                    ) SELECT t2.domain_admin_id, t1.domain_id, CONCAT(t1.subdomain_name, '.', t2.domain_name), 1,
                        'toadd'
                    FROM subdomain AS t1
                    JOIN domain AS t2 ON(t2.domain_id = t1.domain_id)
                    WHERE t1.domain_id = ?
                    AND t1.subdomain_status <> 'todelete'
                ",
                undef, $row->{'domain_id'}
            );

            debug( "Adding missing OpenDKIM entries for domain aliases of customer with ID $row->{'admin_id'}" );
            $self->{'dbh'}->do(
                "
                    INSERT IGNORE INTO opendkim (admin_id, domain_id, alias_id, domain_name, opendkim_status)
                    SELECT ?, domain_id, alias_id, alias_name, 'toadd'
                    FROM domain_aliasses
                    WHERE domain_id = ?
                    AND alias_status <> 'todelete'
                ",
                undef, $row->{'admin_id'}, $row->{'domain_id'}
            );

            debug( "Adding missing OpenDKIM entries for subdomains (alssub) of customer with ID $row->{'admin_id'}" );
            $self->{'dbh'}->do(
                "
                    INSERT IGNORE INTO opendkim (
                        admin_id, domain_id, alias_id, domain_name, is_subdomain, opendkim_status
                    ) SELECT ?, t2.domain_id, t1.alias_id, CONCAT(t1.subdomain_alias_name, '.', t2.alias_name), 1,
                        'toadd'
                    FROM subdomain_alias AS t1
                    JOIN domain_aliasses AS t2 ON(t2.alias_id = t1.alias_id)
                    WHERE t2.domain_id = ?
                    AND subdomain_alias_status <> 'todelete'
                ",
                undef, $row->{'admin_id'}, $row->{'domain_id'}
            );

            $self->{'dbh'}->commit();
        };
        if ( $@ ) {
            $self->{'dbh'}->rollback();
            die;
        }
    }

    $self->run() == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' );
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>
 Rene Schuster <mail@reneschuster.de>
 Sascha Bay <info@space2place.de>

=cut

1;
__END__
