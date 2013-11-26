/*
 * the jailkit chroot() launcher
 * this program does a chroot(), changes uid and gid and then executes the daemon
 *
 * I tried to merge some of the ideas from chrsh by Aaron D. Gifford,
 * start-stop-daemon from Marek Michalkiewicz and suexec by the Apache
 * group in this utility
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

#include "config.h"

#include <stdio.h>
#include <stdlib.h>
#include <unistd.h>
#include <string.h>
#include <sys/stat.h>
#include <sys/types.h>
#include <errno.h>
#include <syslog.h>
#ifdef HAVE_GETOPT_H
#include <getopt.h>
#endif
#ifdef HAVE_LIBERTY_H
#include <liberty.h>
#endif
#include <grp.h>
#include <pwd.h>

#include "jk_lib.h"
#include "config.h"
#define PROGRAMNAME "jk_chrootlaunch"

static int parse_uid(char *tmpstr) {
	struct passwd *pw=NULL;
	if (!tmpstr) return -1;
	if (tmpstr && tmpstr[0] >= '0' && tmpstr[0] <= '9') {
		int tmp = strtol(tmpstr, NULL, 10);
		if (tmp >= 0) {
			pw = getpwuid(tmp);
			if (!pw) {
				syslog(LOG_ERR, "abort, user '%s' does not exist (interpreted as uid %d)",tmpstr,tmp);
				exit(1);
			}
		} else {
			syslog(LOG_ERR, "abort, user '%s' is a negative uid (interpreted as %d)",tmpstr,tmp);
			exit(1);
		}
	} else {
		pw = getpwnam(tmpstr);
		if (!pw) {
			syslog(LOG_ERR, "abort, user %s does not exist",tmpstr);
			exit(1);
		}
	}
	return pw->pw_uid;
}

static int parse_gid(char *tmpstr) {
	struct group *gr=NULL;
	if (!tmpstr) return -1;
	if (tmpstr && tmpstr[0] >= '0' && tmpstr[0] <= '9') {
		int tmp = strtol(tmpstr, NULL, 10);
		if (tmp >= 0) {
			gr = getgrgid(tmp);
			if (!gr) {
				syslog(LOG_ERR, "abort, group '%s' does not exist (interpreted as gid %d)",tmpstr,tmp);
				exit(1);
			}
		} else {
			syslog(LOG_ERR, "abort, group '%s' is a negative gid (interpreted as %d)",tmpstr,tmp);
			exit(1);
		}
	} else {
		gr = getgrnam(tmpstr);
		if (!gr) {
			syslog(LOG_ERR, "abort, group %s does not exist",tmpstr);
			exit(1);
		}
	}
	return gr->gr_gid;
}

/* tests the jail and executable, if they exists etc.
returns a newly allocated executable relative to the chroot,
so it can be used during exec() */
static char *test_jail_and_exec(char *jail, char *exec) {
	struct stat sbuf;
	char *tmpstr, *retval;
	if (!jail) {
		syslog(LOG_ERR,"abort, a jaildir must be specified on the commandline");
		exit(21);
	}
	if (!exec) {
		syslog(LOG_ERR,"abort, an executable must be specified on the commandline");
		exit(23);
	}
	if (jail[0] != '/') {
		syslog(LOG_ERR,"abort, jail '%s' not accepted, the jail must be an absolute path", jail);
		exit(27);
	}
	/* test the jail existance */
	if (!basicjailissafe(jail)) {
		syslog(LOG_ERR, "abort, jail directory %s is not a safe jail, check ownership and permissions",jail);
		exit(25);
	}
	/* test the executable, first we test if the executable was specified relative in the jail or absolute */
	if (strncmp(jail,exec,strlen(jail))==0) {
		/* the exec contains the path of the jail, so it was absolute */
		tmpstr = strdup(exec);
	} else {
		/* the executable was specified as relative path to the jail, combine them together */
		tmpstr = malloc0((strlen(exec)+strlen(jail)+1)*sizeof(char));
		tmpstr = strcat(strcat(tmpstr, jail), exec);
	}
	if (lstat(tmpstr, &sbuf) == 0) {
		if (S_ISLNK(sbuf.st_mode)) {
			syslog(LOG_ERR, "abort, executable %s is a symlink", tmpstr);
			exit(29);
		}
		if (S_ISREG(sbuf.st_mode) && (sbuf.st_mode & (S_ISUID | S_ISGID))) {
			syslog(LOG_ERR, "abort, executable %s is setuid/setgid file", tmpstr);
			exit(29);
		}
		if (sbuf.st_mode & (S_IWGRP | S_IWOTH)) {
			syslog(LOG_ERR, "abort, executable %s is writable for group or others", tmpstr);
			exit(29);
		}
		if (sbuf.st_uid != 0 || sbuf.st_gid != 0) {
			syslog(LOG_ERR, "abort, executable %s is not owned root:root",tmpstr);
			exit(29);
		}
	} else {
		syslog(LOG_ERR, "abort, could not get properties for executable %s: %s",tmpstr,strerror(errno));
		exit(29);
	}
	retval = strdup(&tmpstr[strlen(jail)]);
	free(tmpstr);
	return retval;
}

