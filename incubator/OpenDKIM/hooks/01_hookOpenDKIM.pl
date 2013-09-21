#!/usr/bin/perl

=head1 NAME

    Plugin::OpenDKIM
 
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
# @category i-MSCP
# @package iMSCP_Plugin
# @subpackage OpenDKIM
# @copyright 2010-2013 by i-MSCP | http://i-mscp.net
# @author Sascha Bay <info@space2place.de>
# @link http://i-mscp.net i-MSCP Home Site
# @license http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 
package Plugin::OpenDKIM;
 
use strict;
use warnings;
 
use iMSCP::Debug;
use iMSCP::HooksManager;
use iMSCP::Database;
 
my $hooksManager = iMSCP::HooksManager->getInstance();
 
=head1 DESCRIPTION
 
Plugin adds OpenDKIM support for i-MSCP panel
 
=head1 PUBLIC METHODS
 
=over 4
 
=item onAfterMtaBuildOpenDKIM
 
Adds the lines to the main.cf for OpenDKIM support
 
Return int 0
 
=cut
 
sub onAfterMtaBuildOpenDKIM
{
	my $fileContent = shift;
	
	my $postfixOpendkimConfig;	
	my $imscpDbName = $main::imscpConfig{'DATABASE_NAME'};
	
	my $db = iMSCP::Database->factory();
	
	$db->set('DATABASE_NAME', $imscpDbName);
	
	my $rs = $db->connect();
	if($rs) {
		error("Unable to connect to the i-MSCP '$imscpDbName' SQL database: $rs");
		return $rs if $rs;
	}

	my $rdata = $db->doQuery(
		'plugin_name', 'SELECT `plugin_name`, `plugin_config` FROM `plugin` WHERE `plugin_name` = ?', 'OpenDKIM'
	);
	
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}
	
	require JSON;
	JSON->import();
	
	my $opendkimConfig = decode_json($rdata->{'OpenDKIM'}->{'plugin_config'});
	
	if($$fileContent =~ /^smtpd_milters.*/gm) {
		if($opendkimConfig->{'opendkim_port'} =~ /\d{4,5}/ && $opendkimConfig->{'opendkim_port'} <= 65535) { #check the port is numeric and has min. 4 and max. 5 digits
			$postfixOpendkimConfig = " inet:localhost:" . $opendkimConfig->{'opendkim_port'};
		} else {
			$postfixOpendkimConfig = " inet:localhost:12345";
		}
			
		$$fileContent =~ s/^(smtpd_milters.*)/$1$postfixOpendkimConfig/gm;
	} else {	
		if($opendkimConfig->{'opendkim_port'} =~ /\d{4,5}/ && $opendkimConfig->{'opendkim_port'} <= 65535) { #check the port is numeric and has min. 4 and max. 5 digits
			$postfixOpendkimConfig = "\n# Start Added by Plugins::i-MSCP\n";
			$postfixOpendkimConfig .= "milter_default_action = accept\n";
			$postfixOpendkimConfig .= "smtpd_milters = inet:localhost:" .$opendkimConfig->{'opendkim_port'} ."\n";
			$postfixOpendkimConfig .= "non_smtpd_milters = \$smtpd_milters\n";
			$postfixOpendkimConfig .= "# Added by Plugins::i-MSCP End\n";
		} else {
			$postfixOpendkimConfig = "\n# Start Added by Plugins::i-MSCP\n";
			$postfixOpendkimConfig .= "milter_default_action = accept\n";
			$postfixOpendkimConfig .= "smtpd_milters = inet:localhost:12345\n";
			$postfixOpendkimConfig .= "non_smtpd_milters = \$smtpd_milters\n";
		}
		
		$$fileContent .= "$postfixOpendkimConfig";
	}
	
	0;
}
 
$hooksManager->register('afterMtaBuildMainCfFile', \&onAfterMtaBuildOpenDKIM);
 
=back
 
=head1 AUTHOR
 
Sascha Bay <info@space2place.de>
 
=cut
 
1;
