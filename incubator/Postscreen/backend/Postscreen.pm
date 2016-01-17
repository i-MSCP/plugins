=head1 NAME

 Plugin::Postscreen

=cut

# i-MSCP Postscreen plugin
# @copyright 2015-2016 Laurent Declercq <l.declercq@nuxwin.com>
# @copyright 2013-2016 Rene Schuster <mail@reneschuster.de>
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

package Plugin::Postscreen;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Database;
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::Execute;
use iMSCP::File;
use iMSCP::TemplateParser;
use version;
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
	unless(-x '/usr/lib/postfix/postscreen') {
		error('Your Postfix version is too old. Postscreen requires Postfix version 2.8 or more recent.');
		return 1;
	}

	0;
}

=item uninstall()

 Perform uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	$self->_postscreenAccessFile('remove');
}

=item update()

 Perform update tasks

 Return int 0 on success, other on failure

=cut

sub update
{
	my ($self, $fromVersion, $toVersion) = @_;

	if(version->parse($fromVersion) < version->parse("0.0.6")) {
		my $roundcubeConffile = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/config/config.inc.php";

		# Reset roundcube config.inc.php if any
		if(-f $roundcubeConffile) {
			my $file = iMSCP::File->new( filename => $roundcubeConffile );
			my $fileContent = $file->get();
			unless (defined $fileContent) {
				error("Unable to read $file->{'filename'} file");
				return 1;
			}

			$fileContent = replaceBloc(
				"// BEGIN Plugin::Postscreen\n", "// END Plugin::Postscreen\n", '', $fileContent
			);

			my $rs = $file->set($fileContent);
			return $rs if $rs;

			$rs = $file->save();
			return $rs if $rs;
		}

		require Servers::mta;
		my $mta = Servers::mta->factory();

		# Reset postfix main.cf file if any
		if(-f $mta->{'config'}->{'POSTFIX_CONF_FILE'}) {
			my $file = iMSCP::File->new( filename => $mta->{'config'}->{'POSTFIX_CONF_FILE'} );
			my $fileContent = $file->get();
			unless (defined $fileContent) {
				error("Unable to read $file->{'filename'} file");
				return 1;
			}

			$fileContent = replaceBloc(
				"// Begin Plugin::Postscreen\n", "// Ending Plugin::Postscreen\n", '', $fileContent
			);

			my $rs = $file->set($fileContent);
			return $rs if $rs;

			$rs = $file->save();
			return $rs if $rs;
		}

		# Reset postfix master.cf file if any
		if(-f $mta->{'config'}->{'POSTFIX_MASTER_CONF_FILE'}) {
			my $file = iMSCP::File->new( filename => $mta->{'config'}->{'POSTFIX_MASTER_CONF_FILE'} );
			my $fileContent = $file->get();
			unless (defined $fileContent) {
				error("Unable to read $file->{'filename'} file");
				return 1;
			}

			$fileContent = replaceBloc(
				"// Begin Plugin::Postscreen\n",
				"// Ending Plugin::Postscreen\n",
				'smtp      inet  n       -       -       -       -       smtpd\n',
				$fileContent
			);

			my $rs = $file->set($fileContent);
			return $rs if $rs;

			$rs = $file->save();
			return $rs if $rs;
		}
	}

	0;
}

=item enable()

 Perform enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = shift;

	my $rs = $self->_postfixMainCf('configure');
	return $rs if $rs;

	$rs = $self->_postfixMasterCf('configure');
	return $rs if $rs;

	$rs = $self->_postscreenAccessFile('add');
	return $rs if $rs;

	$rs = $self->_schedulePostfixRestart();
	return $rs if $rs;

	my @packages = split ',', $main::imscpConfig{'WEBMAIL_PACKAGES'};
	if('Roundcube' ~~ @packages) {
		$self->_roundcubeSmtpPort('configure');
	}
}

=item disable()

 Perform disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
	my $self = shift;

	my $rs = $self->_postfixMainCf('deconfigure');
	return $rs if $rs;

	$rs = $self->_postfixMasterCf('deconfigure');
	return $rs if $rs;

	$rs = $self->_schedulePostfixRestart();
	return $rs if $rs;

	if('Roundcube' ~~ [ split ',', $main::imscpConfig{'WEBMAIL_PACKAGES'} ]) {
		$self->_roundcubeSmtpPort('deconfigure');
	}

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _postfixMainCf($action)

 Modify postfix main.cf config file

 Param string $action Action to perform ( configure|deconfigure )
 Return int 0 on success, other on failure

=cut

