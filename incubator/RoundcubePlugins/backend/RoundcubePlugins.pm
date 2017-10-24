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
use iMSCP::Debug qw/ debug error getMessageByType /;
use iMSCP::Dir;
use iMSCP::Execute qw/ execute /;
use iMSCP::File;
use iMSCP::Composer;
use iMSCP::Service;
use iMSCP::TemplateParser qw/ replaceBloc /;
use JSON;
use PHP::Var qw/ export /;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 i-MSCP RoundcubePlugins plugin (backend).

=head1 PUBLIC METHODS

=over 4

=item uninstall()

 Perform uninstalation tasks

 Return int 0 on success, die on failure

=cut

sub uninstall
{
    my ($self) = @_;

    for( 'composer.json', 'composer.lock' ) {
        next unless -f "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/$_";
        iMSCP::File->new( filename => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/$_" )->delFile() == 0 or die(
            getMessageByType( 'error', { amount => 1, remove => 1 } )
        );
    }

    for ( keys %{$self->{'config_prev'}->{'plugins'}} ) {
        next if !defined $self->{'config_prev'}->{'plugins'}->{$_}->{'git'}->{'target_dir'}
            || $self->{'config_prev'}->{'plugins'}->{$_}->{'git'}->{'target_dir'} eq '/';
        iMSCP::Dir->new( dirname => $self->{'config_prev'}->{'plugins'}->{$_}->{'git'}->{'target_dir'} )->remove()
    }

    iMSCP::Dir->new(
        dirname => "$main::imscpConfig{'GUI_ROOT_DIR'}/data/persistent/plugins/RoundcubePlugins"
    )->remove();
}

=item enable( )

 Perform enable tasks

 Return int 0 on success, die on failure

=cut

sub enable
{
    my ($self) = @_;

    my $composer = $self->_getComposer();
    my $composerJson = $composer->getComposerJson( 'scalar' );
    my (@toConfigurePlugins, @toActivatePlugins);

    while ( my ($plugin, $meta) = each( %{$self->{'config'}->{'plugins'}} ) ) {
        next unless $meta->{'enabled'};
        push @toConfigurePlugins, $plugin;

        if ( $meta->{'git'}->{'repository'} && $meta->{'git'}->{'target_dir'} ) {
            $self->_cloneGitRepository( $meta->{'git'}->{'repository'}, $meta->{'git'}->{'target_dir'} );
        }

        unless ( $meta->{'composer'} ) {
            push @toActivatePlugins, $plugin;
            next;
        }

        if ( $meta->{'composer'}->{'repositories'} ) {
            push @{$composerJson->{'repositories'}}, $meta->{'composer'}->{'repositories'};
        }

        while ( my ($package, $version) = each( %{$meta->{'composer'}->{'require'}} ) ) {
            $composer->requirePackage( $package, $version );
        }
    }

    $composer->setStdRoutines( \&_stdRoutine, \&_stdRoutine )->installPackages();
    $self->_configurePlugins( @toConfigurePlugins );
    $self->_activatePlugins( @toActivatePlugins );
    0;
}

=item disable( )

 Perform disable tasks

 Return int 0 on success, die on failure

=cut

sub disable
{
    my ($self) = @_;

    # Outside of real 'disable' action, tasks done are useless
    return 0 unless $self->{'action'} eq 'disable';

    $self->_activatePlugins();
    $self->_getComposer()->setStdRoutines( \&_stdRoutine, \&_stdRoutine )->installPackages();
    0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _getComposer()

 Get composer object

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
        'cache-files-ttl'   => 15552000,
        cafile              => $main::imscpConfig{'DISTRO_CA_BUNDLE'},
        capath              => $main::imscpConfig{'DISTRO_CA_PATH'},
        'discard-changes'   => JSON::true,
        'htaccess-protect'  => JSON::false,
        'preferred-install' => 'dist',
        'process-timeout'   => 2000
    };
    $composerJson->{'minimum-stability'} = 'dev';
    $composerJson->{'prefer-stable'} = JSON::true;
    $composer;
}

