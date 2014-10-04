## i-MSCP InstantSSH plugin v2.0.0

Plugin allowing to provide full or restricted SSH access to your customers.

### Introduction

This plugin allow to give your customers a full or restricted SSH access (jailed shell). A customer to which SSH
permissions are given can add its own SSH keys  and use them to authenticate on the server.

For each customer, you can set the maximum number of allowed SSH keys and choose if they can override the default
authentication options. The authentication options are those specified in the documentation of the authorized_keys file
(see man authorized_keys).

Default authentication options are set as follow:

	no-agent-forwarding,no-port-forwarding,no-X11-forwarding

which in order:

* Forbids authentication agent forwarding
* Forbids TCP forwarding
* Forbids X11 forwarding

You can override default authentication options by editing the **default_ssh_auth_options** option which is defined in
the plugin configuration file. In that file, you can also restrict the list of authentication options that your
customers can add by editing the **allowed_ssh_auth_options** option. You must note that any authentication option
appearing in the the default authentication string must also be specified in the **allowed_ssh_auth_options** option.

#### Jailed shell

Jailed shell allow you to provide SSH access to your customers in a secured and restricted environment from which they
cannot out. It's the preferable way to give an SSH access to an un-trusted customer.

Several commands can be added into the customers jails by simply adding the needed application sections into the
**app_sections configuration option**. See the plugin configuration file for further details.

### Requirements

* i-MSCP >= 1.1.14 (plugin API >= 0.2.11)
* openSSH server with public key authentication support enabled

#### Debian / Ubuntu packages to install before installing this plugin

* initscripts
* libfile-homedir-perl
* libpam-chroot
* makejail

You can install these packages by executing the following commands:

	# aptitude update
	# aptitude install initscripts libfile-homedir-perl libpam-chroot makejail

**Note:** If a package is not installed on your system, the plugin will raise an error.

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

### Sponsors

  - [Osna-Solution UG](http://portal.osna-solution.de// "Osna-Solution UG")

### Author(s)

 * Laurent Declercq <l.declercq@nuxwin.com>
