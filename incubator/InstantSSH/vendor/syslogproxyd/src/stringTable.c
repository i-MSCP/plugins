/*
 * stringTable.c
 *
 *  Created on: 3 Feb 2012
 *      Author: martin
 */

#include <stdlib.h>
#include <string.h>
#include <strings.h>

#include "stringTable.h"

char **stringtable_create( stringtable_t *st, long nStrings, size_t nBytes ) {
	// Create a new string table, with index space for nStrings and nBytes total capacity
	// (including trailing NULs). All index pointers are initialised to NULL.
	//
	// Returns: pointer to array of index pointers, or NULL if an error occurred

	long i;

	if( nStrings <= 0 || nBytes <= 0 )
		return NULL;

	// allocate string storage
	if( (st->startPos = malloc(nBytes)) == NULL )
		return NULL;

	// allocate index
	if( (st->index = calloc(nStrings, sizeof(char *))) == NULL  )
		return NULL;

	// initialise table and index
	st->maxStrings = nStrings;
	st->writePos = st->startPos;
	st->endPos = st->writePos + nBytes;
	st->nStrings = 0;
	for( i=0; i<nStrings; i++)
		st->index[i] = NULL;

	return st->index;
}


long stringtable_add( stringtable_t *st, const char *string ) {
	// add string to string table and pointer to index array, checking for overflow
	// Returns: number of index entries

	ssize_t nBytes;

	// check space in index
	if( st->nStrings + 1 > st->maxStrings )
		return -1;

	nBytes = 1 + strlen(string);	// number of bytes to insert

	// check for empty string
	if( nBytes == 1 && *(st->writePos - 1) == '\0' && st->writePos > st->startPos )
		st->index[st->nStrings++] = st->writePos - 1;	// re-use previous NUL if possible
	else {
		// check space in table
		if( st->writePos + nBytes > st->endPos )
			return -1;

		// copy string and trailing NUL to table
		bcopy(string, st->writePos, nBytes);

		// update index
		st->index[st->nStrings++] = st->writePos;

		// update table pointer
		st->writePos += nBytes;
	}

	return st->nStrings;
}


void stringtable_free( stringtable_t *st ) {
	// release storage allocated to index and table
	// Returns: nothing

	free( st->index );
	free( st->startPos );

}
