# i-MSCP PanelRedirect plugin v1.2.0

Provides access to i-MSCP panel (and tools) through standard http(s) ports.

## Requirements

 - i-MSCP >= Serie 1.4.x

## Installation

1. Upload the plugin through the plugin management interface
2. Install the plugin through the plugin management interface

## Update

1. Backup your plugin configuration file if needed
2. Upload the plugin through the plugin management interface
3. Restore your plugin configuration file if needed
4. Update the plugin list through the plugin management interface

## Usage of BASE_SERVER_VHOST (control panel host) as customer domain

Note that if you use `BASE_SERVER_VHOST` (control panel host) as customer
domain, the plugin will automatically remove it own vhost files to avoid
interfering with the customer vhosts files. In such case, it is up to you to
enable the redirect feature for the domain.

Note that here, the `domain` word is used as generic term to designate either a
domain, a domain alias, or a subdomain.

Generally speaking, using this plugin when you also use `BASE_SERVER_VHOST` as
customer domain doesn't make any sense. In short, this is a NOOP.

## License

    i-MSCP PanelRedirect plugin
    Copyright (C) 2016-2017 by Laurent Declercq <l.declercq@nuxwin.com>
    Copyright (C) 2014-2016 by Ninos Ego <me@ninosego.de>
    
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
