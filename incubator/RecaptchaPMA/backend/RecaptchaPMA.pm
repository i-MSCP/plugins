#!/usr/bin/perl

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2010-2014 by Sascha Bay
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
# @subpackage  RecaptchaPMA
# @copyright   Sascha Bay <info@space2place.de>
# @author      Sascha Bay <info@space2place.de>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Plugin::RecaptchaPMA;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Database;
use iMSCP::Debug;
use iMSCP::File;
use JSON;
use version;

use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This plugin is designed to activate the reCAPTCHA for the phpMyAdmin login.

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

 Perform update tasks

 Return int 0 on success, other on failure

=cut

sub update
{
	my $self = $_[0];

	my $rs = $self->_modifyPmaConfig('add');
	return $rs if $rs;
}

=item enable()

 Process enable tasks

 Return int 0 on success, other on failure

=cut

sub enable()
{
	my $self = $_[0];

	my $rs = $self->_modifyPmaConfig('add');
	return $rs if $rs;
}

=item disable()

 Process disable tasks

 Return int 0 on success, other on failure

=cut

sub disable()
{
	my $self = $_[0];

	my $rs = $self->_modifyPmaConfig('remove');
	return $rs if $rs;
}

=item _modifyPmaConfig($action)

 Modify phpMyAdmin config file

 Return int 0 on success, other on failure

=cut

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize plugin

 Return Plugin::RecaptchaPMA

=cut

sub _init
{
	my $self = $_[0];

	# Force return value from plugin module
	$self->{'FORCE_RETVAL'} = 'yes';

	if($self->{'action'} ~~ ['install', 'change', 'update', 'enable', 'disable']) {
		# Loading plugin configuration
		my $rdata = iMSCP::Database->factory()->doQuery(
			'plugin_name', 'SELECT plugin_name, plugin_config FROM plugin WHERE plugin_name = ?', 'RecaptchaPMA'
		);
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}

		$self->{'config'} = decode_json($rdata->{'RecaptchaPMA'}->{'plugin_config'});
	}

	$self;
}

sub _modifyPmaConfig($$)
{
	my ($self, $action) = @_;
	my $recaptchaPmaConfig;

	my $pmaConfigFile = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/pma/config.inc.php";

	my $file = iMSCP::File->new('filename' => $pmaConfigFile);

	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read $main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/pma/config.inc.php");
		return 1;
	}

	if($action eq 'add') {
		$recaptchaPmaConfig = "\n# Begin Plugin::RecaptchaPMA\n";
		$recaptchaPmaConfig .= "\$cfg['CaptchaLoginPublicKey'] = '" . $self->{'config'}->{'reCaptchaLoginPublicKey'} . "';\n";
		$recaptchaPmaConfig .= "\$cfg['CaptchaLoginPrivateKey'] = '" . $self->{'config'}->{'reCaptchaLoginPrivateKey'} . "';\n";
		$recaptchaPmaConfig .= "# Ending Plugin::RecaptchaPMA\n";
		if ($fileContent =~ /# Begin Plugin::RecaptchaPMA.*# Ending Plugin::RecaptchaPMA\n/sgm) {
			$fileContent =~ s/\n# Begin Plugin::RecaptchaPMA.*# Ending Plugin::RecaptchaPMA\n/$recaptchaPmaConfig/sgm;
		} else {
			$fileContent .= $recaptchaPmaConfig;
		}
	} elsif($action eq 'remove') {
		$fileContent =~ s/\n# Begin Plugin::RecaptchaPMA.*# Ending Plugin::RecaptchaPMA\n//sgm;
	}

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=back

=head1 AUTHORS AND CONTRIBUTORS

 Sascha Bay <info@space2place.de>

=cut

1;
