=head1 NAME

 Plugin::Monitorix

=cut

# i-MSCP Monitorix plugin
# Copyright (C) 2013-2016 Laurent Declercq <l.declercq@nuxwin.com>
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

package Plugin::Monitorix;

use strict;
use warnings;
no if $] >= 5.017011, warnings => 'experimental::smartmatch';
use iMSCP::Debug;
use iMSCP::Dir;
use iMSCP::File;
use iMSCP::Execute;
use iMSCP::Database;
use iMSCP::TemplateParser;
use iMSCP::Service;
use File::Basename;
use version;
use Cwd;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package provides the backend part for the i-MSCP Monitorix plugin.

=head1 PUBLIC METHODS

=over 4

=item install()

 Process install tasks

 Return int 0 on success, other on failure

=cut

sub install
{
	my $self = $_[0];

	my $rs = $self->_checkRequirements();
	return $rs if $rs;

	$rs = iMSCP::File->new(
		filename => "$main::imscpConfig{'PLUGINS_DIR'}/Monitorix/config/etc/monitorix/conf.d/20-imscp.conf"
	)->copyFile(
		"$self->{'config'}->{'confdir_path'}/conf.d/20-imscp.conf"
	);
	return $rs if $rs;

	my $file = iMSCP::File->new( filename => "$self->{'config'}->{'confdir_path'}/conf.d/20-imscp.conf" );

	my $fileContent = $file->get();
	unless(defined $fileContent) {
		error("Unable to read $file->{'filename'}");
		return 1;
	}

	$fileContent = process( { PLUGINS_DIR => $main::imscpConfig{'PLUGINS_DIR'} }, $fileContent );

	$rs = $file->set($fileContent);
	return $rs if $rs;

	$rs = $file->save();
	return $rs if $rs;

	$self->_setupApache('deconfigure');
}

=item uninstall()

 Process uninstall tasks

 Return int 0 on success, other on failure

=cut

sub uninstall
{
	my $self = $_[0];

	if(-f "$self->{'config'}->{'confdir_path'}/conf.d/20-imscp.conf") {
		my $rs = iMSCP::File->new( filename => "$self->{'config'}->{'confdir_path'}/conf.d/20-imscp.conf" )->delFile();
		return $rs if $rs;

		iMSCP::Service->getInstance()->restart('monitorix');
	}

	$self->_setupApache('configure');
}

=item update($fromVersion, $toVersion)

 Process update tasks

 Param string $fromVersion Version from which the plugin is updated
 Param string $toVersion Version to which the plugin is updated
 Return int 0 on success, other on failure

=cut

sub update
{
	my ($self, $fromVersion, $toVersion) = @_;

	my $rs = $self->install();
	return $rs if $rs;

	if(version->parse($fromVersion) < version->parse("1.1.1")) {
		unless(-f $self->{'config'}->{'cgi_path'}) {
			error("File $self->{'config'}->{'cgi_path'} not found");
			return 1;
		}

		# Cancel changes made by previous versions in the Monitorix CGI script

		my $file = iMSCP::File->new( filename => $self->{'config'}->{'cgi_path'} );
		my $fileContent = $file->get();
		unless(defined $fileContent) {
			error("Unable to read $file->{'filename'}");
			return 1;
		}

		$fileContent =~ s%/var/lib/monitorix/www/cgi/monitorix.conf.path%monitorix.conf.path%;

		$rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		# Cancel changes made by previous plugin versions in the monitorix.conf file

		if(-f "$self->{'config'}->{'confdir_path'}/monitorix.conf") {
			$file = iMSCP::File->new( filename => "$self->{'config'}->{'confdir_path'}/monitorix.conf" );

			$fileContent = $file->get();
			unless(defined $fileContent) {
				error("Unable to read $file->{'filename'}");
				return 1;
			}

			$fileContent = replaceBloc(
				"# Start_BaseDir Added by Plugins::Monitorix\n",
				"# Added by Plugins::Monitorix End_BaseDir\n",
				"base_dir = /var/lib/monitorix/www/\n",
				$fileContent
			);

			$fileContent = replaceBloc(
				"# Start_ImgDir Added by Plugins::Monitorix\n",
				"# Added by Plugins::Monitorix End_ImgDir\n",
				"imgs_dir = imgs/\n",
				$fileContent
			);

			$rs = $file->set($fileContent);
			return $rs if $rs;

			$rs = $file->save();
			return $rs if $rs;
		}
	}

	0;
}