sub _postfixMainCf
{
	my ($self, $action) = @_;

	require Servers::mta;
	my $mta = Servers::mta->factory();

	my $file = iMSCP::File->new( filename => $mta->{'config'}->{'POSTFIX_CONF_FILE'} );

	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read $file->{'filename'} file");
		return 1;
	}

	my $postscreenDnsblSites = join ",\n\t\t\t ", @{$self->{'config'}->{'postscreen_dnsbl_sites'}};
	my $postscreenAccessList = join ",\n\t\t\t ", @{$self->{'config'}->{'postscreen_access_list'}};

	if($action eq 'configure') {
		my $confSnippet = <<EOF;
# Plugin::Postscreen - Begin
postscreen_greet_action = $self->{'config'}->{'postscreen_greet_action'}
postscreen_dnsbl_sites = $postscreenDnsblSites
postscreen_dnsbl_threshold = $self->{'config'}->{'postscreen_dnsbl_threshold'}
postscreen_dnsbl_action = $self->{'config'}->{'postscreen_dnsbl_action'}
postscreen_access_list = $postscreenAccessList
postscreen_blacklist_action = $self->{'config'}->{'postscreen_blacklist_action'}
# Plugin::Postscreen - Ending
EOF

		if(getBloc("# Plugin::Postscreen - Begin\n", "# Plugin::Postscreen - Ending\n", $fileContent) ne '') {
			$fileContent = replaceBloc(
				"# Plugin::Postscreen - Begin\n", "# Plugin::Postscreen - Ending\n", $confSnippet, $fileContent
			);
		} else {
			$fileContent .= $confSnippet;
		}
	} else {
		$fileContent = replaceBloc(
			"# Plugin::Postscreen - Begin\n", "# Plugin::Postscreen - Ending\n", '', $fileContent
		);
	}

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _postfixMasterCf($action)

 Modify postfix master.cf config file

 Param string $action Action to perform (configure|deconfigure)
 Return int 0 on success, other on failure

=cut

sub _postfixMasterCf
{
	my ($self, $action) = @_;

	require Servers::mta;
	my $mta = Servers::mta->factory();

	my $file = iMSCP::File->new( filename => $mta->{'config'}->{'POSTFIX_MASTER_CONF_FILE'} );

	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read $file->{'filename'} file");
		return 1;
	}

	my $confSnippet = <<EOF;
# Plugin::Postscreen - Begin
smtp      inet  n       -       -       -       1       postscreen
smtpd     pass  -       -       -       -       -       smtpd
tlsproxy  unix  -       -       -       -       0       tlsproxy
dnsblog   unix  -       -       -       -       0       dnsblog
# Plugin::Postscreen - Ending
EOF

	if($action eq 'configure') {
		if(getBloc("# Plugin::Postscreen - Begin\n", "# Plugin::Postscreen - Ending\n", $fileContent) ne '') {
			$fileContent = replaceBloc(
				"# Plugin::Postscreen - Begin\n", "# Plugin::Postscreen - Ending\n", $confSnippet, $fileContent
			);
		} else {
			$fileContent .= $confSnippet;
		}
	} else {
		$fileContent = replaceBloc(
			"# Plugin::Postscreen - Begin\n", "# Plugin::Postscreen - Ending\n", '', $fileContent
		);
	}

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _postscreenAccessFile($action)

 Create or delete postscreen access files

 Param string $action Action to perform (add|remove)
 Return int 0 on success, other on failure

=cut

sub _postscreenAccessFile
{
	my ($self, $action) = @_;

	for(@{$self->{'config'}->{'postscreen_access_list'}}) {
		next unless /^cidr:/;

		(my $fileName = $_) =~ s/^cidr://;

		my $file = iMSCP::File->new( filename => $fileName );
		if($action eq 'add') {
			unless(-f $fileName) {
				my $fileContent = <<EOF;
# For more information please check man postscreen or
# http://www.postfix.org/postconf.5.html#postscreen_access_list
#
# Rules are evaluated in specified order.
# Blacklist 192.168.* except 192.168.0.1
# 192.168.0.1         permit
# 192.168.0.0/16      reject
EOF

				my $rs = $file->set($fileContent);
				return $rs if $rs;

				$rs = $file->save();
				return $rs if $rs;

				$rs = $file->mode(0644);
				return $rs if $rs;
			}
		} elsif(-f $fileName) {
			my $rs = $file->delFile();
			return $rs if $rs;
		}
	}

	0;
}

=item _roundcubeSmtpPort($action)

 Change the SMTP port in Roundcube configuration file

 Param string $action Action to perform ( configure|deconfigure )
 Return int 0 on success, other on failure

=cut

sub _roundcubeSmtpPort
{
	my ($self, $action) = @_;

	my $roundcubeMainIncFile = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/config/config.inc.php";

	my $file = iMSCP::File->new( filename => $roundcubeMainIncFile );

	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read $file->{'filename'} file");
		return 1;
	}

	if($action eq 'configure') {
		my $confSnippet = <<EOF;
# Plugin::Postscreen - Begin
\$config['smtp_port'] = 587;
# Plugin::Postscreen - Ending
EOF

		if(getBloc("# Plugin::Postscreen - Begin\n", "# Plugin::Postscreen - Ending\n", $fileContent) ne '') {
			$fileContent = replaceBloc(
				"# Plugin::Postscreen - Begin\n", "# Plugin::Postscreen - Ending\n", $confSnippet, $fileContent
			);
		} else {
			$fileContent .= $confSnippet;
		}
	} elsif($action eq 'deconfigure') {
		$fileContent = replaceBloc(
			"# Plugin::Postscreen - Begin\n", "# Plugin::Postscreen - Ending\n", '', $fileContent
		);
	}

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _schedulePostfixRestart()

 Restart the postfix daemon

 Return int 0 on success, other on failure

=cut

sub _schedulePostfixRestart
{
	require Servers::mta;
	Servers::mta->factory()->restart('defer');
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>
 Rene Schuster <mail@reneschuster.de>

=cut

1;
__END__
