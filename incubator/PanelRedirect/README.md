# i-MSCP PanelRedirect plugin v1.1.5

Provides access to i-MSCP panel (and tools) through standard http(s) ports.

## Requirements

* i-MSCP Serie 1.3.x

## Installation

1. Upload the plugin through the plugin management interface
2. Install the plugin through the plugin management interface

## Update

1. Backup your plugin configuration file if needed
2. Upload the plugin through the plugin management interface
3. Restore your plugin configuration file if needed (compare it with the new version first)
4. Update the plugin list through the plugin management interface

## Operational modes

The plugin provides two operational modes which are:

### Redirect mode

In this case, a request made on `http://panel.hostname.tld/webmail` would be redirected to 
`http//panel.hostname.tld:8880/webmail`, excepted if the `BASE_SERVER_VHOST_PREFIX` value is set to `https://`. In such
case, the request would be redirected to `https//panel.hostname.tld:8443/webmail`.

###Proxy mode (default mode)

In this mode, all is transparent. A request made on `http://panel.hostname.tld/webmail` would result to the view of the
webmail without any URL change, excepted if the `BASE_SERVER_VHOST_PREFIX` value is set to `https://`. In such case, the
request would be redirected to `https://panel.hostname.tld/webmail`.

## License

```
Copyright (C) 2014-2016 by Ninos Ego <me@ninosego.de>

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

See [LICENSE](LICENSE)

## Author

* Ninos Ego <me@ninosego.de>
