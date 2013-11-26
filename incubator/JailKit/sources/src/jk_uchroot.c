/*
 * the jailkit chroot() shell
 * this program does a safe chroot() and then executes the shell
 * that the user has within that new root (according to newroot/etc/passwd)
 *
 * I tried to merge some of the ideas from chrsh by Aaron D. Gifford,
 * start-stop-daemon from Marek Michalkiewicz and suexec by the Apache
 * group in this shell
 *

Copyright (c) 2003, 2004, 2005, 2006, 2007, 2008, 2009 Olivier Sessink
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
#include <string.h>
#include <pwd.h>
#include <grp.h>
#include <sys/stat.h>
#include <sys/types.h>
#include <errno.h>
#include <syslog.h>
#include <limits.h>
#include <fcntl.h>
#ifdef HAVE_GETOPT_H
#include <getopt.h>
#endif
#ifdef HAVE_LIBERTY_H
#include <liberty.h>
#endif
#ifdef HAVE_SYS_CAPABILITY_H
#include <sys/capability.h>
#endif
/*#define DEBUG*/

#ifdef DEBUG
#define DEBUG_MSG printf
#else
#define DEBUG_MSG(args...)
 /**/
#endif

#define PROGRAMNAME "jk_uchroot"
#define CONFIGFILE INIPREFIX"/jk_uchroot.ini"

#include "jk_lib.h"
#include "utils.h"
#include "iniparser.h"
#include "passwdparser.h"

/* doesn't compile on FreeBSD without this */
extern char **environ;

