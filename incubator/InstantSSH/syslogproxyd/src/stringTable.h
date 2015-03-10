/*
 * stringTable.h
 *
 *  Created on: 3 Feb 2012
 *      Author: martin
 */

#ifndef STRINGTABLE_H_
#define STRINGTABLE_H_

typedef struct {
	long nStrings;
	long maxStrings;
	char *startPos;
	char *writePos;
	char *endPos;
	char **index;
} stringtable_t;

char **stringtable_create( stringtable_t *st, long nStrings, size_t nBytes );
long stringtable_add( stringtable_t *st, const char *string );
void stringtable_free( stringtable_t *st );

#endif /* STRINGTABLE_H_ */
