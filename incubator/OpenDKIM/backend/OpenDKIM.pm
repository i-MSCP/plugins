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
use parent 'Modules::Domain';

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
	
	if(! -x '/usr/sbin/opendkim-genkey' && ! -x '/usr/bin/opendkim-genkey') {
		error('Unable to find opendkim-genkey. Please, install the opendkim (Wheezy: opendkim-tools) package first.');
		return 1;
	}
	
	my $rs = $self->_checkRequirements();
	return $rs if $rs;
	
	$rs = $self->_restartDaemonOpendkim();
	return $rs if $rs;
	
	$self->_restartDaemonPostfix();
}

=item change()

 Perform change tasks

 Return int 0 on success, other on failure

=cut

sub change
{
	my $self = shift;
	
	my $rs = $self->_modifyOpendkimSystemConfig('add');
	return $rs if $rs;
	
	$rs = $self->_modifyOpendkimDefaultConfig('add');
	return $rs if $rs;
	
	$rs = $self->_restartDaemonOpendkim();
	return $rs if $rs;
	
	$rs = $self->_modifyPostfixMainConfig('add');
	return $rs if $rs;
	
	$self->_restartDaemonPostfix();
}

=item update()

 Perform update tasks

 Return int 0 on success, other on failure

=cut

sub update
{
	my $self = shift;
	
	my $rs = $self->_modifyOpendkimSystemConfig('add');
	return $rs if $rs;
	
	$rs = $self->_modifyOpendkimDefaultConfig('add');
	return $rs if $rs;
	
	$rs = $self->_createOpendkimTrustedHostsFile();
	return $rs if $rs;
	
	$rs = $self->_restartDaemonOpendkim();
	return $rs if $rs;
	
	$rs = $self->_modifyPostfixMainConfig('remove');
	return $rs if $rs;
	
	$rs = $self->_modifyPostfixMainConfig('add');
	return $rs if $rs;
	
	$self->_restartDaemonPostfix();
}

=item enable()

 Perform enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = shift;
	
	my $rs = $self->_modifyPostfixMainConfig('add');
	return $rs if $rs;
	
	$rs = $self->_restartDaemonPostfix();
	return $rs if $rs;
	
	$self->_recoverOpendkimDnsEntries();
}

=item disable()

 Perform disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
	my $self = shift;
	
	my $rs = $self->_modifyPostfixMainConfig('remove');
	return $rs if $rs;
	
	$self->_restartDaemonPostfix();
}

=item uninstall()

 Perform uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;
	
	# OpenDKIM will be set to default parameters
	my $rs = $self->_modifyOpendkimSystemConfig('remove');
	return $rs if $rs;
	
	$rs = $self->_modifyOpendkimDefaultConfig('remove');
	return $rs if $rs;
	
	$rs = $self->_restartDaemonOpendkim();
	return $rs if $rs;
	
	$rs = iMSCP::Dir->new('dirname' => '/etc/opendkim/')->remove() if -d '/etc/opendkim/';
	return $rs if $rs;
	# OpenDKIM will be set to default parameters
	
	$self->_restartDaemonPostfix();
}

=item run()

 Create new entry for the opendkim

 Return int 0 on success, other on failure

=cut

