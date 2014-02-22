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
# @subpackage  SpamAssassin
# @copyright   Sascha Bay <info@space2place.de>
# @copyright   Rene Schuster <mail@reneschuster.de>
# @author      Sascha Bay <info@space2place.de>
# @author      Rene Schuster <mail@reneschuster.de>
# @link        http://i-mscp.net i-MSCP Home Site
# @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2

package Plugin::SpamAssassin;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::Database;
use Servers::cron;
use version;
use JSON;

use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP SpamAssassin plugin.

=head1 PUBLIC METHODS

=over 4

=item install()

 Perform install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = $_[0];

	if(! -x '/usr/sbin/spamd') {
		error('Unable to find SpamAssassin daemon. Please, install the spamassassin packages first.');
		return 1;
	}

	if(! -x '/usr/sbin/spamass-milter') {
		error('Unable to find spamass-milter daemon. Please, install the spamass-milter package first.');
		return 1;
	}

	my $rs = $self->_checkVersion();
	return $rs if $rs;

	$rs = $self->_checkSaUser();
	return $rs if $rs;

	$rs = $self->_setupDatabase();
	return $rs if $rs;

	$self->change();
}

=item change()

 Perform change tasks

 Return int 0 on success, other on failure

=cut

sub change
{
	my $self = $_[0];

	my $rs = $self->_getSaDbPassword();
	return $rs if $rs;

	# SpamAssassin configuration
	$rs = $self->_updateSpamassassinRules();
	return $rs if $rs;

	$rs = $self->_setSpamassassinConfig('00_imscp.cf');
	return $rs if $rs;

	$rs = $self->_modifySpamassassinDefaultConfig('add');
	return $rs if $rs;

	$rs = $self->_setSpamassassinConfig('00_imscp.pre');
	return $rs if $rs;

	$rs = $self->_checkSpamassassinPlugins();
	return $rs if $rs;

	$rs = $self->_restartDaemon('spamassassin', 'restart');
	return $rs if $rs;

	# spamass-milter configuration
	$rs = $self->_modifySpamassMilterDefaultConfig('add');
	return $rs if $rs;

	$rs = $self->_restartDaemon('spamass-milter', 'restart');
	return $rs if $rs;

	# Roundcube Plugins configuration
	$rs = $self->_installRoundcubePlugins();
	return $rs if $rs;

	$rs = $self->_setRoundcubePluginConfig('sauserprefs');
	return $rs if $rs;

	$rs = $self->_setRoundcubePluginConfig('markasjunk2');
	return $rs if $rs;

	$self->_checkRoundcubePlugins();
}

=item update()

 Perform update tasks

 Return int 0 on success, other on failure

=cut

sub update
{
	$_[0]->change();
}

=item enable()

 Perform enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = $_[0];

	my $rs = $self->_checkVersion();
	return $rs if $rs;

	# Add Postfix configuration
	$rs = $self->_modifyPostfixMainConfig('add');
	return $rs if $rs;

	$self->_restartDaemonPostfix();
}

=item disable()

 Perform disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
	my $self = $_[0];

	# Remove cronjobs
	my $rs = $self->_unregisterCronjob('discover_razor');
	return $rs if $rs;
		
	$rs = $self->_unregisterCronjob('clean_awl_db');
	return $rs if $rs;
		
	$rs = $self->_unregisterCronjob('clean_bayes_db');
	return $rs if $rs;

	$rs = $self->_unregisterCronjob('bayes_sa-learn');
	return $rs if $rs;

	# Deactivate Roundcube Plugins
	$rs = $self->_setRoundcubePlugin('sauserprefs', 'remove');
	return $rs if $rs;

	$rs = $self->_setRoundcubePlugin('markasjunk2', 'remove');
	return $rs if $rs;

	# Remove Postfix configuration
	$rs = $self->_modifyPostfixMainConfig('remove');
	return $rs if $rs;

	$self->_restartDaemonPostfix();;
}

=item uninstall()

 Perform uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = $_[0];

	# Remove Roundcube Plugins
	my $rs = $self->_removeRoundcubePlugins();
	return $rs if $rs;

	# Remove spamass-milter configuration
	$rs = $self->_modifySpamassMilterDefaultConfig('remove');
	return $rs if $rs;

	$rs = $self->_restartDaemon('spamass-milter', 'restart');
	return $rs if $rs;	

	# Remove SpamAssassin configuration
	$rs = $self->_modifySpamassassinDefaultConfig('remove');
	return $rs if $rs;

	$rs = $self->_removeSpamassassinConfig();
	return $rs if $rs;

	$rs = $self->_setSpamassassinPlugin('DecodeShortURLs', 'remove');
	return $rs if $rs;

	$rs = $self->_setSpamassassinPlugin('iXhash2', 'remove');
	return $rs if $rs;

	$rs = $self->_restartDaemon('spamassassin', 'restart');
	return $rs if $rs;

	# Delete database user
	$self->_dropSaDatabaseUser();
}

=item discoverRazor()

 Create the Razor server list files.

 Return int 0 on success, other on failure

=cut

