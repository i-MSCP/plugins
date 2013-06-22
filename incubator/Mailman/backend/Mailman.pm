#!/usr/bin/perl

=head1 NAME

 Plugin::Mailman - i-MSCP Mailman plugin (backend)

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
# @category    i-MSCP
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Laurent Declercq <l.declercq@nuxwin.com>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Plugin::Mailman;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::Execute;
use iMSCP::Templator;
use Servers::httpd;
use Servers::mta;
use File::Temp;

use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides backend part for the i-MSCP Mailman plugin.

=head1 PUBLIC METHODS

=over 4

=item install()

 Perform installation tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	# Check plugin requirements
	my $rs = _checkRequirements();
	return $rs if $rs;

	if(-f '/etc/mailman/mm_cfg.py') {
		my $file = iMSCP::File->new('filename' => '/etc/mailman/mm_cfg.py');

		my $fileContent = $file->get();
		return 1 if ! $fileContent;

		$fileContent =~ s#^(DEFAULT_URL_PATTERN\s*=\s*).*$#$1'http://%s/'#gm;
		$fileContent =~ s/^#\s*(MTA\s*=\s*None)/$1/im;

		$rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
		return $rs if $rs;

		$rs = $file->mode(0644);
		return $rs if $rs;
	} else {
		error('File /etc/mailman/mm_cfg.py not found');
		return 1;
	}

	my ($stdout, $stderr);
	$rs = execute('/usr/sbin/postconf -e mailman_destination_recipient_limit=1', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	my $mta = Servers::mta->factory();

	# Schedule MTA restart
	$mta->{'restart'} = 'yes';

	if(defined $main::execmode && $main::execmode eq 'setup') {
		my $database = iMSCP::Database->factory();

		my $rdata = $database->doQuery(
			'dummy', "UPDATE `mailman` SET `mailman_status` = 'change' WHERE `mailman_status` NOT IN('toadd', 'delete')"
		);
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}

		$rs = $self->run();
		return $rs if $rs;
	}

	0;
}

=item uninstall()

 Perform un-installation tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	my $database = iMSCP::Database->factory();
	my $rs = 0;

	my $rdata = $database->doQuery('1', "SHOW TABLES LIKE 'mailman'");
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	if(%{$rdata}) {
		$rdata = $database->doQuery(
			'mailman_id',
			'
				SELECT
					`t1`.*, `t2`.`domain_name`
				FROM
					`mailman` AS `t1`
				INNER JOIN
					`domain` AS `t2` ON (`t2`.`domain_admin_id` = `t1`.`mailman_admin_id`)
			'
		);
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}

		if(%{$rdata}) {
			for(keys %{$rdata}) {
				$self->_deleteList(
					$rdata->{$_}->{'mailman_admin_id'}, $rdata->{$_}->{'domain_name'},
					$rdata->{$_}->{'mailman_list_name'}
				);

				my @sql;

				if($rs) {
					@sql = (
						'UPDATE `mailman` SET `mailman_status` = ? WHERE `mailman_id` = ?',
						scalar getMessageByType('error'), $rdata->{$_}->{'mailman_id'}
					);
				} else {
					@sql = ('DELETE FROM `mailman` WHERE `mailman_id` = ?', $rdata->{$_}->{'mailman_id'});
				}

				$rdata = $database->doQuery('dummy', @sql);
				unless(ref $rdata eq 'HASH') {
					error($rdata);
					return 1;
				}

				return $rs if $rs;
			}
		}

		# Drop mailman table
		$rdata = $database->doQuery('dummy', 'DROP TABLE IF EXISTS `mailman`');
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}
	}

	0;
}

=item run()

 Run all scheduled actions according lists status

 Return int 0 on success, other on failure

=cut

