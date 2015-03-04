/*
 * check_msg.c
 *
 *  Created on: 21 Jan 2012
 *      Author: martin
 */


#include <ctype.h>
#include <string.h>
#include <strings.h>
#include <stdlib.h>
#include <time.h>

#include "logUtils.h"
#include "check_msg.h"
#include "tokens.h"		// tokens and globals for FLEX scanner 'validate_header.l'


//-----------------------------
// private function prototypes
//-----------------------------

static size_t insert_bytes(char *dest, char *src, size_t dest_len, size_t src_len, size_t dest_maxlen);


//----------------------------
// public function prototypes
//----------------------------

size_t validate_syslog_msg( char *msg, size_t len, size_t maxlen ) {
	// validate a syslog message, correcting missing/malformed headers
	// as required by RFC 3164.  The message must be passed in a buffer
	// with space for 1024 characters.
	//
	// Returns: new message length, or 0 if an error occurred

	// declare global variables
	extern int pri_len;		// used by 'validate_header.l' to return length of PRI field

	// define local variables
	int flex_buffer_handle;

	// initialise header scanner
	flex_buffer_handle = yy_scan_string ((const char *)msg);
	if (flex_buffer_handle == 0) {
		logmsg (LOG_ERR, "yy_scan_str(): can't create yybuffer state");
		return 0;
	}

	// scan message header
	switch (yylex ()) {
	case TOK_BAD_PRI:
		logmsg (LOG_DEBUG, "missing or malformed priority");
		len = insert_pri_timestamp_hostname (msg, len, maxlen);
		break;
	case TOK_BAD_TIMESTAMP:
		logmsg (LOG_DEBUG, "missing or malformed timestamp");
		len = pri_len + insert_timestamp_hostname (msg + pri_len, len - pri_len, maxlen - pri_len);
		break;
	case TOK_HEADER_OK:
		logmsg (LOG_DEBUG, "header is valid");
		break;
	default:
		logmsg (LOG_ERR, "yylex() returned unrecognised token");
		yy_delete_buffer (flex_buffer_handle);	// release scanner resources
		return 0;
		break;
	}

	// free scanner resources
	yy_delete_buffer (flex_buffer_handle);

	// truncate message to MAXLEN characters (the standard says 1024)
	if( len > maxlen ) {
		logmsg(LOG_DEBUG, "truncating length to %z characters", maxlen);
		len = maxlen;
	}

	return len;
}


size_t insert_pri_timestamp_hostname(char *msg, size_t msg_len, size_t msg_maxlen) {
	// insert default priority, timestamp and hostname
	// Returns: new message length, or 0 if an error occurred

	char header[msg_maxlen];
	size_t header_len;
	time_t t;
	struct tm *timestamp;

	t = time(NULL);
	if( (timestamp = localtime(&t)) == NULL ) {
		logwarn(LOG_ERR, "localtime()");
		return 0;
	}

	if( (header_len = strftime(header, msg_maxlen, "<13>%b %e %H:%M:%S localhost ", timestamp)) == 0 ) {
		logwarn(LOG_ERR, "strftime()");
		return 0;
	}

	return( insert_bytes(msg, header, msg_len, header_len, msg_maxlen) );
}


size_t insert_timestamp_hostname(char *msg, size_t msg_len, size_t msg_maxlen) {
	// insert default priority, timestamp and hostname
	// Returns: new message length, or 0 if an error occurred

	char header[msg_maxlen];
	size_t header_len;
	time_t t;
	struct tm *timestamp;

	t = time(NULL);
	if( (timestamp = localtime(&t)) == NULL ) {
		logwarn(LOG_ERR, "localtime()");
		return 0;
	}

	if( (header_len = strftime(header, msg_maxlen, "%b %e %H:%M:%S localhost ", timestamp)) == 0 ) {
		logwarn(LOG_ERR, "strftime()");
		return 0;
	}

	return( insert_bytes(msg, header, msg_len, header_len, msg_maxlen) );
}


static size_t insert_bytes(char *dest, char *src, size_t dest_len, size_t src_len, size_t dest_maxlen) {
	// prepend src_len bytes from src into dest and truncate result to dest_maxlen
	// Returns: new length of dest, or 0 if an error occurred

	if( dest_len > dest_maxlen ) {
		logwarn(LOG_ERR, "dest_len > dest_maxlen");
		return 0;
	}

	if( dest_len + src_len > dest_maxlen )
		src_len = dest_maxlen - dest_len;

	// create space
	memmove( dest + src_len, dest, src_len );

	// insert new bytes
	memmove( dest, src, src_len );

	return dest_len + src_len;
}
