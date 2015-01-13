#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2015 by internet Multi Server Control Panel
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
# @category    iMSCP
# @package     iMSCP_Plugin
# @subpackage  OpenDKIM
# @copyright   Sascha Bay <info@space2place.de>
# @copyright   Rene Schuster <mail@reneschuster.de>
# @author      Sascha Bay <info@space2place.de>
# @author      Rene Schuster <mail@reneschuster.de>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Plugin::OpenDKIM;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::Database;
use JSON;

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
	my $self = $_[0];

	if(! -x '/usr/sbin/opendkim') {
		error('Unable to find opendkim daemon. Please, install the opendkim package first.');
		return 1;
	}

	if(! -x '/usr/sbin/opendkim-genkey' && ! -x '/usr/bin/opendkim-genkey') {
		error('Unable to find opendkim-genkey. Please, install the opendkim-tools package first.');
		return 1;
	}

	unless(-d '/etc/opendkim/keys') {
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

	$self->update();
}

=item change()

 Perform change tasks

 Return int 0 on success, other on failure

=cut

sub change
{
	my $self = $_[0];

	my $rs = $self->_createOpendkimFile('TrustedHosts');
	return $rs if $rs;

	$self->update();
}

=item update()

 Perform update tasks

 Return int 0 on success, other on failure

=cut

sub update
{
	my $self = $_[0];

	my $rs = $self->_modifyOpendkimConfig('add');
	return $rs if $rs;

	$rs = $self->_modifyOpendkimDefaultConfig('add');
	return $rs if $rs;

	$self->_restartDaemonOpendkim();
}

=item enable()

 Perform enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = $_[0];

	my $rs = $self->_addOpendkimDnsEntries();
	return $rs if $rs;

	$rs = $self->_modifyPostfixMainConfig('add');
	return $rs if $rs;

	$self->_restartDaemonPostfix();
}

=item disable()

 Perform disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
	my $self = $_[0];

	my $rs = $self->_removeOpendkimDnsEntries();
	return $rs if $rs;

	$rs = $self->_modifyPostfixMainConfig('remove');
	return $rs if $rs;

	$self->_restartDaemonPostfix();
}

=item uninstall()

 Perform uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = $_[0];

	my $rs = $self->_modifyOpendkimConfig('remove');
	return $rs if $rs;

	$rs = $self->_modifyOpendkimDefaultConfig('remove');
	return $rs if $rs;

	$rs = $self->_restartDaemonOpendkim();
	return $rs if $rs;

	iMSCP::Dir->new('dirname' => '/etc/opendkim')->remove();
}

=item run()

 Create new entry for the OpenDKIM

 Return int 0 on success, other on failure

=cut

