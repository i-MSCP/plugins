=head1 NAME

 Plugin::RoundcubePlugins

=cut

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

package Plugin::RoundcubePlugins;

use strict;
use warnings;
use File::Basename qw/ basename /;
use File::chmod qw/ chmod /;
use iMSCP::Composer;
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::Dir;
use iMSCP::Execute qw/ executeNoWait /;
use iMSCP::File;
use iMSCP::TemplateParser qw/ replaceBloc /;
use JSON;
use PHP::Var qw/ export /;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP RoundcubePlugins plugin (backend).

=head1 PUBLIC METHODS

=over 4

=item update( $fromVersion )

 Process uninstalation tasks

 Param string $fromVersion Version from which the plugin is being updated
 Return int 0 on success, die on failure

=cut

sub update
{
    my (undef, $fromVersion) = @_;

    return 0 if version->parse( $fromVersion ) > version->parse( '3.0.0' );

    # Make sure that composer.json-dist is available
    if ( -f "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/composer.json"
        && !-f "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/composer.json-dist"
    ) {
        iMSCP::File->new( filename => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/composer.json" )->moveFile(
            "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/composer.json-dist"
        ) == 0 or die ( getMessageByType( 'error', { amount => 1 => remove => 1 } ));
    }

    # Remove old .composer directory
    iMSCP::Dir->new( dirname => "$main::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/.composer" )->remove();

    # Remove old-way configuration
    for( '/etc/dovecot/dovecot.conf', "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/config/config.inc.php" ) {
        next unless -f;

        my $file = iMSCP::File->new( filename => $_ );
        my $fileContent = $file->get();
        defined $fileContent or die sprintf( "Couldn't read %s file", $file->{'filename'} );

        $file->set( replaceBloc(
            qr/^\s*\Q# Begin Plugin::RoundcubePlugin::\E.*\n/m,
            qr/\Q# Ending Plugin::RoundcubePlugin::\E.*\n/,
            '',
            $fileContent
        ));
        $file->save() == 0 or die ( getMessageByType( 'error', { amount => 1 => remove => 1 } ));

        require iMSCP::Service;
        iMSCP::Service->getInstance()->restart( 'dovecot' );
    }

    # Fix permissions, else composer will fail to delete older files
    my $stderr = '';
    executeNoWait(
        [ 'perl', "$main::imscpConfig{'ENGINE_ROOT_DIR'}/setup/set-gui-permissions.pl", '-v' ],
        \&_stdRoutine,
        sub { $stderr .= $_[0] }
    ) == 0 or die( $stderr || 'Unknown error' );
    0;
}

=item uninstall()

 Process uninstalation tasks

 Return int 0 on success, die on failure

=cut

sub uninstall
{
    for( 'composer.json', 'composer.lock' ) {
        next unless -f "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/$_";
        iMSCP::File->new( filename => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/$_" )->delFile() == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } )
        );
    }

    iMSCP::Dir->new(
        dirname => "$main::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/plugins/RoundcubePlugins"
    )->remove();
}

=item enable( )

 Process enable tasks

 Return int 0 on success, die on failure

=cut

sub enable
{
    my ($self) = @_;

    # Force 'enable' action (subtask of change action)
    $self->{'action'} = 'enable';

    my $composer = $self->_getComposer();
    my $composerJson = $composer->getComposerJson( 'scalar' );
    my @plugins;

    while ( my ($plugin, $meta) = each( %{$self->{'config'}->{'plugins'}} ) ) {
        next unless $meta->{'enabled'};

        if ( $meta->{'composer'}->{'repositories'} ) {
            push @{$composerJson->{'repositories'}}, $_ for @{$meta->{'composer'}->{'repositories'}};
        }

        while ( my ($package, $version) = each( %{$meta->{'composer'}->{'require'}} ) ) {
            $composer->requirePackage( $package, $version );
        }

        push @plugins, $plugin;
    }

    $composer
        ->setStdRoutines( \&_stdRoutine, \&_stdRoutine )
        ->updatePackages();
    $self->_configurePlugins( @plugins );
    $self->_togglePlugins( @plugins );
    0;
}

=item disable( )

 Process disable tasks

 Return int 0 on success, die on failure

=cut

