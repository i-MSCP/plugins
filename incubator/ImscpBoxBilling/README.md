# i-MSCP ImscpBoxBilling plugin v0.0.3

Plugin which allows server provisioning through the BoxBilling software.

## Introduction

This plugin provide a bridge between the i-MSCP Control Panel and the BoxBilling billing software. It allows server
provisioning through BoxBilling.

## Requirements

* i-MSCP >= 1.1.11 (plugin API >= 0.2.10)

## Installation and update

### i-MSCP side

1. Upload the plugin through the plugin management interface
2. Activate the plugin through the plugin management interface

### BoxBilling side

1. Copy the Manager/Imscp.php file from the plugin archive into the BoxBilling **bb-library/Server/Manager** directory
2. Setup a new server, an hosting plan (see below) and a product/service using the imscp server manager

**Note**

Only the hosting plan name is relevant. The hosting plan must match with one that has been created in the i-MSCP control
panel, and be owned either by an administrator (admin level hosting plan) or by the reseller (reseller level hosting plan)
as filled out while creating the server.

## License

```
i-MSCP CronJobs plugin
Copyright (c) 2014-2015 laurent declercq <l.declercq@nuxwin.com>

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
```

see [LICENSE](LICENSE)

## Know issues

* The FTP account as shown in service details ( client interface ) is inoperant

## Author

* Laurent Declercq <l.declercq@nuxwin.com>
