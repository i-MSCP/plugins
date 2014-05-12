/*
 * the jailkit chroot() shell
 * this program does a safe chroot() and then executes the shell
 * that the user has within that new root (according to newroot/etc/passwd)
 *
 * I tried to merge some of the ideas from chrsh by Aaron D. Gifford,
 * start-stop-daemon from Marek Michalkiewicz and suexec by the Apache
 * group in this shell
 *

Copyright (c) 2003, 2004, 2005, 2006, 2007, 2008, 2009, 2010, 2011, 2013 Olivier Sessink
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

#include "config.h"

#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <fcntl.h>
#include <string.h>
#include <pwd.h>
#include <grp.h>
#include <sys/stat.h>
#include <sys/types.h>
#include <errno.h>
#include <syslog.h>
#include <limits.h>
#include <signal.h>

#ifdef HAVE_SYS_CAPABILITY_H
#include <sys/capability.h>
#endif

/* #define DEBUG */

#ifdef DEBUG
#define DEBUG_MSG printf
#else
#define DEBUG_MSG(args...)
 /**/
#endif

#define PROGRAMNAME "jk_chrootsh"
#define CONFIGFILE INIPREFIX"/jk_chrootsh.ini"

#include "jk_lib.h"
#include "utils.h"
#include "iniparser.h"
#include "passwdparser.h"

/* doesn't compile on FreeBSD without this */
extern char **environ;

/*
typedef struct {
	char *key;
	char *value;
} Tsavedenv;

static Tsavedenv *savedenv_new(const char *key) {
	Tsavedenv *savedenv;
	char *val = getenv(key);
	if (!val) return NULL;
	savedenv = malloc(sizeof(Tsavedenv));
	savedenv->key = strdup(key);
	savedenv->value = strdup(val);
	return savedenv;
}

static void savedenv_restore(Tsavedenv *savedenv) {
	if (savedenv) {
		setenv(savedenv->key, savedenv->value, 1);
		DEBUG_MSG("restored %s=%s\n",savedenv->key, savedenv->value);
	}
}

static void savedenv_free(Tsavedenv *savedenv) {
	if (savedenv) {
		free(savedenv->key);
		free(savedenv->value);
		free(savedenv);
	}
}
*/

static int in_array(char **haystack, char * needle, int needlelen) {
	if (haystack && needle) {
		char **tmp = haystack;
		while (*tmp) {
			if (strlen(*tmp)==needlelen && strncmp(*tmp, needle, needlelen)==0) return 1;
			tmp++;
		}
	}
	return 0;
}

static void unset_environ_except(char **except) {
	char **tmp = environ;
	if (environ == NULL || *tmp==NULL || except==NULL || *except==NULL) {
		clearenv();
		return;
	}
	while (*tmp) {
		char* pos = strchr(*tmp, '=');
		if (pos == NULL) {
			/* invalid environment variable, are we being hacked? clear them all! */
			clearenv();
			return;
		}
		if (!in_array(except, *tmp, pos-*tmp)) {
			char *key = strndup(*tmp, pos-*tmp);
			unsetenv(key);
			free(key);
			tmp = environ;
			continue;
		}
		tmp++;
	}
}

static int have_capabilities(void) {
#ifdef HAVE_CAP_GET_PROC
	cap_t caps = cap_get_proc();
	if (caps) {
		cap_flag_value_t value_p;
		cap_get_flag(caps, CAP_SYS_CHROOT, CAP_EFFECTIVE,&value_p);
		cap_free(caps);
		return (value_p);
	}
#endif  /*HAVE_CAP_GET_PROC*/
	return 0;
}

void signal_handler(int signum) {
	syslog(LOG_ERR, "abort, received signal %s (%d)", strsignal(signum),signum);
	exit(666);
}

