/*
 * sig_install_action.c
 *
 *  Created on: 21 Jan 2012
 *      Author: martin
 */

#include <signal.h>
#include "logUtils.h"

int sig_install_action(int signum, const struct sigaction *p_action) {
	// install action for specified signal
	// Returns: 0 on success or if signal disposition was SIG_IGN, otherwise -1 if
	// an error occurred

	struct sigaction old_sa;

	// if current disposition is SIG_IGN then keep existing action
	if( sigaction(signum, NULL, &old_sa) == -1 ) {
		logwarn(LOG_ERR, "signal %d: sigaction()", signum);
		return -1;
	} else if( old_sa.sa_handler == SIG_IGN ) {
		logmsg(LOG_INFO, "signal %d: set to 'ignore' - not installing handler", signum);
		return 0;
	}

	// install new action
	if( sigaction(signum, p_action, NULL) == -1 ) {
		logwarn(LOG_ERR, "signal %d: sigaction()", signum);
		return -1;
	}

	return 0;
}


