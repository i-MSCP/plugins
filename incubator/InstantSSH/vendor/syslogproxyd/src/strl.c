#include <assert.h>
#include <string.h>

#include "strl.h"

/*Implementation of BSD strlcat and strlcpy, for systems that don't have them.
Written by Dave Vandervies, December 2007.
Placed in the public domain; attribution is appreciated.
*/

#ifndef HAS_STRLFUNCS

size_t my_strlcpy(char *dest,const char *src,size_t maxlen)
{
size_t len,needed;

#ifdef PARANOID
assert(dest!=NULL);
assert(src!=NULL);
#endif

len=needed=strlen(src)+1;
if(len >= maxlen)
len=maxlen-1;

memcpy(dest,src,len);
dest[len]='\0';

return needed-1;
}

size_t my_strlcat(char *dest,const char *src,size_t maxlen)
{
size_t src_len,dst_len;
size_t len,needed;

#ifdef PARANOID
assert(dest!=NULL);
assert(src!=NULL);
#endif

src_len=strlen(src);
/*Be paranoid about dest being a properly terminated string*/
{
char *end=memchr(dest,'\0',maxlen);
if(!end)
return maxlen+src_len;
dst_len=end-dest;
}

len=needed=dst_len+src_len+1;
if(len >= maxlen)
len=maxlen-1;

memcpy(dest+dst_len,src,len-dst_len);
dest[len]='\0';

return needed-1;
}

#endif /*!HAS_STRLFUNCS*/