=item _activatePlugins( )

 Activate plugins (disable those that are not enabled)

 Return void, die on failure

=cut

sub _activatePlugins
{
    my (undef, @plugins) = @_;

    my $file = iMSCP::File->new(
        filename => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/config/config.inc.php"
    );
    my $fileContent = $file->get();
    defined $fileContent or die ( sprintf( "Couldn't read %s", $file->{'filename'} ));

    $fileContent = replaceBloc(
        qr/(:?^\n)?\Q# Begin Plugin::RoundcubePlugins\E\n/m,
        qr/\Q# Ending Plugin::RoundcubePlugins\E\n/,
        '',
        $fileContent
    );

    if ( @plugins ) {
        $fileContent .= <<"EOF";

# Begin Plugin::RoundcubePlugins
\$config[\'plugins\'] = array_merge(\$config[\'plugins\'], @{ [ substr( export( \@plugins, purity => 1 ), 0, -1 ) ]});
# Ending Plugin::RoundcubePlugins
EOF
    }

    $file->set( $fileContent );
    $file->save() == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ));
}

=item _configurePlugins(@plugins)

 Configure plugins

 Param list @plugins List of plugins to configure
 Return void, die on failure

=cut

sub _configurePlugins
{
    my ($self, @plugins) = @_;

    my $pluginsDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/plugins";

    for( @plugins ) {
        my $conffile = $self->{'config'}->{'plugins'}->{$_}->{'config'}->{'file'} || 'config.inc.php';
        $self->{'config'}->{'plugins'}->{$_}->{'config'}->{'parameters'} &&
            ref $self->{'config'}->{'plugins'}->{$_}->{'config'}->{'parameters'} eq 'HASH' || next;

        next unless -f "$pluginsDir/$_/$conffile";

        my $file = iMSCP::File->new( filename => "$pluginsDir/$_/$conffile" );
        my $fileContent = $file->get;
        defined $fileContent or die( sprintf( "Couldn't read the %s file", $file->{'filename'} ));

        $fileContent = replaceBloc(
            qr/(:?^\n)?\Q# Begin Plugin::RoundcubePlugins\E\n/m,
            qr/\Q# Ending Plugin::RoundcubePlugins\E\n/,
            '',
            $fileContent
        );

        $fileContent =~ s/\n*\Q?>\E\n*/\n/m;
        $fileContent .= "\n# Begin Plugin::RoundcubePlugins\n";
        while ( my ($pname, $value) = each( %{$self->{'config'}->{'plugins'}->{$_}->{'config'}->{'parameters'}} ) ) {
            $fileContent .= export( "config['$pname']" => ref $value ? $value : \$value, purity => 1 ) . "\n";
        }
        $fileContent .= "# Ending Plugin::RoundcubePlugins\n";

        $file->{'filename'} = "$pluginsDir/$_/config.inc.php";
        $file->set( $fileContent );
        $file->save() == 0 or die( getMessageByType( 'error', { amount => 1, remove => 1 } ));
    }

    0;
}

=item _cloneGitRepository( $repository, $targetDir )

 Clone the given git repository, update it if it already exists

 Param string $repository Git repository URL
 Param string $targetDir Local target directory for the repository
 Return void, die on failure

=cut

sub _cloneGitRepository
{
    my ($self, $repository, $targetDir) = @_;

    return if $self->{'seen_git_repository'}->{$repository};

    my $rs = execute(
        [
            '/bin/su',
            '-l', $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'},
            '-s', '/bin/sh',
            '-c', "/usr/bin/git @{ [ -d $targetDir ? qq/-C $targetDir pull/ : 'clone --depth 1' ]}"
                . " --quiet $repository" . " @{ [ -d _ ? '' : $targetDir ]}"
        ],
        \my $stdout,
        \my $stderr
    );
    debug( $stdout ) if $stdout;
    $rs == 0 or die( sprintf( "Couldn't clone git repository: %s", $stderr || 'Unknown error' ));

    $self->{'seen_git_repository'}->{$repository} = 1;
}

=item _stdRoutine

 STD routine for the iMSCP::Composer object

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
