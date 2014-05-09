## i-MSCP KaziWhmcs plugin v0.0.4

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

### Implemented WHMCS module commands (functions)

#### AdminLink

The **AdminLink** function is used to show an i-MSCP login button in the admin area server configuration page.

#### LoginLink

The **LoginLink** function is used to show a control panel link on the product management page.

#### ClientArea

The **ClientArea** function is used to show an i-MSCP login button in client area.

#### CreateAccount

The **CreateAccount** function is called when a new product is due to be provisionned. This can be invoked
automatically by WHMCS upon checkout or payment, or  manually by administrator.

#### SuspendAccount

The **SuspendAccount** function is called when a suspension is requested. This can be invoked automatically by WHMCS
when a product become overdue on payment, or manually by the administrator.

#### UnsuspendAccount

The **UnsuspendAccount** function is called when an unsuspension is requested. This can be invoked automatically
upon payment of an overdue invoice for a product, or manually by the administrator.

#### ChangePassword

The **ChangePassword** function is called when a client request a password change from the client area, or manually by
the administrator.

#### UsageUpdate

The **UsageUpdate** function is used to perform a daily import of the disk and bandwidth usage for accounts from a
server. The data imported is then used to display the usage stats both within the client and admin areas of WHMCS, and
also in disk and bandwidth overage billing calculations if enabled for a product.


### TODO

* Renew command
* ChangePackage command

### License

The files in this archive are released under the **GNU LESSER GENERAL PUBLIC LICENSE**. You can find a copy of this
license in [LICENSE.txt](LICENSE.txt).

### Author

 * Laurent Declercq <l.declercq@nuxwin.com>
