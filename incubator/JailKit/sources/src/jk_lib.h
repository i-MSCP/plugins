/*
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

#ifndef __JK_LIB_H
#define __JK_LIB_H

#include "config.h"

#ifdef DEBUG
#define DEBUG_MSG printf
#else
#define DEBUG_MSG(args...)
 /**/
#endif

#ifdef DEBUG
#define DEBUG_LOG(args...) syslog(LOG_DEBUG, args)
#else
#define DEBUG_LOG(args...)
 /**/
#endif

#ifndef HAVE_MALLOC0
#define malloc0(size) memset(malloc(size),0,size)
#define HAVE_MALLOC0
#endif /* HAVE_MALLOC0 */


#define TESTPATH_NOREGPATH 1    // (0000 0001)
#define TESTPATH_GROUPW    2    // (0000 0010)
#define TESTPATH_OTHERW    4    // (0000 0100)
#define TESTPATH_SETUID    8    // (0000 1000)
#define TESTPATH_SETGID   16    // (0001 0000)
#define TESTPATH_OWNER    32    // (0010 0000)
#define TESTPATH_GROUP    64    // (0100 0000)

int file_exists(const char *path);
char *implode_array(char **arr, int arrlen, const char *delimiter);
char *ending_slash(const char *src);
int testsafepath(const char *path, int owner, int group);
int basicjailissafe(const char *path);
int dirs_equal(const char *dir1, const char *dir2);
int getjaildir(const char *oldhomedir, char **jaildir, char **newhomedir);
char *strip_string(char * string);
int count_char(const char *string, char lookfor);
char **explode_string(const char *string, char delimiter);
int count_array(char **arr);
void free_array(char **arr);

#endif /* __JK_LIB_H */
