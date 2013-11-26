#ifndef __UTILS_H
#define __UTILS_H

#include "config.h"

#ifndef HAVE_MALLOC0
#define malloc0(size) memset(malloc(size),0,size)
#define HAVE_MALLOC0
#endif /* HAVE_MALLOC0 */

#ifndef HAVE_STRNDUP
char *strndup(const char *s, size_t n);
#endif
#ifndef HAVE_STRNLEN
size_t strnlen(const char *s, size_t n);
#endif

#ifndef HAVE_WORDEXP
#ifndef HAVE_MEMPCPY
void *mempcpy(void *dest, const void *src, size_t n);
#endif
#ifndef HAVE_STPCPY
char *stpcpy(char *dest, const char *src);
#endif
#endif /* HAVE_WORDEXP */

char *return_malloced_getwd(void);

#ifndef HAVE_CLEARENV
int clearenv(void);
#endif

#ifndef HAVE_GET_CURRENT_DIR_NAME
char *get_current_dir_name(void);
#endif

#endif /* __UTILS_H */
