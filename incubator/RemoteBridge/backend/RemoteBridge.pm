#!/usr/bin/perl

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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
#
# @category    i-MSCP
# @package     iMSCP_Plugin
# @subpackage  RemoteBridge
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Sascha Bay <info@space2place.de>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Plugin::RemoteBridge;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Database;
use parent 'Common::SingletonClass';

=item install()

 Perform install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;
	
	my $rs = 0;
	
	0;
}

=item uninstall()

 Perform un-installation tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;
	
	my $rs = 0;
	
	# Drop remote bridge table table
	my $rdata = iMSCP::Database->factory()->doQuery('dummy', 'DROP TABLE IF EXISTS `remote_bridge`');
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	0;
}

=item run()

 Process all scheduled actions according lists status

 Return int 0 on success, other on failure

=cut

sub run
{
	my $self = shift;
	
	my $rs = 0;
	
	my $rdata = iMSCP::Database->factory()->doQuery(
		'bridge_id',
		"
			SELECT
				*
			FROM
				`remote_bridge`
			WHERE
				`bridge_status` 
			IN
				('toadd', 'change', 'delete')
		"
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	if(%{$rdata}) {
		for(keys %{$rdata}) {
			if($rdata->{$_}->{'bridge_status'} eq 'toadd') {
				$rs = $self->_addRemoteBridge(
					$rdata->{$_}->{'bridge_key'}, $rdata->{$_}->{'bridge_ipaddress'});
				return $rs if $rs;
			} elsif($rdata->{$_}->{'bridge_status'} eq 'change') {
				$rs = $self->_updateRemoteBridge(
					$rdata->{$_}->{'bridge_key'}, $rdata->{$_}->{'bridge_ipaddress'});
				return $rs if $rs;
			} elsif($rdata->{$_}->{'bridge_status'} eq 'delete') {
				$rs = $self->_deleteRemoteBridge($rdata->{$_}->{'bridge_key'}, $rdata->{$_}->{'bridge_ipaddress'});
				return $rs if $rs;
			}
		}
	}

	0;
}

=item _addRemoteBridge($BridgeKey, $ServerIpaddress)

 Add the given remote bridge

 Return int 0 on success, other on failure

=cut

sub _addRemoteBridge
{
	my $self = shift;
	my $BridgeKey = shift;
	my $ServerIpaddress = shift;
	
	my $rs = 0;
	
	my $rdata = iMSCP::Database->factory()->doQuery(
		'dummy',
		"
			UPDATE 
				`remote_bridge` 
			SET 
				`bridge_status` = 'ok' 
			WHERE 
				`bridge_key` = ? 
			AND 
				`bridge_ipaddress` = ?
		",
			$BridgeKey, $ServerIpaddress
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	0;
}

=item _updateRemoteBridge($BridgeKey, $ServerIpaddress)

 Update the given remote bridge (Bridge key and Server ipaddress)

 Return int 0 on success, other on failure

=cut

sub _updateRemoteBridge
{
	my $self = shift;
	my $BridgeKey = shift;
	my $ServerIpaddress = shift;
	
	my $rs = 0;
	
	my $rdata = iMSCP::Database->factory()->doQuery(
		'dummy',
		"
			UPDATE 
				`remote_bridge`
			SET 
				`bridge_status` = 'ok' 
			WHERE 
				`bridge_key` = ? 
			AND 
				`bridge_ipaddress` = ?
		",
			$BridgeKey, $ServerIpaddress
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	0;
}

=item _deleteRemoteBridge($BridgeKey, $ServerIpaddress)

 Delete the given remote bridge

 Return int 0 on success, other on failure

=cut

sub _deleteRemoteBridge
{
	my $self = shift;
	my $BridgeKey = shift;
	my $ServerIpaddress = shift;
	
	my $rs = 0;

	my $rdata = iMSCP::Database->factory()->doQuery(
		'dummy', 
		"
			DELETE FROM
				`remote_bridge` 
			WHERE 
				`bridge_key` = ? 
			AND 
				`bridge_ipaddress` = ?
		",
			$BridgeKey, $ServerIpaddress
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	0;
}

1;
