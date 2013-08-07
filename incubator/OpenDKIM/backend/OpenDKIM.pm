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
# @subpackage  OpenDKIM
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Sascha Bay <info@space2place.de>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Plugin::OpenDKIM;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::Database;

use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP OpenDKIM plugin.

=head1 PUBLIC METHODS

=over 4

=item install()

 Perform install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	if(! -x '/usr/sbin/opendkim') {
		error('Unable to find opendkim daemon. Please, install the opendkim package first.');
		return 1;
	}
	
	my $rs = $self->_checkRequirements();
	return 1 if $rs;
	
	$rs = $self->_registerOpenDKIMHook();
	return 1 if $rs;
	
	$rs = $self->_restartDaemonOpenDKIM();
	return 1 if $rs;
	
	$rs = $self->_restartDaemonPostfix();
	return 1 if $rs;
}

=item change()

 Perform change tasks

 Return int 0 on success, other on failure

=cut

sub change
{
	my $self = shift;

	my $rs = $self->_checkRequirements();
	return 1 if $rs;
	
	$rs = $self->_registerOpenDKIMHook();
	return 1 if $rs;
	
	$rs = $self->_restartDaemonOpenDKIM();
	return 1 if $rs;
	
	$rs = $self->_restartDaemonPostfix();
	return 1 if $rs;
}

=item update()

 Perform update tasks

 Return int 0 on success, other on failure

=cut

sub update
{
	my $self = shift;

	my $rs = $self->_checkRequirements();
	return 1 if $rs;
	
	$rs = $self->_unregisterOpenDKIMHook();
	return 1 if $rs;
	
	$rs = $self->_registerOpenDKIMHook();
	return 1 if $rs;
	
	$rs = $self->_restartDaemonOpenDKIM();
	return 1 if $rs;
	
	$rs = $self->_restartDaemonPostfix();
	return 1 if $rs;
}

=item enable()

 Perform enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = shift;

	my $rs = $self->_checkRequirements();
	return 1 if $rs;
	
	$rs = $self->_registerOpenDKIMHook();
	return 1 if $rs;
	
	$rs = $self->_restartDaemonOpenDKIM();
	return 1 if $rs;
	
	$rs = $self->_restartDaemonPostfix();
	return 1 if $rs;
}

=item disable()

 Perform disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
	my $self = shift;

	my $rs = $self->_unregisterOpenDKIMHook();
	return 1 if $rs;
	
	$rs = $self->_modifyPostfixMainConfig(
		'remove'
	);
	return 1 if $rs;
	
	$rs = $self->_restartDaemonPostfix();
	return 1 if $rs;
}

=item uninstall()

 Perform uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;
	
	my $rs = $self->_unregisterOpenDKIMHook();
	return 1 if $rs;
	
	#OpenDKIM will be set to default parameters
	$rs = $self->_modifyOpenDKIMSystemConfig(
		'remove'
	);
	return 1 if $rs;
	
	$rs = $self->_modifyOpenDKIMDefaultConfig(
		'remove'
	);
	return 1 if $rs;
	
	$rs = $self->_restartDaemonOpenDKIM();
	return 1 if $rs;
	
	$rs = iMSCP::Dir->new('dirname' => '/etc/imscp/opendkim')->remove() if -d '/etc/imscp/opendkim';
	return 1 if $rs;
	#OpenDKIM will be set to default parameters
	
	$rs = $self->_modifyPostfixMainConfig(
		'remove'
	);
	return 1 if $rs;
	
	$rs = $self->_restartDaemonPostfix();
	return 1 if $rs;
}

=item run()

 Create new entry for the opendkim

 Return int 0 on success, other on failure

=cut

sub run
{
	my $self = shift;

}

=back

=head1 PRIVATE METHODS

=over 4

=item _modifyOpenDKIMSystemConfig()

 Modify OpenDKIM system config file

 Return int 0 on success, other on failure

=cut

