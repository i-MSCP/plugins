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

 This package implements the backend for the TemplateEditor plugin.

=head1 PUBLIC METHODS

=over 4

=item run()

 Register template loader

 Return int 0 on success, other on failure

=cut

sub run()
{
	my $self = $_[0];

	$self->{'hooksManager'}->register('onLoadTemplate', sub { $self->dbTemplateLoader(); });
}

=back

=head1 EVENT LISTENERS

=over 4

=item dbTemplateLoader($serviceName, $templateName, \$templateContent, \%data)

 Loader which is responsible to load a template from the database. This is listener that listen on the onLoadTemplate
event which is triggered each time a template is loaded by the i-MSCP backend.

This loader looks in the database to know if a custom template has been defined for the given template. If a template is
found, it is used in place of the default.

 Return int 0 on success, other on failure

=cut

sub dbTemplateLoader
{
	my ($self, $serviceName, $templateName, $templateContent, $data) = @_;

	# Retrieve template scope
	my $templateScope = $self->_getTemplateScope($serviceName, $templateName);

	if($templateScope ne 'unknown') {
		if($templateScope eq 'system') {
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
						template_parent_id IS NOT NULL
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
		} elsif(exists $data->{'DOMAIN_ADMIN_ID'}) {
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
						service_name = ?
					AND
						admin_id = ?
					LIMIT
						1
				',
				$templateName, $serviceName, $data->{'DOMAIN_ADMIN_ID'}
			);
			unless(ref $template eq 'HASH') {
				error($template);
				return 1;
			} elsif(%{$template}) {
				$$templateContent = $template->{$templateName}->{'content'};
			}
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

	$self->{'db'} = iMSCP::Database->factory();

	my $pluginConfig = $self->{'db'}->doQuery(
		'plugin_name', 'SELECT plugin_name, plugin_config FROM plugin WHERE plugin_name = ?', 'TemplateEditor'
	);
	unless(ref $pluginConfig eq 'HASH') {
		fatal($pluginConfig);
	}

	$self->{'config'} = decode_json($pluginConfig->{'TemplateEditor'}->{'plugin_config'});
	#$self->{'memcached'} = $self->_getMemcached();

	$self;
}

=item _getTemplateScope()

 Get scope of the given template ('system', 'site')

 Param string $templateName
 Return string ServiceName scope
 Return string Template scope ('system', 'site') or 'unknown' in case the template is not know by the plugin

=cut

sub _getTemplateScope
{
	my ($self, $serviceName, $templateName) = @_;

	my $templateScope = 'unknown';

	if(exists $self->{'config'}->{'service_templates'}->{$serviceName}) {
		if(exists $self->{'config'}->{'service_templates'}->{$serviceName}->{'system'}) {
			my @templateNames = keys %{$self->{'config'}->{'service_templates'}->{$serviceName}->{'system'}};
			 $templateScope = 'system' if $templateName ~~ @templateNames;
		}

		if(exists $self->{'config'}->{'service_templates'}->{$serviceName}->{'site'}) {
			my @templateNames = keys %{$self->{'config'}->{'service_templates'}->{$serviceName}->{'site'}};
			$templateScope = 'system' if $templateName ~~ @templateNames;
		}
	}

	$templateScope;
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
