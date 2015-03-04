/*
 * logUtils.h
 *
 *  Created on: 21 Jan 2012
 *      Author: martin
 */

#ifndef _LOGUTILS_H
#define _LOGUTILS_H


#define LOGENTRYMAXLEN 256


// macros
#define logwarn(...) reallogwarn(__FILE__, __func__, __LINE__, __VA_ARGS__ )
#define logwarnx(...) reallogwarnx(__FILE__, __func__, __LINE__, __VA_ARGS__ )


#include <string.h>
#include <errno.h>
#include <syslog.h>

void log_to_stderr (int);
void log_to_syslog (int);
int log_set_threshold(int);
int log_priority(int);

void logmsg(int, const char *, ...);
void reallogwarn(const char *, const char *, int , int , char *, ...);
void reallogwarnx(const char *, const char *, int , int , char *, ...);


#endif	// _LOGUTILS_H
