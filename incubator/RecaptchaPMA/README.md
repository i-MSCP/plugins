# i-MSCP RecaptchaPMA plugin v1.1.0

This plugin add reCAPTCHA support to phpMyAdmin.

## Requirements

* i-MSCP version >= 1.2.3
* Account on https://www.google.com/recaptcha/

## Installation

1. Be sure that all requirements as stated in the requirements section are meets
2. Upload the plugin through the plugin management interface
3. Activate the plugin through the plugin management interface

## Update

1. Be sure that all requirements as stated in the requirements section are meets
2. Backup your plugin configuration file if needed
3. Upload the plugin through the plugin management interface
4. Restore your plugin configuration file if needed ( compare it with the new version first )
5. Update the plugin list through the plugin management interface

## Configuration

To make this plugin working, you must provide both, your public reCaptcha login Key and your private reCaptcha login
Key in the plugin configuration file. 

See [Configuration file](../RecaptchaPMA/config.php)

**Note:** When changing a configuration parameter in the plugin configuration file, do not forget to trigger plugin
change by updating the plugin list through the plugin management interface.

## License

```
Copyright (C) 2014-2016 Sascha Bay <info@space2place.de>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; version 2 of the License

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
```

See [LICENSE](LICENSE)

## Author

* Sascha Bay <info@space2place.de>
