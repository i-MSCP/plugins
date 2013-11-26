/*
 *
 * jk_procmailwrapper
 * this program will simply execute procmail for users that are not in a jail
 * and it will exit() for users that are in a jail (mail will *not* be delivered)
 *
 * this will probably extended in the near future
 *
Copyright (c) 2003, 2004, 2005, 2006 Olivier Sessink
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions 
are met:
  * Redistributions of source code must retain the above copyright 
    notice, this list of conditions and the following disclaimer.
  * Redistributions in binary form must reproduce the above 
    copyright notice, this list of conditions and the following 
    disclaimer in the documentation and/or other materials provided 
    with the distribution.
  * The names of its contributors may not be used to endorse or 
    promote products derived from this software without specific 
    prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
POSSIBILITY OF SUCH DAMAGE.
*/
/* #define DEBUG */

#include "config.h"

#include <string.h>
#include <stdio.h>
#include <pwd.h>
#include <sys/types.h>
#include <unistd.h>
#include <stdlib.h>
#include <syslog.h>
#include <errno.h>
#include <grp.h>

#include "jk_lib.h"

#define PROGRAMNAME "jk_procmailwrapper"

int user_is_chrooted(const char *homedir) {
	char *tmp;
	tmp = strchr(homedir, '.');
	if (tmp != NULL) {
		return 1;
	}
	return 0;
}

void clean_exit(char * name, int error) {
	printf("%s, exiting with error %d\n", name, error);
	exit(error);
}

