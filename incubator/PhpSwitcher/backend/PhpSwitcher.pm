=head1 NAME

 Plugin::PhpSwitcher

=cut

# i-MSCP PhpSwitcher plugin
# Copyright (C) 2014-2015 Laurent Declercq <l.declercq@nuxwin.com>
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
use iMSCP::Database;
use Servers::httpd;
use iMSCP::Dir;
use Scalar::Defer;
use parent 'Common::SingletonClass';

my $phpVersions = lazy {
	my $rows = iMSCP::Database->factory()->doQuery('version_id', 'SELECT * FROM php_switcher_version');
	(ref $rows eq 'HASH') or die($rows);
	$rows;
};

my $phpVersionsAdmins = lazy {
	my $rows = iMSCP::Database->factory()->doQuery(
		'admin_id', 'SELECT admin_id, version_id FROM php_switcher_version_admin'
	);
	(ref $rows eq 'HASH') or die($rows);
	$rows;
};

my $defaultPhpBinaryPath = Servers::httpd->factory()->{'config'}->{'PHP_CGI_BIN'};

=head1 DESCRIPTION

 This package implements the backend side of the PhpSwitcher plugin.

=head1 PUBLIC METHODS

=over 4

=item enable()

 Process enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	$_[0]->_registerListeners();
}

=item change()

 Process change tasks

 Return int 0 on success, other on failure

=cut

sub change
{
	$_[0]->_registerListeners();
}

=item run()

 Process plugin tasks

 Return int 0 on success, other on failure

=cut

sub run
{
	$_[0]->_registerListeners();
}

=back

=head1 EVENT LISTENERS

=over 4

=item overridePhpData(\%data)

 Listener responsible to override PHP binary path

 Param hash \%data Data as provided by the Alias|Domain|SubAlias|Subdomain modules
 Return int 0 on success, other on failure

=cut

sub overridePhpData
{
	my $data = shift;

	my $phpBinaryPath;
	if(exists $phpVersionsAdmins->{$data->{'DOMAIN_ADMIN_ID'}}) {
		$phpBinaryPath = $phpVersions->{
			$phpVersionsAdmins->{$data->{'DOMAIN_ADMIN_ID'}}->{'version_id'}
		}->{'version_binary_path'};
	} else {
		$phpBinaryPath = $defaultPhpBinaryPath;
	}

	my $httpdConfig = Servers::httpd->factory()->{'config'};
	my $httpdConfigObj = tied %{$httpdConfig};
	$httpdConfigObj->{'temporary'} = 1;
	$httpdConfig->{'PHP_CGI_BIN'} = $phpBinaryPath;
	$httpdConfigObj->{'temporary'} = 0;

	0;
}

#=item overridePhpIni($tplName, \$tplContent, \%data)
#
# Listener responsible to override PHP .ini file
#
# Param string $tplName Template file name
# Param string \$tplContent Template content
# Param hash \%data Data as provided by the Alias|Domain|SubAlias|Subdomain modules
# Return int 0
#
#=cut
#
#sub overridePhpIni
#{
#	my ($tplName, $tplContent, $data) = @_;
#
#	if($tplName eq 'php.ini' && exists $phpVersionsAdmins{$data->{'DOMAIN_ADMIN_ID'}}) {
#		# TODO if template is php.ini, fill template content with our own php.ini
#	}
#
#	0;
#}

=back

=head1 PRIVATE METHODS

=over 4

=item _registerListeners()

 Register event listeners

 Return int 0 on success, other on failure

=cut

sub _registerListeners
{
	my $self = shift;

	if($main::imscpConfig{'HTTPD_SERVER'} eq 'apache_fcgid' ) {
		$self->{'eventManager'}->register('beforeHttpdAddDmn', \&overridePhpData);
		$self->{'eventManager'}->register('beforeHttpdRestoreDmn', \&overridePhpData);
		$self->{'eventManager'}->register('beforeHttpdRestoreSub', \&overridePhpData);
		$self->{'eventManager'}->register('beforeHttpdDelDmn', \&overridePhpData);
		#$self->{'eventManager'}->register('onLoadTemplate', \&overridePhpIni);
		$self->{'eventManager'}->register('afterDispatchRequest', sub {
			my $rs = shift;

			unless($rs) {
				my $qrs = iMSCP::Database->factory()->doQuery(
					'dummy', "UPDATE php_switcher_version SET version_status = 'ok' WHERE version_status = 'tochange'"
				);
				unless(ref $qrs eq 'HASH') {
					error($qrs);
					return 1;
				}

				0;
			}
		});
	} else { # Handle case where the administrator switched to another httpd implementation
		$self->{'FORCE_RETVAL'} = 1;
		$self->{'eventManager'}->register('onBeforeSetPluginStatus', sub {
			my ($pluginName, $pluginStatus) = @_;
			$$pluginStatus = 'todisable' if $pluginName eq 'PhpSwitcher';
			0;
		});
	}
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
