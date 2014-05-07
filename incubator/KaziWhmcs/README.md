KaziWhmcs plugin
================

= Howto install

== i-MSCP side

  - Upload the plugin archive through the plugin interface
  - Update the plugins list through the plugins interface
  - Activate the plugin

== WHMCS side

  - Copy the imscp directory from the plugin archive into the whmcs server module directory (module/servers)
  - Login into your WHMCS interface as administrator and go to the admin/configservers.php page
  - Create a new server by adding the relevant information:

    Name: Arbitrary name which allows you to find your server easily in the server list
    Hostname: This must be the hostname and port such as panel.domain.tld:80 of your i-MSCP control panel
    IP address: This must be the i-MSCP base server IP as set in your i-MSCP configuration file
    Username: The name of the i-MSCP Reseller which must be the used for that server
    Password: Password of your i-MSCP reseller
    Secure: Tick if SSL must be used for connection from WHMCS to the i-MSCP control panel

Note: Only mandatory parameters are described above.

You can create as many servers as you want (using different i-MSCP reseller).

