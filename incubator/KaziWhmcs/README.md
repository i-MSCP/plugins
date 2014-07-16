## i-MSCP KaziWhmcs plugin v0.0.6

Plugin allowing i-MSCP server provisioning through the WHMCS billing software.

### Introduction

This plugin provide a bridge between the i-MSCP control panel and the WHMCS billing software. It allow i-MSCP server
provisioning through WHMCS.

**Note:** At this time, only the hosting accounts can be provisioned through this plugin. The provisioning of reseller
accounts will be implemented in near future.

### Requirements

  i-MSCP >= 1.1.11 (plugin API >= 0.2.10)

### Installation and update

#### i-MSCP side

1. Login into the panel as admin and go to the plugin management interface
2. Upload the **KaziWhmcs** plugin archive
3. Activate the plugin
4. Go to the settings page and disable the "prevent external login..." option for both resellers and clients

**Note:** Before updating, don't forget to backup your plugin configuration file and to restore it once the update is done.
Don't forget also to read the **[ERRATA.md](ERRATA.md)** file for additional steps which can be required after update.

#### WHMCS side

1. Copy the imscp directory from the plugin archive into the WHMCS server module directory (module/servers)
2. Setup a new server and products/services using imscp server provisioning module

**Note:** If you are using a non-standard port (80/443) for access to i-MSCP control panel, add it after the IP/Hostname.

For instance:

    Hostname: panel.domain.tld:4433
    IP Address: 192.168.5.5:4433

### Implemented WHMCS module commands (functions)

#### AdminLink

The **AdminLink** function is used to show the i-MSCP login button in the admin area server configuration page.

#### LoginLink

The **LoginLink** function is used to show the control panel link on the product management page.

#### ClientArea

The **ClientArea** function is used to show the i-MSCP login and tools buttons in client area.

#### CreateAccount

The **CreateAccount** function is called when a new product is due to be provisionned. This can be invoked
automatically by WHMCS upon checkout or payment, or manually by administrator.

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

See the TODO file inside the plugin archive.

### License

The files in this archive are released under the **GNU LESSER GENERAL PUBLIC LICENSE**. You can find a copy of this
license in [LICENSE.txt](LICENSE.txt).

### Author

 * Laurent Declercq <l.declercq@nuxwin.com>
