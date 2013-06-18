#!/usr/bin/perl

package Plugin::Mailman;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::Execute;
use Servers::httpd;
use parent 'Common::SingletonClass';

=item install()

 Perform install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	# TODO

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

	# TODO check for table existence (Idempotence issue)
	my $rdata = $database->doQuery(
		'mailman_id'
		"
			SELECT
				`t1`.*, `t2`.`domain_name`
			FROM
				`mailman` AS `t1`
			INNER JOIN
				`domain` AS ON (`t2`.`domain_admin_id` = `t1`.`mailman_admin_id`)
		"
	);
	unless($rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	if(%{$rdata}) {
		for(keys %{$rdata}) {
			$self->_deleteList($rdata->{$_}->{'domain_name'}, $rdata->{$_}->{'mailman_list_name'});
			return $rs if $rs;
		}
	}

	# Drop mailman table
	$rdata = $database->doQuery('dummy', 'DROP TABLE IF EXISTS `mailman`');
	unless($rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	$rdata = $database->doQuery(
		'dummy', "UPDATE `plugin` SET `plugin_status` = 'disabled' WHERE `plugin_name` = `Mailman`"
	);
	unless($rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	0;
}

=item process()

 Process all scheduled actions according lists status

 Return int 0 on success, other on failure

=cut

sub process
{
	my $self = shift;

	my $database = iMSCP::Database->factory();
	my $rs = 0;

	my $rdata = $database->doQuery(
		'mailman_id'
		"
			SELECT
				`t1`.*, `t2`.`domain_name`
			FROM
				`mailman` AS `t1`
			INNER JOIN
				`domain` AS ON (`t2`.`domain_admin_id` = `t1`.`mailman_admin_id`)
			WHERE
				`mailman_status` IN('toadd', 'change', 'delete')
		"
	);
	unless($rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	if(%{$rdata}) {
		for(keys %{$rdata}) {
			if($rdata->{$_}->{'mailman_status'} eq 'toadd') {
				$rs = $self->_addList(
					$rdata->{$_}->{'domain_name'}, $rdata->{$_}->{'mailman_list_name'}
					$rdata->{$_}->{'mailman_admin_email'}, $rdata->{$_}->{'mailman_admin_password'}
				);
				return $rs if $rs;
			} elsif($rdata->{$_}->{'mailman_status'} eq 'change') {
				$rs = $self->_updateList(
					$rdata->{$_}->{'mailman_list_name'}, $rdata->{$_}->{'mailman_admin_email'},
					$rdata->{$_}->{'mailman_admin_password'}
				);
				return $rs if $rs;
			} elsif($rdata->{$_}->{'mailman_status'} eq 'delete' {
				$rs = $self->_deleteList($rdata->{$_}->{'domain_name'}, $rdata->{$_}->{'mailman_list_name'});
				return $rs if $rs;
			}
		}
	}

	0;
}

=item _addList($domainName, $listName, $adminEmail, $adminPassword)

 Add the given list

 Return int 0 on success, other on failure

=cut

sub _addList
{
	my $self = shift;
	my $domainName = shift;
	my $listName = shift;
	my $adminEmail = shift;
	my $adminPassword = shift;

	my @cmdArgs = ('-q', escape($listName), escape($adminEmail), escape($adminPassword));

	my ($stdout, $stderr);
	my $rs = execute("/usr/sbin/newlist @cmdArgs", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# TODO Add MTA entries

	$rs = $rs = $self->_addlListVhost($listName, $domainName);
	return $rs if $rs;

	0;
}

=item _updateList($listName, $adminEmail, $adminPassword)

 Update the given list (admin email and password)

 Return int 0 on success, other on failure

=cut

sub _updateList
{
	my $self = shift;
	my $listName = shift;
	my $adminEmail = shift;
	my $adminPassword = shift;

	# TODO create mailman list
	#todo if list do not exist ---> create it ($self-->_addList...)

	$rs = $self->_addlListVhost($domainName, $listName);
	return $rs if $rs;

	0;
}

=item _deleteList($domainName, $listName)

 Delete the given list

 Return int 0 on success, other on failure

=cut

sub _deleteList
{
	my $self = shift;
	my $domainName = shift;
	my $listName = shift;

	# TODO delete mailman list
	# TODO Delete MTA entries

	$rs = $self->_deleteListVhost($domainName, $listName);
	return $rs if $rs;

	0;
}

=item _addlListVhost($domainName, $listName)

 Add list vhost

 Return int 0 on success, other on failure

=cut

sub _addlListVhost
{
	my $self = shift;
	my $domainName = shift;
	my $listName = shift;

	my $httpd = Servers::httpd->factory();

	# TODO create vhost file

	my $rs = $httpd->enableSite("lists.$domainName.conf");
	return $rs if $rs;

	0;
}

=item _deleteListVhost($domainName, $listName)

 Delete list vhost

 Return int 0 on success, other on failure

=cut

sub _deleteListVhost
{
	my $self = shift;
	my $domainName = shift;
	my $listName = shift;

	my $rs = $httpd->disableSite("lists.$domainName.conf");
	return $rs if $rs;

	# TODO delete vhost file

	0;
}

=item _deleteListVhost()

 Return list vhost template

 Return int 0 on success, other on failure

=cut

sub _getListVhostTemplate
{

	# TODO
	0;
}

1;
