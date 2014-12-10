# i-MSCP cronjobs plugin v0.0.1

Plugin providing a cron time-based job scheduler for i-MSCP.

WARNING: This plugin is still under development, not ready for use

## Introduction

This plugin provide a cron time-based job scheduler for i-MSCP. 

Administrators give cron permissions to their resellers, and the resellers give the cron permissions to their customers
according their own permissions. This is a cascading permissions system. For instance, a reseller will be able to give
the full cron job permission to a customer only if he has also this permission.

Administrators can also add their own cron jobs using their own interface which is less restricted than the customer
interface. Indeed, administrators can set the user to use for the cron job execution while the customers cannot.

Each cron jobs is added in the crontab file ( see crontab(5) ) which belongs to the user under which the cron command
must be run.

## Requirements

* i-MSCP >= 1.1.17 (plugin api >= 0.2.13)
* InstantSSH plugin >= 3.0.2 ( only if you want enable support for jailed cron jobs )

**Note:** Activation of the InstantSSH plugin is not mandatory to enable support for jailed cron jobs. Only its presence
is required. If this plugin is already activated and if you want enable support for jailed cron jobs, just upload the
InstantSSH plugin and once it's done, deactivate and reactivate this plugin. You must note that once enabled, support for
the jailed cron jobs cannot be disabled and thus, deletion of the InstantSSH plugin is prohibited.

## Installation

1. Login into the panel as admin and go to the plugin management interface
2. Upload the plugin archive
3. Activate the plugin

## Update

1. Backup your **plugins/CronJobs/config.php** configuration file
2. Login into the panel as admin and go to the plugin management interface
3. Deactivate the plugin
4. Upload the plugin archive
5. Restore your **plugins/cronjobs/config.php** configuration file ( compare it with the new version first )
6. Activate the plugin

### Cron job types

Three types of cron jobs are available, which are in order: **Url**, **Jailed** and **Full**.

#### Url

The Url cron jobs allow to schedule commands executed using GNU Wget. The commands must be a valid HTTP(s) URL.

This cron job type is always available. When full cron jobs is selected, the URL cron jobs are run using the wget
command from the system, else, they are run using the wget command from the jailed environment.

#### Jailed

The jailed cron jobs allow to schedule commands which are run through **/bin/sh** in a jailed environment. By default the
plugin will create a jailed environment which provides:

* GNU Wget
* PHP (CLI) and some modules (mysqlnd, pdo, gd, intl, json, mcrypt, mysql, mysqli, pdo_mysql, readline)
* Mysql Monitor and mysqldump
* A set of common UNIX utilities

Only one jailed environment is created for all jailed cron jobs. This is by design. The most important here, is that the
cron jobs cannot broke the whole system.

##### Availability

The jailed cron jobs are available only when the [InstantSSH](../InstantSSH/README.md) plugin is also present on the
system, whatever it is activated or not. The CronJobs plugin reuses the jail builder library which is provided by the
InstantSSH plugin to build the jailed environment.

#### Full

The full cron jobs are identical to the jailed cron jobs, excepted the fact that the commands are not run in a jailed
environment. Such cron jobs should be reserved to trusted users.

## Crontab files

The plugin handles the crontab files automatically. You must note that any manual change in a crontab file which is
already under the control of the plugin will be automatically purged. Therefore, if you want add a cron job in a crontab
file which is already under the control of the plugin, you must add it through the cronjobs interface of the plugin.

An crontab file is under the control of the plugin as soon as you add a cron task for the user to which it belong to,
through the cronjob interface provided by this plugin.

## Cron job precedence

The jailed cron jobs take precedence over the full cron jobs. This essentially mean that if you create a jailed cron
job for a specific user, any other cron job for this user will be also jailed, whatever the value entered for the cron
job type.

## Usage

The development of this plugin took me a lot of time. Thus, I would ask a small contribution for use of this plugin by
doing a donation on my paypal account ( paypal@nuxwin.com ). If you don't understand such asks, or if you do not want
donate, just don't use this plugin.

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
