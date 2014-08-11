#!/usr/bin/perl

=head1 NAME

 Hooks::TemplateEditor

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

package Hooks::TemplateEditor;

use iMSCP::Debug;
use iMSCP::HooksManager;

=head1 DESCRIPTION

 Script which is responsible to register the template loader when i-MSCP is being updated. This is needed for
system templates which are loaded by servers/packages installers (before any plugin run).

=head1 EVENT LISTENER

=over 4

=item registerTemplateLoader

 Register template loader by instantiating the TemplateEditor plugin

 Return int 0, other on failure

=cut

sub registerTemplateLoader
{
	my $backendPluginFile = "$main::imscpConfig{'GUI_ROOT_DIR'}/plugins/TemplateEditor/backend/TemplateEditor.pm";

	# We trap any compile time error(s)
	eval { require $backendPluginFile; };

	if($@) { # We got an error due to a compile time error or missing file
		error($@);
		return 1;
	}

	eval {
		$pluginInstance = $pluginClass->getInstance(
			'hooksManager' => iMSCP::HooksManager->getInstance(), 'action' => 'change'
		);
	};

	if($@) {
		error("An unexpected error occured: $@");
		return 1;
	}

	0;
}

iMSCP::HooksManager->getInstance()->register('beforeInstall', \&registerTemplateLoader);

=back

=head1 AUTHOR

 Laurent Declercq <l.declercq@nuxwin.com>

=cut

1;
