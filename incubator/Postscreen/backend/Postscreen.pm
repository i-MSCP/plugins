#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2014 by internet Multi Server Control Panel
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
# @category    iMSCP
# @package     iMSCP_Plugin
# @subpackage  Postscreen
# @copyright   Rene Schuster <mail@reneschuster.de>
# @author      Rene Schuster <mail@reneschuster.de>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Plugin::Postscreen;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Database;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::File;
use JSON;

use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP Postscreen plugin.

=head1 PUBLIC METHODS

=over 4

=item install()

 Perform install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	if(! -x '/usr/lib/postfix/postscreen') {
		error('Postfix version too low. The Postscreen features are available in Postfix 2.8 and later.');
		return 1;
	}

	my $rs = $self->change();
	return $rs if $rs;

	0;
}

=item change()

 Perform change tasks

 Return int 0 on success, other on failure

=cut

sub change
{
	my $self = shift;

	my $rs = $self->_patchMailgraph('add');
	return $rs if $rs;

	0;
}

=item update()

 Perform update tasks

 Return int 0 on success, other on failure

=cut

sub update
{
	my $self = shift;

	my $rs = $self->change();
	return $rs if $rs;

	0;
}

=item enable()

 Perform enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = shift;

	my $rs = $self->_modifyPostfixMainConfig('add');
	return $rs if $rs;

	$rs = $self->_modifyPostfixMasterConfig('add');
	return $rs if $rs;

	$rs = $self->_restartDaemonPostfix();
	return $rs if $rs;

	$rs = $self->_changeRoundcubeSmtpPort('add');
	return $rs if $rs;

	0;
}

=item disable()

 Perform disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
	my $self = shift;

	my $rs = $self->_modifyPostfixMainConfig('remove');
	return $rs if $rs;

	$rs = $self->_modifyPostfixMasterConfig('remove');
	return $rs if $rs;

	$rs = $self->_restartDaemonPostfix();
	return $rs if $rs;

	$rs = $self->_changeRoundcubeSmtpPort('remove');
	return $rs if $rs;

	0;
}

=item uninstall()

 Perform uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;
	
	my $rs = $self->_patchMailgraph('remove');
	return $rs if $rs;
	
	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize plugin

 Return Plugin::Postscreen

=cut

sub _init
{
	my $self = shift;

	# Force return value from plugin module
	$self->{'FORCE_RETVAL'} = 'yes';

	if($self->{'action'} ~~ ['install', 'change', 'update', 'enable', 'disable']) {
		# Loading plugin configuration
		my $rdata = iMSCP::Database->factory()->doQuery(
			'plugin_name', 'SELECT plugin_name, plugin_config FROM plugin WHERE plugin_name = ?', 'Postscreen'
		);
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}

		$self->{'config'} = decode_json($rdata->{'Postscreen'}->{'plugin_config'});
	}

	$self;
}

=item _modifyPostfixMainConfig($action)

 Modify postfix main.cf config file

 Return int 0 on success, other on failure

=cut

