#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2013 by internet Multi Server Control Panel
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
# @subpackage  Monitorix
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Sascha Bay <info@space2place.de>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Plugin::Monitorix;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::Database;

use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP Monitorix plugin.

=head1 PUBLIC METHODS

=over 4

=item install()

 Perform install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	if(! -x '/usr/bin/monitorix') {
		error('Unable to find monitorix daemon. Please take a look on the README.md and install the monitorix package first.');
		return 1;
	}

	if(! -f '/usr/share/monitorix/cgi/monitorix.cgi') {
		error('Unable to find monitorix cgi script. Please check the path: /usr/share/monitorix/cgi/monitorix.cgi');
		return 1;
	}

	my $rs = $self->_checkRequirements();
	return $rs if $rs;

	$rs = $self->_registerCronjob();
	return $rs if $rs;

	$rs = $self->_restartDaemonMonitorix();
	return $rs if $rs;

	$self->buildMonitorixGraphics();
}

=item change()

 Perform change tasks

 Return int 0 on success, other on failure

=cut

sub change
{
	my $self = shift;

	my $rs = $self->_registerCronjob();
	return $rs if $rs;

	$self->buildMonitorixGraphics();
}

=item update()

 Perform update tasks

 Return int 0 on success, other on failure

=cut

sub update
{
	my $self = shift;

	my $rs = $self->_modifyMonitorixSystemConfigEnabledGraphics();
	return $rs if $rs;

	$rs = $self->_restartDaemonMonitorix();
	return $rs if $rs;

	$rs = $self->_unregisterCronjob();
	return $rs if $rs;

	$rs = $self->_registerCronjob();
	return $rs if $rs;

	$self->buildMonitorixGraphics();
}

=item enable()

 Perform enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = shift;

	my $rs = $self->_registerCronjob();
	return $rs if $rs;

	$self->buildMonitorixGraphics();
}

=item disable()

 Perform disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
	my $self = shift;

	$self->_unregisterCronjob();
}

=item uninstall()

 Perform uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	my $rs = $self->_unregisterCronjob();
	return $rs if $rs;

	$rs = $self->_modifyMonitorixCgiFile('remove');
	return $rs if $rs;

	$rs = $self->_modifyMonitorixSystemConfig('remove');
	return $rs if $rs;

	$rs = $self->_restartDaemonMonitorix();
	return $rs if $rs;

	if(-f '/etc/apache2/conf.d/monitorix.old') {
		$rs = $self->_modifyDefaultMonitorixApacheConfig('add');
		return $rs if $rs;

		$rs = $self->_restartDaemonApache();
		return $rs if $rs;
	}

	0;
}

=item run()

 Create system monitoring graphics using the last available statistics data

 Return int 0 on success, other on failure

=cut

sub run
{
	my $self = shift;

	0;
}

=item buildMonitorixGraphics()

 Build monitorix graphics

 Return int 0 on success, other on failure

=cut

