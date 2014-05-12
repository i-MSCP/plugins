/*
Copyright (c) 2003, 2004, 2005, 2006, 2007, 2008 Olivier Sessink
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

#include <pthread.h>
#include <sys/types.h> /* socket() */
#include <sys/socket.h> /* socket() */
#include <sys/times.h> /* times() */
#include <unistd.h> /* sysconf(), getopt() */
#ifdef HAVE_GETOPT_H
#include <getopt.h>
#endif
#ifdef HAVE_LIBERTY_H
#include <liberty.h>
#endif
#include <time.h> /* nanosleep() */
#include <stdlib.h> /* malloc() */
#include <string.h> /* strcpy() */
#include <fcntl.h> /* fcntl() */
#include <stdio.h> /* DEBUG_MSG() */
#include <errno.h> /* errno */
#include <sys/un.h> /* struct sockaddr_un */
#include <sys/time.h> /* gettimeofday() */
#include <syslog.h> /* syslog() */
#include <signal.h> /* signal() */
#include <pwd.h> /* getpwnam() */
#include <sys/stat.h> /* chmod() */

#define PROGRAMNAME "jk_socketd"
#define CONFIGFILE INIPREFIX"/jk_socketd.ini"

#define MAX_SOCKETS 128
#define CHECKTIME 100000 /* 0.1 seconds */
#define FULLSECOND 1000000
#define MILLISECOND 1000
#define MICROSECOND 1

#include "jk_lib.h"
#include "iniparser.h"

typedef struct {
	pthread_t thread;
	char *outpath;
	char *inpath;
	unsigned int normrate;
	unsigned int peakrate;
	unsigned int roundtime;

	int outsocket;
	int insocket;

	unsigned short int lastwaspeak; /* the previous round was a peakround */
	struct timeval lasttime; /* last time the socket was checked */
	struct timeval lastreset; /* last time that lastsize was set to zero */
	unsigned int lastsize; /* bytes since lastreset */
} Tsocketlink;

/* the only global variable */
unsigned short int do_clean_exit = 0;

static void close_socketlink(Tsocketlink *sl) {
	close(sl->insocket);
	shutdown(sl->insocket,2);
	free(sl->inpath);
	free(sl);
}

/*static void clean_exit(int numsockets, Tsocketlink **sl) {
	unsigned int i;
	for (i=0;i<numsockets;i++) {
		close_socketlink(sl[i]);
	}
}*/

static Tsocketlink *new_socketlink(int outsocket, char *inpath, int normrate, int peakrate, int roundtime, int nodetach) {
	Tsocketlink *sl;
/*	int flags;*/
	int ret;
	struct sockaddr_un serv_addr;

	sl = malloc(sizeof(Tsocketlink));
	sl->outsocket = outsocket;
	sl->inpath = strdup(inpath);
	sl->normrate = normrate;
	sl->peakrate = peakrate;
	sl->roundtime = roundtime;

	sl->insocket = socket(PF_UNIX, SOCK_DGRAM, 0);
/*	DEBUG_MSG("new_socketlink, insocketserver %s at %d\n", sl->inpath, sl->insocket);*/

	strncpy(serv_addr.sun_path, sl->inpath, sizeof(serv_addr.sun_path));
	serv_addr.sun_family = PF_UNIX;
	unlink(sl->inpath);
	ret = bind(sl->insocket, (struct sockaddr *)&serv_addr, sizeof(serv_addr));
	if (ret != 0 || chmod(sl->inpath, 0666)) {
		DEBUG_MSG("bind returned erno %d: %s\n",errno, strerror(errno));
		syslog(LOG_CRIT, "while opening %s: %s", sl->inpath, strerror(errno));
		if (nodetach) printf("while opening %s: %s\n",sl->inpath,strerror(errno));
		close_socketlink(sl);
		return NULL;
	}

/*	flags = fcntl(sl->insocket, F_GETFL, 0);
	fcntl(sl->insocket, F_SETFL, O_NONBLOCK|flags);*/

	gettimeofday(&sl->lastreset,NULL);
	return sl;
}