sub _modifyPostfixMainConfig($$)
{
	my ($self, $action) = @_;
	
	my $rs = 0;
	my $policyService;
	my $postscreenDnsblSites;
	my $postscreenAccessList;
	
	for(@{$self->{'config'}->{'postscreen_dnsbl_sites'}}) {
		if(! $postscreenDnsblSites) {
			$postscreenDnsblSites = $_;
		} else {
			$postscreenDnsblSites .= ",\n\t\t\t $_";
		}
	}
	
	for(@{$self->{'config'}->{'postscreen_access_list'}}) {
		if(! $postscreenAccessList) {
			$postscreenAccessList = $_;
		} else {
			$postscreenAccessList .= ",\n\t\t\t $_";
		}
		my $cidrFile = $_;
		if($cidrFile =~ /^cidr:/sgm) {
				$cidrFile =~ s/^(cidr:)(.*)/$2/gm;
				$rs = $self->_createPostscreenAccessFile($cidrFile);
				return $rs if $rs;
		}
	}
	
	my $file = iMSCP::File->new('filename' => '/etc/postfix/main.cf');
	
	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read /etc/postfix/main.cf");
		return 1;
	}
	
	$fileContent =~ s/^\s*check_policy_service inet:127.0.0.1:12525,\n//gm;
	$fileContent =~ s/^\s*check_policy_service inet:127.0.0.1:10023,\n//gm;
	
	if($self->{'config'}->{'disable_policyd-weight'} eq 'no') {
		$policyService .= "                               check_policy_service inet:127.0.0.1:12525,\n";
		$rs = $self->_servicePorts('show', 'PORT_POLICYD-WEIGHT');
		return $rs if $rs;
	} else {
		$rs = $self->_servicePorts('hide', 'PORT_POLICYD-WEIGHT');
		return $rs if $rs;
	}
	
	if($self->{'config'}->{'disable_postgrey'} eq 'no') {
		$policyService .= "                               check_policy_service inet:127.0.0.1:10023,\n";
		$rs = $self->_servicePorts('show', 'PORT_POSTGREY');
		return $rs if $rs;
	} else {
		$rs = $self->_servicePorts('hide', 'PORT_POSTGREY');
		return $rs if $rs;
	}
	
	my $postfixPostscreenConfig = "\n# Begin Plugin::Postscreen\n";
	$postfixPostscreenConfig .= "postscreen_greet_action = ". $self->{'config'}->{'postscreen_greet_action'} ."\n";
	$postfixPostscreenConfig .= "postscreen_dnsbl_sites = ". $postscreenDnsblSites ."\n";
	$postfixPostscreenConfig .= "postscreen_dnsbl_threshold = ". $self->{'config'}->{'postscreen_dnsbl_threshold'} ."\n";
	$postfixPostscreenConfig .= "postscreen_dnsbl_action = ". $self->{'config'}->{'postscreen_dnsbl_action'} ."\n";
	$postfixPostscreenConfig .= "postscreen_access_list = ". $postscreenAccessList ."\n";
	$postfixPostscreenConfig .= "postscreen_blacklist_action = ". $self->{'config'}->{'postscreen_blacklist_action'} ."\n";
	$postfixPostscreenConfig .= "# Ending Plugin::Postscreen\n";
	
	if($action eq 'add') {
		$fileContent =~ s/^\n# Begin Plugin::Postscreen.*Ending Plugin::Postscreen\n//sgm;
		$fileContent .= "$postfixPostscreenConfig";
	} 
	elsif($action eq 'remove') {
		$policyService  = "                               check_policy_service inet:127.0.0.1:12525,\n";
		$policyService .= "                               check_policy_service inet:127.0.0.1:10023,\n";
		$fileContent =~ s/^\n# Begin Plugin::Postscreen.*Ending Plugin::Postscreen\n//sgm;
		
		$rs = $self->_servicePorts('show', 'PORT_POLICYD-WEIGHT');
		return $rs if $rs;
		$rs = $self->_servicePorts('show', 'PORT_POSTGREY');
		return $rs if $rs;
	}
	
	$fileContent =~ s/(\s*reject_unlisted_recipient,\n)/$1$policyService/gm;
	
	my $rs = $file->set($fileContent);
	return $rs if $rs;
	
	$rs = $file->save();
	return $rs if $rs;
	
	0;
}

=item _modifyPostfixMasterConfig($action)

 Modify postfix master.cf config file

 Return int 0 on success, other on failure

=cut

sub _modifyPostfixMasterConfig($$)
{
	my ($self, $action) = @_;
	
	my $file = iMSCP::File->new('filename' => '/etc/postfix/master.cf');
	
	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read /etc/postfix/master.cf");
		return 1;
	}
	
	my $postfixPostscreenConfig = "# Begin Plugin::Postscreen\n";
	$postfixPostscreenConfig .= "smtp      inet  n       -       -       -       1       postscreen\n";
	$postfixPostscreenConfig .= "smtpd     pass  -       -       -       -       -       smtpd\n";
	$postfixPostscreenConfig .= "tlsproxy  unix  -       -       -       -       0       tlsproxy\n";
	$postfixPostscreenConfig .= "dnsblog   unix  -       -       -       -       0       dnsblog\n";
	$postfixPostscreenConfig .= "# Ending Plugin::Postscreen";
	
	if($action eq 'add') {
		$fileContent =~ s/^# Begin Plugin::Postscreen.*Ending Plugin::Postscreen//sgm;
		$fileContent =~ s/^smtp\s*inet\s*n\s*-\s*-\s*-\s*-\s*smtpd/$postfixPostscreenConfig/gm;
	} 
	elsif($action eq 'remove') {
		$postfixPostscreenConfig = "smtp      inet  n       -       -       -       -       smtpd";
		$fileContent =~ s/^# Begin Plugin::Postscreen.*Ending Plugin::Postscreen/$postfixPostscreenConfig/sgm;
	}
	
	my $rs = $file->set($fileContent);
	return $rs if $rs;
	
	$rs = $file->save();
	return $rs if $rs;
	
	0;
}

=item _patchMailgraph($action)

 Patch Mailgraph to show also the Postscreen reject graphs

 Return int 0 on success, other on failure

=cut

