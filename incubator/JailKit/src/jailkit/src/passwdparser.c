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
/* #define DEBUG */

#include "config.h"
#include <stdio.h>
#include <sys/types.h>
#include <pwd.h>
#include <stdlib.h>
#include <string.h> /* memset() */
#include <strings.h> /* bzero() */
#include <fcntl.h>
#include "utils.h"

#ifdef DEBUG
#define DEBUG_MSG printf
#else
#define DEBUG_MSG(args...)
 /**/
#endif


static char *field_from_line(const char *line, int field) {
	int pos=0, fcount=0, fstart=0;
	while (1) {
		if (line[pos]==':') {
			if (field == fcount) { /* found the end of the field */
				return strndup(line+fstart,pos-fstart);
			} else {
				fcount++;
				fstart = pos+1;
			}
		} else if (line[pos] == '\0') {
			if (fcount == field) return strndup(line+fstart,pos-fstart);
			return NULL;
		}
		pos ++;
	}
	return NULL; /* should not get to this line */
}

static int int_field_from_line(const char *line, int field) {
	char *tmp;
	int retval;
	tmp = field_from_line(line, field);
	if (tmp) {
		retval = atoi(tmp);
		free(tmp);
		return retval;
	}
	return -1;
}

#define BLOCKSIZE 1024
static char * find_line(const char *filename, const char *fcont, int fnum) {
	FILE *fp;
	char buf[BLOCKSIZE+1];
	char *prev, *next, *retline=NULL;
	size_t num;
	int restlen=0;
	
/*	printf("searching for %s in field %d\n",fcont,fnum);*/
	fp = fopen(filename,"r");
	if (fp == NULL) {
		return NULL;
	}
	/* set close-on-exec so this file descriptor will not be passed 
		to the a process after an exec() call */
	fcntl(fileno(fp), F_SETFD, FD_CLOEXEC);
	bzero(buf, (BLOCKSIZE+1)*sizeof(char));
	restlen = num = fread(buf, 1, BLOCKSIZE, fp);
	DEBUG_MSG("read %d bytes from %s\n",num,filename);
	prev = buf;
	while (num || restlen) {
		/* continue the loop if we either expect more bytes from the file (represented by num)
		or there are bytes in the block left (represented by restlen) */
		DEBUG_MSG("num=%d, restlen=%d, prev=%p\n",num,restlen,prev);
		next = strchr(prev, '\n');
		if (next || num==0) {
			char *field;
			if (next) *next = '\0';
			DEBUG_MSG("line: %s\n",prev);
			field = field_from_line(prev,fnum);
			DEBUG_MSG("field=%s, we are looking for %s\n",field,fcont);
			if (field && strcmp(field,fcont)==0) {
				/* we found the line */
				retline = strdup(prev);
/*				printf("retline: %s\n",retline);*/
			}
			if (field) free(field);
			if (retline) {
				DEBUG_MSG("found a line, returning %s\n",retline);
				return retline;
			}
			if (next) {
				*next = '\n';
				prev = next+1;
			} else {
				restlen = 0;
			}
		} else {
			restlen = restlen-(prev-buf);
			DEBUG_MSG("prev=%p,buf=%p,num=%d,restlen=%d\n",prev,buf,num,restlen);
			if (restlen > 0) {
				/* no more newlines, move the  */
				DEBUG_MSG("moving %d bytes to the beginning of the block\n",restlen);
				memmove(buf, prev, restlen);
			} else {
				DEBUG_MSG("*** can restlen be < 0 ????????? restlen=%d\n",restlen);
			}
			DEBUG_MSG("reading next block\n");
			num = fread(buf+restlen, 1, BLOCKSIZE-restlen, fp);
			DEBUG_MSG("read %d bytes from %s\n",num,filename);
			restlen += num;
			DEBUG_MSG("setting byte buf[%d] to \\0\n",restlen);
			buf[restlen] = '\0';
			prev = buf;
		}
	}
	DEBUG_MSG("returning NULL\n");
	return NULL;
}

struct passwd *internal_getpwuid(const char *filename, uid_t uid) {
	static struct passwd retpw;
	char find[10], *line;
	
	snprintf(find,10,"%d",(int)uid);
	line = find_line(filename, find, 2);
	if (line) {
		retpw.pw_name = field_from_line(line, 0);
		retpw.pw_gid = int_field_from_line(line, 3);
		retpw.pw_dir = field_from_line(line, 5);
		retpw.pw_shell = field_from_line(line, 6);

		if (retpw.pw_name == NULL || retpw.pw_gid == -1 || retpw.pw_shell == NULL || retpw.pw_dir == NULL
				|| strlen(retpw.pw_name)<1 || strlen(retpw.pw_dir)<1 || strlen(retpw.pw_shell)<1) {
			if (retpw.pw_name) free(retpw.pw_name);
			if (retpw.pw_dir) free(retpw.pw_dir);
			if (retpw.pw_shell) free(retpw.pw_shell);
			return NULL;
		}

		retpw.pw_uid = uid;
		retpw.pw_gecos = NULL; /* not required */
		retpw.pw_passwd = NULL; /* not required */		
		return &retpw;
	}
	return NULL;
}

