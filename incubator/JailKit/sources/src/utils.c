/*
Copyright (c) 2003, 2004, 2005, Olivier Sessink
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

#include <string.h>
#include <ctype.h>
#include <stdlib.h>
#include <stdio.h>
#include <syslog.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <unistd.h>

#include "utils.h"

#ifndef HAVE_CLEARENV
/* doesn't compile on FreeBSD without this */
extern char **environ;
#endif

#ifndef HAVE_STRNDUP
char *strndup(const char *s, size_t n) {
	char *ret;
	n = strnlen(s, n);
	ret = malloc(n+1);
	if (!ret) return NULL;
	memcpy(ret, s, n);
	ret[n] = 0;
	return ret;
}
#endif

#ifndef HAVE_STRNLEN
size_t strnlen(const char *s, size_t n) {
	int i;
	for (i=0; s[i] && i<n; i++)
		/* noop */ ;
	return i;
}
#endif

#ifndef HAVE_WORDEXP
#ifndef HAVE_MEMPCPY
void *mempcpy(void *dest, const void *src, size_t n) {
	memcpy(dest,src,n);
	return dest+n;
}
#endif

#ifndef HAVE_STPCPY
char *stpcpy(char *dest, const char *src) {
	strcpy(dest, src);
	return dest + strlen(src);
}
#endif
#endif /* HAVE_WORDEXP */

#ifndef HAVE_CLEARENV
/* from Linux Programmer's Manual man clearenv() 
	Used  in  security-conscious  applications.  If  it  is unavailable the
	assignment
		environ = NULL;
	will probably do. */
int clearenv(void) {
	environ = NULL;
	return 1;
}
#endif /* HAVE_CLEARENV */

#ifndef HAVE_GET_CURRENT_DIR_NAME
char *get_current_dir_name(void) {
	char *string;
	string = malloc0(512);
	return getcwd(string, 512);
}
#endif /* HAVE_GET_CURRENT_DIR_NAME */
