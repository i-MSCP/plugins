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
use iMSCP::ProgramFinder;
use iMSCP::Service;
use FileHandle;
use version;
use parent 'Common::SingletonClass';

my $JAILED_CRONJOBS_SUPPORT = 0;

=head1 DESCRIPTION

 CronJobs plugin (backend side).

=head1 PUBLIC METHODS

=over 4

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = shift;

	if($JAILED_CRONJOBS_SUPPORT) {
		my $rs = _checkRequirements();
		return $rs if $rs;

		$rs = $self->_pamChroot('configure');
		return $rs if $rs;

		my $jailBuilder = eval { InstantSSH::JailBuilder->new( id => 'jail', config => $self->{'config'} ) };
		if($@) {
			error(sprintf('Unable to create InstantSSH::JailBuilder object: %s', $@));
			return 1;
		}

		unless($jailBuilder->existsJail()) {
			$rs = $jailBuilder->makeJail();
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
	my $self = shift;

	if($JAILED_CRONJOBS_SUPPORT) {
		my $jailBuilder = eval { InstantSSH::JailBuilder->new( id => 'jail', config => $self->{'config'} ) };
		if($@) {
			error(sprintf('Unable to create InstantSSH::JailBuilder object: %s', $@));
			return 1;
		}

		my $rs = $jailBuilder->removeJail();
		return $rs if $rs;

		my $rootJailDir = $self->{'config'}->{'root_jail_dir'};

		if(-d $rootJailDir) {
			my $dir = iMSCP::Dir->new( dirname => $rootJailDir );

			if($dir->isEmpty()) {
				$rs = $dir->remove();
				return $rs if $rs;
			} else {
				error(sprintf('Cannot delete %s directory: Directory is not empty', $rootJailDir));
				return 1;
			}
		}
	}

	my $rs = iMSCP::Dir->new( dirname => $self->{'config'}->{'makejail_confdir_path'} )->remove();
	return $rs if $rs;

	$self->_pamChroot('deconfigure');
}

=item update($fromVersion, $toVersion)

 Process update tasks

 Param string $fromVersion
 Param string $toVersion
 Return int 0 on success, other on failure

=cut

sub update
{
	my ($self, $fromVersion, $toVersion) = @_;

	if(-f '/etc/rsyslog.d/imscp_cronjobs_plugin.conf') {
		my $rs = iMSCP::File->new( filename => '/etc/rsyslog.d/imscp_cronjobs_plugin.conf' )->delFile();
		return $rs if $rs;

		if(iMSCP::ProgramFinder::find('rsyslogd')) {
			iMSCP::Service->getInstance()->restart('rsyslog');
		}
	}

	0;
}

=item change

 Process change tasks

 Return int 0, other on failure

=cut

sub change
{
	my $self = shift;

	unless(defined $main::execmode && $main::execmode eq 'setup') {
		my $rs = $self->{'db'}->doQuery(
			'u', "UPDATE cron_jobs SET cron_job_status = 'todisable' WHERE cron_job_status <> 'suspended'"
		);
		unless(ref $rs eq 'HASH') {
			error($rs);
			return 1;
		}

		$rs = $self->run();
		return $rs if $rs;

		if($JAILED_CRONJOBS_SUPPORT) {
			my $jailBuilder = eval { InstantSSH::JailBuilder->new( id => 'jail', config => $self->{'config_prev'} ) };
			if($@) {
				error(sprintf('Unable to create InstantSSH::JailBuilder object: %s', $@));
				return 1;
			}

			$rs = $jailBuilder->removeJail();
			return $rs if $rs;

			$jailBuilder = eval { InstantSSH::JailBuilder->new( id => 'jail', config => $self->{'config'} ) };
			if($@) {
				error(sprintf('Unable to create InstantSSH::JailBuilder object: %s', $@));
				return 1;
			}

			$rs = $jailBuilder->makeJail();
			return $rs if $rs;
		}

		$rs = $self->{'db'}->doQuery(
			'u', "UPDATE cron_jobs SET cron_job_status = 'toenable' WHERE cron_job_status <> 'suspended'"
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
	my $self = shift;

	unless((defined $main::execmode && $main::execmode eq 'setup') || $self->{'action'} eq 'install') {
		my $rs = $self->install(); # Handle case where support for jailed cron jobs is activated later on
		return $rs if $rs;

		$rs = $self->{'db'}->doQuery(
			'u', "UPDATE cron_jobs SET cron_job_status = 'toenable' WHERE cron_job_status <> 'suspended'"
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
	my $self = shift;

	unless((defined $main::execmode && $main::execmode eq 'setup')) {
		my $rs = $self->{'db'}->doQuery(
			'u', "UPDATE cron_jobs SET cron_job_status = 'todisable' WHERE cron_job_status <> 'suspended'"
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

=item run()

 Handle cron jobs and cron job permissions

 Return int 0 on succes, other on failure

=cut

sub run
{
	my $self = shift;

	my $dbh = $self->{'db'}->getRawDb();
	my $rs = 0;

	# Handle cron tables

	my $sth = $dbh->prepare(
		"
			SELECT
				cron_job_user, IFNULL(cron_permission_type, 'none') AS cron_permission_type
			FROM
				cron_jobs
			LEFT JOIN
				cron_permissions ON(cron_job_permission_id = cron_permission_id)
			WHERE
				cron_job_status NOT IN('ok', 'suspended')
			GROUP BY
				cron_job_user
		"
	);
	unless($sth && $sth->execute()) {
		$self->{'RETVAL'} = 1;
		error(sprintf("Couldn't prepare or execute SQL statement: %s", $dbh->errstr));
		return 1;
	}

	while (my $row = $sth->fetchrow_hashref()) {
		$rs |= $self->_writeCronTable($row->{'cron_job_user'}, $row->{'cron_permission_type'});
	}

	# Handle cron job permissions
	unless($rs) {
		my $qrs = $self->{'db'}->doQuery('d', "DELETE FROM cron_permissions WHERE cron_permission_status = 'todelete'");
		unless(ref $qrs eq 'HASH') {
			$self->{'RETVAL'} = 1;
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
	my $self = shift;

	$self->{'db'} = iMSCP::Database->factory();

	eval { local $SIG{'__DIE__' }; require InstantSSH::JailBuilder; };
	unless($@) {
		if(
			defined $InstantSSH::JailBuilder::VERSION &&
			version->parse($InstantSSH::JailBuilder::VERSION) >= version->parse('3.2.0')
		) {
			$JAILED_CRONJOBS_SUPPORT = 1;

			for my $param(qw/makejail_path makejail_confdir_path root_jail_dir/) {
				die(sprintf("Parameter %s is missing", $param)) unless exists $self->{'config'}->{$param};
			}
		}
	}

	$self;
}

=item _writeCronTable($cronJobUser, $cronPermissionType)

 Write cron table for the given user

 Param int $cronJobUser UNIX user
 Param string $cronPermissionType Cron permission type
 Return int 0 on success, other on failure

=cut

sub _writeCronTable
{
	my ($self, $cronJobUser, $cronPermissionType) = @_;

	my $cronjobNotificationPrev;
	my @cronjobIds = ();
	my @cronjobs = ();
	my $rs = 0;
	my $qrs;

	my $dbh = $self->{'db'}->getRawDb();
	my $sth = $dbh->prepare(
		"
			SELECT
				*
			FROM
				cron_jobs
			WHERE
				cron_job_user = ?
			AND
				cron_job_status <> 'suspended'
			ORDER BY
				cron_job_notification
		"
	);
	unless($sth && $sth->execute($cronJobUser)) {
		error(sprintf("Couldn't prepare or execute SQL statement: %s", $dbh->errstr));
		return 1;
	}

	while (my $row = $sth->fetchrow_hashref()) {
		push @cronjobIds, $row->{'cron_job_id'};

		if(not $row->{'cron_job_status'} ~~ [ 'tosuspend', 'todisable', 'todelete' ]) {
			my $cronjob = '';

			if(defined $row->{'cron_job_notification'}) {
				if(!defined $cronjobNotificationPrev || $row->{'cron_job_notification'} ne $cronjobNotificationPrev) {
					$cronjob .= "MAILTO='$row->{'cron_job_notification'}'\n";
				}

				$cronjobNotificationPrev = $row->{'cron_job_notification'};
			} else {
				$cronjobNotificationPrev = undef;
				$cronjob .= "MAILTO=''\n";
			}

			if(index($row->{'cron_job_minute'}, '@') == 0) { # time/date shortcut
				$cronjob .= $row->{'cron_job_minute'} . ' ';
			} else {
				$cronjob .= sprintf(
					'%s %s %s %s %s ', $row->{'cron_job_minute'}, $row->{'cron_job_hour'}, $row->{'cron_job_dmonth'},
					$row->{'cron_job_month'}, $row->{'cron_job_dweek'}
				);
			}

			# Percent-signs in the command, unless escaped with backslash are changed into newline characters
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
		if($JAILED_CRONJOBS_SUPPORT && ($cronPermissionType eq 'jailed' || $cronPermissionType ne 'none')) {
			my $jailBuilder = eval { InstantSSH::JailBuilder->new( id => 'jail', config => $self->{'config'} ) };
			if($@) {
				error(sprintf('Unable to create InstantSSH::JailBuilder object: %s', $@));
				$rs = 1;
			}

			unless($rs) {
				if($cronPermissionType eq 'jailed') {
					$rs = $jailBuilder->makeJail() unless $jailBuilder->existsJail();
					$rs ||= $jailBuilder->jailUser($cronJobUser);
				} else {
					$rs = $jailBuilder->unjailUser($cronJobUser);
				}
			}
		}

		unless($rs) {
			debug(sprintf('Writing cron table for user: %s', $cronJobUser));

			my $fh = new FileHandle;
			$fh->autoflush(1);

			# Write cron table
			if($fh->open("| $self->{'config'}->{'crontab_cmd_path'} -u $cronJobUser - 2> /dev/null")) {
				print $fh "SHELL=/bin/sh\n";
				print $fh $_ for @cronjobs;

				unless($fh->close()) {
					error('Unable to write cron table');
					$rs = 1;
				}
			} else {
				error(sprintf('Unable to pipe on crontab command: %s', $!));
				$rs = 1;
			}
		}
	} else {
		if($JAILED_CRONJOBS_SUPPORT && $cronPermissionType ne 'none') {
			my $jailBuilder = eval { InstantSSH::JailBuilder->new( id => 'jail', config => $self->{'config'} ) };
			if($@) {
				error(sprintf('Unable to create InstantSSH::JailBuilder object: %s', $@));
				$rs = 1;
			}

			$rs = $jailBuilder->unjailUser($cronJobUser) unless $rs;
		}

		if(!$rs && -f "$self->{'config'}->{'crontab_dir'}/$cronJobUser") {
			my ($stdout, $stderr);
			$rs = execute("$self->{'config'}->{'crontab_cmd_path'} -u $cronJobUser -r", \$stdout, \$stderr);
			debug($stdout) if $stdout;
			error($stderr) if $rs && $stderr;
		}
	}

	if(@cronjobIds) {
		unless($rs) {
			$qrs = $self->{'db'}->doQuery(
				'u',
				"
					UPDATE
						cron_jobs
					SET
						cron_job_status = IF(
							cron_job_status NOT IN('tosuspend', 'todisable'),
							'ok',
							IF(cron_job_status = 'tosuspend', 'suspended', 'disabled')
						)
					WHERE
						cron_job_id IN(" . (join ',', @cronjobIds) .")
					AND
						cron_job_status <> 'todelete'
				",
			);
		} else {
			$qrs = $self->{'db'}->doQuery(
				'd',
				'UPDATE cron_jobs SET cron_job_status = ? WHERE cron_job_id IN(' . (join ',', @cronjobIds) . ')',
				scalar getMessageByType('error') || 'Unexpected error'
			);
		}
		unless(ref $qrs eq 'HASH') {
			$self->{'RETVAL'} = 1;
			error($qrs);
		}
	}

	unless($rs) {
		$qrs = $self->{'db'}->doQuery(
			'd', "DELETE FROM cron_jobs WHERE cron_job_user = ? AND cron_job_status = 'todelete'", $cronJobUser
		);
		unless(ref $qrs eq 'HASH') {
			error($qrs);
			$rs = 1;
		}
	}

	$rs;
}

=item _checkRequirements()

 Check for requirements

 Return int 0 if all requirements are meet, other otherwise

=cut

sub _checkRequirements
{
	my $ret = 0;

	my ($stdout, $stderr);
	my $rs = execute("LANG=C dpkg-query --show --showformat '\${Status}' msmtp | cut -d ' ' -f 3", \$stdout, \$stderr);
	if($stdout ne 'installed') {
		error('The msmtp package is not installed on your system');
		return 1;
	}

	0;
}

=item _pamChroot($action)

 Configure or deconfigure pam chroot

 Param string $action Action to perform (configure|deconfigure)
 Return int 0 on success, other on failure

=cut

sub _pamChroot
{
	my ($self, $action) = @_;

	if(defined $action && $action ~~ [ 'configure', 'deconfigure' ]) {
		if(-f '/etc/pam.d/cron') {
			my $file = iMSCP::File->new( filename => '/etc/pam.d/cron' );
			my $fileContent = $file->get();
			unless(defined $fileContent) {
				error('Unable to read file /etc/pam.d/cron');
				return 1;
			}

			$fileContent =~ s/^session\s+.*?pam_chroot\.so.*?\n//gm;
			$fileContent .= "session required pam_chroot.so\n" unless $action eq 'deconfigure';

			my $rs = $file->set($fileContent);
			return $rs if $rs;

			$rs = $file->save();
			return $rs if $rs;
		} else {
			error('File /etc/pam.d/cron not found');
			return 1;
		}
	} else {
		error('Unknown action');
	}

	0;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
__END__
