# i-MSCP cronjobs plugin v1.2.0

## Introduction

This plugin provides a cron time-based job scheduler.

Administrators give cron job permissions to their resellers, and the resellers give cron job permissions to their
customers according their own permissions. For instance, a reseller will be able to give the full cron job permission to
a customer only if he has also this permission.

Administrators can also add their own cron jobs using their own interface which is less restricted than the customer
interface in the sense that they can set the UNIX user to use for the cron job execution while the customers cannot.

Each cron job is added in the crontab file (see crontab(5)) that belongs to the UNIX user under which the cron command
must be run. For customers, this is the Web user as created by i-MSCP.

## Requirements

* i-MSCP version >= 1.2.3

### Requirements for jailed cron jobs support

* [InstantSSH](../InstantSSH/README.md) plugin >= 3.2.0

#### Debian / Ubuntu packages

* msmtp

You can install this package by executing the following command:

```shell
# aptitude update && aptitude install msmtp
```

**Notes**
  - The msmtp package is required to allow cron to send email notifications from the jailed environment

## Installation

1.Be sure that all requirements as stated in the requirements section are meets
2. Install the InstantSSH plugin if you want jailed cron jobs support
3. Upload the plugin through the plugin management interface
4. Install the plugin through the plugin management interface

**Note:** Depending on your system and if support for jailed cron jobs is available, installation can take up to several
minutes. Time is needed to build jail.

## Update

1. Be sure that all requirements as stated in the requirements section are meets
2. Backup your plugin configuration file if needed
3. Upload the plugin through the plugin management interface
4. Restore your plugin configuration file if needed ( compare it with the new version first )
5. Update the plugin list through the plugin management interface

**Note:** Prior any update attempt, do not forget to read the [update_errata.md](update_errata.md) file.

## Activation of jailed cron jobs support

If this plugin is already activated and if you want enable support for jailed cron jobs later on, you must follow these
instructions:
 
1. Install the InstantSSH plugin
2. Deactivate this plugin through the plugin management interface
3. Re-activate this plugin through the plugin management interface

## Cron job types

Three types of cron jobs are available, which are in order: **URL**, **Jailed** and **Full**.

### URL

The URL cron jobs are always available. They allow to schedule URL commands executed via GNU Wget. The commands must be
valid HTTP(s) URLs.

**Note:** When a customer has permission for jailed cron jobs, the URL cron jobs are run inside the jailed environment,
else they are run outside the jailed environment. This is by design, and this do not change anything from the customer
point of view.
 
### Jailed

The jailed cron jobs allow to schedule Shell commands which are run through the Shell interpreter in a jailed environment.
By default the plugin will create a jailed environment which provides:

* GNU Wget
* PHP (CLI) and some PHP modules ( mysqlnd, pdo, gd, intl, json, mcrypt, mysql, mysqli, pdo_mysql, readline )
* Mysql monitor and mysqldump
* A set of common UNIX utilities

**Note:** Only one jailed environment is created for all jailed cron jobs. The most important here, is that the cron
jobs cannot broke the whole system.

#### Availability

The jailed cron jobs are available only when the [InstantSSH](../InstantSSH/README.md) plugin is also present on the
system, whatever it is activated or not. The CronJobs plugin reuses the jail builder library which is provided by the
InstantSSH plugin to manage the jailed environment.

The jailed cron jobs doesn't apply to administrators.

### Full

The full cron jobs are identical to the jailed cron jobs, excepted the fact that the Shell commands are not run inside a
jailed environment. Such cron jobs should be reserved to trusted users.

## Crontab files

The plugin handles the crontab files automatically. You must note that any manual change made in a crontab file which is
under the control of this plugin will be automatically overriden on next processing. Therefore, once that a crontab file
is under the control of this plugin, you must use the cron jobs interface provided by this plugin to add, edit or delete
a cron job in this file.

A crontab file is under the control of this plugin as soon as you add a cron task for the user to which it belong to,
through the cron jobs interface provided by this plugin.

## Interfaces access

### Cron jobs permissions interface

The cron job permissions interface allow to give cron job permissions, either to the resellers in the context of the
administrators, or to customers in the context of resellers.

* Administrators can access the cron job permissions interface through the **settings** menu
* Resellers can access the cron job permissions interface through the **Customers** menu

**Note:** When cron job permissions are updated, any cron job which doesn't fit with the new permissions are simply
deleted. For instance, if the execution frequency of a specific cron job is lower than the new cron jobs frequency limit,
the cron job is automatically removed.

### Cron jobs interface

The cron jobs interface allows administrators and customers to add, edit and delete cron jobs.

* Administrators can access the cron jobs interface through the **System tools** menu
* Customers can access the cron jobs interface through the **Webtools** menu

## Configuration

See [Configuration file](../CronJobs/config.php)

**Note:** When changing a configuration parameter in the plugin configuration file, do not forget to trigger plugin
change by updating the plugin list through the plugin management interface.

## Translation

You can translate this plugin through the [Transifex Localization Platform](https://www.transifex.com/organization/i-mscp/dashboard/cronjobs)

## Troubleshootings

See [InstantSSH Troubleshootings](https://github.com/i-MSCP/plugins/tree/master/incubator/InstantSSH#troubleshootings)

## License

```
i-MSCP CronJobs plugin
Copyright (c) 2014-2015 laurent declercq <l.declercq@nuxwin.com>

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

* [IP-Projects GmbH & Co. KG](https://www.ip-projects.de/ "IP-Projects GmbH & Co. KG")
* [Space2Place WebHosting](http://space2place.de "Space2Place WebHosting")

## Author

 * Laurent Declercq <l.declercq@nuxwin.com>
