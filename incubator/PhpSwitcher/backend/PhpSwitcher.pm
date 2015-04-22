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
use iMSCP::Execute;
use iMSCP::File;
use Servers::httpd;
use Scalar::Defer;
use parent 'Common::SingletonClass';

my $phpVersions = lazy {
	my $rows = iMSCP::Database->factory()->doQuery(
		'version_id', 'SELECT version_id, version_version, version_binary_path, version_status FROM php_switcher_version'
	);
	(ref $rows eq 'HASH') or die($rows);
	$rows;
};

my $phpVersionsDomains = lazy {
	my $rows = iMSCP::Database->factory()->doQuery(
		'domain_name', 'SELECT version_id, domain_name FROM php_switcher_version_admin'
	);
	(ref $rows eq 'HASH') or die($rows);
	$rows;
};

my $defaultPhpBinaryPath = lazy { Servers::httpd->factory()->{'config'}->{'PHP_CGI_BIN'}; };
my $defaultPhpMajorVersion = lazy { Servers::httpd->factory()->{'config'}->{'PHP_VERSION'}; };

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
	my $self = shift;

	if(%{$phpVersions}) {
		my $panelUser =
		my $panelGroup = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

		for my $phpVersionId(keys %{$phpVersions}) {
			if($phpVersions->{$phpVersionId}->{'version_status'} ~~ [ 'toadd', 'tochange' ]) {
				my $phpVersion = undef;
				my $rs = 0;

				if(-x $phpVersions->{$phpVersionId}->{'version_binary_path'}) {
					# Find PHP version
					my ($stdout, $stderr);
					my $rs = execute("$phpVersions->{$phpVersionId}->{'version_binary_path'} -v", \$stdout, \$stderr);

					unless($stdout) {
						$rs = 1;
						error("Unable to find PHP version. No output.");
					} elsif($stdout !~ /PHP\s([\d.]+)/m) {
						$rs = 1;
						error(sprintf('Unable to find PHP version. Output was: %s', $stdout));
					} else {
						$phpVersion = $1;
					}

					unless($rs) {
						# Generate static phpinfo HTML file
						$rs = execute("$phpVersions->{$phpVersionId}->{'version_binary_path'} -q -i", \$stdout, \$stderr);
						error($stderr) if $rs;

						unless($rs) {
							my $file = iMSCP::File->new(
								filename => "$main::imscpConfig{'PLUGINS_DIR'}/PhpSwitcher/phpinfo/$phpVersionId.html"
							);

							$rs ||= $file->set($stdout);
							$rs ||= $file->save();
							$rs ||= $file->owner($panelUser, $panelGroup);
							$rs ||= $file->mode(0640);
						}
					}
				} else {
					error("$phpVersions->{$phpVersionId}->{'version_binary_path'} is not executable");
					$rs = 1;
				}

				my $qrs =iMSCP::Database->factory()->doQuery(
					'dummy',
					'UPDATE php_switcher_version SET version_version = ?, version_status = ? WHERE version_id = ?',
					$phpVersion,
					($rs ? scalar getMessageByType('error') : 'ok'),
					$phpVersionId
				);
				unless(ref $qrs eq 'HASH') {
					$self->{'FORCE_RETVAL'} = 1;
					error($qrs);
					return 1;
				}

				unless($rs) {
					$phpVersions->{$phpVersionId}->{'version_version'} = $phpVersion;
					$phpVersions->{$phpVersionId}->{'version_status'} = 'ok';
				}
			}
		}
	}

	$self->_registerListeners();
}

=back

=head1 EVENT LISTENERS

=over 4

=item overridePhpBinaryPath(\%data)

 Event listener which overrides the PHP binary path

 Param hash \%data Data as provided by the Alias|Domain|SubAlias|Subdomain modules
 Return int 0 on success, other on failure

=cut

sub overridePhpBinaryPath
{
	my $data = shift;

	my $domainName = $data->{'DOMAIN_NAME'};
	my $phpBinaryPath = force $defaultPhpBinaryPath;
	my $phpMajorVersion = force $defaultPhpMajorVersion;

	if(
		exists $phpVersionsDomains->{$domainName} &&
		exists $phpVersions->{$phpVersionsDomains->{$domainName}->{'version_id'}}->{'version_binary_path'} &&
		$phpVersions->{$phpVersionsDomains->{$domainName}->{'version_id'}}->{'version_status'} eq 'ok'
	) {
		my $phpVersion = $phpVersions->{$phpVersionsDomains->{$domainName}->{'version_id'}};
		$phpBinaryPath = $phpVersion->{'version_binary_path'};
		($phpMajorVersion) = $phpVersion->{'version_version'} =~ /^(\d)/;
	}

	my $httpdConfig = Servers::httpd->factory()->{'config'};
	my $httpdConfigObj = tied %{$httpdConfig};

	$httpdConfigObj->{'temporary'} = 1;

	$httpdConfig->{'PHP_CGI_BIN'} = $phpBinaryPath;
	debug(sprintf('PHP binary temporarily set to %s', $httpdConfig->{'PHP_CGI_BIN'}));

	$httpdConfig->{'PHP_VERSION'} = $phpMajorVersion;
	debug(sprintf('PHP version temporarily set to %s', $httpdConfig->{'PHP_VERSION'}));

	$httpdConfigObj->{'temporary'} = 0;

	0;
}

=item overridePhpIni($tplName, \$tplContent, \%data)

 Event listener which overrides PHP .ini file

 Param string $tplName Template file name
 Param string \$tplContent Template content
 Param hash \%data Data as provided by the Alias|Domain|SubAlias|Subdomain modules
 Return int 0

=cut

sub overridePhpIni
{
	my ($srvImpl, $tplName, $tplContent, $data) = @_;

	if($srvImpl eq 'apache_fcgid' && $tplName eq 'php.ini' && exists $phpVersionsDomains->{$data->{'DOMAIN_NAME'}}) {
		my $tplFilePath = "$main::imscpConfig{'CONF_DIR'}/fcgi/parts/php5/php.ini";
		# TODO override according PHP version in use
		$$tplContent = iMSCP::File->new( filename => $tplFilePath )->get();
		unless($$tplContent) {
			error(sprintf('Unable to read %s', $tplFilePath));
			return 1;
		}

		debug(sprintf('PHP ini template overridden by content from %s', $tplFilePath));
	}

	0;
}

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
		$self->{'eventManager'}->register('beforeHttpdBuildPhpConf', \&overridePhpBinaryPath);
		$self->{'eventManager'}->register('onLoadTemplate', \&overridePhpIni);
	} else {
		# Handle case where the administrator switched to another httpd implementation
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
