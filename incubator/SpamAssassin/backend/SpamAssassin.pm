=head1 NAME

 Plugin::SpamAssassin

=cut

# i-MSCP SpamAssassin plugin
# Copyright (C) 2013-2016 Sascha Bay <info@space2place.de>
# Copyright (C) 2013-2016 Rene Schuster <mail@reneschuster.de>
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

package Plugin::SpamAssassin;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::Database;
use iMSCP::Service;
use iMSCP::TemplateParser;
use Servers::cron;
use version;

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
	my $self = shift;

	my $rs = _checkRequirements();
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
	my $self = shift;

	my $rs = $self->_getSaDbPassword();
	return $rs if $rs;

	$rs = $self->_updateSpamassassinRules();
	return $rs if $rs;

	$rs = $self->_spamassassinRulesHeinleinSupport('add');
	return $rs if $rs;
	
	$rs = $self->_spamassassinConfig('00_imscp.cf');
	return $rs if $rs;

	$rs = $self->_spamassassinDefaultConfig('configure');
	return $rs if $rs;

	$rs = $self->_spamassassinConfig('00_imscp.pre');
	return $rs if $rs;

	$rs = $self->_checkSpamassassinPlugins();
	return $rs if $rs;

	iMSCP::Service->getInstance()->restart('spamassassin');

	$rs = $self->_spamassMilterDefaultConfig('configure');
	return $rs if $rs;

	iMSCP::Service->getInstance()->restart('spamass-milter');

	if('Roundcube' ~~ [ split ',', $main::imscpConfig{'WEBMAIL_PACKAGES'} ]) {
		$rs = $self->_roundcubePlugins('add');
		return $rs if $rs;

		$rs = $self->_setRoundcubePluginConfig('sauserprefs');
		return $rs if $rs;

		$rs = $self->_setRoundcubePluginConfig('markasjunk2');
		return $rs if $rs;

		$rs = $self->_checkRoundcubePlugins();
		return $rs if $rs;
	}

	0;
}

=item update()

 Perform update tasks

 Return int 0 on success, other on failure

=cut

sub update
{
	my $self = shift;

	$self->change();
}

=item enable()

 Perform enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = shift;

	my $rs = $self->_postfixConfig('configure');
	return $rs if $rs;

	if('Roundcube' ~~ [ split ',', $main::imscpConfig{'WEBMAIL_PACKAGES'} ]) {
		$rs = $self->_setRoundcubePlugin('add');
		return $rs if $rs;

		unless(defined $main::execmode && $main::execmode eq 'setup') {
			# Needed to flush opcode cache if any
			iMSCP::Service->getInstance()->restart('imscp_panel', 'defer');
		}
	}

	$self->_schedulePostfixRestart();
}

=item disable()

 Perform disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
	my $self = shift;

	my $rs = $self->_unregisterCronjob('discover_razor');
	return $rs if $rs;

	$rs = $self->_unregisterCronjob('clean_awl_db');
	return $rs if $rs;

	$rs = $self->_unregisterCronjob('clean_bayes_db');
	return $rs if $rs;

	$rs = $self->_unregisterCronjob('bayes_sa-learn');
	return $rs if $rs;

	if('Roundcube' ~~ [ split ',', $main::imscpConfig{'WEBMAIL_PACKAGES'} ]) {
		$rs = $self->_setRoundcubePlugin('remove');
		return $rs if $rs;

		unless(defined $main::execmode && $main::execmode eq 'setup') {
			# Needed to flush opcode cache if any
			iMSCP::Service->getInstance()->restart('imscp_panel', 'defer');
		}
	}

	$rs = $self->_postfixConfig('deconfigure');
	return $rs if $rs;

	$self->_schedulePostfixRestart();
}

=item uninstall()

 Perform uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = shift;

	if('Roundcube' ~~ [ split ',', $main::imscpConfig{'WEBMAIL_PACKAGES'} ]) {
		my $rs = $self->_roundcubePlugins('remove');
		return $rs if $rs;
	}

	my $rs = $self->_spamassMilterDefaultConfig('deconfigure');
	return $rs if $rs;

	iMSCP::Service->getInstance()->restart('spamass-milter');

	$rs = $self->_spamassassinDefaultConfig('deconfigure');
	return $rs if $rs;

	$rs = $self->_removeSpamassassinConfig();
	return $rs if $rs;

	$rs = $self->_setSpamassassinPlugin('DecodeShortURLs', 'remove');
	return $rs if $rs;

	$rs = $self->_setSpamassassinPlugin('iXhash2', 'remove');
	return $rs if $rs;

	$rs = $self->_spamassassinRulesHeinleinSupport('remove');
	return $rs if $rs;

	iMSCP::Service->getInstance()->restart('spamassassin');

	$self->_dropSaDatabaseUser();
}