sub buildMonitorixGraphics
{
	my $self = shift;

	my $monitorixGraphColor;

	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery(
		'plugin_name', 'SELECT `plugin_name`, `plugin_config` FROM `plugin` WHERE `plugin_name` = ?', 'Monitorix'
	);

	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	require JSON;
	JSON->import();
	
	my $monitorixConfig = decode_json($rdata->{'Monitorix'}->{'plugin_config'});
	
	if($monitorixConfig->{'graph_color'}) {
		$monitorixGraphColor = $monitorixConfig->{'graph_color'};
	} else {
		$monitorixGraphColor = 'white';
	}
	
	while (my($monitorixConfigKey, $monitorixConfigValue) = each($monitorixConfig->{'graph_enabled'})) {
		if($monitorixConfigValue eq 'y') {
			my $rs = $self->_createMonitorixGraphics(
				$monitorixConfigKey,
				$monitorixGraphColor
			);

			return $rs if $rs;
		}
	}
	
	$self->_setMonitorixGraphicsPermission();
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize plugin

 Return Plugin::Mailgraph

=cut

sub _init
{
	my $self = shift;

	# Force return value from plugin module
	$self->{'FORCE_RETVAL'} = 'yes';

	$self;
}

=item _createMonitorixGraphics()

 Creates the monitorix pictures

 Return int 0 on success, other on failure

=cut

sub _createMonitorixGraphics
{
	my $self = shift;

	my $graph = shift;
	my $graphColor = shift;

	my ($stdout, $stderr);

	my $rs = execute('/usr/share/monitorix/cgi/monitorix.cgi mode=localhost graph=_' . $graph . ' when=day color=' . $graphColor . ' silent=imagetag', \$stdout, \$stderr);
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$rs = execute('/usr/share/monitorix/cgi/monitorix.cgi mode=localhost graph=_' . $graph . ' when=week color=' . $graphColor . ' silent=imagetag', \$stdout, \$stderr);
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$rs = execute('/usr/share/monitorix/cgi/monitorix.cgi mode=localhost graph=_' . $graph . ' when=month color=' . $graphColor . ' silent=imagetag', \$stdout, \$stderr);
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$rs = execute('/usr/share/monitorix/cgi/monitorix.cgi mode=localhost graph=_' . $graph . ' when=year color=' . $graphColor . ' silent=imagetag', \$stdout, \$stderr);
	error($stderr) if $stderr && $rs;

	$rs;
}

=item _setMonitorixGraphicsPermission()

 Set the correct file permission of the monitorix pictures

 Return int 0 on success, other on failure

=cut

sub _setMonitorixGraphicsPermission
{
	my $self = shift;

	my $rs = 0;

	my $panelUname =
	my $panelGName =
		$main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	my $monitorixImgGraphsDir = $main::imscpConfig{'GUI_ROOT_DIR'} . '/plugins/Monitorix/tmp_graph';

	if(-d $monitorixImgGraphsDir) {
		my @monitorixPictureFiles = iMSCP::Dir->new(
			'dirname' => $monitorixImgGraphsDir, 'fileType' => '.png'
		)->getFiles();

		for(@monitorixPictureFiles) {
			my $file = iMSCP::File->new('filename' => "$monitorixImgGraphsDir/$_");
			
			if($_ !~ /^.*\d+[a-y]?[z]\.\d.*\.png/) { # Remove useless files, only zoom graphics are needed
				$rs = $file->delFile();
				return $rs if $rs;
			} else {
				$rs = $file->owner($panelUname, $panelGName);
				return $rs if $rs;

				$rs = $file->mode(0644);
				return $rs if $rs;
			}
		}
	} else {
		error("Unable to open folder: $monitorixImgGraphsDir");
		$rs = 1;
	}

	$rs;
}

=item _modifyMonitorixSystemConfig()

 Modify Monitorix system config file

 Return int 0 on success, other on failure

=cut

sub _modifyMonitorixSystemConfig
{
	my $self = shift;
	my $action = shift;

	my $monitorixSystemConfig = '/etc/monitorix.conf';
	if(! -f $monitorixSystemConfig) {
		error("File $monitorixSystemConfig is missing.");
		return 1;
	}

	my $file = iMSCP::File->new('filename' => $monitorixSystemConfig);

	my $fileContent = $file->get();
	if(! $fileContent) {
		error('Unable to read $monitorixSystemConfig.');
		return 1;
	}

	my $monitorixBaseDirConfig = "# Start_BaseDir Added by Plugins::Monitorix\n";
	$monitorixBaseDirConfig .= "base_dir = /var/www/imscp/gui/plugins/Monitorix/\n";
	$monitorixBaseDirConfig .= "# Added by Plugins::Monitorix End_BaseDir\n";

	my $monitorixImgDirConfig = "# Start_ImgDir Added by Plugins::Monitorix\n";
	$monitorixImgDirConfig .= "imgs_dir = tmp_graph/\n";
	$monitorixImgDirConfig .= "# Added by Plugins::Monitorix End_ImgDir\n";
	
	if($action eq 'add') {
		if ($fileContent =~ /^base_dir = \/usr\/share\/monitorix\//gm) {
			$fileContent =~ s/^base_dir = \/usr\/share\/monitorix\//$monitorixBaseDirConfig/gm;
		}

		if ($fileContent =~ /^# Start_BaseDir Added by Plugins.*End_BaseDir\n/sgm) {
			$fileContent =~ s/^# Start BaseDir added by Plugins.*End_BaseDir\n/$monitorixBaseDirConfig/sgm;
		}

		if ($fileContent =~ /^imgs_dir = imgs\//gm) {
			$fileContent =~ s/^imgs_dir = imgs\//$monitorixImgDirConfig/gm;
		}

		if ($fileContent =~ /^# Start_ImgDir Added by Plugins.*End_ImgDir\n/sgm) {
			$fileContent =~ s/^# Start ImgDir added by Plugins.*End_ImgDir\n/$monitorixImgDirConfig/sgm;
		}
	} elsif($action eq 'remove') {
		$fileContent =~ s/^# Start_BaseDir Added by Plugins.*End_BaseDir\n/base_dir = \/usr\/share\/monitorix\//sgm;

		$fileContent =~ s/^# Start_ImgDir Added by Plugins.*End_ImgDir\n/imgs_dir = imgs\//sgm;
	}

	my $rs = $file->set($fileContent);
	return 1 if $rs;

	$file->save();
}

=item _modifyMonitorixSystemConfigEnabledGraphics()

 Modify Monitorix system config file and enables/disables graphics

 Return int 0 on success, other on failure

=cut

sub _modifyMonitorixSystemConfigEnabledGraphics
{
	my $self = shift;

	my $monitorixSystemConfig = '/etc/monitorix.conf';
	if(! -f $monitorixSystemConfig) {
		error("File $monitorixSystemConfig is missing.");
		return 1;
	}

	my $file = iMSCP::File->new('filename' => $monitorixSystemConfig);

	my $fileContent = $file->get();
	if(! $fileContent) {
		error('Unable to read $monitorixSystemConfig.');
		return 1;
	}

	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery(
		'plugin_name', 'SELECT `plugin_name`, `plugin_config` FROM `plugin` WHERE `plugin_name` = ?', 'Monitorix'
	);

	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	require JSON;
	JSON->import();

	my $monitorixConfig = decode_json($rdata->{'Monitorix'}->{'plugin_config'});

	while (my($monitorixConfigKey, $monitorixConfigValue) = each($monitorixConfig->{'graph_enabled'})) {
		$fileContent =~ s/$monitorixConfigKey(\t\t|\t)= (y|n)/$monitorixConfigKey$1= $monitorixConfigValue/gm;
	}

	my $rs = $file->set($fileContent);
	return 1 if $rs;

	$file->save();
}

=item _modifyMonitorixCgiFile()

 Modify Monitorix CGI file

 Return int 0 on success, other on failure

=cut

sub _modifyMonitorixCgiFile
{
	my $self = shift;
	my $action = shift;

	my $monitorixCgi = '/usr/share/monitorix/cgi/monitorix.cgi';
	if(! -f $monitorixCgi) {
		error("File $monitorixCgi is missing.");
		return 1;
	}

	my $file = iMSCP::File->new('filename' => $monitorixCgi);

	my $fileContent = $file->get();
	if(! $fileContent) {
		error('Unable to read $monitorixCgi.');
		return 1;
	}
	
	my $monitorixCgiConfig = "open(IN, \"< /usr/share/monitorix/cgi/monitorix.conf.path\");";
	
	my $monitorixCgiOldConfig = "open(IN, \"< monitorix.conf.path\");";

	if($action eq 'add') {
		$fileContent =~ s/^open\(IN.*/$monitorixCgiConfig/gm;
	} elsif($action eq 'remove') {
		$fileContent =~ s/^open\(IN.*/$monitorixCgiOldConfig/gm;
	}

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _modifyDefaultMonitorixApacheConfig()

 Add or remove /etc/apache2/conf.d/monitorix.conf file

 Return int 0 on success, other on failure

=cut

sub _modifyDefaultMonitorixApacheConfig
{
	my $self = shift;
	my $action = shift;

	my $rs = 0;

	my $monitorixBaseDirConfigFile = '/etc/apache2/conf.d/monitorix.conf';
	my $monitorixBackupFile = '/etc/apache2/conf.d/monitorix.old';

	if($action eq 'add') {
		$rs = iMSCP::File->new('filename' => $monitorixBackupFile)->moveFile("$monitorixBaseDirConfigFile");
	} elsif($action eq 'remove') {
		$rs = iMSCP::File->new('filename' => $monitorixBaseDirConfigFile)->moveFile("$monitorixBackupFile");
	}

	$rs;
}

=item _restartDaemonMonitorix()

 Restart the Monitorix daemon

 Return int 0 on success, other on failure

=cut

sub _restartDaemonMonitorix
{
	my $self = shift;

	my ($stdout, $stderr);

	my $rs = execute('/usr/sbin/service monitorix restart', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs;
}

=item _restartDaemonApache()

 Restart the apache daemon

 Return int 0 on success, other on failure

=cut

sub _restartDaemonApache
{
	my $self = shift;

	require Servers::httpd;

	my $httpd = Servers::httpd->factory();

	$httpd->{'restart'} = 'yes';

	0;
}

=item _registerCronjob()

 Register mailgraph cronjob

 Return int 0 on success, other on failure

=cut

sub _registerCronjob
{
	my $self = shift;

	require iMSCP::Database;

	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery(
		'plugin_name', 'SELECT `plugin_name`, `plugin_config` FROM `plugin` WHERE `plugin_name` = ?', 'Monitorix'
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	require JSON;
	JSON->import();

	my $cronjobConfig = decode_json($rdata->{'Monitorix'}->{'plugin_config'});

	if($cronjobConfig->{'cronjob_enabled'}) {
		my $cronjobFilePath = $main::imscpConfig{'GUI_ROOT_DIR'} . '/plugins/Monitorix/cronjob/cronjob.pl';

		my $cronjobFile = iMSCP::File->new('filename' => $cronjobFilePath);

		my $cronjobFileContent = $cronjobFile->get();
		if(! $cronjobFileContent) {
			error("Unable to read $cronjobFileContent");
			return 1;
		}

		require iMSCP::Templator;
		iMSCP::Templator->import();

		$cronjobFileContent = process(
			{ 'IMSCP_PERLLIB_PATH' => $main::imscpConfig{'ENGINE_ROOT_DIR'} . '/PerlLib' },
			$cronjobFileContent
		);

		my $rs = $cronjobFile->set($cronjobFileContent);
		return $rs if $rs;

		$rs = $cronjobFile->save();
		return $rs if $rs;

		# TODO Check syntax for config values

		require Servers::cron;
		Servers::cron->factory()->addTask(
			{
				'TASKID' => 'PLUGINS:Monitorix',
				'MINUTE' => $cronjobConfig->{'cronjob_config'}->{'minute'},
				'HOUR' => $cronjobConfig->{'cronjob_config'}->{'hour'},
				'DAY' => $cronjobConfig->{'cronjob_config'}->{'day'},
				'MONTH' => $cronjobConfig->{'cronjob_config'}->{'month'},
				'DWEEK' => $cronjobConfig->{'cronjob_config'}->{'dweek'},
				'COMMAND' => "umask 027; perl $cronjobFilePath >/dev/null 2>&1"
			}
		);
	} else {
		0;
	}
}

=item _unregisterCronjob()

 Unregister mailgraph cronjob

 Return int 0 on success, other on failure

=cut

sub _unregisterCronjob
{
	my $self = shift;

	require Servers::cron;
	Servers::cron->factory()->deleteTask({ 'TASKID' => 'PLUGINS:Monitorix' });
}

=item _checkRequirements

 Check requirements for monitorix plugin

 Return int 0 if all requirements are meet, 1 otherwise

=cut

sub _checkRequirements
{
	my $self = shift;

	my $rs = 0;

	if(-f '/etc/apache2/conf.d/monitorix.conf') {
		$rs = $self->_modifyDefaultMonitorixApacheConfig('remove');
		return $rs if $rs;
		
		$rs = $self->_restartDaemonApache();
		return $rs if $rs;
	}
	
	$rs = $self->_modifyMonitorixSystemConfig('add');
	return $rs if $rs;
	
	$rs = $self->_modifyMonitorixSystemConfigEnabledGraphics();
	return $rs if $rs;
	
	$self->_modifyMonitorixCgiFile('add');
}

=back

=head1 AUTHORS AND CONTRIBUTORS

 Sascha Bay <info@space2place.de>

=cut

1;
