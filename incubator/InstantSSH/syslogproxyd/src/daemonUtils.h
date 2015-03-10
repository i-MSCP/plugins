/*
 * daemonUtils.h
 *
 *  Created on: 21 Jan 2012
 *      Author: martin
 */

#ifndef _PRIVUTILS_H
#define _PRIVUTILS_H

#include <stdbool.h>
#include <sys/types.h>

int drop_privileges(uid_t, gid_t);
int init_daemon(const char *, bool, uid_t, gid_t, const char *);
int enter_chroot( const char *appname, const char *dirname );
int detach_process( bool nochdir );

#endif // _PRIVUTILS_H