=item discoverRazor()

 Create the Razor server list files

 Return int 0 on success, other on failure

=cut

sub discoverRazor
{
	my $self = shift;

	$self->{'config'}->{'spamassassinOptions'} =~ m/username=(\S*)/;

	my $rs = execute("su $1 -c '/usr/bin/razor-admin -discover'", \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	$rs;
}

=item cleanAwlDb()

 Check and clean the SpamAssassin auto-whitelist (AWL) database

 Return int 0 on success, other on failure

=cut

sub cleanAwlDb
{
	my $rdata = iMSCP::Database->factory()->doQuery(
		'd',
		"
			DELETE FROM
				`$main::imscpConfig{'DATABASE_NAME'}_spamassassin`.`awl`
			WHERE (
				count = 1 AND last_update < DATE_SUB(NOW(), INTERVAL 1 WEEK)
			) OR (
				last_update < DATE_SUB(NOW(), INTERVAL 1 MONTH)
			)
		"
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	0;
}

=item cleanBayesDb()

 Expire old tokens from the bayes database
 
 It cleans the database only when the number of tokens surpasses the bayes_expiry_max_db_size value.

 Return int 0 on success, other on failure

=cut

sub cleanBayesDb
{
	my $rs = execute("/usr/bin/sa-learn --force-expire", \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	$rs;
}

=item bayesSaLearn()

 Train SpamAssassin's Bayesian classifier with spam and ham reported by the users

 Return int 0 on success, other on failure

=cut

sub bayesSaLearn
{
	my $saLearnDir = "$main::imscpConfig{'GUI_ROOT_DIR'}/plugins/SpamAssassin/sa-learn/";

	for my $saFile (glob($saLearnDir . "*")) {
		$saFile =~ /^($saLearnDir)(.*)__(spam|ham)__(.*)/;

		my $rs = execute("sa-learn --$3 --username=$2 $saFile", \my $stdout, \my $stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		$rs = execute("rm -f $saFile", \$stdout, \$stderr);
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
	my $self = shift;

	$self->{'FORCE_RETVAL'} = 'yes';
	$self->{'SA_DATABASE_USER'} = 'sa_user';
	$self->{'SA_HOST'} = $main::imscpConfig{'DATABASE_USER_HOST'};
	$self;
}

=item _updateSpamassassinRules()

 Update the SpamAssassin filter rules and keys

 Return int 0 on success, other on failure

=cut

sub _updateSpamassassinRules
{
	my $self = shift;

	$self->{'config'}->{'spamassassinOptions'} =~ m/helper-home-dir=(\S*)/;
	my $helperHomeDir = $1;

	$self->{'config'}->{'spamassassinOptions'} =~ m/username=(\S*)/;
	my $saUser = $1;

	my $rs = execute(
		"/bin/su $saUser -c '/usr/bin/sa-update --gpghomedir $helperHomeDir/sa-update-keys'", \my $stdout, \my $stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs >= 4;
	return $rs if $rs >= 4;

	if($self->{'config'}->{'heinlein-support_sa-rules'} eq 'yes') {
		$rs = execute(
			"/bin/su $saUser -c '/usr/bin/sa-update --nogpg --channel spamassassin.heinlein-support.de'", \my $stdout, \my $stderr
		);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs >= 4;
		return $rs if $rs >= 4;
	}

	$rs = execute("su $saUser -c '/usr/bin/sa-compile --quiet'", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$rs = execute("chmod -R go-w,go+rX $helperHomeDir/compiled", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	$rs;
}

=item _discoverPyzor()

 Create Pyzor home folder and discover the servers

 Return int 0 on success, other on failure

=cut

sub _discoverPyzor
{
	my $self = shift;

	$self->{'config'}->{'spamassassinOptions'} =~ m/username=(\S*)/;

	my $rs = execute("su $1 -c '/usr/bin/pyzor discover'", \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	$rs;
}

=item _createRazor()

 Create Razor home folder and registers a new identity

 Return int 0 on success, other on failure

=cut

sub _createRazor
{
	my $self = shift;

	$self->{'config'}->{'spamassassinOptions'} =~ m/username=(\S*)/;

	my $rs = execute("su $1 -c '/usr/bin/razor-admin -create'", \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	$rs = execute("su $1 -c '/usr/bin/razor-admin -register'", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	$rs;
}

=item _spamassassinRulesHeinleinSupport($action)

 Add or remove Heinlein Support SpamAssassin rules

 Param string $action Action to perform (add|remove)
 Return int 0 on success, other on failure

=cut

sub _spamassassinRulesHeinleinSupport
{
	my ($self, $action) = @_;

	if($action eq 'add' && $self->{'config'}->{'heinlein-support_sa-rules'} eq 'yes') {
		# Create an hourly cronjob from the original SpamAssassin cronjob
		my $rs = execute("cp -a /etc/cron.daily/spamassassin /etc/cron.hourly/spamassassin_heinlein-support_de", \my $stdout, \my $stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		my $file = iMSCP::File->new( filename => '/etc/cron.hourly/spamassassin_heinlein-support_de' );
		my $fileContent = $file->get();
		unless(defined $fileContent) {
			error("Unable to read $file->{'filename'} file");
			return 1;
		}

		# Change the sleep timer to 600 seconds on all distributions
		$fileContent =~ s/3600/600/g;
		# Change the sa-update channel on Ubuntu Precise
		$fileContent =~ s/^(sa-update)$/$1 --nogpg --channel spamassassin.heinlein-support.de/m;
		# Change the sa-update channel on Debian Wheezy / Jessie / Stretch and Ubuntu Xenial
		$fileContent =~ s/--gpghomedir \/var\/lib\/spamassassin\/sa-update-keys/--nogpg --channel spamassassin.heinlein-support.de/g;

		$rs = $file->set($fileContent);
		return $rs if $rs;

		$file->save();
	} elsif($action eq 'remove' || $self->{'config'}->{'heinlein-support_sa-rules'} eq 'no') {
		my $rs = execute("rm -rf /var/lib/spamassassin/*/spamassassin_heinlein-support_de*", \my $stdout, \my $stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		$rs = execute("rm -f /etc/cron.hourly/spamassassin_heinlein-support_de", \my $stdout, \my $stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}
}

=item _spamassMilterDefaultConfig($action)

 Modify spamass-milter default config file

 Param string $action Action to perform ( configure|deconfigure)
 Return int 0 on success, other on failure

=cut

sub _spamassMilterDefaultConfig
{
	my ($self, $action) = @_;

	my $file = iMSCP::File->new( filename => '/etc/default/spamass-milter' );

	my $fileContent = $file->get();
	unless(defined $fileContent) {
		error('Unable to read /etc/default/spamass-milter');
		return 1;
	}

	if($action eq 'configure') {
		my $spamassMilterOptions = $self->{'config'}->{'spamassMilter_config'}->{'spamassMilterOptions'};
		my $spamassMilterSocket = $self->{'config'}->{'spamassMilter_config'}->{'spamassMilterSocket'};

		$spamassMilterOptions .= ' -r ' . $self->{'config'}->{'spamassMilter_config'}->{'reject_spam'};

		if($self->{'config'}->{'spamassMilter_config'}->{'check_smtp_auth'} eq 'no') {
			$spamassMilterOptions .= ' -I';
		}

		for(@{$self->{'config'}->{'spamassMilter_config'}->{'networks'}}) {
			$spamassMilterOptions .= ' -i ' . $_;
		}

		$self->{'config'}->{'spamassassinOptions'} =~ m/port=(\d+)/;

		if($1 ne '783') {
			$spamassMilterOptions .= ' -- -p ' . $1;
		}

		$fileContent =~ s/^OPTIONS=.*/OPTIONS="$spamassMilterOptions"/gm;
		$fileContent =~ s/.*SOCKET=.*/SOCKET="$spamassMilterSocket"/gm;
	} elsif($action eq 'deconfigure') {
		$fileContent =~ s/^OPTIONS=.*/OPTIONS="-u spamass-milter -i 127.0.0.1"/gm;
		$fileContent =~ s%^SOCKET=.*%# SOCKET="/var/spool/postfix/spamass/spamass.sock"%gm;
	}

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _spamassassinDefaultConfig($action)

 Modify SpamAssassin default config file

 Param string $action Action to perform ( configure|deconfigure)
 Return int 0 on success, other on failure

=cut

sub _spamassassinDefaultConfig
{
	my ($self, $action) = @_;

	my $file = iMSCP::File->new( filename => '/etc/default/spamassassin' );
	my $fileContent = $file->get();
	unless(defined $fileContent) {
		error("Unable to read $file->{'filename'} file");
		return 1;
	}

	if($action eq 'configure') {
		$fileContent =~ s/^ENABLED=.*/ENABLED=1/gm;
		$fileContent =~ s/^OPTIONS=.*/OPTIONS="$self->{'config'}->{'spamassassinOptions'}"/gm;
		$fileContent =~ s/^CRON=.*/CRON=1/gm;
	} elsif($action eq 'deconfigure') {
		$fileContent =~ s/^ENABLED=.*/ENABLED=0/gm;
		$fileContent =~ s/^OPTIONS=.*/OPTIONS="--create-prefs --max-children 5 --helper-home-dir"/gm;
		$fileContent =~ s/^CRON=.*/CRON=0/gm;
	}

	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _postfixConfig($action)

 Configure or deconfigure postfix

 Param string $action Action to be performed ( configure|deconfigure )
 Return int 0 on success, other on failure

=cut

sub _postfixConfig
{
	my ($self, $action) = @_;

	my $rs = execute('postconf -h smtpd_milters non_smtpd_milters milter_connect_macros', \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	# Extract postconf values
	my @postconfValues = split /\n/, $stdout;

	(my $milterValue = $self->{'config'}->{'spamassMilter_config'}->{'spamassMilterSocket'}) =~ s%/var/spool/postfix%unix:%;
	(my $milterValuePrev = $self->{'config_prev'}->{'spamassMilter_config'}->{'spamassMilterSocket'}) =~ s%/var/spool/postfix%unix:%;
	my $milterMacro = '{if_name} _';

	s/\s*(?:\Q$milterValuePrev\E|\Q$milterValue\E|\Q$milterMacro\E)//g for @postconfValues;

	(my $spamassMilterSocket = $self->{'config'}->{'spamassMilter_config'}->{'spamassMilterSocket'}) =~ s%/var/spool/postfix%unix:%;

	if($action eq 'configure') {
		my @postconf = (
			'milter_default_action=accept',
			'smtpd_milters=' . (
				(@postconfValues)
					? escapeShell("$postconfValues[0] $milterValue") : escapeShell($milterValue)
			),
			'non_smtpd_milters=' . (
				(@postconfValues > 1) ? escapeShell("$postconfValues[1] $milterValue") : escapeShell($milterValue)
			),
			'milter_connect_macros=' . (
				(@postconfValues > 2) ? escapeShell("$postconfValues[2] $milterMacro") : escapeShell($milterMacro)
			)
		);

		$rs = execute("postconf -e @postconf", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	} elsif($action eq 'deconfigure') {
		if(@postconfValues) {
			my @postconf = ( 'smtpd_milters=' . escapeShell($postconfValues[0]) );

			if(@postconfValues > 1) {
				push @postconf, 'non_smtpd_milters=' . escapeShell($postconfValues[1]);
			}

			if(@postconfValues > 2) {
				push @postconf, 'milter_connect_macros=' . escapeShell($postconfValues[2]);
			}

			$rs = execute("postconf -e @postconf", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		}
	}

	0;
}

=item _schedulePostfixRestart()

 Schedule restart of Postfix

 Return int 0

=cut

sub _schedulePostfixRestart
{
	require Servers::mta;
	Servers::mta->factory()->restart('defer');
}

=item _registerCronjob($cronjobName)

 Register cronjob

 Return int 0 on success, other on failure

=cut

sub _registerCronjob
{
	my ($self, $cronjobName) = @_;

	my $cronjobFilePath = "$main::imscpConfig{'PLUGINS_DIR'}/SpamAssassin/cronjobs/cronjob_$cronjobName.pl";

	my $cronjobFile = iMSCP::File->new( filename => $cronjobFilePath );
	my $cronjobFileContent = $cronjobFile->get();
	unless(defined $cronjobFileContent) {
		error("Unable to read $cronjobFile->{'filename'} file");
		return 1;
	}

	require iMSCP::TemplateParser;
	iMSCP::TemplateParser->import();

	$cronjobFileContent = process(
		{ 'IMSCP_PERLLIB_PATH' => $main::imscpConfig{'ENGINE_ROOT_DIR'} . '/PerlLib' }, $cronjobFileContent
	);

	my $rs = $cronjobFile->set($cronjobFileContent);
	return $rs if $rs;

	$rs = $cronjobFile->save();
	return $rs if $rs;

	if($cronjobName eq 'bayes_sa-learn') {
		Servers::cron->factory()->addTask({
			TASKID => 'Plugin::SpamAssassin::BayesSaLearn',
			MINUTE => $self->{'config'}->{'cronjob_bayes_sa-learn'}->{'minute'},
			HOUR => $self->{'config'}->{'cronjob_bayes_sa-learn'}->{'hour'},
			DAY => $self->{'config'}->{'cronjob_bayes_sa-learn'}->{'day'},
			MONTH => $self->{'config'}->{'cronjob_bayes_sa-learn'}->{'month'},
			DWEEK => $self->{'config'}->{'cronjob_bayes_sa-learn'}->{'dweek'},
			COMMAND => "nice -n 15 ionice -c2 -n5 perl $cronjobFilePath >/dev/null 2>&1"
		});
	} elsif($cronjobName eq 'clean_bayes_db') {
		Servers::cron->factory()->addTask({
			TASKID => 'Plugin::SpamAssassin::CleanBayesDb',
			MINUTE => $self->{'config'}->{'cronjob_clean_bayes_db'}->{'minute'},
			HOUR => $self->{'config'}->{'cronjob_clean_bayes_db'}->{'hour'},
			DAY => $self->{'config'}->{'cronjob_clean_bayes_db'}->{'day'},
			MONTH => $self->{'config'}->{'cronjob_clean_bayes_db'}->{'month'},
			DWEEK => $self->{'config'}->{'cronjob_clean_bayes_db'}->{'dweek'},
			COMMAND => "nice -n 15 ionice -c2 -n5 perl $cronjobFilePath >/dev/null 2>&1"
		});
	} elsif($cronjobName eq 'clean_awl_db') {
		Servers::cron->factory()->addTask({
			TASKID => 'Plugin::SpamAssassin::CleanAwlDb',
			MINUTE => $self->{'config'}->{'cronjob_clean_awl_db'}->{'minute'},
			HOUR => $self->{'config'}->{'cronjob_clean_awl_db'}->{'hour'},
			DAY => $self->{'config'}->{'cronjob_clean_awl_db'}->{'day'},
			MONTH => $self->{'config'}->{'cronjob_clean_awl_db'}->{'month'},
			DWEEK => $self->{'config'}->{'cronjob_clean_awl_db'}->{'dweek'},
			COMMAND => "nice -n 15 ionice -c2 -n5 perl $cronjobFilePath >/dev/null 2>&1"
		});
	} elsif($cronjobName eq 'discover_razor') {
		Servers::cron->factory()->addTask({
			TASKID => 'Plugin::SpamAssassin::DiscoverRazor',
			MINUTE => '@weekly',
			COMMAND => "nice -n 15 ionice -c2 -n5 perl $cronjobFilePath >/dev/null 2>&1"
		});
	}

	0;
}

=item _unregisterCronjob($cronjobName)

 Unregister cronjob

 Return int 0 on success, other on failure

=cut

sub _unregisterCronjob
{
	my ($self, $cronjobName) = @_;

	if($cronjobName eq 'bayes_sa-learn') {
		my $rs = Servers::cron->factory()->deleteTask({ TASKID => 'Plugin::SpamAssassin::BayesSaLearn' });
		return $rs if $rs;
	} elsif($cronjobName eq 'clean_bayes_db') {
		my $rs = Servers::cron->factory()->deleteTask({ TASKID => 'Plugin::SpamAssassin::CleanBayesDb' });
		return $rs if $rs;
	} elsif($cronjobName eq 'clean_awl_db') {
		my $rs = Servers::cron->factory()->deleteTask({ TASKID => 'Plugin::SpamAssassin::CleanAwlDb' });
		return $rs if $rs;
	} elsif($cronjobName eq 'discover_razor') {
		my $rs = Servers::cron->factory()->deleteTask({ TASKID => 'Plugin::SpamAssassin::DiscoverRazor' });
		return $rs if $rs;
	}

	0;
}

=item _spamassassinConfig($saFile)

 Copy the SpamAssassin config files and set the values

 Param string $saFile SpamAssassin configuration file to generate
 Return int 0 on success, other on failure

=cut

sub _spamassassinConfig
{
	my ($self, $saFile) = @_;

	my $rs = execute(
		"cp -f $main::imscpConfig{'PLUGINS_DIR'}/SpamAssassin/config-templates/spamassassin/$saFile /etc/spamassassin",
		\my $stdout,
		\my $stderr
	);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	my $file = iMSCP::File->new( filename => "/etc/spamassassin/$saFile" );
	my $fileContent = $file->get();
	unless(defined $fileContent) {
		error("Unable to read $file->{'filename'} file");
		return 1;
	}

	if($saFile eq '00_imscp.cf') {
		my $disableDCC = "";
		
		if($self->{'config'}->{'use_dcc'} eq 'no') {
			$disableDCC = "AND preference NOT LIKE 'use_dcc'";
		}
		$fileContent = process(
			{
				DATABASE_HOST => $main::imscpConfig{'DATABASE_HOST'},
				DATABASE_PORT => $main::imscpConfig{'DATABASE_PORT'},
				SA_DATABASE_NAME => "$main::imscpConfig{'DATABASE_NAME'}_spamassassin",
				SA_DATABASE_USER => $self->{'SA_DATABASE_USER'},
				SA_DATABASE_PASSWORD => $self->{'SA_DATABASE_PASSWORD'},
				DISABLE_DCC => $disableDCC
			},
			$fileContent
		);

		if($self->{'config'}->{'site_wide_bayes'} eq 'yes') {
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

		if($self->{'config'}->{'use_dcc'} eq 'yes') {
			$fileContent =~ s/^#loadplugin Mail::SpamAssassin::Plugin::DCC/loadplugin Mail::SpamAssassin::Plugin::DCC/gm;
		} else {
			$fileContent =~ s/^loadplugin Mail::SpamAssassin::Plugin::DCC/#loadplugin Mail::SpamAssassin::Plugin::DCC/gm;
		}
	}

	$rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$file->mode(0644);
}

=item _removeSpamassassinConfig()

 Remove SpamAssassin config files

 Return int 0 on success, other on failure

=cut

sub _removeSpamassassinConfig
{
	my $rs = execute('rm -f /etc/spamassassin/00_imscp.*', \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	$rs;
}

=item _roundcubePlugins()

 Add or remove Roundcube plugin

 Param string $action Action to perform (add|remove)
 Return int 0 on success, other on failure

=cut

sub _roundcubePlugins
{
	my ($self, $action) = @_;

	my $pluginsSrcDir = "$main::imscpConfig{'PLUGINS_DIR'}/SpamAssassin/roundcube-plugins";
	my $pluginDestDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/plugins";

	if($action eq 'add') {
		my $rs = execute("cp -fR $pluginsSrcDir/* $pluginDestDir/", \my $stdout, \my $stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		my $user = my $group = $main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

		require iMSCP::Rights;
		iMSCP::Rights->import();

		$rs = setRights(
			$pluginDestDir, { user => $user, group => $group, dirmode => '0550', filemode => '0440', recursive => 1 }
		);
		return $rs if $rs;
	} elsif($action eq 'remove') {
		for my $dir(iMSCP::Dir->new( dirname => $pluginsSrcDir )->getDirs()) {
			my $rs = iMSCP::Dir->new( dirname => "$pluginDestDir/$dir" )->remove();
			return $rs if $rs;
		}
	}

	0;
}

=item _setRoundcubePlugin($action)

 Activate or deactivate the Roundcube Plugin

 Param string $action Action to perform (add|remove)
 Return int 0 on success, other on failure

=cut

sub _setRoundcubePlugin
{
	my ($self, $action) = @_;

	my $spamassassinPlugins = '';
	my $roundcubePluginConfig = '';
	my $roundcubeConfDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/config";

	my $confFilename;
	if(-f "$roundcubeConfDir/main.inc.php") {
		$confFilename = 'main.inc.php';
	} elsif(-f "$roundcubeConfDir/config.inc.php") {
		$confFilename = 'config.inc.php';
	} else {
		error('Unable to find roundcube configuration file');
		return 1;
	}

	my $file = iMSCP::File->new( filename => "$roundcubeConfDir/$confFilename" );
	my $fileContent = $file->get();
	unless(defined $fileContent) {
		error("Unable to read $file->{'filename'}");
		return 1;
	}

	if($action eq 'add') {
		if($self->{'config'}->{'sauserprefs'} eq 'yes') {
			$spamassassinPlugins = "'sauserprefs'";
		}

		if($self->{'config'}->{'markasjunk2'} eq 'yes' && $self->{'config'}->{'use_bayes'} eq 'yes') {
			$spamassassinPlugins .= ($spamassassinPlugins eq '') ? "'markasjunk2'" : ", 'markasjunk2'";
		}
		
		$fileContent =~ s/^\n# Begin Plugin::SpamAssassin.*Ending Plugin::SpamAssassin\n//sgm;
		
		$roundcubePluginConfig = "\n# Begin Plugin::SpamAssassin\n";

		if($confFilename eq 'main.inc.php') {
			$roundcubePluginConfig .= "\$rcmail_config['plugins'] = array_merge(\$rcmail_config['plugins'], array(" . $spamassassinPlugins . "));\n";
		} else {
			$roundcubePluginConfig .= "\$config['plugins'] = array_merge(\$config['plugins'], array(" . $spamassassinPlugins . "));\n";
		}

		$roundcubePluginConfig .= "# Ending Plugin::SpamAssassin\n";
		
		$fileContent .= $roundcubePluginConfig;
	} elsif($action eq 'remove') {
		$fileContent =~ s/^\n# Begin Plugin::SpamAssassin.*Ending Plugin::SpamAssassin\n//sgm;
	}
	
	my $rs = $file->set($fileContent);
	return $rs if $rs;

	$file->save();
}

=item _checkSpamassassinPlugins()

 Check which SpamAssassin Plugins have to be activated

 Return int 0 if all requirements are meet, 1 otherwise

=cut

sub _checkSpamassassinPlugins
{
	my $self = shift;

	$self->{'config'}->{'spamassassinOptions'} =~ m/helper-home-dir=(\S*)/;
	my $helperHomeDir = $1;

	$self->{'config'}->{'spamassassinOptions'} =~ m/username=(\S*)/;
	my $saUser = $1;

	my $rs = execute("chown -R $saUser:$saUser $helperHomeDir", \my $stdout, \my $stderr);
	return $rs if $rs;

	if($self->{'config'}->{'use_pyzor'} eq 'yes') {
		$rs = $self->_setSpamassassinUserprefs('use_pyzor', '1');
		return $rs if $rs;

		$rs = $self->_discoverPyzor();
		return $rs if $rs;
	} else {
		$rs = $self->_setSpamassassinUserprefs('use_pyzor', '0');
		return $rs if $rs;
	}

	if($self->{'config'}->{'use_razor2'} eq 'yes') {
		$rs = $self->_setSpamassassinUserprefs('use_razor2', '1');
		return $rs if $rs;

		unless(-d "$helperHomeDir/.razor") {
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

	if($self->{'config'}->{'site_wide_bayes'} eq 'yes' && $self->{'config'}->{'use_bayes'} eq 'yes') {
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

 Check which Roundcube Plugins have to be activated

 Return int 0 if all requirements are meet, 1 otherwise

=cut

sub _checkRoundcubePlugins
{
	my $self = shift;

	if($self->{'config'}->{'markasjunk2'} eq 'yes' && $self->{'config'}->{'use_bayes'} eq 'yes') {
		my $rs = $self->_registerCronjob('bayes_sa-learn');
		return $rs if $rs;
	} else {
		my $rs = $self->_unregisterCronjob('bayes_sa-learn');
		return $rs if $rs;

		$rs = $self->bayesSaLearn();
		return $rs if $rs;
	}

	$self->_setRoundcubePlugin('add');
}

=item _setRoundcubePluginConfig($plugin)

 Set the values in the Roundcube Plugin config file config.inc.php

 Return int 0 on success, other on failure

=cut

sub _setRoundcubePluginConfig
{
	my ($self, $plugin) = @_;

	my $configPlugin = "$main::imscpConfig{'PLUGINS_DIR'}/SpamAssassin/config-templates/$plugin";
	my $pluginsDir = "$main::imscpConfig{'GUI_PUBLIC_DIR'}/tools/webmail/plugins";

	my $rs = execute("cp -fr $configPlugin/* $pluginsDir/$plugin", \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	return $rs if $rs;

	my $file = iMSCP::File->new( filename => "$pluginsDir/$plugin/config.inc.php" );
	my $fileContent = $file->get();
	unless(defined $fileContent) {
		error("Unable to read $file->{'filename'} file");
		return 1;
	}

	if ($plugin eq 'sauserprefs') {
		$fileContent = process(
			{
				DATABASE_HOST => $main::imscpConfig{'DATABASE_HOST'},
				DATABASE_PORT => $main::imscpConfig{'DATABASE_PORT'},
				SA_DATABASE_NAME => "$main::imscpConfig{'DATABASE_NAME'}_spamassassin",
				SA_DATABASE_USER => $self->{'SA_DATABASE_USER'},
				SA_DATABASE_PASSWORD => $self->{'SA_DATABASE_PASSWORD'}
			},
			$fileContent
		);

		my $sauserprefsDontOverride = $self->{'config'}->{'sauserprefs_dont_override'};

		if($self->{'config'}->{'spamassMilter_config'}->{'reject_spam'} eq '-1') {
			$sauserprefsDontOverride .= ", 'rewrite_header Subject', '{report}'";
		}

		if($self->{'config'}->{'use_bayes'} eq 'no') {
			$sauserprefsDontOverride .= ", '{bayes}'";
		}

		if($self->{'config'}->{'site_wide_bayes'} eq 'yes') {
			$sauserprefsDontOverride .= ", 'bayes_auto_learn_threshold_nonspam', 'bayes_auto_learn_threshold_spam'";
		}

		if(
			$self->{'config'}->{'use_razor2'} eq 'no' && $self->{'config'}->{'use_pyzor'} eq 'no' &&
			$self->{'config'}->{'use_dcc'} eq 'no' && $self->{'config'}->{'use_rbl_checks'} eq 'no'
		) {
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

		$fileContent =~ s/\Q{SAUSERPREFS_DONT_OVERRIDE}/$sauserprefsDontOverride/g;

	} elsif ($plugin eq 'markasjunk2') {
		$fileContent =~ s/\Q{GUI_ROOT_DIR}/$main::imscpConfig{'GUI_ROOT_DIR'}/g;
	}

	$rs = $file->set($fileContent);
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

 Set the values in the SpamAssassin userpref table

 Return int 0 if all requirements are meet, 1 otherwise

=cut

sub _setSpamassassinUserprefs
{
	my ($self, $preference, $value) = @_;

	my $rdata = iMSCP::Database->factory()->doQuery(
		'u',
		"UPDATE `$main::imscpConfig{'DATABASE_NAME'}_spamassassin`.`userpref` SET `value` = ? WHERE `preference` = ?",
		$value,
		$preference
	);
	unless(ref $rdata eq 'HASH') {
		error($rdata);
		return 1;
	}

	0;
}

=item _setupDatabase

 Setup SpamAssassin database

 Return int 0 if all requirements are meet, 1 otherwise

=cut

sub _setupDatabase
{
	my $self = shift;

	my $imscpDbName = $main::imscpConfig{'DATABASE_NAME'};
	my $spamassassinDbName = $imscpDbName . '_spamassassin';

	my $db = iMSCP::Database->factory();

	$db->set('DATABASE_NAME', '');

	my $rs = $db->connect();
	if($rs) {
		error("Unable to connect to SQL server");
		return 1;
	}

	$rs = $db->doQuery('1', 'SHOW DATABASES LIKE ?', $spamassassinDbName);
	unless(ref $rs eq 'HASH') {
		error($rs);
		return 1;
	}

	unless(%$rs) {
		error("Unable to find the SpamAssassin '$spamassassinDbName' SQL database.");
		return 1;
	}

	$db->set('DATABASE_NAME', $imscpDbName);
	$rs = $db->connect();
	if($rs) {
		error("Unable to connect to the i-MSCP '$imscpDbName' SQL database: $rs");
		return $rs if $rs;
	}

	$rs = $self->_getSaDbPassword();
	return $rs if $rs;

	$rs = $db->doQuery(
		'g',
		"GRANT SELECT, INSERT, UPDATE, DELETE ON `$spamassassinDbName`.* TO ?@? IDENTIFIED BY ?",
		 $self->{'SA_DATABASE_USER'},
		 $self->{'SA_HOST'},
		 $self->{'SA_DATABASE_PASSWORD'}
	);
	unless(ref $rs eq 'HASH') {
		error("Unable to add privileges on the '$spamassassinDbName' database: $rs");
		return 1;
	}

	0;
}

=item _dropSaDatabaseUser()

 Drop SpamAssassin database user

 Return int 0 if all requirements are meet, 1 otherwise

=cut

sub _dropSaDatabaseUser
{
	my $self = shift;

	my $rdata = iMSCP::Database->factory->doQuery(
		'd', "DROP USER ?@?;",  $self->{'SA_DATABASE_USER'}, $self->{'SA_HOST'}
	);
	unless(ref $rdata eq 'HASH') {
		error("Unable to drop the $self->{'SA_DATABASE_USER'} SQL user: $rdata");
		return 1;
	}

	0;
}

=item _getSaDbPassword()

 Get the SpamAssassin database user password from file or create a new one

=cut

sub _getSaDbPassword
{
	my $self = shift;

	my $saImscpCF = '/etc/spamassassin/00_imscp.cf';

	if(-f $saImscpCF) {
		my $file = iMSCP::File->new( filename => $saImscpCF );
		my $fileContent = $file->get();
		unless(defined $fileContent) {
			error("Unable to read $file->{'filename'} file");
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

 Add or remove the plugin from the SpamAssassin folder

 Param string $action Action to perform ( add|remove )
 Return int 0 on success, other on failure

=cut

sub _setSpamassassinPlugin
{
	my ($self, $plugin, $action) = @_;

	my $spamassassinFolder = '/etc/spamassassin';

	if($action eq 'add') {
		my $pluginDir = "$main::imscpConfig{'GUI_ROOT_DIR'}/plugins/SpamAssassin/spamassassin-plugins/$plugin";

		my $rs = execute("cp -fR $pluginDir/$plugin.* $spamassassinFolder/", \my $stdout, \my $stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;

		$rs = execute("chmod 0644 $spamassassinFolder/$plugin.*", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	} elsif($action eq 'remove') {
		my $rs = execute("rm -f $spamassassinFolder/$plugin.*", \my $stdout, \my $stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	0;
}

=item _checkSaUser()

 Check the SpamAssassin user and home directory

 Return int 0 on success, other on failure

=cut

sub _checkSaUser
{
	my $self = shift;

	$self->{'config'}->{'spamassassinOptions'} =~ m/username=(\S*)/;
	my $user = $1;
	my $group = $1;

	$self->{'config'}->{'spamassassinOptions'} =~ m/helper-home-dir=(\S*)/;
	my $helperHomeDir = $1;

	my $rs = execute("id -g $group", \my $stdout, \my $stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	if($rs eq '1') {
		$rs = execute("groupadd -r $group", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	$rs = execute("id -u $user", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	if($rs eq '1') {
		$rs = execute("useradd -r -g $group -s /bin/sh -d $helperHomeDir $user", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	unless(-d $helperHomeDir) {
		$rs = execute("mkdir -p $helperHomeDir", \$stdout, \$stderr);
		debug($stdout) if $stdout;
		error($stderr) if $stderr && $rs;
		return $rs if $rs;
	}

	$rs = execute("chown -R $user:$group $helperHomeDir", \$stdout, \$stderr);
	debug($stdout) if $stdout;
	error($stderr) if $stderr && $rs;
	$rs;
}

=item _checkRequirements()
 
 Check for requirements

 Return int 0 if all requirements are meet, other otherwise

=cut

sub _checkRequirements
{
	my @reqPkgs = qw/spamassassin spamass-milter libmail-dkim-perl libnet-ident-perl libencode-detect-perl pyzor razor/;
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

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>
 Sascha Bay <info@space2place.de>
 Rene Schuster <mail@reneschuster.de>

=cut

1;
__END__
