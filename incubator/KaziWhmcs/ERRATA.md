## KaziWhmcs plugin version 0.0.6 - Errata

### Updates

In this new version, a hook function has been implemented to fix a problem about wrong usernames generated in pending
orders . This hook function which is defined in the imscp/hooks.php file need a specific action from your side to be
fully operational. Indeed, as stated in the WHMCS documentation:

    Hook files in module folders are only called for active modules. And active modules that contain hooks are cached.
    So if you add a hooks file to a module retrospectively, once the module is already active, then before the system
    will detect and start running that module, you must edit and resave either the addon module configuration, product
    configuration or domain registrar configuration for the respective module for it to be detected.

This essentially mean that after uploading the new imscp directory from the plugin archive into the module/servers
directory of you WHMCS installation, you must, edit and resave each product/addon which uses the imscp module. If you
skip that step, the hook function defined in the imscp/hooks.php file will be silently ignored and thus, the module will
not work properly.

Refs: http://docs.whmcs.com/Hooks
