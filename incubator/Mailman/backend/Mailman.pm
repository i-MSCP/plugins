#!/usr/bin/perl

=head1 NAME

 Plugin::Mailman - i-MSCP Mailman plugin (backend)

=cut

# i-MSCP Mailman plugin
# Copyright (C) 2013 - 2014 Laurent Declercq <l.declercq@nuxwin.com>
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
use iMSCP::HooksManager;
use iMSCP::Database;
use iMSCP::Execute;
use iMSCP::TemplateParser;
use iMSCP::Dir;
use iMSCP::File;

use Servers::httpd;
use Servers::mta;

use File::Temp;

use parent 'Common::SingletonClass';

my $enabledListsDir = '/var/lib/mailman/lists';
my $disabledListsDir = '/var/cache/imscp/mailman/suspended.lists';

=head1 DESCRIPTION

 This package provides backend part for the i-MSCP Mailman plugin.

=head1 PUBLIC METHODS

=over 4

=item install()

 Install mailman plugin

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = $_[0];

	# Check plugin requirements
	my $rs = _checkRequirements();
	return $rs if $rs;

	# Update mailman configuration file
	if(-f '/etc/mailman/mm_cfg.py') {
		my $file = iMSCP::File->new('filename' => '/etc/mailman/mm_cfg.py');

		if(! -f '/etc/mailman/mm_cfg.py.dist') {
			$rs = $file->copyFile('/etc/mailman/mm_cfg.py.dist');
			return $rs if $rs;
		}

		my $fileContent = $file->get();
		return 1 unless defined $fileContent;

		$fileContent =~ s#^(DEFAULT_URL_PATTERN\s*=\s*).*$#$1'http://%s/'#gm;
		$fileContent =~ s/^#\s*(MTA\s*=\s*None)/$1/im;

		$rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;
	} else {
		error('File /etc/mailman/mm_cfg.py not found');
		return 1;
	}

	# Create directory for disabled lists
	my $dir = iMSCP::Dir->new('dirname', $disabledListsDir);
	$rs = $dir->make(
		'user' => $main::imscpConfig{'ROOT_USER'},
		'group' => $main::imscpConfig{'ROOT_GROUP'},
		'mode' => '0640'
	);
	return $rs if $rs;

	# Add Postfix configuration in main.cf working file

	my $mta = Servers::mta->factory();

	my ($stdout, $stderr);
	$rs = execute(
		"$mta->{'config'}->{'CMD_POSTCONF'} -c $mta->{'wrkDir'} -e mailman_destination_recipient_limit=1",
		\$stdout, \$stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Install postfix main.cf file in production directory
	$rs = iMSCP::File->new('filename' => "$mta->{'wrkDir'}/main.cf")->copyFile($mta->{'config'}->{'POSTFIX_CONF_FILE'});
	return $rs if $rs;

	# Schedule Postfix restart
	$mta->{'restart'} = 'yes';

	0;
}

=item change()

 Update mailman plugin

 TODO on change, every DNS record should be updated to ensure that the IP still valid (in case of IP update)

 Return int 0 on success, other on failure

=cut

sub change
{
	my $self = $_[0];

	my $rs = $self->install();
	return $rs if $rs;

	$rs = iMSCP::Database->factory()->doQuery(
		'dummy', 'UPDATE mailman SET mailman_status = ? WHERE mailman_status = ?',' tochange', 'ok'
	);
	unless(ref $rs eq 'HASH') {
		error($rs);
		return 1;
	}

	$self->run();
}

=item update()

 Update mailman plugin

 Return int 0 on success, other on failure

=cut

sub update
{
	$_[0]->change();
}

=item uninstall()

 Uninstall mailman plugin

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = $_[0];

	my $db = iMSCP::Database->factory();

	my $rs = $db->doQuery('dummy', "UPDATE `mailman` SET `mailman_status` = 'todelete'");
	unless(ref $rs eq 'HASH') {
		error($rs);
		return 1;
	}

	$rs = $self->run();
	return $rs if $rs;

	# TODO Remove mailman conf from main.cf file

	#my $dir = '/var/cache/imscp/mailman/suspended.lists';

	#my $rs = iMSCP::Dir->new('dirname' => $dir) if -d $dir;
	#return $rs if $rs;

	# Restore original /etc/mailman/mm_cfg.py file
	if( -f '/etc/mailman/mm_cfg.py.dist') {
		$rs = iMSCP::File->new(
			'filename' => '/etc/mailman/mm_cfg.py.dist'
		)->copyFile(
			'/etc/mailman/mm_cfg.py.'
		);
		return $rs if $rs;
	}

	# Drop mailman table
	$db->doQuery('dummy', 'DROP TABLE IF EXISTS `mailman`');

	0;
}

