## i-MSCP OwnDDNS plugin v0.0.4

Plugin allowing to manage your own DDNS service with i-MSCP.

If you install this plugin manually, make sure it is installed in
gui/plugins/ - if the folder is called different it will not work!

### Requirements

	- i-MSCP versions >= 1.1.11
	- Bind must be activated on i-MSCP
	- Domain for your OwnDDNS must use the nameserver of your i-MSCP installation

### Installation

	- Login into the panel as admin and go to the plugin management interface
	- Upload the OwnDDNS plugin archive
	- Activate the plugin
	
### Update

	- Backup your current plugins/OwnDDNS/config.php file
	- Login into the panel as admin and go to the plugin management interface
	- Upload the OwnDDNS plugin archive
	- Restore your plugins/OwnDDNS/config.php file (check for any change)
	- Update the plugin list through the plugin interface
	
### Configuration

See the plugins/OwnDDNS/config.php file.

### Important
Some FRITZ!Box models don't work with a SSL url. You must use the non ssl url, even if the ssl feature of the panel is activated.
FRITZ!Box model: 3270, 7170, 7250, 7270

### License

```
i-MSCP  OwnDDNS plugin
Copyright (C) 2010-2016 Sascha Bay <info@space2place.de>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

See [LICENSE](LICENSE)

### Authors

* Sascha Bay <info@space2place.de> (Author)
