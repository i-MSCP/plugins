/*
 * logUtils.c
 *
 *  Created on: 21 Jan 2012
 *      Author: martin
 */

#include <stdio.h>
#include <stdlib.h>
#include <stdbool.h>
#include <syslog.h>
#include <stdarg.h>
#include <err.h>

#include "logUtils.h"
#ifndef HAVE_STRLCAT
#	include "strl.h"
#endif


// global variables only in scope for these functions
static bool logToStderr = true;
static bool logToSyslog = false;
static int log_lev = LOG_ERR;
static int syslogPriority = LOG_ERR;


void log_to_stderr(int arg) {
	// pass a non-zero value to enable logging to stderr
	// or zero to disable it
	if (arg) {
		logToStderr = true;
	}
	else {
		logToStderr = false;
	}
}


void log_to_syslog(arg) {
	// pass a non-zero value to enable logging to syslog
	// or zero to diable it
	if (arg) {
		logToSyslog = true;
	}
	else {
		logToSyslog = false;
	}
}


int log_set_threshold(int level) {
	// set least important message to be logged, and syslog facility to be used
	// Returns: previous log level
	int retval;

	retval = log_lev;
	log_lev = level;

	return retval;
}


int log_priority(int priority) {
	// set level of priority at which syslog entries are created (don't set too high, else they will just be filtered out!)
	int retval;

	retval = syslogPriority;
	syslogPriority = priority;

	return retval;
}


void logmsg(int priority, const char *fmt, ...) {
	//  if 'priority' is less than or equal to current log threshold, log message to stderr and/or syslog

	va_list args;
	va_start(args, fmt);

	if ( priority <= log_lev ) {
		if (logToStderr) {
			vfprintf(stderr, fmt, args);
			fprintf(stderr, "\n");
		}
		if (logToSyslog) {
			vsyslog(syslogPriority, fmt, args);

		}
	}

	va_end(args);
}


void reallogwarn(const char *file, const char *func, int line, int priority, char *fmt, ...) {
	va_list args;
	char buf[LOGENTRYMAXLEN];	// NB avoid 'static' in order to maintain thread safety

	va_start(args, fmt);

	if ( priority <= log_lev ) {
		// compose log entry
		snprintf(buf, sizeof(buf), "in %s() at line %d of %s", func, line, file);
		if (*fmt != '\0') {
			strlcat(buf, ": ", sizeof(buf));
			vsnprintf(buf + strlen(buf), sizeof(buf) - strlen(buf), fmt, args);
		}

		// record log entry
		if ( logToStderr ) {
			fprintf(stderr, "%s: %s\n", buf, strerror(errno));
		}
		if ( logToSyslog ) {
			syslog(syslogPriority, "%s: %m", buf);
		}
	}

	va_end(args);
}


void reallogwarnx(const char *file, const char *func, int line, int priority, char *fmt, ...) {
	va_list args;
	char buf[LOGENTRYMAXLEN];	// NB avoid 'static' in order to maintain thread safety
	va_start(args, fmt);

	if ( priority <= log_lev ) {
		// compose log entry
		snprintf(buf, sizeof(buf), "in %s() at line %d of %s", func, line, file);
		if (*fmt != '\0') {
			strlcat(buf, ": ", sizeof(buf));
			vsnprintf(buf + strlen(buf), sizeof(buf) - strlen(buf), fmt, args);
		}

		// record log entry
		if ( logToStderr ) {
			fprintf(stderr, "%s\n", buf);
		}
		if ( logToSyslog ) {
			syslog(syslogPriority, "%s", buf);
		}
	}

	va_end(args);
}

