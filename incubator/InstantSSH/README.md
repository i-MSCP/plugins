# i-MSCP InstantSSH plugin v3.2.0

Plugin which allows to provide full or restricted shell access to i-MSCP customers.

## Introduction

This plugin allows to give your customers a full or restricted shell access.

A customer to which SSH permissions are given can create SSH users and use them to login on the server.

For each customer, you can set the maximum number of allowed SSH users and choose if they can override the default
authentication options. The authentication options are those specified in the documentation of the authorized_keys file.

## Requirements

* i-MSCP version >= 1.2.3
* openSSH server with both, passsword and key-based authentication support enabled

**Note:** If you want allow only the key-based authentication, you can set the **passwordless_authentication**
configuration option to **TRUE** in the plugin configuration file.

### Debian / Ubuntu packages

* build-essential
* busybox-static or busybox
* libpam-chroot
* makejail

You can install these packages by executing the following commands:

```bash
# aptitude update && aptitude -y install build-essential busybox-static libpam-chroot makejail
```

**Notes**

* If a package is not installed on your system, the plugin installer will throw an error
* If you have any problem with the PAM chroot module read the **Troubleshooting** section below

## Installation

1. Be sure that all requirements as stated in the requirements section are meets
2. Upload the plugin through the plugin management interface
3. Install the plugin through the plugin management interface

## Update

1. Be sure that all requirements as stated in the requirements section are meets
2. Backup your plugin configuration file if needed
3. Upload the plugin archive through the plugin management interface
4. Restore your plugin configuration file if needed ( compare it with the new version first )
5. Update the plugin list through the plugin management interface

**Note:** Prior any update attempt, do not forget to read the [update_errata.md](update_errata.md) file.

## Configuration

### Authentication options

Default authentication options are set as follow:

```
no-agent-forwarding,no-port-forwarding,no-X11-forwarding
```

which in order:

* Forbids authentication agent forwarding
* Forbids TCP forwarding
* Forbids X11 forwarding

You can override default authentication options by editing the **default_ssh_auth_options** option which is defined in
the plugin configuration file. In that file, you can also restrict the list of authentication options that your
customers can add by editing the **allowed_ssh_auth_options** option. You must note that any authentication option
appearing in the the default authentication string must also be specified in the **allowed_ssh_auth_options** option.

### Jailed shells

The Jailed shells allow you to provide SSH access to your customers in a restricted environment from which they can
theoretically not escape. It's the preferable way to give an SSH access to an un-trusted customer.

Several commands can be added into the jails by simply adding the required application sections to the **app_sections**
configuration option.

The default configuration comes with a set of preselected application sections which allow to setup very restricted
jailed shell environments.

Be aware that the creation of the jailed environments may take time, depending on many factors such as the type of your
server, the number of file to copy inside the jails and so on...

See [Configuration file](../InstantSSH/config.php) for further details.

**Note:** When changing a configuration parameter in the plugin configuration file, do not forget to trigger plugin
change by updating the plugin list through the plugin management interface.

## Translation

You can translate this plugin by copying the [l10n/en_GB.php](l10n/en_GB.php) language file, and by translating all the
array values inside the new file.

Feel free to post your language files in our forum for intergration in a later release. You can also fork the plugin
repository and do a pull request if you've a github account.

**Note:** File encoding must be UTF-8.

## Plugin usage

The development of this plugin took me a lot of time, especially the Jailbuilder layer which allows to build the jailed
shell environments. Thus, I would ask a small contribution for use of this plugin by doing a donation on my paypal
account ( paypal@nuxwin.com ). If you don't understand such asks, or if you do not want donate, just don't use this
plugin.

## Troubleshootings

### PAM chroot module

The **PAM chroot** module shipped with some libpam-chroot package versions (eg. Ubuntu Lucid) doesn't work as expected.
For instance, You can see the following logs in the /var/log/auth.log file:

```
...
Oct 13 21:04:31 lucid sshd[1509]: PAM unable to dlopen(/lib/security/pam_chroot.so): /lib/security/pam_chroot.so: undefined symbol: __stack_chk_fail_local
Oct 13 21:04:31 lucid sshd[1509]: PAM adding faulty module: /lib/security/pam_chroot.so
...
```

You can fix this easily by following this procedure:

```bash
# cd /usr/local/src
# mkdir libpam-chroot
# cd libpam-chroot
# apt-get install build-essential debhelper libpam0g-dev
# apt-get source libpam-chroot
# cd libpam-chroot*
```

Edit the **Makefile** file to replace the line:

```
CFLAGS=-fPIC -O2 -Wall -Werror -pedantic
```

by

```
CFLAGS=-fPIC -O2 -Wall -Werror -pedantic -fno-stack-protector
```

Rebuild and reinstall the package as follow:

```bash
# dpkg-buildpackage -uc -us
# cd ..
# dpkg -i libpam-chroot*.deb
```

## License

```
i-MSCP InstantSSH plugin
Copyright (C) 2014-2015 Laurent Declercq <l.declercq@nuxwin.com>

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
```

See [LICENSE](LICENSE)

## Sponsors

* [Osna-Solution UG](http://portal.osna-solution.de "Osna-Solution UG")
* [Space2Place WebHosting](http://space2place.de "Space2Place WebHosting")

## Author

* Laurent Declercq <l.declercq@nuxwin.com>
