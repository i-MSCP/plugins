=head1 NAME

 Plugin::RoundcubePlugins

=cut

# i-MSCP - internet Multi Server Control Panel
# Copyright (C) 2013-2016 Rene Schuster <mail@reneschuster.de>
# Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>
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

package Plugin::RoundcubePlugins;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Debug;
use iMSCP::Database;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::TemplateParser;
use iMSCP::Rights;
use iMSCP::Service;
use Servers::cron;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP RoundcubePlugins plugin.

=head1 PUBLIC METHODS

=over 4

=item install()

 Perform install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	my $rs = $self->_checkRequirements();
	return $rs if $rs;

	$rs = $self->_installPlugins();
	return $rs if $rs;

	$rs = $self->_setPluginConfig('password', 'config.inc.php');
	return $rs if $rs;

	if($main::imscpConfig{'PO_SERVER'} eq 'dovecot') {
		$rs = $self->_setPluginConfig('managesieve', 'config.inc.php');
		return $rs if $rs;
	}

	$rs = $self->_setPluginConfig('newmail_notifier', 'config.inc.php');
	return $rs if $rs;

	$rs = $self->_setPluginConfig('rcguard', 'config.inc.php');
	return $rs if $rs;

	$self->_setPluginConfig('pop3fetcher', 'imscp_fetchmail.php');
}

=item uninstall()

 Perform uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	my $rs = $self->_removePluginFile('managesieve', 'config.inc.php');
	return $rs if $rs;

	$rs = $self->_removePluginFile('managesieve', 'imscp_default.sieve');
	return $rs if $rs;

	$rs = $self->_removePluginFile('newmail_notifier', 'config.inc.php');
	return $rs if $rs;

	$self->_removePlugins();
}

=item update()

 Perform update tasks

 Return int 0 on success, other on failure

=cut

sub update
{
	my $self = shift;

	$self->install();
}

=item change()

 Perform change tasks

 Return int 0 on success, other on failure

=cut

sub change
{
	my $self = shift;

	$self->install();
}

=item enable()

 Perform enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = shift;

	my $rs = $self->_checkRequirements();
	return $rs if $rs;

	$rs = $self->_setRoundcubePlugin('add');
	return $rs if $rs;

	if($main::imscpConfig{'PO_SERVER'} eq 'dovecot') {
		$self->_restartDaemonDovecot();
	}

	unless(defined $main::execmode && $main::execmode eq 'setup') {
		# Needed to flush opcode cache if any
		iMSCP::Service->getInstance()->restart('imscp_panel', 'defer');
	}

	0;
}

=item disable()

 Perform disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
	my $self = shift;

	my $rs = $self->_setRoundcubePlugin('remove');
	return $rs if $rs;

	$rs = $self->_unregisterCronjobPop3fetcher();
	return $rs if $rs;

	if($main::imscpConfig{'PO_SERVER'} eq 'dovecot') {
		$self->_restartDaemonDovecot();
	}

	unless(defined $main::execmode && $main::execmode eq 'setup') {
		# Needed to flush opcode cache if any
		iMSCP::Service->getInstance()->restart('imscp_panel', 'defer');
	}

	0;
}

=item fetchmail()

 Fetch emails from external pop3 accounts

 Return int 0 on success, other on failure

=cut