int main (int argc, char **argv, char **envp) {
	int i;
	struct passwd *pw=NULL;
	struct group *gr=NULL;
	char *jaildir=NULL, *newhome=NULL;

	DEBUG_MSG(PROGRAMNAME", started\n");
	pw = getpwuid(getuid());
	if (!user_is_chrooted(pw->pw_dir)) {
		/* if the user does not have a chroot homedir, we start the normal procmail now,
		but first we drop all privileges */
		if (setgid(getgid())) {
			syslog(LOG_ERR, "abort, failed to become gid %d", getgid());
			exit(34);
		}
		if (initgroups(pw->pw_name, getgid())) {
			syslog(LOG_ERR, "abort, failed to initgroups for user %s and group %d", pw->pw_name, getgid());
			exit(35);
		}
		if (setuid(getuid())) {
			syslog(LOG_ERR, "abort, failed to become uid %d", getuid());
			exit(36);
		}
		execve(PROCMAILPATH, argv, envp);
		/* if we get here, there is something wrong */
		exit(1);
	}
	/* OK, so the user is a jailed user, now we start checking things!! */
	
	/* open the log facility */
	openlog(PROGRAMNAME, LOG_PID, LOG_AUTH);
	
	/* check if it us that the user wants */
	{
		char *tmp = strrchr(argv[0], '/');
		if (!tmp) {
			tmp = argv[0];
		} else {
			tmp++;
		}
		if (strcmp(tmp, PROGRAMNAME) && (tmp[0] != '-' || strcmp(&tmp[1], PROGRAMNAME))) {
			DEBUG_MSG("wrong name, tmp=%s, &tmp[1]=%s\n", tmp, &tmp[1]);
			syslog(LOG_ERR, "abort, "PROGRAMNAME" is called as %s", argv[0]);
			exit(1);
		}
	}
	DEBUG_MSG("close filedescriptors\n");
	/* open file descriptors can be used to break out of a chroot, so we close all of them, except for stdin,stdout and stderr */
	for (i=getdtablesize();i>3;i--) {
		while (close(i) != 0 && errno == EINTR);
	}

	/* now test if we are setuid root (the effective user id must be 0, and the real user id > 0 */
	if (geteuid() != 0 || getuid() == 0) {
		syslog(LOG_ERR, "abort, "PROGRAMNAME" is not setuid root, or is run by root");
		exit(11);
	}

	DEBUG_MSG("get user info\n");
	gr = getgrgid(getgid());
	if (!pw || !gr) {
		syslog(LOG_ERR, "abort, failed to get user or group information for %d:%d", getuid(), getgid());
		exit(13);
	}

	/* now we clear the environment */
	clearenv();

	if (pw->pw_gid != getgid()) {
		syslog(LOG_ERR, "abort, the group ID from /etc/passwd (%d) does not match the group ID we run with (%d)", pw->pw_gid, getgid());
		exit(15);
	}
	if (!pw->pw_dir || strlen(pw->pw_dir) ==0 || strchr(pw->pw_dir, '.') == NULL) {
		syslog(LOG_ERR, "abort, the homedir does not contain the jail pattern path/./path");
		exit(17);
	}
	DEBUG_MSG("get jaildir\n");
	if (!getjaildir(pw->pw_dir, &jaildir, &newhome)) {
		syslog(LOG_ERR, "abort, failed to read the jail and the home from %s",pw->pw_dir);
		exit(17);
	}
	DEBUG_MSG("get chdir()\n");
	if (chdir(jaildir) != 0) {
		syslog(LOG_ERR, "abort, failed to chdir() to %s",jaildir);
		exit(19);
	} else {
		/* test if it really succeeded */
		char *test = get_current_dir_name();
		if (strcmp(jaildir, test) != 0) {
			syslog(LOG_ERR, "abort, current dir != %s after chdir()",jaildir);
			exit(21);
		}
		free(test);
	}		
	
	/* here do test the ownership of the jail and the homedir and such
	the function testsafepath doe exit itself on any failure */
	{ 
		int ret;
		DEBUG_MSG("test paths\n");
		ret = testsafepath(jaildir,0,0);
		if (ret != 0) {
			syslog(LOG_ERR, "abort, path %s is not a safe jail, check ownership and permissions", jaildir);
			exit(53);	
		}
		ret = testsafepath(pw->pw_dir, getuid(), getgid());
		if ((ret & TESTPATH_NOREGPATH) ) {
			syslog(LOG_ERR, "abort, path %s is not a directory", pw->pw_dir);
			exit(53);	
		}
		if ((ret & TESTPATH_OWNER) ) {
			syslog(LOG_ERR, "abort, path %s is not owned by %d", pw->pw_dir,getuid());
			exit(53);
		}
	}
	/* do a final log message */
	syslog(LOG_INFO, "now entering jail %s for user %d", jaildir, getuid());
	
	DEBUG_MSG("chroot()\n");
	/* do the chroot() call */
	if (chroot(jaildir)) {
		syslog(LOG_ERR, "abort, failed to chroot() to %s", jaildir);
		exit(33);
	}
	
	/* drop all privileges, it seems that we first have to setgid(), 
		then we have to call initgroups(), 
		then we call setuid() */
	if (setgid(getgid())) {
		syslog(LOG_ERR, "abort, failed to become gid %d", getgid());
		exit(34);
	}
	if (initgroups(pw->pw_name, getgid())) {
		syslog(LOG_ERR, "abort, failed to initgroups for user %s and group %d", pw->pw_name, getgid());
		exit(35);
	}
	if (setuid(getuid())) {
		syslog(LOG_ERR, "abort, failed to become uid %d", getuid());
		exit(36);
	}
	
	/* test for user and group info, is it the same? checks username, groupname and home */
	{
		char *oldpw_name,*oldgr_name;
		oldpw_name = strdup(pw->pw_name);
		oldgr_name = strdup(gr->gr_name);
		
		pw = getpwuid(getuid());
		gr = getgrgid(getgid());
		if (!pw || !gr) {
			syslog(LOG_ERR, "abort, failed to get user and group information in the jail for %d:%d", getuid(), getgid());
			exit(35);
		}
		if (strcmp(pw->pw_name, oldpw_name)!=0 || strcmp(gr->gr_name, oldgr_name)!=0) {
			syslog(LOG_ERR, "abort, user or group names differ inside the jail for %d:%d", getuid(), getgid());
			exit(37);
		}
		if (strcmp(pw->pw_dir, newhome)!=0) {
			syslog(LOG_ERR, "abort, home directory is incorrect inside the jail for %d:%d", getuid(), getgid());
			exit(39);
		}
		free(oldpw_name);
		free(oldgr_name);
	}
	
	/* test procmail in the jail, it is not allowed to be setuid() or setgid()
	it is common to have procmail setuid() root and setgid() mail in the regular 
	system, but it is for most situations not required, and therefore very much 
	not recommended inside a jail. So we will simply exit because it is a 
	security risk */
	testsafepath(PROCMAILPATH,0,0);
	
	/* prepare the new environment */
	setenv("HOME",newhome,1);
	setenv("USER",pw->pw_name,1);
	if (chdir(newhome) != 0) {
		syslog(LOG_ERR, "abort, failed to chdir() inside the jail to %s",newhome);
		exit(41);
	}

	/* cleanup before execution */
	free(newhome);
	free(jaildir);

	/* now execute the jailed shell */
	/*execl(pw->pw_shell, pw->pw_shell, NULL);*/
	{
		char **newargv;
		int i;
		newargv = malloc0((argc+1)*sizeof(char *));
		newargv[0] = PROCMAILPATH;
		for (i=1;i<argc;i++) {
			newargv[i] = argv[i];
		}
		execv(PROCMAILPATH, newargv);
	}
	DEBUG_MSG(strerror(errno));
	syslog(LOG_ERR, "WARNING: could not execute %s for user %d:%d",PROCMAILPATH,getuid(),getgid());
	
	exit(111);
}
