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
	
	if(! -x '/usr/bin/opendkim-genkey') {
		error('Unable to find opendkim-genkey. Please, install the opendkim-tools package first.');
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
	
	my $rs = $self->_registerOpenDKIMHook();
	return 1 if $rs;
	
	$rs = $self->_modifyOpenDKIMSystemConfig('add');
	return 1 if $rs;
	
	$rs = $self->_modifyOpenDKIMDefaultConfig('add');
	return 1 if $rs;
	
	$rs = $self->_restartDaemonOpenDKIM();
	return 1 if $rs;
	
	$rs = $self->_modifyPostfixMainConfig('add');
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
	
	my $rs = $self->_unregisterOpenDKIMHook();
	return 1 if $rs;
	
	$rs = $self->_registerOpenDKIMHook();
	return 1 if $rs;
	
	$rs = $self->_modifyOpenDKIMSystemConfig('add');
	return 1 if $rs;
	
	$rs = $self->_modifyOpenDKIMDefaultConfig('add');
	return 1 if $rs;
	
	$rs = $self->_restartDaemonOpenDKIM();
	return 1 if $rs;
	
	$rs = $self->_modifyPostfixMainConfig('add');
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
	
	my $rs = $self->_registerOpenDKIMHook();
	return 1 if $rs;
	
	$rs = $self->_modifyPostfixMainConfig('add');
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
	
	$rs = $self->_modifyPostfixMainConfig('remove');
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
	
	# OpenDKIM will be set to default parameters
	$rs = $self->_modifyOpenDKIMSystemConfig('remove');
	return 1 if $rs;
	
	$rs = $self->_modifyOpenDKIMDefaultConfig('remove');
	return 1 if $rs;
	
	$rs = $self->_restartDaemonOpenDKIM();
	return 1 if $rs;
	
	$rs = iMSCP::Dir->new('dirname' => '/etc/imscp/opendkim')->remove() if -d '/etc/imscp/opendkim';
	return 1 if $rs;
	# OpenDKIM will be set to default parameters
	
	$rs = $self->_modifyPostfixMainConfig('remove');
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
	
	my $rs = $self->{'hooksManager'}->register('afterMtaAddDmn', \&afterMtaAddDmnOpenDKIM);
	return 1 if $rs;
	
	my $rs = $self->{'hooksManager'}->register('afterMtaDelDmn', \&afterMtaDelDmnOpenDKIM);
	return 1 if $rs;
	
	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item afterMtaAddDmnOpenDKIM
 
Adds the new domain for OpenDKIM support
 
Return int 0
 
=cut
 
sub afterMtaAddDmnOpenDKIM
{
	my $domainData = shift;
	
	my $rs = 0;
	
	my $rdata;
	
	my $domain = $domainData->{DOMAIN_NAME};
	our $domainId;
	our $aliasId;
	
	my ($stdout, $stderr);
	
	my $db = iMSCP::Database->factory();

	$rdata = $db->doQuery(
		'domain_id', 'SELECT `domain_id` FROM `domain` WHERE `domain_name` = ?', $domain
	);
		
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
		
	foreach (keys %$rdata) { $domainId = $rdata->{$_}->{domain_id}; }
		
	
	if(! $domainId) {
		$rdata = $db->doQuery(
			'domain_id', 'SELECT `alias_id`, `domain_id` FROM `domain_aliasses` WHERE `alias_name` = ?', $domain
		);
		
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}
		
		foreach (keys %$rdata) {
			$domainId = $rdata->{$_}->{domain_id};
			$aliasId = $rdata->{$_}->{alias_id};
		}
	}
	
	if($domainId) { # As long domain dns not working with subdomain or subdomainalias, domain keys will only created for domains or aliases
		if(! -d '/etc/imscp/opendkim/' . $domain) {
			$rs = iMSCP::Dir->new('dirname' => '/etc/imscp/opendkim/' . $domain)->make(
				{ 'user' => 'opendkim', 'group' => 'opendkim', 'mode' => 0750 }
			);
			return 1 if $rs;
			
			
			$rs = execute('opendkim-genkey -D /etc/imscp/opendkim/' . $domain . ' -r -h rsa-sha256 -s mail -d ' . $domain, \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			
			my $file = iMSCP::File->new('filename' => '/etc/imscp/opendkim/' . $domain . '/mail.private');
			
			$rs = $file->owner('opendkim', 'opendkim');
			return 1 if $rs;
			
			$file = iMSCP::File->new('filename' => '/etc/imscp/opendkim/' . $domain . '/mail.txt');
			
			my $fileContent = $file->get();
			return 1 if ! $fileContent;
			
			(my $txtRecord) = ($fileContent =~ /(\".*\")/);
			
			# Why should i delete this file? I think there is no security risk
			#$rs = $file->delFile();
			#return 1 if $rs;
			
			$rs = $file->owner('opendkim', 'opendkim');
			return 1 if $rs;
			
			# Now add the private key to the KeyTable file
			$file = iMSCP::File->new('filename' => '/etc/imscp/opendkim/KeyTable');
			
			$fileContent = $file->get();
			#return 1 if ! $fileContent; #Must be deactivated because an empty file rise up an error
			
			my $privateKeyEntry = "mail._domainkey." . $domain . " " . $domain . ":default:/etc/imscp/opendkim/" . $domain . "/mail.private\n";
			
			$fileContent .= $privateKeyEntry;
			
			$rs = $file->set($fileContent);
			return 1 if $rs;

			$rs = $file->save();
			return 1 if $rs;
			
			# Now add the domain to the SigningTable file
			$file = iMSCP::File->new('filename' => '/etc/imscp/opendkim/SigningTable');
			
			$fileContent = $file->get();
			#return 1 if ! $fileContent; #Must be deactivated because an empty file rise up an error
			
			my $domainEntry = "*@" . $domain . " mail._domainkey." . $domain . "\n";
			
			$fileContent .= $domainEntry;
			
			$rs = $file->set($fileContent);
			return 1 if $rs;

			$rs = $file->save();
			return 1 if $rs;
			
			# Save TXT to database
			$rdata = $db->doQuery(
				'domain_id', 
				"
					INSERT INTO `domain_dns` (
						`domain_id`,
						`alias_id`,
						`domain_dns`,
						`domain_class`,
						`domain_type`,
						`domain_text`,
						`protected`
					) VALUES (
						?
						, ?
						, ?
						, 'IN'
						, 'TXT'
						, ?
						, 'yes'
					)
				"
				, $domainId, ($aliasId ? $aliasId : 0), 'mail._domainkey.' . $domain, $txtRecord
			);
			
			unless(ref $rdata eq 'HASH') {
				error($rdata);
				return 1;
			}
			
			# OpenDKIM daemon must be restartet
			my $pluginClass = "Plugin::OpenDKIM";
			$rs = $pluginClass->_restartDaemonOpenDKIM();
			return 1 if $rs;
			
			if(! $aliasId) {
				$rdata = $db->doQuery(
					'dummy',
					"
						UPDATE
							`domain`
						SET
							`domain_status` = 'tochange',
							`domain_dns` = 'yes'
						WHERE
							`domain_id` = ?
					"
					, $domainId
				);
			} else {
				$rdata = $db->doQuery(
					'dummy',
					"
						UPDATE
							`domain_aliasses`
						SET
							`alias_status` = 'tochange'
						WHERE
							`alias_id` = ?
					"
					, $aliasId
				);
			}
			
			unless(ref $rdata eq 'HASH') {
				error($rdata);
				return 1;
			}
			
			if(! $aliasId) {
				$pluginClass = "Modules::Domain";
				$rs = $pluginClass->new()->process($domainId);
			} else {
				$pluginClass = "Modules::Alias";
				$rs = $pluginClass->new()->process($aliasId);
			}
			return 1 if $rs;
		}
	}

	0;
}

=item afterMtaDelDmnOpenDKIM
 
Adds the new domain for OpenDKIM support
 
Return int 0
 
=cut
 
sub afterMtaDelDmnOpenDKIM
{
	my $domainData = shift;
	
	my $rs = 0;
	
	my $domain = $domainData->{DOMAIN_NAME};
	
	my $rs = iMSCP::Dir->new('dirname' => '/etc/imscp/opendkim/' . $domain)->remove() if -d '/etc/imscp/opendkim/' . $domain;
	return 1 if $rs;
	
	# Remove domain from KeyTable file
	my $file = iMSCP::File->new('filename' => '/etc/imscp/opendkim/KeyTable');
	
	my $fileContent = $file->get();
	return 1 if ! $fileContent;
	
	$fileContent =~ s/.*$domain.*\n//g;
	
	$rs = $file->set($fileContent);
	return 1 if $rs;

	$rs = $file->save();
	return 1 if $rs;
	
	# Remove domain from SigningTable file
	$file = iMSCP::File->new('filename' => '/etc/imscp/opendkim/SigningTable');
	
	$fileContent = $file->get();
	return 1 if ! $fileContent;
	
	$fileContent =~ s/.*$domain.*\n//g;
	
	$rs = $file->set($fileContent);
	return 1 if $rs;

	$rs = $file->save();
	return 1 if $rs;
	
	# OpenDKIM daemon must be restartet
	my $pluginClass = "Plugin::OpenDKIM";
	$rs = $pluginClass->_restartDaemonOpenDKIM();
	return 1 if $rs;
	
	0;
}

=item _modifyOpenDKIMSystemConfig()

 Modify OpenDKIM system config file

 Return int 0 on success, other on failure

=cut

sub _modifyOpenDKIMSystemConfig
{
	my $self = shift;
	my $action = shift;

	my $opendkimSystemconf = '/etc/opendkim.conf';
	return 1 if ! -f $opendkimSystemconf;
	
	my $file = iMSCP::File->new('filename' => $opendkimSystemconf);

	my $fileContent = $file->get();
	return 1 if ! $fileContent;
	
	my $opendkimConfig = "# Start Added by Plugins::OpenDKIM\n";
	$opendkimConfig .= "KeyTable\t\trefile:/etc/imscp/opendkim/KeyTable\n";
	$opendkimConfig .= "SigningTable\t\trefile:/etc/imscp/opendkim/SigningTable\n";
	$opendkimConfig .= "ExternalIgnoreList\t/etc/imscp/opendkim/TrustedHosts\n";
	$opendkimConfig .= "InternalHosts\t\t/etc/imscp/opendkim/TrustedHosts\n";
	$opendkimConfig .= "# Added by Plugins::OpenDKIM End\n";
	
	if($action eq 'add') {
		if ($fileContent =~ /^# Start Added by Plugins.*End\n/sgm) {
			$fileContent =~ s/^# Start Added by Plugins.*End\n/$opendkimConfig/sgm;
		} else {
			$fileContent .= "$opendkimConfig";
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
	
	my $opendkimSocketConfig;

	my $opendkimDefaultConfig = '/etc/default/opendkim';
	return 1 if ! -f $opendkimDefaultConfig;

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
		$opendkimSocketConfig = "# Start Added by Plugins::OpenDKIM\n";
		$opendkimSocketConfig .= "SOCKET=\"inet:" .$opendkimConfig->{'opendkim_port'}. "\@localhost\"\n";
		$opendkimSocketConfig .= "# Added by Plugins::OpenDKIM End\n";
	} else {
		$opendkimSocketConfig = "# Start Added by Plugins::OpenDKIM\n";
		$opendkimSocketConfig .= "SOCKET=\"inet:12345\@localhost\"\n";
		$opendkimSocketConfig .= "# Added by Plugins::OpenDKIM End\n";
	}
	
	my $file = iMSCP::File->new('filename' => $opendkimDefaultConfig);
	
	my $fileContent = $file->get();
	return 1 if ! $fileContent;
	
	if($action eq 'add') {
		if ($fileContent =~ /^# Start Added by Plugins.*End\n/sgm) {
			$fileContent =~ s/^# Start Added by Plugins.*End\n/$opendkimSocketConfig/sgm;
		} else {
			$fileContent .= "$opendkimSocketConfig";
		}
	} elsif($action eq 'remove') {
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
	
	my $postfixopendkimConfig;
	
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
		$postfixopendkimConfig = "# Start Added by Plugins::OpenDKIM\n";
		$postfixopendkimConfig .= "milter_default_action = accept\n";
		$postfixopendkimConfig .= "milter_protocol = 2\n";
		$postfixopendkimConfig .= "smtpd_milters = inet:localhost:" .$opendkimConfig->{'opendkim_port'} ."\n";
		$postfixopendkimConfig .= "non_smtpd_milters = inet:localhost:" .$opendkimConfig->{'opendkim_port'} ."\n";
		$postfixopendkimConfig .= "# Added by Plugins::OpenDKIM End\n";
	} else {
		$postfixopendkimConfig = "# Start Added by Plugins::OpenDKIM\n";
		$postfixopendkimConfig .= "milter_default_action = accept\n";
		$postfixopendkimConfig .= "milter_protocol = 2\n";
		$postfixopendkimConfig .= "smtpd_milters = inet:localhost:12345\n";
		$postfixopendkimConfig .= "non_smtpd_milters = inet:localhost:12345\n";
	}
	
	my $file = iMSCP::File->new('filename' => '/etc/postfix/main.cf');
	
	my $fileContent = $file->get();
	return 1 if ! $fileContent;
	
	if($action eq 'add') {
		if ($fileContent =~ /^# Start Added by Plugins.*End\n/sgm) {
			$fileContent =~ s/^# Start Added by Plugins.*End\n/$postfixopendkimConfig/sgm;
		} else {
			$fileContent .= "$postfixopendkimConfig";
		}
	} elsif($action eq 'remove') {
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
		{ 'user' => 'opendkim', 'group' => 'opendkim', 'mode' => 0750 }
	);
	return 1 if $rs;
	
	$rs = $self->_createOpenDKIMKeyTableFile();
	return 1 if $rs;
	
	$rs = $self->_createOpenDKIMSigningTableFile();
	return 1 if $rs;
	
	$rs = $self->_createOpenDKIMTrustedHostsFile();
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

	$rs = $KeyTable->mode(0640);
	return 1 if $rs;

	$rs = $KeyTable->owner('opendkim', 'opendkim');
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

	$rs = $SigningTable->mode(0640);
	return 1 if $rs;

	$rs = $SigningTable->owner('opendkim', 'opendkim');
	return 1 if $rs;
}

=item _createOpenDKIMTrustedHostsFile()

 Creates TrustedHosts file

 Return int 0 on success, other on failure

=cut

sub _createOpenDKIMTrustedHostsFile
{
	my $self = shift;
	
	my $TrustedHostsFile = iMSCP::File->new('filename' => '/etc/imscp/opendkim/TrustedHosts');
	
	my $fileContent = "127.0.0.1\n";
	$fileContent .= "localhost\n";
	
	my $rs = $TrustedHostsFile->set($fileContent);
	return 1 if $rs;
	
	$rs = $TrustedHostsFile->save();
	return 1 if $rs;

	$rs = $TrustedHostsFile->mode(0640);
	return 1 if $rs;

	$rs = $TrustedHostsFile->owner('opendkim', 'opendkim');
	return 1 if $rs;
}

=item _registerOpenDKIMHook()

 Register new hook to /etc/imscp/hooks.d

 Return int 0 on success, other on failure

=cut

sub _registerOpenDKIMHook
{
	my $self = shift;

	my $hookOpendkim = $main::imscpConfig{'GUI_ROOT_DIR'} . '/plugins/OpenDKIM/hooks/01_hookOpenDKIM.pl';
	
	my $file = iMSCP::File->new('filename' => $hookOpendkim);
	my $rs = $file->copyFile($main::imscpConfig{'CONF_DIR'} . '/hooks.d/01_hookOpenDKIM.pl');
	return 1 if $rs;
	
	$file = iMSCP::File->new('filename' => $main::imscpConfig{'CONF_DIR'} . '/hooks.d/01_hookOpenDKIM.pl');
	
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

	my $hookOpendkim = $main::imscpConfig{'CONF_DIR'} . '/hooks.d/01_hookOpenDKIM.pl';
	
	if(-f $hookOpendkim) {
		my $rs = iMSCP::File->new('filename' => $hookOpendkim)->delFile();
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
	
	my $rs = execute('service opendkim restart', \$stdout, \$stderr);
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
	
	require Servers::mta;
	
	my $mta = Servers::mta->factory();

	$mta->{'restart'} = 'yes';
	
	0;
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
		if(! -f '/etc/imscp/opendkim/SigningTable') {
			$rs = $self->_createOpenDKIMTrustedHostsFile();
			return 1 if $rs;
		}
	}
	
	$rs = $self->_modifyOpenDKIMSystemConfig('add');
	return 1 if $rs;
	
	$rs = $self->_modifyOpenDKIMDefaultConfig('add');
	return 1 if $rs;
	
	$rs = $self->_modifyPostfixMainConfig('add');
	return 1 if $rs;
}

=back

=head1 AUTHORS AND CONTRIBUTORS

 Sascha Bay <info@space2place.de>

=cut

1;