sub _modifyOpenDKIMSystemConfig
{
	my $self = shift;
	my $action = shift;

	my $opendkim_systemconf = '/etc/opendkim.conf';
	return 1 if ! -f $opendkim_systemconf;
	
	my $file = iMSCP::File->new('filename' => $opendkim_systemconf);

	my $fileContent = $file->get();
	return 1 if ! $fileContent;
	
	my $OpenDKIMConfig = "# Start Added by Plugins::OpenDKIM\n";
	$OpenDKIMConfig .= "KeyTable\t\trefile:/etc/imscp/opendkim/KeyTable\n";
	$OpenDKIMConfig .= "SigningTable\t\trefile:/etc/imscp/opendkim/SigningTable\n";
	$OpenDKIMConfig .= "# Added by Plugins::OpenDKIM End\n";
	
	if($action eq 'add') {
		if ($fileContent =~ /^# Start Added by Plugins.*End\n/sgm) {
			$fileContent =~ s/^# Start Added by Plugins.*End\n/$OpenDKIMConfig/sgm;
		} else {
			$fileContent .= "$OpenDKIMConfig";
		}
	}
	elsif($action eq 'remove') {
		$fileContent =~ s/^# Start Added by Plugins.*End\n//sgm;
	}
	
	my $rs = $file->set($fileContent);
	return 1 if $rs;

	$rs = $file->save();
	return 1 if $rs;
}

=item _modifyOpenDKIMDefaultConfig()

 Modify OpenDKIM default config file

 Return int 0 on success, other on failure

=cut