sub _patchMailgraph($$)
{
	my ($self, $action) = @_;
	
	my ($rs, $stdout, $stderr) = (0, undef, undef);
	
	if(-x '/usr/sbin/mailgraph') {
		if($action eq 'add') {
			if($self->{'config'}->{'patch_mailgraph'} eq 'yes' && ! -x '/usr/sbin/mailgraph_POSTSCREEN-PLUGIN') {
				$rs = execute("$main::imscpConfig{'CMD_CP'} /usr/sbin/mailgraph /usr/sbin/mailgraph_POSTSCREEN-PLUGIN", \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
				return $rs if $rs;
				
				$rs = execute("/usr/bin/patch -f /usr/sbin/mailgraph $main::imscpConfig{'GUI_ROOT_DIR'}/plugins/Postscreen/mailgraph/mailgraph_postscreen.patch", \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
				return $rs if $rs;
			} elsif($self->{'config'}->{'patch_mailgraph'} eq 'no' && -x '/usr/sbin/mailgraph_POSTSCREEN-PLUGIN') {
				$rs = execute("$main::imscpConfig{'CMD_MV'} /usr/sbin/mailgraph_POSTSCREEN-PLUGIN /usr/sbin/mailgraph", \$stdout, \$stderr);
				debug($stdout) if $stdout;
				error($stderr) if $stderr && $rs;
				return $rs if $rs;
			}
		}
		elsif($action eq 'remove' && -x '/usr/sbin/mailgraph_POSTSCREEN-PLUGIN') {
			$rs = execute("$main::imscpConfig{'CMD_MV'} /usr/sbin/mailgraph_POSTSCREEN-PLUGIN /usr/sbin/mailgraph", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		}
		
		$rs = $self->_restartDaemonMailgraph();
		return $rs if $rs;
	}
	
	0;
}

=item  _servicePorts($action, $service)

 Show or hide the service ports

 Return int 0 on success, other on failure

=cut

sub _servicePorts($$$)
{
	my ($self, $action, $service) = @_;
	
	my $newValue;
	
	my $rdata = iMSCP::Database->factory()->doQuery('name', 'SELECT `name`, `value` FROM `config` WHERE `name` = ?', $service);
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	my ($c1, $c2, $c3, $c4, $c5) = split(/;/, $rdata->{$service}->{'value'});
	
	if($action eq 'show') {
		$newValue = $c1.";".$c2.";".$c3.";1;".$c5;
	} 
	elsif($action eq 'hide') {
		$newValue = $c1.";".$c2.";".$c3.";0;".$c5;
	}
	my @sql = ('UPDATE `config` SET `value` = ? WHERE `name` = ?', $newValue, $service);
	my $rdata = iMSCP::Database->factory->doQuery('dummy', @sql);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	0;
}

=item _createPostscreenAccessFile($fileName)

 Create the Postscreen Access File

 Return int 0 on success, other on failure

=cut

sub _createPostscreenAccessFile($$)
{	
	my ($self, $fileName) = @_;
	
	if(! -e $fileName) {
		my $file = iMSCP::File->new('filename' => $fileName);
		
		my $fileContent = "# For more information please check man postscreen or:\n";
		$fileContent .= "# http://www.postfix.org/postconf.5.html#postscreen_access_list\n";
		$fileContent .= "#\n";
		$fileContent .= "# Rules are evaluated in the order as specified.\n";
		$fileContent .= "# Blacklist 192.168.* except 192.168.0.1\n";
		$fileContent .= "# 192.168.0.1         permit\n";
		$fileContent .= "# 192.168.0.0/16      reject\n";
		
		my $rs = $file->set($fileContent);
		return $rs if $rs;
		
		$rs = $file->save();
		return $rs if $rs;
		
		$rs = $file->mode(0644);
		return $rs if $rs;
	}
	
	0;
}

=item _changeRoundcubeSmtpPort($action)

 Change the SMTP port in Roundcube main.inc.php from 25 to 587

 Return int 0 on success, other on failure

=cut

sub _changeRoundcubeSmtpPort($$)
{
	my ($self, $action) = @_;

	my $roundcubeMainIncFile = "$main::imscpConfig{'GUI_ROOT_DIR'}/public/tools" . $main::imscpConfig{'WEBMAIL_PATH'} . "config/main.inc.php";
	my $file = iMSCP::File->new('filename' => $roundcubeMainIncFile);
	
	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read $roundcubeMainIncFile");
		return 1;
	}
	
	if($action eq 'add') {
		$fileContent =~ s/=\s+25;/= 587;/sgm;
	}
	elsif($action eq 'remove') {
		$fileContent =~ s/=\s+587;/= 25;/sgm;
	}
	
	my $rs = $file->set($fileContent);
	return $rs if $rs;
	
	$rs = $file->save();
	return $rs if $rs;
	
	0;
}

=item _restartDaemonPostfix()

 Restart the postfix daemon

 Return int 0 on success, other on failure

=cut

sub _restartDaemonPostfix
{
	my $self = shift;
	
    require Servers::mta;
    Servers::mta->factory()->{'restart'} = 'yes';
	
	0;
}

=item _restartDaemonMailgraph()

 Restart the Mailgraph daemon

 Return int 0 on success, other on failure

=cut

sub _restartDaemonMailgraph
{
	my $self = shift;
	
	my ($stdout, $stderr) = (undef, undef);
	my $rs = execute('service mailgraph restart', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;
	
	0;
}

=back

=head1 AUTHOR

 Rene Schuster <mail@reneschuster.de>

=cut

1;
