=head1 NAME

 Plugin::AdminerSQL

=cut

# i-MSCP AdminerSQL plugin
# Copyright (C) 2013-2016 Laurent Declercq <l.declercq@nuxwin.com>
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

package Plugin::AdminerSQL;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::Service;
use Cwd;
use parent 'Common::SingletonClass';

my $ADMINER_VERSION = '4.2.1';

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP AdminerSQL plugin.

=head1 PUBLIC METHODS

=over 4

=item enable()

 Process enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = shift;

	my $curDir = getcwd();
	my $prodDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/adminer";
	my $srcDir = "$main::imscpConfig{'PLUGINS_DIR'}/AdminerSQL/src";
	my $panelUName =
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	# Create production directory
	my $rs = iMSCP::Dir->new( dirname => $prodDir )->make({ user => $panelUName, group => $panelGName, mode => 0550 });
	return $rs if $rs;


	my $file = iMSCP::File->new( filename => "$srcDir/designs/$self->{'config'}->{'theme'}/adminer.css" );
	$rs = $file->copyFile("$srcDir/adminer/static/default.css");
	return $rs if $rs;

	my $fileSuffix = '-' . $ADMINER_VERSION .
		( ($self->{'config'}->{'driver'} eq 'all') ? '' :  '-' . $self->{'config'}->{'driver'} ) . '.php';

	unless(chdir($srcDir)) {
		error(sprintf("Unable to change directory to $srcDir: %s", $!));
		return 1;
	}

	# Compile Adminer
	my ($stdout, $stderr);
	$rs = execute("php $srcDir/compile.php $self->{'config'}->{'driver'}", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Install Adminer in production directory
	my $file = iMSCP::File->new( filename => "$srcDir/adminer$fileSuffix" );

	$rs = $file->owner($panelUName, $panelGName);
	return $rs if $rs;

	$rs = $file->mode(0440);
	return $rs if $rs;

	$rs = $file->moveFile("$prodDir/adminer.php");
	return $rs if $rs;

	# Compile Adminer editor
	$rs = execute("php $srcDir/compile.php editor $self->{'config'}->{'driver'}", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Install Adminer editor in production directory
	$file = iMSCP::File->new( filename => "$srcDir/editor$fileSuffix" );

	$rs = $file->owner($panelUName, $panelGName);
	return $rs if $rs;

	$rs = $file->mode(0440);
	return $rs if $rs;

	$rs = $file->moveFile("$prodDir/editor.php");
	return $rs if $rs;

	unless(chdir($curDir)) {
		error(sprintf("Unable to change directory to $curDir: %s", $!));
		return 1;
	}

	unless(defined $main::execmode && $main::execmode eq 'setup') {
		# Needed to flush opcode cache if any
		eval { iMSCP::Service->getInstance()->restart('imscp_panel', 'defer'); };
	}

	0;
}

=item disable()

 Process disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
	my $rs = iMSCP::Dir->new( dirname => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/adminer" )->remove();
	return $rs if $rs;

	unless(defined $main::execmode && $main::execmode eq 'setup') {
		# Needed to flush opcode cache if any
		iMSCP::Service->getInstance()->restart('imscp_panel', 'defer');
	}

	0;
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>
 Sascha Bay <info@space2place.de>

=cut

1;
__END__
