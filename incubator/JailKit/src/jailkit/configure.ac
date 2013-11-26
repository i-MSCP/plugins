# Jailkit
# configure.ac - the autoconf file
#
#Copyright (C) 2003, 2004, 2005, 2006, 2007, 2008, 2009, 2010, 2011, 2013 Olivier Sessink
#All rights reserved.
#
#Redistribution and use in source and binary forms, with or without
#modification, are permitted provided that the following conditions
#are met:
#  * Redistributions of source code must retain the above copyright
#    notice, this list of conditions and the following disclaimer.
#  * Redistributions in binary form must reproduce the above
#    copyright notice, this list of conditions and the following
#    disclaimer in the documentation and/or other materials provided
#    with the distribution.
#  * The names of its contributors may not be used to endorse or
#    promote products derived from this software without specific
#    prior written permission.
#
#THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
#"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
#LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
#FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
#COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
#INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
#BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
#LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
#CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
#LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
#ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
#POSSIBILITY OF SUCH DAMAGE.
#
#

AC_INIT([jailkit],[2.16],[https://savannah.nongnu.org/bugs/?group=jailkit])

AC_SUBST([PACKAGE], AC_PACKAGE_NAME)
AC_DEFINE_UNQUOTED([PACKAGE], "AC_PACKAGE_NAME", [The package name.])
AC_SUBST([VERSION], AC_PACKAGE_VERSION)
AC_DEFINE_UNQUOTED([VERSION], "AC_PACKAGE_VERSION", [The package version.])

AC_PREFIX_DEFAULT([/usr])
dnl does this make sense?
if test "$prefix" = "NONE"; then
	sysconfdir="/etc"
	AC_MSG_NOTICE([config files will be in /etc/jailkit/])
fi
AC_CONFIG_SRCDIR([src/jk_socketd.c])
AC_CONFIG_HEADER([src/config.h])

AC_GNU_SOURCE

AC_PROG_CC
AC_ISC_POSIX
AC_C_INLINE
AC_PROG_INSTALL

if test "$CC" = "gcc" ; then
	CFLAGS="$CFLAGS -Wall -pipe"
fi

dnl ************************
dnl Check for standard headers
dnl ************************

AC_HEADER_STDC

dnl ************************
dnl Checks for header files.
dnl ************************

AC_CHECK_HEADERS([errno.h stdio.h ctype.h getopt.h math.h time.h sys/socket.h pthread.h pwd.h wordexp.h liberty.h strings.h])

AC_CHECK_FUNCS([strnlen wordexp clearenv get_current_dir_name])

AC_CHECK_FUNCS(
	[strndup],,
	AC_CHECK_LIB([iberty], [strndup])
)

AC_CHECK_FUNCS(
	[mempcpy],,
	AC_CHECK_LIB([iberty], [mempcpy])
)

AC_CHECK_FUNCS(
	[stpcpy],,
	AC_CHECK_LIB([iberty], [stpcpy])
)

# Check if -pthread is available
# if not try -lpthread
OLDCFLAGS=$CFLAGS
OLDLDFLAGS=$LDFLAGS
CFLAGS="$CFLAGS -pthread"
LDFLAGS="$LDFLAGS -pthread"
AC_MSG_CHECKING([for pthread support])
AC_LINK_IFELSE(
 [AC_LANG_PROGRAM(
  [#include <pthread.h>
   void *g(void *d) { return NULL; }],
  [pthread_t t; pthread_create(&t,NULL,g,NULL);])],
  [AC_MSG_RESULT([use -pthread])]
	,
	CFLAGS=$OLDCFLAGS
	LDFLAGS=$OLDLDFLAGS
	[AC_CHECK_LIB(
	[pthread],
	[pthread_create],,
	AC_CHECK_LIB(
		[c_r],
		[pthread_create],,
		AC_MSG_ERROR([libpthread not found])
	  )
  )]
)

# solaris has -lsocket or -lxnet for various socket functions
AC_CHECK_FUNCS(
	[recvfrom],,
	AC_CHECK_LIB([xnet], [recvfrom])
)
AC_CHECK_FUNCS(
	[send],,
	AC_CHECK_LIB([socket], [send])
)

# solaris needs -lrt or -lposix4 for nanosleep()
AC_CHECK_FUNCS(
	[nanosleep],,
	AC_CHECK_LIB(
		[posix4],
		[nanosleep],,
		AC_CHECK_LIB(
			[rt],
			[nanosleep],,
			AC_MSG_ERROR(nanosleep not found)
		)
	)
)

# FreeBSD has a gnugetopt library for this
# Solaris might have libiberty
AC_CHECK_FUNCS(
	[getopt_long],,
	AC_CHECK_LIB(
		[gnugetopt],
		[getopt_long],,
		AC_CHECK_LIB(
			[iberty],
			[getopt_long],,
			AC_MSG_ERROR(getopt_long not found)
		)
	)
)
AC_CHECK_HEADERS([sys/capability.h])

AC_CHECK_FUNCS(
	[cap_get_proc],,
	AC_CHECK_LIB([cap], [cap_get_proc],
		AC_DEFINE(HAVE_CAP_GET_PROC)
		LDFLAGS="$LDFLAGS -lcap",
	)
)

dnl you should maybe add AC_ARG_VAR for every AC_PATH_PROG to allow users
dnl to specify the binary path if it is not in PATH
AC_ARG_VAR(
	[PYTHONINTERPRETER],
	[The `python' binary with path. Use it to define or override the location of `python'.]
)
AC_PATH_PROG([PYTHONINTERPRETER], [python], [no])
if test "x$PYTHONINTERPRETER" = "xno" ; then
	AC_MSG_ERROR([python not found please install python])
fi
AC_SUBST(PYTHONINTERPRETER)
AC_MSG_CHECKING([for up to date python])
echo -n "checking for up to date python... "
if $PYTHONINTERPRETER -c 'i=1;i+=1' >/dev/null 2>&1 ; then
	AC_MSG_RESULT([ok])
else
	AC_MSG_RESULT([failed])
	AC_MSG_ERROR([your python version is too old, please install python 2 or newer])
fi

AC_ARG_VAR(
	[PROCMAILPATH],
	[The `procmail' binary with path. Use it to define or override the location of `procmail'.]
)
AC_PATH_PROG([PROCMAILPATH], [procmail], [no])
if test "x$PROCMAILPATH" != "xno" ; then
	AC_DEFINE_UNQUOTED([PROCMAILPATH], "$PROCMAILPATH", [The procmail path.])
	AC_SUBST([HAVEPROCMAIL_TRUE], [])
else
	AC_SUBST([HAVEPROCMAIL_TRUE], [#])
fi

AC_CONFIG_FILES([Makefile src/Makefile py/Makefile man/Makefile])
AC_OUTPUT
