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

# TODO delTmp for PHP version which are managed by this plugin

package Plugin::PhpSwitcher;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Database;
use Servers::httpd;
use JSON;

use parent 'Common::SingletonClass';

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
	if($main::imscpConfig{'HTTPD_SERVER'} eq 'apache_fcgid') {
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

	0;
}

=item phpSwitcherEventListener(\%data)

 Event listener which is responsible to override PHP binary and PHP configuration paths

 Return int 0 on success, other on failure

=cut

sub phpSwitcherEventListener($)
{
	my $phpSwitcher = __PACKAGE__->getInstance();
	my $adminId = $_[0]->{'DOMAIN_ADMIN_ID'} // 0;

	my $phpVersions = ($phpSwitcher->{'memcached'}) ? $phpSwitcher->{'memcached'}->get('php_versions') : { };

	if(! defined $phpVersions) {
		$phpVersions = $phpSwitcher->{'db'}->doQuery(
			'admin_id',
			'
				SELECT
					admin_id, version_binary_path, version_confdir_path
				FROM
					php_switcher_version
				INNER JOIN
					php_switcher_version_admin USING (version_id)
			'
		);
		unless(ref $phpVersions eq 'HASH') {
			error($phpVersions);
			return 1;
		}

		$phpSwitcher->{'memcached'}->set('php_versions', $phpVersions);
	}

	if(exists $phpVersions->{$adminId}) {
		$phpSwitcher->{'httpd'}->{'config'}->{'PHP5_FASTCGI_BIN'} = $phpVersions->{$adminId}->{'version_binary_path'};
		$phpSwitcher->{'httpd'}->{'config'}->{'PHP_STARTER_DIR'} = $phpVersions->{$adminId}->{'version_confdir_path'};
	} else {
		$phpSwitcher->{'httpd'}->{'config'}->{'PHP5_FASTCGI_BIN'} = $phpSwitcher->{'default_binary_path'},;
		$phpSwitcher->{'httpd'}->{'config'}->{'PHP_STARTER_DIR'} = $phpSwitcher->{'default_confdir_path'};
	}

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize plugin instance

 Return Plugin::PhpSwitcher

=cut

sub _init()
{
	my $self = $_[0];

	$self->{'db'} = iMSCP::Database->factory();

	my $pluginConfig = $self->{'db'}->doQuery(
		'plugin_name', 'SELECT plugin_name, plugin_config FROM plugin WHERE plugin_name = ?', 'PhpSwitcher'
	);
	unless(ref $pluginConfig eq 'HASH') {
		fatal($pluginConfig);
	} else {
		$self->{'config'} = decode_json($pluginConfig->{'PhpSwitcher'}->{'plugin_config'});
	}

	$self->{'httpd'} = Servers::httpd->factory();
	$self->{'default_binary_path'} = $self->{'httpd'}->{'config'}->{'PHP5_FASTCGI_BIN'};
	$self->{'default_confdir_path'} = $self->{'httpd'}->{'config'}->{'PHP_STARTER_DIR'};

	# Small-haking to avoid too many IO operations and conffile override on failure
	my %config = %{$self->{'httpd'}->{'config'}};
	untie %{$self->{'httpd'}->{'config'}};
	%{$self->{'httpd'}->{'config'}} = %config;

	$self->{'memcached'} = $self->_getMemcached();

	$self;
}

=item _getMemcached()

 Get memcached instance

 Return Cache::Memcached::Fast or undef in case memcached server is not enabled

=cut

sub _getMemcached
{
	my $self = $_[0];

	my $memcached;

	if($self->{'config'}->{'memcached'}->{'enabled'}) {
		if(eval 'require Cache::Memcached::Fast') {
			require Digest::SHA;
			Digest::SHA->import('sha1_hex');

			$memcached = new Cache::Memcached::Fast({
				servers => ["$self->{'config'}->{'memcached'}->{'hostname'}:$self->{'config'}->{'memcached'}->{'port'}"],
				namespace => substr(sha1_hex('PhpSwitcher'), 0 , 8) . '_', # Hashed manually (expected)
				connect_timeout => 0.5,
				io_timeout => 0.5,
				close_on_error => 1,
				compress_threshold => 100_000,
				compress_ratio => 0.9,
				compress_methods => [ \&IO::Compress::Gzip::gzip, \&IO::Uncompress::Gunzip::gunzip ],
				max_failures => 3,
				failure_timeout => 2,
				ketama_points => 150,
				nowait => 1,
				serialize_methods => [ \&Storable::freeze, \&Storable::thaw ],
				utf8 => 1,
			});
		}
	}

	$memcached;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
