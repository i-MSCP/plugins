#!/usr/bin/perl

=head1 NAME

 Plugin::PhpSwitcher

=cut

# i-MSCP PhpSwitcher plugin
# Copyright (C) 2014 Laurent Declercq <l.declercq@nuxwin.com>
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
#

package Plugin::PhpSwitcher;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Database;
use iMSCP::TemplateParser;
use Servers::httpd;
use parent 'Common::SingletonClass';

our %phpVersions = ();

our $httpdServer;
our $PHP5_FASTCGI_BIN;
our $PHP_STARTER_DIR;

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP PhpSwitcher plugin.

=head1 PUBLIC METHODS

=over 4

=item run()

 Register event listener

 Return int 0 on success, other on failure

=cut

sub run
{
	$Plugin::PhpSwitcher::httpdServer = Servers::httpd->factory();
	$Plugin::PhpSwitcher::PHP5_FASTCGI_BIN = $httpdServer->{'config'}->{'PHP5_FASTCGI_BIN'};
	$Plugin::PhpSwitcher::PHP_STARTER_DIR = $httpdServer->{'config'}->{'PHP_STARTER_DIR'};

	my $hooksManager = iMSCP::HooksManager->getInstance();

	$hooksManager->register('beforeHttpdAddDmn', \&phpSwitcherEventListener);
	$hooksManager->register('beforeHttpdRestoreDmn', \&phpSwitcherEventListener);
	$hooksManager->register('beforeHttpdDisableDmn', \&phpSwitcherEventListener);
	$hooksManager->register('beforeHttpdDelDmn', \&phpSwitcherEventListener);

	$hooksManager->register('beforeHttpdAddSub', \&phpSwitcherEventListener);
	$hooksManager->register('beforeHttpdRestoreSub', \&phpSwitcherEventListener);
	$hooksManager->register('beforeHttpdDisableSub', \&phpSwitcherEventListener);
	$hooksManager->register('beforeHttpdDelSub', \&phpSwitcherEventListener);
}


=item phpSwitcherEventListener(\%data)

 Event listener which is responsible to override PHP binary and conffdir paths

 Return int 0 on success, other on failure

=cut

sub phpSwitcherEventListener($)
{
	my $data = $_[0];

	my $adminId = $data->{'DOMAIN_ADMIN_ID'};

	if(!exists $Plugin::PhpSwitcher::phpVersions{$adminId}) {
		my $rdata = iMSCP::Database->factory()->doQuery(
			'admin_id',
			'
				SELECT
					admin_id, version_binary_path, version_confdir_path
				FROM
					php_switcher_version
				INNER JOIN
					php_switcher_version_admin USING (version_id)
				WHERE
					admin_id = ?
			',
			$adminId
		);
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		} elsif(%{$rdata}) {
			# TODO memcached
			$Plugin::PhpSwitcher::phpVersions{$adminId} = $rdata->{$adminId};
		}
	}

	if(exists $Plugin::PhpSwitcher::phpVersions{$adminId}) {
		$Plugin::PhpSwitcher::httpdServer->{'config'}->{'PHP5_FASTCGI_BIN'} =
			$Plugin::PhpSwitcher::phpVersions{$adminId}->{'version_binary_path'};

		$Plugin::PhpSwitcher::httpdServer->{'config'}->{'PHP_STARTER_DIR'} =
			$Plugin::PhpSwitcher::phpVersions{$adminId}->{'version_confdir_path'};
	} else {
		$Plugin::PhpSwitcher::httpdServer->{'config'}->{'PHP5_FASTCGI_BIN'} =
			$Plugin::PhpSwitcher::PHP5_FASTCGI_BIN;

		$Plugin::PhpSwitcher::httpdServer->{'config'}->{'PHP_STARTER_DIR'} =
			$Plugin::PhpSwitcher::PHP_STARTER_DIR;
	}

	0;
}

END
{
	$httpdServer->{'config'}->{'PHP5_FASTCGI_BIN'} = $Plugin::PhpSwitcher::PHP5_FASTCGI_BIN;
	$httpdServer->{'config'}->{'PHP_STARTER_DIR'} = $Plugin::PhpSwitcher::PHP_STARTER_DIR;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