sub disable
{
    my ($self) = @_;

    my @plugins;

    if ( $self->{'action'} eq 'disable' ) {
        $self->_getComposer()
            ->setStdRoutines( \&_stdRoutine, \&_stdRoutine )
            ->updatePackages();
        @plugins = grep (
            $self->{'config_prev'}->{'plugins'}->{$_}->{'enabled'}, keys %{$self->{'config_prev'}->{'plugins'}}
        );
    } else {
        while ( my ($plugin, $meta) = each( %{$self->{'config'}->{'plugins'}} ) ) {
            next if $meta->{'enabled'} || !$self->{'config_prev'}->{'plugins'}->{$plugin}->{'enabled'};
            push @plugins, $plugin;
        }
    }

    return 0 unless @plugins;

    $self->_togglePlugins( @plugins );
    $self->_configurePlugins( @plugins );
    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _getComposer()

 Get iMSCP::Composer object

 Return iMSCP::Composer, die on failure

=cut

sub _getComposer
{
    my $rcDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail";
    my $composerJson = iMSCP::File->new( filename => "$rcDir/composer.json-dist" )->get();
    defined $composerJson or die( sprintf( "Couldn't read Roundcube composer.json-dist file" ));

    iMSCP::Dir->new( dirname => "$main::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/plugins/RoundcubePlugins" )->make(
        {
            user  => $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'},
            group => $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'},
            mode  => 0750
        }
    );

    my $composer = iMSCP::Composer->new(
        user          => $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'},
        home_dir      => "$main::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/plugins/RoundcubePlugins",
        working_dir   => $rcDir,
        composer_path => '/usr/local/bin/composer',
        composer_json => $composerJson
    );
    $composerJson = $composer->getComposerJson( 'scalar' );
    # We provide our own installer for Roundcube plugins
    delete $composerJson->{'require'}->{'roundcube/plugin-installer'};
    $composer->requirePackage( 'imscp/roundcube-plugin-installer', '^1.0' );
    $composerJson->{'config'} = {
        'cache-files-ttl'        => 15552000,
        cafile                   => $main::imscpConfig{'DISTRO_CA_BUNDLE'},
        capath                   => $main::imscpConfig{'DISTRO_CA_PATH'},
        'classmap-authoritative' => JSON::false,
        'discard-changes'        => JSON::true,
        'htaccess-protect'       => JSON::false,
        'optimize-autoloader'    => JSON::true,
        'apcu-autoloader'        => JSON::true,
        'preferred-install'      => 'dist',
        'process-timeout'        => 2000
    };
    $composerJson->{'minimum-stability'} = 'dev';
    $composerJson->{'prefer-stable'} = JSON::true;
    $composer;
}

=item _configurePlugins(@plugins)

 Configure (or deconfigure) the given plugins

 Param list @plugins List of plugins to configure (or deconfigure)
 Return void, die on failure

=cut

sub _configurePlugins
{
    my ($self, @plugins) = @_;

    my $pluginsDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/plugins";

    for( @plugins ) {
        my $config = $self->{'config'}->{'plugins'}->{$_}->{'config'} || next;
        ref $config eq 'HASH' or die( 'Invalid `config` parameter' );

        if ( $config->{'script'} ) {
            ref $config->{'script'} eq '' or die( 'Invalid `include_file` parameter' );
            -f $config->{'script'} or die( sprintf( 'File %s is missing or not executable', $config->{'script'} ));
            # Make sure that the script is executable
            $File::chmod::UMASK = 0; # Stick to system CHMOD(1) behavior
            chmod( 'u+x', $config->{'script'} ) or die(
                sprintf( "Couldn't turns on the executable bit on the %s file", $config->{'script'} )
            );

            my $stderr = '';
            executeNoWait(
                [ $config->{'script'}, $self->{'action'} eq 'enable' ? 'pre-configure' : 'pre-deconfigure' ],
                \&_stdRoutine,
                sub { $stderr .= $_[0] }
            ) == 0 or die( $stderr || 'Unknown error' );
        }

        if ( $self->{'action'} eq 'enable' ) {
            my $conffile = $config->{'file'} || 'config.inc.php.dist';
            next unless -f "$pluginsDir/$_/$conffile";

            my $file = iMSCP::File->new( filename => "$pluginsDir/$_/$conffile" );
            my $fileContent = $file->get;
            defined $fileContent or die( sprintf( "Couldn't read the %s file", $file->{'filename'} ));

            $fileContent = replaceBloc(
                qr%(:?^\n)?\Q// i-MSCP Plugin::RoundcubePlugins BEGIN.\E\n%m,
                qr%\Q// i-MSCP Plugin::RoundcubePlugins ENDING.\E\n%,
                '',
                $fileContent
            );

            $fileContent =~ s/\n*\Q?>\E\n*/\n/m;

            if ( $config->{'parameters'} || $config->{'include_file'} ) {
                $fileContent .= "\n// i-MSCP Plugin::RoundcubePlugins BEGIN.\n";

                if ( $config->{'include_file'} ) {
                    ref $config->{'include_file'} eq '' or die( 'Invalid `include_file` parameter' );
                    -f $config->{'include_file'} or die( sprintf( "File %s not found", $config->{'include_file'} ));
                    $fileContent .= "include_once '$config->{'include_file'}';\n";
                }

                if ( $config->{'parameters'} ) {
                    ref $config->{'parameters'} eq 'HASH' or die( 'Invalid `parameters` parameter' );

                    while ( my ($pname, $value) = each( %{$config->{'parameters'}} ) ) {
                        $fileContent .= <<"EOT";
@{[ export( qq/config['$pname']/ => ref $value ? $value : \$value, purity => 1, short => 1 ) ]}
EOT
                    }
                }

                $fileContent .= "// i-MSCP Plugin::RoundcubePlugins ENDING.\n";
            }

            $file->{'filename'} = "$pluginsDir/$_/config.inc.php";
            $file->set( $fileContent );
            $file->save() == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ));
        } elsif ( -f "$pluginsDir/$_/config.inc.php" ) {
            iMSCP::File->new( filename => "$pluginsDir/$_/config.inc.php" )->delFile() == 0 or die(
                getMessageByType( 'error', { amount => 1, remove => 1 } )
            );
        }

        if ( $config->{'script'} ) {
            my $stderr = '';
            executeNoWait(
                [ $config->{'script'}, $self->{'action'} eq 'enable' ? 'configure' : 'deconfigure' ],
                \&_stdRoutine,
                sub { $stderr .= $_[0] }
            ) == 0 or die( $stderr || 'Unknown error' );
        }
    }
}