sub discoverRazor
{
	$_[0]->{'config'}->{'spamassassinOptions'} =~ m/username=(\S*)/;

	my ($stdout, $stderr);
	my $rs = execute("/bin/su $1 -c '/usr/bin/razor-admin -discover'", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	0;
}

=item cleanAwlDb()

 Check and clean the SpamAssassin auto-whitelist (AWL) database.

 Return int 0 on success, other on failure

=cut

sub cleanAwlDb
{
	my $rdata = iMSCP::Database->factory()->doQuery(
		'dummy', "DELETE FROM `$main::imscpConfig{'DATABASE_NAME'}_spamassassin`.`awl` WHERE (count=1 AND last_update<DATE_SUB(NOW(), INTERVAL 1 WEEK)) OR (last_update<DATE_SUB(NOW(), INTERVAL 1 MONTH))"
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	0;
}

=item cleanBayesDb()

 Expire old tokens from the bayes database.
 
 It cleans the database only when the number of tokens
 surpasses the bayes_expiry_max_db_size value.

 Return int 0 on success, other on failure

=cut

sub cleanBayesDb
{
	my ($stdout, $stderr);
	my $rs = execute("/usr/bin/sa-learn --force-expire", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs;
}

=item bayesSaLearn()

 Train SpamAssassin's Bayesian classifier with spam and ham reported by the users.

 Return int 0 on success, other on failure

=cut

sub bayesSaLearn
{
	my $saLearnDir = "$main::imscpConfig{'GUI_ROOT_DIR'}/plugins/SpamAssassin/sa-learn/";

	foreach my $saFile (glob($saLearnDir . "*")) {
		$saFile =~ /^($saLearnDir)(.*)__(spam|ham)__(.*)/;

		my ($stdout, $stderr);
		my $rs = execute("/usr/bin/sa-learn --$3 --username=$2 $saFile", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		$rs = execute("$main::imscpConfig{'CMD_RM'} -f $saFile", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize plugin

 Return Plugin::SpamAssassin

=cut

sub _init
{
	my $self = $_[0];

	# Force return value from plugin module
	$self->{'FORCE_RETVAL'} = 'yes';

	# Set SpamAssassin database user
	$self->{'SA_DATABASE_USER'} ='sa_user';

	# Set SpamAssassin host
	if($main::imscpConfig{'DATABASE_HOST'} eq 'localhost') {
		$self->{'SA_HOST'} = 'localhost'
	} else {
		$self->{'SA_HOST'} = $main::imscpConfig{'BASE_SERVER_IP'};
	}

	if($self->{'action'} ~~ ['install', 'change', 'update', 'enable']) {
		# Loading plugin configuration
		my $rdata = iMSCP::Database->factory()->doQuery(
			'plugin_name', 'SELECT plugin_name, plugin_config FROM plugin WHERE plugin_name = ?', 'SpamAssassin'
		);
		unless(ref $rdata eq 'HASH') {
			error($rdata);
			return 1;
		}

		$self->{'config'} = decode_json($rdata->{'SpamAssassin'}->{'plugin_config'});
	}

	$self;
}

=item _updateSpamassassinRules()

 Update the SpamAssassin filter rules and keys.

 Return int 0 on success, other on failure

=cut

sub _updateSpamassassinRules
{
	my $self = $_[0];

	$self->{'config'}->{'spamassassinOptions'} =~ m/helper-home-dir=(\S*)/;
	my $helperHomeDir = $1;

	$self->{'config'}->{'spamassassinOptions'} =~ m/username=(\S*)/;
	my $saUser = $1;

	my ($stdout, $stderr);
	my $rs = execute("/bin/su $saUser -c '/usr/bin/sa-update --gpghomedir $helperHomeDir/sa-update-keys'", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs >= 4;

	$rs = execute("/bin/su $saUser -c '/usr/bin/sa-compile --quiet'", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$rs = execute("$main::imscpConfig{'CMD_CHMOD'} -R go-w,go+rX $helperHomeDir/compiled", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs;
}

=item _discoverPyzor()

 Create Pyzor home folder and discover the servers.

 Return int 0 on success, other on failure

=cut

sub _discoverPyzor
{
	$_[0]->{'config'}->{'spamassassinOptions'} =~ m/username=(\S*)/;

	my ($stdout, $stderr);
	my $rs = execute("/bin/su $1 -c '/usr/bin/pyzor discover'", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs;
}

=item _createRazor()

 Create Razor home folder and registers a new identity.

 Return int 0 on success, other on failure

=cut

sub _createRazor
{
	$_[0]->{'config'}->{'spamassassinOptions'} =~ m/username=(\S*)/;

	my ($stdout, $stderr);
	my $rs = execute("/bin/su $1 -c '/usr/bin/razor-admin -create'", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$rs = execute("/bin/su $1 -c '/usr/bin/razor-admin -register'", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs;
}

=item _modifySpamassMilterDefaultConfig($action)

 Modify spamass-milter default config file.

 Return int 0 on success, other on failure

=cut

sub _modifySpamassMilterDefaultConfig($$)
{
	my ($self, $action) = @_;

	my $file = iMSCP::File->new('filename' => '/etc/default/spamass-milter');

	my $fileContent = $file->get();
	unless(defined $fileContent) {
		error("Unable to read /etc/default/spamass-milter");
		return 1;
	}

	if($action eq 'add') {
		my $spamassMilterOptions = $self->{'config'}->{'spamassMilterOptions'};
		my $milterSocket = $self->{'config'}->{'spamassMilterSocket'};
		
		if($self->{'config'}->{'reject_spam'} eq 'yes') {
			$spamassMilterOptions .= ' -r -1';
		}
		
		$self->{'config'}->{'spamassassinOptions'} =~ m/port=(\d+)/;

		if($1 ne '783') {
			$spamassMilterOptions .= ' -- -p ' . $1;
		}
		
		$fileContent =~ s/^OPTIONS=.*/OPTIONS="$spamassMilterOptions"/gm;
		$fileContent =~ s/.*SOCKET=.*/SOCKET="$milterSocket"/gm;
	} elsif($action eq 'remove') {
		$fileContent =~ s/^OPTIONS=.*/OPTIONS="-u spamass-milter -i 127.0.0.1"/gm;
		$fileContent =~ s%^SOCKET=.*%# SOCKET="/var/spool/postfix/spamass/spamass.sock"%gm;
	}

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _modifySpamassassinDefaultConfig($action)

 Modify SpamAssassin default config file.

 Return int 0 on success, other on failure

=cut

sub _modifySpamassassinDefaultConfig($$)
{
	my ($self, $action) = @_;

	my $file = iMSCP::File->new('filename' => '/etc/default/spamassassin');

	my $fileContent = $file->get();
	unless(defined $fileContent) {
		error("Unable to read /etc/default/spamassassin");
		return 1;
	}

	if($action eq 'add') {
		my $spamassassinOptions = $self->{'config'}->{'spamassassinOptions'};
		
		$fileContent =~ s/^ENABLED=.*/ENABLED=1/gm;
		$fileContent =~ s/^OPTIONS=.*/OPTIONS="$spamassassinOptions"/gm;
		$fileContent =~ s/^CRON=.*/CRON=1/gm;
	} elsif($action eq 'remove') {
		my $spamassassinOptions = "--create-prefs --max-children 5 --helper-home-dir";
		
		$fileContent =~ s/^ENABLED=.*/ENABLED=0/gm;
		$fileContent =~ s/^OPTIONS=.*/OPTIONS="$spamassassinOptions"/gm;
		$fileContent =~ s/^CRON=.*/CRON=0/gm;
	}

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _modifyPostfixMainConfig($action)

 Modify postfix main.cf config file.

 Return int 0 on success, other on failure

=cut

sub _modifyPostfixMainConfig($$)
{
	my ($self, $action) = @_;

	my $file = iMSCP::File->new('filename' => '/etc/postfix/main.cf');

	my $fileContent = $file->get();
	unless(defined $fileContent) {
		error("Unable to read /etc/postfix/main.cf");
		return 1;
	}

	my ($stdout, $stderr);
	my $rs = execute('postconf smtpd_milters', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	if($action eq 'add') {
		$stdout =~ /^smtpd_milters\s?=\s?(.*)/gm;
		my @miltersValues = split(' ', $1);

		my $milterSocket = $self->{'config'}->{'spamassMilterSocket'};
		$milterSocket =~ s%/var/spool/postfix(.*)%$1%sgm;

		if(scalar @miltersValues >= 1) {
			$fileContent =~ s/^\t# Begin Plugin::SpamAssassin.*Ending Plugin::SpamAssassin\n//sgm;
			$fileContent =~ s/^# Begin Plugin::SpamAssassin::Macros.*Ending Plugin::SpamAssassin::Macros\n//sgm;
			
			my $postfixSpamassassinConfig = "\n\t# Begin Plugin::SpamAssassin\n";
			$postfixSpamassassinConfig .= "\tunix:" . $milterSocket . "\n";
			$postfixSpamassassinConfig .= "\t# Ending Plugin::SpamAssassin";
			
			my $milterConnectMacros = "\n# Begin Plugin::SpamAssassin::Macros\n";
			$milterConnectMacros .= "milter_connect_macros = j {daemon_name} v {if_name} _\n";
			$milterConnectMacros .= "# Ending Plugin::SpamAssassin::Macros";
			
			if($fileContent =~ /# Ending Plugin::ClamAV/gm) {
				$fileContent =~ s/(# Ending Plugin::ClamAV.*)/$1$postfixSpamassassinConfig/gm;
			} else {
				$fileContent =~ s/^(smtpd_milters.*)/$milterConnectMacros$1/gm;
			}
			
			$fileContent =~ s/^(non_smtpd_milters.*)/$1$milterConnectMacros/gm;
		} else {
			my $postfixSpamassassinConfig = "\n# Begin Plugins::i-MSCP\n";
			$postfixSpamassassinConfig .= "milter_default_action = accept\n";
			$postfixSpamassassinConfig .= "smtpd_milters = \n";
			$postfixSpamassassinConfig .= "\t# Begin Plugin::SpamAssassin\n";
			$postfixSpamassassinConfig .= "\tunix:" . $milterSocket . "\n";
			$postfixSpamassassinConfig .= "\t# Ending Plugin::SpamAssassin\n";
			$postfixSpamassassinConfig .= "non_smtpd_milters = \$smtpd_milters\n";
			$postfixSpamassassinConfig .= "# Begin Plugin::SpamAssassin::Macros\n";
			$postfixSpamassassinConfig .= "milter_connect_macros = j {daemon_name} v {if_name} _\n";
			$postfixSpamassassinConfig .= "# Ending Plugin::SpamAssassin::Macros\n";
			$postfixSpamassassinConfig .= "# Ending Plugins::i-MSCP\n";
			
			$fileContent .= "$postfixSpamassassinConfig";
		}
	} elsif($action eq 'remove') {
		$stdout =~ /^smtpd_milters\s*=\s*(.*)/gm;
		my @miltersValues = split(' ', $1);
		
		if(scalar @miltersValues > 1) {
			$fileContent =~ s/^\t# Begin Plugin::SpamAssassin.*Ending Plugin::SpamAssassin\n//sgm;
			$fileContent =~ s/^# Begin Plugin::SpamAssassin::Macros.*Ending Plugin::SpamAssassin::Macros\n//sgm;
		}
		elsif($fileContent =~ /^\t# Begin Plugin::SpamAssassin.*Ending Plugin::SpamAssassin\n/sgm) {
			$fileContent =~ s/^\n# Begin Plugins::i-MSCP.*Ending Plugins::i-MSCP\n//sgm;
		}
	}

	$rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _restartDaemon($daemon, $action)

 Restart the daemon

 Return int 0 on success, other on failure

=cut

sub _restartDaemon($$$)
{
	my ($self, $daemon, $action) = @_;

	my ($stdout, $stderr);
	my $rs = execute("service $daemon $action", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs;
}

=item _restartDaemonPostfix()

 Restart the postfix daemon.

 Return int 0 on success, other on failure

=cut

sub _restartDaemonPostfix
{
	require Servers::mta;
	Servers::mta->factory()->{'restart'} = 'yes';

	0;
}

=item _registerCronjob($cronjobName)

 Register cronjob.

 Return int 0 on success, other on failure

=cut

sub _registerCronjob($$)
{
	my ($self, $cronjobName) = @_;

	my $cronjobFilePath = "$main::imscpConfig{'GUI_ROOT_DIR'}/plugins/SpamAssassin/cronjobs/cronjob_$cronjobName.pl";

	my $cronjobFile = iMSCP::File->new('filename' => $cronjobFilePath);

	my $cronjobFileContent = $cronjobFile->get();
	unless(defined $cronjobFileContent) {
		error("Unable to read $cronjobFileContent");
		return 1;
	}

	require iMSCP::TemplateParser;
	iMSCP::TemplateParser->import();

	$cronjobFileContent = process(
		{ 'IMSCP_PERLLIB_PATH' => $main::imscpConfig{'ENGINE_ROOT_DIR'} . '/PerlLib' },
		$cronjobFileContent
	);

	my $rs = $cronjobFile->set($cronjobFileContent);
	return $rs if $rs;

	$rs = $cronjobFile->save();
	return $rs if $rs;

	if($cronjobName eq 'bayes_sa-learn') {
		Servers::cron->factory()->addTask(
			{
				'TASKID' => 'Plugin::SpamAssassin::BayesSaLearn',
				'MINUTE' => '*/5',
				'HOUR' => '*',
				'DAY' => '*',
				'MONTH' => '*',
				'DWEEK' => '*',
				'COMMAND' => "$main::imscpConfig{'CMD_PERL'} $cronjobFilePath >/dev/null 2>&1"
			}
		);
	} elsif($cronjobName eq 'clean_bayes_db') {
		Servers::cron->factory()->addTask(
			{
				'TASKID' => 'Plugin::SpamAssassin::CleanBayesDb',
				'MINUTE' => $self->{'config'}->{'cronjob_clean_bayes_db'}->{'minute'},
				'HOUR' => $self->{'config'}->{'cronjob_clean_bayes_db'}->{'hour'},
				'DAY' => $self->{'config'}->{'cronjob_clean_bayes_db'}->{'day'},
				'MONTH' => $self->{'config'}->{'cronjob_clean_bayes_db'}->{'month'},
				'DWEEK' => $self->{'config'}->{'cronjob_clean_bayes_db'}->{'dweek'},
				'COMMAND' => "$main::imscpConfig{'CMD_PERL'} $cronjobFilePath >/dev/null 2>&1"
			}
		);
	} elsif($cronjobName eq 'clean_awl_db') {
		Servers::cron->factory()->addTask(
			{
				'TASKID' => 'Plugin::SpamAssassin::CleanAwlDb',
				'MINUTE' => $self->{'config'}->{'cronjob_clean_awl_db'}->{'minute'},
				'HOUR' => $self->{'config'}->{'cronjob_clean_awl_db'}->{'hour'},
				'DAY' => $self->{'config'}->{'cronjob_clean_awl_db'}->{'day'},
				'MONTH' => $self->{'config'}->{'cronjob_clean_awl_db'}->{'month'},
				'DWEEK' => $self->{'config'}->{'cronjob_clean_awl_db'}->{'dweek'},
				'COMMAND' => "$main::imscpConfig{'CMD_PERL'} $cronjobFilePath >/dev/null 2>&1"
			}
		);
	} elsif($cronjobName eq 'discover_razor') {
		Servers::cron->factory()->addTask(
			{
				'TASKID' => 'Plugin::SpamAssassin::DiscoverRazor',
				'MINUTE' => '@weekly',
				'HOUR' => '',
				'DAY' => '',
				'MONTH' => '',
				'DWEEK' => '',
				'COMMAND' => "$main::imscpConfig{'CMD_PERL'} $cronjobFilePath >/dev/null 2>&1"
			}
		);
	}

	0;
}

=item _unregisterCronjob($cronjobName)

 Unregister cronjob.

 Return int 0 on success, other on failure

=cut

sub _unregisterCronjob($$)
{
	my ($self, $cronjobName) = @_;

	if($cronjobName eq 'bayes_sa-learn') {
		Servers::cron->factory()->deleteTask({ 'TASKID' => 'Plugin::SpamAssassin::BayesSaLearn' });
	} elsif($cronjobName eq 'clean_bayes_db') {
		Servers::cron->factory()->deleteTask({ 'TASKID' => 'Plugin::SpamAssassin::CleanBayesDb' });
	} elsif($cronjobName eq 'clean_awl_db') {
		Servers::cron->factory()->deleteTask({ 'TASKID' => 'Plugin::SpamAssassin::CleanAwlDb' });
	} elsif($cronjobName eq 'discover_razor') {
		Servers::cron->factory()->deleteTask({ 'TASKID' => 'Plugin::SpamAssassin::DiscoverRazor' });
	}

	0;
}

=item _setSpamassassinConfig($saFile)

 Copy the SpamAssassin config files and set the values.

 Return int 0 if all requirements are meet, 1 otherwise

=cut

sub _setSpamassassinConfig($$)
{
	my ($self, $saFile) = @_;

	my ($stdout, $stderr);
	my $rs = execute("$main::imscpConfig{'CMD_CP'} -f $main::imscpConfig{'GUI_ROOT_DIR'}/plugins/SpamAssassin/config-templates/spamassassin/$saFile /etc/spamassassin/", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	my $file = iMSCP::File->new('filename' => "/etc/spamassassin/$saFile");

	my $fileContent = $file->get();
	unless(defined $fileContent) {
		error("Unable to read /etc/spamassassin/$saFile");
		return 1;
	}

	if($saFile eq '00_imscp.cf') {
		$fileContent =~ s/\{DATABASE_HOST\}/$main::imscpConfig{'DATABASE_HOST'}/g;
		$fileContent =~ s/\{DATABASE_PORT\}/$main::imscpConfig{'DATABASE_PORT'}/g;
		$fileContent =~ s/\{SA_DATABASE_NAME\}/$main::imscpConfig{'DATABASE_NAME'}_spamassassin/g;
		$fileContent =~ s/\{SA_DATABASE_USER\}/$self->{'SA_DATABASE_USER'}/g;
		$fileContent =~ s/\{SA_DATABASE_PASSWORD\}/$self->{'SA_DATABASE_PASSWORD'}/g;

		if($self->{'config'}->{'site-wide_bayes'} eq 'yes') {
			$fileContent =~ s/^#bayes_sql_override_username/bayes_sql_override_username/gm;
			$fileContent =~ s/^#bayes_auto_expire/bayes_auto_expire/gm;
		} else {
			$fileContent =~ s/^bayes_sql_override_username/#bayes_sql_override_username/gm;
			$fileContent =~ s/^bayes_auto_expire/#bayes_auto_expire/gm;
		}
	} elsif($saFile eq '00_imscp.pre') {
		if($self->{'config'}->{'use_lang_check'} eq 'yes') {
			$fileContent =~ s/^#loadplugin Mail::SpamAssassin::Plugin::TextCat/loadplugin Mail::SpamAssassin::Plugin::TextCat/gm;
		} else {
			$fileContent =~ s/^loadplugin Mail::SpamAssassin::Plugin::TextCat/#loadplugin Mail::SpamAssassin::Plugin::TextCat/gm;
		}
	}

	$rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$file->mode(0644);
}

=item _removeSpamassassinConfig()

 Remove SpamAssassin config files.

 Return int 0 on success, other on failure

=cut

sub _removeSpamassassinConfig
{
	my ($stdout, $stderr);
	my $rs = execute("$main::imscpConfig{'CMD_RM'} -f /etc/spamassassin/00_imscp.*", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;

	$rs;
}

=item _installRoundcubePlugins()

 Copy the plugins to the Roundcube Plugin folder.

 Return int 0 on success, other on failure

=cut

sub _installRoundcubePlugins()
{
	my $roundcubePlugin = "$main::imscpConfig{'GUI_ROOT_DIR'}/plugins/SpamAssassin/roundcube-plugins";
	my $pluginFolder = "$main::imscpConfig{'GUI_ROOT_DIR'}/public/tools" . $main::imscpConfig{'WEBMAIL_PATH'} . "plugins";

	my ($stdout, $stderr);
	my $rs = execute("$main::imscpConfig{'CMD_CP'} -fR $roundcubePlugin/* $pluginFolder/", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $panelGName = $panelUName;

	require iMSCP::Rights;
	iMSCP::Rights->import();
	setRights(
		"$pluginFolder/",
		{ 'user' => $panelUName, 'group' => $panelGName, 'dirmode' => '0550', 'filemode' => '0440', 'recursive' => 1 }
	);
}

=item _removeRoundcubePlugins()

 Remove the Plugins from the Roundcube Plugin folder.

 Return int 0 on success, other on failure

=cut

sub _removeRoundcubePlugins()
{
	my $roundcubePlugin = "$main::imscpConfig{'GUI_ROOT_DIR'}/plugins/SpamAssassin/roundcube-plugins";
	my $pluginFolder = "$main::imscpConfig{'GUI_ROOT_DIR'}/public/tools" . $main::imscpConfig{'WEBMAIL_PATH'} . "plugins";

	foreach my $plugin (glob($roundcubePlugin . "/*")) {
		$plugin =~ s%$roundcubePlugin/(.*)%$pluginFolder/$1%gm;
		my $rs = iMSCP::Dir->new('dirname' => $plugin)->remove() if -d $plugin;
		return $rs if $rs;
	}

	0;
}

=item _setRoundcubePlugin($plugin, $action)

 Activate or deactivate the Roundcube Plugin.

 Return int 0 on success, other on failure

=cut

sub _setRoundcubePlugin($$$)
{
	my ($self, $plugin, $action) = @_;

	# Modify the roundcube main.inc.php
	my $roundcubeMainIncFile = "$main::imscpConfig{'GUI_ROOT_DIR'}/public/tools" . $main::imscpConfig{'WEBMAIL_PATH'} . "config/main.inc.php";
	my $file = iMSCP::File->new('filename' => $roundcubeMainIncFile);

	my $fileContent = $file->get();
	unless(defined $fileContent) {
		error("Unable to read $roundcubeMainIncFile");
		return 1;
	}

	if($action eq 'add') {
		if ($fileContent =~ /$plugin/sgm) {
			$fileContent =~ s/,\s+'$plugin'//g;
		}

		if ($fileContent =~ /imscp_pw_changer/sgm) {
			$fileContent =~ s/,\s+'$plugin'//g;
			$fileContent =~ s/imscp_pw_changer/imscp_pw_changer', '$plugin/g;
		}
	} elsif($action eq 'remove') {
		if ($fileContent =~ /$plugin/sgm) {
			$fileContent =~ s/,\s+'$plugin'//g;
		}
	}

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _checkSpamassassinPlugins()

 Check which SpamAssassin Plugins have to be activated.

 Return int 0 if all requirements are meet, 1 otherwise

=cut

sub _checkSpamassassinPlugins
{
	my $self = $_[0];

	$self->{'config'}->{'spamassassinOptions'} =~ m/helper-home-dir=(\S*)/;
	my $helperHomeDir = $1;

	$self->{'config'}->{'spamassassinOptions'} =~ m/username=(\S*)/;
	my $saUser = $1;

	my ($stdout, $stderr);
	my $rs = execute("$main::imscpConfig{'CMD_CHOWN'} -R $saUser:$saUser $helperHomeDir", \$stdout, \$stderr);
	return $rs if $rs;

	if($self->{'config'}->{'use_pyzor'} eq 'yes') {
		if(! -x '/usr/bin/pyzor') {
			error('Unable to find pyzor. Please, install the pyzor package first.');
			return 1;
		}

		$rs = $self->_setSpamassassinUserprefs('use_pyzor', '1');
		return $rs if $rs;

		$rs = $self->_discoverPyzor();
		return $rs if $rs;
	} else {
		$rs = $self->_setSpamassassinUserprefs('use_pyzor', '0');
		return $rs if $rs;
	}

	if($self->{'config'}->{'use_razor2'} eq 'yes') {
		if(! -x '/usr/bin/razor-admin') {
			error('Unable to find razor. Please, install the razor package first.');
			return 1;
		}

		$rs = $self->_setSpamassassinUserprefs('use_razor2', '1');
		return $rs if $rs;

		if(! -d $helperHomeDir . '.razor') {
			$rs = $self->_createRazor();
			return $rs if $rs;
		}

		$rs = $self->_registerCronjob('discover_razor');
		return $rs if $rs;
	} else {
		$rs = $self->_setSpamassassinUserprefs('use_razor2', '0');
		return $rs if $rs;

		$rs = $self->_unregisterCronjob('discover_razor');
		return $rs if $rs;
	}

	if($self->{'config'}->{'use_auto-whitelist'} eq 'yes') {
		$rs = $self->_setSpamassassinUserprefs('use_auto_whitelist', '1');
		return $rs if $rs;

		$rs = $self->_registerCronjob('clean_awl_db');
		return $rs if $rs;
	} else {
		$rs = $self->_setSpamassassinUserprefs('use_auto_whitelist', '0');
		return $rs if $rs;

		$rs = $self->_unregisterCronjob('clean_awl_db');
		return $rs if $rs;

		$rs = $self->cleanAwlDb();
		return $rs if $rs;
	}

	if($self->{'config'}->{'site-wide_bayes'} eq 'yes' && $self->{'config'}->{'use_bayes'} eq 'yes') {
		$rs = $self->_registerCronjob('clean_bayes_db');
		return $rs if $rs;
	} else {
		$rs = $self->_unregisterCronjob('clean_bayes_db');
		return $rs if $rs;
	}

	if($self->{'config'}->{'use_bayes'} eq 'yes') {
		$rs = $self->_setSpamassassinUserprefs('use_bayes', '1');
		return $rs if $rs;
	} else {
		$rs = $self->_setSpamassassinUserprefs('use_bayes', '0');
		return $rs if $rs;
	}

	if($self->{'config'}->{'use_dcc'} eq 'yes') {
		$rs = $self->_setSpamassassinUserprefs('use_dcc', '1');
		return $rs if $rs;
	} else {
		$rs = $self->_setSpamassassinUserprefs('use_dcc', '0');
		return $rs if $rs;
	}

	if($self->{'config'}->{'use_rbl_checks'} eq 'yes') {
		$rs = $self->_setSpamassassinUserprefs('skip_rbl_checks', '0');
		return $rs if $rs;
	} else {
		$rs = $self->_setSpamassassinUserprefs('skip_rbl_checks', '1');
		return $rs if $rs;
	}

	if($self->{'config'}->{'DecodeShortURLs'} eq 'yes') {
		$rs = $self->_setSpamassassinPlugin('DecodeShortURLs', 'add');
		return $rs if $rs;
	} else {
		$rs = $self->_setSpamassassinPlugin('DecodeShortURLs', 'remove');
		return $rs if $rs;
	}

	if($self->{'config'}->{'iXhash2'} eq 'yes') {
		$rs = $self->_setSpamassassinPlugin('iXhash2', 'add');
		return $rs if $rs;
	} else {
		$rs = $self->_setSpamassassinPlugin('iXhash2', 'remove');
		return $rs if $rs;
	}

	0;
}

=item _checkRoundcubePlugins

 Check which Roundcube Plugins have to be activated.

 Return int 0 if all requirements are meet, 1 otherwise

=cut

sub _checkRoundcubePlugins
{
	my $self = $_[0];

	my $rs = 0;

	if($self->{'config'}->{'sauserprefs'} eq 'yes') {
		$rs = $self->_setRoundcubePlugin('sauserprefs', 'add');
		return $rs if $rs;
	} else {
		$rs = $self->_setRoundcubePlugin('sauserprefs', 'remove');
		return $rs if $rs;
	}

	if($self->{'config'}->{'markasjunk2'} eq 'yes' && $self->{'config'}->{'use_bayes'} eq 'yes') {
		$rs = $self->_setRoundcubePlugin('markasjunk2', 'add');
		return $rs if $rs;

		$rs = $self->_registerCronjob('bayes_sa-learn');
		return $rs if $rs;
	} else {
		$rs = $self->_setRoundcubePlugin('markasjunk2', 'remove');
		return $rs if $rs;

		$rs = $self->_unregisterCronjob('bayes_sa-learn');
		return $rs if $rs;

		$rs = $self->bayesSaLearn();
		return $rs if $rs;
	}

	0;
}

=item _setRoundcubePluginConfig($plugin)

 Set the values in the Roundcube Plugin config file config.inc.php.

 Return int 0 on success, other on failure

=cut

sub _setRoundcubePluginConfig($$)
{
	my ($self, $plugin) = @_;

	my $configPlugin = "$main::imscpConfig{'GUI_ROOT_DIR'}/plugins/SpamAssassin/config-templates/$plugin";
	my $pluginFolder = "$main::imscpConfig{'GUI_ROOT_DIR'}/public/tools" . $main::imscpConfig{'WEBMAIL_PATH'} . "plugins";

	my ($stdout, $stderr);
	my $rs = execute("$main::imscpConfig{'CMD_CP'} -fr $configPlugin/* $pluginFolder/$plugin", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	my $configFile = "$pluginFolder/$plugin/config.inc.php";
	my $file = iMSCP::File->new('filename' => $configFile);

	my $fileContent = $file->get();
	unless(defined $fileContent) {
		error("Unable to read $configFile");
		return 1;
	}

	if ($plugin eq 'sauserprefs') {
		$fileContent =~ s/\{DATABASE_HOST\}/$main::imscpConfig{'DATABASE_HOST'}/g;
		$fileContent =~ s/\{DATABASE_PORT\}/$main::imscpConfig{'DATABASE_PORT'}/g;
		$fileContent =~ s/\{SA_DATABASE_NAME\}/$main::imscpConfig{'DATABASE_NAME'}_spamassassin/g;
		$fileContent =~ s/\{SA_DATABASE_USER\}/$self->{'SA_DATABASE_USER'}/g;
		$fileContent =~ s/\{SA_DATABASE_PASSWORD\}/$self->{'SA_DATABASE_PASSWORD'}/g;
		
		my $sauserprefsDontOverride = $self->{'config'}->{'sauserprefs_dont_override'};
		
		if($self->{'config'}->{'reject_spam'} eq 'yes') {
			$sauserprefsDontOverride .= ", 'rewrite_header Subject', '{report}'";
		}
		
		if($self->{'config'}->{'use_bayes'} eq 'no') {
			$sauserprefsDontOverride .= ", '{bayes}'";
		}
		
		my $sauserprefsBayesDelete;

		if($self->{'config'}->{'site-wide_bayes'} eq 'yes') {
			$sauserprefsDontOverride .= ", 'bayes_auto_learn_threshold_nonspam', 'bayes_auto_learn_threshold_spam'";
			$sauserprefsBayesDelete = "false";
		} else {
			$sauserprefsBayesDelete = "true";
		}
		
		if($self->{'config'}->{'use_razor2'} eq 'no' && $self->{'config'}->{'use_pyzor'} eq 'no' && $self->{'config'}->{'use_dcc'} eq 'no' && $self->{'config'}->{'use_rbl_checks'} eq 'no') {
			$sauserprefsDontOverride .= ", '{tests}'";
		} else {
			if($self->{'config'}->{'use_razor2'} eq 'no') {
				$sauserprefsDontOverride .= ", 'use_razor2'";
			}

			if($self->{'config'}->{'use_pyzor'} eq 'no') {
				$sauserprefsDontOverride .= ", 'use_pyzor'";
			}

			if($self->{'config'}->{'use_dcc'} eq 'no') {
				$sauserprefsDontOverride .= ", 'use_dcc'";
			}

			if($self->{'config'}->{'use_rbl_checks'} eq 'no') {
				$sauserprefsDontOverride .= ", 'skip_rbl_checks'";
			}
		}

		if($self->{'config'}->{'use_lang_check'} eq 'no') {
			$sauserprefsDontOverride .= ", 'ok_languages', 'ok_locales'";
		}

		$fileContent =~ s/\{SAUSERPREFS_DONT_OVERRIDE\}/$sauserprefsDontOverride/g;
		$fileContent =~ s/\{SAUSERPREFS_BAYES_DELETE\}/$sauserprefsBayesDelete/g;

	} elsif ($plugin eq 'markasjunk2') {
		my $guiRootDir = $main::imscpConfig{'GUI_ROOT_DIR'};
		$fileContent =~ s/\{GUI_ROOT_DIR\}/$guiRootDir/g;
	}

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$rs = $file->mode(0440);
	return $rs if $rs;

	my $panelUName = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};
	my $panelGName = $panelUName;

	$file->owner($panelUName, $panelGName);
}

=item _setSpamassassinUserprefs($preference, $value)

 Set the values in the SpamAssassin userpref table.

 Return int 0 if all requirements are meet, 1 otherwise

=cut

sub _setSpamassassinUserprefs($$$)
{
	my ($self, $preference, $value) = @_;

	my $rdata = iMSCP::Database->factory()->doQuery(
		'dummy', "UPDATE `$main::imscpConfig{'DATABASE_NAME'}_spamassassin`.`userpref` SET `value` = ? WHERE `preference` = ?", $value, $preference
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	0;
}

=item _setupDatabase

 Setup SpamAssassin database.

 Return int 0 if all requirements are meet, 1 otherwise

=cut

sub _setupDatabase
{
	my $self = $_[0];

	my $imscpDbName = $main::imscpConfig{'DATABASE_NAME'};
	my $spamassassinDbName = $imscpDbName . '_spamassassin';

	my $db = iMSCP::Database->factory();

	# We do not want to use the imscp database here
	$db->set('DATABASE_NAME', '');

	my $rs = $db->connect();
	if($rs) {
		error("Unable to connect to SQL server");
		return 1;
	}

	# Check for SpamAssassin database existence
	$rs = $db->doQuery('1', 'SHOW DATABASES LIKE ?', $spamassassinDbName);
	unless(ref $rs eq 'HASH') {
		error($rs);
		return 1;
	}

	# The SpamAssassin database doesn't exist, create it
	unless(%$rs) {
		error("Unable to find the SpamAssassin '$spamassassinDbName' SQL database.");
		return 1;
	}

	# Connect to imscp database
	$db->set('DATABASE_NAME', $imscpDbName);
	$rs = $db->connect();
	if($rs) {
		error("Unable to connect to the i-MSCP '$imscpDbName' SQL database: $rs");
		return $rs if $rs;
	}

	# Create the SpamAssassin database user with the necessary privileges
	my $rs = $self->_getSaDbPassword();
	return $rs if $rs;

	my $rs = $db->doQuery(
		'dummy', "GRANT SELECT, INSERT, UPDATE, DELETE ON `$spamassassinDbName`.* TO ?@? IDENTIFIED BY ?;",  $self->{'SA_DATABASE_USER'}, $self->{'SA_HOST'}, $self->{'SA_DATABASE_PASSWORD'}
	);
	unless(ref $rs eq 'HASH') {
		error("Unable to add privileges on the '$spamassassinDbName' database for the SpamAssassin $self->{'SA_DATABASE_USER'}\@$self->{'SA_HOST'} SQL user: $rs");
		return 1;
	}

	0;
}

=item _dropSaDatabaseUser()

 Drop SpamAssassin database user.

 Return int 0 if all requirements are meet, 1 otherwise

=cut

sub _dropSaDatabaseUser()
{
	my $self = $_[0];

	# Create the SpamAssassin database user with the necessary privileges
	my $rdata = iMSCP::Database->factory->doQuery(
		'dummy', "DROP USER ?@?;",  $self->{'SA_DATABASE_USER'}, $self->{'SA_HOST'}
	);
	unless(ref $rdata eq 'HASH') {
		error("Unable to drop the $self->{'SA_DATABASE_USER'} SQL user: $rdata");
		return 1;
	}

	0;
}

=item _getSaDbPassword()

 Get the SpamAssassin database user password from file or create a new one.

=cut

sub _getSaDbPassword
{
	my $self = $_[0];

	my $saImscpCF = '/etc/spamassassin/00_imscp.cf';

	if(-f $saImscpCF) {
		# Get the SpamAssassin database user password from file
		my $file = iMSCP::File->new('filename' => $saImscpCF);

		my $fileContent = $file->get();
		unless(defined $fileContent) {
			error("Unable to read $saImscpCF");
			return 1;
		}

		$fileContent =~ m/user_scores_sql_password\s*([a-zA-Z0-9_]+)/;
		$self->{'SA_DATABASE_PASSWORD'} = $1;
	} elsif(! $self->{'SA_DATABASE_PASSWORD'}) {
		# Create the SpamAssassin database user password
		my @allowedChars = ('A'..'Z', 'a'..'z', '0'..'9', '_');

		my $saDbPassword;
		$saDbPassword .= $allowedChars[rand @allowedChars] for 1..16;
		$saDbPassword =~ s/('|"|`|#|;|\/|\s|\||<|\?|\\)/_/g;

		$self->{'SA_DATABASE_PASSWORD'} = $saDbPassword;
	}

	0;
}

=item _setSpamassassinPlugin($plugin, $action)

 Add or remove the plugin from the SpamAssassin folder.

 Return int 0 on success, other on failure

=cut

sub _setSpamassassinPlugin($$$)
{
	my ($self, $plugin, $action) = @_;

	my ($stdout, $stderr);
	my $spamassassinFolder = '/etc/spamassassin';

	if($action eq 'add') {
		my $pluginFolder = "$main::imscpConfig{'GUI_ROOT_DIR'}/plugins/SpamAssassin/spamassassin-plugins/$plugin";
		
		my $rs = execute("$main::imscpConfig{'CMD_CP'} -fr $pluginFolder/$plugin.* $spamassassinFolder/", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
		
		$rs = execute("$main::imscpConfig{'CMD_CHMOD'} 0644 $spamassassinFolder/$plugin.*", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	} elsif($action eq 'remove') {
		my $rs = execute("$main::imscpConfig{'CMD_RM'} -f $spamassassinFolder/$plugin.*", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	0;
}

=item _checkSaUser()

 Check the SpamAssassin user and home directory.

 Return int 0 on success, other on failure

=cut

sub _checkSaUser
{
	my $self = $_[0];

	$self->{'config'}->{'spamassassinOptions'} =~ m/username=(\S*)/;
	my $user = $1;
	my $group = $1;

	$self->{'config'}->{'spamassassinOptions'} =~ m/helper-home-dir=(\S*)/;
	my $helperHomeDir = $1;

	my ($rs, $stdout, $stderr);
	$rs = execute("$main::imscpConfig{'CMD_ID'} -g $group", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	if($rs eq '1') {
		$rs = execute("$main::imscpConfig{'CMD_GROUPADD'} -r $group", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	$rs = execute("$main::imscpConfig{'CMD_ID'} -u $user", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	if($rs eq '1') {
		$rs = execute("$main::imscpConfig{'CMD_USERADD'} -r -g $group -s /bin/sh -d $helperHomeDir $user", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	if(! -d $helperHomeDir) {
		$rs = execute("$main::imscpConfig{'CMD_MKDIR'} -p $helperHomeDir", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
		
		$rs = execute("$main::imscpConfig{'CMD_CHOWN'} -R $user:$group $helperHomeDir", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	0;
}

=item _checkVersion()

 Check the SpamAssassin and Roundcube versions.

 Return int 0 on success, other on failure

=cut

sub _checkVersion
{
	my $self = $_[0];

	# Check the SpamAssassin version
	my ($stdout, $stderr);
	my $rs = execute('/usr/sbin/spamd --version', \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr;
	error('Unable to get SpamAssassin version') if $rs && ! $stderr;
	return $rs if $rs;

	chomp($stdout);
	$stdout =~ m/^SpamAssassin\s*Server\s*version\s*([0-9\.]+)\s*/;

	if($1) {
		if(version->new($1) > version->new('3.3.2')) {
			error("Your SpamAssassin version $1 is not compatible with this plugin version. Please check the documentation.");
			return 1;
		}
	} else {
		error("Unable to find SpamAssassin version.");
		return 1;
	}

	# Check the Roundcube version
	tie %{$self->{'ROUNDCUBE'}}, 'iMSCP::Config', 'fileName' => "$main::imscpConfig{'CONF_DIR'}/roundcube/roundcube.data";

	if(version->new($self->{'ROUNDCUBE'}->{'ROUNDCUBE_VERSION'}) > version->new('0.9.5')) {
		error("Your Roundcube version $self->{'ROUNDCUBE'}->{'ROUNDCUBE_VERSION'} is not compatible with this plugin version. Please check the documentation.");
		return 1;
	}

	0;
}

=back

=head1 AUTHORS

 Sascha Bay <info@space2place.de>
 Rene Schuster <mail@reneschuster.de>

=cut

1;
