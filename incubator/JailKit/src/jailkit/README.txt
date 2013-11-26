ABOUT

Jailkit is a set of utilities to limit user accounts to specific files 
using chroot() and or specific commands. Setting up a jail is a lot 
easier using these utilities.

See 'man jailkit' for more detailed info on the different utilities 
in this package. If you have not yet installed jailkit you can see 
this man page using 'man man/jailkit.8'.

REQUIREMENTS

The most elementary tools are written in C and only need libc and 
libpthreads (available on almost any Linux system and most other posix
compliant systems).

The scripts are written in python, so they need python installed.

COPYRIGHT

Jailkit is an open source project written by Olivier Sessink. It is 
released under a modified BSD licence.

EXAMPLE

Suppose you need to create an account 'test' that can do sftp and scp
only. You want it in a jail called /home/sftproot where it will have 
a homedirectory /home/test

#initialise the jail
mkdir /home/sftproot
jk_init -j /home/sftproot jk_lsh
jk_init -j /home/sftproot sftp
jk_init -j /home/sftproot scp
# create the account
adduser test
jk_jailuser -j /home/sftproot test
# edit the jk_lsh configfile in the jail (man jk_lsh)
# you can use every editor you want, I chose 'joe'
joe /home/sftproot/etc/jailkit/jk_lsh.ini
# now restart jk_socketd
killall jk_socketd
jk_socketd
# test the account
sftp test@localhost
# check the logs if everything is correct
tail /var/log/daemon.log /var/log/auth.log

NOTES ON VARIOUS APPLICATIONS

cvs - cvs needs /tmp/ for temporary files
procmail - needs /dev/null

COPYRIGHT

Copyright (C) 2003, 2004, 2005, Olivier Sessink

Copying and distribution of this file, with or without modification,
are permitted in any medium without royalty provided the copyright
notice and this notice are preserved.
