=head1 NAME

 Plugin::CronJobs

=cut

# i-MSCP CronJobs plugin
# Copyright (C) 2014 Laurent Declercq <l.declercq@nuxwin.com>
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

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use lib "$main::imscpConfig{'GUI_ROOT_DIR'}/plugins/InstantSSH/backend",
        "$main::imscpConfig{'GUI_ROOT_DIR'}/plugins/InstantSSH/backend/Vendor";

use iMSCP::Debug;
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
		my $rs = $self->_configurePamChroot();
		return $rs if $rs;
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

		my $rs = iMSCP::Dir->new( dirname => $rootJailDir )->remove();
		return $rs if $rs;
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
		my $rs = $self->{'db'}->doQuery('dummy', "UPDATE cron_jobs SET cron_job_status = 'todisable'");
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

		$rs = $self->{'db'}->doQuery('dummy', "UPDATE cron_jobs SET cron_job_status = 'toenable'");
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
		my $rs = $self->install();
		return $rs if $rs;

		$rs = $self->{'db'}->doQuery('dummy', "UPDATE cron_jobs SET cron_job_status = 'toenable'");
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
		my $rs = $self->{'db'}->doQuery('dummy', "UPDATE cron_jobs SET cron_job_status = 'todisable'");
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

	$sth = $dbh->prepare(
		"
			SELECT
				cron_job_user, cron_job_status, IFNULL(cron_permission_type, 'unknown') AS cron_permission_type
			FROM
				cron_jobs
			LEFT JOIN
				cron_permissions ON(cron_job_permission_id = cron_permission_id)
			WHERE
				cron_job_status IN('toadd', 'tochange', 'toenable', 'todisable', 'todelete')
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
					cron_job_status NOT IN('disabled', 'todelete')
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

	# Handle cron permissions

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

 Return Plugin::CronJobs

=cut

sub _init
{
	my $self = $_[0];

	$self->{'db'} = iMSCP::Database->factory();

	my $config = $self->{'db'}->doQuery(
		'plugin_name', "SELECT plugin_name, plugin_config FROM plugin WHERE plugin_name = 'CronJobs'"
	);
	unless(ref $config eq 'HASH') {
		die("CronJobs: $config");
	} else {
		$self->{'config'} = decode_json($config->{'CronJobs'}->{'plugin_config'})
	}

	# Load jail builder library if available
	eval { local $SIG{'__DIE__'}; require InstantSSH::JailBuilder; };
	unless($@) {
		if(version->parse("v$InstantSSH::JailBuilder::VERSION") >= version->parse("v3.1.0")) {
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

	$self;
}

=item _writeCrontab($cronJobUser, $cronPermissionType)

 Write crontab for the given user

 Param int $cronJobUser Unix user
 Param string $cronPermissionType Cron permission type
 Return int 0 on success, other on failure

=cut

sub _writeCrontab
{
	my ($self, $cronJobUser, $cronPermissionType) = @_;

	my $dbh = $self->{'db'}->getRawDb();

	$sth = $dbh->prepare('SELECT * FROM cron_jobs WHERE cron_job_user = ?');
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

			$cronjob .=
				$row->{'cron_job_minute'} . ' ' . $row->{'cron_job_hour'} . ' ' . $row->{'cron_job_dmonth'} . ' ' .
				$row->{'cron_job_month'} . ' ' . $row->{'cron_job_dweek'} . ' ';

			if($row->{'cron_job_type'} eq 'url') {
				$cronjob .= '/usr/bin/wget -q -t 1 -T 3600 -O /dev/null ';
				# Stay compatible with self-signed certificates
				$cronjob .= '--no-check-certificate ' if index($row->{'cron_job_command'}, 'https') == 0;
				$cronjob .= escapeShell($row->{'cron_job_command'}) . ' /dev/null 2>&1';
			} else {
				$cronjob .= $row->{'cron_job_command'};
			}

			push @cronjobs, $cronjob . "\n";
		}
	}

	if(@cronjobs) {
		if(
			$self->{'config'}->{'jailed_cronjobs_support'} &&
			($cronPermissionType eq 'jailed' || $cronPermissionType ne 'unknown')
		) {
			my $jailBuilder;
			eval { $jailBuilder = InstantSSH::JailBuilder->new( id => 'jail', config => $self->{'config'} ); };
			if($@) {
				error("Unable to create JailBuilder object: $@");
				return 1;
			}

			if($cronPermissionType eq 'jailed') {
				unless($jailBuilder->existsJail()) {
					$rs = $jailBuilder->makeJail();
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
		if($self->{'config'}->{'jailed_cronjobs_support'} && $cronPermissionType ne 'unknown') {
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

		unless($uninstall) {
			# Remove any pam_chroot.so lines
			$fileContent =~ s/^session\s+.*?pam_chroot\.so.*?\n//gm;

			my $jailDir = $self->{'config'}->{'root_jail_dir'};
			$fileContent .= "session required pam_chroot.so debug\n";
		} else {
			$fileContent =~ s/^session\s+.*?pam_chroot\.so.*?\n//gm;
		}

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