sub run
{
	my $self = $_[0];

	my $rs = 0;
	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery(
		'opendkim_id', 
		"
			SELECT
				opendkim_id, domain_id, IFNULL(alias_id, 0) AS alias_id, domain_name, opendkim_status
			FROM
				opendkim
			WHERE
				opendkim_status IN('toadd', 'tochange', 'todelete')
		"
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	my @sql;

	if(%{$rdata}) {
		for(keys %{$rdata}) {
			if($rdata->{$_}->{'opendkim_status'} ~~ ['toadd', 'tochange']) {
				$rs = $self->_addOpendkimDomainKey(
					$rdata->{$_}->{'domain_id'}, $rdata->{$_}->{'alias_id'}, $rdata->{$_}->{'domain_name'}
				);

				@sql = (
					'UPDATE opendkim SET opendkim_status = ? WHERE opendkim_id = ?',
					($rs ? scalar getMessageByType('error') : 'ok'), $_
				);
			} elsif($rdata->{$_}->{'opendkim_status'} eq 'todelete') {
				$rs = $self->_deleteOpendkimDomainKey(
					$rdata->{$_}->{'domain_id'},
					$rdata->{$_}->{'alias_id'},
					$rdata->{$_}->{'domain_name'}
				);

				if($rs) {
					@sql = (
						'UPDATE opendkim SET opendkim_status = ? WHERE opendkim_id = ?',
						scalar getMessageByType('error'), $_
					);
				} else {
					@sql = ('DELETE FROM opendkim WHERE opendkim_id = ?', $_);
				}
			}

			my $rdata2 = $db->doQuery('dummy', @sql);
			unless(ref $rdata2 eq 'HASH') {
				error($rdata2);
				return 1;
			}
		}

		# OpenDKIM daemon must be restarted
		$rs |= $self->_restartDaemonOpendkim();
		return $rs if $rs;
	}

	$rs;
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
	my $self = $_[0];

	# Force plugin manager to handle retval
	$self->{'FORCE_RETVAL'} = 'yes';

    if($self->{'action'} ~~ [ 'install', 'uninstall', 'change', 'update', 'enable', 'disable' ]) {
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

 Adds domain key for OpenDKIM support

 Param int $domainId Domain unique identifier
 Param int $aliasId Domain alias unique identifier (0 if no domain alias)
 Param string $domain Domain name
 Return int 0 on success, other on failure

=cut
 
sub _addOpendkimDomainKey($$$$)
{
	my ($self, $domainId, $aliasId, $domain) = @_;

	# This action must be idempotent (this allow to handle 'tochange' status which include key renewal)
	my $rs = $self->_deleteOpendkimDomainKey($domainId, $aliasId, $domain);
	return $rs if $rs;

	$rs = iMSCP::Dir->new('dirname' => "/etc/opendkim/keys/$domain")->make(
		{ 'user' => 'opendkim', 'group' => 'opendkim', 'mode' => 0750 }
	);
	return $rs if $rs;

	# Generate the domain private key and the DNS TXT record suitable for inclusion in DNS zone file
	# The DNS TXT record contain the public key
	my ($stdout, $stderr);
	$rs = execute("opendkim-genkey -D /etc/opendkim/keys/$domain -r -s mail -d $domain", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Fix permissions on the domain private key file

	my $file = iMSCP::File->new('filename' => "/etc/opendkim/keys/$domain/mail.private");

	$rs = $file->mode(0640);
	return $rs if $rs;

	$rs = $file->owner('opendkim', 'opendkim');
	return $rs if $rs;

	# Retrieve the TXT DNS record

	$file = iMSCP::File->new('filename' => "/etc/opendkim/keys/$domain/mail.txt");

	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read $file->{'filename'}");
		return 1;
	}

	$fileContent =~ s/"\n(.*)"p=/ p=/sgm; # Fix for Ubuntu 14.04 Trusty Tahr
	(my $txtRecord) = ($fileContent =~ /(".*")/);

	# Fix permissions on the TXT DNS record file

	$rs = $file->mode(0640);
	return $rs if $rs;

	$rs = $file->owner('opendkim', 'opendkim');
	return $rs if $rs;

	# Add the domain private key into the OpenDKIM KeyTable file

	$file = iMSCP::File->new('filename' => '/etc/opendkim/KeyTable');

	$fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read $file->{'filename'}");
		return 1;
	}

	$fileContent .= "mail._domainkey.$domain $domain:mail:/etc/opendkim/keys/$domain/mail.private\n";

	$rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	# Add the domain entry into the OpenDKIM SigningTable file

	$file = iMSCP::File->new('filename' => '/etc/opendkim/SigningTable');

	$fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read $file->{'filename'}");
		return 1;
	}

	$fileContent .= "*\@$domain mail._domainkey.$domain\n";

	$rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	# Add the TXT DNS record into the database
	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery(
		'domain_id',
		'
			INSERT INTO domain_dns (
				domain_id, alias_id, domain_dns, domain_class, domain_type, domain_text, owned_by
			) VALUES (
				?, ?, ?, ?, ?, ?, ?
			)
		',
		$domainId,
		$aliasId,
		'mail._domainkey 60',
		'IN',
		'TXT',
		$txtRecord,
		'OpenDKIM_Plugin'
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	0;
}

=item _deleteOpendkimDomainKey($domainId, $aliasId, $domain)

 Deletes domain key from OpenDKIM support

 Param int $domainId Domain unique identifier
 Param int $aliasId Domain alias unique identifier (0 if no domain alias)
 Return int 0 on success, other on failure

=cut

sub _deleteOpendkimDomainKey($$$$$)
{
	my ($self, $domainId, $aliasId, $domain) = @_;

	# Remove the directory which host the domain private key and the DNS TXT record files
	my $rs = iMSCP::Dir->new('dirname' => "/etc/opendkim/keys/$domain")->remove();
	return $rs if $rs;

	# Remove the domain private key from the OpenDKIM KeyTable file

	my $file = iMSCP::File->new('filename' => '/etc/opendkim/KeyTable');

	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read $file->{'filename'}");
		return 1;
	}

	$fileContent =~ s/.*$domain.*\n//g;

	$rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	# Remove the domain entry from the OpenDKIM SigningTable file if any

	$file = iMSCP::File->new('filename' => '/etc/opendkim/SigningTable');

	$fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read $file->{'filename'}");
		return 1;
	}

	$fileContent =~ s/.*$domain.*\n//g;

	$rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	my $db = iMSCP::Database->factory();

	# Remove the TXT DNS record from the database

	my $rdata = $db->doQuery(
		'dummy',
		'DELETE FROM domain_dns WHERE domain_id = ? AND alias_id = ? AND owned_by = ?',
		$domainId,
		$aliasId,
		'OpenDKIM_Plugin'
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	# Schedule domain change if needed
	#
	# Even if the OpenDKIM feature is being deactivated for a particular domain, we must not force rebuild of related
	# configuration file when the domain has a status other than 'ok'. For instance, a domain which is disbaled
	# should stay disabled. In such a case, the OpenDNS entry (TXT DNS resource record) will be removed when the
	# domain will be re-activated.

	if($aliasId eq '0') {
		$rdata = $db->doQuery(
			'dummy',
			'UPDATE domain SET domain_status = ? WHERE domain_id = ? AND domain_status = ?',
			'tochange',
			$domainId,
			'ok'
		);
	} else {
		$rdata = $db->doQuery(
			'dummy',
			'UPDATE domain_aliasses SET alias_status = ? WHERE alias_id = ? AND alias_status = ?',
			'tochange',
			$aliasId,
			'ok'
		);
	}

	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	0;
}

=item _addOpendkimDnsEntries()

 Add OpenDKIM DNS entries

 Return int 0 on success, other on failure

=cut

sub _addOpendkimDnsEntries
{
	my $self = $_[0];

	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery(
		'opendkim_id',
		'
			SELECT
				opendkim_id, domain_id, IFNULL(alias_id, 0) AS alias_id, domain_name
			FROM
				opendkim
			WHERE
				opendkim_status = ?
		',
		'ok'
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
					error("Unable to read $file->{'filename'}");
					return 1;
				}

				$fileContent =~ s/"\n(.*)"p=/ p=/sgm; # Fix for Ubuntu 14.04 Trusty Tahr
				(my $txtRecord) = ($fileContent =~ /(\".*\")/);

				# Add the DNS TXT record into the database
				my $rdata2 = $db->doQuery(
					'domain_id',
					'
						INSERT INTO domain_dns (
							domain_id, alias_id, domain_dns, domain_class, domain_type, domain_text, owned_by
						) VALUES (
							?, ?, ?, ?, ?, ?, ?
						)
					',
					$domainId,
					$aliasId,
					'mail._domainkey 60',
					'IN',
					'TXT',
					$txtRecord,
					'OpenDKIM_Plugin'
				);
				unless(ref $rdata2 eq 'HASH') {
					error($rdata2);
					return 1;
				}

				if($aliasId eq '0') {
					$rdata2 = $db->doQuery(
						'dummy',
						'UPDATE domain SET domain_status = ? WHERE domain_id = ? AND domain_status = ?',
						'tochange',
						$domainId,
						'ok'
					);
				} else {
					$rdata2 = $db->doQuery(
						'dummy',
						'UPDATE domain_aliasses SET alias_status = ? WHERE alias_id = ? AND alias_status = ?',
						'tochange',
						$aliasId,
						'ok'
					);
				}

				unless(ref $rdata2 eq 'HASH') {
					error($rdata2);
					return 1;
				}
			} else {
				error("OpenDKIM directory for the domain $rdata->{$_}->{'domain_name'} doesn't exist.");
			}
		}
	}

	0;
}

