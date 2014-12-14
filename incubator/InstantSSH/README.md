## i-MSCP InstantSSH plugin v3.1.0

Plugin allowing to provide full or restricted shell access to your customers.

### Introduction

This plugin allow to give your customers a full or restricted shell access.

A customer to which SSH permissions are given can create SSH users and use them to login on the server.

For each customer, you can set the maximum number of allowed SSH users and choose if they can override the default
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

#### Jailed shells

The Jailed shells allow you to provide SSH access to your customers in a restricted environment from which they can
theoretically not escape. It's the preferable way to give an SSH access to an un-trusted customer.

Several commands can be added into the jails by simply adding the required application sections to the **app_sections**
configuration option.

The default configuration comes with a set of preselected application sections which allow to setup very restricted
jailed shell environments. These environments are setup using busybox which combines tiny versions of many common UNIX
utilities into a single small executable.

See the plugin **config.php** file for further details.

### Requirements

* i-MSCP >= 1.1.19 (plugin API >= 0.2.14)
* openSSH server with both, passsword and key-based authentication support enabled

**Note:** If you want allow only the key-based authentication, you can set the **passwordless_authentication**
configuration option to **TRUE** in the plugin configuration file.

#### Debian / Ubuntu packages

* busybox
* libpam-chroot
* makejail

You can install these packages by executing the following commands:

	# aptitude install busybox libpam-chroot makejail

**Notes**
  - If a package is not installed on your system, the plugin installer throws an error
  - If you have any problem with the PAM chroot module read the **Troubleshooting** section below

### Installation

1. Be sure that all required packages as mentioned in the requirements section are installed on your system
2. Login into the panel as admin and go to the plugin management interface
3. Upload the plugin archive
4. Configure the plugin for your needs by editing the **plugins/InstantSSH/config.php** configuration file
4. Install the plugin

### Update

1. Be sure that all required packages as mentioned in the requirements section are installed on your system
2. Backup the **plugins/InstantSSH/config.php** configuration file
3. Login into the panel as admin and go to the plugin management interface
4. Deactivate the plugin
5. Upload the plugin archive
6. Configure the plugin for your needs by editing the **plugins/InstantSSH/config.php** configuration file
7. Activate the plugin

**Note:** Don't forget to read the [update errata](update_errata.md) file.

### Troubleshootings

#### PAM chroot module

The **PAM chroot** module shipped with some libpam-chroot package versions (eg. Ubuntu Lucid) doesn't work as expected.
For instance, You can see the following logs in the /var/log/auth.log file:

	...
	Oct 13 21:04:31 lucid sshd[1509]: PAM unable to dlopen(/lib/security/pam_chroot.so): /lib/security/pam_chroot.so: undefined symbol: __stack_chk_fail_local
	Oct 13 21:04:31 lucid sshd[1509]: PAM adding faulty module: /lib/security/pam_chroot.so
	...

You can fix this easily by following this procedure:

	# cd /usr/local/src
	# mkdir libpam-chroot
	# cd libpam-chroot
	# apt-get install build-essential debhelper libpam0g-dev
	# apt-get source libpam-chroot
	# cd libpam-chroot*

Edit the **Makefile** file to replace the line:

	CFLAGS=-fPIC -O2 -Wall -Werror -pedantic

by

	CFLAGS=-fPIC -O2 -Wall -Werror -pedantic -fno-stack-protector

Rebuild and reinstall the package as follow:

	# dpkg-buildpackage -uc -us
	# cd ..
	# dpkg -i libpam-chroot*.deb

### Plugin usage

The development of this plugin took me a lot of time, especially the Jailbuilder layer which allows to build the jailed
shell environments. Thus, I would ask a small contribution for use of this plugin by doing a donation on my paypal
account ( paypal@nuxwin.com ). If you don't understand such asks, or if you do not want donate, just don't use this
plugin.

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

  - [Osna-Solution UG](http://portal.osna-solution.de "Osna-Solution UG")
  - [Space2Place WebHosting](http://space2place.de "Space2Place WebHosting")

### Author

 * Laurent Declercq <l.declercq@nuxwin.com>
