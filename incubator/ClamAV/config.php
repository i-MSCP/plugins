<?php
/**
 * i-MSCP ClamAV plugin
 * Copyright (C) 2013-2015 Laurent Declercq <l.declercq@nuxwin.com>
 * Copyright (C) 2013-2015 Rene Schuster <mail@reneschuster.de>
 * Copyright (C) 2013-2015 Sascha Bay <info@space2place.de>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

return array(
	/**
	 * Clamav-milter configuration options
	 * 
	 * For more information about the different configuration options please check 'man clamav-milter.conf'.
	 * 
	 * Warning: Don't change anything if you don't know what you are doing. Be aware that when changing the port of the
	 * Postfix smtpd_milter for ClamAV require to rerun the i-MSCP setup script.
	 */

	// Postfix smtpd_milter for ClamAV ( default: inet:localhost:32767 )
	//
	// Possible values:
	//  inet:localhost:32767 ( for connection through TCP )
	//  unix:/clamav/clamav-milter.ctl ( for connection through socket )
	'PostfixMilterSocket' => 'inet:localhost:32767',

	//
	//// The following configation parameters are added in the /etc/clamav/clamav-milter.conf configuration file
	//

	// ClamAV milter socket ( default: inet:32767@localhost )
	//
	// Possible values:
	//  inet:32767@localhost ( for connection through TCP )
	//  /var/spool/postfix/clamav/clamav-milter.ctl ( for connection through socket )
	'MilterSocket' => 'inet:32767@localhost',

	// Possible values: true, false
	'FixStaleSocket' => 'true',

	'User' => 'clamav',

	// Possible values: true, false
	'AllowSupplementaryGroups' => 'true',

	'ReadTimeout' => '120',

	// Possible values: true, false
	'Foreground' => 'false',

	'PidFile' => '/var/run/clamav/clamav-milter.pid',
	'ClamdSocket' => 'unix:/var/run/clamav/clamd.ctl',
	'OnClean' => 'Accept',

	// Action to be performed on infected messages ( default: Reject )
	// Possible values: Reject, Quarantine
	'OnInfected' => 'Reject',

	'OnFail' => 'Defer',

	// Possible values: Replace (or YES), NO
	'AddHeader' => 'Replace',

	// Possible values: true, false
	'LogSyslog' => 'true',

	// Look at http://linux.die.net/man/3/syslog => "facility"
	'LogFacility' => 'LOG_MAIL',

	// Possible values: true, false
	'LogVerbose' => 'false',

	// Possible values: Full, Basic, Off
	'LogInfected' => 'Basic',

	// Possible values: Off, On
	'LogClean' => 'Off',

	// Possible values: true, false
	'LogRotate' => 'true',
	
	// Messages larger than this value won't be scanned.
	'MaxFileSize' => '25M',

	// Possible values: 'true', 'false'
	'SupportMultipleRecipients' => 'false',

	'TemporaryDirectory' => '/tmp',
	'LogFile' => '/var/log/clamav/clamav-milter.log',

	// Possible values: true, false
	'LogTime' => 'true',

	// Possible values: true, false
	'LogFileUnlock' => 'false',

	'LogFileMaxSize' => '0M',
	'MilterSocketGroup' => 'clamav',
	'MilterSocketMode' => '666',

	// The string "%v", if present, will be replaced with the virus name. ( useful together with "OnInfected => Reject" )
	'RejectMsg' => 'Blocked by ClamAV - FOUND VIRUS: %v' 
);
