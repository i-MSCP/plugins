=head1 NAME

 Plugin::RecaptchaPMA

=cut

# i-MSCP RecaptchaPMA plugin
# Copyright (C) 2010-2016 by Sascha Bay
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

package Plugin::RecaptchaPMA;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::File;
use iMSCP::TemplateParser;
use iMSCP::Service;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provide the backend part of the RecaptchaPMA plugin.

=head1 PUBLIC METHODS

=over 4

=item enable()

 Process enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = shift;

	my $rs = $self->_pmaConfig('configure');
	return $rs if $rs;

	unless(defined $main::execmode && $main::execmode eq 'setup') {
		# Needed to flush opcode cache if any
		iMSCP::Service->getInstance()->restart('imscp_panel', 'defer');
	}

	0;
}

=item disable()

 Process disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
	my $self = shift;

	my $rs = $self->_pmaConfig('deconfigure');
	return $rs if $rs;

	unless(defined $main::execmode && $main::execmode eq 'setup') {
		# Needed to flush opcode cache if any
		iMSCP::Service->getInstance()->restart('imscp_panel', 'defer');
	}

	0;
}

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize plugin

 Return Plugin::RecaptchaPMA or die on failure

=cut

sub _init
{
	my $self = shift;

	if($self->{'action'} ~~ [ 'enable', 'disable', 'change', 'update' ]) {
		$self->{'FORCE_RETVAL'} = 'yes';
	}

	$self;
}

=item _pmaConfig($action)

 Configure or deconfigure PMA

 Param string $action Action to perform ( configure|deconfigure)
 Return int 0 on success, other on failure

=cut

sub _pmaConfig
{
	my ($self, $action) = @_;

	my $file = iMSCP::File->new( filename => "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/pma/config.inc.php" );
	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read $file->{'filename'} file");
		return 1;
	}

	if($action eq 'configure') {
		my $configSnippet = <<EOF;
# Begin Plugin::RecaptchaPMA
\$cfg['CaptchaLoginPublicKey'] = "$self->{'config'}->{'reCaptchaLoginPublicKey'}";
\$cfg['CaptchaLoginPrivateKey'] = "$self->{'config'}->{'reCaptchaLoginPrivateKey'}";
# Ending Plugin::RecaptchaPMA
EOF

		if(getBloc("# Begin Plugin::RecaptchaPMA\n", "# Ending Plugin::RecaptchaPMA\n", $fileContent) ne '') {
			$fileContent = replaceBloc(
				"# Begin Plugin::RecaptchaPMA\n",
				"# Ending Plugin::RecaptchaPMA\n",
				$configSnippet,
				$fileContent
			);
		} else {
			$fileContent .= $configSnippet;
		}
	} elsif($action eq 'deconfigure') {
		$fileContent = replaceBloc(
			"# Begin Plugin::RecaptchaPMA\n", "# Ending Plugin::RecaptchaPMA\n", '', $fileContent
		);
	}

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=back

=head1 AUTHORS

 Sascha Bay <info@space2place.de>

=cut

1;
__END__
