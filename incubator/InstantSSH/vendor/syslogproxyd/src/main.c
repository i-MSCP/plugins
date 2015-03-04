/*
 * main.c
 *
 *  Created on: 21 Jan 2012
 *      Author: martin
 *  Modified by Laurent Declercq <l.declercq@nuxwin.com> for i-MSCP
 */

//===================
// macro definitions
//===================
#define DEFAULT_DEST 		"/dev/log"
#define DEFAULT_PIDFILE		"/var/run/syslogproxyd.pid"
#define DEFAULT_LOG_LEVEL	LOG_NOTICE
#define MAX_MSG_LEN			1024
#define MAX_SOCKETS			100
#define STRING_TABLE_SIZE	8192

#define USAGE_STR "\n\
%s [options] <log_socket_path> [<log_socket_path>]...\n\
\n\
options:\n\
 -d               --foreground                remain foreground and log to stderr\n\
 -V <0-9>         --verbosity <0-9>           set reporting level (default %d)\n\
 -?               --help                      this message\n\
 -v[v[..]]        --verbose                   increase verbosity\n\
 -q[q[..]]        --quiet                     reduce verbosity\n\
 -n               --no_validation             switch off RFC 3164 message validation\n\
 -u <username>    --user <username>           run as different user (requires root)\n\
 -g <groupname>   --group <groupname>         run as different group (requires root)\n\
 -p <file>        --pidfile <file>            alternate location to store PID (default %s)\n\
 -s <dest_socket> --dest_socket <dest_socket> destination socket (default %s)\n\
\n\
", appname, verbosity, DEFAULT_PIDFILE, DEFAULT_DEST

//=========
// headers
//=========

#include <getopt.h>
#include <stdio.h>
#include <unistd.h>
#include <stdbool.h>
#include <err.h>
#include <errno.h>
#include <string.h>
#include <stdlib.h>
#include <libgen.h>
#include <grp.h>
#include <pwd.h>
#include <poll.h>
#include <limits.h>
#include <signal.h>
#include <sys/wait.h>
#include <sys/un.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <sys/stat.h>

#include "logUtils.h"
#include "daemonUtils.h"
#include "check_msg.h"
#include "stringTable.h"
#include "sig_install_action.h"

//=============================
// global variable definitions
//=============================
volatile sig_atomic_t sig_raised;

//=============================
// local function declarations
//=============================
static void signal_handler(int);

