# i-mscp cronjobs plugin v0.0.1

plugin implementing a cron time-based job scheduler for i-MSCP.

WARNING: This plugin is still under development, not ready for use

## Introduction

This plugin provide a cron time-based job scheduler for i-MSCP. Administrators give cron permissions to their reseller,
and the resellers give cron permissions to their customers according their own permissions. This is a cascading permissions
level.  For instance a reseller will be able to give the full cronjob permission to a customer only if he has also this
permission.

Administrators can also add their own cron jobs using their own interface. The administrator interface differ from the
customer interface in sense that only administrators can setup the unix user to use for the command execution.

### Cronjob types

Three types of cronjobs are available, which are in order: **Url**, **Jailed** and **Full**.

The jailed cronjob type is available and functional only when the InstantSSH plugin is also installed on the system. Thus,
this is a pre-requirement for use of jailed cronjob types.

#### Url cronjob

This cronjob type allow to schedule commands which are run through Wget. The command must be a valid URL.

#### Jailed cronjob

This cronjob type allow to schedule command which are run through sh,  in a jailed environment. Thus, any needed binary
should be available inside the jail, else, the command will fail. By default the plugin will create a specific jailed
environment which provides Perl, PHP and all the standard commands.

#### Full cronjob

This cronjob type is identical to the jailed cronjob type, excepted the fact that the command is not run in a jailed
environment. Giving to an untrusted customer the permissions to add such a cronjob is not really safe and can lead to
several security issue on your system.

## Requirements

* i-MSCP >= 1.1.15 (plugin api >= 0.2.12)
* instantssh plugin >= 2.0.4 (only if you want enable support for jailed cronjobs)

## Installation

1. Login into the panel as admin and go to the plugin management interface
2. Upload the plugin archive
3. Activate the plugin

## Update

1. Backup your **plugins/cronjobs/config.php** configuration file
2. Login into the panel as admin and go to the plugin management interface
3. Deactivate the plugin
4. Upload the plugin archive
5. Restore your **plugins/cronjobs/config.php** configuration file (compare it with the new version first)
6. Activate the plugin

## Plugin usage

the development of this plugin took me a lot of time. thus, i would ask a small contribution for use of this plugin by
doing a donation on my paypal account ( paypal@nuxwin.com ). if you don't understand such asks, or if you do not want
donate, just don't use this plugin.

## License

	i-mscp cronjobs plugin
	copyright (c) 2014 laurent declercq <l.declercq@nuxwin.com>

	this library is free software; you can redistribute it and/or
 	modify it under the terms of the gnu lesser general public
	license as published by the free software foundation; either
	version 2.1 of the license, or (at your option) any later version.

	this library is distributed in the hope that it will be useful,
	but without any warranty; without even the implied warranty of
	merchantability or fitness for a particular purpose.  see the gnu
	lesser general public license for more details.

	you should have received a copy of the gnu lesser general public
	license along with this library; if not, write to the free software
	foundation, inc., 51 franklin street, fifth floor, boston, ma  02110-1301  usa

 see [lgpl v2.1](http://www.gnu.org/licenses/lgpl-2.1.txt "lgpl v2.1")

## Sponsors

## Author(s)

 * laurent declercq <l.declercq@nuxwin.com>
