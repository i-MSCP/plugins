# Update errata

## Update to version 1.1.0

### Dovecot 2.x

If you're using Dovecot on you server ( 2.x branch ), you could see some warning messages such as:

```
Jan 14 00:42:49 wheezy dovecot: master: Warning: /var/chroot/CronJobs/jail/var/www/virtual/domain.tld is no longer mounted. See http://wiki2.dovecot.org/Mountpoints
```

Even if this is not a big issue, a routine has been added in this version to force Dovecot to ignore any mountpoints
mounted under the root directory of the jailed cron environment. However Dovecot will continue to warn you for the old
mountpoints which were detected previously. You can easily fix that issue by running the following command on your
system:

```
# doveadm mount remove /var/chroot/CronJobs/jail/var/www/virtual/*
```

**Note:** If you changed the paths in the plugin configuration file, you must adapt the command above.
