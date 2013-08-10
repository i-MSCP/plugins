#!/usr/bin/perl

=head1 NAME

    Plugin::OpenDKIM
 
=cut

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
# @category i-MSCP
# @package iMSCP_Plugin
# @subpackage OpenDKIM
# @copyright 2010-2013 by i-MSCP | http://i-mscp.net
# @author Sascha Bay <info@space2place.de>
# @link http://i-mscp.net i-MSCP Home Site
# @license http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 
package Plugin::OpenDKIM;
 
use strict;
use warnings;
 
use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Database;
use iMSCP::File;
 
my $hooksManager = iMSCP::HooksManager->getInstance();
 
=head1 DESCRIPTION
 
Plugin adds OpenDKIM support for i-MSCP panel
 
=head1 PUBLIC METHODS
 
=over 4
 
=item onAfterMtaBuildOpenDKIM
 
Adds the lines to the main.cf for OpenDKIM support
 
Return int 0
 
=cut
 
sub onAfterMtaBuildOpenDKIM
{
	my $fileContent = shift;
	
	my $postfixOpendkimConfig;	
	my $imscpDbName = $main::imscpConfig{'DATABASE_NAME'};
	
	my $db = iMSCP::Database->factory();
	
	$db->set('DATABASE_NAME', $imscpDbName);
	
	my $rs = $db->connect();
	if($rs) {
		error("Unable to connect to the i-MSCP '$imscpDbName' SQL database: $rs");
		return 1 if $rs;
	}

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
		$postfixOpendkimConfig = "# Start Added by Plugins::OpenDKIM\n";
		$postfixOpendkimConfig .= "milter_default_action = accept\n";
		$postfixOpendkimConfig .= "milter_protocol = 2\n";
		$postfixOpendkimConfig .= "smtpd_milters = inet:localhost:" .$opendkimConfig->{'opendkim_port'} ."\n";
		$postfixOpendkimConfig .= "non_smtpd_milters = inet:localhost:" .$opendkimConfig->{'opendkim_port'} ."\n";
		$postfixOpendkimConfig .= "# Added by Plugins::OpenDKIM End\n";
	} else {
		$postfixOpendkimConfig = "# Start Added by Plugins::OpenDKIM\n";
		$postfixOpendkimConfig .= "milter_default_action = accept\n";
		$postfixOpendkimConfig .= "milter_protocol = 2\n";
		$postfixOpendkimConfig .= "smtpd_milters = inet:localhost:12345\n";
		$postfixOpendkimConfig .= "non_smtpd_milters = inet:localhost:12345\n";
	}
	
	if ($$fileContent =~ /^# Start Added by Plugins.*End\n/sgm) {
		$$fileContent =~ s/^# Start Added by Plugins.*End\n/$postfixOpendkimConfig/sgm;
	} else {
		$$fileContent .= "$postfixOpendkimConfig";
	}
	
	0;
}

=item afterMtaAddDmnOpenDKIM
 
Adds the new domain for OpenDKIM support
 
Return int 0
 
=cut
 
sub afterMtaAddDmnOpenDKIM
{
	my $domainData = shift;
	
	my $rs = 0;
	
	my $domain = $domainData->{DOMAIN_NAME};
	my $domainId;
	
	my ($stdout, $stderr);
	
	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery(
		'domain_id', 'SELECT `domain_id` FROM `domain` WHERE `domain_name` = ?', $domain
	);
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	foreach (keys %$rdata) { $domainId = $rdata->{$_}->{domain_id}; }
	
	if(! -d '/etc/imscp/opendkim/' . $domain) {
		$rs = iMSCP::Dir->new('dirname' => '/etc/imscp/opendkim/' . $domain)->make(
			{ 'user' => 'opendkim', 'group' => 'opendkim', 'mode' => 0750 }
		);
		return 1 if $rs;
		
		
		$rs = execute('opendkim-genkey -D /etc/imscp/opendkim/' . $domain . ' -r -d ' . $domain, \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		
		my $file = iMSCP::File->new('filename' => '/etc/imscp/opendkim/' . $domain . '/default.private');
		
		$rs = $file->owner('opendkim', 'opendkim');
		return 1 if $rs;
		
		$file = iMSCP::File->new('filename' => '/etc/imscp/opendkim/' . $domain . '/default.txt');
		
		my $fileContent = $file->get();
		return 1 if ! $fileContent;
		
		(my $txtRecord) = ($fileContent =~ /(\".*\")/);
		
		# Why should i delete this file? I think there is no security risk
		#$rs = $file->delFile();
		#return 1 if $rs;
		
		# Now add the private key to the KeyTable file
		$file = iMSCP::File->new('filename' => '/etc/imscp/opendkim/KeyTable');
		
		$fileContent = $file->get();
		#return 1 if ! $fileContent; #Must be deactivated because an empty file rise up an error
		
		my $privateKeyEntry = "default._domainkey." . $domain . " " . $domain . ":default:/etc/imscp/opendkim/" . $domain . "/default.private\n";
		
		$fileContent .= $privateKeyEntry;
		
		$rs = $file->set($fileContent);
		return 1 if $rs;

		$rs = $file->save();
		return 1 if $rs;
		
		# Now add the domain to the SigningTable file
		$file = iMSCP::File->new('filename' => '/etc/imscp/opendkim/SigningTable');
		
		$fileContent = $file->get();
		#return 1 if ! $fileContent; #Must be deactivated because an empty file rise up an error
		
		my $domainEntry = "*@" . $domain . " default._domainkey." . $domain;
		
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
					`domain_dns`,
					`domain_class`,
					`domain_type`,
					`domain_text`,
					`protected`
				) VALUES (
					?
					, 'default._domainkey'
					, 'IN'
					, 'TXT'
					, ?
					, 'yes'
				)
			"
			,$domainId ,$txtRecord
		);
		
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}
		
		# OpenDKIM daemon must restartet
		my $pluginClass = "Plugin::OpenDKIM";
		$rs = $pluginClass->getInstance()->_restartDaemonOpenDKIM();
		
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
			,$domainId
		);
		
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}
		
		$pluginClass = "Modules::Domain";
		$rs = -$pluginClass->new()->process($domainId);
		return 1 if $rs;
	}

	0;
}
 
$hooksManager->register('afterMtaBuildMainCfFile', \&onAfterMtaBuildOpenDKIM);
$hooksManager->register('afterMtaAddDmn', \&afterMtaAddDmnOpenDKIM);
 
=back
 
=head1 AUTHOR
 
Sascha Bay <info@space2place.de>
 
=cut
 
1;
