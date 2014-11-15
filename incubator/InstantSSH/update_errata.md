# Update errata

## Update to version 3.0.0

### Cascading permissions (admin -> reseller -> customer)

Support for cascading permissions has been added. From now, administrators give SSH permissions to the resellers, and
the resellers give SSH permisssions to their customers according their own permissions.

### Multiple SSH users per customer

This new version add support for multiple SSH users per customer. Those users share the UID/GID of the i-MSCP  unix
users (vuxxx), which in the context of the InstantSSH plugin are merely called 'parent users'.

#### Parent users' usage

The parent users are still added in the jailed /etc/passwd files as first entry, but with the shell field set to
'/bin/false' to prevent any login from them. Doing this allow to always show the same user/group in the **ls** command
results. Indeed, this command always take the first entry matching the UID/GID from the /etc/passwd and /etc/group files.

#### SSH users's prefixes

A prefix is added to all SSH usernames to allow the administrator to filter them easily in the /etc/passwd file. The
default prefix, which is set to **imscp_**, can be modified by editing the **ssh_user_name_prefix** configuration parameter
in the plugin configuration file. This parameter does applies only to the newly created SSH users.

**Warning**: You must never set the **ssh_user_name_prefix** to an empty value. Doing this shall allow the customers to
create unix users with reserved names.

### Password authentication capability

This new version also come with the password authentication capability which was missing in previous versions.
The passwords are encrypted in the database using the better available algorythm as provided by crypt(). For safety
reasons, this feature can be disabled by allowing only the passwordless authentication. This can be achieved by setting
the 'passwordless_authentication' configuration parameter to TRUE into the plugin configuration file.

### Note regarding update from a previous versions

#### i-MSCP default user (vuxxx)

During update, the fields (homedir, shell) of the i-MSCP unix users (vuxxx) are reset back to their default values and
un-jailed if needed.

#### SSH keys entries

The ssh keys entries are automatically converted into SSH user entries where the SSH usernames are defined using the
prefix of SSH usernames and the SSH key unique identifier (eg. <ssh_user_name_prefix>1, <ssh_user_name_prefix>2...)

#### Reseller permissions

The resellers's permissions are automatically created using a predefined set of permissions. Once the plugin update is
done, don't forget to edit the SSH permissions of your resellers if you want restrict them (eg. Force usage of jailed
shell, forbid the edition of authentication options).
