## i-MSCP JailKit plugin v0.0.1

Plugin providing jailed shell access using jailkit.

### REQUIREMENTS

    # i-MSCP version: >= 1.1.5
    # Debian packages: build-essential, python, diffutils

### INSTALLATION

  - Login into the panel as admin and go to the plugin management interface
  - Upload the **JailKit** plugin archive
  - Install the plugin

### UPDATE

  - Backup your current config.php file
  - Login into the panel as admin and go to the plugin management interface
  -Upload the **JailKit** plugin archive
  - Restore your config.php file (check for any change)
  - Update the plugin list

**Note:** The update will be triggered only if the plugin is enabled.

### CONFIGURATION CHANGE

  - Made your changes in the plugin config.php file or [local configuration file](https://github.com/i-MSCP/imscp/tree/master/gui/data/persistent/plugins)
  - Update the plugin list

 **Note:** The configuration change will be done only if the plugin is enabled.

## JAILKIT EXPERTS

If you are a jailkit expert, you can also adjust the jailkit configuration by editing the jailkit ini files. However,
you should know that any change made in these files will be lost after updating the plugin to a new version. Therefore,
you must think to backup your files.

### DEVELOPERS GUIDLINE

 See the src/README file

### AUTHORS

 * Laurent Declercq <l.declercq@nuxwin.com>
 * Sascha Bay <info@space2place.de>

**Thank you for using this plugin.**
