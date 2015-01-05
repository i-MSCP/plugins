## PanelRedirect v1.0.2 plugin for i-MSCP

Plugin which redirects apache2 requests of the panel to nginx.

### Introduction

This plugin redirects any apache2 requests for the i-MSCP control panel (made on ports 80/443) on nginx.

### Requirements

* i-MSCP >= 1.2.0

### Installation

* Login into the panel as admin and go to the plugin management interface
* Upload the **PanelRedirect** plugin archive
* Click on the **Update Plugins** button
* Activate the plugin

### Update

* Backup your current config file **plugins/PanelRedirect/config.php**
* Login into the panel as admin and go to the plugin management interface
* Upload the **PanelRedirect** plugin archive
* Restore your **plugins/PanelRedirect/config.php** (compare it with new config file first)
* Click on the **Update Plugins** button in the plugin management interface

### License

The files in this archive are released under the **GNU LESSER GENERAL PUBLIC LICENSE**. You can find a copy of this
license in **[LICENSE.txt](LICENSE.txt)**.

### AUTHOR

 * Ninos Ego <me@ninosego.de>

**Thank you for using this plugin.**
