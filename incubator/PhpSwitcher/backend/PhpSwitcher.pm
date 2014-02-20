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
use parent 'Common::SingletonClass';

our %phpVersions = ();

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
	iMSCP::HooksManager->getInstance()->register('beforeHttpdBuildConfFile', \&overridePhpVersion);
}

sub overridePhpVersion
{
	my ($tplContent, $tplName, $data) = @_;

	if($tplName eq 'php5-fcgid-starter.tpl') {
		my $adminId = $data->{'DOMAIN_ADMIN_ID'};

		if(!exists $phpVersions{$adminId}) {
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

		if(exists $Plugin::PhpSwitcher::phpVersions{$adminId}) {;
			$$tplContent = process(
				{
					PHP5_FASTCGI_BIN => $Plugin::PhpSwitcher::phpVersions{$adminId}->{'version_binary_path'}
					#PHP_STARTER_DIR => $Plugin::PhpSwitcher::phpVersions{$adminId}->{version_confdir_path'}
				},
				$$tplContent
			);
		}
	}

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