int main (int argc, char **argv) {
	unsigned int i;
	unsigned int use_capabilities=0;
	int ret;
	char **newargv;
	char *user=NULL, *tmp=NULL;

	struct passwd *pw=NULL;
	struct group *gr=NULL;
	unsigned long ngroups_max=NGROUPS_MAX;
	unsigned long ngroups=0;
	gid_t *gids;
	struct passwd *intpw=NULL; /* for internal_getpwuid() */
	char *jaildir=NULL, *newhome=NULL, *shell=NULL;
	Tiniparser *parser=NULL;
	char **envs=NULL;
	unsigned int relax_home_group_permissions=0;
	unsigned int relax_home_other_permissions=0;
	unsigned int relax_home_group=0;
	char *injail_shell=NULL;
	unsigned int skip_injail_passwd_check=0;

	DEBUG_MSG(PROGRAMNAME", started\n");
	/* open the log facility */
	openlog(PROGRAMNAME, LOG_PID, LOG_AUTH);

	/* attach signal handler */
	signal(SIGILL,signal_handler);
	signal(SIGSEGV,signal_handler);
	signal(SIGTERM,signal_handler);
	signal(SIGFPE,signal_handler);

	/* check if it PRORAMNAME that the user wants */
	tmp = strrchr(argv[0], '/');
	if (!tmp) {
		tmp = argv[0];
	} else {
		tmp++;
	}

	if (strcmp(tmp, PROGRAMNAME) != 0 && strcmp(tmp, "su")!= 0 && strcmp(tmp, "-su")!= 0 && strcmp(tmp, "-"PROGRAMNAME)!=0) {
		DEBUG_MSG("wrong name, tmp=%s, &tmp[1]=%s\n", tmp, &tmp[1]);
		syslog(LOG_ERR, "abort, "PROGRAMNAME" is called as %s", argv[0]);
		exit(1);
	}

	/* now test if we are setuid root (the effective user id must be 0, and the real user id > 0 */
	if (geteuid() != 0) {
		if (have_capabilities()) {
			use_capabilities=1;
		} else {
			syslog(LOG_ERR, "abort, effective user ID is not 0, possibly "PROGRAMNAME" is not setuid root");
			exit(11);
		}
	}
	if (getuid() == 0) {
		syslog(LOG_ERR, "abort, "PROGRAMNAME" is run by root, which does not make sense because user root can break out of a jail anyway");
		exit(12);
	}

	DEBUG_MSG("get user info\n");
	/* get user info based on the users name and not on the uid. this enables support
	for systems with multiple users with the same user id */
	tmp = getenv("USER");
	if (tmp && strlen(tmp)) {
		user = strdup(tmp);
	}
	if (user) {
		pw = getpwnam(user);
	} else {
		pw = getpwuid(getuid());
	}
	if (!pw) {
		syslog(LOG_ERR, "abort, failed to get user information for user ID %d: %s, check /etc/passwd", getuid(), strerror(errno));
		exit(13);
	}
	if (!pw->pw_name || strlen(pw->pw_name)==0) {
		syslog(LOG_ERR, "abort, got an empty username for user ID %d: %s, check /etc/passwd", getuid(), strerror(errno));
		exit(13);
	}
	if (user && strcmp(user,pw->pw_name)!=0) {
		syslog(LOG_ERR, "abort, asked for user %s, got user info for %s", user, pw->pw_name);
		exit(13);
	}
	if (pw->pw_uid != getuid()) {
		syslog(LOG_ERR, "abort, started by user ID %d, got user info %s with user ID %d,", getuid(), pw->pw_name, pw->pw_uid);
		exit(13);
	}
	DEBUG_MSG("got user %s\nget group info\n",pw->pw_name);
	gr = getgrgid(getgid());
	if (!gr) {
		syslog(LOG_ERR, "abort, failed to get group information for group ID %d: %s, check /etc/group", getgid(), strerror(errno));
		exit(13);
	}
	DEBUG_MSG("get additional groups\n");
	/* ngroups_max = sysconf(_SC_NGROUPS_MAX);*/
	gids = malloc(ngroups_max * sizeof(gid_t));
	ngroups = getgroups(ngroups_max,gids);
	if (ngroups == -1) {
		syslog(LOG_ERR, "abort, failed to get additional group information: %s, check /etc/group", strerror(errno));
		exit(13);
	}
#ifdef DEBUG
	printf("got additional groups ");
	for (i=0;i<ngroups;i++) {
		printf("%d, ",gids[i]);
	}
	printf("\n");
#endif

	/* make sure the jailkit config directory is owned root:root and not writable for others */
	if ( (testsafepath(INIPREFIX, 0, 0) &~TESTPATH_GROUPW) != 0 ) {
		syslog(LOG_ERR, "abort, jailkit configuration directory "INIPREFIX" is not safe; it should be owned 0:0 and not writable for others");
		exit(14);
	}
	parser = new_iniparser(CONFIGFILE);
	if (parser) {
		char *groupsec, *section=NULL, buffer[1024]; /* openbsd complains if this is <1024 */
		groupsec = strcat(strcpy(malloc0(strlen(gr->gr_name)+7), "group "), gr->gr_name);
		if (iniparser_has_section(parser, pw->pw_name)) {
			section = strdup(pw->pw_name);
		} else if (iniparser_has_section(parser, groupsec)) {
			section = groupsec;
		} else if (iniparser_has_section(parser, "DEFAULT")) {
			section = strdup("DEFAULT");
		}
		if (section != groupsec) free(groupsec);
		if (section) {
			unsigned int pos = iniparser_get_position(parser) - strlen(section) - 2;

			if (iniparser_get_string_at_position(parser, section, "env", pos, buffer, 1024) > 0) {
				envs = explode_string(buffer, ',');
			}
			relax_home_group_permissions = iniparser_get_int_at_position(parser, section, "relax_home_group_permissions", pos);
			relax_home_other_permissions = iniparser_get_int_at_position(parser, section, "relax_home_other_permissions", pos);
			relax_home_group = iniparser_get_int_at_position(parser, section, "relax_home_group", pos);
			if (iniparser_get_string_at_position(parser, section, "injail_shell", pos, buffer, 1024) > 0) {
				injail_shell = strdup(buffer);
			}
			if (injail_shell) {
				skip_injail_passwd_check = iniparser_get_int_at_position(parser, section, "skip_injail_passwd_check", pos);
			}
			DEBUG_MSG("section %s: relax_home_group_permissions=%d, relax_home_other_permissions=%d, relax_home_group=%d, injail_shell=%s, skip_injail_passwd_check=%d\n",
					section, relax_home_group_permissions, relax_home_other_permissions,
					relax_home_group, injail_shell, skip_injail_passwd_check);
			free(section);
		} else {
			DEBUG_MSG("no relevant section found in configfile\n");
		}
		iniparser_close(parser);
	} else {
		DEBUG_MSG("no configfile "CONFIGFILE" ??\n");
	}

	DEBUG_MSG("close filedescriptors\n");
	/* open file descriptors can be used to break out of a chroot, so we close all of them, except for stdin,stdout and stderr */
#ifdef OPEN_MAX
    i = OPEN_MAX;
#elif defined(NOFILE)
    i = NOFILE;
#else
    i = getdtablesize();
#endif
	while (--i > 2) {
		/*printf("closing file descriptor %d\n",i);*/
		while (close(i) != 0 && errno == EINTR);
	}
	/* now make sure file descriptors 0 1 and 2 are valid before we (or a child) starts writing to it */
	while (1) {
		int fd;
		fd = open("/dev/null", O_RDWR);
		if (fd < 0)
			exit(10);
		if (fd > 2) {
			close(fd);
			break;
		} else {
			DEBUG_MSG("re-opening file descriptor %d\n",fd);
		}
	}

	/* now we clear the environment, except for values allowed in /etc/jailkit/jk_chrootsh.ini */
	unset_environ_except(envs);
	if (envs) {
		free_array(envs);
	}

	if (pw->pw_gid != getgid()) {
		syslog(LOG_ERR, "abort, the group ID from /etc/passwd (%d) does not match the group ID we run with (%d)", pw->pw_gid, getgid());
		exit(15);
	}
	if (!pw->pw_dir || strlen(pw->pw_dir) ==0) {
		syslog(LOG_ERR, "abort, got an empty home directory for user %s (%d)", pw->pw_name, getuid());
		exit(16);
	}
	if (strstr(pw->pw_dir, "/./") == NULL) {
		syslog(LOG_ERR, "abort, homedir '%s' for user %s (%d) does not contain the jail separator <jail>/./<home>", pw->pw_dir, pw->pw_name, getuid());
		exit(17);
	}
	DEBUG_MSG("get jaildir\n");
	if (!getjaildir(pw->pw_dir, &jaildir, &newhome)) {
		syslog(LOG_ERR, "abort, failed to read the jail and the home from %s for user %s (%d)",pw->pw_dir, pw->pw_name, getuid());
		exit(17);
	}
	DEBUG_MSG("dir=%s,jaildir=%s,newhome=%s\n",pw->pw_dir, jaildir, newhome);
	DEBUG_MSG("get chdir()\n");
	if (chdir(jaildir) != 0) {
		syslog(LOG_ERR, "abort, chdir(%s) failed: %s, check the permissions for %s",jaildir,strerror(errno),jaildir);
		exit(19);
	} else {
		char test[1024];
		/* test if it really succeeded */
		if (getcwd(test, 1024)==NULL || !dirs_equal(jaildir, test)) {
			syslog(LOG_ERR, "abort, the current dir is %s after chdir(%s), but it should be %s",test,jaildir,jaildir);
			exit(21);
		}
	}

	/* here do test the ownership of the jail and the homedir and such
	the function testsafepath doe exit itself on any failure */
	if (!basicjailissafe(jaildir)) {
		syslog(LOG_ERR, "abort, %s is not a safe jail, check ownership and permissions.", jaildir);
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
	if (!relax_home_group && (ret & TESTPATH_GROUP)) {
		syslog(LOG_ERR, "abort, path %s does not have group owner %d, set option 'relax_home_group' to relax this check", pw->pw_dir,getgid());
		exit(53);
	}
	if (!relax_home_group_permissions && (ret & TESTPATH_GROUPW)) {
		syslog(LOG_ERR, "abort, path %s is group writable, set option 'relax_home_group_permissions' to relax this check", pw->pw_dir);
		exit(53);
	}
	if (!relax_home_other_permissions && (ret & TESTPATH_OTHERW)) {
		syslog(LOG_ERR, "abort, path %s is writable for other, set option 'relax_home_other_permissions' to relax this check", pw->pw_dir);
		exit(53);
	}
	/* do a final log message */
	tmp = implode_array(&argv[1], argc-1, " ");
	syslog(LOG_INFO, "now entering jail %s for user %s (%d) with arguments %s", jaildir, pw->pw_name, getuid(), tmp);
	free(tmp);

	DEBUG_MSG("chroot()\n");
	/* do the chroot() call */
	if (chroot(jaildir)) {
		syslog(LOG_ERR, "abort, chroot(%s) failed: %s, check the permissions for %s", jaildir, strerror(errno), jaildir);
		exit(33);
	}

	if (use_capabilities) {
#ifdef HAVE_CAP_GET_PROC
		cap_t caps;
		cap_value_t capv[1];
		/* drop chroot capability, should we drop all other capabilities that may be used to escape from the jail too ?  */
		if ((caps = cap_get_proc()) == NULL) {
			syslog(LOG_ERR, "abort, failed to retrieve current capabilities: %s", strerror(errno));
			exit(101);
		}
		capv[0] = CAP_SYS_CHROOT;
		/* other capabilities that should/could be dropped:
		CAP_SETPCAP, CAP_SYS_MODULE, CAP_SYS_RAWIO, CAP_SYS_PTRACE, CAP_SYS_ADMIN */
		if (cap_set_flag(caps, CAP_PERMITTED, 1, capv, CAP_CLEAR)) {
			syslog(LOG_ERR, "abort, failed to set PERMITTED capabilities: %s", strerror(errno));
			exit(102);
		}
		if (cap_set_flag(caps, CAP_EFFECTIVE, 1, capv, CAP_CLEAR)) {
			syslog(LOG_ERR, "abort, failed to set effective capabilities: %s", strerror(errno));
			exit(103);
		}
		if (cap_set_flag(caps, CAP_INHERITABLE, 1, capv, CAP_CLEAR)) {
			syslog(LOG_ERR, "abort, failed to set INHERITABLE capabilities: %s", strerror(errno));
			exit(104);
		}
		if (cap_set_proc(caps)) {
			syslog(LOG_ERR, "abort, failed to apply new capabilities: %s", strerror(errno));
			exit(105);
		}
#else
		/* we should never get here */
		exit(333);
#endif
	} else {
		/* drop all privileges, it seems that we first have to setgid(),
			then we have to call initgroups(),
			then we call setuid() */
		if (setgid(getgid())) {
			syslog(LOG_ERR, "abort, failed to set effective group ID %d: %s", getgid(), strerror(errno));
			exit(34);
		}
		if (setgroups(ngroups, gids)==-1) {
			syslog(LOG_ERR, "abort, failed to set additional groups: %s", strerror(errno));
			exit(35);
		}
		free(gids);
	/*	if (initgroups(pw->pw_name, getgid())) {
			syslog(LOG_ERR, "abort, failed to init groups for user %s (%d), check %s/etc/group", pw->pw_name,getuid(),jaildir);
			exit(35);
		}*/
		if (setuid(getuid())) {
			syslog(LOG_ERR, "abort, failed to set effective user ID %d: %s", getuid(), strerror(errno));
			exit(36);
		}
	}
	/* test for user and group info, is it the same? checks username, groupname and home */
	if (!skip_injail_passwd_check){
		char *oldpw_name,*oldgr_name;
		oldpw_name = strdup(pw->pw_name);
		oldgr_name = strdup(gr->gr_name);

		if (user) {
			pw = getpwnam(user);
		} else {
			pw = getpwuid(getuid());
		}
		if (!pw) {
			syslog(LOG_ERR, "abort, failed to get user information in the jail for user ID %d: %s, check %s/etc/passwd",getuid(),strerror(errno),jaildir);
			exit(35);
		}
		if (pw->pw_uid != getuid()) {
			syslog(LOG_ERR, "abort, got user information in the jail for user ID %d instead of user ID %d, check %s/etc/passwd",pw->pw_uid,getuid(),jaildir);
			exit(35);
		}
		DEBUG_MSG("got %s as pw_dir\n",pw->pw_dir);
		gr = getgrgid(getgid());
		if (!gr) {
			syslog(LOG_ERR, "abort, failed to get group information in the jail for group ID %d: %s, check %s/etc/group",getgid(),strerror(errno),jaildir);
			exit(35);
		}
		if (strcmp(pw->pw_name, oldpw_name)!=0) {
			syslog(LOG_ERR, "abort, username %s differs from jail username %s for user ID %d, check /etc/passwd and %s/etc/passwd", oldpw_name, pw->pw_name, getuid(), jaildir);
			exit(37);
		}
		if (strcmp(gr->gr_name, oldgr_name)!=0) {
			syslog(LOG_ERR, "abort, groupname %s differs from jail groupname %s for group ID %d, check /etc/passwd and %s/etc/passwd", oldgr_name, gr->gr_name, getgid(), jaildir);
			exit(37);
		}
		if (strcmp(pw->pw_dir, newhome)!=0) {
			DEBUG_MSG("%s!=%s\n",pw->pw_dir, newhome);
			/* if these are different, it could be that getpwuid() gets the real user
			info (from for example ldap or nscd), and not the info inside the jail, lets
			test that, and if true, we should use the	shell from the internal function as well*/
			intpw = internal_getpwuid("/etc/passwd", getuid());
			if (!intpw) {
				DEBUG_MSG("%s!=%s\n",intpw->pw_dir, newhome);
				syslog(LOG_ERR, "abort, failed to find user %d in %s/etc/passwd", getuid(), jaildir);
				exit(39);
			}
			if (!dirs_equal(intpw->pw_dir, newhome)) {
				DEBUG_MSG("%s!=%s\n",intpw->pw_dir, newhome);
				syslog(LOG_ERR, "abort, home directory %s differs from jail home directory %s for user %s (%d), check /etc/passwd and %s/etc/passwd", newhome, pw->pw_dir, pw->pw_name, getuid(), jaildir);
				exit(39);
			}
		}
		free(oldpw_name);
		free(oldgr_name);
	}
	if (injail_shell) {
		shell = injail_shell;
	} else if (intpw) {
		shell = intpw->pw_shell;
	} else {
		shell = pw->pw_shell;
	}
	/* test the shell in the jail, it is not allowed to be setuid() root */
	testsafepath(shell,0,0);

	/* prepare the new environment */
	setenv("HOME",newhome,1);
	setenv("USER",pw->pw_name,1);
	setenv("USERNAME",pw->pw_name,1);
	setenv("SHELL",shell,1);
	if (chdir(newhome) != 0) {
		syslog(LOG_ERR, "abort, chdir(%s) failed inside the jail %s: %s, check the permissions for %s/%s",newhome,jaildir,strerror(errno),jaildir,newhome);
		exit(41);
	}

	/* cleanup before execution */
	free(newhome);

	/* now execute the jailed shell */
	/*execl(pw->pw_shell, pw->pw_shell, NULL);*/
	newargv = malloc0((argc+1)*sizeof(char *));
	newargv[0] = shell;
	for (i=1;i<argc;i++) {
		newargv[i] = argv[i];
	}
	execv(shell, newargv);
	DEBUG_MSG(strerror(errno));
	syslog(LOG_ERR, "ERROR: failed to execute shell %s for user %s (%d), check the permissions and libraries of %s/%s",shell,pw->pw_name,getuid(),jaildir,shell);

	free(jaildir);
	exit(111);
}
