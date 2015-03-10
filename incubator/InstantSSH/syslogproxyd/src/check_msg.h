/*
 * check_msg.h
 *
 *  Created on: 21 Jan 2012
 *      Author: martin
 */

#ifndef CHECK_MSG_H_
#define CHECK_MSG_H_

size_t validate_syslog_msg( char *msg, size_t len, size_t maxlen );
size_t insert_pri_timestamp_hostname( char *msg, size_t len, size_t maxlen);
size_t insert_timestamp_hostname(char *msg, size_t msg_len, size_t msg_maxlen);

#endif /* CHECK_MSG_H_ */
