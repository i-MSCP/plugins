#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by Sascha Bay
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
#
# @category    i-MSCP
# @package     iMSCP_Plugin
# @subpackage  AdminerSQL
# @copyright   2010-2015 by Sascha Bay
# @author      Sascha Bay <info@space2place.de>
# @contributor Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Plugin::AdminerSQL;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::Execute;

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
	my $rs = 0;
	
	# Load plugin configuration
	my $rdata = iMSCP::Database->factory()->doQuery(
		'plugin_name', 'SELECT plugin_name, plugin_config FROM plugin WHERE plugin_name = ?', 'AdminerSQL'
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	require JSON;
	JSON->import();

	my $config = decode_json($rdata->{'AdminerSQL'}->{'plugin_config'});

	my $productionDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/adminer";
	my $sourcesPath = "$main::imscpConfig{'GUI_ROOT_DIR'}/plugins/AdminerSQL/adminer-sources";
	my $compileCmd = "$main::imscpConfig{'CMD_PHP'} $sourcesPath/compile.php";
	my $panelUName =
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	# Copy needed css file
	unless($config->{'theme'} eq 'default') {
		my $file = iMSCP::File->new('filename' => "$sourcesPath/designs/$config->{'theme'}/adminer.css");
		$rs = $file->copyFile("$sourcesPath/adminer/static/default.css");
		return $rs if $rs;
	} else {
		my $file = iMSCP::File->new('filename' => "$sourcesPath/adminer/static/default_sik.css");
		$rs = $file->copyFile("$sourcesPath/adminer/static/default.css");
		return $rs if $rs;
	}

	my $driver = ($config->{'driver'} eq 'all') ? '' : $config->{'driver'};
	my $language = ($config->{'language'} eq 'all') ? '' : $config->{'language'};

	# Compile Adminer PHP file
	my ($stdout, $stderr);
	$rs = execute("$compileCmd $driver $language", \$stdout, \$stderr);
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

	my $version = '-' . $config->{'adminer_version'};
	$driver = ($driver ne '') ? '-' . $driver : '';
	$language = ($language ne '') ? '-' . $language : '';

	# Move compiled Adminer PHP file into production directory
	my $file = iMSCP::File->new('filename' => "$sourcesPath/adminer$version$driver$language.php");

	$rs = $file->owner($panelUName, $panelGName);
	return $rs if $rs;

	$rs = $file->mode(0440);
	return $rs if $rs;

	$rs = $file->moveFile("$productionDir/adminer.php");
	return $rs if $rs;

	# Move compiled Adminer editor PHP file into production directory
	$file = iMSCP::File->new('filename' => "$sourcesPath/editor$version$driver$language.php");

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
	if(-d "$main::imscpConfig{'GUI_PUBLIC_DIR'}/adminer") {
		iMSCP::Dir->new('dirname' => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/adminer")->remove();
	} else {
		0;
	}
}

=back

=head1 AUTHORS AND CONTRIBUTORS

 Sascha Bay <info@space2place.de>
 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
