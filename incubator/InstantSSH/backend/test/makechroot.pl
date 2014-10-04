#!/usr/bin/perl

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

# Script allowing to test creation of jailed environment without any frontEnd

use strict;
use warnings;

use lib "/var/www/imscp/engine/PerlLib", "/var/www/imscp/gui/plugins/InstantSSH/backend";

use iMSCP::EventManager;
use iMSCP::Bootstrapper;
use InstantSSH::JailBuilder;

$ENV{'LC_MESSAGES'} = 'C';
$ENV{'IMSCP_CLEAR_SCREEN'} = 0;

my $user = shift || die("Please enter an unix username.\n");

iMSCP::Bootstrapper->getInstance()->boot({ norequirements => "yes", nolock => "yes", config_readonly => "yes" });

# Load InstantSSH plugin manually
require '/var/www/imscp/gui/plugins/InstantSSH/backend/InstantSSH.pm';

my $instantSSH = Plugin::InstantSSH->getInstance(eventManager => iMSCP::EventManager->getInstance);

# Ensure that the environment is ready to use
$instantSSH->install();

# Let create a jailed environment manually for testing purpose
my $jailBuilder = InstantSSH::JailBuilder->new(config => $instantSSH->{'config'}, user => $user);

# Create the jailed shell environment
exit $jailBuilder->makeJail();