=item _togglePlugins( @plugins )

 Activate (or deactivate) the given plugins

 Param list @plugins Plugin to activate (or deactivate)
 Return void, die on failure

=cut

sub _togglePlugins
{
    my ($self, @plugins) = @_;

    my $file = iMSCP::File->new(
        filename => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/config/config.inc.php"
    );
    my $fileContent = $file->get();
    defined $fileContent or die ( sprintf( "Couldn't read %s", $file->{'filename'} ));

    if ( $fileContent =~ /\$config\s*\[\s*['"]plugins['"]\s*\]\s*=\s*(?:array\s*\(|\[)(.*)(?:\)|\])\s*;/is ) {
        my @activePlugins = split /,+/, $1 =~ s/[\s'"]+//grs;

        for my $plugin ( @plugins ) {
            if ( $self->{'action'} eq 'enable' && !grep($_ eq $plugin, @activePlugins) ) {
                push @activePlugins, $plugin;
            } elsif ( $self->{'action'} eq 'disable' ) {
                @activePlugins = grep( $_ ne $plugin, @activePlugins);
            }
        }

        return unless $fileContent =~ s/\$config\s*\[['"]plugins['"]\s*\].*;/\$config['plugins'] = @{
            [ export( [ sort @activePlugins ], purity => 1, short => 1 ) ]
        }/is
    } else {
        @plugins = undef if $self->{'action'} eq 'disable';
        $fileContent .= <<"EOF";

// List of active plugins (in plugins/ directory)
\$config[\'plugins\'] = @{ [ export( [ sort @plugins ], purity => 1, short => 1 ) ] }
EOF
    }

    $file->set( $fileContent );
    $file->save() == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ));
}

=item _stdRoutine

 STD routine

 Return void
 
=cut

sub _stdRoutine
{
    ( my $out = $_[0] ) =~ s/\x08+/\n/g;
    $out =~ s/^[\s\n]+|[\s\n]+$//g;
    debug( $out ) if $out ne '';
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