sub _modifyOpenDKIMDefaultConfig
{
	my $self = shift;
	my $action = shift;
	
	my $OpenDKIMSocketConfig;

	my $opendkim_defaultconf = '/etc/default/opendkim';
	return 1 if ! -f $opendkim_defaultconf;

	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery(
		'plugin_name', 'SELECT `plugin_name`, `plugin_config` FROM `plugin` WHERE `plugin_name` = ?', 'OpenDKIM'
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	require JSON;
	JSON->import();
	
	my $opendkimConfig = decode_json($rdata->{'OpenDKIM'}->{'plugin_config'});
	
	if($opendkimConfig->{'opendkim_port'} =~ /\d{4,5}/ && $opendkimConfig->{'opendkim_port'} <= 65535) { #check the port is numeric and has min. 4 and max. 5 digits
		$OpenDKIMSocketConfig = "# Start Added by Plugins::OpenDKIM\n";
		$OpenDKIMSocketConfig .= "SOCKET=\"inet:" .$opendkimConfig->{'opendkim_port'}. "\@localhost\"\n";
		$OpenDKIMSocketConfig .= "# Added by Plugins::OpenDKIM End\n";
	} else {
		$OpenDKIMSocketConfig = "# Start Added by Plugins::OpenDKIM\n";
		$OpenDKIMSocketConfig .= "SOCKET=\"inet:12345\@localhost\"\n";
		$OpenDKIMSocketConfig .= "# Added by Plugins::OpenDKIM End\n";
	}
	
	my $file = iMSCP::File->new('filename' => $opendkim_defaultconf);
	
	my $fileContent = $file->get();
	return 1 if ! $fileContent;
	
	if($action eq 'add') {
		if ($fileContent =~ /^# Start Added by Plugins.*End\n/sgm) {
			$fileContent =~ s/^# Start Added by Plugins.*End\n/$OpenDKIMSocketConfig/sgm;
		} else {
			$fileContent .= "$OpenDKIMSocketConfig";
		}
	}
	elsif($action eq 'remove') {
		$fileContent =~ s/^# Start Added by Plugins.*End\n//sgm;
	}
	
	my $rs = $file->set($fileContent);
	return 1 if $rs;

	$rs = $file->save();
	return 1 if $rs;
}

=item _modifyPostfixMainConfig()

 Modify postfix main.cf config file

 Return int 0 on success, other on failure

=cut

sub _modifyPostfixMainConfig
{
	my $self = shift;
	my $action = shift;
	
	my $PostfixOpenDKIMConfig;
	
	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery(
		'plugin_name', 'SELECT `plugin_name`, `plugin_config` FROM `plugin` WHERE `plugin_name` = ?', 'OpenDKIM'
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	require JSON;
	JSON->import();
	
	my $opendkimConfig = decode_json($rdata->{'OpenDKIM'}->{'plugin_config'});
	
	if($opendkimConfig->{'opendkim_port'} =~ /\d{4,5}/ && $opendkimConfig->{'opendkim_port'} <= 65535) { #check the port is numeric and has min. 4 and max. 5 digits
		$PostfixOpenDKIMConfig = "# Start Added by Plugins::OpenDKIM\n";
		$PostfixOpenDKIMConfig .= "milter_default_action = accept\n";
		$PostfixOpenDKIMConfig .= "milter_protocol = 2\n";
		$PostfixOpenDKIMConfig .= "smtpd_milters = inet:localhost:" .$opendkimConfig->{'opendkim_port'} ."\n";
		$PostfixOpenDKIMConfig .= "non_smtpd_milters = inet:localhost:" .$opendkimConfig->{'opendkim_port'} ."\n";
		$PostfixOpenDKIMConfig .= "# Added by Plugins::OpenDKIM End\n";
	} else {
		$PostfixOpenDKIMConfig = "# Start Added by Plugins::OpenDKIM\n";
		$PostfixOpenDKIMConfig .= "milter_default_action = accept\n";
		$PostfixOpenDKIMConfig .= "milter_protocol = 2\n";
		$PostfixOpenDKIMConfig .= "smtpd_milters = inet:localhost:12345\n";
		$PostfixOpenDKIMConfig .= "non_smtpd_milters = inet:localhost:12345\n";
	}
	
	my $file = iMSCP::File->new('filename' => '/etc/postfix/main.cf');
	
	my $fileContent = $file->get();
	return 1 if ! $fileContent;
	
	if($action eq 'add') {
		if ($fileContent =~ /^# Start Added by Plugins.*End\n/sgm) {
			$fileContent =~ s/^# Start Added by Plugins.*End\n/$PostfixOpenDKIMConfig/sgm;
		} else {
			$fileContent .= "$PostfixOpenDKIMConfig";
		}
	}
	elsif($action eq 'remove') {
		$fileContent =~ s/^# Start Added by Plugins.*End\n//sgm;
	}
	
	my $rs = $file->set($fileContent);
	return 1 if $rs;

	$rs = $file->save();
	return 1 if $rs;
}

=item _createOpenDKIMTableFileDir()

 Creates directory for KeyTable and SigningTable files

 Return int 0 on success, other on failure

=cut

sub _createOpenDKIMTableFileDir
{
	my $self = shift;
	
	my $rs = iMSCP::Dir->new('dirname' => '/etc/imscp/opendkim')->make(
		{ 'user' => $main::imscpConfig{'ROOT_USER'}, 'group' => $main::imscpConfig{'ROOT_GROUP'} , 'mode' => 0750 }
	);
	return 1 if $rs;
	
	$rs = $self->_createOpenDKIMKeyTableFile();
	return 1 if $rs;
	
	$rs = $self->_createOpenDKIMSigningTableFile();
	return 1 if $rs;
}

=item _createOpenDKIMTableFiles()

 Creates KeyTable and SigningTable file

 Return int 0 on success, other on failure

=cut

sub _createOpenDKIMKeyTableFile
{
	my $self = shift;
	
	my $KeyTable = iMSCP::File->new('filename' => '/etc/imscp/opendkim/KeyTable');
	
	my $rs = $KeyTable->save();
	return 1 if $rs;

	$rs = $KeyTable->mode(0644);
	return 1 if $rs;

	$rs = $KeyTable->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return 1 if $rs;
}

=item _createOpenDKIMSigningTableFile()

 Creates SigningTable file

 Return int 0 on success, other on failure

=cut

sub _createOpenDKIMSigningTableFile
{
	my $self = shift;
	
	my $SigningTable = iMSCP::File->new('filename' => '/etc/imscp/opendkim/SigningTable');
	
	my $rs = $SigningTable->save();
	return 1 if $rs;

	$rs = $SigningTable->mode(0644);
	return 1 if $rs;

	$rs = $SigningTable->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return 1 if $rs;
}

=item _registerOpenDKIMHook()

 Register new hook to /etc/imscp/hooks.d

 Return int 0 on success, other on failure

=cut

sub _registerOpenDKIMHook
{
	my $self = shift;

	my $hookOpenDKIM = $main::imscpConfig{'GUI_ROOT_DIR'} . '/plugins/OpenDKIM/hooks/01_postfixOpenDKIM.pl';
	
	my $file = iMSCP::File->new('filename' => $hookOpenDKIM);
	my $rs = $file->copyFile($main::imscpConfig{'CONF_DIR'} . '/hooks.d/01_postfixOpenDKIM.pl');
	return 1 if $rs;
	
	$file = iMSCP::File->new('filename' => $main::imscpConfig{'CONF_DIR'} . '/hooks.d/01_postfixOpenDKIM.pl');
	
	$rs = $file->mode(0640);
	return 1 if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return 1 if $rs;
}

=item _unregisterOpenDKIMHook()

 Unregister OpenDKIM hook from /etc/imscp/hooks.d

 Return int 0 on success, other on failure

=cut

sub _unregisterOpenDKIMHook
{
	my $self = shift;

	my $hookOpenDKIM = $main::imscpConfig{'CONF_DIR'} . '/hooks.d/01_postfixOpenDKIM.pl';
	
	if(-f $hookOpenDKIM) {
		my $rs = iMSCP::File->new('filename' => $hookOpenDKIM)->delFile();
		return 1 if $rs;
	}
}

=item _restartDaemonOpenDKIM()

 Restart the OpenDKIM daemon

 Return int 0 on success, other on failure

=cut

sub _restartDaemonOpenDKIM
{
	my $self = shift;
	
	my ($stdout, $stderr);
	
	my $rs = execute('/etc/init.d/opendkim restart', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	return 1 if $rs;
}

=item _restartDaemonPostfix()

 Restart the postfix daemon

 Return int 0 on success, other on failure

=cut

sub _restartDaemonPostfix
{
	my $self = shift;
	my ($stdout, $stderr);
	
	my $rs = execute('/etc/init.d/postfix restart', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	return 1 if $rs;
}

=item _checkRequirements

 Check requirements for opendkim plugin

 Return int 0 if all requirements are meet, 1 otherwise

=cut

sub _checkRequirements
{
	my $self = shift;	
	my $rs = 0;

	if(! -d '/etc/imscp/opendkim') {
		$rs = $self->_createOpenDKIMTableFileDir();
		return 1 if $rs;
	} else {
		if(! -f '/etc/imscp/opendkim/KeyTable') {
			$rs = $self->_createOpenDKIMKeyTableFile();
			return 1 if $rs;
		}
		if(! -f '/etc/imscp/opendkim/SigningTable') {
			$rs = $self->_createOpenDKIMSigningTableFile();
			return 1 if $rs;
		}
	}
	
	$rs = $self->_modifyOpenDKIMSystemConfig(
		'add'
	);
	return 1 if $rs;
	
	$rs = $self->_modifyOpenDKIMDefaultConfig(
		'add'
	);
	return 1 if $rs;
	
	$rs = $self->_modifyPostfixMainConfig(
		'add'
	);
	return 1 if $rs;
}

=back

=head1 AUTHORS AND CONTRIBUTORS

 Sascha Bay <info@space2place.de>

=cut

1;