sub run
{
	my $self = shift;
	
	my $rs = 0;
	
	my $db = iMSCP::Database->factory();
	
	my $rdata = $db->doQuery(
		'opendkim_id', 
		"
			SELECT
				`opendkim_id`, `domain_id`,
				`alias_id`, `domain_name`,
				`customer_dns_previous_status`, `opendkim_status`
			FROM
				`opendkim`
			WHERE
				`opendkim_status` IN('toadd', 'todelete')
			ORDER BY
				`domain_id` ASC
		"
	);
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	my @sql;
	
	if(%{$rdata}) {
		for(keys %{$rdata}) {
			if($rdata->{$_}->{'opendkim_status'} eq 'toadd') {
				$rs = $self->_addOpendkimDomainKey(
					$rdata->{$_}->{'domain_id'}, $rdata->{$_}->{'alias_id'},
					$rdata->{$_}->{'domain_name'}
				);

				@sql = (
					'UPDATE `opendkim` SET `opendkim_status` = ? WHERE `opendkim_id` = ?',
					($rs ? scalar getMessageByType('error') : 'ok'), $rdata->{$_}->{'opendkim_id'}
				);
			} elsif($rdata->{$_}->{'opendkim_status'} eq 'todelete') {
				$rs = $self->_deleteOpendkimDomainKey(
					$rdata->{$_}->{'domain_id'}, $rdata->{$_}->{'alias_id'},
					$rdata->{$_}->{'domain_name'}, ($rdata->{$_}->{'customer_dns_previous_status'}) ? $rdata->{$_}->{'customer_dns_previous_status'} : 'na'
				);
				if($rs) {
					@sql = (
						'UPDATE `opendkim` SET `opendkim_status` = ? WHERE `opendkim_id` = ?',
						scalar getMessageByType('error'), $rdata->{$_}->{'opendkim_id'}
					);
				} else {
					@sql = ('DELETE FROM `opendkim` WHERE `opendkim_id` = ?', $rdata->{$_}->{'opendkim_id'});
				}
			}

			my $rdata2 = $db->doQuery('dummy', @sql);
			
			unless(ref $rdata2 eq 'HASH') {
				error($rdata2);
				return 1;
			}
		}
		
		# OpenDKIM daemon must be restartet
		$rs = $self->_restartDaemonOpendkim();
		return $rs if $rs;
	}
	
	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _addOpendkimDomainKey
 
Adds the new domain key for OpenDKIM support
 
Return int 0
 
=cut
 
sub _addOpendkimDomainKey
{
	my $self = shift;
	
	my $domainId = shift;
	my $aliasId = shift;
	my $domain = shift;
	
	my $rs = 0;
	
	my $rdata;
	
	my $pluginClass;
	
	my ($stdout, $stderr);
	
	my $db = iMSCP::Database->factory();
	
	if(! -d '/etc/opendkim/' . $domain) {
		$rs = iMSCP::Dir->new('dirname' => '/etc/opendkim/' . $domain)->make(
			{ 'user' => 'opendkim', 'group' => 'opendkim', 'mode' => 0750 }
		);
		return $rs if $rs;
		
		
		$rs = execute('opendkim-genkey -D /etc/opendkim/' . $domain . ' -r -s mail -d ' . $domain, \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		
		my $file = iMSCP::File->new('filename' => '/etc/opendkim/' . $domain . '/mail.private');
		
		$rs = $file->owner('opendkim', 'opendkim');
		return $rs if $rs;
		
		$file = iMSCP::File->new('filename' => '/etc/opendkim/' . $domain . '/mail.txt');
		
		my $fileContent = $file->get();
		return $fileContent if ! $fileContent;
		
		(my $txtRecord) = ($fileContent =~ /(\".*\")/);
		
		# Why should i delete this file? I think there is no security risk, and we need this file when the plugin will be re enabled
		#$rs = $file->delFile();
		#return $rs if $rs;
		
		$rs = $file->owner('opendkim', 'opendkim');
		return $rs if $rs;
		
		# Now add the private key to the KeyTable file
		$file = iMSCP::File->new('filename' => '/etc/opendkim/KeyTable');
		
		$fileContent = $file->get();
		#return $fileContent if ! $fileContent; #Must be deactivated because an empty file rise up an error
		
		my $privateKeyEntry = "mail._domainkey." . $domain . " " . $domain . ":mail:/etc/opendkim/" . $domain . "/mail.private\n";
		
		$fileContent .= $privateKeyEntry;
		
		$rs = $file->set($fileContent);
		return $rs if $rs;
		
		$rs = $file->save();
		return $rs if $rs;
		
		# Now add the domain to the SigningTable file
		$file = iMSCP::File->new('filename' => '/etc/opendkim/SigningTable');
		
		$fileContent = $file->get();
		#return $fileContent if ! $fileContent; #Must be deactivated because an empty file rise up an error
		
		my $domainEntry = "*@" . $domain . " mail._domainkey." . $domain . "\n";
		
		$fileContent .= $domainEntry;
		
		$rs = $file->set($fileContent);
		return $rs if $rs;
		
		$rs = $file->save();
		return $rs if $rs;
		
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
					`owned_by`
				) VALUES (
					?
					, ?
					, ?
					, 'IN'
					, 'TXT'
					, ?
					, 'opendkim_feature'
				)
			"
			, $domainId, $aliasId, 'mail._domainkey', $txtRecord
		);
		
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}
		
		if($aliasId eq '0') {
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
		
		if($aliasId eq '0') {	
			$pluginClass = "Modules::Domain";
			$rs = $pluginClass->new()->process($domainId);
		} else {
			require Modules::Alias;
			$pluginClass = "Modules::Alias";
			$rs = $pluginClass->new()->process($aliasId);
		}
		return $rs if $rs;
	}

	0;
}

=item _recoverOpendkimDnsEntries
 
Recover dns entries after disabling the OpenDKIM plugin
 
Return int 0
 
=cut
 
sub _recoverOpendkimDnsEntries
{
	my $self = shift;
	
	my $rs = 0;
	
	my $rdata;
	my $rdata2;
	
	my $pluginClass;
	
	my $db = iMSCP::Database->factory();
	
	$rdata = $db->doQuery(
		'opendkim_id', 
		"
			SELECT
				`opendkim_id`, `domain_id`,
				`alias_id`, `domain_name`
			FROM
				`opendkim`
			WHERE
				`opendkim_status` = 'ok'
			ORDER BY
				`domain_id` ASC, `alias_id` ASC
		"
	);
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	if(%{$rdata}) {
		for(keys %{$rdata}) {
		
			my $domain = $rdata->{$_}->{'domain_name'};
			my $domainId = $rdata->{$_}->{'domain_id'};
			my $aliasId = $rdata->{$_}->{'alias_id'};
			
			if(-d '/etc/opendkim/' . $domain) {
			
				my $file = iMSCP::File->new('filename' => '/etc/opendkim/' . $domain . '/mail.txt');
				
				my $fileContent = $file->get();
				return $fileContent if ! $fileContent;
				
				(my $txtRecord) = ($fileContent =~ /(\".*\")/);
				
				# Save TXT to database
				$rdata2 = $db->doQuery(
					'domain_id', 
					"
						INSERT INTO `domain_dns` (
							`domain_id`,
							`alias_id`,
							`domain_dns`,
							`domain_class`,
							`domain_type`,
							`domain_text`,
							`owned_by`
						) VALUES (
							?
							, ?
							, ?
							, 'IN'
							, 'TXT'
							, ?
							, 'opendkim_feature'
						)
					"
					, $domainId, $aliasId, 'mail._domainkey', $txtRecord
				);
				
				unless(ref $rdata2 eq 'HASH') {
					error($rdata2);
					return 1;
				}
				
				if($aliasId eq '0') {
					$rdata2 = $db->doQuery(
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
					$rdata2 = $db->doQuery(
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
				
				unless(ref $rdata2 eq 'HASH') {
					error($rdata2);
					return 1;
				}
				
				if($aliasId eq '0') {	
					$pluginClass = "Modules::Domain";
					$rs = $pluginClass->new()->process($domainId);
				} else {
					require Modules::Alias;
					$pluginClass = "Modules::Alias";
					$rs = $pluginClass->new()->process($aliasId);
				}
				return $rs if $rs;
			} else {
				error('The OpenDKIM folder for the domain ' . $rdata->{$_}->{'domain_name'} . ' does not exist!');
			}
		}
	}

	0;
}

=item _deleteOpendkimDomainKey
 
Deletes domain key from OpenDKIM support
 
Return int 0
 
=cut
 
sub _deleteOpendkimDomainKey
{
	my $self = shift;
	
	my $domainId = shift;
	my $aliasId = shift;
	my $domain = shift;
	my $customerDnsPreviousStatus = shift;
	
	my $rs = 0;
	
	my $rdata;
	my $rdata2;
	
	my $pluginClass;
	
	my $db = iMSCP::Database->factory();
	
	$rs = iMSCP::Dir->new('dirname' => '/etc/opendkim/' . $domain)->remove() if -d '/etc/opendkim/' . $domain;
	return $rs if $rs;
	
	# Remove domain from KeyTable file
	my $file = iMSCP::File->new('filename' => '/etc/opendkim/KeyTable');
	
	my $fileContent = $file->get();
	#return $fileContent if ! $fileContent; #Must be deactivated because an empty file rise up an error
	
	$fileContent =~ s/.*$domain.*\n//g;
	
	$rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;
	
	# Remove domain from SigningTable file
	$file = iMSCP::File->new('filename' => '/etc/opendkim/SigningTable');
	
	$fileContent = $file->get();
	#return $fileContent if ! $fileContent; #Must be deactivated because an empty file rise up an error
	
	$fileContent =~ s/.*$domain.*\n//g;
	
	$rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;
	
	$rdata = $db->doQuery('dummy', 'DELETE FROM `domain_dns` WHERE `domain_id` = ? AND `alias_id` = ? AND `domain_dns` = ?', $domainId, $aliasId, 'mail._domainkey');
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	if($aliasId eq '0') {
		$rdata = $db->doQuery('domain_dns_id', "SELECT * FROM `domain_dns` WHERE `domain_id` = ?", $domainId);
		
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}
		
		if(%{$rdata}) {
			$rdata2 = $db->doQuery('dummy', "UPDATE `domain` SET `domain_status` = 'tochange' WHERE `domain_id` = ?", $domainId);
			
			unless(ref $rdata2 eq 'HASH') {
				error($rdata2);
				return 1;
			}
		} else {
			$rdata2 = $db->doQuery('dummy', "UPDATE `domain` SET `domain_status` = 'tochange', `domain_dns` = ? WHERE `domain_id` = ?", $customerDnsPreviousStatus, $domainId);
			
			unless(ref $rdata2 eq 'HASH') {
				error($rdata2);
				return 1;
			}
		}
	} else {
		$rdata = $db->doQuery('dummy', "UPDATE `domain_aliasses` SET `alias_status` = 'tochange' WHERE `alias_id` = ?", $aliasId);
		
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}
	}

	if($aliasId eq '0') {	
		$pluginClass = "Modules::Domain";
		$rs = $pluginClass->new()->process($domainId);
	} else {
		require Modules::Alias;
		$pluginClass = "Modules::Alias";
		$rs = $pluginClass->new()->process($aliasId);
	}
	return $rs if $rs;
	
	0;
}

=item _modifyOpendkimSystemConfig()

 Modify OpenDKIM system config file

 Return int 0 on success, other on failure

=cut

sub _modifyOpendkimSystemConfig
{
	my $self = shift;
	my $action = shift;

	my $opendkimSystemconf = '/etc/opendkim.conf';
	return 1 if ! -f $opendkimSystemconf;
	
	my $file = iMSCP::File->new('filename' => $opendkimSystemconf);

	my $fileContent = $file->get();
	return $fileContent if ! $fileContent;
	
	my $opendkimConfig = "\n# Begin Plugin::OpenDKIM\n";
	$opendkimConfig .= "KeyTable\t\trefile:/etc/opendkim/KeyTable\n";
	$opendkimConfig .= "SigningTable\t\trefile:/etc/opendkim/SigningTable\n";
	$opendkimConfig .= "ExternalIgnoreList\t/etc/opendkim/TrustedHosts\n";
	$opendkimConfig .= "InternalHosts\t\t/etc/opendkim/TrustedHosts\n";
	$opendkimConfig .= "# Ending Plugin::OpenDKIM\n";
	
	if($action eq 'add') {
		if ($fileContent =~ /^# Begin Plugin::OpenDKIM.*Ending Plugin::OpenDKIM\n/sgm) {
			$fileContent =~ s/^\n# Begin Plugin::OpenDKIM.*Ending Plugin::OpenDKIM\n/$opendkimConfig/sgm;
		} else {
			$fileContent .= "$opendkimConfig";
		}
	}
	elsif($action eq 'remove') {
		$fileContent =~ s/^\n# Begin Plugin::OpenDKIM.*Ending Plugin::OpenDKIM\n//sgm;
	}
	
	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _modifyOpendkimDefaultConfig()

 Modify OpenDKIM default config file

 Return int 0 on success, other on failure

=cut

sub _modifyOpendkimDefaultConfig
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
		$opendkimSocketConfig = "\n# Begin Plugin::OpenDKIM\n";
		$opendkimSocketConfig .= "SOCKET=\"inet:" .$opendkimConfig->{'opendkim_port'}. "\@localhost\"\n";
		$opendkimSocketConfig .= "# Ending Plugin::OpenDKIM\n";
	} else {
		$opendkimSocketConfig = "\n# Begin Plugin::OpenDKIM\n";
		$opendkimSocketConfig .= "SOCKET=\"inet:12345\@localhost\"\n";
		$opendkimSocketConfig .= "# Ending Plugin::OpenDKIM\n";
	}
	
	my $file = iMSCP::File->new('filename' => $opendkimDefaultConfig);
	
	my $fileContent = $file->get();
	return $fileContent if ! $fileContent;
	
	if($action eq 'add') {
		if ($fileContent =~ /^# Begin Plugin::OpenDKIM.*Ending Plugin::OpenDKIM\n/sgm) {
			$fileContent =~ s/^\n# Begin Plugin::OpenDKIM.*Ending Plugin::OpenDKIM\n/$opendkimSocketConfig/sgm;
		} else {
			$fileContent .= "$opendkimSocketConfig";
		}
	} elsif($action eq 'remove') {
		$fileContent =~ s/^\n# Begin Plugin::OpenDKIM.*Ending Plugin::OpenDKIM\n//sgm;
	}
	
	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _modifyPostfixMainConfig()

 Modify postfix main.cf config file

 Return int 0 on success, other on failure

=cut

sub _modifyPostfixMainConfig
{
	my $self = shift;
	my $action = shift;
	
	my $postfixOpendkimConfig;
	my @miltersValues;
	
	my $rs = 0;
	my ($stdout, $stderr);
	
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
	
	my $file = iMSCP::File->new('filename' => '/etc/postfix/main.cf');
	
	my $fileContent = $file->get();
	return $fileContent if ! $fileContent;
	
	$rs = execute('postconf smtpd_milters', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	if($action eq 'add') {
		$stdout =~ /^smtpd_milters\s?=\s?(.*)/gm;
		@miltersValues = split(' ', $1);
		
		if(scalar @miltersValues >= 1) {
			$fileContent =~ s/^\t# Begin Plugin::OpenDKIM.*Ending Plugin::OpenDKIM\n//sgm;
			
			if($opendkimConfig->{'opendkim_port'} =~ /\d{4,5}/ && $opendkimConfig->{'opendkim_port'} <= 65535) { #check the port is numeric and has min. 4 and max. 5 digits
				$postfixOpendkimConfig = "\t# Begin Plugin::OpenDKIM\n";
				$postfixOpendkimConfig .= "\tinet:localhost:" . $opendkimConfig->{'opendkim_port'} ."\n";
				$postfixOpendkimConfig .= "\t# Ending Plugin::OpenDKIM\n";
			} else {
				$postfixOpendkimConfig = "\t# Begin Plugin::OpenDKIM\n";
				$postfixOpendkimConfig .= "\tinet:localhost:12345\n";
				$postfixOpendkimConfig .= "\t# Ending Plugin::OpenDKIM\n";
			}
			
			$fileContent =~ s/^(non_smtpd_milters.*)/$postfixOpendkimConfig$1/gm;
		} else {
			$fileContent =~ s/^\n# Begin Plugins::i-MSCP.*Ending Plugins::i-MSCP\n//sgm;
			
			if($opendkimConfig->{'opendkim_port'} =~ /\d{4,5}/ && $opendkimConfig->{'opendkim_port'} <= 65535) { #check the port is numeric and has min. 4 and max. 5 digits
				$postfixOpendkimConfig = "\n# Begin Plugins::i-MSCP\n";
				$postfixOpendkimConfig .= "milter_default_action = accept\n";
				$postfixOpendkimConfig .= "smtpd_milters = \n";
				$postfixOpendkimConfig .= "\t# Begin Plugin::OpenDKIM\n";
				$postfixOpendkimConfig .= "\tinet:localhost:" . $opendkimConfig->{'opendkim_port'} ."\n";
				$postfixOpendkimConfig .= "\t# Ending Plugin::OpenDKIM\n";
				$postfixOpendkimConfig .= "non_smtpd_milters = \$smtpd_milters\n";
				$postfixOpendkimConfig .= "# Ending Plugins::i-MSCP\n";
			} else {
				$postfixOpendkimConfig = "\n# Begin Plugins::i-MSCP\n";
				$postfixOpendkimConfig .= "milter_default_action = accept\n";
				$postfixOpendkimConfig .= "smtpd_milters = \n";
				$postfixOpendkimConfig .= "\t# Begin Plugin::OpenDKIM\n";
				$postfixOpendkimConfig .= "\tinet:localhost:12345\n";
				$postfixOpendkimConfig .= "\t# Ending Plugin::OpenDKIM\n";
				$postfixOpendkimConfig .= "non_smtpd_milters = \$smtpd_milters\n";
				$postfixOpendkimConfig .= "# Ending Plugins::i-MSCP\n";
			}
			
			$fileContent .= "$postfixOpendkimConfig";
		}
	} elsif($action eq 'remove') {		
		$stdout =~ /^smtpd_milters\s?=\s?(.*)/gm;
		@miltersValues = split(' ', $1);
		
		if(scalar @miltersValues > 1) {
			$fileContent =~ s/^\t# Begin Plugin::OpenDKIM.*Ending Plugin::OpenDKIM\n//sgm;
		} else {
			$fileContent =~ s/^\n# Begin Plugins::i-MSCP.*Ending Plugins::i-MSCP\n//sgm;
		}
	}
	
	$rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _createOpendkimFileDir()

 Creates directory for KeyTable and SigningTable files

 Return int 0 on success, other on failure

=cut

sub _createOpendkimFileDir
{
	my $self = shift;
	
	my $rs = iMSCP::Dir->new('dirname' => '/etc/opendkim/')->make(
		{ 'user' => 'opendkim', 'group' => 'opendkim', 'mode' => 0750 }
	);
	return $rs if $rs;
	
	$rs = $self->_createOpendkimKeyTableFile();
	return $rs if $rs;
	
	$rs = $self->_createOpendkimSigningTableFile();
	return $rs if $rs;
	
	$self->_createOpendkimTrustedHostsFile();
}

=item _createOpendkimTableFiles()

 Creates KeyTable and SigningTable file

 Return int 0 on success, other on failure

=cut

sub _createOpendkimKeyTableFile
{
	my $self = shift;
	
	my $KeyTable = iMSCP::File->new('filename' => '/etc/opendkim/KeyTable');
	
	my $rs = $KeyTable->save();
	return $rs if $rs;

	$rs = $KeyTable->mode(0640);
	return $rs if $rs;

	$KeyTable->owner('opendkim', 'opendkim');
}

=item _createOpendkimSigningTableFile()

 Creates SigningTable file

 Return int 0 on success, other on failure

=cut

sub _createOpendkimSigningTableFile
{
	my $self = shift;
	
	my $SigningTable = iMSCP::File->new('filename' => '/etc/opendkim/SigningTable');
	
	my $rs = $SigningTable->save();
	return $rs if $rs;

	$rs = $SigningTable->mode(0640);
	return $rs if $rs;

	$SigningTable->owner('opendkim', 'opendkim');
}

=item _createOpendkimTrustedHostsFile()

 Creates TrustedHosts file

 Return int 0 on success, other on failure

=cut

sub _createOpendkimTrustedHostsFile
{
	my $self = shift;
	
	my $fileContent;
	
	my $TrustedHostsFile = iMSCP::File->new('filename' => '/etc/opendkim/TrustedHosts');
	
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

# This did not work with perl 5.1.0
#	while (my($opendkimConfigKey, $opendkimConfigValue) = each($opendkimConfig->{'opendkim_trusted_hosts'})) {
#		$fileContent .= $opendkimConfigValue . "\n";
#	}
# Thanks to Nuxwin for the solution above

	for(@{$opendkimConfig->{'opendkim_trusted_hosts'}}) {
		$fileContent .= "$_\n";
	} 
	
	my $rs = $TrustedHostsFile->set($fileContent);
	return $rs if $rs;
	
	$rs = $TrustedHostsFile->save();
	return $rs if $rs;

	$rs = $TrustedHostsFile->mode(0640);
	return $rs if $rs;

	$TrustedHostsFile->owner('opendkim', 'opendkim');
}

=item _restartDaemonOpendkim()

 Restart the OpenDKIM daemon

 Return int 0 on success, other on failure

=cut

sub _restartDaemonOpendkim
{
	my $self = shift;
	
	my ($stdout, $stderr);
	
	my $rs = execute('service opendkim restart', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	return $rs if $rs;
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

	if(! -d '/etc/opendkim/') {
		$rs = $self->_createOpendkimFileDir();
		return $rs if $rs;
	} else {
		if(! -f '/etc/opendkim/KeyTable') {
			$rs = $self->_createOpendkimKeyTableFile();
			return $rs if $rs;
		}
		if(! -f '/etc/opendkim/SigningTable') {
			$rs = $self->_createOpendkimSigningTableFile();
			return $rs if $rs;
		}
		if(! -f '/etc/opendkim/SigningTable') {
			$rs = $self->_createOpendkimTrustedHostsFile();
			return $rs if $rs;
		}
	}
	
	$rs = $self->_modifyOpendkimSystemConfig('add');
	return $rs if $rs;
	
	$rs = $self->_modifyOpendkimDefaultConfig('add');
	return $rs if $rs;
	
	$self->_modifyPostfixMainConfig('add');
}

=back

=head1 AUTHORS AND CONTRIBUTORS

 Sascha Bay <info@space2place.de>

=cut

1;