static void sleepround(long microseconds, int debug) {
	if (microseconds > 0) {
		struct timespec sleeptime;
		sleeptime.tv_sec = microseconds / FULLSECOND;
		sleeptime.tv_nsec = (microseconds % FULLSECOND)*1000;

		DEBUG_MSG("sleepround, sleeping %d milliseconds\n", (int)(microseconds / MILLISECOND));
		nanosleep(&sleeptime, NULL);
		/*pthread_delay_np(&sleeptime);*/
	}
}

/* return time difference in micro-seconds */
long timediff(struct timeval start, struct timeval end) {
	return (long) (end.tv_sec - start.tv_sec) * FULLSECOND + (end.tv_usec - start.tv_usec);
}

#define BUFSIZE 512

static void socketlink_handle(Tsocketlink *sl) {
	while (do_clean_exit == 0) {
		char *buf[BUFSIZE];
		int numbytes;
		numbytes = recvfrom(sl->insocket, &buf, BUFSIZE, 0, NULL, 0);
		/* numbytes = read(sl->insocket, &buf, BUFSIZE); */
		if (numbytes < 0) {
			DEBUG_MSG("recvfrom error %d: %s\n", errno, strerror(errno));
		} else if (numbytes > 0) {
			int ret = send(sl->outsocket, &buf, numbytes, 0);
			if (ret == -1) {
				DEBUG_MSG("send error %d: %s\n", errno, strerror(errno));
				syslog(LOG_CRIT, "failed to write log message, error %d: %s",errno,strerror(errno));
			} else if (ret != numbytes) {
				syslog(LOG_WARNING, "failed to write complete log message, message was %d bytes, delivered %d bytes",numbytes, ret);
			}
			/* write(sl->outsocket, &buf, numbytes); */
			gettimeofday(&sl->lasttime,NULL);
			sl->lastsize += numbytes;
			DEBUG_MSG("lastsize=%d\n",sl->lastsize);
			if (sl->lastsize > sl->peakrate) {
				/* size is over the peakrate, mark this round as peak, and sleep the rest of the second */
				DEBUG_MSG("sleep, we're over peakrate!! (size=%d)\n",sl->lastsize);
				syslog(LOG_WARNING, "device %s is over the peak limit (%d bytes/s)", sl->inpath, (unsigned int)((unsigned long)sl->peakrate * (unsigned long)1000000 / (unsigned long)sl->roundtime));
				sleepround(sl->roundtime - timediff(sl->lastreset, sl->lasttime),1);
				sl->lastsize = 0;
				gettimeofday(&sl->lastreset,NULL);
				sl->lastwaspeak = 1;
				DEBUG_MSG("reset all to zero, peak=1\n");
			} else if (sl->lastsize > sl->normrate) {
				/* size is over the normal size, check if the time is also over the normal time */
				if (timediff(sl->lastreset, sl->lasttime) > sl->roundtime) {
					/* we will reset, the time is over a second */
					DEBUG_MSG("time is over a second (timediff=%ld), reset all to zero, peak=1\n", timediff(sl->lastreset, sl->lasttime));
					sl->lastsize = 0;
					gettimeofday(&sl->lastreset,NULL);
					sl->lastwaspeak = 1;
				} else {
					DEBUG_MSG("timediff = %ld\n",timediff(sl->lastreset, sl->lasttime));
					/* it is under a second, this is a peak, what to do now? */
					if (sl->lastwaspeak) {
						/* lastround was a peak, so this one is not allowed to be a peak, sleeping!! */
						DEBUG_MSG("sleep, previous was a peak and we're over the normal rate (size=%d)!\n", sl->lastsize);
						syslog(LOG_WARNING, "device %s is over the normal limit (%d bytes/s), directly after a peak", sl->inpath, (sl->normrate * 1000000 / sl->roundtime));
						sleepround(sl->roundtime - timediff(sl->lastreset, sl->lasttime),1);
						sl->lastsize = 0;
						gettimeofday(&sl->lastreset,NULL);
						sl->lastwaspeak = 1;
						DEBUG_MSG("reset all to zero, peak=1\n");
					} else {
						/* lastround was not a peak, so this round is allowed to be a peak */
						DEBUG_MSG("detected a new peak (size=%d)!\n", sl->lastsize);
					}
				}
			} else if (timediff(sl->lastreset, sl->lasttime) > sl->roundtime) {
				DEBUG_MSG("time is over a second (timediff=%ld), reset all to zero, peak=0\n", timediff(sl->lastreset, sl->lasttime));
				sl->lastsize = 0;
				gettimeofday(&sl->lastreset,NULL);
				sl->lastwaspeak = 0;
			}
		}
	}
}
/*
static void sigterm_handler(int signal) {
	DEBUG_MSG("sigterm_handler, called\n");
	exit(1);
	if (do_clean_exit != 1) {
		syslog(LOG_NOTICE, "got signal %d, exiting", signal);
		do_clean_exit = 1;
		/ *raise(SIGTERM);* /
	}
}*/

