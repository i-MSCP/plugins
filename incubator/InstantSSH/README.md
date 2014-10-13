## i-MSCP InstantSSH plugin v2.0.0

Plugin allowing to provide full or jailed shell access to your customers.

### Introduction

This plugin allow to give your customers a full or jailed shell access.

A customer to which SSH permissions are given can add its own SSH keys and use them to authenticate on the server.

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

#### Jailed shells

The Jailed shells allow you to provide SSH access to your customers in a secured and restricted environment from which
they can theorically not escape. It's the preferable way to give an SSH access to an un-trusted customer.

Several commands can be added into the customers jails by simply adding the needed application sections into the
**app_sections configuration option**.

The default configuration comes with a set of preselected application sections which allow to setup a very
restricted jailed shell environment. This environment is setup by using busybox which combines tiny versions of many
common UNIX utilities into a single small executable.

See the plugin **config.php** file for further details.

### Plugin usage

The developement of this plugin took me a lot of time, especially the Jailbuilder layer which allows to build the jailed
shell environments. Thus, I would ask a small contribution for use of this plugin by doing a donation on my paypal
account ( paypal@nuxwin.com ). If you don't understand such asks, or if you do not want donate, just don't use this
plugin.

### Requirements

* i-MSCP >= 1.1.14 (plugin API >= 0.2.11)
* openSSH server with public key authentication support enabled

#### Debian / Ubuntu packages to install before installing this plugin

* busybox
* libpam-chroot
* makejail

You can install these packages by executing the following command:

	# aptitude update
	# aptitude install busybox libpam-chroot makejail

**Note:** If a package is not installed on your system, the plugin will thrown an error.

### Installation

1. Login into the panel as admin and go to the plugin management interface
2. Upload the **InstantSSH** plugin archive
3. Activate the plugin

### Update

1. Be sure that all required packages as mentionned in the requirements section are installed on your system
2. Backup your current config file **plugins/InstantSSH/config.php**
3. Login into the panel as admin and go to the plugin management interface
4. Upload the **InstantSSH** plugin archive
5. Restore your **plugins/InstantSSH/config.php** (compare it with the new version first)
6. Click on the **Update Plugins** button in the plugin management interface

### Troubleshooting

#### Ubuntu Lucid

The the **pam_chroot.so** module provided libpam-chroot package from the Ubuntu Lucid repository is buggy. For instance,
you can see such a log in the /var/log/auth.log:

	...
	Oct 13 21:04:31 lucid sshd[1509]: PAM unable to dlopen(/lib/security/pam_chroot.so): /lib/security/pam_chroot.so: undefined symbol: __stack_chk_fail_local
	Oct 13 21:04:31 lucid sshd[1509]: PAM adding faulty module: /lib/security/pam_chroot.so
	...

This can be easily fixed by following this procedure:

	# cd /usr/usr/local
	# mkdir libpam-chroot
	# cd libpam-chroot
	# apt-get install build-essential devscripts debhelper libpam0g-dev
	# apt-get source libpam-chroot && cd libpam-chroot*

Then edit the Makefile file to replace the line:

	CFLAGS=-fPIC -O2 -Wall -Werror -pedantic

by

	CFLAGS=-fPIC -O2 -Wall -Werror -pedantic -fno-stack-protector


Once it's done, you must rebuild and reinstall the package as follow:

	# dpkg-buildpackage -uc -us
	# cd ..
	# dpkg -i libpam-chroot_0.9-3_i386.deb

**Refs:**
  - https://answers.launchpad.net/ubuntu/+source/openssh/+question/33707

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
  - [Sascha Bay aka TheCry](https://github.com/thecry "Sascha Bay")

### Author(s)

 * Laurent Declercq <l.declercq@nuxwin.com>
