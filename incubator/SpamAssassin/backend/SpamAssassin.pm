=head1 NAME

 Plugin::SpamAssassin

=cut

# i-MSCP SpamAssassin plugin
# Copyright (C) 2015-2019 Laurent Declercq <l.declercq@nuxwin.com>
# Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>
# Copyright (C) 2013-2016 Rene Schuster <mail@reneschuster.de>
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

package Plugin::SpamAssassin;

use strict;
use warnings;
use File::Basename qw/ basename dirname /;
use File::Temp;
use iMSCP::Boolean;
use iMSCP::Crypt qw/ ALPHA64 decryptRijndaelCBC encryptRijndaelCBC randomStr /;
use iMSCP::Database;
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::Execute 'execute';
use iMSCP::File;
use iMSCP::Rights 'setRights';
use iMSCP::TemplateParser qw/ process replaceBloc /;
use iMSCP::Service;
use iMSCP::SystemGroup;
use iMSCP::SystemUser;
use iMSCP::Umask;
use List::MoreUtils 'uniq';
use Module::Load::Conditional 'check_install';
use PHP::Var 'export';
use Servers::cron;
use Servers::mta;
use Servers::sqld;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP SpamAssassin plugin backend.

=head1 PUBLIC METHODS

=over 4

=item install( )

 Installation tasks

 Return int 0, other on failure

=cut

