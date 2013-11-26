#include <stdio.h>
#include <sys/types.h>
#include <pwd.h>
#include "passwdparser.h"

int main(int argc, char **argv) {
	struct passwd *pw1;
	
	
	
/*	pw1 = internal_getpwuid("passwdparsertester.test", 10);
	pw1 = internal_getpwuid("passwdparsertester.test", 20);*/
	pw1 = internal_getpwuid("passwdparsertester.test", 100);
	if (pw1) {
		printf("found user %s with shell %s and home %s\n",pw1->pw_name, pw1->pw_shell, pw1->pw_dir);
	} else {
		printf("found nothing\n");
	}
	return 0;
}

