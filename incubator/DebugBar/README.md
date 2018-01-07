# i-MSCP DebugBar plugin v2.0.0

Provides debug information for i-MSCP.

## Introduction

This plugin is a development helper for i-MSCP that provides many debug
information. The plugin comes with a set of components, each providing a
particular set of debug information.

Right now, the followings components are provided:

- Version: i-MSCP version, list of all available PHP extensions.
- Variables: Contents of $_GET, $_POST, $_COOKIE, $_FILES, $_SESSION and $_ENV
  variables.
- Timer: Timing information of current request, time spent in level script;
  support custom timers. Also average, min and max time for requests.
- Files: Number and size of files included with complete list.
- Memory: Peak memory usage, memory usage of level scripts and the whole
  application; support for custom memory markers.
- Database: Full listing of SQL queries and the time for each.
- APCu: Provides information about PHP APCu userland cache state
- Cache: Provides information about PHP OPcache cache state

## Requirements

* i-MSCP Serie ≥ 1.6.x

## Installation

1. Upload the plugin archive through the plugin management interface
2. Activate the plugin through the plugin management interface

## Update

1. Backup your plugin configuration file if needed
2. Upload the plugin through the plugin management interface
3. Restore your plugin configuration file if needed
4. Update the plugin list through the plugin management interface

## Configuration

You can set the DebugBar components to use in the plugins/DebugBar/config.php
file.

## License

    i-MSCP DebugBar plugin
    Copyright (C) 2010-2017 by Laurent Declercq <l.declercq@nuxwin.com>
    
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; version 2 of the License
    
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

See [GPL v2](http://www.gnu.org/licenses/gpl-2.0.html "GPL v2")
