# i-MSCP cronjobs plugin v1.0.0

WARNING: This plugin is still under development, not ready for use

## Introduction

This plugin provide a cron time-based job scheduler for i-MSCP. 

Administrators give cron permissions to their resellers, and the resellers give the cron permissions to their customers
according their own permissions. For instance, a reseller will be able to give the full cron permission to a customer
only if he has also this permission.

Administrators can also add their own cron jobs using their own interface which is less restricted than the customer
interface in the sense that they can set the UNIX user to use for the cron job execution while the customers cannot.

Each cron job is added in the crontab file ( see crontab(5) ) that belongs to the UNIX user under which the cron command
must be run. For customers, this is the Web user as created by i-MSCP.

## Requirements

* i-MSCP >= 1.1.21 ( plugin API >= 0.2.15 )

### Requirements for jailed cron jobs support

* [InstantSSH](../InstantSSH/README.md) plugin >= 3.1.0

**Note:** It is not necessary to activate the InstantSSH plugin. Only its presence is necessary.

#### Debian / Ubuntu packages

* libpam-chroot
* msmtp

You can install this package by executing the following command:

	# aptitude install libpam-chroot msmtp

**Notes**
  - If support for jailed cronjob is detected and a package is not installed on your system, an error will be throw
  - The msmtp package is required to allow cron to send email notifications from the jailed environment.

## Installation

1. Login into the panel as admin and go to the plugin management interface
2. Upload the plugin archive
3. Activate the plugin

## Update

1. Backup your **plugins/CronJobs/config.php** configuration file
2. Login into the panel as admin and go to the plugin management interface
3. Deactivate the plugin
4. Upload the plugin archive
5. Restore your **plugins/CronJobs/config.php** configuration file ( compare it with the new version first )
6. Activate the plugin

## Activation of jailed cron jobs support

If this plugin is already activated and if you want enable support for jailed cron jobs later on, you must follow these
instructions:
 
1. Be sure that all required packages as mentioned in the requirements section are installed on your system
2. Upload the InstantSSH plugin
3. Deactivate this plugin
4. Re-activate this plugin

## Cron job types

Three types of cron jobs are available, which are in order: **URL**, **Jailed** and **Full**.

### URL

The URL cron jobs are always available. They allow to schedule URL commands executed via GNU Wget. The commands must be
valid HTTP(s) URLs.

**Note:** When a customer has permission for jailed cron jobs, the URL cron jobs are run inside the jailed environment,
else they are run outside the jailed environment. This is by design, and this do not change anything from the customer
point of view.
 
### Jailed

The jailed cron jobs allow to schedule commands which are run through the shell interpreter in a jailed environment.
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

The full cron jobs are identical to the jailed cron jobs, excepted the fact that the commands are not run inside a
jailed environment. Such cron jobs should be reserved to trusted users.

## Crontab files

The plugin handles the crontab files automatically. You must note that any manual change made in a crontab file which is
under the control of this plugin will be automatically overriden on next processing. Therefore, once that a crontab file
is under the control of this plugin, you must use the cron jobs interface provided by this plugin to add, edit or delete
a cron job in this file.

A crontab file is under the control of this plugin as soon as you add a cron task for the user to which it belong to,
through the cron jobs interface provided by this plugin.

## Interfaces access

### Cron permissions interfaces

* Administrators can access their cron permissions interface through the **Settings** menu
* Resellers can access their cron permissions interface through the **Customers** menu

### Cron jobs interfaces

* Administrators can access their cron jobs interface through the **System tools** menu
* Customers can access their cron jobs interface through the **Webtools** menu

## Translation

You can translate this plugin by copying the [l10n/en_GB.php](l10n/en_GB.php) language file, and by translating all the
array values inside the new file.

Feel free to post your language files in our forum for intergration in a later release.

**Note:** File encoding must be UTF-8

## Usage

A small contribution for use of this plugin is requested by doing a donation on my paypal account ( paypal@nuxwin.com ).
If you do not want to contribute, you should not use that plugin.

## License

	i-MSCP CronJobs plugin
	copyright (c) 2014 laurent declercq <l.declercq@nuxwin.com>
	
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

 see [lgpl v2.1](http://www.gnu.org/licenses/lgpl-2.1.txt "lgpl v2.1")

## Sponsors

 - [IP-Projects GmbH & Co. KG](https://www.ip-projects.de/ "IP-Projects GmbH & Co. KG")
 - [Space2Place WebHosting](http://space2place.de "Space2Place WebHosting")

## Author(s)

 * laurent declercq <l.declercq@nuxwin.com>