=item _removeOpendkimDnsEntries()
 
 Remove OpenDKIM DNS entries
 
 Return int 0 on success, other on failure
 
=cut

sub _removeOpendkimDnsEntries
{
	my $self = $_[0];

	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery('dummy', 'DELETE FROM domain_dns WHERE owned_by = ?', 'OpenDKIM_Plugin');
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	$rdata = $db->doQuery(
		'opendkim_id', 'SELECT opendkim_id, domain_id, IFNULL(alias_id, 0) AS alias_id FROM opendkim'
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	# Schedule domain change if needed
	#
	# Even if the OpenDKIM feature is being deactivated for a particular domain, we must not force rebuild of related
	# configuration file when the domain has a status other than 'ok'. For instance, a domain which is disbaled
	# should stay disabled. In such a case, the OpenDNS entry (TXT DNS resource record) will be removed when the
	# domain will be re-activated.

	if(%{$rdata}) {
		for(keys %{$rdata}) {
			my $domainId = $rdata->{$_}->{'domain_id'};
			my $aliasId = $rdata->{$_}->{'alias_id'};

			my $rdata2;
			
			if($aliasId eq '0') {
				$rdata2 = $db->doQuery(
					'dummy',
					'UPDATE domain SET domain_status = ? WHERE domain_id = ? AND domain_status = ?',
					'tochange',
					$domainId,
					'ok'
				);
			} else {
				$rdata2 = $db->doQuery(
					'dummy',
					'UPDATE domain_aliasses SET alias_status = ? WHERE alias_id = ? AND alias_status = ?',
					'tochange',
					$aliasId,
					'ok'
				);
			}

			unless(ref $rdata2 eq 'HASH') {
				error($rdata2);
				return 1;
			}
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
		error("Unable to read $file->{'filename'}");
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

	$file->save();
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
		error("Unable to read $file->{'filename'}");
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
	} elsif($action eq 'remove') {
		$fileContent =~ s/^\n# Begin Plugin::OpenDKIM.*Ending Plugin::OpenDKIM\n//sgm;
	}

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _modifyPostfixMainConfig($action)

 Modify Postfix main.cf config file

 Return int 0 on success, other on failure

=cut

sub _modifyPostfixMainConfig($$)
{
	my ($self, $action) = @_;

	my ($stdout, $stderr);
	my $rs = execute('postconf smtpd_milters non_smtpd_milters', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;
	
	my $openDkimMilterSocket = ($self->{'config'}->{'opendkim_port'} =~ /\d{4,5}/ && $self->{'config'}->{'opendkim_port'} <= 65535)
		? 'inet:localhost:' . $self->{'config'}->{'opendkim_port'}
		: 'inet:localhost:12345';

	# Extract postconf values
	s/^.*=\s*(.*)/$1/ for ( my @postconfValues = split "\n", $stdout );

	if($action eq 'add') {
		

		my @postconf = (
			# milter_default_action
			'milter_default_action=accept',

			# smtpd_milters
			($postconfValues[0] !~ /$openDkimMilterSocket/)
				? 'smtpd_milters=' . escapeShell("$postconfValues[0] $openDkimMilterSocket") : '',

			# non_smtpd_milters
			($postconfValues[1] !~ /\$smtpd_milters/)
				? 'non_smtpd_milters=' . escapeShell("$postconfValues[1] \$smtpd_milters") : ''
		);
		
		$rs = execute("postconf -e @postconf", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	} elsif($action eq 'remove') {
		$postconfValues[0] =~ s/\s*$openDkimMilterSocket//;
		$rs = execute('postconf -e smtpd_milters=' . escapeShell($postconfValues[0]), \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}
}

=item _createOpendkimFolder()

 Create OpenDKIM folder and corresponding files

 Return int 0 on success, other on failure

=cut

sub _createOpendkimFolder
{
	my $self = $_[0];

	my $rs = iMSCP::Dir->new('dirname' => '/etc/opendkim/keys')->make(
		{ 'user' => 'opendkim', 'group' => 'opendkim', 'mode' => 0750 }
	);
	return $rs if $rs;

	$rs = $self->_createOpendkimFile('KeyTable');
	return $rs if $rs;

	$rs = $self->_createOpendkimFile('SigningTable');
	return $rs if $rs;

	$self->_createOpendkimFile('TrustedHosts');
}

=item _createOpendkimFile($fileName)

 Create OpenDKIM files

 Return int 0 on success, other on failure

=cut

sub _createOpendkimFile($$)
{
	my ($self, $fileName) = @_;

	my $file = iMSCP::File->new('filename' => "/etc/opendkim/$fileName");

	my $rs = 0;

	if($fileName eq 'TrustedHosts') {
		my $fileContent = '';

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

	$file->owner('opendkim', 'opendkim');
}

=item _restartDaemonOpendkim()

 Restart the OpenDKIM daemon

 Return int 0 on success, other on failure

=cut

sub _restartDaemonOpendkim
{
	my ($stdout, $stderr);
	my $rs = execute("$main::imscpConfig{'SERVICE_MNGR'} opendkim restart", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs;
}

=item _restartDaemonPostfix()

 Restart the postfix daemon

 Return int 0

=cut

sub _restartDaemonPostfix
{
	require Servers::mta;
	Servers::mta->factory()->{'restart'} = 'yes';

	0;
}

=back

=head1 AUTHORS

 Sascha Bay <info@space2place.de>
 Rene Schuster <mail@reneschuster.de>

=cut

1;
