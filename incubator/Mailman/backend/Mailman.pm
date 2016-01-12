=head1 NAME

 Plugin::Mailman - i-MSCP Mailman plugin (backend side)

=cut

# i-MSCP Mailman plugin
# Copyright (C) 2013-2016 Laurent Declercq <l.declercq@nuxwin.com>
#
# This library is free software; you can redistribute it and/or
# modify it under the terms of the GNU Lesser General Public
# License as published by the Free Software Foundation; either
# version 2.1 of the License, or (at your option) any later version.
#
# This library is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
# Lesser General Public License for more details.
#
# You should have received a copy of the GNU Lesser General Public
# License along with this library; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA

package Plugin::Mailman;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::Execute;
use iMSCP::TemplateParser;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Net;
use Servers::httpd;
use Servers::mta;
use File::Temp;

use parent 'Common::SingletonClass';


use POSIX qw/strftime/;

my $enabledListsDir = '/var/lib/mailman/lists';
my $disabledListsDir = '/var/cache/imscp/mailman/disabled.lists';

=head1 DESCRIPTION

 This package provides the backend side of the i-MSCP Mailman plugin.

=head1 PUBLIC METHODS

=over 4

=item install()

 Install mailman plugin

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = $_[0];

	# Check for requirements
	my $rs = _checkRequirements();
	return $rs if $rs;

	# Update mailman configuration file
	if(-f '/etc/mailman/mm_cfg.py') {
		my $file = iMSCP::File->new( filename => '/etc/mailman/mm_cfg.py' );

		unless(-f '/etc/mailman/mm_cfg.py.dist') {
		
			$rs = execute("cp -fR /etc/mailman/mm_cfg.py /etc/mailman/mm_cfg.py.dist", \my $stdout, \my $stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		}

		my $fileContent = $file->get();
		return 1 unless defined $fileContent;

		$fileContent =~ s#^(DEFAULT_URL_PATTERN\s*=\s*).*$#$1'http://%s/'#gm;
		$fileContent =~ s/^#\s*(MTA\s*=\s*None)/$1/im;
		$fileContent =~ s#^(DEFAULT_SERVER_LANGUAGE\s*=\s*).*$#$1'$self->{'config'}->{'DEFAULT_LANGUAGE'}'#gm;

		$rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;
	} else {
		error('File /etc/mailman/mm_cfg.py not found');
		return 1;
	}

	# Create directory for disabled lists or set it permissions if it already exist
	$rs = iMSCP::Dir->new( dirname => $disabledListsDir )->make(
		'user' => $main::imscpConfig{'ROOT_USER'},
		'group' => $main::imscpConfig{'ROOT_GROUP'},
		'mode' => '0750'
	);
	return $rs if $rs;

	# Add mailman configuration parameter in Postfix main.cf file

	my $mta = Servers::mta->factory();

	my $file = iMSCP::File->new( filename => "$mta->{'wrkDir'}/main.cf" );
	my $fileContent = $file->get();
	unless(defined $fileContent) {
		error("Unable to read $mta->{'wrkDir'}/main.cf");
		return 1;
	}

	my $beginTag = "# Mailman plugin - Begin";
	my $endingTag = "# Mailman plugin - Ending";

	if(getBloc("$beginTag\n", "$endingTag\n", $fileContent) ne '') {
		$fileContent = replaceBloc(
			"$beginTag\n",
			"$endingTag\n",
			"$beginTag\nmailman_destination_recipient_limit = 1\n$endingTag\n",
			$fileContent
		);
	} else {
		$fileContent .= <<EOF;

$beginTag
mailman_destination_recipient_limit = 1
$endingTag
EOF
	}

	$rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	# Install postfix main.cf file in production directory
	$rs = execute("cp -fR $mta->{'wrkDir'}/main.cf $mta->{'config'}->{'POSTFIX_CONF_FILE'}", \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Schedule Postfix restart
	$mta->{'restart'} = 1;

	0;
}

=item change()

 Change mailman plugin

 Return int 0 on success, other on failure

=cut

sub change
{
	my $self = $_[0];

	my $rs = $self->install();
	return $rs if $rs;

	# Schedule change of any mailing list
	# We are using the 'toadd' status because the 'tochange' status is used for email and password update only.
	$rs = $self->{'db'}->doQuery(
		'dummy', 'UPDATE mailman SET mailman_status = ? WHERE mailman_status = ?', 'toadd', 'ok'
	);
	unless(ref $rs eq 'HASH') {
		error($rs);
		return 1;
	}

	$self->run();
}

=item uninstall()

 Uninstall mailman plugin

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = $_[0];

	my $db = $self->{'db'};

	# Schedule deletion of any mailing list
	my $rs = $db->doQuery('dummy', 'UPDATE mailman SET mailman_status = ?', 'todelete');
	unless(ref $rs eq 'HASH') {
		error($rs);
		return 1;
	}

	# Process mailing lists deletion
	$rs = $self->run();
	return $rs if $rs;

	# Remove mailman configuration parameter from Postfix main.cf file

	my $mta = Servers::mta->factory();

	my $file = iMSCP::File->new( filename => "$mta->{'wrkDir'}/main.cf" );
	my $fileContent = $file->get();
	unless(defined $fileContent) {
		error("Unable to read $mta->{'wrkDir'}/main.cf");
		return 1;
	}

	my $beginTag = "# Mailman plugin - Begin";
	my $endingTag = "# Mailman plugin - Ending";

	$fileContent = replaceBloc("\n$beginTag\n", "$endingTag\n", '', $fileContent);

	$rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	# Install postfix main.cf file in production directory
	$rs = execute("cp -fR $mta->{'wrkDir'}/main.cf $mta->{'config'}->{'POSTFIX_CONF_FILE'}", \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Schedule Postfix restart
	$mta->{'restart'} = 1;

	# Delete cache dir

	$rs = iMSCP::Dir->new( dirname => '/var/cache/imscp/mailman' )->remove();
	return $rs if $rs;

	# Restore original /etc/mailman/mm_cfg.py file if any
	if( -f '/etc/mailman/mm_cfg.py.dist') {
	
		$rs = execute("mv /etc/mailman/mm_cfg.py.dist /etc/mailman/mm_cfg.py}", \my $stdout, \my $stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	0;
}

=item enable()

 Enable mailman plugin

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = $_[0];

	if($self->{'action'} eq 'enable') {
		my $rs = $self->{'db'}->doQuery(
			'dummy', 'UPDATE mailman SET mailman_status = ? WHERE mailman_status = ?', 'toenable', 'disabled'
		);
		unless(ref $rs eq 'HASH') {
			error($rs);
			return 1;
		}

		$rs = $self->run();
		return $rs if $rs;
	}

	0;
}

=item disable()

 Disable mailman plugin

 Return int 0 on success, other on failure

=cut

sub disable
{
	my $self = $_[0];

	if($self->{'action'} eq 'disable') {
		my $rs = $self->{'db'}->doQuery(
			'dummy', 'UPDATE mailman SET mailman_status = ? WHERE mailman_status = ?', 'todisable', 'ok'
		);
		unless(ref $rs eq 'HASH') {
			error($rs);
			return 1;
		}

		$rs = $self->run();
		return $rs if $rs;
	}

	0;
}

=item run()

 Run all scheduled actions according status of mailing lists

 Return int 0 on success, other on failure

=cut

sub run
{
	my $self = $_[0];

	my $db = $self->{'db'};
	my $rs = 0;

	# Get all mailing list data
	my $rows = $db->doQuery(
		'mailman_id',
		"
			SELECT
				t1.*, t2.domain_name, t2.domain_id, t3.ip_number AS ip_addr
			FROM
				mailman AS t1
			INNER JOIN
				domain AS t2 ON (t2.domain_admin_id = t1.mailman_admin_id)
			INNER JOIN
				server_ips AS t3 on(t3.ip_id = t2.domain_ip_id)
			WHERE
				mailman_status IN('toadd', 'tochange', 'todelete', 'toenable', 'todisable')
		"
	);
	unless(ref $rows eq 'HASH') {
		$self->{'FORCE_RETVAL'} = 'yes';
		error($rows);
		return 1;
	}

	# Process action acording list status
	if(%{$rows}) {
		for my $row (values %{$rows}) {
			my $status = $row->{'mailman_status'};
			my @sql;

			if($status eq 'toadd') {
				$rs = $self->_addList($row);
				@sql = (
					'UPDATE mailman SET mailman_status = ? WHERE mailman_id = ?',
					($rs ? scalar getMessageByType('error') || 'Unknown error' : 'ok'), $row->{'mailman_id'}
				);
			} elsif($status eq 'tochange') {
				$rs = $self->_updateList($row);
				@sql = (
					'UPDATE mailman SET mailman_status = ? WHERE mailman_id = ?',
					($rs ? scalar getMessageByType('error') || 'Unknown error' : 'ok'), $row->{'mailman_id'}
				);
			} elsif($status eq 'toenable') {
				$rs = $self->_enableList($row);
				@sql = (
					'UPDATE mailman SET mailman_status = ? WHERE mailman_id = ?',
					($rs ? scalar getMessageByType('error') || 'Unknown error' : 'ok'), $row->{'mailman_id'}
				);
			} elsif($status eq 'todisable') {
				$rs = $self->_disableList($row);
				@sql = (
					'UPDATE mailman SET mailman_status = ? WHERE mailman_id = ?',
					($rs ? scalar getMessageByType('error') || 'Unknown error' : 'disabled'), $row->{'mailman_id'}
				);
			} elsif($status eq 'todelete') {
				$rs = $self->_deleteList($row);

				if($rs) {
					@sql = (
						'UPDATE mailman SET mailman_status = ? WHERE mailman_id = ?',
						scalar getMessageByType('error') || 'Unknown error', $row->{'mailman_id'}
					);
				} else {
					@sql = ('DELETE FROM mailman WHERE mailman_id = ?', $row->{'mailman_id'});
				}
			}

			my $qrs = $db->doQuery('dummy', @sql);
			unless(ref $qrs eq 'HASH') {
				$self->{'FORCE_RETVAL'} = 'yes';
				error($qrs);
				return 1;
			}
		}
	}

	$rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Plugin::Mailman

=cut

sub _init
{
	my $self = $_[0];

	$self->{'db'} = iMSCP::Database->factory();

	$self;
}

=item _addList(\%data)

 Add a mailing list

 Param hash \%data Mailing list data
 Return int 0 on success, other on failure

=cut

sub _addList
{
	my ($self, $data) = @_;

	unless($self->_listExists($data)) {
		my @cmdArgs = (
			'-q',
			'-u', escapeShell("lists.$data->{'domain_name'}"),
			'-e', escapeShell($data->{'domain_name'}),
			escapeShell($data->{'mailman_list_name'}),
			escapeShell($data->{'mailman_admin_email'}),
			escapeShell($data->{'mailman_admin_password'})
		);

		my($stdout, $stderr);
		my $rs = execute("/usr/lib/mailman/bin/newlist @cmdArgs", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	### MTA entries - Begin

	## Add transport entries - Begin

	my $mta = Servers::mta->factory();

	my @entries = (
		'', '-admin', '-bounces', '-confirm', '-join', '-leave', '-owner', '-request', '-subscribe', '-unsubscribe'
	);

	# Backup current working transport table if any
	if(-f "$mta->{'wrkDir'}/transport") {
		my $rs = iMSCP::File->new(
			filename => "$mta->{'wrkDir'}/transport"
		)->copyFile(
			"$mta->{'bkpDir'}/transport." . time
		);
		return $rs if $rs;
	}

	my $file = iMSCP::File->new( filename => "$mta->{'wrkDir'}/transport" );
	my $fileContent = $file->get();
	unless(defined $fileContent) {
		error("Unable to read $mta->{'wrkDir'}/transport file");
		return 1;
	}

	for(@entries) {
		my $entry = "$data->{'mailman_list_name'}$_\@$data->{'domain_name'}\tmailman:\n";
		$fileContent .= $entry unless $fileContent =~ /^$entry/gm;
	}

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	# Install transport table in production directory
	$rs = $file->copyFile($mta->{'config'}->{'MTA_TRANSPORT_HASH'});
	return $rs if $rs;

	# Schedule postmap of transport table
	$mta->{'postmap'}->{$mta->{'config'}->{'MTA_TRANSPORT_HASH'}} = 'mailman_plugin';

	## Add transport entries - Ending

	## Add mailboxes entries - Begin

	# Backup current wokring mailboxes table if any
	if(-f "$mta->{'wrkDir'}/mailboxes") {
		my $rs = iMSCP::File->new(
			filename => "$mta->{'wrkDir'}/mailboxes"
		)->copyFile(
			"$mta->{'bkpDir'}/transport." . time
		);
		return $rs if $rs;
	}

	$file = iMSCP::File->new( filename => "$mta->{'wrkDir'}/mailboxes" );

	$fileContent = $file->get();
	unless(defined $fileContent){
		error("Unable to read $mta->{'wrkDir'}/mailboxes");
		return 1;
	}

	for(@entries) {
		my $entry = "$data->{'mailman_list_name'}$_\@$data->{'domain_name'}\t/dev/null\n";
		$fileContent .= $entry unless $fileContent =~ /^$entry/gm;
	}

	$rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	# Install mailboxes table in production directory
	$rs = $file->copyFile($mta->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'});
	return $rs if $rs;

	# Schedule postmap of mailboxes table
	$mta->{'postmap'}->{$mta->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'}} = 'mailman_plugin';

	## Add mailboxes entries - Ending

	### MTA entries - Ending

	# Add vhost
	$rs = $self->_addListsVhost($data);
	return $rs if $rs;

	# Add DNS resource record
	if($main::imscpConfig{'NAMED_SERVER'} eq 'bind') {
		$rs = $self->_addListsDnsRecord($data);
		return $rs if $rs;
	}

	0;
}

=item _updateList(\%data)

 Update a mailing list (admin email and password)

 Param hash \%data Mailing list data
 Return int 0 on success, other on failure

=cut

sub _updateList
{
	my ($self, $data) = @_;

	# Update admin email

	my $tmpFile = File::Temp->new();

	print $tmpFile "owner = ['$data->{'mailman_admin_email'}']\nhostname = ['$data->{'domain_name'}']\n";

	my @cmdArgs = ('-i', escapeShell($tmpFile), escapeShell($data->{'mailman_list_name'}));

	my ($stdout, $stderr);
	my $rs = execute("/usr/lib/mailman/bin/config_list @cmdArgs", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Update admin password

	@cmdArgs = (
		'-q',
		'-l', escapeShell($data->{'mailman_list_name'}),
		'-p', escapeShell($data->{'mailman_admin_password'})
	);

	$rs = execute("/var/lib/mailman/bin/change_pw @cmdArgs", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs;
}

=item _enableList(\%data)

 Enable a mailing list

 Param hash \%data Mailing list data
 Return int 0 on success, other on failure

=cut

sub _enableList
{
	my ($self, $data) = @_;

	if(-d "$disabledListsDir/$data->{'mailman_list_name'}") {
		my $rs = iMSCP::Dir->new(
			dirname => "$disabledListsDir/$data->{'mailman_list_name'}"
		)->moveDir(
			"$enabledListsDir/$data->{'mailman_list_name'}"
		);
		return $rs if $rs;

		$rs = $self->_addListsVhost($data);
		return $rs if $rs;

		if($main::imscpConfig{'NAMED_SERVER'} eq 'bind') {
			$rs = $self->_addListsDnsRecord($data);
			return $rs if $rs;
		}
	}

	0;
}

=item _disableList(\%data)

 Disable a mailing list

 Param hash $data Mailing list data
 Return int 0 on success, other on failure

=cut

sub _disableList
{
	my ($self, $data) = @_;

	if(-d "$enabledListsDir/$data->{'mailman_list_name'}") {
		my $rs = iMSCP::Dir->new(
			dirname => "$enabledListsDir/$data->{'mailman_list_name'}"
		)->moveDir(
			"$disabledListsDir/$data->{'mailman_list_name'}"
		);
		return $rs if $rs;
	}

	my $rs = $self->_deleteListsVhost($data);
	return $rs if $rs;

	if($main::imscpConfig{'NAMED_SERVER'} eq 'bind') {
		$rs = $self->_deleteListsDnsRecord($data);
		return $rs;
	}

	0;
}

=item _deleteList(\%data)

 Delete a mailing list

 Param hash $data Mailing list data
 Return int 0 on success, other on failure

=cut

sub _deleteList
{
	my ($self, $data) = @_;

	my @cmdArgs = ('-a', escapeShell($data->{'mailman_list_name'}));
	my ($stdout, $stderr);
	my $rs = execute("/usr/lib/mailman/bin/rmlist @cmdArgs", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	### MTA entries - Begin

	my $mta = Servers::mta->factory();

	my @entries = (
		'', '-admin', '-bounces', '-confirm', '-join', '-leave', '-owner', '-request', '-subscribe', '-unsubscribe'
	);

	## Delete transport entries - Begin

	# Backup current working transport table if any
	if (-f "$mta->{'wrkDir'}/transport") {
		my $rs = iMSCP::File->new(
			filename => "$mta->{'wrkDir'}/transport"
		)->copyFile(
			"$mta->{'bkpDir'}/transport." . time
		);
		return $rs if $rs;
	}

	my $file = iMSCP::File->new( filename => "$mta->{'wrkDir'}/transport" );
	my $fileContent = $file->get();
	unless(defined $fileContent){
		error("Unable to read $mta->{'wrkDir'}/transport");
		return 1;
	}

	for(@entries) {
		my $entry = "$data->{'mailman_list_name'}$_\@$data->{'domain_name'}\tmailman:\n";
		$fileContent =~ s/^$entry//gm;
	}

	$rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	# Install transport table in production directory
	$rs = $file->copyFile($mta->{'config'}->{'MTA_TRANSPORT_HASH'});
	return $rs if $rs;

	# Schedule postmap of transport table
	$mta->{'postmap'}->{$mta->{'config'}->{'MTA_TRANSPORT_HASH'}} = 'mailman_plugin';

	## Delete transport entries - Ending

	## Delete mailboxes entries - Begin

	$rs = iMSCP::File->new(
		filename => $mta->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'}
	)->copyFile(
		"$mta->{'bkpDir'}/transport." . time
	);
	return $rs if $rs;

	$file = iMSCP::File->new( filename => "$mta->{'wrkDir'}/mailboxes");
	$fileContent = $file->get();
	unless(defined $fileContent){
		error("Unable to read $mta->{'wrkDir'}/mailboxes");
		return 1;
	}

	for(@entries) {
		my $entry = "$data->{'mailman_list_name'}$_\@$data->{'domain_name'}\t/dev/null\n";
		$fileContent =~ s/^$entry//gm;
	}

	$rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	# Install mailboxes table in production directory
	$rs = $file->copyFile($mta->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'} );
	return $rs if $rs;

	# Schedule postmap of mailboxes table
	$mta->{'postmap'}->{$mta->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'}} = 'mailman_plugin';

	## Delete mailboxes entries - Ending

	### MTA entries - Ending

	# Delete the lists vhost and DNS resource record

	$rs = $self->_deleteListsVhost($data);
	return $rs if $rs;

	if($main::imscpConfig{'NAMED_SERVER'} eq 'bind') {
		$rs = $self->_deleteListsDnsRecord($data);
		return $rs if $rs;
	}

	0;
}

=item _addListsVhost(\%data)

 Add vhost for mailing list

 Param hash $data Mailing list data
 Return int 0 on success, other on failure

=cut

sub _addListsVhost
{
	my ($self, $data) = @_;

	my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
		($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $data->{'mailman_admin_id'});

	my $ipAddr = $data->{'ip_addr'};
	my $ipMngr = iMSCP::Net->getInstance();

	my $vhost = iMSCP::TemplateParser::process(
		{
			'DOMAIN_IP_ADDR' => ($ipMngr->getAddrVersion($ipAddr) eq 'ipv4') ? $ipAddr : '[' . $ipAddr . ']',
			'DOMAIN_NAME' => $data->{'domain_name'},
			'USER' => $userName
		},
		$self->_getListVhostTemplate()
	);
	unless(defined $vhost) {
		error('Unable to generate" mailing list vhost');
		return 1;
	}

	my $httpd = Servers::httpd->factory();

	my $file = iMSCP::File->new(
		filename => "$httpd->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/lists.$data->{'domain_name'}.conf"
	);

	my $rs = $file->set($vhost);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	$rs = $httpd->enableSites("lists.$data->{'domain_name'}.conf");
	return $rs if $rs;

	# Schedule Apache restart (reload)
	$httpd->{'restart'} = 1;

	0;
}

=item _deleteListsVhost(\%data)

 Delete mailing list vhost

 Param hash $data Mailing list data
 Return int 0 on success, other on failure

=cut

sub _deleteListsVhost
{
	my ($self, $data) = @_;

	my $httpd = Servers::httpd->factory();

	my $vhostFilePath = "$httpd->{'config'}->{'HTTPD_SITES_AVAILABLE_DIR'}/lists.$data->{'domain_name'}.conf";

	if(-f $vhostFilePath) {
		my $rs = $httpd->disableSites("lists.$data->{'domain_name'}.conf");
		return $rs if $rs;

		my $file = iMSCP::File->new( filename => $vhostFilePath );
		$rs = $file->delFile();
		return $rs if $rs;

		# Schedule Apache restart (reload)
		$httpd->{'restart'} = 1;
	}

	0;
}

=item _addListsDnsRecord(\%data)

 Add DNS resource record for mailing list

 Param hash $data Mailing list data
 Return int 0 on success, other on failure

=cut

sub _addListsDnsRecord
{
	my ($self, $data) = @_;

	if($main::imscpConfig{'NAMED_SERVER'} eq 'bind') {
		my $rs = $self->_deleteListsDnsRecord($data);
		return $rs if $rs;
	}

	my $ipAddr = $data->{'ip_addr'};
	my $ipMngr = iMSCP::Net->getInstance();

	if($ipMngr->can('getAddrType')) {
		$ipAddr = ($ipMngr->getAddrType($data->{'ip_addr'}) eq 'PUBLIC')
			? $data->{'ip_addr'} : $main::imscpConfig{'BASE_SERVER_PUBLIC_IP'};
	}

	my $rs = $self->{'db'}->doQuery(
		'dummy',
		'
			INSERT INTO domain_dns (
				domain_id, alias_id, domain_dns, domain_class, domain_type, domain_text, owned_by
			) VALUES(
				?, ?, ?, ?, ?, ?, ?
			)
		',
		$data->{'domain_id'},
		'0',
		"lists.$data->{'domain_name'}.",
		'IN',
		($ipMngr->getAddrVersion($ipAddr) eq 'ipv4') ? 'A' : 'AAAA',
		$ipAddr,
		'plugin_mailman'
	);
	unless(ref $rs eq 'HASH') {
		error($rs);
		return 1;
	}

	0;
}

=item = _deleteListsDnsRecord(\%data)

 Delete mailing list DNS resource record

 Param hash $data Mailing list data
 Return int 0 on success, other on failure

=cut

sub _deleteListsDnsRecord
{
	my ($self, $data) = @_;

	my $db = $self->{'db'};
	my $rawDb = $db->startTransaction();

	eval {
		$rawDb->do(
			'DELETE FROM domain_dns WHERE domain_id = ? AND owned_by = ?', undef, $data->{'domain_id'}, 'plugin_mailman'
		);

		$rawDb->do(
			'UPDATE domain SET domain_status = ? WHERE domain_id = ? AND domain_status = ?',
			undef,
			'tochange',
			$data->{'domain_id'},
			'ok'
		);

		$rawDb->commit();
	};

	if($@) {
		$rawDb->rollback();
		$db->endTransaction();
		error($@);
		return 1;
	}

	$db->endTransaction();

	0;
}

=item _getListsVhostTemplate()

 Return vhost template for mailing list

 Return string String representing lists vhost template

=cut

sub _getListVhostTemplate
{
	<<EOF;
<VirtualHost {DOMAIN_IP_ADDR}:80>
    ServerAdmin webmaster\@{DOMAIN_NAME}
    ServerName lists.{DOMAIN_NAME}

    RedirectMatch /[/]*\$ http://lists.{DOMAIN_NAME}/listinfo

    <IfModule mod_cband.c>
        CBandUser {USER}
    </IfModule>

    Include /etc/mailman/apache.conf
    ScriptAlias / /usr/lib/cgi-bin/mailman/

    # Disable mailman web-based list creation and destruction... i-MSCP manages this!
    Redirect /mailman/create /mailman/admin
    Redirect /mailman/rmlist /mailman/admin
    Redirect /mailman/edithtml /mailman/admin
    Redirect /create /admin
    Redirect /rmlist /admin
    Redirect /edithtml /admin
    Redirect /cgi-bin/mailman/create /cgi-bin/mailman/admin
    Redirect /cgi-bin/mailman/rmlist /cgi-bin/mailman/admin
    Redirect /cgi-bin/mailman/edithtml /cgi-bin/mailman/edithtml
</VirtualHost>
EOF
}

=item _listExists(\%data)

 Whether or not the given mailing list exists

 Return int 1 if the given mailing exists, 0 otherwise

=cut

sub _listExists
{
	my ($self, $data) = @_;

	my $listName = lc($data->{'mailman_list_name'});

	my @cmdArgs = (escapeShell("^$listName\$"));

	(execute("/usr/lib/mailman/bin/list_lists -b | grep -q @cmdArgs")) ? 0 : 1;
}

=item _checkRequirements

 Check requirements

 Return int 0 if all requirements are meet, other otherwise

=cut

sub _checkRequirements
{
	my $self = $_[0];

	my ($stdout, $stderr);
	my $rs = execute(
		"LANG=C dpkg-query --show --showformat '\${Status}' mailman | cut -d ' ' -f 3", \$stdout, \$stderr
	);
	debug($stdout) if $stdout;
	if($stdout ne 'installed') {
		error("The mailman package is not installed on your system");
		return $rs;
	}

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
