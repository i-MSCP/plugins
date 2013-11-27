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
# @contributor Rene Schuster <mail@reneschuster.de>
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
use JSON;

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
		error('Unable to find opendkim-genkey. Please, install the opendkim-tools package first.');
		return 1;
	}

	if(! -d '/etc/opendkim/keys/') {
		my $rs = $self->_createOpendkimFolder();
		return $rs if $rs;
	} else {
		my $rs = $self->_createOpendkimFile('KeyTable');
		return $rs if $rs;

		$rs = $self->_createOpendkimFile('SigningTable');
		return $rs if $rs;

		$rs = $self->_createOpendkimFile('TrustedHosts');
		return $rs if $rs;
	}

	my $rs = $self->update();
	return $rs if $rs;

	0;
}

=item change()

 Perform change tasks

 Return int 0 on success, other on failure

=cut

sub change
{
	my $self = shift;

	my $rs = $self->_createOpendkimFile('TrustedHosts');
	return $rs if $rs;

	$rs = $self->update();
	return $rs if $rs;

	0;
}

=item update()

 Perform update tasks

 Return int 0 on success, other on failure

=cut

sub update
{
	my $self = shift;

	my $rs = $self->_modifyOpendkimConfig('add');
	return $rs if $rs;

	$rs = $self->_modifyOpendkimDefaultConfig('add');
	return $rs if $rs;

	$rs = $self->_restartDaemonOpendkim();
	return $rs if $rs;

	0;
}

=item enable()

 Perform enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = shift;

	my $rs = $self->_recoverOpendkimDnsEntries();
	return $rs if $rs;

	$rs = $self->_modifyPostfixMainConfig('add');
	return $rs if $rs;

	$rs = $self->_restartDaemonPostfix();
	return $rs if $rs;

	0;
}

=item disable()

 Perform disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
	my $self = shift;

	my $rs = $self->_removeOpendkimDnsEntries();
	return $rs if $rs;

	$rs = $self->_modifyPostfixMainConfig('remove');
	return $rs if $rs;

	$rs = $self->_restartDaemonPostfix();
	return $rs if $rs;

	0;
}

=item uninstall()

 Perform uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	my $rs = $self->_modifyOpendkimConfig('remove');
	return $rs if $rs;

	$rs = $self->_modifyOpendkimDefaultConfig('remove');
	return $rs if $rs;

	$rs = $self->_restartDaemonOpendkim();
	return $rs if $rs;

	$rs = iMSCP::Dir->new('dirname' => '/etc/opendkim/')->remove() if -d '/etc/opendkim/';
	return $rs if $rs;

	0;
}

=item run()

 Create new entry for the OpenDKIM

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
				`opendkim_id`, `domain_id`, `alias_id`, `domain_name`, `customer_dns_previous_status`, `opendkim_status`
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
					$rdata->{$_}->{'domain_id'}, $rdata->{$_}->{'alias_id'}, $rdata->{$_}->{'domain_name'}
				);

				@sql = (
					'UPDATE `opendkim` SET `opendkim_status` = ? WHERE `opendkim_id` = ?',
					($rs ? scalar getMessageByType('error') : 'ok'), $rdata->{$_}->{'opendkim_id'}
				);
			} elsif($rdata->{$_}->{'opendkim_status'} eq 'todelete') {
				$rs = $self->_deleteOpendkimDomainKey(
					$rdata->{$_}->{'domain_id'},
					$rdata->{$_}->{'alias_id'},
					$rdata->{$_}->{'domain_name'},
					($rdata->{$_}->{'customer_dns_previous_status'})
						? $rdata->{$_}->{'customer_dns_previous_status'} : 'na'
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

		# OpenDKIM daemon must be restarted
		$rs = $self->_restartDaemonOpendkim();
		return $rs if $rs;
	}

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize plugin

 Return Plugin::OpenDKIM

=cut

sub _init
{
	my $self = shift;

    if($self->{'action'} ~~ ['install', 'change', 'update', 'enable']) {
		# Loading plugin configuration
		my $rdata = iMSCP::Database->factory()->doQuery(
			'plugin_name', 'SELECT plugin_name, plugin_config FROM plugin WHERE plugin_name = ?', 'OpenDKIM'
		);
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}

		$self->{'config'} = decode_json($rdata->{'OpenDKIM'}->{'plugin_config'});
	}

	$self;
}

=item _addOpendkimDomainKey($domainId, $aliasId, $domain)

 Adds the new domain key for OpenDKIM support

 Return int 0

=cut
 