=item enable()

 Enable mailman plugin

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = shift;

	#	try {
	#		exec_query('UPDATE `mailman` SET `mailman_status` = ?', $cfg->ITEM_TOENABLE_STATUS);
	#	} catch(iMSCP_Exception_Database $e) {
	#		throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
	#	}

	#$self->run();

	0;
}

=item disable()

 Disable mailman plugin

 Return int 0 on success, other on failure

=cut

sub disable
{
	my $self = shift;

		#try {
		#	exec_query(
		#		'UPDATE `mailman` SET `mailman_status` = ? WHERE `mailman_status` = ?',
		#		array($cfg->ITEM_TODISABLE_STATUS, $cfg->ITEM_OK_STATUS)
		#	);
		#} catch(iMSCP_Exception_Database $e) {
		#	throw new iMSCP_Plugin_Exception($e->getMessage(), $e->getCode(), $e);
		#}

	#$self->run();

	0;
}

#sub run
#{
#	my $self = shift;
#
#	# We are delaying the job
#	iMSCP::HooksManager->getInstance()->register('afterDispatchRequest', sub { $self->_run() });
#}

=item run()

 Run all scheduled actions according lists status

 Return int 0 on success, other on failure

=cut

sub run
{
	my $self = shift;

	my $db = iMSCP::Database->factory();
	my $rs = 0;

	# Get all mailing list data
	my $listData = $db->doQuery(
		'mailman_id',
		"
			SELECT
				t1.*, t`.domain_name, t2.domain_id
			FROM
				mailman AS t1
			INNER JOIN
				domain AS t2 ON (t2.domain_admin_id = t1.mailman_admin_id)
			WHERE
				mailman_status IN('toadd', 'tochange', 'todelete', 'toenable', 'todisable')
		"
	);
	unless(ref $listData eq 'HASH') {
		error($listData);
		return 1;
	}

	# Process action acording mailing list status
	if(%{$listData}) {
		for my $data(values %{$listData}) {
			my $status = $data->{'mailman_status'};
			my @sql;

			if($status eq 'toadd') {
				$rs = $self->_addList($data);
				@sql = (
					'UPDATE mailman SET mailman_status = ? WHERE mailman_id = ?',
					($rs ? scalar getMessageByType('error') || 'Unknown error' : 'ok'), $data->{'mailman_id'}
				);
			} elsif($status eq 'tochange') {
				$rs = $self->_updateList($data);
				@sql = (
					'UPDATE mailman SET mailman_status = ? WHERE mailman_id = ?',
					($rs ? scalar getMessageByType('error') || 'Unknown error' : 'ok'), $data->{'mailman_id'}
				);
			} elsif($status eq 'toenable') {
				$rs = $self->_enableList($data);
				@sql = (
					'UPDATE mailman SET mailman_status = ? WHERE mailman_id = ?',
					($rs ? scalar getMessageByType('error') || 'Unknown error' : 'ok'), $data->{'mailman_id'}
				);
			} elsif($status eq 'todisable') {
				$rs = $self->_disableList($data);
				@sql = (
					'UPDATE mailman SET mailman_status = ? WHERE mailman_id = ?',
					($rs ? scalar getMessageByType('error') || 'Unknown error' : 'disabled'), $data->{'mailman_id'}
				);
			} elsif($status eq 'todelete') {
				$rs = $self->_deleteList($data);

				if($rs) {
					@sql = (
						'UPDATE mailman SET mailman_status = ? WHERE mailman_id = ?',
						scalar getMessageByType('error') || 'Unknown error', $data->{'mailman_id'}
					);
				} else {
					@sql = ('DELETE FROM mailman WHERE mailman_id = ?', $data->{'mailman_id'});
				}
			}

			my $rdata = $db->doQuery('dummy', @sql);
			unless(ref $rdata eq 'HASH') {
				error($rdata);
				return 1;
			}
		}
	}

	$rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _addList(\%data)

 Add list

 Return int 0 on success, other on failure

=cut

sub _addList($$)
{
	my ($self, $data) = @_;

	my ($rs, $stdout, $stderr);

	if(!$self->_listExists($data)) {
		my @cmdArgs = (
			'-q',
			'-u', escapeShell("lists.$data->{'domain_name'}"),
			'-e', escapeShell($data->{'domain_name'}),
			escapeShell($data->{'mailman_list_name'}),
			escapeShell($data->{'mailman_admin_email'}),
			escapeShell($data->{'mailman_admin_password'})
		);

		$rs = execute("/usr/lib/mailman/bin/newlist @cmdArgs", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	# Add transport entries - Begin

	my $mta = Servers::mta->factory();

	my @entries = (
		'', '-admin', '-bounces', '-confirm', '-join', '-leave', '-owner', '-request', '-subscribe', '-unsubscribe'
	);

	# Backup current working transport table if any
	$rs = iMSCP::File->new(
		'filename' => "$mta->{'wrkDir'}/transport"
	)->copyFile(
		"$mta->{'bkpDir'}/transport." . time
	) if -f "$mta->{'wrkDir'}/transport";
	return $rs if $rs;

	my $file = iMSCP::File->new('filename' => "$mta->{'wrkDir'}/transport");
	my $content = $file->get();

	if(! defined $content) {
		error("Unable to read $mta->{'wrkDir'}/transport file");
		return 1;
	}

	for(@entries) {
		my $entry = "$data->{'mailman_list_name'}$_\@$data->{'domain_name'}\tmailman:\n";
		$content .= $entry unless $content =~ /^$entry/gm;
	}

	$rs = $file->set($content);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	# Install transport table in production directory
	$rs = $file->copyFile($mta->{'config'}->{'MTA_TRANSPORT_HASH'});
	return $rs if $rs;

	# Add transport entries - Ending

	# Add mailboxes entries - Begin

	# Backup current wokring mailboxes table if any
	$rs = iMSCP::File->new(
		'filename' => "$mta->{'wrkDir'}/mailboxes"
	)->copyFile(
		"$mta->{'bkpDir'}/transport." . time
	) if -f "$mta->{'wrkDir'}/mailboxes";
	return $rs if $rs;

	$file = iMSCP::File->new('filename' => "$mta->{'wrkDir'}/mailboxes");
	$content = $file->get();

	if(! defined $content){
		error("Unable to read $mta->{'wrkDir'}/mailboxes");
		return 1;
	}

	for(@entries) {
		my $entry = "$data->{'mailman_list_name'}$_\@$data->{'domain_name'}\t/dev/null\n";
		$content .= $entry unless $content =~ /^$entry/gm;
	}

	$rs = $file->set($content);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	# Install mailboxes table in production directory
	$rs = $file->copyFile($mta->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'});
	return $rs if $rs;

	# Add mailboxes entries - Ending

	# Schedule postmap of both transport and mailboxes tables
	$mta->{'postmap'}->{$mta->{'config'}->{'MTA_TRANSPORT_HASH'}} = 'mailman_plugin';
	$mta->{'postmap'}->{$mta->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'}} = 'mailman_plugin';

	# MTA entries - Ending

	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery(
		'mailman_id',
		'SELECT `mailman_id` FROM `mailman` WHERE `mailman_admin_id` = ? LIMIT 2',
		$data->{'mailman_admin_id'}
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1
	}

	if(scalar keys %{$rdata} == 1) {
		# Add vhost if needed
		$rs = $self->_addlListsVhost($data);
		return $rs if $rs;

		# Add DNS record if needed
		$rs = $self->_addListsDnsRecord($data);
		return $rs if $rs;
	}

	0;
}

=item _updateList(\%data)

 Update the given mailing list (admin email and password)

 Return int 0 on success, other on failure

=cut

sub _updateList($$)
{
	my ($self, $data) = @_;

	my ($rs, $stdout, $stderr);

	# Update admin email

	my $tmpFile = File::Temp->new();

	print $tmpFile "owner = ['$data->{'mailman_admin_email'}']\nhostname = ['$data->{'domain_name'}']\n";

	my @cmdArgs = ('-i', escapeShell($tmpFile), escapeShell($data->{'mailman_list_name'}));

	$rs = execute("/usr/lib/mailman/bin/config_list @cmdArgs", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Update admin password

	@cmdArgs = (
		'-q', '-l', escapeShell($data->{'mailman_list_name'}), '-p', escapeShell($data->{'mailman_admin_password'})
	);

	$rs = execute("/var/lib/mailman/bin/change_pw @cmdArgs", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs;
}

=item _enableList(\%data)

 Enable the given mailing list

 Return int 0 on success, other on failure

=cut

sub _enableList($$)
{
	my ($self, $data) = @_;

	if(-d "$disabledListsDir/$data->{'domain_name'}/$data->{'mailman_list_name'}") {
		iMSCP::Dir->new(
			'dirname', "$disabledListsDir/$data->{'domain_name'}/$data->{'mailman_list_name'}"
		)->moveDir(
			"$enabledListsDir/$data->{'mailman_list_name'}"
		);
	} else {
		0;
	}
}

=item _disableList(\%data)

 Disable the given mailing list

 Return int 0 on success, other on failure

=cut

sub _disableList($$)
{
	my ($self, $data) = @_;

	if(-d "$enabledListsDir/$data->{'domain_name'}/$data->{'mailman_list_name'}") {
		iMSCP::Dir->new(
			'dirname', "$enabledListsDir/$data->{'domain_name'}/$data->{'mailman_list_name'}"
		)->moveDir(
			"$disabledListsDir/$data->{'domain_name'}/$data->{'mailman_list_name'}"
		);
	} else {
		0;
	}
}

=item _deleteList(\%data)

 Delete the given mailing list

 Return int 0 on success, other on failure

=cut

sub _deleteList($$)
{
	my ($self, $data) = @_;

	my @cmdArgs = ('-a', escapeShell($data->{'mailman_list_name'}));
	my ($stdout, $stderr);

	my $rs = execute("/usr/lib/mailman/bin/rmlist @cmdArgs", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# MTA entries - Begin

	my $mta = Servers::mta->factory();

	my @entries = (
		'', '-admin', '-bounces', '-confirm', '-join', '-leave', '-owner', '-request', '-subscribe', '-unsubscribe'
	);

	# Delete transport entries - Begin

	# Backup current working transport table if any
	$rs = iMSCP::File->new(
		'filename' => "$mta->{'wrkDir'}/transport"
	)->copyFile(
		"$mta->{'bkpDir'}/transport." . time
	) if -f "$mta->{'wrkDir'}/transport";
	return $rs if $rs;

	my $file = iMSCP::File->new('filename' => "$mta->{'wrkDir'}/transport");
	my $content = $file->get();

	if(! defined $content){
		error("Unable to read $mta->{'wrkDir'}/transport");
		return 1;
	}

	for(@entries) {
		my $entry = "$data->{'mailman_list_name'}$_\@$data->{'domain_name'}\tmailman:\n";
		$content =~ s/^$entry//gm;
	}

	$rs = $file->set($content);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	# Install transport table in production directory
	$rs = $file->copyFile($mta->{'config'}->{'MTA_TRANSPORT_HASH'});
	return $rs if $rs;

	# Delete transport entries - Ending

	# Delete mailboxes entries - Begin

	$rs = iMSCP::File->new(
		'filename' => $mta->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'}
	)->copyFile(
		"$mta->{'bkpDir'}/transport." . time
	);
	return $rs if $rs;

	$file = iMSCP::File->new('filename' => "$mta->{'wrkDir'}/mailboxes");
	$content = $file->get();

	if(! defined $content){
		error("Unable to read $mta->{'wrkDir'}/mailboxes");
		return 1;
	}

	for(@entries) {
		my $entry = "$data->{'mailman_list_name'}$_\@$data->{'domain_name'}\t/dev/null\n";
		$content =~ s/^$entry//gm;
	}

	$rs = $file->set($content);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	# Install mailboxes table in production directory
	$rs = $file->copyFile($mta->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'} );
	return $rs if $rs;

	# Remove entries from table - Ending

	# Schedule postmap of both transport and mailboxes tables
	$mta->{'postmap'}->{$mta->{'config'}->{'MTA_TRANSPORT_HASH'}} = 'mailman_plugin';
	$mta->{'postmap'}->{$mta->{'config'}->{'MTA_VIRTUAL_MAILBOX_HASH'}} = 'mailman_plugin';

	# MTA entries - Ending

	# Delete lists vhost and DNS entry if needed

	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery(
		'mailman_id',
		'SELECT `mailman_id` FROM `mailman` WHERE `mailman_admin_id` = ? LIMIT 2',
		$data->{'mailman_admin_id'}
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1
	}

	if(scalar keys %{$rdata} == 1) {
		$rs = $self->_deleteListsVhost($data);
		return $rs if $rs;

		$rs = $self->_deleteListsDnsRecord($data);
		return $rs if $rs;
	}

	0;
}

=item _addlListsVhost(\%data)

 Add vhost for mailing list

 Return int 0 on success, other on failure

=cut

sub _addlListsVhost($$)
{
	my ($self, $data) = @_;

	my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} .
		($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $data->{'mailman_admin_id'});

	my $variables = {
		BASE_SERVER_IP => $main::imscpConfig{'BASE_SERVER_IP'},
		DOMAIN_NAME => $data->{'domain_name'},
		USER => $userName
	};

	my $vhost = iMSCP::TemplateParser::process($variables, $self->_getListVhostTemplate());
	return 1 unless defined $vhost;

	my $httpd = Servers::httpd->factory();

	my $file = iMSCP::File->new(
		'filename' => "$httpd->{'config'}->{'APACHE_SITES_DIR'}/lists.$data->{'domain_name'}.conf"
	);

	my $rs = $file->set($vhost);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	$rs = $httpd->enableSite("lists.$data->{'domain_name'}.conf");
	return $rs if $rs;

	# Schedule Apache restart
	$httpd->{'restart'} = 'yes';

	0;
}

=item _deleteListsVhost(\%data)

 Delete mailing list vhost

 Return int 0 on success, other on failure

=cut

sub _deleteListsVhost($$)
{
	my ($self, $data) = @_;

	my $httpd = Servers::httpd->factory();
	my $vhostFilePath = "$httpd->{'config'}->{'APACHE_SITES_DIR'}/lists.$data->{'domain_name'}.conf";

	if(-f $vhostFilePath) {
		my $rs = $httpd->disableSite("lists.$data->{'domain_name'}.conf");
		return $rs if $rs;

		my $file = iMSCP::File->new('filename' => $vhostFilePath);
		$rs = $file->delFile();
		return $rs if $rs;

		# Schedule Apache reload
		$httpd->{'restart'} = 'yes';
	}

	0;
}

=item _addListsDnsRecord(\%data)

 Add DNS record for mailing list

 Return int 0 on success, other on failure

=cut

sub _addListsDnsRecord($$)
{
	my ($self, $data) = @_;

	my $db = iMSCP::Database->factory();
	my $rawDb = $db->startTransaction();

	eval{
		$rawDb->do(
			'
				INSERT IGNORE INTO domain_dns (
					domain_id, alias_id, domain_dns, domain_class, domain_type, domain_text, owned_by
				) VALUES(
					?, ?, ?, ?, ?, ?, ?
				)
			',
			undef, $data->{'domain_id'}, '0', "lists.$data->{'domain_name'}.", 'IN', 'A',
			$main::imscpConfig{'BASE_SERVER_IP'}, 'plugin_mailman'
		);

		# TODO Only that status should be updated? What about subdomain...
		$rawDb->do(
			'UPDATE domain SET domain_status = ? WHERE domain_id = ?', undef, 'tochange', $data->{'domain_id'}
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

=item = _deleteListsDnsRecord(\%data)

 Delete mailing list DNS record

 Return int 0 on success, other on failure

=cut

sub _deleteListsDnsRecord($$)
{
	my ($self, $data) = @_;

	my $db = iMSCP::Database->factory();
	my $rawDb = $db->startTransaction();

	eval {
		$rawDb->do(
			'DELETE FROM `domain_dns` WHERE `domain_id` = ? AND `owned_by` = ?',
			undef, $data->{'domain_id'}, 'plugin_mailman'
		);

		# TODO Only that status should be updated? What about subdomain...
		$rawDb->do(
			'UPDATE domain SET domain_status = ? WHERE domain_id = ? AND domain_status = ?',
			undef, 'tochange', $data->{'domain_id'}, 'ok'
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
<VirtualHost {BASE_SERVER_IP}:80>
    ServerAdmin webmaster\@{DOMAIN_NAME}
    ServerName lists.{DOMAIN_NAME}

    RedirectMatch /[/]*\$ http://lists.{DOMAIN_NAME}/listinfo

    <IfModule mod_cband.c>
        CBandUser {USER}
    </IfModule>

    Include /etc/mailman/apache.conf
    ScriptAlias / /usr/lib/cgi-bin/mailman/

    # Disable mailman web-based list creation and destruction..
    # i-MSCP manages this!
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

sub _listExists($$)
{
	my ($self, $data) = @_;

	my($stdout, $stderr);
	my $rs = execute("/usr/lib/mailman/bin/list_lists -b", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	if(defined $stdout) {
		my @lists = split("\n", $stdout);
		return ($data->{'mailman_list_name'} ~~ @lists);
	}

	0;
}

=item _checkRequirements

 Check requirements

 Return int 0 if all requirements are meet, 1 otherwise

=cut

sub _checkRequirements
{
	my $self = $_[0];

	my($rs, $stdout, $stderr);

	if(! -d '/usr/lib/mailman') {
		error('Unable to find mailman library directory. Please, install mailman first.');
		return 1;
	}

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