sub install
{
    my ( $self ) = @_;

    eval {
        my @packages;

        my $config = $self->{'config'}->{'spamassassin'};
        ref $config eq 'HASH' or die(
            "Missing or invalid 'spamassassin' configuration parameter."
        );

        if ( ref $config->{'dist_packages'} eq 'ARRAY' ) {
            push @packages, @{ $config->{'dist_packages'} };
        }

        $config = $self->{'config'}->{'spamass-milter'};
        ref $config eq 'HASH' or die(
            "Missing or invalid 'spamass-milter' configuration parameter."
        );

        if ( ref $config->{'dist_packages'} eq 'ARRAY' ) {
            push @packages, @{ $config->{'dist_packages'} };
        }

        $self->_installDistPackages( @packages );
        $self->_setupSpamAssassinUnixUser();
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item update( $fromVersion )

 Update tasks

 Param $string $fromVersion Version from which plugin is being updated
 Return int 0 on success, other on failure

=cut

sub update
{
    my ( $self, $fromVersion ) = @_;

    my $rs = eval {
        $fromVersion = version->parse( $fromVersion );

        return 0 unless $fromVersion < version->parse( '3.0.0' );

        # Remove deprecated SA configuration file
        if ( -f '/etc/spamassassin/00_imscp.pre' ) {
            my $rs = iMSCP::File->new(
                filename => '/etc/spamassassin/00_imscp.pre'
            )->delFile();
            return $rs if $rs;
        }

        # Remove deprecated SA cronjobs
        for my $cronjobID ( qw/
            BayesSaLearn CleanAwlDb CleanBayesDb DiscoverRazor
        / ) {
            my $rs = Servers::cron->factory()->deleteTask( {
                TASKID => "Plugin::SpamAssassin::${cronjobID}"
            } );
            return $rs if $rs;
        }

        if ( -f '/etc/cron.hourly/spamassassin_heinlein-support_de' ) {
            # Remove deprecated hourly cronjob for Heinlein SA ruleset
            my $rs = iMSCP::File->new(
                filename => '/etc/cron.hourly/spamassassin_heinlein-support_de'
            )->delFile();
            return $rs if $rs;
        }

        # Remove deprecated SA SQL user
        Servers::sqld->factory()->dropUser(
            'sa_user', $::imscpConfig{'DATABASE_USER_HOST'}
        );

        # Make sure that all required distribution packages are installed
        $self->install();
        0;
    };
    if ( $@ ) {
        error( $@ );
        $rs = 1;
    }

    $rs;
}

=item uninstall( )

 Uninstallation tasks

 Return int 0, other on failure

=cut

sub uninstall
{
    my ( $self ) = @_;

    eval {
        my @packages;
        my $config = $self->{'config_prev'}->{'spamassassin'};

        if ( ref $config->{'dist_packages'} eq 'ARRAY' ) {
            push @packages, @{ $config->{'dist_packages'} };
        }

        $config = $self->{'config_prev'}->{'spamass-milter'};

        if ( ref $config->{'dist_packages'} eq 'ARRAY' ) {
            push @packages, @{ $config->{'dist_packages'} };
        }

        $self->_uninstallDistPackages( @packages );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }
}

=item enable( )

 Activation tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
    my ( $self ) = @_;

    eval { $self->_setupSpamAssassin(); };
    if ( $@ ) {
        error( $@ );
        return 1;
    }
}

=item disable( )

 Deactivation tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
    my ( $self ) = @_;

    eval { $self->_setupSpamAssassin( 'deconfigure' ); };
    if ( $@ ) {
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

 Return Plugin::SpamAssassin, die on failure

=cut

sub _init
{
    my ( $self ) = @_;

    $self->{'FORCE_RETVAL'} = 'yes';
    $self->{'dbh'} = iMSCP::Database->factory()->getRawDb();
    $self;
}

=item _setupSpamAssassin( [ $action = 'configure ] )

 Setup SpamAssassin

 Param string $action Setup action (configure|deconfigure)
 Return void, die on failure

=cut

sub _setupSpamAssassin
{
    my ( $self, $action ) = @_;
    $action //= 'configure';

    if ( $action eq 'configure' ) {
        my $config = $self->{'config'}->{'spamassassin'};
        ref $config eq 'HASH' or die(
            "Missing or invalid 'spamassassin' configuration parameter."
        );

        $self->_setupSpamAssassinSqlUser( 'configure' );

        my ( $user, $group, $homedir ) = (
            ref \( $config->{'user'} // \1 ) eq 'SCALAR'
                && length $config->{'user'}
                    ? $config->{'user'} : 'debian-spamd',
            ref \( $config->{'group'} // \1 ) eq 'SCALAR'
                && length $config->{'group'}
                    ? $config->{'group'} : 'debian-spamd',
            ref \( $config->{'homedir'} // \1 ) eq 'SCALAR'
                && length $config->{'homedir'}
                    ? $config->{'homedir'} : '/var/lib/spamassassin',
        );

        # Placeholders for administrator settings
        my $placeholders = {
            PLUGINS_DIR          => $::imscpConfig{'PLUGINS_DIR'},
            SA_DSN               => "DBI:"
                . ( !!check_install(
                module => 'DBD::MariaDB', verbose => FALSE
            ) ? 'MariaDB' : 'mysql' )
                . ":$::imscpConfig{'DATABASE_NAME'}_spamassassin"
                . ":$::imscpConfig{'DATABASE_HOST'}"
                . ( $::imscpConfig{'DATABASE_HOST'} ne 'localhost'
                ? ":$::imscpConfig{'DATABASE_PORT'}" : ''
            ),
            SA_DATABASE_USER     => $self->{'_sa_sql_user'},
            SA_DATABASE_PASSWORD => $self->{'_sa_sql_user_passwd'},
            SA_USER              => $user,
            SA_GROUP             => $group,
            SA_HOMEDIR => $homedir
        };

        if ( ref $config->{'admin_settings'} eq 'HASH' ) {
            if ( $action eq 'configure' ) {
                $self->_addSettings(
                    'SpamAssassin', $config->{'admin_settings'}, $placeholders
                );
            }

            $self->_removeSettings( 'SpamAssassin' );
        }

        if ( ref $config->{'user_preferences'} eq 'HASH' ) {
            $self->_setGlobalSaUserPreferences(
                $config->{'user_preferences'},
                $action eq 'deconfigure' )
        }

        if ( $config->{'sa-update'}->{'enabled'} // TRUE ) {
            iMSCP::Dir->new(
                dirname => "$config->{'homedir'}/sa-update-keys"
            )->make( {
                user  => $user,
                group => $group,
                mode  => 0700
            } );

            my $gpgPath = '/usr/share/spamassassin/GPG.KEY';
            if ( ref \( $config->{'sa-update'}->{'gpg_path'} // \1 ) eq 'SCALAR'
                && length $config->{'sa-update'}->{'gpg_path'}
                && -f $config->{'sa-update'}->{'gpg_path'}
            ) {
                $gpgPath = $config->{'sa-update'}->{'gpg_path'};
            }

            my $cmd = <<"EOF";
env -i LANG="\$LANG" PATH="\$PATH" \\
    /sbin/start-stop-daemon --chuid $user:$group \\
    --start \\
    --exec /usr/bin/sa-update -- --gpghomedir $homedir/sa-update-keys --import $gpgPath 2>&1            
EOF
            $self->_execShellCommand( $cmd ) == 0 or die( getMessageByType(
                'error', { amount => 1, remove => TRUE }
            ));
        }

        my $spamdOptions = '--max-children=5 --sql-config --nouser-config'
            . ' --username={SA_USER} --groupname={SA_GROUP}'
            . ' --helper-home-dir={SA_HOMEDIR}'
            . ' --socketpath=/var/run/spamassassin.sock --socketowner={SA_USER}'
            . ' --socketgroup={SA_GROUP} --socketmode=0666';

        if ( ref \( $config->{'spamd'}->{'options'} // \1 ) eq 'SCALAR'
            && length $config->{'spamd'}->{'options'}
        ) {
            $spamdOptions = $config->{'spamd'}->{'options'};
        }

        $spamdOptions = process(
            {
                SA_USER    => $user,
                SA_GROUP   => $group,
                SA_HOMEDIR => $homedir
            },
            $spamdOptions
        );

        my $file = iMSCP::File->new( filename => '/etc/default/spamassassin' );
        defined( my $fileC = $file->getAsRef()) or die( getMessageByType(
            'error', { amount => 1, remove => TRUE }
        ));

        unless ( ${ $fileC } =~ s/^ENABLED=.*/ENABLED=1/m ) {
            ${ $fileC } .= "ENABLED=1\n";
        }

        unless ( ${ $fileC } =~ s/^OPTIONS=.*/OPTIONS="$spamdOptions"/m ) {
            ${ $fileC } .= "$spamdOptions\n";
        }

        unless ( ${ $fileC } =~ s%^CRON=.*%CRON=@{ [ ( $config->{'sa-update'}->{'enabled'} // TRUE ) ? 1 : 0 ] }%m ) {
            ${ $fileC } .= "@{ [ ( $config->{'sa-update'}->{'enabled'} // TRUE ) ? 1 : 0 ] }\n";
        }
        $file->save() == 0 or die( getMessageByType(
            'error', { amount => 1, remove => TRUE }
        ));

        $self->_setupSpamAssassinPlugins( 'configure' );
        $self->_setupSpamAssassinRulesets( 'configure' );
        $self->_setupSpamAssassinMilter( 'configure' );

        my $service = 'spamassassin';
        if ( ref \( $config->{'service'} // \1 ) eq 'SCALAR'
            && length $config->{'service'}
        ) {
            $service = $config->{'service'};
        }

        iMSCP::Service->getInstance()->enable( $service );

        my $serviceTasksSub = sub {
            eval {
                # Restart or start SpamAssassin service
                iMSCP::Service->getInstance()->restart( $service );
            };
            if ( $@ ) {
                error( $@ );
                return 1;
            }

            0;
        };
        if ( defined $::execmode && $::execmode eq 'setup' ) {
            $self->{'eventManager'}->register(
                'beforeSetupRestartServices',
                sub {
                    unshift @{ $_[0] }, [ $serviceTasksSub, 'SpamAssassin' ];
                    0;
                } ) == 0 or die( getMessageByType(
                'error', { amount => 1, remove => TRUE }
            ));
        } else {
            $serviceTasksSub->() == 0 or die( getMessageByType(
                'error', { amount => 1, remove => TRUE }
            ));
        }

        return;
    }

    $self->_setupSpamAssassinMilter( 'deconfigure' );

    my $config = $self->{'config_prev'}->{'spamassassin'};
    ref $config eq 'HASH' or die(
        "Missing or invalid 'spamassassin' (prev) configuration parameter."
    );

    my $service = 'spamassassin';
    if ( ref \( $config->{'service'} // \1 ) eq 'SCALAR'
        && length $config->{'service'}
    ) {
        $service = $config->{'service'};
    }

    iMSCP::Service->getInstance()->stop( $service );
    iMSCP::Service->getInstance()->disable( $service );

    # On the plugin deactivation, we do not want trigger a full deconfiguration
    # as this would lead to data loss.
    return if $self->{'action'} eq 'disable';

    $self->_setupSpamAssassinRulesets( 'deconfigure' );
    $self->_setupSpamAssassinPlugins( 'deconfigure' );
    $self->_setupSpamAssassinSqlUser( 'deconfigure' );

    if ( -f '/etc/spamassassin/00_imscp.cf' ) {
        iMSCP::File->new(
            filename => '/etc/spamassassin/00_imscp.cf'
        )->delFile() == 0 or die( getMessageByType(
            'error', { amount => 1, remove => TRUE }
        ));
    }

    return unless -f '/etc/default/spamassassin';

    my $file = iMSCP::File->new( filename => '/etc/default/spamassassin' );
    defined( my $fileC = $file->getAsRef()) or die( getMessageByType(
        'error', { amount => 1, remove => TRUE }
    ));
    ${ $fileC } =~ s/^(ENABLED=).*/$1=0/m;
    ${ $fileC } =~ s/^(OPTIONS=).*/$1"--create-prefs --max-children 5 --helper-home-dir"/m;
    ${ $fileC } =~ s/^(CRON=).*/${1}0/m;
    $file->save() == 0 or die( getMessageByType( 'error', { amount => 1, remove => TRUE } ));
}

=item _setupSpamAssassinUnixUser( )

 Setup SA unix user

 Return void, die on failure

=cut

sub _setupSpamAssassinUnixUser
{
    my ( $self ) = @_;

    my $config = $self->{'config'}->{'spamassassin'};

    my ( $user, $group, $homedir ) = (
        ref \( $config->{'user'} // \1 ) eq 'SCALAR'
            && length $config->{'user'}
                ? $config->{'user'} : 'debian-spamd',
        ref \( $config->{'group'} // \1 ) eq 'SCALAR'
            && length $config->{'group'}
                ? $config->{'group'} : 'debian-spamd',
        ref \( $config->{'homedir'} // \1 ) eq 'SCALAR'
            && length $config->{'homedir'}
                ? $config->{'homedir'} : '/var/lib/spamassassin',
    );

    my $rs = iMSCP::SystemGroup->getInstance()->addSystemGroup( $group, TRUE );
    $rs ||= iMSCP::SystemUser->new( {
        username => $user,
        group    => $group,
        system   => TRUE,
        comment  => '',
        home     => $homedir,
        shell    => '/bin/sh'
    } )->addSystemUser();
    $rs ||= setRights( $homedir, {
        user      => $user,
        group     => $group,
        recursive => TRUE
    } );
    $rs == 0 or die( getMessageByType(
        'error', { amount => 1, remove => TRUE }
    ));
}

=item _setupSpamAssassinSqlUser( [ $action = 'configure ] )

 Create SA SQL user

 Param string $action Setup action (configure|deconfigure)
 Return void, die on failure

=cut

sub _setupSpamAssassinSqlUser
{
    my ( $self, $action ) = @_;
    $action //= 'configure';

    my %config = @{ $self->{'dbh'}->selectcol_arrayref(
        "SELECT `name`, `value` FROM `config` WHERE `name` LIKE 'SA_SQL_%'",
        { Columns => [ 1, 2 ] }
    ) };

    ( $config{'SA_SQL_USER'} = decryptRijndaelCBC(
        $::imscpDBKey, $::imscpDBiv, $config{'SA_SQL_USER'} // ''
    ) || ( $action eq 'configure'
        ? 'sa_user_' . randomStr( 8, ALPHA64 ) : undef )
    );

    ( $config{'SA_SQL_USER_PASSWD'} = decryptRijndaelCBC(
        $::imscpDBKey,
        $::imscpDBiv,
        $config{'SA_SQL_USER_PASSWD'} // ''
    ) || ( $action eq 'configure'
        ? randomStr( 16, ALPHA64 ) : undef )
    );

    if ( defined $config{'SA_SQL_USER'} ) {
        for my $host (
            $::imscpOldConfig{'DATABASE_USER_HOST'},
            $::imscpConfig{'DATABASE_USER_HOST'}
        ) {
            next unless length $host;
            Servers::sqld->factory()->dropUser(
                $config{'SA_SQL_USER'}, $host
            );
        }
    }

    if ( $action eq 'deconfigure' ) {
        $self->{'dbh'}->do(
            "DELETE FROM `config` WHERE `name` LIKE 'SA_SQL_%'"
        );
        return;
    }

    # Save generated values in database (encrypted)
    $self->{'dbh'}->do(
        '
            INSERT INTO `config` (`name`,`value`)
            VALUES (?,?),(?,?)
            ON DUPLICATE KEY UPDATE `value` = `value`
        ',
        undef,
        'SA_SQL_USER',
        encryptRijndaelCBC(
            $::imscpDBKey, $::imscpDBiv, $config{'SA_SQL_USER'}
        ),
        'SA_SQL_USER_PASSWD',
        encryptRijndaelCBC(
            $::imscpDBKey, $::imscpDBiv, $config{'SA_SQL_USER_PASSWD'}
        )
    );

    Servers::sqld->factory()->createUser(
        $config{'SA_SQL_USER'},
        $::imscpConfig{'DATABASE_USER_HOST'},
        $config{'SA_SQL_USER_PASSWD'}
    );

    $self->{'dbh'}->do(
        "
            GRANT SELECT, INSERT, UPDATE, DELETE
            ON `@{ [ $::imscpConfig{'DATABASE_NAME'} . '_spamassassin' ] }`.*
            TO ?\@?
        ",
        undef,
        $config{'SA_SQL_USER'},
        $::imscpConfig{'DATABASE_USER_HOST'}
    );

    # Make SQL username/password available for setup routines
    (
        $self->{'_sa_sql_user'}, $self->{'_sa_sql_user_passwd'}
    ) = (
        $config{'SA_SQL_USER'},
        $config{'SA_SQL_USER_PASSWD'}
    );
}

=item _deconfigureSaPlugins( [ $action = configure] )

 Configure/deconfigure SA plugins

 Return void, die on failure

=cut

sub _setupSpamAssassinPlugins
{
    my ( $self, $action ) = @_;
    $action //= 'configure';

    my $pluginPrevDefs = $self->{'config'}->{'spamassassin'}->{'plugins_definitions'};
    my $pluginDefs = $self->{'config'}->{'spamassassin'}->{'plugins_definitions'};
    my ( @pkgToInstall, @pkgToUninstall );

    # Generate list of distribution packages to uninstall
    if ( defined $pluginPrevDefs ) {
        while ( my ( $plugin, $pluginPrevDef ) = each(
            %{ $pluginPrevDefs }
        ) ) {
            next unless ( $action eq 'configure'
                || !$pluginDefs->{$plugin}->{'enabled'}
            ) && ref $pluginPrevDef->{'dist_packages'} eq 'ARRAY';
            push @pkgToUninstall, @{ $pluginPrevDef->{'dist_packages'} };
        }
    }

    if ( $action eq 'configure' ) {
        # Generate list of distribution packages to install
        for my $pluginDef ( values %{ $pluginDefs } ) {
            next unless $pluginDef->{'enabled'}
                && ref $pluginDef->{'dist_packages'} eq 'ARRAY';
            push @pkgToInstall, @{ $pluginDef->{'dist_packages'} };
        }

        # Install required distribution packages
        $self->_installDistPackages();
    }

    # Uninstall distribution packages that are not longer required
    $self->_uninstallDistPackages();

    if ( defined $pluginPrevDefs ) {
        my $saConfigPrev = $self->{'config_prev'}->{'spamassassin'};
        my ( $user, $group, $homedir ) = (
            ref \( $saConfigPrev->{'user'} // \1 ) eq 'SCALAR'
                && length $saConfigPrev->{'user'}
                    ? $saConfigPrev->{'user'} : 'debian-spamd',
            ref \( $saConfigPrev->{'group'} // \1 ) eq 'SCALAR'
                && length $saConfigPrev->{'group'}
                    ? $saConfigPrev->{'group'} : 'debian-spamd',
            ref \( $saConfigPrev->{'homedir'} // \1 ) eq 'SCALAR'
                && length $saConfigPrev->{'homedir'}
                    ? $saConfigPrev->{'homedir'} : '/var/lib/spamassassin',
        );

        # Placeholder for shell commands and administrator settings
        my $placeholders = {
            PLUGINS_DIR          => $::imscpConfig{'PLUGINS_DIR'},
            SA_DSN               => 'DBI'
                . ( !!check_install(
                module => 'DBD::MariaDB', verbose => FALSE
            ) ? ':MariaDB' : ':mysql' )
                . ":$::imscpConfig{'DATABASE_NAME'}_spamassassin"
                . ":$::imscpConfig{'DATABASE_HOST'}"
                . ( $::imscpConfig{'DATABASE_HOST'} ne 'localhost'
                    ? ":$::imscpConfig{'DATABASE_PORT'}" : ''
            ),
            SA_DATABASE_NAME     => "$::imscpConfig{'DATABASE_NAME'}_spamassassin",
            SA_DATABASE_USER     => $self->{'_sa_sql_user'},
            SA_DATABASE_PASSWORD => $self->{'_sa_sql_user_passwd'},
            SA_USER              => $user,
            SA_GROUP             => $group,
            SA_HOMEDIR           => $homedir
        };

        # Deconfigure the SA plugins which are not longer required
        while ( my ( $plugin, $pluginDef ) = each( %{ $pluginPrevDefs } ) ) {
            next unless $action eq 'deconfigure' ||
                !$pluginDefs->{$plugin}->{'enabled'};

            if ( ref $pluginDef->{'cronjobs'} eq 'HASH' ) {
                for my $cronjobID ( keys %{ $pluginDef->{'cronjobs'} } ) {
                    Servers::cron->factory()->deleteTask( {
                        TASKID => $cronjobID
                    } ) == 0 or die( getMessageByType(
                        'error', { amount => 1, remove => TRUE }
                    ));
                }
            }

            if ( ref $pluginDef->{'shell_commands'}->{$action} eq 'ARRAY' ) {
                for my $cmd (
                    @{ $pluginDef->{'shell_commands'}->{'deconfigure'} }
                ) {
                    $self->_execShellCommand( $cmd, $placeholders ) == 0 or die(
                        getMessageByType( 'error', { amount => 1, remove => TRUE } )
                    );
                }
            }
            
            if( ref $pluginDef->{'admin_settings'} eq 'HASH' ) {
                $self->_removeSettings( $plugin );
            }

            if ( ref $pluginDef->{'user_preferences'} eq 'HASH' ) {
                $self->_setGlobalSaUserPreferences(
                    $pluginDef->{'user_preferences'}, TRUE
                );
            }

            if ( ref \( $pluginDef->{'load_file'} // \1 ) eq 'SCALAR'
                && length $pluginDef->{'load_file'}
                && -f $pluginDef->{'load_file'}
            ) {
                my $file = iMSCP::File->new(
                    filename => $pluginDef->{'load_file'}
                );
                defined( my $fileC = $file->getAsRef()) or die( getMessageByType(
                    'error', { amount => 1, remove => TRUE }
                ));

                ${ $fileC } =~ s/^(loadplugin\s+\Q$plugin\E)$/#$1/m;

                $file->save() == 0 or die( getMessageByType(
                    'error', { amount => 1, remove => true }
                ));
            }
        }
    }

    return unless $action eq 'configure' && ref $pluginDefs eq 'HASH';

    my $saConfig = $self->{'config_prev'}->{'spamassassin'};
    my ( $user, $group, $homedir ) = (
        ref \( $saConfig->{'user'} // \1 ) eq 'SCALAR'
            && length $saConfig->{'user'}
                ? $saConfig->{'user'} : 'debian-spamd',
        ref \( $saConfig->{'group'} // \1 ) eq 'SCALAR'
            && length $saConfig->{'group'}
                ? $saConfig->{'group'} : 'debian-spamd',
        ref \( $saConfig->{'homedir'} // \1 ) eq 'SCALAR'
            && length $saConfig->{'homedir'}
                ? $saConfig->{'homedir'} : '/var/lib/spamassassin',
    );

    # Placeholder for shell commands and settings
    my $placeholders = {
        PLUGINS_DIR          => $::imscpConfig{'PLUGINS_DIR'},
        SA_DSN               => 'DBI'
            . ( !!check_install(
            module => 'DBD::MariaDB', verbose => FALSE
        ) ? ':MariaDB' : ':mysql' )
            . ":$::imscpConfig{'DATABASE_NAME'}_spamassassin"
            . ":$::imscpConfig{'DATABASE_HOST'}"
            . ( $::imscpConfig{'DATABASE_HOST'} ne 'localhost'
            ? ":$::imscpConfig{'DATABASE_PORT'}" : ''
        ),
        SA_DATABASE_NAME     => "$::imscpConfig{'DATABASE_NAME'}_spamassassin",
        SA_DATABASE_USER     => $self->{'_sa_sql_user'},
        SA_DATABASE_PASSWORD => $self->{'_sa_sql_user_passwd'},
        SA_USER              => $user,
        SA_GROUP             => $group,
        SA_HOMEDIR           => $homedir
    };

    # Configure all required SpamAssassin plugins
    while ( my ( $plugin, $pluginDef ) = each( %{ $pluginDefs } ) ) {
        next unless $pluginDefs->{'enabled'};

        if ( ref \( $pluginDef->{'load_file'} // \1 ) eq 'SCALAR'
            && length $pluginDef->{'load_file'}
            && -f $pluginDef->{'load_file'}
        ) {
            my $file = iMSCP::File->new(
                filename => $pluginDef->{'load_file'}
            );
            defined( my $fileC = $file->getAsRef()) or die( getMessageByType(
                'error', { amount => 1, remove => TRUE }
            ));

            if ( ${ $fileC } !~ s/^[#\s]*(loadplugin\s+\Q$plugin\E)$/$1/m ) {
                ${ $fileC } .= $pluginDef->{'load_file'} eq '/etc/spamassassin/00_imscp.cf'
                    ? <<"EOF" : "\nloadplugin $plugin";

# $plugin - BEGIN.
loadplugin $plugin @{ [ $plugin =~ s/.*:://r ] }.pm
# $plugin - ENDING.
EOF
            }

            $file->save() == 0 or die( getMessageByType(
                'error', { amount => 1, remove => true }
            ));
        }

        if( ref $pluginDef->{'admin_settings'} eq 'HASH' ) {
            $self->_addSettings(
                $plugin, $pluginDef->{'admin_settings'}, $placeholders
            );
        }

        if ( ref $pluginDef->{'user_preferences'} eq 'HASH' ) {
            $self->_setGlobalSaUserPreferences(
                $pluginDef->{'user_preferences'}
            );
        }

        if ( ref $pluginDef->{'shell_commands'}->{$action} eq 'ARRAY' ) {
            for my $cmd (
                @{ $pluginDef->{'shell_commands'}->{'configure'} }
            ) {
                $self->_execShellCommand( $cmd, $placeholders ) == 0 or die(
                    getMessageByType( 'error', { amount => 1, remove => TRUE } )
                );
            }
        }

        if ( ref $pluginDef->{'cronjobs'} eq 'HASH' ) {
            while ( my ( $cronjobID, $cronjobDef ) = each(
                %{ $pluginDef->{'cronjobs'} } )
            ) {
                $cronjobDef->{'COMMAND'} = process(
                    $placeholders, $cronjobDef->{'COMMAND'}
                );

                Servers::cron->factory()->addTask(
                    { TASKID => $cronjobID, %{ $cronjobDef } }
                ) == 0 or die( getMessageByType(
                    'error', { amount => 1, remove => TRUE }
                ));
            }
        }
    }
}

=item _setupSpamAssassinRuleset( [ $action = 'configure' ] )

 Setup SA rulesets

 Param string $action Setup action (configure|deconfigure)
 Return void, die on failure

=cut

sub _setupSpamAssassinRulesets
{
    my ( $self, $action ) = @_;
    $action //= 'configure';

    my $config = $self->{'config'}->{'spamassassin'};
    my $configPrev = $self->{'config'}->{'spamassassin'};

    my ( $user, $group, $homedir ) = (
        ref \( $config->{'user'} // \1 ) eq 'SCALAR'
            && length $config->{'user'}
                ? $config->{'user'} : 'debian-spamd',
        ref \( $config->{'group'} // \1 ) eq 'SCALAR'
            && length $config->{'group'}
                ? $config->{'group'} : 'debian-spamd',
        ref \( $config->{'homedir'} // \1 ) eq 'SCALAR'
            && length $config->{'homedir'}
                ? $config->{'homedir'} : '/var/lib/spamassassin',
    );

    # Deconfigure rulesets that are not longer required
    while ( my ( $rulesetID, $ruleset ) = each(
        %{ $configPrev->{'rulesets'} } )
    ) {
        next unless ref $ruleset eq 'HASH';
        next unless $action eq 'deconfigure' || !$ruleset->{'enabled'};

        if ( ref \( $ruleset->{$rulesetID}->{'channel'} // \1 ) eq 'SCALAR '
            && length $ruleset->{$rulesetID}->{'channel'}
        ) {
            for my $dentry ( glob(
                "$homedir/*/$ruleset->{$rulesetID}->{'channel'}"
            ) ) {
                if ( -f $dentry ) {
                    iMSCP::File->new(
                        filename => $dentry
                    )->delFile() == 0 or die( getMessageByType(
                        'error', { amount => 1, remove => TRUE }
                    ));
                    next;
                }

                iMSCP::Dir->new( dirname => $dentry )->remove();
            }

            if ( -f "/etc/cron.daily/spamassassin-$ruleset->{$rulesetID}->{'channel'}" ) {
                iMSCP::File->new(
                    filename => "/etc/cron.daily/spamassassin-$rulesetID->{'channel'}"
                )->delFile() == 0 or die( getMessageByType(
                    'error', { amount => 1, remove => TRUE }
                ));
            }
        }

        if ( ref \( $ruleset->{'gpg_id'} // 1 ) eq 'SCALAR'
            && length $ruleset->{'gpg_id'}
        ) {
            my $cmd = <<"EOF";
env -i LANG="\$LANG" PATH="\$PATH" GNUPGHOME=$homedir/sa-update-keys \\
    /sbin/start-stop-daemon --chuid $user:$group \\
    --start \\
    --exec /usr/bin/gpg -- --batch --no-tty --list-keys "$ruleset->{'gpg_id'}" >/dev/null 2>&1 || exit

env -i LANG="\$LANG" PATH="\$PATH" GNUPGHOME=$homedir/sa-update-keys \\
    /sbin/start-stop-daemon --chuid $user:$group \\
    --start \\
    --exec /usr/bin/gpg -- --batch --no-tty --yes --delete-key "$ruleset->{'gpg_id'}" 2>&1
EOF
            $self->_execShellCommand( $cmd ) == 0 or die( getMessageById(
                'error', { amount => 1, remove => TRUE }
            ));
        }
    }

    return unless $action eq 'configure' || ref $config->{'rulesets'} ne 'HASH';

    # Configure required rulesets

    while ( my ( $rulesetID, $ruleset ) = each( %{ $config->{'rulesets'} } ) ) {
        next unless ref $ruleset eq 'HASH' && $ruleset->{'enabled'};

        if ( ref \( $ruleset->{'channel'} // \1 ) eq 'SCALAR'
            && length $ruleset->{'channel'}
        ) {
            # Import SA 3rd-party ruleset GPG key into SA user keyring if any
            if (
                ref \( $ruleset->{'gpg_id'} // \1 ) eq 'SCALAR'
                    && length $ruleset->{'gpg_id'}
                    && ref \( $ruleset->{'gpg_uri'} // \1 ) eq 'SCALAR'
                    && length $ruleset->{'gpg_uri'}
            ) {
                my $cmd = <<"EOF";
bailout() {
  [ -n "\${1:-}" ] && EXIT="\${1}" || EXIT=0
  [ -n "\${tmpkeyfile:-}" ] && rm -rf "\${tmpkeyfile}"
  exit \$EXIT
}

trap bailout HUP INT QUIT ABRT ALRM TERM

tmpkeyfile=\$(mktemp /tmp/imscp-sa-update_XXXXXX)
/usr/bin/wget --no-check-certificate -O \${tmpkeyfile} $ruleset->{'gpg_uri'}
/bin/chown $user:$group \${tmpkeyfile}

env -i LANG="\$LANG" PATH="\$PATH" \\
    /sbin/start-stop-daemon --chuid $user:$group \\
    --start \\
    --exec /usr/bin/sa-update -- --gpghomedir $homedir/sa-update-keys --import \${tmpkeyfile} 2>&1

bailout 0
EOF
                $self->_execShellCommand( $cmd ) == 0 or die( getMessageById(
                    'error', { amount => 1, remove => TRUE }
                ));
            }

            # Create a daily cronjob for automatic update of 3rd-party SpamAssassin ruleset
            my $file = iMSCP::File->new(
                filename => $::imscpConfig{'PLUGINS_DIR'}
                    . '/SpamAssassin/templates/spamassassin/sa-update-cronjob'
            );
            defined( my $fileC = $file->getAsRef()) or die( getMessageByType(
                'error', { amount => 1, remove => TRUE }
            ));

            ${ $fileC } = process(
                {
                    RULESET_ID  => $rulesetID,
                    SA_USER     => $user,
                    SA_GROUP    => $group,
                    SLEEP_RANGE => $config->{'sa-update'}->{'sleep_range'} // 601,
                    GPG         => ref \( $ruleset->{'gpg_id'} // \1 ) eq 'SCALAR'
                        && length $ruleset->{'gpg_id'}
                        && ref \( $ruleset->{'gpg_uri'} // \1 ) eq 'SCALAR'
                        && length $ruleset->{'gpg_uri'}
                            ? "--gpghomedir $homedir/sa-update-keys --gpgkey $ruleset->{'gpg_id'}"
                            : '--nogpg',
                    CHANNEL     => $ruleset->{'channel'}
                },
                ${ $fileC }
            );
            $file->{'filename'} = "/etc/cron.daily/spamassassin-$rulesetID";
            my $rs = $file->save();
            $rs ||= $file->mode( 0755 );
            $rs == 0 or die( getMessageByType(
                'error', { amount => 1, remove => TRUE } ) || 'Unknown error'
            );
        }
    }
}

=item _setupSpamAssassinMilter( [ $action = 'configure' ] )

 Setup SpamAssassin MILTER

 Param string $action Setup action (configure|deconfigure)
 Return void, die on failure

=cut

sub _setupSpamAssassinMilter
{
    my ( $self, $action ) = @_;
    $action //= 'configure';

    ref $self->{'config'}->{'spamass-milter'} eq 'HASH' or die(
        "spamass-milter configuration isn't defined or isn't a HASH"
    );

    my $config = $self->{'config'}->{'spamass-milter'};

    # Configuration

    if ( $action eq 'configure' ) {
        if ( -f '/etc/default/spamass-milter' ) {
            my $file = iMSCP::File->new(
                filename => '/etc/default/spamass-milter'
            );
            defined( my $fileC = $file->getAsRef()) or die( getMessageByType(
                'error', { amount => 1, remove => TRUE }
            ));

            my $options = ref \( $config->{'options'} // \1 ) eq 'SCALAR'
                ? $config->{'options'} : '';

            # Remove SPAMC(1) options if any. Those will be re-add later
            ( $options, my $spamcOptions ) = $options =~ /(.*?)(?:\s+[-]{2}\s+(.*))?$/;

            # Add named options

            if ( ref $config->{'networks'} eq 'ARRAY'
                && @{ $config->{'networks'} }
            ) {
                $options .= " -i @{ [ join ',', @{ $config->{'networks'} } ] }";
            }

            if ( ref \( $config->{'ignore_auth_sender_msgs'} // \1 ) eq 'SCALAR'
                && length $config->{'ignore_auth_sender_msgs'}
            ) {
                $options .= ' -I';
            }

            if ( ref \( $config->{'spam_reject_policy'} // \1 ) eq 'SCALAR'
                && length $config->{'spam_reject_policy'}
            ) {
                $options .= ' -r ' . $config->{'spam_reject_policy'};
            }

            # Re-add SPAMC(1) flags if any
            if ( length $spamcOptions ) {
                $options .= ' -- ' . $spamcOptions;
            }

            # Comment all OPTIONS variables and set options through first
            #  OPTIONS variable.

            ${ $fileC } =~ s/^(OPTIONS=)/#$1/gm;

            unless ( ${ $fileC } =~ s/^[#\s]*(OPTIONS=).*/$1"$options"/m ) {
                ${ $fileC } .= "OPTIONS=\"$options\"\n";
            }

            # Socket to create for communication with Postfix (MILTER)
            my $socketPath = '/var/spool/postfix/spamass/spamass.sock';
            if ( ref \( $config->{'socket_path'} // \1 ) eq 'SCALAR'
                && length $config->{'socket_path'} ) {
                $socketPath = $config->{'socket_path'}
            };
            unless ( ${ $fileC } =~ s/^[#\s]*(SOCKET=).*/$1"$socketPath"/m ) {
                ${ $fileC } .= "SOCKET=\"$socketPath\"\n";
            }

            # Ownership for the socket
            my $socketOwner = 'postfix:postfix';
            if ( ref \( $config->{'socket_owner'} // \1 ) eq 'SCALAR'
                && length $config->{'socket_owner'} ) {
                $socketOwner = $config->{'socket_owner'}
            };
            unless ( ${ $fileC } =~ s/^[#\s]*(SOCKETOWNER=).*/$1"$socketOwner"/m ) {
                ${ $fileC } .= "SOCKETOWNER=\"$socketOwner\"\n";
            }

            # Mode for the socket
            my $socketMode = '0660';
            if ( ref \( $config->{'socket_mode'} // \1 ) eq 'SCALAR'
                && length $config->{'socket_mode'} ) {
                $socketMode = $config->{'socket_mode'}
            };
            unless ( ${ $fileC } =~ s/^[#\s]*(SOCKETMODE=).*/$1"$socketMode"/m ) {
                ${ $fileC } .= "SOCKETMODE=\"$socketMode\"\n";
            }

            $file->save() == 0 or die( getMessageByType(
                'error', { amount => 1, remove => TRUE }
            ));
        }

        $self->_setupPostfixMilter( 'configure' );

        my $serviceTasksSub = sub {
            eval {
                my $service = 'spamass-milter';
                if ( ref \( $config->{'service'} // \1 ) eq 'SCALAR'
                    && length $config->{'service'} ) {
                    $service = $config->{'service'}
                };

                my $serviceMngr = iMSCP::Service->getInstance();
                $serviceMngr->enable( $service );
                # Restart or start SpamAssassin MILTER service
                $serviceMngr->restart( $service );
            };
            if ( $@ ) {
                error( $@ );
                return 1;
            }
            0;
        };

        if ( defined $::execmode && $::execmode eq 'setup' ) {
            $self->{'eventManager'}->register(
                'beforeSetupRestartServices',
                sub {
                    unshift @{ $_[0] }, [ $serviceTasksSub, 'SpamAssassin' ];
                    0;
                }
            ) == 0 or die( getMessageByType(
                'error', { amount => 1, remove => TRUE }
            ));
            return;
        }

        $serviceTasksSub->() == 0 or die( getMessageByType(
            'error', { amount => 1, remove => TRUE }
        ));
        return;
    }

    # Deconfiguration

    $self->_setupPostfixMilter( 'deconfigure' );

    $config = $self->{'config_prev'}->{'spamass-milter'};

    my $service = 'spamass-milter';
    if ( ref \( $config eq 'SCALAR' // \1 ) && length $config->{'service'} ) {
        $service = $config->{'service'}
    };

    iMSCP::Service->getInstance()->stop( $service );
    iMSCP::Service->getInstance()->disable( $service );

    return unless -f '/etc/default/spamass-milter';

    my $file = iMSCP::File->new( filename => '/etc/default/spamass-milter' );
    defined( my $fileC = $file->getAsRef()) or die( getMessageByType(
        'error', { amount => 1, remove => TRUE }
    ));

    unless ( ${ $fileC } =~ s/^(OPTIONS=).*/$1"-u spamass-milter -i 127.0.0.1"/m ) {
        ${ $fileC } .= "\nOPTIONS=\"-u spamass-milter -i 127.0.0.1\"\n";
    }

    unless ( ${ $fileC } =~ s%^(SOCKET=).*%$1"/var/spool/postfix/spamass/spamass.sock"%m ) {
        ${ $fileC } .= "SOCKET=\"/var/spool/postfix/spamass/spamass.sock\"\n";
    }

    unless ( ${ $fileC } =~ s/^(SOCKETOWNER=).*/$1"postfix:postfix"/m ) {
        ${ $fileC } .= "SOCKETOWNER=\"postfix:postfix\"\n";
    }

    unless ( ${ $fileC } =~ s/^(SOCKETMODE=).*/$1"0660"/m ) {
        ${ $fileC } .= "SOCKETMODE=\"0660\"\n";
    }

    $file->save() == 0 or die( getMessageByType(
        'error', { amount => 1, remove => TRUE }
    ));
}

=item _setupPostfixMilter( [ $action = 'configure' ] )

 Setup postfix spamass-milter

 Param string $action Action to be performed (configure|deconfigure)
 Return void, die on failure

=cut

sub _setupPostfixMilter
{
    my ( $self, $action ) = @_;
    $action //= 'deconfigure';

    my $config = $self->{'config_prev'}->{'spamass-milter'};
    ref $config eq 'HASH' or die(
        "Invalid or missing 'spamass-milter configuration parameter."
    );

    my $serverEndpoint = 'unix:/spamass/spamass.sock';
    if ( ref \( $config->{'postfix_milter_server_endpoint'} // \1 ) eq 'SCALAR'
        && length $config->{'postfix_milter_server_endpoint'}
    ) {
        $serverEndpoint = $config->{'postfix_milter_server_endpoint'};
    }

    my $connectTimeout = '30s';
    if ( ref \( $config->{'postfix_milter_connect_timeout'} // \1 ) eq 'SCALAR'
        && length $config->{'postfix_milter_connect_timeout'}
    ) {
        $connectTimeout = $config->{'postfix_milter_connect_timeout'};
    }

    my $defaultAction = 'accept';
    if ( ref \( $config->{'postfix_milter_default_action'} // \1 ) eq 'SCALAR'
        && length $config->{'postfix_milter_default_action'}
    ) {
        $defaultAction = $config->{'postfix_milter_default_action'};
    }

    {
        my $valueReg = qr/(?:\{\s*)?\Q$serverEndpoint\E(?:[^}]+})?/;

        Servers::mta->factory()->postconf( (
            smtpd_milters         => {
                action => 'remove',
                values => [ $valueReg ]
            },
            non_smtpd_milters     => {
                action => 'remove',
                values => [ $valueReg ]
            },
            milter_connect_macros => {
                action => 'remove',
                values => [ 'i j {daemon_name} v {if_name} _' ]
            }
        )) == 0 or die( getMessageByType( 'error', { amount => 1, remove => TRUE } ));
    }

    return if $action eq 'deconfigure';

    $config = $self->{'config'}->{'spamass-milter'};
    ref $config eq 'HASH' or die(
        "Invalid or missing 'spamass-milter' configuration parameter."
    );

    my $mta = Servers::mta->factory();
    my $hasPerMilterAppSettings = version->parse( '3.0.0' )
        >= version->parse( $mta->{'POSTFIX_VERSION'} );

    $serverEndpoint = 'unix:/spamass/spamass.sock';
    if ( ref \( $config->{'milter_server_endpoint'} // \1 ) eq 'SCALAR'
        && length $config->{'milter_server_endpoint'}
    ) {
        $serverEndpoint = $config->{'milter_server_endpoint'};
    }

    $connectTimeout = '10s';
    if ( ref \( $config->{'milter_connect_timeout'} // \1 ) eq 'SCALAR'
        && length $config->{'milter_connect_timeout'}
    ) {
        $connectTimeout = $config->{'milter_connect_timeout'};
    }

    $defaultAction = 'accept';
    if ( ref \( $config->{'milter_default_action'} // \1 ) eq 'SCALAR'
        && length $config->{'milter_default_action'}
    ) {
        $defaultAction = $config->{'milter_default_action'};
    }

    my $milterValue = ( $hasPerMilterAppSettings ? '{ ' : '' )
        . "$serverEndpoint"
        . ( $hasPerMilterAppSettings
            ? ", connect_timeout=$connectTimeout, default_action=$defaultAction }"
            : ''
        );
    my $posReg = qr/.*/;

    Servers::mta->factory()->postconf( (
        smtpd_milters         => {
            action => 'add',
            values => [ $milterValue ],
            # Make sure that SpamAssassin filtering is processed first
            before => $posReg
        },
        non_smtpd_milters     => {
            action => 'add',
            values => [ $milterValue ],
            # Make sure that SpamAssassin filtering is processed first
            before => $posReg
        },
        milter_connect_macros => {
            action => 'replace',
            values => [ 'i j {daemon_name} v {if_name} _' ]
        }
    )) == 0 or die( getMessageByType( 'error', { amount => 1, remove => TRUE } ));
}

=item _installDistPackages( @packages )
 
 Install the given distribution packages

 Param list @packages List of distribution packages to install
 Return void, die on failure

=cut

sub _installDistPackages
{
    my ( undef, @packages ) = @_;

    return unless @packages;

    local $ENV{'DEBIAN_FRONTEND'} = 'noninteractive';
    my ( $stdout, $stderr );
    execute(
        [ '/usr/bin/apt-get', 'update' ], \$stdout, \$stderr
    ) == 0 or die( $stderr || 'Unknown error' );
    debug( $stdout ) if $stdout;
    execute(
        [
            '/usr/bin/apt-get',
            '-o', 'DPkg::Options::=--force-confold',
            '-o', 'DPkg::Options::=--force-confdef',
            '-o', 'DPkg::Options::=--force-confmiss',
            '--auto-remove',
            '--install-recommends',
            '--no-install-suggests',
            '--purge',
            '--quiet=2',
            'install', @packages
        ],
        \$stdout,
        \$stderr
    ) == 0 or die( $stderr || 'Unknown error' );
    debug( $stdout ) if $stdout;
}

=item _uninstallDistPackages( @packages )
 
 Uninstall the given distribution packages

 Param list \@packages List of distribution packages to uninstall
 Return void, die on failure

=cut

sub _uninstallDistPackages
{
    my ( undef, @packages ) = @_;

    return unless @packages;

    local $ENV{'DEBIAN_FRONTEND'} = 'noninteractive';
    my ( $stdout, $stderr );
    execute(
        [
            '/usr/bin/apt-get',
            '--auto-remove',
            '--quiet=2',
            'purge', @packages
        ],
        \$stdout,
        \$stderr
    ) == 0 or die( $stderr || 'Unknown error' );
    debug( $stdout ) if $stdout;
}

=item _setGlobalSaPref( \%userPreferences, $value [, $remove = FALSE ] )

 Add, update or remove the given given global SpamAssassin user preferences

 Param hashref \%userPreferences SpamAssassin user preference
 Param bool remove Flag indicating whether or not the Global SpamAssassin user
                   preferences must be removed
 Return void, die on failure

=cut

sub _setGlobalSaUserPreferences
{
    my ( undef, $userPreferences, $remove ) = @_;

    my $dbh = iMSCP::Database->factory()->getRawDb();

    while ( my ( $preference, $value ) = each( %{ $userPreferences } ) ) {
        if ( $remove ) {
            $dbh->do(
                "
                    DELETE FROM `$::imscpConfig{'DATABASE_NAME'}_spamassassin`.`userpref`
                    WHERE `username` <> '\$GLOBAL' AND `preference` = ?
                ",
                undef,
                $preference
            );
            return;
        }

        # Update global preference
        $dbh->do(
            "
                INSERT INTO `$::imscpConfig{'DATABASE_NAME'}_spamassassin`.`userpref` (
                    `username`, `preference`, `value`
                ) VALUES( '\$GLOBAL', ?, ? )
                ON DUPLICATE KEY UPDATE `value` = ?
            ",
            undef,
            $preference,
            $value,
            $value
        );
    }
}

=item _execShellCommand( $command, [ \%placeholder = { } ] )

 Execute the given shell command through /bin/sh command interpreter

 Param string $command Shell command to be executed
 Param hashref \%placeholders Placeholder for the shell command
 Return int Command exit status

=cut

sub _execShellCommand
{
    my ( undef, $command, $placeholders ) = @_;
    $placeholders //= {};

    my $file = File::Temp->new(
        TEMPLATE => __PACKAGE__ . '::XXXXXXXXXXX',
        SUFFIX   => '.sh',
        TMPDIR   => TRUE
    );
    print { $file } process( $placeholders, <<"EOF" );
#!/bin/sh
set -u
umask 022
PATH=/bin:/sbin:/usr/local/bin:/usr/local/sbin:/usr/bin:/usr/sbin

$command
EOF
    $file->flush();
    my $rs = execute(
        [ '/bin/sh', '-xe', $file->filename() ],
        \my $stdout,
        \my $stderr
    );
    debug( $stdout ) if $stdout;
    error( $stderr || 'Unknown error' ) if $rs;
    $rs;
}

=item _addSettings( $itemName, \@settings [, \%placeholders ] )

 Add the given user preferences or administrator settings

 Param string $itemName Item name
 Param arrayref \@settings Plugin settings
 Param hashref \%placeholders
 Return void, die on failure

=cut

sub _addSettings
{
    my ( $self, $itemName, $settings, $placeholders ) = @_;
    $placeholders //= {};

    my $file = iMSCP::File->new( filename => '/etc/spamassassin/00_imscp.cf' );

    unless ( -f $file->{'filename'} ) {
        local $UMASK = 027;
        local $) = getgrnam( $self->{'config'}->{'spamassassin'}->{'group'} ) // die(
            "Couldn't get SpamAssassin user group ID"
        );

        $file->set( <<"EOF" );
# SPAMASSASSIN(1p) local configuration file - auto-generated by i-MSCP
#     DO NOT EDIT THIS FILE BY HAND -- YOUR CHANGES WILL BE OVERWRITTEN
EOF
        $file->save() == 0 or die( getMessageByType(
            'error', { amount => 1, remove => TRUE }
        ));
    }

    defined( my $fileC = $file->getAsRef()) or die( getMessageByType(
        'error', { amount => 1, remove => TRUE }
    ));

    ${ $fileC } = replaceBloc(
        qr/(?:^\n)?#\s+\Q$itemName\E\s+-\s+BEGIN\.\n/m,
        qr/#\s+\Q$itemName\E\s+-\s+ENDING\.\n/,
        '',
        ${ $fileC }
    );

    ${ $fileC } .= process( $placeholders, <<"EOF" );

# $itemName - BEGIN.
@{ [ join "\n", map { "$_ $settings->{$_}" } sort keys %{ $settings } ] }
# $itemName - ENDING.
EOF
    $file->save() == 0 or die( getMessageByType(
        'error', { amount => 1, remove => TRUE }
    ));
}

=item _removeSettings( $itemName )

 Delete the user preferences or administrator settings that belong to the given item

 Param string $itemName Item name
 Return void, die on failure

=cut

sub _removeSettings
{
    my ( undef, $itemName ) = @_;

    return unless -f '/etc/spamassassin/00_imscp.cf';

    my $file = iMSCP::File->new(
        filename => '/etc/spamassassin/00_imscp.cf'
    );
    defined( my $fileC = $file->getAsRef()) or die( getMessageByType(
        'error', { amount => 1, remove => TRUE
    } ));

    ${ $fileC } = replaceBloc(
        qr/(?:^\n)?#\s+\Q$itemName\E\s+-\s+BEGIN\.\n/m,
        qr/#\s+\Q$itemName\E\s+-\s+ENDING\.\n/,
        '',
        ${ $fileC }
    );

    $file->save() == 0 or die( getMessageByType(
        'error', { amount => 1, remove => TRUE }
    ));
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
