## i-MSCP InstantSSH plugin v0.0.3

Plugin allowing to provide full SSH access to customers.

### Introduction

This plugin allow to give your customers a full SSH access by allowing them to add their SSH keys in i-MSCP. This plugin
doesn't provide a secured shell environment such as jailkit. Therefore, customers to which an SSH access is given must
be trusted even if they are restricted in their actions. For the same reason, no reseller interface is provided at this
moment. Thus, SSH permissions can be added only through the admin interface.

For each customer, you can set the maximum number of allowed SSH keys, and choose if they can override the default
authentication options. The authentication options are those specified in the documentation of the authorized_keys file
(see man authorized_keys).

Default authentication options are set as follow:

	no-agent-forwarding,no-port-forwarding,no-X11-forwarding

which in order:

* Forbids authentication agent forwarding
* Forbids TCP forwarding
* Forbids X11 forwarding

You can override default authentication options by editing the **default_ssh_auth_options** parameter which is defined
in the plugin configuration file. In that file, you can also restrict the list of authentication options that your
customers can add by editing the **allowed_ssh_auth_options** parameter. You must note that any authentication option
appearing in the the default authentication string must also be specified in the **allowed_ssh_auth_options** parameter.

### Requirements

* i-MSCP >= 1.1.5 (plugin API >= 0.2.8)
* openSSH server with public key authentication support enabled

#### Debian / Ubuntu packages

* libfile-homedir-perl

### Installation

1. Login into the panel as admin and go to the plugin management interface
2. Upload the **InstantSSH** plugin archive
3. Activate the plugin

### Update

1. Backup your current config file **plugins/InstantSSH/config.php**
2. Login into the panel as admin and go to the plugin management interface
3. Upload the **InstantSSH** plugin archive
4. Restore your **plugins/InstantSSH/config.php** (compare it with the new configuration file first)
5. Click on the **Update Plugins** button in the plugin management interface

### License

 i-MSCP InstantSSH plugin
 Copyright (C) 2014 Laurent Declercq <l.declercq@nuxwin.com>

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

 See [LGPL v2.1](http://www.gnu.org/licenses/lgpl-2.1.txt "LGPL v2.1")

### Author

 * Laurent Declercq <l.declercq@nuxwin.com>
