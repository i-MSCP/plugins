=head1 NAME

 Plugin::CronJobs

=cut

# i-MSCP CronJobs plugin
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
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301 USA

package Plugin::CronJobs;

use strict;
use warnings;

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use lib "$main::imscpConfig{'GUI_ROOT_DIR'}/plugins/InstantSSH/backend",
        "$main::imscpConfig{'GUI_ROOT_DIR'}/plugins/InstantSSH/backend/Vendor";

use iMSCP::Debug;
use iMSCP::File;
use iMSCP::Dir;
use iMSCP::Execute;
use FileHandle;
use JSON;
use version;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provide the backend part of the CronJobs plugin.

=head1 PUBLIC METHODS

=over 4

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = $_[0];

	if($self->{'config'}->{'jailed_cronjobs_support'}) {
		my $rs = _checkRequirements();
		return $rs if $rs;

		$rs = $self->_configurePamChroot();
		return $rs if $rs;

		# If present, tells dovecot to ignore any mountpoints withing root jail directory
		if(-x '/usr/bin/doveadm') {
			my ($stdout, $stderr);
			$rs = execute(
				"doveadm mount add $self->{'config'}->{'root_jail_dir'}/jail/$main::imscpConfig{'USER_WEB_DIR'}/* ignore || true",
				\$stdout,
				\$stderr
			);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		}
	}

	0;
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = $_[0];

	if($self->{'config'}->{'jailed_cronjobs_support'}) {
		my $rootJailDir = $self->{'config'}->{'root_jail_dir'};

		if(-d "$rootJailDir/jail") {
			my $jailBuilder;
			eval { $jailBuilder = InstantSSH::JailBuilder->new( id => 'jail', config => $self->{'config'} ); };
			if($@) {
				error("Unable to create JailBuilder object: $@");
				return 1;
			}

			my $rs = $jailBuilder->removeJail();
			return $rs if $rs;
		}

		if(-f '/etc/rsyslog.d/imscp_cronjobs_plugin.conf') {
			my $rs = iMSCP::File->new( filename => '/etc/rsyslog.d/imscp_cronjobs_plugin.conf' )->delFile();
			return $rs if $rs;

			my ($stdout, $stderr);
			execute("$main::imscpConfig{'SERVICE_MNGR'} rsyslog restart", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		}

		my $rs = iMSCP::Dir->new( dirname => $rootJailDir )->remove();
		return $rs if $rs;

		# If present, remove pattern wich tells dovecot to ignore any mountpoints withing root jail directory
		if(-x '/usr/bin/doveadm') {
			my ($stdout, $stderr);
			$rs = execute(
				"doveadm mount remove $self->{'config'}->{'root_jail_dir'}/jail/$main::imscpConfig{'USER_WEB_DIR'}/* || true",
				\$stdout,
				\$stderr
			);
			debug($stdout) if $stdout;
			error($stderr) if $stderr && $rs;
			return $rs if $rs;
		}
	}

	my $rs = iMSCP::Dir->new( dirname => $self->{'config'}->{'makejail_confdir_path'} )->remove();
	return $rs if $rs;

	$self->_configurePamChroot('uninstall');
	return $rs if $rs;

	0;
}

=item change

 Process change tasks

 Return int 0, other on failure

=cut

sub change
{
	my $self = $_[0];

	unless(defined $main::execmode && $main::execmode eq 'setup') {
		my $rs = $self->{'db'}->doQuery(
			'dummy', "UPDATE cron_jobs SET cron_job_status = 'todisable' WHERE cron_job_status != 'suspended'"
		);
		unless(ref $rs eq 'HASH') {
			error($rs);
			return 1;
		}

		$rs = $self->run();
		return $rs if $rs;

		if($self->{'config'}->{'jailed_cronjobs_support'}) {
			my $jailBuilder;
			eval { $jailBuilder = InstantSSH::JailBuilder->new( id => 'jail', config => $self->{'config'} ); };
			if($@) {
				error("Unable to create JailBuilder object: $@");
				return 1;
			}

			if($jailBuilder->existsJail()) {
				$rs = $jailBuilder->makeJail(); # Update jail
				return $rs if $rs;
			}
		}

		$rs = $self->{'db'}->doQuery(
			'dummy', "UPDATE cron_jobs SET cron_job_status = 'toenable' WHERE cron_job_status != 'suspended'"
		);
		unless(ref $rs eq 'HASH') {
			error($rs);
			return 1;
		}

		$rs = $self->run();
		return $rs if $rs;
	}

	0;
}

