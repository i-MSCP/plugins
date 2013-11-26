/* 
just test code

*/
#include "iniparser.h"
#include <string.h>

int main (int argc, char **argv) {
	Tiniparser *parser=NULL;
	
	parser = new_iniparser("iniparsertester.ini");
	if (parser) {
		unsigned long nextitempos=0;
		char buffer[1024];
		
		if (iniparser_has_section(parser, "nextitem")) {
			nextitempos = iniparser_get_position(parser) - strlen("nextitem") - 2;
		}
		
		if (iniparser_get_string(parser, "lastitem", "string1", buffer, 1024) > 0) {
			printf("%s",buffer);
		}
		if (iniparser_get_string(parser, "myitem", "string1", buffer, 1024) > 0) {
			printf("%s",buffer);
		}
		if (iniparser_get_string_at_position(parser, "nextitem", "string1", nextitempos, buffer, 1024) > 0) {
			/* does not exist */
			printf("(does not exist: %s)",buffer);
		}
		if (iniparser_get_string_at_position(parser, "nextitem", "string2", nextitempos, buffer, 1024) > 0) {
			printf("%s",buffer);
		}
		printf(" %d ",iniparser_get_int(parser, "myitem", "key1")); /* 1 */
		/* the next one is fake */
		if (iniparser_get_string_at_position(parser, "alsoitem", "key2", nextitempos, buffer, 1024) > 0) {
			printf("(does not exist: %s)",buffer);
		}
		printf(" %d ",iniparser_get_int(parser, "nextitem", "key2")); /* 2 */
		
		printf(" %d ",iniparser_get_int(parser, "myitem", "key2")); /* 3 */
		printf(" %d ",iniparser_get_int(parser, "lastitem", "key1")); /* 4 */
		if (iniparser_get_string(parser, "nextitem", "key1", buffer, 1024) > 0) {
			printf("(does not exist: %s)",buffer);
		}
		
		printf(" %d ",iniparser_get_int(parser, "lastitem", "key2")); /* 5 */
		printf(" %d ",iniparser_get_int(parser, "alsoitem", "key3")); /* 6 */
		printf(" %d ",iniparser_get_int(parser, "lastitem", "key3")); /* 7 */
		if (iniparser_get_string(parser, "myitem", "key3", buffer, 1024) > 0) {
			printf("(does not exist: %s)",buffer);
		}
		printf(" %d ",iniparser_get_int(parser, "lastitem", "key4")); /* 8 */
		printf(" %d ",iniparser_get_int(parser, "myitem", "key5")); /* 9 */
		printf(" %d ",iniparser_get_int(parser, "alsoitem", "key10")); /* 10 */
		printf("\n");		 /* 1 */
		printf(" %f ",iniparser_get_float_at_position(parser, "alsoitem", "keyfloat", 0)); /* 0.1 */
		printf("\n");		 /* 1 */
	} else {
		printf("no testfile found\n");
	}
	return 0;
}
