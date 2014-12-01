# Update errata

## Update to version 3.0.0

### Cascading permissions ( admin -> reseller -> customer )

Support for cascading permissions has been added. From now, administrators give SSH permissions to the resellers, and
the resellers give SSH permisssions to their customers according their own permissions.

### Multiple SSH users per customer

This new version add support for multiple SSH users per customer. Those users share the UID/GID of the i-MSCP unix
users (vuxxx), which in the context of the InstantSSH plugin are merely called **parent users**.

#### Parent users' usage

The parent users are still added in the jailed /etc/passwd files as first entry, but with the shell field set to
**/bin/false** to prevent any login from them. Doing this allow to always show the same user/group in the **ls** command
results. Indeed, this command always take the first entry matching the UID/GID from the /etc/passwd and /etc/group files.

#### SSH users's prefixes

A prefix is added to all SSH usernames to allow the administrator to filter them easily in the /etc/passwd file and also
to prevent customer to create SSH users with reserved names. The default prefix, which is set to **imscp_**, can be
modified by editing the **ssh_user_name_prefix** configuration parameter in the plugin configuration file. This parameter
does applies only to the newly created SSH users.

**Warning**: You must never set the **ssh_user_name_prefix** to an empty value. Doing this would allow the customers to
create unix users with reserved names.

### Password authentication capability

This new version also come with the password authentication capability which was missing in previous versions. The
passwords are encrypted in the database using the better available algorythm as provided by crypt(). For safety reasons,
this feature can be disabled by allowing only the passwordless authentication. This can be achieved by setting the
**passwordless_authentication** configuration parameter to **TRUE** into the plugin configuration file.

### Note regarding the system and database update

#### i-MSCP default user (vuxxx)

During update, the fields (homedir, shell) of the i-MSCP unix users (vuxxx) are reset back to their default values and
un-jailed if needed.

#### SSH keys entries

The ssh keys entries are automatically converted into SSH user entries where the SSH usernames are defined using the
prefix of SSH usernames and the SSH key unique identifier (eg. \<ssh_user_name_prefix\>1, \<ssh_user_name_prefix\>2...)

#### Reseller permissions

The SSH permissions for resellers which have customers with existent SSH permissions are automatically created using a
predefined set of permissions. After the plugin update, you should review those permissions if you want restrict the
resellers (eg. to force usage of jailed shells and/or forbid the edition of authentication options).

## Update to version 2.1.0

### Translation support

This new version add translation support. The plugin can now be translated in your language using a simple PHP file
which return an array of translation strings. In order, to translate this plugin in your language, you must:
 
1. Create a new translation file for your language (eg. de_DE.php) in the **plugins/InstantSSH/l10n** directory by
copying the **en_GB.php** file ( only if the file doesn't exist yet ). The file must be UTF-8 encoded.
2. Translate the strings (The keys are the msgid strings and the values the msgstr strings). You must only translate the
msgstr strings.

During your translation session, you must enable the **DEBUG** mode in the **/etc/imscp/imscp.conf** file to force reload
of the translation files on each request, else, the translation strings are put in cache. Don't forget to disable it once
you have finished to translate.

You're welcome to submit your translation files in our forum if you want see them integrated in the later release.
