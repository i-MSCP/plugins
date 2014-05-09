## i-MSCP KaziWhmcs plugin v0.0.1

Plugin allowing server provisioning through the WHMCS billing software.

### Introduction

This plugin provide a bridge between the i-MSCP control panel and the WHMCS
billing software. It allow server provisioning through WHMCS.

### Requirements

  i-MSCP >= 1.1.5 (plugin API >= 0.2.8)

### Installation and update

#### i-MSCP side

1. Login into the panel as admin and go to the plugin management interface
2. Upload the **KaziWhmcs** plugin archive
3. Activate the plugin

#### WHMCS side

1. Copy the imscp directory from the plugin archive into the WHMCS server module directory (module/servers)
2. Setup a new server and products/services using imscp server provisioning module

### License

The files in this archive are released under the **GNU LESSER GENERAL PUBLIC LICENSE**. You can find a copy of this
license in [LICENSE.txt](LICENSE.txt).

### Author

 * Laurent Declercq <l.declercq@nuxwin.com>