=item enable()

 Process enable tasks

 Return int 0, other on failure

=cut

sub enable
{
	my $self = $_[0];

	if($self->{'action'} eq 'enable') {
		my $rs = $self->install(); # Handle the case where support for jailed cron jobs is activated later on
		return $rs if $rs;

		$rs = $self->{'db'}->doQuery(
			'dummy', "UPDATE cron_jobs SET cron_job_status = 'toenable' WHERE cron_job_status != 'suspended'"
		);
		unless(ref $rs eq 'HASH') {
			error($rs);
			return 1;
		}

		$rs = $self->run();
		return $rs if $rs;
	}

	0;
}

=item disable()

 Process disable tasks

 Return int 0, other on failure

=cut

sub disable
{
	my $self = $_[0];

	if($self->{'action'} eq 'disable') {
		my $rs = $self->{'db'}->doQuery(
			'dummy', "UPDATE cron_jobs SET cron_job_status = 'todisable' WHERE cron_job_status != 'suspended'"
		);
		unless(ref $rs eq 'HASH') {
			error($rs);
			return 1;
		}

		$rs = $self->run();
		return $rs if $rs;

		if($self->{'config'}->{'jailed_cronjobs_support'}) {
			my $jailBuilder;
			eval { $jailBuilder = InstantSSH::JailBuilder->new( id => 'jail', config => $self->{'config'} ); };
			if($@) {
				error("Unable to create JailBuilder object: $@");
				return 1;
			}

			$rs = $jailBuilder->removeJail();
			return $rs if $rs;
		}
	}

	0;
}

=item run()

 Handle cron jobs and cron job permissions

 Return int 0 on succes, other on failure

=cut

