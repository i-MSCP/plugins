=head1 NAME

 Plugin::AdminerSQL

=cut

# i-MSCP AdminerSQL plugin
# Copyright (C) 2010-2015 Sascha Bay <info@space2place.de>
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

package Plugin::AdminerSQL;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::Execute;
use JSON;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP AdminerSQL plugin.

=head1 PUBLIC METHODS

=over 4

=item enable()

 Process enable tasks

 Return int 0 on success, other on failure

=cut

sub enable()
{
	my $self = $_[0];

	my $productionDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/adminer";
	my $sourcesPath = "$main::imscpConfig{'PLUGINS_DIR'}/AdminerSQL/adminer-sources";
	my $compileCmd = "$main::imscpConfig{'CMD_PHP'} $sourcesPath/compile.php";
	my $panelUName =
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	# Copy needed css file
	unless($self->{'config'}->{'theme'} eq 'default') {
		my $file = iMSCP::File->new( filename => "$sourcesPath/designs/$self->{'config'}->{'theme'}/adminer.css" );
		my $rs = $file->copyFile("$sourcesPath/adminer/static/default.css");
		return $rs if $rs;
	} else {
		my $file = iMSCP::File->new( filename => "$sourcesPath/adminer/static/default_sik.css" );
		my $rs = $file->copyFile("$sourcesPath/adminer/static/default.css");
		return $rs if $rs;
	}

	my $driver = ($self->{'config'}->{'driver'} eq 'all') ? '' : $self->{'config'}->{'driver'};
	my $language = ($self->{'config'}->{'language'} eq 'all') ? '' : $self->{'config'}->{'language'};

	# Compile Adminer PHP file
	my ($stdout, $stderr);
	my $rs = execute("$compileCmd $driver $language", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Compile Adminer Editor PHP file
	$rs = execute("$compileCmd editor $driver $language", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Create production directory
	$rs = iMSCP::Dir->new(
		'dirname' => $productionDir
	)->make(
		{ 'user' => $panelUName, 'group' => $panelGName, 'mode' => 0550 }
	);
	return $rs if $rs;

	my $version = '-' . $self->{'config'}->{'adminer_version'};
	$driver = ($driver ne '') ? '-' . $driver : '';
	$language = ($language ne '') ? '-' . $language : '';

	# Move compiled Adminer PHP file into production directory
	my $file = iMSCP::File->new( filename => "$sourcesPath/adminer$version$driver$language.php" );

	$rs = $file->owner($panelUName, $panelGName);
	return $rs if $rs;

	$rs = $file->mode(0440);
	return $rs if $rs;

	$rs = $file->moveFile("$productionDir/adminer.php");
	return $rs if $rs;

	# Move compiled Adminer editor PHP file into production directory
	$file = iMSCP::File->new( filename => "$sourcesPath/editor$version$driver$language.php" );

	$rs = $file->owner($panelUName, $panelGName);
	return $rs if $rs;

	$rs = $file->mode(0440);
	return $rs if $rs;

	$file->moveFile("$productionDir/editor.php");
}

=item disable()

 Process disable tasks

 Return int 0 on success, other on failure

=cut

sub disable()
{
	iMSCP::Dir->new( dirname => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/adminer" )->remove();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Plugin::AdminerSQL or die on failure

=cut

sub _init
{
	my $self = $_[0];

	if($self->{'action'} ~~ [ 'enable', 'update', 'change' ]) {
		my $config = iMSCP::Database->factory()->doQuery(
			'plugin_name', 'SELECT plugin_name, plugin_config FROM plugin WHERE plugin_name = ?', 'AdminerSQL'
		);
		unless(ref $config eq 'HASH') {
			die("AdminerSQL: $config");
		}

		$self->{'config'} = decode_json($config->{'AdminerSQL'}->{'plugin_config'});
	}

	$self;
}

=back

=head1 Authors and Contributors

 Sascha Bay <info@space2place.de>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
