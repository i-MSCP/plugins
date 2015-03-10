#ifndef H_STRL
#define H_STRL

/*Implementation of BSD strlcat and strlcpy, for systems that don't have them.
Written by Dave Vandervies, December 2007.
Placed in the public domain; attribution is appreciated.
*/

#ifdef __cplusplus
extern "C" { /*make C++ compilers play nicely with the linker*/
#endif

#ifndef HAS_STRLFUNCS

/*strlcpy copies a string from src to dest, creating a string at most
maxlen bytes long (including the '\0' terminator).
Returns the length of the string that would be created without
truncation, excluding the '\0' terminator. (So if the return value
is >= maxlen, the result was truncated.)
*/
size_t my_strlcpy(char *dest,const char *src,size_t maxlen);

/*strlcat appends the contents of src to dest, creating a string at
most maxlen bytes long (including the '\0' terminator).
If src is already longer than maxlen bytes long, its contents
are not changed.
Returns the length of the string that would be created without
truncation, excluding the '\0' terminator, or maxlen+strlen(src)
if no '\0' is found within maxlen bytes of *dest. (So if the
return value is >= maxlen, the result was truncated.)
*/
size_t my_strlcat(char *dest,const char *src,size_t maxlen);

#ifndef CLC_PEDANTIC
#undef strlcpy
#define strlcpy my_strlcpy
#undef strlcat
#define strlcat my_strlcat
#endif /*CLC_PEDANTIC*/

#else /*HAS_STRLFUNCS*/
#include <string.h>
#endif /*HAS_STRLFUNCS*/

#ifdef __cplusplus
} /*close extern "C"*/
#endif

#endif /*H_STRL #include guard*/