sub run
{
	my $self = shift;

	my $database = iMSCP::Database->factory();
	my $rs = 0;

	# TODO todisable/toenable status

	my $rdata = $database->doQuery(
		'mailman_id',
		"
			SELECT
				`t1`.*, `t2`.`domain_name`
			FROM
				`mailman` AS `t1`
			INNER JOIN
				`domain` AS `t2` ON (`t2`.`domain_admin_id` = `t1`.`mailman_admin_id`)
			WHERE
				`mailman_status` IN('toadd', 'change', 'delete')
		"
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	my @sql;

	if(%{$rdata}) {
		for(keys %{$rdata}) {
			my $status = $rdata->{$_}->{'mailman_status'};

			if($status eq 'toadd') {
				$rs = $self->_addList(
					$rdata->{$_}->{'mailman_admin_id'}, $rdata->{$_}->{'domain_name'},
					$rdata->{$_}->{'mailman_list_name'}, $rdata->{$_}->{'mailman_admin_email'},
					$rdata->{$_}->{'mailman_admin_password'},
				);

				@sql = (
					'UPDATE `mailman` SET `mailman_status` = ? WHERE `mailman_id` = ?',
					($rs ? scalar getMessageByType('error') : 'ok'), $rdata->{$_}->{'mailman_id'}
				);
			} elsif($status eq 'change') {
				$rs = $self->_updateList(
					$rdata->{$_}->{'domain_name'}, $rdata->{$_}->{'mailman_list_name'},
					$rdata->{$_}->{'mailman_admin_email'}, $rdata->{$_}->{'mailman_admin_password'}
				);

				@sql = (
					'UPDATE `mailman` SET `mailman_status` = ? WHERE `mailman_id` = ?',
					($rs ? scalar getMessageByType('error') : 'ok'), $rdata->{$_}->{'mailman_id'}
				);
			} elsif($status eq 'delete') {
				$rs = $self->_deleteList(
					$rdata->{$_}->{'mailman_admin_id'}, $rdata->{$_}->{'domain_name'},
					$rdata->{$_}->{'mailman_list_name'}
				);

				if($rs) {
					@sql = (
						'UPDATE `mailman` SET `mailman_status` = ? WHERE `mailman_id` = ?',
						scalar getMessageByType('error'), $rdata->{$_}->{'mailman_id'}
					);
				} else {
					@sql = ('DELETE FROM `mailman` WHERE `mailman_id` = ?', $rdata->{$_}->{'mailman_id'});
				}
			}

			$rdata = $database->doQuery('dummy', @sql);
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

=item _addList($adminId, $domainName, $listName, $adminEmail, $adminPassword)

 Add list

 Return int 0 on success, other on failure

=cut

sub _addList($$$$$$)
{
	my $self = shift;
	my $adminId = shift;
	my $domainName = shift;
	my $listName = shift;
	my $adminEmail = shift;
	my $adminPassword = shift;

	my ($stdout, $stderr);
	my $rs = 0;

	if(!$self->_listExists($listName)) {
		my @cmdArgs = (
			'-q',
			'-u',
			escapeShell("lists.$domainName"),
			'-e',
			escapeShell($domainName),
			escapeShell($listName),
			escapeShell($adminEmail),
			escapeShell($adminPassword)
		);

		$rs = execute("/usr/lib/mailman/bin/newlist @cmdArgs", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	# MTA entries - Begin

	my @entries = (
		'', '-admin', '-bounces', '-confirm', '-join', '-leave', '-owner', '-request', '-subscribe', '-unsubscribe'
	);

	my $mta = Servers::mta->factory();

	# Add needed entries in transport table - Begin

	# Backup current transport table if exists
	$rs = iMSCP::File->new(
		'filename' => $mta->{'MTA_TRANSPORT_HASH'}
	)->copyFile( "$mta->{'bkpDir'}/transport." . time);
	return $rs if $rs;

	my $file = iMSCP::File->new('filename' => "$mta->{'wrkDir'}/transport");
	my $content = $file->get();

	if(! defined $content){
		error("Unable to read $mta->{'wrkDir'}/transport");
		return 1;
	}

	for(@entries) {
		my $entry = "$listName$_\@$domainName\tmailman:\n";
		$content .= $entry unless $content =~ /^$entry/gm;
	}

	$rs = $file->set($content);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	# Install transport table in production directory
	$rs = $file->copyFile($mta->{'MTA_TRANSPORT_HASH'});
	return $rs if $rs;

	# Add entries in transport table - Ending

	# Add entries in mailboxes table - Begin

	# Backup current mailboxes table if exists
	$rs = iMSCP::File->new(
		'filename' => $mta->{'MTA_VIRTUAL_MAILBOX_HASH'}
	)->copyFile( "$mta->{'bkpDir'}/transport." . time);
	return $rs if $rs;

	$file = iMSCP::File->new('filename' => "$mta->{'wrkDir'}/mailboxes");
	$content = $file->get();

	if(! defined $content){
		error("Unable to read $mta->{'wrkDir'}/mailboxes");
		return 1;
	}

	for(@entries) {
		my $entry = "$listName$_\@$domainName\t/dev/null\n";
		$content .= $entry unless $content =~ /^$entry/gm;
	}

	$rs = $file->set($content);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	# Install mailboxes table in production directory
	$rs = $file->copyFile($mta->{'MTA_VIRTUAL_MAILBOX_HASH'} );
	return $rs if $rs;

	# Add entries in mailboxes table - Ending

	# Schedule postmap of transport table
	$mta->{'postmap'}->{$mta->{'MTA_TRANSPORT_HASH'}} = 'mailman_plugin';

	# Schedule postmap of mailbox table
	$mta->{'postmap'}->{$mta->{'MTA_VIRTUAL_MAILBOX_HASH'}} = 'mailman_plugin';

	# MTA entries - Ending

	# Add lists vhost
	$rs = $self->_addlListsVhost($adminId, $domainName);
	return $rs if $rs;

	0;
}

=item _updateList($domainName, $listName, $adminEmail, $adminPassword)

 Update list (admin email and password)

 Return int 0 on success, other on failure

=cut

sub _updateList($$$$$)
{
	my $self = shift;
	my $domainName = shift;
	my $listName = shift;
	my $adminEmail = shift;
	my $adminPassword = shift;

	my ($rs, $stdout, $stderr, );

	# Update admin email - Begin

	my $tmpFile = File::Temp->new();
	my $file = iMSCP::File->new('filename' => $tmpFile->filename);

	$rs = $file->set("owner = ['$adminEmail']\nhostname = ['$domainName']");
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	my @cmdArgs = ('-i', escapeShell($tmpFile->filename), escapeShell($listName));

	$rs = execute("/usr/lib/mailman/bin/config_list @cmdArgs", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Update admin email - Ending

	# Update admin password - Begin

	@cmdArgs = ('-q', '-l', escapeShell($listName), '-p', escapeShell($adminPassword));

	$rs = execute("/var/lib/mailman/bin/change_pw @cmdArgs", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Update admin password - Ending

	0;
}

=item _deleteList($adminId, $domainName, $listName)

 Delete list

 Return int 0 on success, other on failure

=cut

sub _deleteList($$$$)
{
	my $self = shift;
	my $adminId = shift;
	my $domainName = shift;
	my $listName = shift;

	my @cmdArgs = ('-a', escapeShell($listName));
	my ($stdout, $stderr);

	my $rs = execute("/usr/lib/mailman/bin/rmlist @cmdArgs", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# MTA entries - Begin

	my @entries = (
		'', '-admin', '-bounces', '-confirm', '-join', '-leave', '-owner', '-request', '-subscribe', '-unsubscribe'
	);

	my $mta = Servers::mta->factory();

	# Remove entries from transport table - Begin

	# Backup current transport table if exists
	$rs = iMSCP::File->new(
		'filename' => $mta->{'MTA_TRANSPORT_HASH'}
	)->copyFile( "$mta->{'bkpDir'}/transport." . time);
	return $rs if $rs;

	my $file = iMSCP::File->new('filename' => "$mta->{'wrkDir'}/transport");
	my $content = $file->get();

	if(! defined $content){
		error("Unable to read $mta->{'wrkDir'}/transport");
		return 1;
	}

	for(@entries) {
		my $entry = "$listName$_\@$domainName\tmailman:\n";
		$content =~ s/^$entry//gm;
	}

	$rs = $file->set($content);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	# Install transport table in production directory
	$rs = $file->copyFile($mta->{'MTA_TRANSPORT_HASH'});
	return $rs if $rs;

	# Remove entries from transport table - Ending

	# Remove entries from mailboxes table - Begin

	# Backup current mailboxes table if exists
	$rs = iMSCP::File->new(
		'filename' => $mta->{'MTA_VIRTUAL_MAILBOX_HASH'}
	)->copyFile( "$mta->{'bkpDir'}/transport." . time);
	return $rs if $rs;

	$file = iMSCP::File->new('filename' => "$mta->{'wrkDir'}/mailboxes");
	$content = $file->get();

	if(! defined $content){
		error("Unable to read $mta->{'wrkDir'}/mailboxes");
		return 1;
	}

	for(@entries) {
		my $entry = "$listName$_\@$domainName\t/dev/null\n";
		$content =~ s/^$entry//gm;
	}

	$rs = $file->set($content);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;
	$rs = $file->mode(0644);
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return $rs if $rs;

	# Install mailboxes table in production directory
	$rs = $file->copyFile($mta->{'MTA_VIRTUAL_MAILBOX_HASH'} );
	return $rs if $rs;

	# Remove entries from table - Ending

	# Schedule postmap of transport table
	$mta->{'postmap'}->{$mta->{'MTA_TRANSPORT_HASH'}} = 'mailman_plugin';

	# Schedule postmap of mailboxes table
	$mta->{'postmap'}->{$mta->{'MTA_VIRTUAL_MAILBOX_HASH'}} = 'mailman_plugin';

	# MTA entries - Ending

	$rs = $self->_deleteListsVhost($adminId, $domainName);
	return $rs if $rs;

	0;
}

=item _addlListsVhost($adminId, $domainName)

 Add lists vhost

 Return int 0 on success, other on failure

=cut

sub _addlListsVhost($$$)
{
	my $self = shift;
	my $adminId = shift;
	my $domainName = shift;

	my $userName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . ($main::imscpConfig{'SYSTEM_USER_MIN_UID'} + $adminId);

	my $variables = {
		BASE_SERVER_IP => $main::imscpConfig{'BASE_SERVER_IP'},
		DOMAIN_NAME => $domainName,
		USER => $userName
	};

	my $listsVhost = iMSCP::Templator::process($variables, $self->_getListVhostTemplate());
	return 1 if ! $listsVhost;

	my $httpd = Servers::httpd->factory();

	my $file = iMSCP::File->new('filename' => "$httpd->{'tplValues'}->{'APACHE_SITES_DIR'}/lists.$domainName.conf");

	my $rs = $file->set($listsVhost);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->owner($main::imscpConfig{'ROOT_USER'}, $main::imscpConfig{'ROOT_GROUP'});
	return$rs if $rs;

	$rs = $file->mode(0644);
	return $rs if $rs;

	$rs = $httpd->enableSite("lists.$domainName.conf");
	return $rs if $rs;

	0;
}

=item _deleteListsVhost($adminId, $domainName)

 Delete lists vhost

 Return int 0 on success, other on failure

=cut

sub _deleteListsVhost($$$)
{
	my $self = shift;
	my $adminId = shift;
	my $domainName = shift;

	my $database = iMSCP::Database->factory();

	my $rdata = $database->doQuery(
		'mailman_id', 'SELECT `mailman_id` FROM `mailman` WHERE `mailman_admin_id` = ? LIMIT 2', $adminId
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1
	}

	if(scalar keys(%{$rdata}) == 1) {
		my $httpd = Servers::httpd->factory();
		my $vhostFilePath = "$httpd->{'tplValues'}->{'APACHE_SITES_DIR'}/lists.$domainName.conf";

		if(-f $vhostFilePath) {
			my $rs = $httpd->disableSite("lists.$domainName.conf");
			return $rs if $rs;

			my $file = iMSCP::File->new('filename' => $vhostFilePath);
			$rs = $file->delFile();
			return $rs if $rs;
		}
	}

	0;
}

=item _getListsVhostTemplate()

 Return lists vhost template

 Return string String representing lists vhost template

=cut

sub _getListVhostTemplate()
{
	my $listVhostTpl = <<EOF;
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

	$listVhostTpl;
}

=item _listExists

 Whether or not the given list exists

 Return int 1 if the given list exists, 0 otherwise

=cut

sub _listExists($$)
{
	my $self = shift;
	my $listName = shift;

	my($stdout, $stderr);
	my $rs = execute("/usr/lib/mailman/bin/list_lists -b", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	if(defined $stdout) {
		my @lists = split("\n", $stdout);
		return ($listName ~~ @lists);
	}

	0;
}

=item _checkRequirements

 Check requirements

 Return int 0 if all requirements are meet, 1 otherwise

=cut

sub _checkRequirements
{
	my $self = shift;

	my($rs, $stdout, $stderr);

	if(! -d '/usr/lib/mailman') {
		error('Unable to find mailman library directory. Install mailman first.');
		return 1;
	} elsif($main::imscpConfig{'MTA_SERVER'} ne 'postfix') {
		error('Mailman plugin require i-MSCP Postfix server implementation');
		return 1;
	} elsif($main::imscpConfig{'HTTPD_SERVER'} !~ /^apache_/) {
		error('Mailman plugin require i-MSCP Apache server implementation');
		return 1;
	} elsif($main::imscpConfig{'NAMED_SERVER'} ne 'bind') {
		error('Mailman plugin require i-MSCP bind9 server implementation');
		return 1;
	}

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