static void print_usage() {
	printf(PACKAGE" "VERSION"\nUsage: "PROGRAMNAME" -j jaildir [-u user] [-g group] [-p pidfile] -x executable -- [executable options]\n");
	printf("\t-p|--pidfile pidfile\n");
	printf("\t-j|--jail jaildir\n");
	printf("\t-x|--exec executable\n");
	printf("\t-u|--user username|uid\n");
	printf("\t-g|--group group|gid\n");
	printf("\t-h|--help\n");
	printf(PROGRAMNAME" logs all errors to syslog, for diagnostics check your logfiles\n");
}

int main (int argc, char **argv) {
	char *pidfile=NULL, *jail=NULL, *exec=NULL;
	int uid=-1,gid=-1;
	unsigned int i;
	char **newargv;

	openlog(PROGRAMNAME, LOG_PID, LOG_DAEMON);

	{
		int c=0;
		char *tuser=NULL, *tgroup=NULL, *texec=NULL;
		while (c != -1) {
			int option_index = 0;
			static struct option long_options[] = {
				{"pidfile", required_argument, NULL, 'p'},
				{"jail", required_argument, NULL, 'j'},
				{"exec", required_argument, NULL, 'x'},
				{"user", required_argument, NULL, 'u'},
				{"group", required_argument, NULL, 'g'},
				{"help", no_argument, NULL, 'h'},
				{"version", no_argument, NULL, 'V'},
				{NULL, 0, NULL, 0}
			};
		 	c = getopt_long(argc, argv, "j:p:u:g:x:hv",long_options, &option_index);
			switch (c) {
			case 'j':
				jail = ending_slash(optarg);
				break;
			case 'p':
				pidfile = strdup(optarg);
				break;
			case 'u':
				tuser = strdup(optarg);
				break;
			case 'g':
				tgroup = strdup(optarg);
				break;
			case 'x':
				texec = strdup(optarg);
				break;
			case 'h':
			case 'V':
				print_usage();
				exit(1);
			}
		}
		uid = parse_uid(tuser);
		gid = parse_gid(tgroup);
		exec = test_jail_and_exec(jail,texec);
		/* construct the new argv from all leftover options */
		newargv = malloc0((2 + argc - optind)*sizeof(char *));
		newargv[0] = exec;
		c = 1;
		while (optind < argc) {
			newargv[c] = strdup(argv[optind]);
			c++;
			optind++;
		}
		free(tuser);
		free(tgroup);
		free(texec);
	}

	if (pidfile) {
		FILE *pidfilefd = fopen(pidfile, "w");
		int pid = getpid();
		if (pidfilefd && fprintf(pidfilefd, "%d",pid)>=0) {
			fclose(pidfilefd);
		} else {
			syslog(LOG_NOTICE, "failed to write PID into %s", pidfile);
		}
	}

	/* open file descriptors can be used to break out of a chroot, so we close all of them, except for stdin,stdout and stderr */
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

	if (chdir(jail)) {
		syslog(LOG_ERR, "abort, could not change directory chdir() to the jail %s: %s", jail,strerror(errno));
		exit(33);
	}
	if (chroot(jail)) {
		syslog(LOG_ERR, "abort, could not change root chroot() to the jail %s: %s", jail,strerror(errno));
		exit(35);
	}
	if (gid != -1 && setgid(gid)<0) {
		syslog(LOG_ERR, "abort, could not setgid %d: %s", gid,strerror(errno));
		exit(37);
	}
	if (uid != -1 && setuid(uid)<0) {
		syslog(LOG_ERR, "abort, could not setuid %d: %s", uid,strerror(errno));
		exit(39);
	}
	syslog(LOG_NOTICE,"executing %s in jail %s",exec,jail);
	execv(exec, newargv);
	syslog(LOG_ERR, "error: failed to execute %s in jail %s: %s",exec,jail,strerror(errno));
	exit(31);
}