sub run
{
	my $self = $_[0];

	my $dbh = $self->{'db'}->getRawDb();

	# Handle cron jobs

	my $sth = $dbh->prepare(
		"
			SELECT
				cron_job_user, cron_job_status, IFNULL(cron_permission_type, 'none') AS cron_permission_type
			FROM
				cron_jobs
			LEFT JOIN
				cron_permissions ON(cron_job_permission_id = cron_permission_id)
			WHERE
				cron_job_status IN('toadd', 'tochange', 'toenable', 'todisable', 'tosuspend', 'todelete')
			GROUP BY
				cron_job_user
		"
	);
	unless($sth) {
		$self->{'RETVAL'} = 1; # Not an error related to a specific plugin item
		error("Couldn't prepare SQL statement: " . $dbh->errstr);
		return 1;
	}

	unless($sth->execute()) {
		$self->{'RETVAL'} = 1; # Not an error related to a specific plugin item
		error("Couldn't execute prepared statement: " . $dbh->errstr);
		return 1;
	}

	my $rs = 0;

	while (my $row = $sth->fetchrow_hashref()) {
		$self->_loadPluginConfig();

		$rs ||= $self->_writeCrontab($row->{'cron_job_user'}, $row->{'cron_permission_type'});

		my $nextStatus = ($row->{'cron_job_status'} eq 'todisable') ? 'disabled' : 'ok';

		my $qrs = $self->{'db'}->doQuery(
			'dummy',
			"
				UPDATE
					cron_jobs
				SET
					cron_job_status = ?
				WHERE
					cron_job_user = ?
				AND
					cron_job_status NOT IN( 'tosuspend', 'suspended', 'disabled', 'todelete')
			",
			($rs ? scalar getMessageByType('error') : $nextStatus),
			$row->{'cron_job_user'}
		);
		unless(ref $qrs eq 'HASH') {
			error($qrs);
			$rs ||= 1;
		}

		unless($rs) {
			$qrs = $self->{'db'}->doQuery(
				'dummy',
				"UPDATE cron_jobs SET cron_job_status = ? WHERE cron_job_user = ? AND cron_job_status = ?",
				'suspended',
				$row->{'cron_job_user'},
				'tosuspend'
			);
			unless(ref $qrs eq 'HASH') {
				error($qrs);
				$rs ||= 1;
			}
		}

		unless($rs) {
			$qrs = $self->{'db'}->doQuery(
				'dummy',
				'DELETE FROM cron_jobs WHERE cron_job_user = ? AND cron_job_status = ?',
				$row->{'cron_job_user'},
				'todelete'
			);
			unless(ref $qrs eq 'HASH') {
				error($qrs);
				$rs ||= 1;
			}
		}
	}

	# Handle cron job permissions

	unless($rs) {
		my $qrs = $self->{'db'}->doQuery(
			'dummy', "DELETE FROM cron_permissions WHERE cron_permission_status = 'todelete'"
		);
		unless(ref $qrs eq 'HASH') {
			$self->{'RETVAL'} = 1; # Not an error related to a specific plugin item
			error($qrs);
			$rs = 1;
		}
	}

	$rs;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init

 Initialize instance

 Return Plugin::CronJobs or die on failure

=cut

sub _init
{
	my $self = $_[0];

	$self->{'db'} = iMSCP::Database->factory();

	if($self->{'action'} ~~ [ 'install', 'uninstall', 'update', 'change', 'enable', 'disable' ]) {
		$self->_loadPluginConfig();
	}

	$self;
}

=item _loadPluginConfig

 Load plugin configuraiton

 Return int 0 or die on failure

=cut

sub _loadPluginConfig
{
	my $self = $_[0];

	unless($self->{'_loadedPluginConfig'}) {
		my $config = $self->{'db'}->doQuery(
			'plugin_name', "SELECT plugin_name, plugin_config FROM plugin WHERE plugin_name = 'CronJobs'"
		);
		unless(ref $config eq 'HASH') {
			die("CronJobs: $config");
		}

		$self->{'config'} = decode_json($config->{'CronJobs'}->{'plugin_config'});

		# Load jail builder library if available
		eval { local $SIG{'__DIE__'}; require InstantSSH::JailBuilder; };
		unless($@) {
			if(
				defined $InstantSSH::JailBuilder::VERSION &&
				version->parse("$InstantSSH::JailBuilder::VERSION") >= version->parse("3.1.0")
			) {
				$self->{'config'}->{'jailed_cronjobs_support'} = 1;

				for(qw/makejail_path makejail_confdir_path root_jail_dir/) {
					die("Missing $_ configuration parameter") unless defined $self->{'config'}->{$_};
				}
			} else {
				$self->{'config'}->{'jailed_cronjobs_support'} = 0;
			}
		} else {
			$self->{'config'}->{'jailed_cronjobs_support'} = 0;
		}

		$self->{'_loadedPluginConfig'} = 1;
	}

	0;
}

=item _writeCrontab($cronJobUser, $cronPermissionType)

 Write crontab for the given user

 Param int $cronJobUser UNIX user
 Param string $cronPermissionType Cron permission type
 Return int 0 on success, other on failure

=cut

sub _writeCrontab
{
	my ($self, $cronJobUser, $cronPermissionType) = @_;

	my $dbh = $self->{'db'}->getRawDb();

	my $sth = $dbh->prepare('SELECT * FROM cron_jobs WHERE cron_job_user = ?');
	unless($sth) {
		error("Couldn't prepare SQL statement: " . $dbh->errstr);
		return 1;
	}

	unless($sth->execute($cronJobUser)) {
		error("Couldn't execute prepared statement: " . $dbh->errstr);
		return 1;
	}

	my @cronjobs = ();
	my $cronjobNotificationPrev = 'none';

	while (my $row = $sth->fetchrow_hashref()) {
		if($row->{'cron_job_status'} ~~ ['toadd', 'tochange', 'toenable', 'ok']) {
			my $cronjob = '';

			if(defined $row->{'cron_job_notification'} && $row->{'cron_job_notification'} ne $cronjobNotificationPrev) {
				$cronjob .= "MAILTO='$row->{'cron_job_notification'}'\n";
				$cronjobNotificationPrev = $row->{'cron_job_notification'};
			} elsif($cronjobNotificationPrev eq 'none' || $cronjobNotificationPrev ne '') {
				$cronjob .= "MAILTO=''\n";
				$cronjobNotificationPrev = '';
			}

			if(index($row->{'cron_job_minute'}, '@') == 0) { # time/date shortcut
				$cronjob .= $row->{'cron_job_minute'} . ' ';
			} else {
				$cronjob .=
					$row->{'cron_job_minute'} . ' ' . $row->{'cron_job_hour'} . ' ' . $row->{'cron_job_dmonth'} . ' ' .
					$row->{'cron_job_month'} . ' ' . $row->{'cron_job_dweek'} . ' ';
			}

			# Percent-signs in the command, unless escaped with backslash, will be changed into newline characters
			$row->{'cron_job_command'} =~ s/%/\\%/g;

			if($row->{'cron_job_type'} eq 'url') {
				$cronjob .= '/usr/bin/wget -t 1 --connect-timeout=5 --dns-timeout=5 --read-timeout=3600 -O /dev/null ';
				$cronjob .= '--no-check-certificate '; # Stay compatible with self-signed certificates
				$cronjob .= escapeShell($row->{'cron_job_command'});
			} else {
				$cronjob .= $row->{'cron_job_command'};
			}

			push @cronjobs, $cronjob . "\n";
		}
	}

	if(@cronjobs) {
		if(
			$self->{'config'}->{'jailed_cronjobs_support'} &&
			($cronPermissionType eq 'jailed' || $cronPermissionType ne 'none')
		) {
			my $jailBuilder;
			eval { $jailBuilder = InstantSSH::JailBuilder->new( id => 'jail', config => $self->{'config'} ); };
			if($@) {
				error("Unable to create JailBuilder object: $@");
				return 1;
			}

			if($cronPermissionType eq 'jailed') {
				unless($jailBuilder->existsJail()) {
					my $rs = $jailBuilder->makeJail();
					return $rs if $rs;
				}

				my $rs = $jailBuilder->jailUser($cronJobUser);
				return $rs if $rs;
			} else {
				my $rs = $jailBuilder->unjailUser($cronJobUser);
				return $rs if $rs;
			}
		}

		my $fh = new FileHandle;
		$fh->autoflush(1);

		if($fh->open("| $self->{'config'}->{'crontab_cmd_path'} -u $cronJobUser - 2> /dev/null")) {
			print $fh "SHELL=/bin/sh\n";
			print $fh $_ for @cronjobs;

			unless($fh->close()) {
				error("Unable to write crontab file");
				return 1;
			}
		} else {
			error("Unable to pipe on crontab command: $!");
			return 1;
		}
	} else {
		if($self->{'config'}->{'jailed_cronjobs_support'} && $cronPermissionType ne 'none') {
			my $jailBuilder;
			eval { $jailBuilder = InstantSSH::JailBuilder->new( id => 'jail', config => $self->{'config'} ); };
			if($@) {
				error("Unable to create JailBuilder object: $@");
				return 1;
			}

			my $rs = $jailBuilder->unjailUser($cronJobUser);
			return $rs if $rs;
		}

		if(-f "$self->{'config'}->{'crontab_dir'}/$cronJobUser") {
			my ($stdout, $stderr);
			my $rs = execute("$self->{'config'}->{'crontab_cmd_path'} -u $cronJobUser -r", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $rs && $stderr;
			return $rs if $rs;
		}
	}

	0;
}

=item _checkRequirements()

 Check for requirements

 Return int 0 if all requirements are meet, other otherwise

=cut

sub _checkRequirements
{
	my $ret = 0;

	for my $package (qw/libpam-chroot makejail msmtp/) {
		my ($stdout, $stderr);
		my $rs = execute(
			"LANG=C dpkg-query --show --showformat '\${Status}' $package | cut -d ' ' -f 3", \$stdout, \$stderr
		);
		debug($stdout) if $stdout;
		if($stdout ne 'installed') {
			error("The $package package is not installed on your system");
			$ret ||= 1;
		}
	}

	$ret;
}

=item _configurePamChroot($uninstall = false)

 Configure pam chroot

 Param bool $uninstall OPTIONAL Whether pam chroot configuration must be removed ( default: false )
 Return int 0 on success, other on failure

=cut

sub _configurePamChroot
{
	my $uninstall = $_[1] // 0;

	if(-f '/etc/pam.d/cron') {
		my $file = iMSCP::File->new( filename => '/etc/pam.d/cron' );

		my $fileContent = $file->get();
		unless(defined $fileContent) {
			error('Unable to read file /etc/pam.d/cron');
			return 1;
		}

		$fileContent =~ s/^session\s+.*?pam_chroot\.so.*?\n//gm;
		$fileContent .= "session required pam_chroot.so\n" unless $uninstall;

		my $rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;
	} else {
		error('File /etc/pam.d/cron not found');
		return 1;
	}

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
