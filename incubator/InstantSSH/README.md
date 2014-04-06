## i-MSCP InstantSSH  plugin v0.0.2

Plugin allowing to provide full SSH access to customers.

### LICENSE

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

### INTRODUCTION

This plugin allow to give your customers a full SSH access by allowing them to add their SSH keys in i-MSCP. This plugin
doesn't provide a secured shell environment such as jailkit. Thus, customers to which an SSH access is given must be
trusted even if they are restricted in their actions. For the same reason, no reseller interface is provided at this
moment. Thus, SSH permissions can be added only through the admin interface.

For each customer, you can set the maximum number of allowed SSH keys and choose if he can override the default SSH key
options which are defined in the plugin configuration file.

SSH key options which are allowed are those specified in the authorized_keys file documentation (see man authorized_keys).
You must be aware that while the SSH keys validity is checked, no test is made for the SSH key options. Therefore,
customers which are allowed to override the SSH key options must know what they are doing.

Default SSH key options are set as follow:

	no-agent-forwarding,no-port-forwarding,no-X11-forwarding

which in order:

* Forbids authentication agent forwarding
* Forbids TCP forwarding
* Forbids X11 forwarding

You can override default SSH key options by editing the plugin configuration file.

### REQUIREMENTS

* i-MSCP >= 1.1.5 (plugin API >= 0.2.8)
* openSSH server with public key authentication support enabled

#### Debian / Ubuntu packages to install

* libfile-homedir-perl

### INSTALLATION

1. Login into the panel as admin and go to the plugin management interface
2. Upload the **InstantSSH** plugin archive
3. Activate the plugin

### UPDATE

1. Backup your current config file **plugins/InstantSSH/config.php**
2. Login into the panel as admin and go to the plugin management interface
3. Upload the **InstantSSH** plugin archive
4. Restore your **plugins/InstantSSH/config.php** (compare it with new config file first)
5. Click on the **Update Plugins** button in the plugin management interface

### AUTHOR

 * Laurent Declercq <l.declercq@nuxwin.com>