static void print_usage() {
	printf(PACKAGE" "VERSION"\nUsage: "PROGRAMNAME" -j jaildir -x executable -- [executable options]\n");
	printf("\t-j|--jail jaildir\n");
	printf("\t-x|--exec executable\n");
	printf("\t-h|--help\n");
	printf(PROGRAMNAME" logs all errors to syslog, for diagnostics check your logfiles\n");
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

/* check basics */
/* parse arguments */
/* parse configfile */
/* check user info */
/* check jail */
/* do chroot call */
int main(int argc, char **argv) {
	char *jail = NULL;
	char *user = NULL;
	char *executable = NULL;
	struct passwd *pw=NULL;
	struct group *gr=NULL;
	Tiniparser *parser=NULL;
	char **newargv=NULL;
	char **allowed_jails = NULL;
	unsigned int skip_injail_passwd_check=0;
	unsigned int i;
	char *tmp;
	unsigned int use_capabilities=0;

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

	/* now test if we are setuid root (the effective user id must be 0, and the real user id > 0 */
#ifndef DEVELOPMENT
	if (geteuid() != 0) {
		if (have_capabilities()) {
			use_capabilities=1;
		} else {
			syslog(LOG_ERR, "abort, effective user ID is not 0, possibly "PROGRAMNAME" is not setuid root");
			exit(11);
		}
	}
#endif
	if (getuid() == 0) {
		syslog(LOG_ERR, "abort, "PROGRAMNAME" is run by root, which does not make sense because user root can use the chroot utility");
		exit(12);
	}



	DEBUG_MSG("get user info\n");
	/* get user info based on the users name and not on the uid. this enables support
	for systems with multiple users with the same user id*/
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
	gr = getgrgid(getgid());
	if (!gr) {
		syslog(LOG_ERR, "abort, failed to get group information for group ID %d: %s, check /etc/group", getgid(), strerror(errno));
		exit(13);
	}


	{
		int c=0;
		int len;
		char *execplusjailpath;
		while (c != -1) {
			int option_index = 0;
			static struct option long_options[] = {
				{"jail", required_argument, NULL, 'j'},
				{"executable", required_argument, NULL, 'x'},
				{"help", no_argument, NULL, 'h'},
				{"version", no_argument, NULL, 'V'},
				{NULL, 0, NULL, 0}
			};
		 	c = getopt_long(argc, argv, "j:x:hv",long_options, &option_index);
			switch (c) {
			case 'j':
				jail = ending_slash(optarg);
				DEBUG_MSG("argument jail='%s', ending_slash returned '%s'\n",optarg,jail);
				break;
			case 'x':
				executable = strdup(optarg);
				break;
			case 'h':
			case 'V':
				print_usage();
				exit(1);
			}
		}
		/* construct the new argv from all leftover options */
		newargv = malloc0((2 + argc - optind)*sizeof(char *));
		newargv[0] = executable;
		c = 1;
		while (optind < argc) {
			newargv[c] = strdup(argv[optind]);
			c++;
			optind++;
		}

		if (jail == NULL) {
			printf("ERROR: No jail path specified. Use -j or --jail\n");
			print_usage();
			exit(1);
		}
		if (executable == NULL) {
			printf("ERROR: No executable path specified. Use -x or --executable\n");
			print_usage();
			exit(1);
		}
		len = strlen(jail)+strlen(executable)+2;
		execplusjailpath = malloc(len+1);
		snprintf(execplusjailpath, len, "%s/%s",jail, executable);
		if (!file_exists(execplusjailpath)) {
			printf("ERROR: Executable path %s does not exist or is not a regular file\n", execplusjailpath);
			print_usage();
			exit(1);
		}
		free(execplusjailpath);
	}
	/* make sure the jailkit config directory is owned root:root and not writable for others */
	if ( (testsafepath(INIPREFIX, 0, 0) &~TESTPATH_GROUPW) != 0 ) {
		syslog(LOG_ERR, "abort, jailkit configuration directory "INIPREFIX" is not safe; it should be owned 0:0 and not writable for others");
		exit(14);
	}
	parser = new_iniparser(CONFIGFILE);
	if (parser) {
		/* first check for a section specific for this user, then for a group section, else a DEFAULT section */
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
			/* from this section, retrieve the options
			 - which jails are allowed
			 - which shell to use
			 - if the user has to be in <jail>/etc/passwd (only if shell is given)
			 */
			unsigned int pos = iniparser_get_position(parser) - strlen(section) - 2;
			if (iniparser_get_string_at_position(parser, section, "allowed_jails", pos, buffer, 1024) > 0) {
				DEBUG_MSG("found allowed_jails=%s\n",buffer);
				allowed_jails = explode_string(buffer, ',');
			}

			skip_injail_passwd_check = iniparser_get_int_at_position(parser, section, "skip_injail_passwd_check", pos);

			free(section);
		} else {
			DEBUG_MSG("no relevant section found in configfile\n");
		}
		if (allowed_jails == NULL) {
			syslog(LOG_ERR,"abort, no relevant section for user %s (%d) or group %s (%d) or DEFAULT found in "CONFIGFILE, pw->pw_name,getuid(),gr->gr_name,getgid());
			exit(1);
		}
		iniparser_close(parser);
	} else {
		DEBUG_MSG("no configfile "CONFIGFILE" ??\n");
		syslog(LOG_ERR,"abort, no config file "CONFIGFILE);
		exit(1);
	}

#ifdef OPEN_MAX
    i = OPEN_MAX;
#elif defined(NOFILE)
    i = NOFILE;
#else
    i = getdtablesize();
#endif
	while (--i > 2) {
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
		}
	}


	/* check if the requested jail is allowed */
	{
		unsigned int allowed = 0;
		/* 'jail' has an ending slash */
		for (i=0;allowed_jails[i]!=NULL&&!allowed;i++) {
			allowed = dirs_equal(jail,allowed_jails[i]);
			DEBUG_MSG("allowed=%d after testing '%s' with '%s'\n",allowed,jail,allowed_jails[i]);
		}
		if (allowed!=1) {
			syslog(LOG_ERR,"abort, user %s (%d) is not allowed in jail %s",pw->pw_name, getuid(),jail);
			exit(21);
		}
	}
	/* test the jail */
	if (!basicjailissafe(jail)) {
		syslog(LOG_ERR, "abort, jail %s is not safe, check ownership and permissions for the jail inclusing system directories such as /etc, /lib, /usr, /dev, /sbin, and /bin", jail);
		exit(53);
	}

	if (chdir(jail) != 0) {
		syslog(LOG_ERR, "abort, chdir(%s) failed: %s",jail,strerror(errno));
		exit(19);
	} else {
		char test[1024];
		/* test if it really succeeded */
		if (getcwd(test, 1024)==NULL || !dirs_equal(jail, test)) {
			syslog(LOG_ERR, "abort, the current dir is %s after chdir(%s), but it should be %s",test,jail,jail);
			exit(21);
		}
	}

	syslog(LOG_INFO, "entering jail %s for user %s (%d) in order to execute %s", jail, pw->pw_name, getuid(), executable);

	/* do the chroot() call */
	if (chroot(jail)) {
		syslog(LOG_ERR, "abort, chroot(%s) failed: %s", jail, strerror(errno));
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
		/* drop all privileges, we first have to setgid(),
			then we call setuid() */
		if (setgid(getgid())) {
			syslog(LOG_ERR, "abort, failed to set effective group ID %d: %s", getgid(), strerror(errno));
			exit(34);
		}
		if (setuid(getuid())) {
			syslog(LOG_ERR, "abort, failed to set effective user ID %d: %s", getuid(), strerror(errno));
			exit(36);
		}
	}

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
			syslog(LOG_ERR, "abort, failed to get user information in the jail for user ID %d: %s, check %s/etc/passwd",getuid(),strerror(errno),jail);
			exit(35);
		}
		DEBUG_MSG("got %s as pw_dir\n",pw->pw_dir);
		if (pw->pw_uid != getuid()) {
			syslog(LOG_ERR, "abort, got user information in the jail for user ID %d instead of user ID %d, check %s/etc/passwd",pw->pw_uid,getuid(),jail);
			exit(35);
		}
		gr = getgrgid(getgid());
		if (!gr) {
			syslog(LOG_ERR, "abort, failed to get group information in the jail for group ID %d: %s, check %s/etc/group",getgid(),strerror(errno),jail);
			exit(35);
		}
		if (strcmp(pw->pw_name, oldpw_name)!=0) {
			syslog(LOG_ERR, "abort, username %s differs from jail username %s for user ID %d, check /etc/passwd and %s/etc/passwd", oldpw_name, pw->pw_name, getuid(), jail);
			exit(37);
		}
		if (strcmp(gr->gr_name, oldgr_name)!=0) {
			syslog(LOG_ERR, "abort, groupname %s differs from jail groupname %s for group ID %d, check /etc/passwd and %s/etc/passwd", oldgr_name, gr->gr_name, getgid(), jail);
			exit(37);
		}
		free(oldpw_name);
		free(oldgr_name);
	}

	/* now execute the jailed shell */
	execv(executable, newargv);
	/* normally we wouldn't come to this bit of code */
	syslog(LOG_ERR, "ERROR: failed to execute %s for user %s (%d), check the permissions and libraries of %s%s",executable,pw->pw_name,getuid(),jail,executable);

	free(jail);
	exit(111);
}
