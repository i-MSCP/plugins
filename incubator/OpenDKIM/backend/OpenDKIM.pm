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
use iMSCP::Database;
use iMSCP::Debug qw/ debug error getMessageByType getMessage /;
use iMSCP::Dir;
use iMSCP::Execute qw/ execute /;
use iMSCP::File;
use iMSCP::Service;
use iMSCP::TemplateParser qw/ getBloc process replaceBloc /;
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

    my $rs = $self->{'dbh'}->do( "DELETE FROM domain_dns WHERE owned_by = 'OpenDKIM_Plugin'" );
    unless ( defined $rs ) {
        error( $self->{'dbh'}->errstr );
        return 1;
    }

    local $@;
    eval { iMSCP::Dir->new( dirname => $self->{'config_prev'}->{'opendkim_confdir'} )->remove(); };
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

    $fromVersion = version->parse( $fromVersion );

    if ( $fromVersion < version->parse( '1.1.1' ) ) {
        # Fix bug in versions < 1.1.1 where the `owned_by' field for DKIM DNS resource records was reseted back to
        # `opendkim_feature', leading to orphaned custom DNS resource records
        eval {
            local $self->{'dbh'}->{'RaiseError'} = 1;
            $self->{'dbh'}->do( "DELETE FROM domain_dns WHERE owned_by = 'opendkim_feature'" );
        };
        if ( $@ ) {
            error( $@ );
            return 1;
        }
    }

    if ( $fromVersion < version->parse( '1.3.0' ) ) {
        eval { iMSCP::Dir->new( dirname => '/var/www/spool/postfix/opendkim' )->remove(); };
        if ( $@ ) {
            error( $@ );
            return 1;
        }

        if ( $self->{'config'}->{'opendkim_adsp_extension'} ) {
            # Add DNS resource record for DKIM ADSP (Author Domain Signing Practices) extension
            eval {
                local $self->{'dbh'}->{'AutoCommit'} = 0;
                local $self->{'dbh'}->{'RaiseError'} = 1;

                my $sth = $self->{'dbh'}->prepare(
                    "
                        SELECT domain_id, IFNULL(alias_id, 0) AS alias_id
                        FROM opendkim
                        WHERE opendkim_status <> 'todelete'
                    "
                );

                while ( my $row = $sth->fetchrow_hashref() ) {
                    $self->{'dbh'}->do(
                        "
                            INSERT IGNORE INTO domain_dns (
                                domain_id, alias_id, domain_dns, domain_class, domain_type, domain_text, owned_by,
                                domain_dns_status
                            ) VALUES (
                                ?, ?, '_adsp._domainkey 60', 'IN', 'TXT', ?, 'OpenDKIM_Plugin', 'toadd'
                            )
                        ",
                        undef,
                        $row->{'domain_id'},
                        $row->{'alias_id'},
                        '"dkim=' . $self->{'config'}->{'opendkim_adsp_signing_practice'} . '"'
                    );
                }

                $self->{'dbh'}->commit();
            };
            if ( $@ ) {
                $self->{'dbh'}->rollback();
                error( $@ );
                return 1;
            }
        }
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

    unless ( defined $main::execmode && $main::execmode eq 'setup'
        || !grep( $_ eq $self->{'action'}, 'install', 'update' )
    ) {
        my $rs = $self->_installDistributionPackages();
        return $rs if $rs;
    }

    my $rs = $self->_opendkimConfigure( 'configure' );
    $rs ||= $self->_postfixConfigure( 'configure' );
    return $rs if $rs;

    local $@;
    eval {
        local $self->{'dbh'}->{'RaiseError'} = 1;
        $self->{'dbh'}->do( "UPDATE domain_dns SET domain_dns_status = 'toenable' WHERE owned_by = 'OpenDKIM_Plugin'" );
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

    return 0 if defined $main::execmode && $main::execmode eq 'setup';

    local $@;
    eval {
        local $self->{'dbh'}->{'RaiseError'} = 1;
        $self->{'dbh'}->do(
            "UPDATE domain_dns SET domain_dns_status = 'todisable' WHERE owned_by = 'OpenDKIM_Plugin'"
        );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    my $rs = $self->_postfixConfigure( 'deconfigure' );
    $rs ||= $self->_opendkimConfigure( 'deconfigure' );
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
                SELECT opendkim_id, domain_id, IFNULL(alias_id, 0) AS alias_id, domain_name, opendkim_status
                FROM opendkim
                WHERE opendkim_status IN('toadd', 'tochange', 'todelete')
            "
        );

        while ( my $row = $sth->fetchrow_hashref() ) {
            my @sql;
            if ( $row->{'opendkim_status'} =~ /^to(?:add|change)$/ ) {
                my $rs = $self->_addDomainKey( $row->{'domain_id'}, $row->{'alias_id'}, $row->{'domain_name'} );
                @sql = (
                    'UPDATE opendkim SET opendkim_status = ? WHERE opendkim_id = ?',
                    undef,
                    ( $rs ? getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' : 'ok' ),
                    $row->{'opendkim_id'}
                );
            } elsif ( $row->{'opendkim_status'} eq 'todelete' ) {
                if ( $self->_deleteDomainKey( $row->{'domain_id'}, $row->{'alias_id'}, $row->{'domain_name'} ) ) {
                    @sql = (
                        'UPDATE opendkim SET opendkim_status = ? WHERE opendkim_id = ?',
                        undef,
                        ( getMessageByType( 'error', { amount => 1, remove => 1 } ) || 'Unknown error' ),
                        $row->{'opendkim_id'}
                    );
                } else {
                    @sql = ( 'DELETE FROM opendkim WHERE opendkim_id = ?', undef, $row->{'opendkim_id'} );
                }
            }

            $self->{'dbh'}->do( @sql );
        }

        iMSCP::Service->getInstance()->reload( 'opendkim' );
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

 Return Plugin::OpenDKIM or die on failure

=cut

sub _init
{
    my ($self) = @_;

    for (
        qw/ postfix_rundir postfix_milter_socket opendkim_adsp_extension opendkim_adsp_signing_practice opendkim_confdir
        opendkim_keysize opendkim_rundir opendkim_socket opendkim_user opendkim_group opendkim_canonicalization
        opendkim_trusted_hosts /
    ) {
        $self->{'config'}->{$_} or die( sprintf( "Missing or undefined `%s' plugin configuration parameter", $_ ));

        for my $config( qw/ config config_prev / ) {
            next if ref $self->{$config}->{$_} eq 'ARRAY';
            $self->{$config}->{$_} =~ s/%(.*?)%/$self->{$config}->{$1}/ge;
        }
    }

    $self->{'dbh'} = iMSCP::Database->factory()->getRawDb();
    $self;
}

=item _installDistributionPackages( )
 
 Install required distribution packages

 Return int 0 on success, other on failure

=cut

sub _installDistributionPackages
{
    local $ENV{'DEBIAN_FRONTEND'} = 'noninteractive';

    my $rs = execute( [ 'apt-get', 'update' ], \my $stdout, \my $stderr );
    debug( $stdout ) if $stdout;
    error( sprintf( "Couldn't update APT index: %s", $stderr || 'Unknown error' )) if $rs;
    return $rs if $rs;

    $rs = execute(
        [
            'apt-get', '-o', 'DPkg::Options::=--force-confold', '-o', 'DPkg::Options::=--force-confdef',
            '-o', 'DPkg::Options::=--force-confmiss', '--assume-yes', '--auto-remove', '--no-install-recommends',
            '--purge', '--quiet', 'install', 'opendkim', 'opendkim-tools'
        ],
        \$stdout,
        \$stderr
    );
    debug( $stdout ) if $stdout;
    error( sprintf( "Couldn't install distribution packages: %s", $stderr || 'Unknown error' )) if $rs;
    $rs;
}

=item _opendkimConfigure( $action )

 Configure or deconfigure OpenDKIM

 Param string $action Action to be performed (configure|deconfigure)
 Return int 0 on success, other on failure

=cut

sub _opendkimConfigure
{
    my ($self, $action) = @_;

    unless ( defined $action && grep($_ eq $action, 'configure', 'deconfigure') ) {
        error( 'Missing or invalid $action parameter' );
        return 1;
    }

    local $@;

    if ( $action eq 'deconfigure' ) {
        eval {
            my $serviceMngr = iMSCP::Service->getInstance();
            $serviceMngr->stop( 'opendkim' );
            $serviceMngr->disable( 'opendkim' );
        };
        if ( $@ ) {
            error( $@ );
            return 1;
        }
    }

    eval {
        # Postfix rundir
        iMSCP::Dir->new( dirname => $self->{'config'}->{'postfix_rundir'} )->make(
            user           => $main::imscpConfig{'ROOT_USER'},
            group          => $main::imscpConfig{'ROOT_GROUP'},
            mode           => 0755,
            fixpermissions => 1
        );

        # OpenDKIM directories
        for( $self->{'config'}->{'opendkim_confdir'}, "$self->{'config'}->{'opendkim_confdir'}/keys",
            $self->{'config'}->{'opendkim_rundir'}
        ) {
            my $dir = iMSCP::Dir->new( dirname => $_ );

            if ( $action eq 'configure' ) {
                $dir->make(
                    {
                        user           => $self->{'config'}->{'opendkim_user'},
                        group          => $self->{'config'}->{'opendkim_group'},
                        mode           => 0750,
                        fixpermissions => 1
                    }
                );
                next;
            }

            $dir->remove( $self->{'config_prev'}->{'opendkim_rundir'} ) if $_ eq $self->{'config'}->{'opendkim_rundir'};
        }
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    # OpenDKIM files
    for( qw/ KeyTable SigningTable TrustedHosts / ) {
        my $file = iMSCP::File->new( filename => "$self->{'config'}->{'opendkim_confdir'}/$_" );

        if ( $action eq 'configure' ) {
            $file->set( join "\n", @{$self->{'config'}->{'opendkim_trusted_hosts'}} ) if $_ eq 'TrustedHosts';

            my $rs = $file->save();
            $rs ||= $file->mode( 0640 );
            $rs ||= $file->owner( $self->{'config'}->{'opendkim_user'}, $self->{'config'}->{'opendkim_group'} );
            return $rs if $rs;
            next;
        }

        # We remove the file only on the 'disable" action, not on 'change' or
        # 'update' actions.
        #
        # Doing this necessarily means that if the administrator deactivates the
        # plugin, all keys will be renewed when the plugin will be reactivated.
        # We do not have the choice because if we don't remove the file, this
        # could lead to orphaned keys (case where a domain is being removed
        # while the plugin is deactivated).
        if ( $self->{'action'} eq 'disable' && -f "$self->{'config_prev'}->{'opendkim_confdir'}/$_" ) {
            $file->{'filename'} = "$self->{'config_prev'}->{'opendkim_confdir'}/$_";

            my $rs = $file->delFile();
            return $rs if $rs;
        }
    }

    # Create or update the /etc/default/opendkim configuration file
    my $file = iMSCP::File->new( filename => '/etc/default/opendkim' );
    my $fContent = $file->get() // '';

    if ( $action eq 'configure' ) {
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
    my $rs = $file->save();
    return $rs if $rs;

    # OpenDKIM Systemd configuration

    if ( iMSCP::Service->getInstance()->isSystemd() ) {
        # Make sure that the Systemd opendkim.service conffile has not been
        # redefined in old fashion way
        if ( -f '/etc/systemd/system/opendkim.service' ) {
            $rs = iMSCP::File->new( filename => '/etc/systemd/system/opendkim.service' )->delFile();
            return $rs if $rs;
        }

        if ( $action eq 'configure' ) {
            if ( -x '/lib/opendkim/opendkim.service.generate' ) {
                # Override the default systemd configuration for OpenDKIM by
                # generating the /etc/systemd/system/opendkim.service.d/override.conf
                # and /etc/tmpfiles.d/opendkim.conf files, according changes made in
                # the /etc/default/opendkim file.
                execute( '/lib/opendkim/opendkim.service.generate', \my $stdout, \my $stderr );
                debug( $stdout ) if $stdout;
                error( $stderr || 'Unknown error' ) if $rs;
                return $rs if $rs;
            } elsif ( -f '/lib/systemd/system/opendkim.service' ) {
                # Make use of our own systemd override.conf file (Ubuntu 16.04)
                eval { iMSCP::Dir->new( dirname => '/etc/systemd/system/opendkim.service.d' )->make() };
                if ( $@ ) {
                    error( $@ );
                    return 1;
                }

                $file = iMSCP::File->new(
                    filename => "$main::imscpConfig{'PLUGINS_DIR'}/OpenDKIM/systemd/override.conf"
                );
                $fContent = $file->get();
                unless ( defined $fContent ) {
                    error( sprintf( "Couldn't read %s file", $file->{'filename'} ));
                    return 1;
                }

                $file->set( process(
                    {
                        OPENDKIM_RUNDIR => $self->{'config'}->{'opendkim_rundir'},
                        OPENDKIM_USER   => $self->{'config'}->{'opendkim_user'},
                        OPENDKIM_GROUP  => $self->{'config'}->{'opendkim_group'}
                    },
                    $fContent
                ));

                $file->{'filename'} = '/etc/systemd/system/opendkim.service.d/override.conf';
                $rs = $file->save();
                return $rs if $rs;
            }
        } else {
            eval { iMSCP::Dir->new( dirname => '/etc/systemd/system/opendkim.service.d' )->remove(); };
            if ( $@ ) {
                error( $@ );
                return 1;
            }

        }
    }

    # OpenDKIM main configuration file

    $file = iMSCP::File->new( filename => '/etc/opendkim.conf' );
    $fContent = $file->get();
    unless ( defined $fContent ) {
        error( sprintf( "Couldn't read %s file", $file->{'filename'} ));
        return 1;
    }

    if ( $action eq 'configure' ) {
        my $cfg = <<"EOF";
# Begin Plugin::OpenDKIM
UMask               0117
Mode                sv
Syslog              yes
SyslogSuccess       yes
LogWhy              no
SignatureAlgorithm  rsa-sha256
Canonicalization    $self->{'config'}->{'opendkim_canonicalization'}
KeyTable            refile:$self->{'config'}->{'opendkim_confdir'}/KeyTable
SigningTable        refile:$self->{'config'}->{'opendkim_confdir'}/SigningTable
ExternalIgnoreList  $self->{'config'}->{'opendkim_confdir'}/TrustedHosts
InternalHosts       $self->{'config'}->{'opendkim_confdir'}/TrustedHosts
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
    $rs = $file->save();
    return $rs if $rs;

    if ( $action eq 'configure' ) {
        my $serviceTasksSub = sub {
            eval {
                my $serviceMngr = iMSCP::Service->getInstance();
                $serviceMngr->enable( 'opendkim' );
                $serviceMngr->restart( 'opendkim' );
            };
            if ( $@ ) {
                error( $@ );
                return 1;
            }
            0;
        };

        if ( defined $main::execmode && $main::execmode eq 'setup' ) {
            return $self->{'eventManager'}->register(
                'beforeSetupRestartServices',
                sub {
                    unshift @{$_[0]}, [ $serviceTasksSub, 'OpenDKIM' ];
                    0;
                }
            );
        }

        return $serviceTasksSub->();
    }

    0
}

=item _postfixConfigure( $action )

 Configure or deconfigure Postfix

 Param string $action Action to perform (configure|deconfigure)
 Return int 0 on success, other on failure

=cut

sub _postfixConfigure
{
    my ($self, $action) = @_;

    unless ( defined $action && grep($_ eq $action, 'configure', 'deconfigure') ) {
        error( 'Missing or invalid $action parameter' );
        return 1;
    }

    my @milterPrevValues = ( qr/\Q$self->{'config_prev'}->{'postfix_milter_socket'}\E/ );
    if ( defined $self->{'config_prev'}->{'opendkim_port'} ) {
        # Remove the milter value from the deprecated opendkim_port
        # configuration parameter
        push @milterPrevValues, qr/\Qinet:localhost:$self->{'config_prev'}->{'opendkim_port'}\E/;
    }

    my $mta = Servers::mta->factory();
    my $rs = $mta->postconf(
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
    );
    return $rs if $rs;

    if ( $action eq 'deconfigure' ) {
        # On deconfigure action, we reload postfix immediately,as the OpenDKIM
        # service will become unavailable
        return Servers::mta::factory()->reload();
    }

    my $milterValue = $self->{'config'}->{'postfix_milter_socket'};
    $mta->postconf(
        (
            milter_default_action => {
                action => 'replace',
                values => [ 'tempfail' ]
            },
            smtpd_milters         => {
                action => 'add',
                values => [ $milterValue ]
            },
            non_smtpd_milters     => {
                action => 'add',
                values => [ $milterValue ]
            }
        )
    );
}

=item _addDomainKey( $domainId, $aliasId, $domainName )

 Adds domain key for the given domain or domain alias

 Param int $domainId Domain unique identifier
 Param int $aliasId Domain alias unique identifier (0 if no domain alias)
 Param string $domainName Domain name
 Return int 0 on success, other on failure

=cut

sub _addDomainKey
{
    my ($self, $domainId, $aliasId, $domainName) = @_;

    local $@;

    # This action must be idempotent.
    # This allow to handle 'tochange' status which include key renewal.
    my $rs = $self->_deleteDomainKey( $domainId, $aliasId, $domainName );
    return $rs if $rs;

    eval {
        iMSCP::Dir->new( dirname => "/etc/opendkim/keys/$domainName" )->make(
            {
                user           => $self->{'config'}->{'opendkim_user'},
                group          => $self->{'config'}->{'opendkim_group'},
                mode           => 0750,
                fixpermissions => 1
            }
        );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    # Generate the domain private key and the DNS TXT record suitable for
    # inclusion in DNS zone file. The DNS TXT record contains the public key
    $rs = execute(
        [
            'opendkim-genkey',
            '-b', $self->{'config'}->{'opendkim_keysize'},
            '-D', "/etc/opendkim/keys/$domainName",
            '-r',
            '-s', 'mail',
            '-d', $domainName
        ],
        \my $stdout,
        \my $stderr
    );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    return $rs if $rs;

    # Fix permissions for the domain private key file
    my $file = iMSCP::File->new( filename => "/etc/opendkim/keys/$domainName/mail.private" );
    $rs = $file->mode( 0640 );
    $rs ||= $file->owner( $self->{'config'}->{'opendkim_user'}, $self->{'config'}->{'opendkim_group'} );
    return $rs if $rs;

    # Retrieve the TXT DNS record

    $file = iMSCP::File->new( filename => "/etc/opendkim/keys/$domainName/mail.txt" );
    my $fContent = $file->get();
    unless ( defined $fContent ) {
        error( sprintf( "Couldn't read %s file", $file->{'filename'} ));
        return 1;
    }

    # Extract all quoted <character-string>s, excluding delimiters
    $_ =~ s/^"(.*)"$/$1/ for my @txtRecordChunks = extract_multiple(
        $fContent, [ sub { extract_delimited( $_[0], '"' ) } ], undef, 1
    );

    my $txtRecord = join '', @txtRecordChunks;

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

        # Store TXT-DATA as multiple <character-string>s
        $txtRecord = join ' ', map( qq/"$_"/, @txtRecordChunks );
    } else {
        # Store TXT-DATA as only-one <character-string>
        $txtRecord = qq/"$txtRecord"/;
    }

    # Fix permissions on the TXT DNS record file
    $rs = $file->mode( 0640 );
    $rs ||= $file->owner( $self->{'config'}->{'opendkim_user'}, $self->{'config'}->{'opendkim_group'} );
    return $rs if $rs;

    # Add the domain private key into the KeyTable file
    $file = iMSCP::File->new( filename => '/etc/opendkim/KeyTable' );
    $fContent = $file->get();
    unless ( defined $fContent ) {
        error( sprintf( "Couldn't read %s file", $file->{'filename'} ));
        return 1;
    }

    $fContent .= "mail._domainkey.$domainName $domainName:mail:/etc/opendkim/keys/$domainName/mail.private\n";
    $file->set( $fContent );
    $rs = $file->save();
    return $rs if $rs;

    # Add the domain entry into the SigningTable file
    $file = iMSCP::File->new( filename => '/etc/opendkim/SigningTable' );
    $fContent = $file->get();
    unless ( defined $fContent ) {
        error( sprintf( "Couldn't read %s file", $file->{'filename'} ));
        return 1;
    }

    $fContent .= "*\@$domainName mail._domainkey.$domainName\n";
    $file->set( $fContent );
    $rs = $file->save();
    return $rs if $rs;

    # Schedule TXT DNS resource records addition
    eval {
        local $self->{'_dbh'}->{'AutoCommit'} = 0;
        local $self->{'_dbh'}->{'RaiseError'} = 1;

        $self->{'dbh'}->do(
            "
                INSERT INTO domain_dns (
                    domain_id, alias_id, domain_dns, domain_class, domain_type, domain_text, owned_by, domain_dns_status
                ) VALUES (
                    ?, ?, 'mail._domainkey 60', 'IN', 'TXT', ?, 'OpenDKIM_Plugin', 'toadd'
                )
            ",
            undef,
            $domainId,
            $aliasId,
            $txtRecord
        );

        if ( $self->{'config'}->{'opendkim_adsp_extension'} ) {
            $self->{'dbh'}->do(
                "
                    INSERT INTO domain_dns (
                        domain_id, alias_id, domain_dns, domain_class, domain_type, domain_text, owned_by,
                        domain_dns_status
                    ) VALUES (
                        ?, ?, '_adsp._domainkey 60', 'IN', 'TXT', ?, 'OpenDKIM_Plugin', 'toadd'
                    )
                ",
                undef,
                $domainId,
                $aliasId,
                '"dkim=' . $self->{'config'}->{'opendkim_adsp_signing_practice'} . '"'
            );
        }

        $self->{'dbh'}->commit();
    };
    if ( $@ ) {
        $self->{'dbh'}->rollback();
        error( $@ );
        return 1;
    }

    0;
}

=item _deleteDomainKey( $domainId, $aliasId, $domainName )

 Deletes domain key for the given domain

 Param int $domainId Domain unique identifier
 Param int $aliasId Domain alias unique identifier (0 if no domain alias)
 Param string $domainName Domain name
 Return int 0 on success, other on failure

=cut

sub _deleteDomainKey
{
    my ($self, $domainId, $aliasId, $domainName) = @_;

    local $@;
    eval {
        # Remove the directory that holds the domain private key and the DNS
        # TXT record file
        iMSCP::Dir->new( dirname => "/etc/opendkim/keys/$domainName" )->remove();
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    # Remove the domain private key from the KeyTable file
    my $file = iMSCP::File->new( filename => '/etc/opendkim/KeyTable' );
    my $fContent = $file->get();
    unless ( defined $fContent ) {
        error( sprintf( "Couldn't read %s file", $file->{'filename'} ));
        return 1;
    }

    $fContent =~ s/^\Qmail._domainkey.$domainName\E\s[^\n]+\n//gm;
    $file->set( $fContent );
    my $rs = $file->save();
    return $rs if $rs;

    # Remove the domain entry from the SigningTable file
    $file = iMSCP::File->new( filename => '/etc/opendkim/SigningTable' );
    $fContent = $file->get();
    unless ( defined $fContent ) {
        error( sprintf( "Couldn't read %s file", $file->{'filename'} ));
        return 1;
    }

    $fContent =~ s/^\Q*@\E\Q$domainName\E\s[^\n]+\n//gm;
    $file->set( $fContent );
    $rs = $file->save();
    return $rs if $rs;

    # Schedule TXT DNS resource records deletion
    eval {
        local $self->{'_dbh'}->{'RaiseError'} = 1;

        $self->{'dbh'}->do(
            "
                UPDATE domain_dns SET domain_dns_status = 'todelete'
                WHERE domain_id = ? AND alias_id = ? AND owned_by = 'OpenDKIM_Plugin'
            ",
            undef, $domainId, $aliasId
        );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>
 Rene Schuster <mail@reneschuster.de>
 Sascha Bay <info@space2place.de>

=cut

1;
__END__
