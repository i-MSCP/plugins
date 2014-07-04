## i-MSCP ImscpBoxBilling plugin v0.0.3

Plugin allowing server provisioning through the BoxBilling software.

### Introduction

This plugin provide a bridge between the i-MSCP control panel and the BoxBilling
billing software. It allow server provisioning through BoxBilling.

### Requirements

  i-MSCP >= 1.1.11 (plugin API >= 0.2.10)

### Installation and update

#### i-MSCP side

1. Login into the panel as admin and go to the plugin management interface
2. Upload the **ImscpBoxBilling** plugin archive
3. Activate the plugin

#### BoxBilling side

1. Copy the Manager/Imscp.php file from the plugin archive into the BoxBilling **bb-library/Server/Manager** directory
2. Setup a new server, an hosting plan (see below) and a product/service using the imscp server manager

**Note:** Only the hosting plan name is relevant. The hosting plan must match with one that has been created in the
i-MSCP control panel, and be owned either by an administrator (admin level hosting plan) or by the reseller
(reseller level hosting plan) as filled out while creating the server.

### License

The files in this archive are released under the **GNU LESSER GENERAL PUBLIC LICENSE**. You can find a copy of this
license in [LICENSE.txt](LICENSE.txt).

### KNOWN ISSUE

* The FTP account as shown in service details (client interface) is inoperant

### Author

 * Laurent Declercq <l.declercq@nuxwin.com>