=item enable()

 Process enable tasks

 Return int 0 on success, other on failure

=cut

sub enable
{
	my $self = $_[0];

	unless(defined $main::execmode && $main::execmode eq 'setup') {
		my $rs = $self->_enableGraphs();
		return $rs if $rs;

		iMSCP::Service->getInstance()->restart('monitorix');

		$rs = $self->buildGraphs();
		return $rs if $rs;
	}

	$self->_addCronjob();
}

=item disable()

 Process disable tasks

 Return int 0 on success, other on failure

=cut

sub disable
{
	my $self = $_[0];

	unless(defined $main::execmode && $main::execmode eq 'setup') {
		$_[0]->_deleteCronjob();
	} else {
		0;
	}
}

=item buildGraphs()

 Build monitorix graphs

 Return int 0 on success, other on failure

=cut

sub buildGraphs
{
	my $self = $_[0];

	if(defined $self->{'config'}->{'graph_enabled'}) {
		my $prevDir = getcwd();
		my $newDir = dirname($self->{'config'}->{'cgi_path'});

		unless(chdir($newDir)) {
			error("Unable to change directory to $newDir: $!");
			return 1;
		}

		my $graphColor = (
			defined $self->{'config'}->{'graph_color'} && $self->{'config'}->{'graph_color'} ~~ [ 'black', 'white' ]
		) ? $self->{'config'}->{'graph_color'} : 'white';

		for my $graph(keys %{$self->{'config'}->{'graph_enabled'}}) {
			if(lc($self->{'config'}->{'graph_enabled'}->{$graph}) eq 'y') {
				for my $when('1hour', '1day', '1week', '1month', '1year') {
					my @cmd = (
						'perl',
						$self->{'config'}->{'cgi_path'},
						'mode=localhost',
						'graph=' . escapeShell('_' . $graph . '1'),
						'when=' . escapeShell($when),
						'color=' . escapeShell($graphColor),
						'silent=imagetag'
					);
					my ($stdout, $stderr);
					my $rs = execute("@cmd", \$stdout, \$stderr);
					debug($stdout) if $stdout;
					error($stderr) if $stderr && $rs;
					return $rs if $rs;
				}
			}
		}

		unless(chdir($prevDir)) {
			error("Unable to change directory to $prevDir: $!");
			return 1;
		}

		my $panelUname =
		my $panelGName =
			$main::imscpConfig{'SYSTEM_USER_PREFIX'} . $main::imscpConfig{'SYSTEM_USER_MIN_UID'};

		my $graphsDir = $main::imscpConfig{'PLUGINS_DIR'} . '/Monitorix/themes/default/assets/images/graphs';

		if(-d $graphsDir) {
			for(iMSCP::Dir->new( dirname => $graphsDir, fileType => '.png' )->getFiles()) {
				my $file = iMSCP::File->new( filename => "$graphsDir/$_" );

				if($_ !~ /^_[a-z]*\d[a-y]?[z]\.1(?:hour|day|week|month|year)\.png$/) {
					my $rs = $file->delFile(); # Remove useless files, only zoom graphics are needed
					return $rs if $rs;
				} else {
					my $rs = $file->owner($panelUname, $panelGName);
					return $rs if $rs;

					$rs = $file->mode(0640);
					return $rs if $rs;
				}
			}
		}
	}

	0
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize instance

 Return Plugin::Monitorix or die on failure

=cut

sub _init
{
	my $self = $_[0];

	for(qw/bin_path cgi_path confdir_path cronjob_enabled cronjob_timedate/) {
		die("Missing $_ configuration parameter") unless exists $self->{'config'}->{$_};
	}

	$self;
}

=item _enableGraphs()

 Enable/Disable monitorix graphs

 Return int 0 on success, other on failure

=cut

sub _enableGraphs
{
	my $self = $_[0];

	my $confFile = "$self->{'config'}->{'confdir_path'}/conf.d/20-imscp.conf";

	unless(-f $confFile) {
		error("File $confFile not found.");
		return 1;
	}

	my $file = iMSCP::File->new( filename => $confFile );

	my $fileContent = $file->get();
	unless(defined $fileContent) {
		error('Unable to read $confFile');
		return 1;
	}

	my $graphs = "<graph_enable>\n";
	$graphs .= "\t$_ = $self->{'config'}->{'graph_enabled'}->{$_}\n" for keys %{$self->{'config'}->{'graph_enabled'}};
	$graphs .= "</graph_enable>\n";

	$fileContent = replaceBloc("<graph_enable>\n", "</graph_enable>\n", $graphs, $fileContent);

	my $rs = $file->set($fileContent);
	return 1 if $rs;

	$file->save();
}

=item _setupApache($action)

 Configure or deconfigure Apache2

 Pararm string $action Action to perform ( configure|deconfigure )
 Return int 0 on success, other on failure

=cut

sub _setupApache
{
	my ($self, $action) = @_;

	my $confFile = '/etc/apache2/conf.d/monitorix.conf';
	my $backupConffile = '/etc/apache2/conf.d/monitorix.old';

	if($action eq 'configure') {
		if(-f $backupConffile) {
			my $rs = iMSCP::File->new( filename => $backupConffile )->moveFile($confFile );
			return $rs if $rs;

			$rs = $self->_scheduleApacheRestart();
			return $rs if $rs;
		}
	} elsif($action eq 'deconfigure') {
		if(-f $confFile) {
			my $rs = iMSCP::File->new( filename => $confFile )->moveFile($backupConffile);
			return $rs if $rs;

			$rs = $self->_scheduleApacheRestart();
			return $rs if $rs;
		}
	}

	0;
}

=item _scheduleApacheRestart()

 Schedule restart of Apache2

 Return int 0

=cut

sub _scheduleApacheRestart
{
	require Servers::httpd;

	Servers::httpd->factory()->{'restart'} = 'yes';

	0;
}

=item _addCronjob()

 Add cronjob

 Return int 0 on success, other on failure

=cut

sub _addCronjob
{
	my $self = $_[0];

	if($self->{'config'}->{'cronjob_enabled'}) {
		my $scriptPath = $main::imscpConfig{'PLUGINS_DIR'} . '/Monitorix/cronjob/cronjob.pl';

		my $file = iMSCP::File->new( filename => $scriptPath );

		my $fileContent = $file->get();
		unless(defined $fileContent) {
			error("Unable to read $file->{'filename'} file");
			return 1;
		}

		$fileContent = process(
			{ 'IMSCP_PERLLIB_PATH' => $main::imscpConfig{'ENGINE_ROOT_DIR'} . '/PerlLib' }, $fileContent
		);

		my $rs = $file->set($fileContent);
		return $rs if $rs;

		$rs = $file->save();
		return $rs if $rs;

		require Servers::cron;

		Servers::cron->factory()->addTask(
			{
				'TASKID' => 'PLUGINS:Monitorix',
				'MINUTE' => $self->{'config'}->{'cronjob_timedate'}->{'minute'},
				'HOUR' => $self->{'config'}->{'cronjob_timedate'}->{'hour'},
				'DAY' => $self->{'config'}->{'cronjob_timedate'}->{'day'},
				'MONTH' => $self->{'config'}->{'cronjob_timedate'}->{'month'},
				'DWEEK' => $self->{'config'}->{'cronjob_timedate'}->{'dweek'},
				'COMMAND' => "nice -n 15 ionice -c2 -n5 perl $scriptPath >/dev/null 2>&1"
			}
		);
	} else {
		0;
	}
}

=item _deleteCronjob()

 Delete monitorix cronjob

 Return int 0 on success, other on failure

=cut

sub _deleteCronjob
{
	require Servers::cron;

	Servers::cron->factory()->deleteTask({ 'TASKID' => 'PLUGINS:Monitorix' });
}

=item _checkRequirements

 Check requirements

 Return int 0 if all requirements are meet, 1 otherwise

=cut

sub _checkRequirements
{
	my $self = $_[0];

	unless(-x $self->{'config'}->{'bin_path'}) {
		error("$self->{'config'}->{'bin_path'} doesn't exists or is not an executable");
		return 1;
	}

	unless(-f $self->{'config'}->{'cgi_path'}) {
		error("$self->{'config'}->{'cgi_path'} doesn't exists");
		return 1;
	}

	0
}

=back

=head1 AUTHORS

 Laurent Declercq <l.declercq@nuxwin.com>
 Sascha Bay <info@space2place.de>

=cut

1;
__END__
