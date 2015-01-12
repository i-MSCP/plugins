<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2014 by i-MSCP Team
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
 *
 * @category    iMSCP
 * @package     iMSCP_Plugin
 * @subpackage  ClamAV
 * @copyright   Sascha Bay <info@space2place.de>
 * @copyright   Rene Schuster <mail@reneschuster.de>
 * @author      Sascha Bay <info@space2place.de>
 * @author      Rene Schuster <mail@reneschuster.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

return array(
	/*
	 * Here you can adjust the clamav-milter configuration options.
	 * 
	 * For more information about the different configuration options
	 * please check 'man clamav-milter.conf' on the shell.
	 * 
	 * ATTENTION:
	 * Don't change anything, if you don't know what you are doing!
	 * 
	 */
	
	// This value will be added to the snmpd_milters in the main.cf (inet:localhost:32767 [TCP connection], unix:/clamav/clamav-milter.ctl [Unix socket connection])
	// Attention: If you sare using the unix socket the path is not equal with the MilterSocket value! The part "/var/spool/postfix" is not needed here
	'PostfixMilterSocket' => 'inet:localhost:32767',

	// The following values will be added to /etc/clamav/clamav-milter.conf
	
	// This value must match with the Postfix milter socket (inet:32767@localhost [TCP connection], /var/spool/postfix/clamav/clamav-milter.ctl[Unix socket connection])
	'MilterSocket' => 'inet:32767@localhost',

	'FixStaleSocket' => 'true',

	'User' => 'clamav',

	'AllowSupplementaryGroups' => 'true',

	'ReadTimeout' => '120',

	'Foreground' => 'false',

	'PidFile' => '/var/run/clamav/clamav-milter.pid',

	'ClamdSocket' => 'unix:/var/run/clamav/clamd.ctl',

	'OnClean' => 'Accept',

	// Action to be performed on infected messages. Possible values: Reject, Quarantine
	'OnInfected' => 'Reject',

	'OnFail' => 'Defer',

	// Possible values: Replace (or YES), NO
	'AddHeader' => 'Replace',

	// Possible values: TRUE, FALSE
	'LogSyslog' => 'true',

	// Look at http://linux.die.net/man/3/syslog => "facility"
	'LogFacility' => 'LOG_MAIL',

	'LogVerbose' => 'false',

	// Possible values: Full, Basic, Off
	'LogInfected' => 'Basic',

	'LogClean' => 'Off',

	'LogRotate' => 'true',
	
	// Messages larger than this value won't be scanned.
	'MaxFileSize' => '25M',
	
	'SupportMultipleRecipients' => 'false',

	'TemporaryDirectory' => '/tmp',

	'LogFile' => '/var/log/clamav/clamav-milter.log',

	'LogTime' => 'true',

	'LogFileUnlock' => 'false',

	'LogFileMaxSize' => '0M',

	'MilterSocketGroup' => 'clamav',

	'MilterSocketMode' => '666',

	// The string "%v", if present, will be replaced with the virus name. (useful together with "OnInfected => Reject")
	'RejectMsg' => 'Blocked by ClamAV - FOUND VIRUS: %v' 
);
