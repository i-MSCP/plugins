# i-MSCP cronjobs plugin v0.0.1

Plugin providing a cron time-based job scheduler for i-MSCP.

WARNING: This plugin is still under development, not ready for use

## Introduction

This plugin provide a cron time-based job scheduler for i-MSCP. 

Administrators give cron permissions to their resellers, and the resellers are giving cron permissions to their
customers according their own permissions. This is a cascading permissions system. For instance, a reseller will be able
to give the full cron job permission to a customer only if he has also thispermission.

Administrators can also add their own cron jobs using their own interface which is less restricted than the customer
interface. Indeed, administrators can set the user to use for the cron job execution while the customers cannot.

### Cron job types

Three types of cron jobs are available, which are in order: **Url**, **Jailed** and **Full**.

#### Url

The Url cron jobs allow to schedule commands executed using GNU Wget. The commands must be a valid HTTP(s) URL.

#### Jailed

The jailed cron jobs allow to schedule commands which are run through /bin/sh (default) in a jailed cron environment.
By default the plugin will create a jailed cron environment which provides Perl, PHP and the common UNIX utilities as
provided by the BusyBox excutable.

Only one jailed cron environment is created for all jailed cron jobs. This is by design. The most important thing here
is that the cron jobs cannot broke the whole system.

##### Jailed cron jobs availability

The jailed cron jobs are available only when the InstantSSH plugin is also present on the system, whatever it is
activated or not. The CronJobs plugin reuses the jail builder library which is provided by the InstantSSH plugin to
build the jailed cron environment.

#### Full

The full cron jobs are identical to the jailed cron jobs, excepted the fact that the commands are not run in a jailed
cron environment.

## Requirements

* i-MSCP >= 1.1.15 (plugin api >= 0.2.12)
* InstantSSH plugin >= 2.0.4 ( only if you want enable support for jailed cron jobs )

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
