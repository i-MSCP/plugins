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
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
#
# @category    i-MSCP
# @package     iMSCP_Plugin
# @subpackage  JailKit
# @copyright   2010-2013 by i-MSCP | http://i-mscp.net
# @author      Sascha Bay <info@space2place.de>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Plugin::JailKit;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::Database;

use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 Plugin allows i-MSCP accounts to create chroot ssh logins.

=head1 PUBLIC METHODS

=over 4

=item install()

 Perform install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	if(! -x '/usr/sbin/jk_init') {
		$self->_installJailKitPackage();
	}
	
	$self->_checkRequirements();
}

=item change()

 Perform change tasks

 Return int 0 on success, other on failure

=cut

sub change
{
	my $self = shift;
	
	0;
}

=item update()

 Perform update tasks

 Return int 0 on success, other on failure

=cut

sub update
{
	my $self = shift;
	
	0;
}

=item enable()

 Perform enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = shift;
	
	0;
}

=item disable()

 Perform disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
	my $self = shift;

	0;
}

=item uninstall()

 Perform uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;
	
	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery(
		'plugin_name', 'SELECT `plugin_name`, `plugin_config` FROM `plugin` WHERE `plugin_name` = ?', 'JailKit'
	);
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	require JSON;
	JSON->import();

	my $jailkitConfig = decode_json($rdata->{'JailKit'}->{'plugin_config'});
	
	my $rs = iMSCP::Dir->new('dirname' => $jailkitConfig->{'jailfolder'})->remove() if -d $jailkitConfig->{'jailfolder'};
	return $rs if $rs;
}

=item run()

 Create new entry for the jailkit

 Return int 0 on success, other on failure

=cut

sub run
{
	my $self = shift;
	
	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _restartDaemonJailKit()

 Restart the JailKit daemon

 Return int 0 on success, other on failure

=cut

sub _restartDaemonJailKit
{
	my $self = shift;
	
	my ($stdout, $stderr);
	
	my $rs = execute('service jailkit restart', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	return $rs if $rs;
}

=item _checkRequirements

 Check requirements for jailkit plugin

 Return int 0 if all requirements are meet, 1 otherwise

=cut

sub _checkRequirements
{
	my $self = shift;	
	
	my $db = iMSCP::Database->factory();

	my $rdata = $db->doQuery(
		'plugin_name', 'SELECT `plugin_name`, `plugin_config` FROM `plugin` WHERE `plugin_name` = ?', 'JailKit'
	);
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	require JSON;
	JSON->import();

	my $jailkitConfig = decode_json($rdata->{'JailKit'}->{'plugin_config'});
	
	if(! -d $jailkitConfig->{'jailfolder'}) {
		my $rs = iMSCP::Dir->new('dirname' => $jailkitConfig->{'jailfolder'})->make(
			{ 'user' => 'root', 'group' => 'root', 'mode' => 0750 }
		);
		return $rs if $rs;
	}
}

=item _installJailKitPackage

 Installs the debian jailkit package

 Return int 0 if all requirements are meet, 1 otherwise

=cut

sub _installJailKitPackage
{
	my $self = shift;
	
	my $rs = 0;
	my ($stdout, $stderr);
	
	my $jailKitAmd64 = $main::imscpConfig{'GUI_ROOT_DIR'} . '/plugins/JailKit/installation/jailkit_2.16-1_amd64.deb';
	my $jailKitI386 = $main::imscpConfig{'GUI_ROOT_DIR'} . '/plugins/JailKit/installation/jailkit_2.16-1_i386.deb';
	
	$rs = execute('/bin/uname -m', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	
	if($stdout =~ /^i\d+/) {
		$rs = execute('/usr/bin/dpkg -i ' . $jailKitI386, \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
	} else {
		$rs = execute('/usr/bin/dpkg -i ' . $jailKitAmd64, \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
	}
	
	$rs = execute("$main::imscpConfig{'CMD_CP'} -f $main::imscpConfig{'GUI_ROOT_DIR'}/plugins/JailKit/installation/jailkit-config/* /etc/jailkit/", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;
	
	$self->_restartDaemonJailKit();
}

=back

=head1 AUTHORS AND CONTRIBUTORS

 Sascha Bay <info@space2place.de>

=cut

1;
