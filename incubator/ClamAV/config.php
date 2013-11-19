<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2013 by i-MSCP Team
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
 * @copyright   2010-2013 by i-MSCP Team
 * @author      Sascha Bay <info@space2place.de>
 * @contributor Rene Schuster <mail@reneschuster.de>
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
	
	'MilterSocket' => '/var/spool/postfix/clamav/clamav-milter.ctl',
	
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
	
	// Possible values: FALSE, TRUE
	'LogSyslog' => 'false',
	
	// Look at http://linux.die.net/man/3/syslog => "facility"
	'LogFacility' => 'LOG_MAIL',
	
	'LogVerbose' => 'false',
	
	// Possible values: Full, Basic, Off
	'LogInfected' => 'Off',
	
	'LogClean' => 'Off',
	
	// Messages larger than this value won't be scanned.
	'MaxFileSize' => '25M',
	
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