//===============
// main function
//===============
int main( int argc, char *argv[] ) {

	//------------------------------
	// global variable declarations
	//------------------------------
	extern volatile sig_atomic_t sig_raised;

	//----------------------------
	// local variable definitions
	//----------------------------
	int verbosity;
	int retval;
	int client_sock_fd;
	struct pollfd server_sock_pfd[MAX_SOCKETS];
	int num_sockets;
	int nfds;
	int i;
	int argc_count;
	mode_t prev_umask;
	bool remain_foreground;
	bool msg_validation;
	bool uid_specified;
	bool gid_specified;
	bool pidfile_created;
	char msg[MAX_MSG_LEN + 1];
	char ch;
	const char *appname;
	const char *pidfile;
	uid_t uid;
	gid_t gid;
	pid_t child_pid;
	int child_status;
	stringtable_t st;
	FILE *pidfile_stream;
	char **src_path;
	struct passwd *password_ptr;
	struct group *group_ptr;
	char *dest_path = DEFAULT_DEST;
	ssize_t msg_len, num_bytes;
	struct sockaddr_un src_addr, dest_addr;
	struct sigaction action;
	struct stat metadata;
	static struct option longopts[] = {
		{ "foreground",		no_argument,		NULL, 'd' },
		{ "help",			no_argument,		NULL, '?' },
		{ "verbosity",		required_argument,	NULL, 'V' },
		{ "verbose",		no_argument,		NULL, 'v' },
		{ "quiet",			no_argument,		NULL, 'q' },
		{ "no_validation",	no_argument,		NULL, 'n' },
		{ "user",			required_argument,	NULL, 'u' },
		{ "group",			required_argument,	NULL, 'g' },
		{ "pidfile",		required_argument,	NULL, 'p' },
		{ "dest_socket",	required_argument,	NULL, 's' },
		{ NULL, 0, NULL, 0 }
	};

	//------------------------
	// set initial conditions
	//------------------------
	sig_raised = -1;
	verbosity = DEFAULT_LOG_LEVEL;
	retval = EXIT_SUCCESS;
	remain_foreground = false;
	msg_validation = true;
	appname = basename(argv[0]);
	uid_specified = false;
	gid_specified = false;
	uid = getuid();
	gid = getgid();
	num_sockets = 0;
	pidfile = DEFAULT_PIDFILE;
	pidfile_created = false;
	client_sock_fd = 0;
	for(i=0; i<MAX_SOCKETS; i++)
		server_sock_pfd[i].fd = 0;

	// log to stderr during start-up
	log_to_stderr(true);
	log_to_syslog(false);
	log_set_threshold(verbosity);

	// allocate storage for socket paths
	if( (src_path = stringtable_create(&st, MAX_SOCKETS, STRING_TABLE_SIZE)) == NULL ) {
		logwarnx(LOG_ERR, "Can't create string table with %z indexes, %z bytes", MAX_SOCKETS, STRING_TABLE_SIZE);
		goto exit_err;
	}

	//----------------------------
	// parse command line options
	//----------------------------
	while ( (ch = getopt_long(argc, argv, "a:dDvqn?V:u:g:p:", longopts, NULL)) != -1 ) {
		switch (ch) {
			case 's':
				dest_path = optarg;
				break;
			case 'd':
			case 'D': // retained for backward compatibility with V0.1
				remain_foreground = true;
				break;
			case 'V':
				errno = 0;
				verbosity = strtol(optarg, NULL, 0);
				if ( errno != 0 ) {
					logmsg(LOG_ERR, "verbosity %s: %s", strerror(errno), optarg);
					goto exit_err;
				} else
					log_set_threshold(verbosity);
				break;
			case 'v':
				verbosity++;
				log_set_threshold(verbosity);
				break;
			case 'q':
				verbosity--;
				log_set_threshold(verbosity);
				break;
			case 'n':
				msg_validation = false;
				break;
			case 'u':
				uid_specified = true;
				if( (password_ptr = getpwnam(optarg)) == NULL ) {
					logmsg(LOG_ERR, "user not found: %s", optarg);
					goto exit_err;
				} else
					uid = password_ptr->pw_uid;
				break;
			case 'g':
				gid_specified = true;
				if( (group_ptr = getgrnam(optarg)) == NULL ) {
					logmsg(LOG_ERR, "group not found: %s", optarg);
					goto exit_err;
				} else
					gid = group_ptr->gr_gid;
				break;
			case 'p':
				pidfile = optarg;
				break;
			case '?':
			default:
				fprintf(stderr, USAGE_STR);
				retval = EXIT_FAILURE;
				goto tidy_up;
				break;
		}
	}

	argc -= optind;
	argv += optind;

	// remaining arguments give log socket paths
	if( argc > 0 ) {
		argc_count = 0;
		LOOP: while(argc_count < argc) {
			for( i = 0; i < num_sockets - 1; i++ ) { // ignore duplicate paths
				if( strcmp(src_path[i], argv[argc_count]) == 0 ) {
					argc_count++;
					goto LOOP;
				}
			}

			if( (num_sockets = stringtable_add(&st, argv[argc_count])) == -1 ) {
				logwarnx(LOG_ERR, "string table full: can't add socket %s", argv[argc_count]);
				goto exit_err;
			}

			argc_count++;
		}
	} else {
		logmsg(LOG_ERR, "missing arguments");
		fprintf(stderr, USAGE_STR);
		retval = EXIT_FAILURE;
		goto tidy_up;
	}

	// default to using the same GID as the UID
	if( uid_specified && !gid_specified )
		gid = uid;

	//-----------------
	// create PID file
	//-----------------
	logmsg(LOG_DEBUG, "creating PID file %s", pidfile);
	if( stat(pidfile, &metadata) == 0 ) {
		logmsg(LOG_ERR, "error: PID file %s already exists - server may already be running", pidfile);
		return EXIT_FAILURE;	// don't goto exit_err as this would delete the existing PID file
	} else {
		if( (pidfile_stream = fopen(pidfile, "w")) == NULL ) {
			if( errno == EPERM )
				logmsg(LOG_WARNING, "warning: insufficient permission to create PID file %s - continuing without a PID file");
			else {
				logwarn(LOG_ERR, "error: can't create PID file %s: fopen()", pidfile);
				return EXIT_FAILURE;
			}
		} else
			pidfile_created = true;
	}

	//------------------------------------------------------
	// create input socket(s) - unix domain datagram server
	//------------------------------------------------------

	// modify permissions mask to create socket pseudo-files as rw-rw-rw-
	prev_umask = umask(0111);

	for( i=0; i<num_sockets; i++ ) {
		logmsg(LOG_INFO, "input socket %s", src_path[i]);

		// delete stale path (if it exists and is a socket)
		if( stat(src_path[i], &metadata) == 0 ) {
			if( S_ISSOCK(metadata.st_mode) ) {
				if( unlink(src_path[i]) ) {
					logwarn(LOG_ERR, "unlink()");
					goto exit_err;
				}
			} else {
				logmsg(LOG_ERR, "error: %s already exists as something not a socket", src_path);
				goto exit_err;
			}
		}

		// create unassigned socket
		if( (server_sock_pfd[i].fd = socket(AF_LOCAL, SOCK_DGRAM, 0)) == -1 ) {
			logwarn(LOG_ERR, "socket()");
			goto exit_err;
		} else
			server_sock_pfd[i].events = POLLIN;

		// copy socket path into address structure
		bzero(&src_addr, sizeof(src_addr));
		src_addr.sun_family = AF_LOCAL;
		strncpy(src_addr.sun_path, src_path[i], sizeof(src_addr.sun_path) - 1);

		// bind socket to address
		if( bind(server_sock_pfd[i].fd, (struct sockaddr *) &src_addr, sizeof(src_addr)) == -1 ) {
			logwarn(LOG_ERR, "bind()");
			goto exit_err;
		}

	}	// next server socket

	// restore original permissions mask
	umask(prev_umask);

	//----------------------------------------------------
	// create output socket - unix domain datagram client
	//----------------------------------------------------
	logmsg(LOG_DEBUG, "output socket %s", dest_path);
	if( (client_sock_fd = socket(AF_LOCAL, SOCK_DGRAM, 0)) == -1 ) {
		logwarn(LOG_ERR, "socket()");
		goto exit_err;
	}

	// no need to bind to a source address because we are not expecting an answer

	// validate destination path
	if( stat(dest_path, &metadata) || !S_ISSOCK(metadata.st_mode) ) {
		logmsg(LOG_ERR, "error: destination path %s was not found or is not a socket");
		goto exit_err;
	}

	// set up destination address (socket path)
	bzero(&dest_addr, sizeof(dest_addr));
	dest_addr.sun_family = AF_LOCAL;
	strncpy(dest_addr.sun_path, dest_path, sizeof(dest_addr.sun_path) - 1);
	logmsg(LOG_INFO, "forwarding messages to %s", dest_path);


	//===========================
	// start up actions complete
	//===========================
	if( !remain_foreground ) {
		// detach process and close standard IO streams
		logmsg(LOG_INFO, "starting server - detaching process");
		if( daemon(true, false) ) {
			logwarn(LOG_ERR, "daemon()");
			goto exit_err;
		} else {
			// begin logging to syslog
			// log to stderr during start-up
			log_to_stderr(false);
			log_to_syslog(true);
			logmsg(LOG_DEBUG, "daemon alive");
		}
	}

	// fork child

	logmsg(LOG_DEBUG, "forking child");
	if( (child_pid = fork()) == -1 ) {
		logwarn(LOG_ERR, "fork()");
		goto exit_err;
	} else {
		if( child_pid != 0 ) {

			//================
			// PARENT PROCESS
			//================

			// write PID to pidfile
			if( fprintf(pidfile_stream, "%d\n", getpid()) < 0 ) {
				logwarn(LOG_ERR, "error: writing PID fprintf()");
				goto exit_err;
			} else
				fclose(pidfile_stream);

			// install signal handler for SIGINT, SIGHUP, SIGCHLD and SIGTERM
			action.sa_handler = signal_handler;
			sigemptyset( &action.sa_mask);			// don't block any signals
			action.sa_flags = SA_NOCLDSTOP;			// override default SA_RESTART behaviour and don't signal child stop/resumes

			logmsg(LOG_DEBUG, "installing signal handlers");
			if(	sig_install_action(SIGINT, &action) ||
					sig_install_action(SIGCHLD, &action) ||
					sig_install_action(SIGHUP, &action) ||
					sig_install_action(SIGTERM, &action) )
				goto exit_err;

			// parent main loop
			do {
				logmsg(LOG_DEBUG, "server running");

				// wait for a signal
				pause();
				logmsg(LOG_INFO, "caught signal: %s", strsignal(sig_raised));

				// handle signal
				switch( sig_raised ) {
				case SIGCHLD:
					// child changed state
					do {
						logmsg(LOG_INFO, "waiting for child process to finish");
						if( waitpid(child_pid, &child_status, 0) == -1 ) {
							logwarn(LOG_ERR, "waitpid()");
							goto exit_err;
						}
					} while ( !WIFEXITED(child_status) && !WIFSIGNALED(child_status) );
					break;
				case SIGINT:
				case SIGHUP:
				case SIGTERM:
					logmsg(LOG_INFO, "sending SIGTERM to child (PID %d)", child_pid);
					kill(child_pid, SIGTERM);
					sig_raised = -1;
					break;
				default:
					logmsg(LOG_ERR, "ignoring unrecognised signal");
					sig_raised = -1;
					break;
				}
			} while( sig_raised == -1 );

			logmsg(LOG_INFO, "server terminating");
			goto normal_exit;

			// end of parent process
		} else {

			//===============
			// CHILD PROCESS
			//===============

			// drop privileges
			logmsg(LOG_INFO, "child dropping privileges to UID %d, GID %d", uid, gid);
			if( drop_privileges(uid, gid) )
				return EXIT_FAILURE;

			//=================
			// child main loop
			//=================

			// relay messages received on input sockets
			while( (nfds = poll(server_sock_pfd, num_sockets, -1)) != -1 ) {

				// find readable input socket(s)
				for( i=0; i<num_sockets; i++) {
					if( server_sock_pfd[i].revents & (POLLERR | POLLHUP | POLLNVAL) ) {
						logwarn(LOG_ERR, "child: poll() %s", src_path[i]);
						return EXIT_FAILURE;
					} else if( server_sock_pfd[i].revents & POLLIN ) {

						// receive and relay message
						logmsg(LOG_INFO, "child: receiving message on server socket %s", src_path[i]);
						if( (msg_len = recv(server_sock_pfd[i].fd, msg, MAX_MSG_LEN, 0)) == -1 ) {
							if( errno == EINTR ) {
								logmsg(LOG_ERR, "child: caught signal: %s", strsignal(sig_raised));
								goto tidy_up;
							} else {
								logwarn(LOG_ERR, "child: recv()");
								goto exit_err;
							}
						} else
							msg[msg_len] = '\0';

						logmsg(LOG_DEBUG, "child: original message (%zd bytes) \"%s\"", msg_len, msg);

						// validate message
						if( msg_validation ) {
							msg_len = validate_syslog_msg(msg, msg_len, MAX_MSG_LEN);
							msg[msg_len] = '\0';

							logmsg(LOG_DEBUG, "child: post-validation message (%zd bytes) \"%s\"", msg_len, msg);
						}

						// relay message
						if( msg_len > 0 ) {
							logmsg(LOG_DEBUG, "child: relaying message (%zd bytes)", msg_len);
							num_bytes = sendto(client_sock_fd, msg, msg_len, 0, (struct sockaddr *)&dest_addr, sizeof(dest_addr));
							if( num_bytes == -1 && errno == EINTR ) {
								logmsg(LOG_ERR, "child: caught signal: %s", strsignal(sig_raised));
								goto tidy_up;
							} else {
								if( num_bytes != msg_len )
									logwarn(LOG_ERR, "child: relaying message: sendto()");
								else
									logmsg(LOG_DEBUG, "child: OK, sent %zd bytes", num_bytes);
							}
						} else
							logmsg(LOG_ERR, "child: bad message length %zd: not relaying", msg_len);

					}
				}
			}
			logwarn(LOG_ERR, "child: poll()");
			return EXIT_FAILURE;

		} // end of child process
	}

	//------
	// done
	//------
	normal_exit:
	goto tidy_up;

	exit_err:
	logmsg(LOG_ERR, "server error");
	retval = EXIT_FAILURE;

	tidy_up:

	// close open descriptors
	for( i=0; i<num_sockets; i++)
		if( server_sock_pfd[i].fd && close(server_sock_pfd[i].fd) )
			logwarn(LOG_ERR, "descriptor %d (server socket %s): close()", server_sock_pfd[i].fd, src_path[i]);
	if( client_sock_fd && close(client_sock_fd) )
		logwarn(LOG_ERR, "descriptor %d (client socket %s): close()", client_sock_fd, dest_path);

	// delete server socket pseudo-files
	for( i=0; i<num_sockets; i++) {
		logmsg(LOG_INFO, "deleting input socket pseudo-file %s", src_path[i]);
		if( stat(src_path[i], &metadata) )
			logwarn(LOG_ERR, "stat()");
		else {
			if( S_ISSOCK(metadata.st_mode) ) {
				if( unlink(src_path[i]) )
					logwarn(LOG_ERR, "unlink()");
			} else {
				logmsg(LOG_ERR, "%s is not a socket");
			}
		}
	}

	// delete PID file
	if( pidfile_created ) {
		logmsg(LOG_INFO, "deleting PID file");
		if( unlink(pidfile) && errno != ENOENT )
			logwarn(LOG_ERR, "unlink(): %s", pidfile);
	}

	// exit
	logmsg(LOG_INFO, "server exited");
	return retval;
}

//============================
// local function definitions
//============================
static void signal_handler( int sig ) {
	// set global flag to show that a signal has occured

	// declare usage of global variable
	extern volatile sig_atomic_t sig_raised;

	// signal handlers should be as simple as possible
	sig_raised = sig;
}
