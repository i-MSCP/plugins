#ifdef __linux__
#define _GNU_SOURCE	// setresgid() etc
#endif

#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>
#include <stdlib.h>
#include <stdbool.h>
#ifdef __linux__
#include <grp.h>	// setgroups() etc
#endif

#include "logUtils.h"

int drop_privileges(uid_t newuid, gid_t newgid) {

	// drop root privileges and switch to the specified UID and GID
	// Returns: 0 for success, otherwise -1 if an error occurred

	uid_t realuid, effectiveuid, saveduid;
	gid_t realgid, effectivegid, savedgid;

	// check whether UID and/or GID need to change
	if( getuid() == newuid && getgid() == newgid ) {
		logmsg(LOG_DEBUG, "UID and GID match current user - no need to drop privileges");
		return 0;
	}

	// check we are root
	if ( getuid() != 0 ) {
		logmsg(LOG_ERR, "insufficient permission to change UID/GID");
		return -1;
	}

	// prune anciliary groups before dropping root privilege
	if ( setgroups(1, &newgid) ) {
		return -1;
	}

	// set real, effective and saved GID before dropping the UID
	if ( setresgid(newgid, newgid, newgid) ) {
		return -1;
	}

	// set real, effective and saved UID
	if ( setresuid(newuid, newuid, newuid) ) {
		return -1;
	}


	// check everything worked
	if ( getresgid(&realgid, &effectivegid, &savedgid) || realgid!=newgid || effectivegid!=newgid || savedgid!=newgid ) {
		return -1;
	}
	if ( getresuid(&realuid, &effectiveuid, &saveduid) || realuid!=newuid || effectiveuid!=newuid || saveduid!=newuid ) {
		return -1;
	}

	return 0;
}


int enter_chroot( const char *appname, const char *chroot_dir ) {
	// enter chroot jail
	// Returns: 0 if the chroot() was successful, otherwise -1

	struct stat metadata;

	// connect to syslog before entering the jail (in case there is no log socket in it)
	openlog(appname, LOG_NDELAY, LOG_USER);

	// chroot to target directory
	if( chroot(chroot_dir) ) {
		logwarn(LOG_ERR, "chroot()");
		return -1;
	} else {
		// check for /etc/localtime
		if( stat("/etc/localtime", &metadata) )
			logmsg(LOG_WARNING, "warning: can't stat /etc/localtime in chroot area - syslog timestamps may have wrong timezone");

		// check for /dev/null
		if( stat("/dev/null", &metadata) )
			logmsg(LOG_WARNING, "warning: can't stat /dev/null in chroot area - call to daemon() may fail");

		return 0;
	}
}


int detach_process( bool nochdir ) {
	// detach process and close stdin, stdout and stderr
	// change to current '/' directory unless nochdir is true
	// Returns: 0 for success, otherwise -1 if an error occurred

	logmsg(LOG_NOTICE, "server starting, logging to syslog");
	logmsg(LOG_DEBUG, "detaching process");
	errno=0;
	if ( daemon(nochdir, false) ) {
		logwarn(LOG_ERR, "daemon()");
		return -1;
	}
	log_to_syslog(true);
	log_to_stderr(false);

	return 0;
}


int init_daemon( const char *appname, bool remain_foreground, uid_t uid, gid_t gid, const char *chroot_dir ) {
	// chroot (unless chroot_dir is NULL), drop privileges to uid/gid and fork
	// into the background (unless remain_foreground is true)
	// Returns: 0 for success, otherwise -1 if an error occurred

	bool nochdir;

	// chroot if required
	nochdir = true;
	if ( chroot_dir != NULL && enter_chroot(appname, chroot_dir) == 0 )
			nochdir = false;	// successful chroot()

	// check we have root if we are going to change UID or GID
	if ( (uid != getuid() || gid != getgid()) && getuid() != 0 ) {
		logmsg(LOG_ERR, "error: running as a different UID or GID requires root privilege");
		return -1;
	}

	// drop privileges
	if ( drop_privileges(uid, gid) ) {
		logwarn(LOG_ERR, "drop_privileges(%i, %i)", uid, gid);
		return -1;
	}
	logmsg(LOG_DEBUG, "dropped privileges to uid %i, gid %i", uid, gid);


	// detach process
	if ( !remain_foreground ) {
		if( detach_process(nochdir) )
			return -1;
	}
	else
		logmsg(LOG_NOTICE, "remaining foreground");

	return 0;
}



