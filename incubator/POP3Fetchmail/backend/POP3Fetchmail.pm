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
# @subpackage  POP3Fetchmail
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Sascha Bay <info@space2place.de>
# @contributor Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Plugin::POP3Fetchmail;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Execute;
use iMSCP::Dir;
use iMSCP::File;

use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP POP3Fetchmail plugin.

=head1 PUBLIC METHODS

=over 4

=item install()

 Perform install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $rs = $self->_installPop3fetcherRoundcubePlugin();
	return $rs if $rs;
	
	$rs = $self->_setPop3FetcherCronjobDatabaseImscp();
	return $rs if $rs;
	
	$rs = $self->_registerPop3fetcherRoundcubePlugin();
	return $rs if $rs;
	
	$self->_registerCronjob();
}

=item update()

 Perform update tasks

 Return int 0 on success, other on failure

=cut

sub update
{
	my $self = shift;

	my $rs = $self->_installPop3fetcherRoundcubePlugin();
	return $rs if $rs;

	my $rs = $self->_setPop3FetcherCronjobDatabaseImscp();
	return $rs if $rs;

	$rs = $self->_registerPop3fetcherRoundcubePlugin();
	return $rs if $rs;

	$rs = $self->_unregisterCronjob();
	return $rs if $rs;

	$self->_registerCronjob();
}

=item change()

 Perform change tasks

 Return int 0 on success, other on failure

=cut

sub change
{
	my $self = shift;

	my $rs = $self->_installPop3fetcherRoundcubePlugin();
	return $rs if $rs;

	$rs = $self->_setPop3FetcherCronjobDatabaseImscp();
	return $rs if $rs;

	$rs = $self->_registerPop3fetcherRoundcubePlugin();
	return $rs if $rs;

	$self->_registerCronjob();
}

=item enable()

 Perform enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = shift;

	my $rs = $self->_registerPop3fetcherRoundcubePlugin();
	return $rs if $rs;

	$self->_registerCronjob();
}

=item disable()

 Perform disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
	my $self = shift;

	my $rs = $self->_unregisterPop3fetcherRoundcubePlugin();
	return $rs if $rs;

	$self->_unregisterCronjob();
}

=item uninstall()

 Perform uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	my $pop3FetcherFolder = "$main::imscpConfig{'GUI_ROOT_DIR'}/public/tools" .
		$main::imscpConfig{'WEBMAIL_PATH'} . 'plugins/pop3fetcher';

	my $rs = $self->_unregisterCronjob();
	return $rs if $rs;

	$rs = iMSCP::Dir->new('dirname' => $pop3FetcherFolder)->remove() if -d $pop3FetcherFolder;
	return $rs if $rs;

	$self->_unregisterPop3fetcherRoundcubePlugin();
}

=item fetchmails()

 Fetch emails from external email accounts

 Return int 0 on success, other on failure

=cut

