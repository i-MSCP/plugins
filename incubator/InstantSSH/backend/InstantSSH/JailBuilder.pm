#!/usr/bin/perl

=head1 NAME

 InstantSSH::JailBuilder;

=cut

# i-MSCP InstantSSH plugin
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

package InstantSSH::JailBuilder;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Dir;

use JSON;
use List::MoreUtils qw(uniq);

use parent 'Common::Object';

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

my $jailConf = {
	'root_jail_dir' => '/var/chroot/InstantSSH',
	'shared_jail' => 1,
	'paths' => [],
	'commands' => [],
	'packages' => [],
	'include_pkg_deps' => 0,
	'preserve_files' => ['/home'],
	'users' => [],
	'groups' => [],
	'devices' => [],
	'mounts' => [],
	'need_logsocket' => 0
};

my $makejailConffilePath = '/etc/makejail/instantSSH.py';
my $buildMakejailConffile = 1;

=head1 DESCRIPTION

 This package is part of the InstantSSH i-MSCP plugin. It provide jail builder which allow to build jailed shell
environments.

=head1 PUBLIC METHODS

=over 4

=item makeJail(\%config, $user)

 Create or update jail for the given user using the give jail configuration options

 Param hash \%config Hash containing Jail configuration options
 Param string $user i-MSCP user to add into the jail
 Return int 0 on success, other on failure

=cut

sub makeJail
{
	my ($self, $config, $user) = @_;

	$config = {} unless $config && ref $config eq 'HASH';
	$user ||= '__unknown__';

	if(getpwnam($user)) {
		# Override default root jail directory if needed
		if(exists $config->{'root_jail_dir'}) {
			$jailConf->{'root_jail_dir'} = $config->{'root_jail_dir'};
		}

		if(exists $config->{'shared_jail'}) {
			$jailConf->{'shared_jail'} = $config->{'shared_jail'};
		}

		#  Create the jail directory if it doesn't already exists or set it permissions
		iMSCP::Dir->new(
			'dirname' => $jailConf->{'root_jail_dir'} . (($jailConf->{'shared_jail'}) ? '/shared_jail' : "/$user")
		)->make(
			{ 'user' => 'root', 'group' => 'root' => 'mode' => 0755 }
		);

		# Build makejail configuration file if needed
		if($buildMakejailConffile || ! -f $makejailConffilePath) {
			my $rs = $self->_buildMakejailConffile($config, $user);
			return $rs if $rs;
		}
	} else {
		error(sprintf("InstantSSH: The %s i-MSCP unix user doesn't exists", $user));
		return 1;
	}

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _buildMakejailConffile($config, $user)

 Build makejail configuration file

 Param hash \%config Hash containing Jail configuration options
 Return int 0 on success, other on failure

=cut

sub _buildMakejailConffile
{
	my ($self, $config, $user) = @_;

	$config = {} unless $config && ref $config eq 'HASH';

	if(exists $config->{'apps_sections'}) {
		# Process sections as specified in apps_sections configuration options.
		if(ref $config->{'apps_sections'} eq 'ARRAY') {
			for my $appsSection(@{$config->{'apps_sections'}}) {
				if(exists $config->{$appsSection}) {
					$self->_handleAppsSection($config, $appsSection);
				} else {
					error(sprinf("InstantSSH: The %s applications section doesn't exists", $config->{$appsSection}));
					return 1;
				}
			}

			push @{$jailConf->{'users'}}, $user;
			push @{$jailConf->{'groups'}}, (getgrgid((getpwnam('vu2004'))[3]))[0];
		} else {
			error("InstantSSH: The 'apps_sections' option must be an array");
			return 1;
		}
	} else {
		error("InstantSSH: The 'apps_sections' is missing in jail configuration options");
		return 1;
	}

	# TODO write conffile using $makejailConfig content
	use Data::Dumper;
	print Dumper($jailConf);
	exit;

	0;
}

=item handleAppsSection(\%config, $appsSection)

 Handle applications sections

 Param hash \%config Hash containing Jail configuration options
 Param string $appsSection Applications section definition
 Return int 0 on success, 1 on failure

=cut

sub _handleAppsSection()
{
	my ($self, $config, $appsSection) = @_;

	# Handle included application sections

	if(exists $config->{$appsSection}->{'include_apps_sections'}) {
		if(ref $config->{$appsSection}->{'include_apps_sections'} eq 'ARRAY') {
			for my $includedAppsSection(@{$config->{$appsSection}->{'include_apps_sections'}}) {
				if(not grep $_ eq $includedAppsSection, @{$self->{'_apps_sections'}}) {
					$self->_handleAppsSection($config, $includedAppsSection);
					push @{$self->{'_apps_sections'}}, $includedAppsSection;
				}
			}
		} else {
			error("InstantSSH: The 'include_apps_sections' applications section option must be an array");
			return 1;
		}
	}

	# Handle list options from application section

	for my $option(qw/paths commands packages devices mounts preserve_files users groups/) {
		if(exists $config->{$appsSection}->{$option}) {
			if(ref $config->{$appsSection}->{$option} eq 'ARRAY') {
				for my $item (@{$config->{$appsSection}->{$option}}) {
					push @{$jailConf->{$option}}, $item;
				}

				@{$jailConf->{$option}} = uniq(@{$jailConf->{$option}});
			} else {
				error(sprintf("InstantSSH: The '%s' applications section option must be an array", $option));
				return 1;
			}
		}
	}

	# Handle boolean options from application section

	if (exists $config->{$appsSection}->{'include_pkg_deps'}) {
		$jailConf->{'include_pkg_deps'} = 1;
	}

	if (exists $config->{$appsSection}->{'need_logsocket'}) {
		$jailConf->{'need_logsocket'} = 1;
	}

	0;
}


#sub _addPasswdFile($$$)
#{
#	my ($self, $file, $what) = @_;
#
#	my $dest = $config->{'root_jail_dir'} . '/' . (($config->{'shared_jail'}) ? 'shared_jail' : $user  ) . $file;
#
#	open my $fh '<', $file or die ("Unable to open file for reading: $!");
#	my @lines = <$fh>;
#	close $fh;
#
#	open $fh, '+<', $dest or die ("Unable to open file for writing: $!");
#	s/(.*?):.*\n/$1/g for (my @outLines = <$fh>);
#
#	for my $line(@line) {
#		next if index($line, ':') == -1;
#		my $entry = (split ':', $line, 2)[0];
#		print $fh $entry if $entry eq $what && not grep $_ ne $what, @outlines;
#	}
#
#	close $fh;
#}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