static void usage() {
	printf(PROGRAMNAME" version "VERSION", usage:\n\n");
	printf(" -n|--nodetach                 do not detach from the terminal, useful for debugging\n");
	printf(" -p pidfile|--pidfile=pidfile  write PID to file pidfile\n");
	printf(" -h|--help                     this help screen\n\n");
	printf(" --socket=/path/to/socket      do not read ini file, create specific socket\n");
	printf(" --base=integer                message rate limit (in bytes) per interval\n");
	printf(" --peak=integer                message rate limit peak (in bytes)\n");
	printf("                               (--peek supported for backwards compatibility)");
	printf(" --interval=float              message rate limit interval\n\n");
}

static unsigned short int have_socket(char *path, Tsocketlink **sl, unsigned int size) {
	unsigned int i;
	for (i=0;i<size;i++) {
		if (strcmp(sl[i]->inpath, path)==0) return 1;
	}
	return 0;
}

int main(int argc, char**argv) {
	Tsocketlink *sl[MAX_SOCKETS];

/*	struct timeval startround, endround;*/
	unsigned int numsockets = 0;
	int outsocket;
	unsigned int i;
	unsigned short int nodetach = 0;
	char *m_socket = NULL;
	char *pidfile = NULL;
	FILE *pidfilefd = NULL;
	unsigned int m_base=511, m_peak=2048;
	float m_interval=10.0;
/*	signal(SIGINT, sigterm_handler);
	signal(SIGTERM, sigterm_handler);*/

	{
		int c;
		while (1) {
			int option_index = 0;
			static struct option long_options[] = {
				{"pidfile", required_argument, NULL, 0},
				{"nodetach", no_argument, NULL, 0},
				{"help", no_argument, NULL, 0},
				{"socket", required_argument, NULL, 0},
				{"base", required_argument, NULL, 0},
				{"interval", required_argument, NULL, 0},
				{"peak", required_argument, NULL, 0},
				{"peek", required_argument, NULL, 0},
				{NULL, 0, NULL, 0}
			};
		 	c = getopt_long(argc, argv, "p:nh",long_options, &option_index);
			if (c == -1)
				break;
			switch (c) {
			case 0:
				switch (option_index) {
				case 0:
					pidfile = strdup(optarg);
					break;
				case 1:
					nodetach = 1;
					break;
				case 2:
					usage();
					exit(0);
				case 3:
					m_socket = strdup(optarg);
					break;
				case 4:
					m_base = atoi(optarg);
					break;
				case 5:
					m_interval = (float)atof(optarg);
					break;
				case 6:
					m_peak = atoi(optarg);
					break;
				case 7: /* for backwards compatibility */
					m_peak = atoi(optarg);
					break;
				}
				break;
			case 'p':
				pidfile = strdup(optarg);
				break;
			case 'n':
				nodetach = 1;
				break;
			case 'h':
				usage();
				exit(0);
			}
		}
	}
	openlog(PROGRAMNAME, LOG_PID, LOG_DAEMON);

	outsocket = socket(AF_UNIX, SOCK_DGRAM, 0);
	DEBUG_MSG("outsocket at %d\n", outsocket);
	{
		struct sockaddr client_addr;
		strncpy(client_addr.sa_data, "/dev/log", sizeof(client_addr.sa_data));
		client_addr.sa_family = AF_UNIX;
		if (connect(outsocket, &client_addr, sizeof(client_addr)) != 0) {
			/*DEBUG_MSG("connect returned erno %d: %s\n",errno, strerror(errno));*/
			outsocket = socket(AF_UNIX, SOCK_STREAM, 0);
			if (connect(outsocket, &client_addr, sizeof(client_addr)) != 0) {
				syslog(LOG_CRIT, "version "VERSION", while connecting to /dev/log: %s", strerror(errno));
				if (nodetach) printf("version "VERSION", while connecting to /dev/log: %s\n",strerror(errno) );
				exit(1);
			}
		}
		if (nodetach) printf("opened /dev/log\n");
	}

	if (!m_socket)
	{
		char buf[1024], *tmp;
		Tiniparser *ip = new_iniparser(CONFIGFILE);
		if (!ip) {
			syslog(LOG_CRIT, "version "VERSION", abort, could not parse configfile "CONFIGFILE);
			if (nodetach) printf("version "VERSION", abort, could not parse configfile "CONFIGFILE"\n");
			exit(11);
		}
		while ((tmp = iniparser_next_section(ip, buf, 1024))) {
			if (!have_socket(tmp, sl, numsockets)) {
				unsigned int base=511, peak=2048;
				float interval=5.0;
				long prevpos, secpos;

				if (numsockets == MAX_SOCKETS) {
					syslog(LOG_NOTICE, "Warning: jk_socketd is compiled to support maximum %d sockets and more sockets are requested, not all sockets are opened!",MAX_SOCKETS);
					if (nodetach) printf("Warning: jk_socketd is compiled to support maximum %d sockets and more sockets are requested, not all sockets are opened!\n",MAX_SOCKETS);
					break;
				}

				prevpos = iniparser_get_position(ip);
				secpos = prevpos - strlen(tmp)-4;
				DEBUG_MSG("secpos=%ld, prevpos=%ld\n",secpos,prevpos);
				base = iniparser_get_int_at_position(ip, tmp, "base", secpos);
				peak = iniparser_get_int_at_position(ip, tmp, "peak", secpos);
				interval = iniparser_get_float_at_position(ip, tmp, "interval", secpos);
				iniparser_set_position(ip, secpos);
				if (10 > base || base >  1000000) base = 511;
				if (100 > peak || peak > 10000000 || peak < base) {
					/* for backwards compatibility we check 'peek' */
					peak = iniparser_get_int_at_position(ip, tmp, "peek", secpos);
					if (100 > peak || peak > 10000000 || peak < base) {
						peak = 2048;
					}
				}
				if (0.01 > interval || interval > 60.0) interval = 5.0;
				sl[numsockets] = new_socketlink(outsocket, tmp, base, peak, (int)(interval*1000000.0), nodetach);
				if (sl[numsockets]) {
					syslog(LOG_NOTICE, "version "VERSION", listening on socket %s with rates [%d:%d]/%f",tmp,base,peak,interval);
					if (nodetach) printf("version "VERSION", listening on socket %s with rates [%d:%d]/%f\n",tmp,base,peak,interval);
					numsockets++;
				} else {
					if (nodetach) printf("version "VERSION", failed to create socket %s\n",tmp);
				}
				DEBUG_MSG("setting position to %ld\n",prevpos);
				iniparser_set_position(ip, prevpos);
			} else {
				syslog(LOG_NOTICE, "version "VERSION", socket %s is mentioned multiple times in config file",tmp);
				if (nodetach) printf("version "VERSION", socket %s is mentioned multiple times in config file\n",tmp);
			}

		}
	}
	else
	{
		unsigned int base=m_base, peak=m_peak;
		float interval=m_interval;
		if (10 > base || base >  1000000) base = 511;
		if (100 > peak || peak > 10000000 || peak < base) peak = 2048;
		if (0.01 > interval || m_interval > 60.0) interval = 5.0;
		sl[numsockets] = new_socketlink(outsocket, m_socket, base, peak, (int)(interval*1000000.0), nodetach);
		if (sl[numsockets]) {
			syslog(LOG_NOTICE, "version "VERSION", listening on socket %s with rates [%d:%d]/%f",m_socket,base,peak,interval);
			if (nodetach) printf("version "VERSION", listening on socket %s with rates [%d:%d]/%f\n",m_socket,base,peak,interval);
			numsockets++;
		} else {
			if (nodetach) printf("version "VERSION",failed to create socket %s\n",m_socket);
		}
	}

	if (numsockets == 0) {
		printf("version "VERSION", no sockets specified in configfile "CONFIGFILE" or on commandline, nothing to do, exiting...\n");
		syslog(LOG_ERR,"version "VERSION", no sockets specified in configfile "CONFIGFILE" or on commandline, nothing to do, exiting...");
		exit(1);
	}

	if (pidfile) pidfilefd = fopen(pidfile, "w");

	/* now chroot() to some root:root dir without binaries, and change to nobody:nogroup */

	{
		struct passwd *pw = getpwnam("nobody");
		int ret;
		char *path = INIPREFIX;
		if (!pw) {
			syslog(LOG_ERR, "cannot get UID and GID for user nobody");
			if (nodetach) printf("cannot get UID and GID for user nobody");
		}
		ret = testsafepath(path, 0,0);
		if (ret != 0) {
			syslog(LOG_ERR, "abort, path %s is not owned root:root or does not have 0644 permissions\n",path);
			exit(53);
		}

		if (!(chdir(path)==0 && chroot(path)==0)) {
			syslog(LOG_ERR, "failed to chroot to "INIPREFIX);
			if (nodetach) printf("failed to chroot to "INIPREFIX);
		}
		if (pw) {
			if (setgid(pw->pw_gid) != 0 || setuid(pw->pw_uid) != 0) {
				syslog(LOG_ERR, "failed to change to user nobody (uid=%d, gid=%d)", pw->pw_uid, pw->pw_gid);
				if (nodetach) printf("failed to change to user nobody (uid=%d, gid=%d)\n", pw->pw_uid, pw->pw_gid);
			}
		}
	}

	if (!nodetach) {
		/* detach and set the detached process as the new process group leader */
		if (fork() != 0) {
			DEBUG_MSG("exit process %d\n", getpid());
			exit(0);
		}
		DEBUG_MSG("after fork(), process id %d continues\n", getpid());
		setsid();
	}
	if (pidfile) {
		if (pidfilefd) {
			/* we should do this using fscanf(pidfilefd,"%d", getpid()) */
			char buf[32];
			unsigned int size;
			size = snprintf(buf, 32, "%d", getpid());
			fwrite(buf, size, sizeof(char), pidfilefd);
			fclose(pidfilefd);
		} else {
			syslog(LOG_NOTICE, "failed to write pid to %s", pidfile);
		}
	}
	/* use sl[0] for the main process */
	for (i=1;i<numsockets;i++) {
		pthread_create(&sl[i]->thread, NULL,(void*)&socketlink_handle, (void*) sl[i]);
		DEBUG_MSG("created thread %i for %s\n",i,sl[i]->inpath);
	}
	DEBUG_MSG("main thread starting work on socket\n");
	socketlink_handle(sl[0]);
/*	DEBUG_MSG("pause() for process %d\n",getpid());
	pause();
	DEBUG_MSG("before clean_exit\n");
	clean_exit(numsockets,sl);
	DEBUG_MSG("after clean_exit\n");
	if (nodetach) printf("caught signal, exiting\n");*/
	exit(0);
}