sub _addOpendkimDomainKey($$$$)
{
	my ($self, $domainId, $aliasId, $domain) = @_;

	if(! -d "/etc/opendkim/keys/$domain") {
		my $rs = iMSCP::Dir->new('dirname' => "/etc/opendkim/keys/$domain")->make(
			{ 'user' => 'opendkim', 'group' => 'opendkim', 'mode' => 0750 }
		);
		return $rs if $rs;

		my ($stdout, $stderr);
		$rs = execute("opendkim-genkey -D /etc/opendkim/keys/$domain -r -s mail -d $domain", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		my $file = iMSCP::File->new('filename' => "/etc/opendkim/keys/$domain/mail.private");

		$rs = $file->owner('opendkim', 'opendkim');
		return $rs if $rs;

		$file = iMSCP::File->new('filename' => "/etc/opendkim/keys/$domain/mail.txt");

		my $fileContent = $file->get();
		unless (defined $fileContent) {
			error("Unable to read /etc/opendkim/keys/$domain/mail.txt");
			return 1;
		}

		(my $txtRecord) = ($fileContent =~ /(\".*\")/);

		$rs = $file->owner('opendkim', 'opendkim');
		return $rs if $rs;

		# Add the domain to the KeyTable file
		$file = iMSCP::File->new('filename' => '/etc/opendkim/KeyTable');

		$fileContent = $file->get();
		unless (defined $fileContent) {
			error("Unable to read /etc/opendkim/KeyTable");
			return 1;
		}

		$fileContent .= "mail._domainkey.$domain $domain:mail:/etc/opendkim/keys/$domain/mail.private\n";

		$rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		# Add the domain to the SigningTable file
		$file = iMSCP::File->new('filename' => '/etc/opendkim/SigningTable');

		$fileContent = $file->get();
		unless (defined $fileContent) {
			error("Unable to read /etc/opendkim/SigningTable");
			return 1;
		}

		$fileContent .= "*\@$domain mail._domainkey.$domain\n";

		$rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		# Save the DNS TXT record to database
		my $db = iMSCP::Database->factory();

		my $rdata = $db->doQuery(
			'domain_id', 
			"
				INSERT INTO `domain_dns` (
					`domain_id`, `alias_id`, `domain_dns`, `domain_class`, `domain_type`, `domain_text`, `owned_by`
				) VALUES (
					?, ?, ?, 'IN', 'TXT', ?, 'opendkim_feature'
				)
			",
			$domainId, $aliasId, 'mail._domainkey', $txtRecord
		);
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}

		if($aliasId eq '0') {
			$rdata = $db->doQuery(
				'dummy',
				"UPDATE `domain` SET `domain_status` = 'tochange', `domain_dns` = 'yes' WHERE `domain_id` = ?",
				$domainId
			);
		} else {
			$rdata = $db->doQuery(
				'dummy', 
				"UPDATE `domain_aliasses` SET `alias_status` = 'tochange' WHERE `alias_id` = ?", 
				$aliasId
			);
		}
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}
	}

	0;
}

=item _removeOpendkimDnsEntries()
 
 Remove all OpenDKIM DNS entries
 
 Return int 0
 
=cut

sub _removeOpendkimDnsEntries
{
	my $self = shift;

	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery(
		'dummy', 'DELETE FROM `domain_dns` WHERE `owned_by` = ?', 'opendkim_feature'
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	$rdata = $db->doQuery(
		'opendkim_id', 'SELECT * FROM `opendkim`'
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	if(%{$rdata}) {
		for(keys %{$rdata}) {
			my $domainId = $rdata->{$_}->{'domain_id'};
			my $aliasId = $rdata->{$_}->{'alias_id'};
			my $domain_dns = $rdata->{$_}->{'customer_dns_previous_status'};
			my $rdata2;
			
			if($aliasId eq '0') {
				$rdata2 = $db->doQuery(
					'domain_dns_id', 
					"SELECT `domain_dns_id` FROM `domain_dns` WHERE `domain_id` = ? LIMIT 1",
					$domainId
				);
				unless(ref $rdata2 eq 'HASH') {
					error($rdata2);
					return 1;
				}

				if(%{$rdata2}) {
					$rdata2 = $db->doQuery(
						'dummy', 
						"UPDATE `domain` SET `domain_status` = 'tochange' WHERE `domain_id` = ?",
						$domainId
					);
					unless(ref $rdata2 eq 'HASH') {
						error($rdata2);
						return 1;
					}
				} else {
					$rdata2 = $db->doQuery(
						'dummy',
						"UPDATE `domain` SET `domain_status` = 'tochange', `domain_dns` = ? WHERE `domain_id` = ?",
						$domain_dns, $domainId
					);
				unless(ref $rdata2 eq 'HASH') {
					error($rdata2);
					return 1;
				}
				}
			} else {
				$rdata2 = $db->doQuery(
					'dummy',
					"UPDATE `domain_aliasses` SET `alias_status` = 'tochange' WHERE `alias_id` = ?",
					$aliasId
				);
				unless(ref $rdata2 eq 'HASH') {
					error($rdata2);
					return 1;
				}
			}
		}
	}

	0;
}

=item _recoverOpendkimDnsEntries()
 
 Recover all OpenDKIM DNS entries
 
 Return int 0
 
=cut

sub _recoverOpendkimDnsEntries
{
	my $self = shift;

	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery(
		'opendkim_id', 
		"
			SELECT
				`opendkim_id`, `domain_id`, `alias_id`, `domain_name`
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

			if(-d "/etc/opendkim/keys/$domain") {
				my $file = iMSCP::File->new('filename' => "/etc/opendkim/keys/$domain/mail.txt");

				my $fileContent = $file->get();
				unless (defined $fileContent) {
					error("Unable to read /etc/opendkim/keys/$domain/mail.txt");
					return 1;
				}

				(my $txtRecord) = ($fileContent =~ /(\".*\")/);

				# Save the DNS TXT record to database
				my $rdata2 = $db->doQuery(
					'domain_id', 
					"
						INSERT INTO `domain_dns` (
							`domain_id`, `alias_id`, `domain_dns`, `domain_class`, `domain_type`, `domain_text`, `owned_by`
						) VALUES (
							?, ?, ?, 'IN', 'TXT', ?, 'opendkim_feature'
						)
					",
					$domainId, $aliasId, 'mail._domainkey', $txtRecord
				);
				unless(ref $rdata2 eq 'HASH') {
					error($rdata2);
					return 1;
				}

				if($aliasId eq '0') {
					$rdata2 = $db->doQuery(
						'dummy', 
						"UPDATE `domain` SET `domain_status` = 'tochange', `domain_dns` = 'yes' WHERE `domain_id` = ?", 
						$domainId
					);
				} else {
					$rdata2 = $db->doQuery(
						'dummy', 
						"UPDATE `domain_aliasses` SET `alias_status` = 'tochange' WHERE `alias_id` = ?", 
						$aliasId
					);
				}
				unless(ref $rdata2 eq 'HASH') {
					error($rdata2);
					return 1;
				}
			} else {
				error("The OpenDKIM folder for the domain $rdata->{$_}->{'domain_name'} does not exist!");
			}
		}
	}

	0;
}

=item _deleteOpendkimDomainKey($domainId, $aliasId, $domain, $customerDnsPreviousStatus)
 
 Deletes domain key from OpenDKIM support
 
 Return int 0
 
=cut
 
sub _deleteOpendkimDomainKey($$$$$)
{
	my ($self, $domainId, $aliasId, $domain, $customerDnsPreviousStatus) = @_;

	my $rs = iMSCP::Dir->new('dirname' => "/etc/opendkim/keys/$domain")->remove() if -d "/etc/opendkim/keys/$domain";
	return $rs if $rs;

	# Remove domain from KeyTable file
	my $file = iMSCP::File->new('filename' => '/etc/opendkim/KeyTable');

	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read /etc/opendkim/KeyTable");
		return 1;
	}

	$fileContent =~ s/.*$domain.*\n//g;

	$rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	# Remove domain from SigningTable file
	$file = iMSCP::File->new('filename' => '/etc/opendkim/SigningTable');

	$fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read /etc/opendkim/SigningTable");
		return 1;
	}

	$fileContent =~ s/.*$domain.*\n//g;

	$rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery(
		'dummy',
		'DELETE FROM `domain_dns` WHERE `domain_id` = ? AND `alias_id` = ? AND `domain_dns` = ?',
		$domainId, $aliasId, 'mail._domainkey'
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	if($aliasId eq '0') {
		$rdata = $db->doQuery(
			'domain_dns_id', 
			"SELECT `domain_dns_id` FROM `domain_dns` WHERE `domain_id` = ? LIMIT 1", 
			$domainId
		);
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}

		my $rdata2;

		if(%{$rdata}) {
			$rdata2 = $db->doQuery(
				'dummy', 
				"UPDATE `domain` SET `domain_status` = 'tochange' WHERE `domain_id` = ?", 
				$domainId
			);
			unless(ref $rdata2 eq 'HASH') {
				error($rdata2);
				return 1;
			}
		} else {
			$rdata2 = $db->doQuery(
				'dummy',
				"UPDATE `domain` SET `domain_status` = 'tochange', `domain_dns` = ? WHERE `domain_id` = ?",
				$customerDnsPreviousStatus, $domainId
			);
			unless(ref $rdata2 eq 'HASH') {
				error($rdata2);
				return 1;
			}
		}
	} else {
		$rdata = $db->doQuery(
			'dummy', 
			"UPDATE `domain_aliasses` SET `alias_status` = 'tochange' WHERE `alias_id` = ?", 
			$aliasId
		);
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}
	}

	0;
}

=item _modifyOpendkimConfig($action)

 Modify OpenDKIM config file

 Return int 0 on success, other on failure

=cut

sub _modifyOpendkimConfig($$)
{
	my ($self, $action) = @_;

	my $file = iMSCP::File->new('filename' => '/etc/opendkim.conf');

	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read /etc/opendkim.conf");
		return 1;
	}

	if($action eq 'add') {
		my $opendkimConfig = "\n# Begin Plugin::OpenDKIM\n";
		$opendkimConfig .= "Canonicalization\t$self->{'config'}->{'opendkim_canonicalization'}\n\n";
		$opendkimConfig .= "KeyTable\t\trefile:/etc/opendkim/KeyTable\n";
		$opendkimConfig .= "SigningTable\t\trefile:/etc/opendkim/SigningTable\n";
		$opendkimConfig .= "ExternalIgnoreList\t/etc/opendkim/TrustedHosts\n";
		$opendkimConfig .= "InternalHosts\t\t/etc/opendkim/TrustedHosts\n";
		$opendkimConfig .= "# Ending Plugin::OpenDKIM\n";

		if ($fileContent =~ /^# Begin Plugin::OpenDKIM.*Ending Plugin::OpenDKIM\n/sgm) {
			$fileContent =~ s/^\n# Begin Plugin::OpenDKIM.*Ending Plugin::OpenDKIM\n/$opendkimConfig/sgm;
		} else {
			$fileContent .= "$opendkimConfig";
		}
	} elsif($action eq 'remove') {
		$fileContent =~ s/^\n# Begin Plugin::OpenDKIM.*Ending Plugin::OpenDKIM\n//sgm;
	}

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	0;
}

