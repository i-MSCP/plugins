## TemplateEditor v0.0.1 plugin for i-MSCP

WARNING: THIS PLUGIN IS STILL UNDER DEVELOPMENT AND IS NOT READY TO USE

Plugin allowing to create persistent, and customized versions of i-MSCP templates.

### Introduction

This plugin provide an efficient way for edition and persistence of i-MSCP templates. It allow to create new versions of
template set (merely called template groups). Two types of template groups are managed: the **system template groups**
and the **site template groups**, where each group defines a set of templates which belong to a specific service managed
by i-MSCP.

#### System template groups

The system template groups define the templates which operate at system-wide level. They are loaded on demand in the
installation context only. Thus, to make a new version effective, the admin must manually run the imscp-setup after each
edition. Those template groups are not assignable and only one version can be enabled at time.

**Note:** In near future, it will be possible to synchronize the system configuration through the frontEnd directly.
See the **[TODO](TODO)** file.

#### Site template groups

The site template groups define the templates which operate at customer level. Those templates are loaded ondemand, when the
customers's configuration files are built. Any custom version of those template groups can be assigned to one or many
customers at once.

### Warning

This plugin allow to edit sensible configuration data which are required by i-MSCP. This plugin should be reserved to
i-MSCP experts only, even if it always possible to switch back to the default configuration easily.

### Requirements

 - i-MSCP versions >= 1.1.14 (plugin API >= 0.2.11)

### Installation

 - Download the TemplateEditor plugin archive through the plugin store
 - Login into the panel as admin and go to the plugin management interface
 - Upload the TemplateEditor plugin archive
 - Install the plugin

### Update

 - Download the TemplateEditor plugin archive through the plugin store
 - Login into the panel as admin and go to the plugin management interface
 - Upload the TemplateEditor plugin archive

### License

The files in this archive are released under the **GNU LESSER GENERAL PUBLIC LICENSE**. You can find a copy of this
license in **[LICENSE.txt](LICENSE.txt)**.

### Author

Laurent Declercq <l.declercq@nuxwin.com>

**Thank you for using this plugin.**
