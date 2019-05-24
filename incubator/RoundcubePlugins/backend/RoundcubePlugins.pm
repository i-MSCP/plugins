=head1 NAME

 Plugin::RoundcubePlugins

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2019 Laurent Declercq <l.declercq@nuxwin.com>
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

package Plugin::RoundcubePlugins;

use strict;
use warnings;
use File::Basename 'basename';
use File::chmod 'chmod';
use iMSCP::Boolean;
use iMSCP::Composer;
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::Dir;
use iMSCP::Execute 'executeNoWait';
use iMSCP::File;
use iMSCP::Service;
use iMSCP::TemplateParser 'replaceBloc';
use JSON;
use PHP::Var 'export';
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP RoundcubePlugins plugin (backend).

=head1 PUBLIC METHODS

=over 4

=item install( )

 Installation tasks
 
 Return int 0 on success, 1 on failure

=cut

sub install
{
    local $@;
    eval {
        iMSCP::Dir->new(
            dirname => "$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/plugins/RoundcubePlugins"
        )->make( {
            user  => $::imscpConfig{'SYSTEM_USER_PREFIX'} . $::imscpConfig{'SYSTEM_USER_MIN_UID'},
            group => $::imscpConfig{'SYSTEM_USER_PREFIX'} . $::imscpConfig{'SYSTEM_USER_MIN_UID'},
            mode  => 0750
        } );
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item uninstall( )

 Uninstallation tasks

 Return int 0 on success, 1 on failure

=cut

sub uninstall
{
    local $@;
    eval {
        iMSCP::Dir->new(
            dirname => "$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/plugins/RoundcubePlugins"
        )->remove();
    };
    if ( $@ ) {
        error( $@ );
        return 1;
    }

    0;
}

=item enable( )

 Configure and activate the Roundcube plugins

 Return int 0 on success, 1 on failure

=cut

sub enable
{
    my ( $self ) = @_;

    local $@;
    my $rs = eval {
        return 0 unless defined $self->{'config'}->{'plugin_definitions'}
            && ref $self->{'config'}->{'plugin_definitions'} eq 'HASH';

        my @pluginNames;
        my $composer = $self->_getComposer();
        my $composerJson = $composer->getComposerJson( TRUE );

        while ( my ( $pluginName, $pluginDef ) = each( %{
            $self->{'config'}->{'plugin_definitions'} }
        ) ) {
            next unless $pluginDef->{'enabled'};

            # Add the composer repositories if there are some defined
            if ( ref $pluginDef->{'composer'}->{'repositories'} eq 'ARRAY' ) {
                for my $repository ( @{ $pluginDef->{'composer'}->{'repositories'} } ) {
                    next unless ref $repository eq 'HASH'
                        && ref \( $repository->{'type'} // \1 ) eq 'SCALAR'
                        && length $repository->{'type'}
                        && ref \( $repository->{'url'} // \1 ) eq 'SCALAR'
                        && length $repository->{'url'};

                    unless ( grep {
                        $_->{'type'} eq $repository->{'type'}
                            && $_->{'url'} eq $repository->{'url'}
                    } @{ $composerJson->{'repositories'} } ) {
                        push @{ $composerJson->{'repositories'} }, $repository;
                    }
                }
            }

            # Add the composer packages if there are some defined
            if ( ref $pluginDef->{'composer'}->{'require'} eq 'HASH' ) {
                while ( my ( $package, $version ) = each(
                    %{ $pluginDef->{'composer'}->{'require'} }
                ) ) {
                    next unless ref \( $version // \1 ) eq 'SCALAR';
                    $composer->require( $package, $version );
                }
            }

            # Execute the plugin configuration script for the 'preconfigure'
            # stage if one is provided
            if ( ref \( $pluginDef->{'config'}->{'script'} // \1 ) eq 'SCALAR'
                && length $pluginDef->{'config'}->{'script'}
                && -f $pluginDef->{'config'}->{'script'}
            ) {
                # Make sure that the configuration script is executable
                $File::chmod::UMASK = 0; # Stick to system CHMOD(1) behavior
                chmod( 'u+x', $pluginDef->{'config'}->{'script'} ) or die( sprintf(
                    "Couldn't turns on the executable bit on the %s file",
                    $pluginDef->{'config'}->{'script'}
                ));

                my $stderr = '';
                executeNoWait(
                    [
                        $pluginDef->{'config'}->{'script'},
                        'preconfigure',
                        ( ref $pluginDef->{'config'}->{'script_argv'}->{'preconfigure'} eq 'ARRAY'
                            ? @{ $pluginDef->{'config'}->{'script_argv'}->{'preconfigure'} }
                            : ()
                        )
                    ],
                    \&_stdRoutine,
                    sub { $stderr .= $_[0] }
                ) == 0 or die( $stderr || 'Unknown error' );
            }

            # Schedule the plugin for configuration and activation
            push @pluginNames, $pluginName;
        }

        # Return early if there are no plugins to configure and activate
        return 0 unless @pluginNames;

        $composer
            ->setStdRoutines( \&_stdRoutine, \&_stdRoutine )
            ->update( TRUE );

        $self->_configurePlugins( @pluginNames );
        $self->_togglePlugins( 'activate', @pluginNames );

        if ( !defined $::execmode || $::execmode ne 'setup' ) {
            # Reload the imscp_panel service to flush opcache cache
            iMSCP::Service->getInstance()->reload( 'imscp_panel' );
        }

        0;
    };
    if ( $@ ) {
        error( $@ );
        $rs = 1;
    }

    $rs;
}

=item disable( )

 Deactivate and deconfigure the Roundcube plugins

 Return int 0 on success, 1 on failure

=cut

sub disable
{
    my ( $self ) = @_;

    local $@;
    my $rs = eval {
        return 0 unless defined $self->{'config_prev'}->{'plugin_definitions'}
            && ref $self->{'config_prev'}->{'plugin_definitions'} eq 'HASH';

        my @pluginNames;
        my $composer = $self->_getComposer();
        my $composerJson = $composer->getComposerJson( TRUE );

        while ( my ( $pluginName, $pluginDef ) = each(
            %{ $self->{'config_prev'}->{'plugin_definitions'} }
        ) ) {
            next unless $pluginDef->{'enabled'};

            # Remove the composer packages if there are some defined
            if ( ref $pluginDef->{'composer'}->{'require'} eq 'HASH' ) {
                for my $package ( keys %{ $pluginDef->{'composer'}->{'require'} } ) {
                    $composer->remove( $package );
                }
            }

            # Remove the composer repositories if there are some defined
            if ( ref $pluginDef->{'composer'}->{'repositories'} eq 'ARRAY' ) {
                for my $repository ( @{ $pluginDef->{'composer'}->{'repositories'} } ) {
                    next unless ref $repository eq 'HASH'
                        && ref \( $repository->{'type'} // \1 ) eq 'SCALAR'
                        && length $repository->{'type'}
                        && ref \( $repository->{'url'} // \1 ) eq 'SCALAR'
                        && length $repository->{'url'};

                    @{ $composerJson->{ 'repositories' } } = grep {
                        $_->{'type'} ne $repository->{'type'}
                            && $_->{'url'} ne $repository->{'url'}
                    } @{ $composerJson->{'repositories'} };
                }
            }

            # Execute the plugin configuration script for the
            # 'predeconfigure' stage if one is provided
            if ( ref \( $pluginDef->{'config'}->{'script'} // \1 ) eq 'SCALAR'
                && length $pluginDef->{'config'}->{'script'}
                && -f $pluginDef->{'config'}->{'script'}
            ) {
                # Make sure that the script is executable
                $File::chmod::UMASK = 0; # Stick to system CHMOD(1) behavior
                chmod( 'u+x', $pluginDef->{'config'}->{'script'} ) or die( sprintf(
                    "Couldn't turns on the executable bit on the %s file",
                    $pluginDef->{'config'}->{'script'}
                ));

                my $stderr = '';
                executeNoWait(
                    [
                        $pluginDef->{'config'}->{'script'},
                        'predeconfigure',
                        ( ref $pluginDef->{'config'}->{'script_argv'}->{'predeconfigure'} eq 'ARRAY'
                            ? @{ $pluginDef->{'config'}->{'script_argv'}->{'predeconfigure'} }
                            : ()
                        )
                    ],
                    \&_stdRoutine,
                    sub { $stderr .= $_[0] }
                ) == 0 or die( $stderr || 'Unknown error' );
            }

            # Schedule the plugin for deactivation and deconfiguration
            push @pluginNames, $pluginName;
        }

        # Return early if there are no plugins to deactivate and deconfigure
        return 0 unless @pluginNames;

        # No need to run composer on the 'change' action as this will be done
        # in enable()
        if ( $self->{'action'} ne 'change' ) {
            $composer
                ->setStdRoutines( \&_stdRoutine, \&_stdRoutine )
                ->update( TRUE );
        }

        $self->_togglePlugins( 'deactivate', @pluginNames );
        $self->_deconfigurePlugins( @pluginNames );

        # No need to reload the imscp_panel service on the 'change' action as
        # this will be done in enable()
        if ( $self->{'action'} ne 'change'
            && ( !defined $::execmode || $::execmode ne 'setup' )
        ) {
            # Reload the imscp_panel service to flush opcache cache
            iMSCP::Service->getInstance()->reload( 'imscp_panel' );
        }

        0;
    };
    if ( $@ ) {
        error( $@ );
        $rs = 1;
    }

    $rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _getComposer( )

 Get iMSCP::Composer object

 Return iMSCP::Composer, die on failure

=cut

sub _getComposer
{
    my ( $self ) = @_;

    $self->{'_composer'} //= iMSCP::Composer->new(
        user                 => $::imscpConfig{'SYSTEM_USER_PREFIX'}
            . $::imscpConfig{'SYSTEM_USER_MIN_UID'},
        composer_home        => "$::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/.composer",
        composer_working_dir => "$::imscpConfig{'GUI_ROOT_DIR'}/vendor/imscp/roundcube/roundcubemail",
        composer_json        => 'composer.json'
    );
}

=item _configurePlugins( @pluginNames )

 Configure the given plugins

 Param list @pluginNames List of plugins to configure
 Return void, die on failure

=cut

sub _configurePlugins
{
    my ( $self, @pluginNames ) = @_;

    my $pluginsDir = "$::imscpConfig{'GUI_ROOT_DIR'}/vendor/imscp/roundcube/roundcubemail/plugins";
    my $pluginDefs = $self->{'config'}->{'plugin_definitions'};

    for my $pluginName ( @pluginNames ) {
        next unless defined( my $config = $pluginDefs->{$pluginName}->{'config'} );

        ref $config eq 'HASH' or die( sprintf(
            "Invalid 'config' section in the '%s' Roundcube plugin definition. Associative array expected.",
            $pluginName
        ));

        # Override the default plugin configuration template file with the
        # provided one if defined, else look for a default one
        my $conffile = ref \( $config->{'file'} // \1 ) eq 'SCALAR'
            && length $config->{'file'}
            ? $config->{'file'} : 'config.inc.php.dist';

        if ( -f "$pluginsDir/$pluginName/$conffile" ) {
            my $file = iMSCP::File->new( filename => "$pluginsDir/$pluginName/$conffile" );
            defined( my $fileC = $file->getAsRef ) or die( getMessageByType(
                'error', { amount => 1, remove => TRUE }
            ));

            ${ $fileC } = replaceBloc(
                qr%(:?^\n)?\Q// i-MSCP Plugin::RoundcubePlugins BEGIN.\E\n%m,
                qr%\Q// i-MSCP Plugin::RoundcubePlugins ENDING.\E\n%,
                '',
                ${ $fileC }
            );
            ${ $fileC } =~ s/\n*\Q?>\E\n*/\n/m;

            # - Insert the provided plugin configuration parameters if any
            # - Insert the 'include' statement for the external plugin
            #   configuration file if any
            if ( exists $config->{'parameters'}
                || exists $config->{'include_file'}
            ) {
                ${ $fileC } .= "\n// i-MSCP Plugin::RoundcubePlugins BEGIN.\n";

                if ( ref \( $config->{'include_file'} // \1 ) eq 'SCALAR'
                    && length $config->{'include_file'}
                    && -f $config->{'include_file'}
                ) {
                    ${ $fileC } .= "include_once '$config->{'include_file'}';\n";
                }

                if ( ref $config->{'parameters'} eq 'HASH' ) {
                    while ( my ( $pname, $value ) = each(
                        %{ $config->{'parameters'} }
                    ) ) {
                        ${ $fileC } .= <<"EOT";
@{[ export( qq/config['$pname']/ => ref $value ? $value : \$value, purity => TRUE, short => TRUE ) ]}
EOT
                    }
                }

                ${ $fileC } .= "// i-MSCP Plugin::RoundcubePlugins ENDING.\n";
            }

            $file->{'filename'} = "$pluginsDir/$pluginName/config.inc.php";
            $file->save() == 0 or die( getMessageByType(
                'error', { amount => 1, remove => TRUE }
            ));
        }

        # Execute the plugin configuration script for the 'configure' stage if
        # one is defined
        if ( ref \( $config->{'script'} // \1 ) eq 'SCALAR'
            && length $config->{'script'}
            && -f $config->{'script'}
        ) {
            my $stderr = '';
            executeNoWait(
                [
                    $config->{'script'},
                    'configure',
                    ( ref $config->{'script_argv'}->{'configure'} eq 'ARRAY'
                        ? @{ $config->{'script_argv'}->{'configure'} }
                        : ()
                    )
                ],
                \&_stdRoutine,
                sub { $stderr .= $_[0] }
            ) == 0 or die( $stderr || 'Unknown error' );
        }
    }
}

=item _configurePlugins( @pluginNames )

 Deconfigure the given plugins

 Param list @pluginNames List of plugins to deconfigure
 Return void, die on failure

=cut

sub _deconfigurePlugins
{
    my ( $self, @pluginNames ) = @_;

    my $pluginsDir = "$::imscpConfig{'GUI_ROOT_DIR'}/vendor/imscp/roundcube/roundcubemail/plugins";
    my $pluginDefs = $self->{'config_prev'}->{'plugin_definitions'};

    for my $pluginName ( @pluginNames ) {
        next unless defined( my $config = $pluginDefs->{$pluginName}->{'config'} );

        ref $config eq 'HASH' or die( sprintf(
            "Invalid 'config' section in the '%s' Roundcube plugin definition. Associative array expected.",
            $pluginName
        ));

        if ( -f "$pluginsDir/$pluginName/config.inc.php" ) {
            iMSCP::File->new(
                filename => "$pluginsDir/$pluginName/config.inc.php"
            )->delFile() == 0 or die( getMessageByType(
                'error', { amount => 1, remove => TRUE }
            ));
        }

        # Execute the plugin configuration script for the 'deconfigure' stage
        # if one is defined
        if ( ref \( $config->{'script'} // \1 ) eq 'SCALAR'
            && length $config->{'script'}
            && -f $config->{'script'}
        ) {
            my $stderr = '';
            executeNoWait(
                [
                    $config->{'script'},
                    'deconfigure',
                    ( ref $config->{'script_argv'}->{'deconfigure'} eq 'ARRAY'
                        ? @{ $config->{'script_argv'}->{'deconfigure'} }
                        : ()
                    )
                ],
                \&_stdRoutine,
                sub { $stderr .= $_[0] }
            ) == 0 or die( $stderr || 'Unknown error' );
        }
    }
}

=item _togglePlugins( $action, @pluginNames )

 Activate or deactivate the given plugins

 Param string $action Action to be performed (activate|deactivate)
 Param list @pluginNames Plugin to activate or deactivate
 Return void, die on failure

=cut

sub _togglePlugins
{
    my ( undef, $action, @pluginNames ) = @_;

    grep ( $action eq $_, qw/ activate deactivate / ) or die(
        'Invalid $action parameter'
    );

    my $file = iMSCP::File->new(
        filename => "$::imscpConfig{'GUI_ROOT_DIR'}/vendor/imscp/roundcube/roundcubemail/config/config.inc.php"
    );
    defined( my $fileC = $file->getAsRef()) or die( getMessageByType(
        'error', { amount => 1, remove => TRUE }
    ));

    my @activePlugins;

    if ( ${ $fileC } =~ /\$config\s*\[\s*['"]plugins['"]\s*\]\s*=\s*(?:array\s*\(|\[)(.*)(?:\)|\])\s*;/is ) {
        @activePlugins = split /,+/, $1 =~ s/[\s'"]+//grs;

        for my $pluginName ( @pluginNames ) {
            if ( $action eq 'activate' ) {
                # Add the plugin to the list of activated plugins
                # unless it is already in the list
                unless ( grep ( $_ eq $pluginName, @activePlugins ) ) {
                    push @activePlugins, $pluginName;
                }
            } else {
                # Remove the plugin from the activated plugins list
                @activePlugins = grep ( $_ ne $pluginName, @activePlugins );
            }
        }

        #${ $fileC } =~ s/\$config\s*\[\s*['"]plugins['"]\s*\].*?;/\$config['plugins'] = @{ [
        ${ $fileC } =~ s/(\$config\s*\[\s*['"]plugins['"]\s*\]).*?;/$1 = @{ [
            export( [ sort @activePlugins ], purity => TRUE, short => TRUE )
        ] }/is
    } else {
        @activePlugins = @pluginNames if $action eq 'activate';
        ${ $fileC } .= <<"EOF";

// List of active plugins (in plugins/ directory)
\$config[\'plugins\'] = @{ [ export( [ sort @activePlugins ], purity => TRUE, short => TRUE ) ] }
EOF
    }

    $file->save() == 0 or die( getMessageByType(
        'error', { amount => TRUE, remove => TRUE }
    ));
}

=item _stdRoutine

 STD routine

 Return void
 
=cut

sub _stdRoutine
{
    $_[0] =~ s/\x08+/\n/g;
    $_[0] =~ s/^[\s\n]+|[\s\n]+$//g;

    debug( $_[0] ) if length $_[0];
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