=item _modifyOpendkimDefaultConfig($action)

 Modify OpenDKIM default config file

 Return int 0 on success, other on failure

=cut

sub _modifyOpendkimDefaultConfig($$)
{
	my ($self, $action) = @_;

	my $file = iMSCP::File->new('filename' => '/etc/default/opendkim');

	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read /etc/default/opendkim");
		return 1;
	}

	if($action eq 'add') {	
		my $opendkimSocketConfig;

		# Check the port is numeric and has min. 4 and max. 5 digits
		if($self->{'config'}->{'opendkim_port'} =~ /\d{4,5}/ && $self->{'config'}->{'opendkim_port'} <= 65535) {
			$opendkimSocketConfig = "\n# Begin Plugin::OpenDKIM\n";
			$opendkimSocketConfig .= "SOCKET=\"inet:$self->{'config'}->{'opendkim_port'}\@localhost\"\n";
			$opendkimSocketConfig .= "# Ending Plugin::OpenDKIM\n";
		} else {
			$opendkimSocketConfig = "\n# Begin Plugin::OpenDKIM\n";
			$opendkimSocketConfig .= "SOCKET=\"inet:12345\@localhost\"\n";
			$opendkimSocketConfig .= "# Ending Plugin::OpenDKIM\n";
		}

		if ($fileContent =~ /^# Begin Plugin::OpenDKIM.*Ending Plugin::OpenDKIM\n/sgm) {
			$fileContent =~ s/^\n# Begin Plugin::OpenDKIM.*Ending Plugin::OpenDKIM\n/$opendkimSocketConfig/sgm;
		} else {
			$fileContent .= "$opendkimSocketConfig";
		}
	} 
	elsif($action eq 'remove') {
		$fileContent =~ s/^\n# Begin Plugin::OpenDKIM.*Ending Plugin::OpenDKIM\n//sgm;
	}

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	0;
}