sub fetchmails
{
	my $pop3FetcherCronFolder = "$main::imscpConfig{'GUI_ROOT_DIR'}/public/tools" .
		$main::imscpConfig{'WEBMAIL_PATH'} . 'plugins/pop3fetcher/cron';

	my ($stdout, $stderr);
	my $rs = execute("$main::imscpConfig{'CMD_PHP'} $pop3FetcherCronFolder/fetchmail.php", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _copyPop3fetcherRoundcubePlugin()

 Copy the pop3fetcher roundcube plugin to the roundcube plugin folder

 Return int 0 on success, other on failure

=cut

sub _installPop3fetcherRoundcubePlugin
{
	my $rs = 0;

	my $panelUName =
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	my $pop3FetcherFolder = "$main::imscpConfig{'GUI_ROOT_DIR'}/public/tools" .
		$main::imscpConfig{'WEBMAIL_PATH'} . 'plugins/pop3fetcher';

	if(! -d $pop3FetcherFolder) {
		$rs = iMSCP::Dir->new('dirname' => $pop3FetcherFolder)->make(
			{ 'user' => $panelUName, 'group' => $panelGName, 'mode' => 0550 }
		);
		return $rs if $rs;
	}

	my ($stdout, $stderr);
	$rs = execute(
		"$main::imscpConfig{'CMD_CP'} -fR $main::imscpConfig{'GUI_ROOT_DIR'}/plugins/POP3Fetchmail/roundcube-plugin/* $pop3FetcherFolder",
		\$stdout,
		\$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Set the correct permissions
	require iMSCP::Rights;
	iMSCP::Rights->import();

	setRights(
		$pop3FetcherFolder,
		{ 'user' => $panelUName, 'group' => $panelGName, 'dirmode' => '0550', 'filemode' => '0440', 'recursive' => 1 }
	);
}

=item _setPop3FetcherCronjobDatabaseImscp()

 Set the correct i-MSCP database in pop3fetcher/cron/fetchmails.php

 Return int 0 on success, other on failure

=cut

sub _setPop3FetcherCronjobDatabaseImscp
{
	# Modify the roundcube pop3fetcher/cron/fetchmail.php
	my $pop3fetcherFetchmailFile = "$main::imscpConfig{'GUI_ROOT_DIR'}/public/tools" .
		$main::imscpConfig{'WEBMAIL_PATH'} . 'plugins/pop3fetcher/cron/fetchmail.php';

	my $file = iMSCP::File->new('filename' => $pop3fetcherFetchmailFile);

	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read $pop3fetcherFetchmailFile");
		return 1;
	}

	if ($fileContent =~ /###IMSCP-DATABASE###/sgm) {
		$fileContent =~ s/###IMSCP-DATABASE###/$main::imscpConfig{'DATABASE_NAME'}/g;
	}

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _registerPop3fetcherRoundcubePlugin()

 Register the pop3fetcher roundcube plugin

 Return int 0 on success, other on failure

=cut

sub _registerPop3fetcherRoundcubePlugin
{
	# Modify the roundcube main.inc.php
	my $roundcubeMainIncFile = "$main::imscpConfig{'GUI_ROOT_DIR'}/public/tools" .
		$main::imscpConfig{'WEBMAIL_PATH'} . 'config/main.inc.php';

	my $file = iMSCP::File->new('filename' => $roundcubeMainIncFile);

	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read $roundcubeMainIncFile");
		return 1;
	}

	if ($fileContent =~ /imscp_pw_changer/sgm) {
		$fileContent =~ s/imscp_pw_changer/imscp_pw_changer', 'pop3fetcher/g;
	}

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _unregisterPop3fetcherRoundcubePlugin()

 Unregister the pop3fetcher roundcube plugin

 Return int 0 on success, other on failure

=cut

sub _unregisterPop3fetcherRoundcubePlugin
{
	# Modify the roundcube main.inc.php
	my $roundcubeMainIncFile = "$main::imscpConfig{'GUI_ROOT_DIR'}/public/tools" .
		$main::imscpConfig{'WEBMAIL_PATH'} . 'config/main.inc.php';

	my $file = iMSCP::File->new('filename' => $roundcubeMainIncFile);

	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read $roundcubeMainIncFile");
		return 1;
    }

	if ($fileContent =~ /pop3fetcher/sgm) {
		$fileContent =~ s/,\s+\'pop3fetcher\'//g;
	}

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _registerCronjob()

 Register pop3fetchmail cronjob

 Return int 0 on success, other on failure

=cut

sub _registerCronjob
{
	require iMSCP::Database;

	my $rdata = iMSCP::Database->factory()->doQuery(
		'plugin_name', 'SELECT `plugin_name`, `plugin_config` FROM `plugin` WHERE `plugin_name` = ?', 'POP3Fetchmail'
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	require JSON;
	JSON->import();

	my $cronjobConfig = decode_json($rdata->{'POP3Fetchmail'}->{'plugin_config'});

	if($cronjobConfig->{'cronjob_enabled'}) {
		my $cronjobFilePath = $main::imscpConfig{'GUI_ROOT_DIR'} . '/plugins/POP3Fetchmail/cronjob/cronjob.pl';

		my $cronjobFile = iMSCP::File->new('filename' => $cronjobFilePath);

		my $cronjobFileContent = $cronjobFile->get();
		unless (defined $cronjobFileContent) {
			error("Unable to read $cronjobFilePath");
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

		require Servers::cron;
		Servers::cron->factory()->addTask(
			{
				'TASKID' => 'PLUGINS:POP3Fetchmail',
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

 Unregister POP3Fetchmail cronjob

 Return int 0 on success, other on failure

=cut

sub _unregisterCronjob
{
	require Servers::cron;
	Servers::cron->factory()->deleteTask({ 'TASKID' => 'PLUGINS:POP3Fetchmail' });
}

=back

=head1 AUTHORS AND CONTRIBUTORS

 Sascha Bay <info@space2place.de>

=cut

1;
