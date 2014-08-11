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

no if $] >= 5.017011, warnings => 'experimental::smartmatch';

use iMSCP::Debug;
use iMSCP::Execute;
use iMSCP::Database;
use JSON;
use parent 'Common::SingletonClass';

=head1 DESCRIPTION

 This package represent the backend side of the TemplateEditor plugin.

=head1 EVENT LISTENERS

=over 4

=item templateLoader($serviceName, $templateName, \$templateContent, \%data)

 Loader which is responsible to load a template from the database. This is a listener that listen on the onLoadTemplate
event which is triggered each time a template is loaded by the i-MSCP backend.

 This loader looks into the database to know if a custom template has been defined for the given template. If a template
is found, it is used in place of the default.

 Return int 0 on success, other on failure

=cut

sub templateLoader
{
	my ($self, $serviceName, $templateName, $templateContent, $data) = @_;

	if(exists $data->{'DOMAIN_ADMIN_ID'}) { # Search for a template which operate at site-wide
		my $template = $self->{'db'}->doQuery(
			'template_name',
			'
				SELECT
					template_name, template_content
				FROM
					template_editor_templates
				INNER JOIN
					template_editor_templates_admins USING(template_id)
				WHERE
					template_name = ?
				AND
					template_service_name = ?
				AND
					template_scope = ?
				AND
					admin_id = ?
				LIMIT
					1
			',
			$templateName, $serviceName, 'site', $data->{'DOMAIN_ADMIN_ID'}
		);
		unless(ref $template eq 'HASH') {
			error($template);
			return 1;
		} elsif(%{$template}) {
			$$templateContent = $template->{$templateName}->{'content'};
		}
	} else { # Search for a template which operate at system-wide
		my $template = $self->{'db'}->doQuery(
			'template_name',
			'
				SELECT
					template_name, template_content
				FROM
					template_editor_templates
				WHERE
					template_name = ?
				AND
					template_service_name = ?
				AND
					template_scope = ?
				AND
					template_is_default = 1
				LIMIT
					1
			',
			$templateName,
			$serviceName,
			'system'
		);
		unless(ref $template eq 'HASH') {
			error($template);
			return 1;
		} elsif(%{$template}) {
			$$templateContent = $template->{$templateName}->{'content'};
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

sub _init
{
	my $self = $_[0];

	if($self->{'action'} ~~ ['run', 'change', 'enable']) {
		$self->{'db'} = iMSCP::Database->factory();

		# Get plugin config
		my $pluginConfig = $self->{'db'}->doQuery(
			'plugin_name', 'SELECT plugin_name, plugin_config FROM plugin WHERE plugin_name = ?', 'TemplateEditor'
		);
		unless(ref $pluginConfig eq 'HASH') {
			fatal($pluginConfig);
		}

		$self->{'config'} = decode_json($pluginConfig->{'TemplateEditor'}->{'plugin_config'});

		#$self->{'memcached'} = $self->_getMemcached();

		# Register template loader
		$self->{'hooksManager'}->register('onLoadTemplate', sub { $self->templateLoader(); });
	}

	$self;
}

#=item _getMemcached()
#
# Get memcached instance
#
# Return Cache::Memcached::Fast or undef in case memcached server is not enabled or not available
#
#=cut
#
#sub _getMemcached
#{
#	my $self = $_[0];
#
#	my $memcached;
#
#	if($self->{'config'}->{'memcached'}->{'enabled'}) {
#		if(eval 'require Cache::Memcached::Fast') {
#			require Digest::SHA;
#			Digest::SHA->import('sha1_hex');
#
#			$memcached = new Cache::Memcached::Fast({
#				servers => ["$self->{'config'}->{'memcached'}->{'hostname'}:$self->{'config'}->{'memcached'}->{'port'}"],
#				namespace => substr(sha1_hex('TemplateEditor'), 0 , 8) . '_', # Hashed manually (expected)
#				connect_timeout => 0.5,
#				io_timeout => 0.5,
#				close_on_error => 1,
#				compress_threshold => 100_000,
#				compress_ratio => 0.9,
#				compress_methods => [ \&IO::Compress::Gzip::gzip, \&IO::Uncompress::Gunzip::gunzip ],
#				max_failures => 3,
#				failure_timeout => 2,
#				ketama_points => 150,
#				nowait => 1,
#				serialize_methods => [ \&Storable::freeze, \&Storable::thaw ],
#				utf8 => 1
#			});
#		}
#	}
#
#	$memcached;
#}

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