sub fetchmail
{
	my $fetchmail = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/plugins/pop3fetcher/imscp_fetchmail.php";

	my ($stdout, $stderr);
	my $rs = execute("php $fetchmail", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize plugin

 Return Plugin::RoundcubePlugins or die on failure

=cut

sub _init
{
	my $self = shift;

	# Force return value from plugin module
	$self->{'FORCE_RETVAL'} = 'yes';

	$self;
}

=item _installPlugins()

 Copy the plugins to the Roundcube Plugin folder

 Return int 0 on success, other on failure

=cut

sub _installPlugins
{
	my $roundcubePlugin = "$main::imscpConfig{'PLUGINS_DIR'}/RoundcubePlugins/roundcube-plugins";
	my $configPlugin = "$main::imscpConfig{'PLUGINS_DIR'}/RoundcubePlugins/config-templates";
	my $pluginFolder = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/plugins";

	my ($stdout, $stderr);
	my $rs = execute("cp -fR $roundcubePlugin/* $pluginFolder/", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$rs = execute("cp -fR $configPlugin/* $pluginFolder/", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	my $panelUName =
	my $panelGName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

	setRights($pluginFolder, {
		user => $panelUName, group => $panelGName, dirmode => '0550', filemode => '0440', recursive => 1
	});
}

=item _removePlugins()

 Remove the Plugins from the Roundcube Plugin folder

 Return int 0 on success, other on failure

=cut

sub _removePlugins
{
	my $pluginSourceDir = "$main::imscpConfig{'PLUGINS_DIR'}/RoundcubePlugins/roundcube-plugins";
	my $pluginTargetDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/plugins";

	my @pluginDirs = iMSCP::Dir->new( dirname => $pluginSourceDir )->getDirs();

	for my $basename(@pluginDirs) {
		my $rs = iMSCP::Dir->new( dirname => "$pluginTargetDir/$basename" )->remove();
		return $rs if $rs;
	}

	0;
}

=item _removePluginFile($plugin, $fileName)

 Remove Plugin config file

 Param string $plugin Plugin name
 Param string $fileName Name of file to remove
 Return int 0 on success, other on failure

=cut

sub _removePluginFile
{
	my ($self, $plugin, $fileName) = @_;

	my $filePath = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/plugins/$plugin/$fileName";

	if(-f $filePath) {
		my $rs = iMSCP::File->new( filename => $filePath )->delFile();
		return $rs if $rs;
	}

	0;
}

=item _setRoundcubePlugin($plugin, $action)

 Activate or deactivate the Roundcube Plugins

 Return int 0 on success, other on failure

=cut

sub _setRoundcubePlugin
{
	my ($self, $action) = @_;

	my $conffile = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/config/config.inc.php";
	my $file = iMSCP::File->new( filename => $conffile );
	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error(sprintf('Could not read %s', $conffile));
		return 1;
	}

	$fileContent = replaceBloc(
		"\n# Begin Plugin::RoundcubePlugins\n", "Ending Plugin::RoundcubePlugins\n", '', $fileContent
	);

	if($action eq 'add') {
		my @plugins = ();

		for my $plugin(
			'additional_message_headers_plugin', 'calendar_plugin', 'contextmenu_plugin', 'dkimstatus_plugin', 
			'emoticons_plugin', 'logon_page_plugin', 'newmail_notifier_plugin', 'odfviewer_plugin', 'password_plugin', 
			'pdfviewer_plugin', 'rcguard_plugin', 'tasklist_plugin', 'vcard_attachments_plugin', 'zipdownload_plugin'
		) {
			if($self->{'config'}->{$plugin} eq 'yes') {
				(my $realPluginName = $plugin) =~ s/_plugin$//;
				push @plugins, $realPluginName;
			}
		}

		if($self->{'config'}->{'archive_plugin'} eq 'yes') {
			push @plugins, 'archive';

			if($main::imscpConfig{'PO_SERVER'} eq 'dovecot') {
				my $rs = $self->_modifyDovecotConfig('archive', 'add');
				return $rs if $rs;
			}
		} elsif($main::imscpConfig{'PO_SERVER'} eq 'dovecot') {
			my $rs = $self->_modifyDovecotConfig('archive', 'remove');
			return $rs if $rs;
		}

		if($self->{'config'}->{'managesieve_plugin'} eq 'yes') {
			if($main::imscpConfig{'PO_SERVER'} eq 'dovecot') {
				my $rs = $self->_checkManagesieveRequirements();
				return $rs if $rs;

				push @plugins, 'managesieve';

				$rs = $self->_modifyDovecotConfig('managesieve', 'add');
				return $rs if $rs;
			} else {
				error('The managesieve plugin requires the Dovecot PO server.');
				return 1;
			}
		} elsif($main::imscpConfig{'PO_SERVER'} eq 'dovecot') {
			my $rs = $self->_modifyDovecotConfig('managesieve', 'remove');
			return $rs if $rs;
		}

		if($self->{'config'}->{'pop3fetcher_plugin'} eq 'yes') {
			push @plugins, 'pop3fetcher';

			my $rs = $self->_registerCronjobPop3fetcher();
			return $rs if $rs;
		} else {
			my $rs = $self->_unregisterCronjobPop3fetcher();
			return $rs if $rs;
		}

		my $roundcubePluginConfig = "\n# Begin Plugin::RoundcubePlugins\n";
		$roundcubePluginConfig .= '$config[\'plugins\'] = array_merge($config[\'plugins\'], array(' .
			"\n\t" . ( join ', ', map { qq/'$_'/ } @plugins ) . "\n));\n";
		$roundcubePluginConfig .= "# Ending Plugin::RoundcubePlugins\n";

		$fileContent .= $roundcubePluginConfig;
	} elsif($action eq 'remove') {
		if($main::imscpConfig{'PO_SERVER'} eq 'dovecot') {
			my $rs = $self->_modifyDovecotConfig('archive', 'remove');
			return $rs if $rs;
		
			$rs = $self->_modifyDovecotConfig('managesieve', 'remove');
			return $rs if $rs;
		}
	}

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _setPluginConfig($plugin, $fileName)

 Set the values in the Plugin config file config.inc.php

 Return int 0 on success, other on failure

=cut

sub _setPluginConfig
{
	my ($self, $plugin, $fileName) = @_;

	my $pluginFolder = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/plugins";
	my $file = iMSCP::File->new( filename => "$pluginFolder/$plugin/$fileName" );
	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error(sprintf('Could not read %s', $file->{'filename'}));
		return 1;
	}

	my $data = { };
	if($plugin eq 'managesieve') {
		$data = {
			managesieve_default => "$pluginFolder/$plugin/imscp_default.sieve",
			managesieve_vacation => $self->{'config'}->{'managesieve_config'}->{'managesieve_vacation'},
			managesieve_script_name => $self->{'config'}->{'managesieve_config'}->{'managesieve_script_name'}
		};
	} elsif($plugin eq 'newmail_notifier') {
		$data = {
			newmail_notifier_basic => $self->{'config'}->{'newmail_notifier_config'}->{'newmail_notifier_basic'},
			newmail_notifier_sound => $self->{'config'}->{'newmail_notifier_config'}->{'newmail_notifier_sound'},
			newmail_notifier_desktop => $self->{'config'}->{'newmail_notifier_config'}->{'newmail_notifier_desktop'}
		};
	} elsif($plugin eq 'password') {
		tie %{$self->{'ROUNDCUBE'}}, 'iMSCP::Config', fileName => "$main::imscpConfig{'CONF_DIR'}/roundcube/roundcube.data";

		(my $dbUser = $self->{'ROUNDCUBE'}->{'DATABASE_USER'}) =~ s%(')%\\$1%g;
		(my $dbPass = $self->{'ROUNDCUBE'}->{'DATABASE_PASSWORD'}) =~ s%(')%\\$1%g;

		$data = {
			password_confirm_current => $self->{'config'}->{'password_config'}->{'password_confirm_current'},
			password_minimum_length => $self->{'config'}->{'password_config'}->{'password_minimum_length'},
			password_require_nonalpha => $self->{'config'}->{'password_config'}->{'password_require_nonalpha'},
			password_force_new_user  => $self->{'config'}->{'password_config'}->{'password_force_new_user'},
			DB_NAME => $main::imscpConfig{'DATABASE_NAME'},
			DB_HOST => $main::imscpConfig{'DATABASE_HOST'},
			DB_PORT => $main::imscpConfig{'DATABASE_PORT'},
			DB_USER => $dbUser,
			DB_PASS => $dbPass
		};
	} elsif($plugin eq 'pop3fetcher') {
		$data = { IMSCP_DATABASE => $main::imscpConfig{'DATABASE_NAME'} };
	} elsif($plugin eq 'rcguard') {
		$data = {
			recaptcha_publickey => $self->{'config'}->{'rcguard_config'}->{'recaptcha_publickey'},
			recaptcha_privatekey => $self->{'config'}->{'rcguard_config'}->{'recaptcha_privatekey'},
			failed_attempts => $self->{'config'}->{'rcguard_config'}->{'failed_attempts'},
			expire_time => $self->{'config'}->{'rcguard_config'}->{'expire_time'},
			recaptcha_https => $self->{'config'}->{'rcguard_config'}->{'recaptcha_https'}
		};
	}

	my $rs = $file->set(process($data, $fileContent));
	return $rs if $rs;

	$file->save();
}

=item _checkManagesieveRequirements()

 Check the managesieve requirements

 Return int 0 on success, other on failure

=cut

sub _checkManagesieveRequirements
{
	my @reqPkgs = qw/dovecot-sieve dovecot-managesieved/;
	execute("dpkg-query --show --showformat '\${Package} \${status}\\n' @reqPkgs", \my $stdout, \my $stderr);
	my %instPkgs = map { /^([^\s]+).*\s([^\s]+)$/ && $1, $2 } split /\n/, $stdout;
	my $ret = 0;

	for my $reqPkg(@reqPkgs) {
		if($reqPkg ~~ [ keys  %instPkgs ]) {
			unless($instPkgs{$reqPkg} eq 'installed') {
				error(sprintf('The %s package is not installed on your system. Please install it.', $reqPkg));
				$ret ||= 1;
			}
		} else {
			error(sprintf('The %s package is not available on your system. Check your sources.list file.', $reqPkg));
			$ret ||= 1;
		}
	}

	$ret;
}

=item _modifyDovecotConfig($plugin, $action)

 Modify dovecot config file dovecot.conf

 Return int 0 on success, other on failure

=cut

sub _modifyDovecotConfig
{
	my ($self, $plugin, $action) = @_;

	# Get the Dovecot config file
	my $dovecotConfig = '/etc/dovecot/dovecot.conf';

	my $file = iMSCP::File->new( filename => $dovecotConfig );

	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error("Unable to read $dovecotConfig");
		return 1;
	}

	# check the Dovecot version
	my ($stdout, $stderr);
	my $rs = execute('/usr/sbin/dovecot --version', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr;
	error('Could not get Dovecot version. Is Dovecot installed?') if $rs && ! $stderr;
	return $rs if $rs;

	chomp($stdout);
	$stdout =~ m/^([0-9\.]+)\s*/;
	my $version = $1;

	unless($version) {
		error('Could not find Dovecot version');
		return 1;
	}

	if($plugin eq 'archive') {
		if(version->parse($version) > version->parse("2.1.0")) {
			if($action eq 'add') {
				$fileContent =~ s/\n\t# Begin Plugin::RoundcubePlugin::archive.*Ending Plugin::RoundcubePlugin::archive\n//sm;
				$fileContent =~ s/^(namespace\s+inbox\s+\{.*?)(^\})/$1\n\t# Begin Plugin::RoundcubePlugin::archive\n\tmailbox Archive \{\n\t\tauto = subscribe\n\t\tspecial_use = \\Archive\n\t\}\n\t# Ending Plugin::RoundcubePlugin::archive\n$2/sm;
			} elsif($action eq 'remove') {
				$fileContent =~ s/\n\t# Begin Plugin::RoundcubePlugin::archive.*Ending Plugin::RoundcubePlugin::archive\n//sm;
			}
		} else {
			if($action eq 'add') {
				$fileContent =~ s/^\t# Begin Plugin::RoundcubePlugin::archive::1st.*Ending Plugin::RoundcubePlugin::archive::1st\n//sm;
				$fileContent =~ s/^(plugin\s+\{)/$1\n\t# Begin Plugin::RoundcubePlugin::archive::1st\n\tautocreate = INBOX.Archive\n\tautosubscribe = INBOX.Archive\n\t# Ending Plugin::RoundcubePlugin::archive::1st/sm;
			
				$fileContent =~ s/^\t# Begin Plugin::RoundcubePlugin::archive::2nd.*(\tmail_plugins\s+=.*?)\s+autocreate\n\t# Ending Plugin::RoundcubePlugin::archive::2nd\n/$1\n/sm;
				$fileContent =~ s/^(protocol\s+imap.*?)(\tmail_plugins\s+=.*?)$/$1\t# Begin Plugin::RoundcubePlugin::archive::2nd\n$2 autocreate\n\t# Ending Plugin::RoundcubePlugin::archive::2nd/sm;
			} elsif($action eq 'remove') {
				$fileContent =~ s/^\t# Begin Plugin::RoundcubePlugin::archive::1st.*Ending Plugin::RoundcubePlugin::archive::1st\n//sm;
				$fileContent =~ s/^\t# Begin Plugin::RoundcubePlugin::archive::2nd.*(\tmail_plugins\s+=.*?)\s+autocreate\n\t# Ending Plugin::RoundcubePlugin::archive::2nd\n/$1\n/sm;
			}
		}
	} elsif($plugin eq 'managesieve') {
		if($action eq 'add') {
			$fileContent =~ s/^\t# Begin Plugin::RoundcubePlugin::managesieve::1st.*Ending Plugin::RoundcubePlugin::managesieve::1st\n//sgm;
			$fileContent =~ s/^(plugin\s+\{)/$1\n\t# Begin Plugin::RoundcubePlugin::managesieve::1st\n\tsieve = ~\/dovecot.sieve\n\t# Ending Plugin::RoundcubePlugin::managesieve::1st/sgm;
			
			$fileContent =~ s/^\t# Begin Plugin::RoundcubePlugin::managesieve::2nd.*(\tmail_plugins\s+=.*?)\s+sieve\n\t# Ending Plugin::RoundcubePlugin::managesieve::2nd\n/$1\n/sgm;
			$fileContent =~ s/^(protocol\s+lda.*?)(\tmail_plugins\s+=.*?)$/$1\t# Begin Plugin::RoundcubePlugin::managesieve::2nd\n$2 sieve\n\t# Ending Plugin::RoundcubePlugin::managesieve::2nd/sgm;

			if(version->parse($version) < version->parse("2.0.0")) {
				$fileContent =~ s/^# Begin Plugin::RoundcubePlugin::managesieve::3nd.*(protocols\s+=.*?)\s+managesieve.*Ending Plugin::RoundcubePlugin::managesieve::3nd\n/$1\n/sgm;
				$fileContent =~ s/^(protocols\s+=.*?)$/# Begin Plugin::RoundcubePlugin::managesieve::3nd\n$1 managesieve\n\nprotocol managesieve {\n\tlisten = localhost:4190\n}\n# Ending Plugin::RoundcubePlugin::managesieve::3nd/sgm;
			}
		} elsif($action eq 'remove') {
			$fileContent =~ s/^\t# Begin Plugin::RoundcubePlugin::managesieve::1st.*Ending Plugin::RoundcubePlugin::managesieve::1st\n//sgm;
			$fileContent =~ s/^\t# Begin Plugin::RoundcubePlugin::managesieve::2nd.*(\tmail_plugins\s*=.*?)\s+sieve\n\t# Ending Plugin::RoundcubePlugin::managesieve::2nd\n/$1\n/sgm;
			
			if(version->parse($version) < version->parse("2.0.0")) {
				$fileContent =~ s/^# Begin Plugin::RoundcubePlugin::managesieve::3nd.*(protocols\s+=.*?)\s+managesieve.*Ending Plugin::RoundcubePlugin::managesieve::3nd\n/$1\n/sgm;
			}
		}	
	}

	$rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _restartDaemonDovecot()

 Restart the Dovecot daemon

 Return int 0 on success, other on failure

=cut

sub _restartDaemonDovecot
{
	require Servers::po;

	Servers::po->factory()->{'restart'} = 'yes';

	0;
}

=item _registerCronjobPop3fetcher()

 Register pop3fetcher cronjob

 Return int 0 on success, other on failure

=cut

sub _registerCronjobPop3fetcher
{
	my $self = shift;

	my $filepath = "$main::imscpConfig{'PLUGINS_DIR'}/RoundcubePlugins/cronjob/cronjob_pop3fetcher.pl";

	my $file = iMSCP::File->new( filename => $filepath );

	my $fileContent = $file->get();
	unless (defined $fileContent) {
		error(sprintf('Could not read %s', $filepath));
		return 1;
	}

	my $rs = $file->set(
		process({ IMSCP_PERLLIB_PATH => $main::imscpConfig{'ENGINE_ROOT_DIR'} . '/PerlLib' }, $fileContent)
	);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	Servers::cron->factory()->addTask({
		TASKID => 'Plugin::RoundcubePlugins::pop3fetcher',
		MINUTE => $self->{'config'}->{'pop3fetcher_cronjob'}->{'minute'},
		HOUR => $self->{'config'}->{'pop3fetcher_cronjob'}->{'hour'},
		DAY => $self->{'config'}->{'pop3fetcher_cronjob'}->{'day'},
		MONTH => $self->{'config'}->{'pop3fetcher_cronjob'}->{'month'},
		DWEEK => $self->{'config'}->{'pop3fetcher_cronjob'}->{'dweek'},
		COMMAND => "nice -n 15 ionice -c2 -n5 perl $filepath >/dev/null 2>&1"
	});

	0;
}

=item _unregisterCronjobPop3fetcher()

 Unregister pop3fetcher cronjob

 Return int 0 on success, other on failure

=cut

sub _unregisterCronjobPop3fetcher
{
	Servers::cron->factory()->deleteTask({ TASKID => 'Plugin::RoundcubePlugins::pop3fetcher' });
}

=item _checkRequirements()

 Check for requirements

 Return int 0 on success, other on failure

=cut

sub _checkRequirements
{
	my $self = shift;

	if( !('Roundcube' ~~ [ split ',', $main::imscpConfig{'WEBMAIL_PACKAGES'} ]) ) {
		error('Roundcube is not installed. Please install it by running the imscp-autoinstall script.');
		return 1;
	}

	tie %{$self->{'ROUNDCUBE'}}, 'iMSCP::Config', fileName => "$main::imscpConfig{'CONF_DIR'}/roundcube/roundcube.data";

	my $roundcubeVersion = $self->{'ROUNDCUBE'}->{'ROUNDCUBE_VERSION'};

	if(version->parse($roundcubeVersion) < version->parse('1.1.0')) {
		error(sprintf('Your Roundcube version %s is not compatible with this plugin version.', $roundcubeVersion));
		return 1;
	}

	0;
}

=back

=head1 AUTHORS

 Rene Schuster <mail@reneschuster.de>
 Sascha Bay <info@space2place.de>

=cut

1;
__END__
