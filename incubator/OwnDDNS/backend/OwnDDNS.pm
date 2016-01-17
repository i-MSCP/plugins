#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2016 Sascha Bay <info@space2place.de>
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
# @subpackage  OwnDDNS
# @copyright   Sascha Bay <info@space2place.de>
# @author      Sascha Bay <info@space2place.de>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Plugin::OwnDDNS;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::File;

use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP OwnDDNS plugin.

=head1 PUBLIC METHODS

=over 4

=item change()

 Perform change tasks

 Return int 0 on success, other on failure

=cut

sub change
{
	$_[0]->update();
}

=item update()

 Process update tasks

 Return int 0 on success, other on failure

=cut

sub update
{
	my $self = $_[0];

	my $rs = $self->_modifyApacheMasterConf('add');
	return $rs if $rs;

	$self->_restartDaemonApache();
}

=item enable()

 Process enable tasks

 Return int 0 on success, other on failure

=cut

sub enable()
{
	my $self = $_[0];

	my $rs = $self->_modifyApacheMasterConf('add');
	return $rs if $rs;

	$self->_restartDaemonApache();
}

=item disable()

 Process disable tasks

 Return int 0 on success, other on failure

=cut

sub disable()
{
	my $self = $_[0];

	my $rs = $self->_modifyApacheMasterConf('remove');
	return $rs if $rs;

	$self->_restartDaemonApache();
}

=item _modifyApacheMasterConf($action)

 Modify Apache master config file

 Return int 0 on success, other on failure

=cut

sub _modifyApacheMasterConf($$)
{
	my ($self, $action) = @_;
	my $ownDdnsConfig;

	my $file = iMSCP::File->new('filename' => '/etc/apache2/sites-available/00_master.conf');

	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read /etc/apache2/sites-available/00_master.conf");
		return 1;
	}

	if($action eq 'add') {
		$ownDdnsConfig = "# SECTION custom BEGIN.\n";
		$ownDdnsConfig .= "    RewriteEngine On\n";
		$ownDdnsConfig .= "    RewriteCond %{ENV:REDIRECT_STATUS} ^\$\n";
		$ownDdnsConfig .= "    RewriteCond %{REQUEST_URI} !^/.*ownddns\\.php\n";
		$ownDdnsConfig .= "    RewriteRule .* https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]\n";
		$ownDdnsConfig .= "    # SECTION custom END.\n";
		
		if ($fileContent =~ /# SECTION custom BEGIN.*# SECTION custom END\.\n/sgm && $main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'} eq 'https://') {
			$fileContent =~ s/# SECTION custom BEGIN.*# SECTION custom END\.\n/$ownDdnsConfig/sgm;
		}
	} elsif($action eq 'remove') {
		$ownDdnsConfig = "# SECTION custom BEGIN.\n";
		$ownDdnsConfig .= "    RewriteEngine On\n";
		$ownDdnsConfig .= "    RewriteRule .* https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]\n";
		$ownDdnsConfig .= "    # SECTION custom END.\n";
		
		if ($fileContent =~ /# SECTION custom BEGIN.*# SECTION custom END\.\n/sgm && $main::imscpConfig{'BASE_SERVER_VHOST_PREFIX'} eq 'https://') {
			$fileContent =~ s/# SECTION custom BEGIN.*# SECTION custom END\.\n/$ownDdnsConfig/sgm;
		}
	}

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _restartDaemonApache()

 Restart the apache daemon

 Return int 0 on success, other on failure

=cut

sub _restartDaemonApache
{
	require Servers::httpd;

	my $httpd = Servers::httpd->factory();

	$httpd->{'restart'} = 'yes';

	0;
}

=back

=head1 AUTHORS AND CONTRIBUTORS

 Sascha Bay <info@space2place.de>

=cut

1;
