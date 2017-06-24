# i-MSCP PolicydSPF plugin v1.2.0

Provides Postfix policy server for RFC 4408 SPF checking.

## Introduction

postfix-policyd-spf-perl is a basic Postfix policy engine for Sender Policy
Framework (SPF) checking. It is implemented in pure Perl and uses Mail::SPF.

## Requirements

* i-MSCP >= Serie 1.4.x
* i-MSCP Postfix server implementation

## Installation

1. Be sure that all requirements as stated in the requirements section are met
2. Upload the plugin through the plugin management interface
3. Activate the plugin through the plugin management interface

## Update

1. Be sure that all requirements as stated in the requirements section are met
2. Backup your plugin configuration file if needed
3. Upload the plugin through the plugin management interface

### Restore you plugin configuration file if needed

1. Restore your plugin configuration file (compare it with the new version first)
2. Update the plugin list through the plugin management interface

## Configuration

See [Configuration file](../PolicydSPF/config.php)

When changing a configuration parameter in the plugin configuration file, don't
forget to trigger a plugin list update, else you're changes will not be token
into account.

## License

    i-MSCP PolicydSPF plugin
    copyright 2016-2017 Laurent Declercq <l.declercq@nuxwin.com>
    copyright 2016 Ninos Ego <me@ninosego.de>
    
    This library is free software; you can redistribute it and/or
    modify it under the terms of the GNU Lesser General Public
    License as published by the Free Software Foundation; either
    version 2.1 of the License, or (at your option) any later version.
    
    This library is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
    Lesser General Public License for more details.
    
    You should have received a copy of the GNU Lesser General Public
    License along with this library; if not, write to the Free Software
    Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