=item _modifyPostfixMainConfig($action)

 Modify Postfix main.cf config file

 Return int 0 on success, other on failure

=cut

sub _modifyPostfixMainConfig($$)
{
	my ($self, $action) = @_;

	my $file = iMSCP::File->new('filename' => '/etc/postfix/main.cf');

	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read /etc/postfix/main.cf");
		return 1;
	}

	my ($stdout, $stderr);
	my $rs = execute('postconf smtpd_milters', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	if($action eq 'add') {
		$stdout =~ /^smtpd_milters\s?=\s?(.*)/gm;
		my @miltersValues = split(' ', $1);

		my $postfixOpendkimConfig;

		if(scalar @miltersValues >= 1) {
			$fileContent =~ s/^\t# Begin Plugin::OpenDKIM.*Ending Plugin::OpenDKIM\n//sgm;

			# Check the port is numeric and has min. 4 and max. 5 digits
			if($self->{'config'}->{'opendkim_port'} =~ /\d{4,5}/ && $self->{'config'}->{'opendkim_port'} <= 65535) {
				$postfixOpendkimConfig = "\t# Begin Plugin::OpenDKIM\n";
				$postfixOpendkimConfig .= "\tinet:localhost:" . $self->{'config'}->{'opendkim_port'} ."\n";
				$postfixOpendkimConfig .= "\t# Ending Plugin::OpenDKIM\n";
			} else {
				$postfixOpendkimConfig = "\t# Begin Plugin::OpenDKIM\n";
				$postfixOpendkimConfig .= "\tinet:localhost:12345\n";
				$postfixOpendkimConfig .= "\t# Ending Plugin::OpenDKIM\n";
			}

			$fileContent =~ s/^(non_smtpd_milters.*)/$postfixOpendkimConfig$1/gm;
		} else {
			$fileContent =~ s/^\n# Begin Plugins::i-MSCP.*Ending Plugins::i-MSCP\n//sgm;

			# Check the port is numeric and has min. 4 and max. 5 digits
			if($self->{'config'}->{'opendkim_port'} =~ /\d{4,5}/ && $self->{'config'}->{'opendkim_port'} <= 65535) {
				$postfixOpendkimConfig = "\n# Begin Plugins::i-MSCP\n";
				$postfixOpendkimConfig .= "milter_default_action = accept\n";
				$postfixOpendkimConfig .= "smtpd_milters = \n";
				$postfixOpendkimConfig .= "\t# Begin Plugin::OpenDKIM\n";
				$postfixOpendkimConfig .= "\tinet:localhost:" . $self->{'config'}->{'opendkim_port'} ."\n";
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
	} 
	elsif($action eq 'remove') {
		$stdout =~ /^smtpd_milters\s?=\s?(.*)/gm;
		my @miltersValues = split(' ', $1);

		if(scalar @miltersValues > 1) {
			$fileContent =~ s/^\t# Begin Plugin::OpenDKIM.*Ending Plugin::OpenDKIM\n//sgm;
		} else {
			$fileContent =~ s/^\n# Begin Plugins::i-MSCP.*Ending Plugins::i-MSCP\n//sgm;
		}
	}

	$rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	0;
}

=item _createOpendkimFolder()

 Create OpenDKIM folder and corresponding files

 Return int 0 on success, other on failure

=cut

sub _createOpendkimFolder
{
	my $self = shift;

	my $rs = iMSCP::Dir->new('dirname' => '/etc/opendkim/keys')->make(
		{ 'user' => 'opendkim', 'group' => 'opendkim', 'mode' => 0750 }
	);
	return $rs if $rs;

	$rs = $self->_createOpendkimFile('KeyTable');
	return $rs if $rs;

	$rs = $self->_createOpendkimFile('SigningTable');
	return $rs if $rs;

	$rs = $self->_createOpendkimFile('TrustedHosts');
	return $rs if $rs;

	0;
}

=item _createOpendkimFile($fileName)

 Create OpenDKIM files

 Return int 0 on success, other on failure

=cut

sub _createOpendkimFile($)
{
	my ($self, $fileName) = @_;

	my $rs = 0;
	my $fileContent;

	my $file = iMSCP::File->new('filename' => "/etc/opendkim/$fileName");

	if($fileName eq 'TrustedHosts') {
		for(@{$self->{'config'}->{'opendkim_trusted_hosts'}}) {
			$fileContent .= "$_\n";
		}

		$rs = $file->set($fileContent);
		return $rs if $rs;
	}

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0640);
	return $rs if $rs;

	$rs = $file->owner('opendkim', 'opendkim');
	return $rs if $rs;

	0;
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

	0;
}

=item _restartDaemonPostfix()

 Restart the postfix daemon

 Return int 0 on success, other on failure

=cut

sub _restartDaemonPostfix
{
	my $self = shift;

	require Servers::mta;
	Servers::mta->factory()->{'restart'} = 'yes';

	0;
}

=back

=head1 AUTHORS AND CONTRIBUTORS

 - Sascha Bay <info@space2place.de> (Author)
 - Rene Schuster <mail@reneschuster.de> (Contributor)

=cut

1;
