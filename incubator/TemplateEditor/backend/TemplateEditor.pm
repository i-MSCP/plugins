#!/usr/bin/perl

=head1 NAME

 Plugin::TemplateEditor

=cut

# i-MSCP TemplateEditor plugin
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
# Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
#

package Plugin::TemplateEditor;

use strict;
use warnings;

use iMSCP::Debug;
use iMSCP::Execute;
use iMSCP::Database;
use JSON;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package implements the backend for the TemplateEditor plugin.

=head1 PUBLIC METHODS

=over 4

=item run()

 Register event listeners

 Return int 0 on success, other on failure

=cut

sub run()
{
	$_[0]->{'hooksManager'}->register('onLoadTemplate', \&genericTemplateLoader());
}

=back

=head1 EVENT LISTENERS

=over 4

=item genericTemplateLoader($serviceName, $templateFileName, \$templateContent, \%data)

 Generic template loader which is responsible to load service templates from database

 Return int 0 on success, other on failure

=cut

sub genericTemplateLoader($$$$)
{
	my ($serviceName, $templateFileName, $templateContent, $data) = @_;
	my $self = __PACKAGE__->getInstance();

	if(exists $data->{'DOMAIN_ADMIN_ID'}) {
		my $template = $self->{'db'}->doQuery(
			'name',
			'
				SELECT
					t1.name, t1.content
				FROM
					template_editor_files AS t1
				INNER JOIN
					template_editor_templates AS t2 ON(t2.id = t1.template_id)
				INNER JOIN
					template_editor_admins_templates AS t3 USING(template_id)
				WHERE
					t1.name = ?
				AND
					t2.service_name = ?
				AND
					t3.admin_id = ?
			',
			$templateFileName, lc($serviceName), $data->{'DOMAIN_ADMIN_ID'}
		);
		unless(ref $template eq 'HASH') {
			error($template);
			return 1;
		} elsif(%{$template}) {
			$$templateContent = $template->{$templateFileName}->{'content'};
		}
	}

	0;
}

=back

=head1 PRIVATE METHODS

=over 4

=item _init()

 Initialize plugin instance

 Return Plugin::TemplateEditor

=cut

sub _init()
{
	my $self = $_[0];

	$self->{'db'} = iMSCP::Database->factory();

	my $pluginConfig = $self->{'db'}->doQuery(
		'plugin_name', 'SELECT plugin_name, plugin_config FROM plugin WHERE plugin_name = ?', 'TemplateEditor'
	);
	unless(ref $pluginConfig eq 'HASH') {
		fatal($pluginConfig);
	}

	$self->{'config'} = decode_json($pluginConfig->{'TemplateEditor'}->{'plugin_config'});
	$self->{'memcached'} = $self->_getMemcached();

	$self;
}

=item _getMemcached()

 Get memcached instance

 Return Cache::Memcached::Fast or undef in case memcached server is not enabled or not available

=cut

sub _getMemcached
{
	my $self = $_[0];

	my $memcached;

	if($self->{'config'}->{'memcached'}->{'enabled'}) {
		if(eval 'require Cache::Memcached::Fast') {
			require Digest::SHA;
			Digest::SHA->import('sha1_hex');

			$memcached = new Cache::Memcached::Fast({
				servers => ["$self->{'config'}->{'memcached'}->{'hostname'}:$self->{'config'}->{'memcached'}->{'port'}"],
				namespace => substr(sha1_hex('TemplateEditor'), 0 , 8) . '_', # Hashed manually (expected)
				connect_timeout => 0.5,
				io_timeout => 0.5,
				close_on_error => 1,
				compress_threshold => 100_000,
				compress_ratio => 0.9,
				compress_methods => [ \&IO::Compress::Gzip::gzip, \&IO::Uncompress::Gunzip::gunzip ],
				max_failures => 3,
				failure_timeout => 2,
				ketama_points => 150,
				nowait => 1,
				serialize_methods => [ \&Storable::freeze, \&Storable::thaw ],
				utf8 => 1
			});
		}
	}

	$memcached;
}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
