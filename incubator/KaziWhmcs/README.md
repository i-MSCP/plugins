## i-MSCP KaziWhmcs plugin v0.0.1

Plugin allowing server provisioning through the WHMCS billing software.

### Introduction

This plugin provide a bridge between the i-MSCP control panel and the WHMCS
billing software. It allow server provisioning through WHMCS.

### Requirements

  i-MSCP >= 1.1.5 (plugin API >= 0.2.8)

### Installation and Update

#### i-MSCP side

1. Login into the panel as admin and go to the plugin management interface
2. Upload the **KaziWhmcs** plugin archive
3. Activate the plugin

#### WHMCS side

1. Copy the imscp directory from the plugin archive into the whmcs server module directory (module/servers)
2. Login into your WHMCS interface as administrator and go to the admin/configservers.php page
3. Create a new server by adding the relevant information:

<table>
    <tr>
        <th>Parameter</th>
        <th>Value</th>
        <th>Description</th>
    </tr>
    <tr>
        <td>Name</td>
        <td>string</td>
        <td>Arbitrary name which allows you to find your server easily in the server list</td>
    </tr>
    <tr>
        <td>Hostname</td>
        <td>string</td>
        <td>This must be the hostname of your i-MSCP control panel</td>
    </tr>
    <tr>
        <td>IP address</td>
        <td>string</td>
        <td>This must be the i-MSCP base server IP as set in your i-MSCP configuration file</td>
    </tr>
    <tr>
        <td>Username</td>
        <td>string</td>
        <td>The name of the i-MSCP Reseller which must be used for that server</td>
    </tr>
    <tr>
        <td>Password</td>
        <td>string</td>
        <td>Password of your i-MSCP reseller</td>
    </tr>
    <tr>
        <td>Secure</td>
        <td>checkbox</td>
        <td>Tick if SSL must be used for connections from WHMCS to the i-MSCP control panel</td>
    </tr>
</table>

**Note:** Only mandatory parameters are described above.

You can create as many servers as you want (using different i-MSCP resellers).

### License

The files in this archive are released under the **GNU LESSER GENERAL PUBLIC LICENSE**. You can find a copy of this
license in [LICENSE.txt](incubator/KaziWhmcs/LICENSE.txt).

### Author

 * Laurent Declercq <l.declercq@nuxwin.com>
