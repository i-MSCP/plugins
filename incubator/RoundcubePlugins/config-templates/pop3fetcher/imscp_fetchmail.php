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
 * @subpackage  RoundcubePlugins
 * @copyright   Sascha Bay <info@space2place.de>
 * @copyright   Rene Schuster <mail@reneschuster.de>
 * @author      Sascha Bay <info@space2place.de>
 * @author      Rene Schuster <mail@reneschuster.de>
 * @link        http://www.i-mscp.net i-MSCP Home Site
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GPL v2
 */

if(isset($_SERVER['REMOTE_ADDR'])) die("Access denied");
 
class fetchmail_cronjob
{
	/**
	 * @var string $debug activates the debug mode
	*/
	private $debug = false;
	
	/**
	 * @var string $debug_console activates the debug mode to console
	*/
	private $debug_console = false;
	
	private $max_forwarded_message_size = 4194304;
	
	/**
	 * @var string $docRoot Roundcubes document root
	*/
	private $docRoot;

	/**
	 * @var string $rcIniset Roundcubes iniset script
	*/
	private $rcIniset = 'program/include/iniset.php';

	/**
	 * Init pop3fetcher synchronization cronjob
	 *
	 * @param boolean	$init	Initialization
	*/
	public function __construct($init = true)
	{
		if ($init === true) {
			$this->init();
		}
	}

	/**
	 * Init pop3fetcher synchronization cronjob
	 *
	 * @return	void
	*/
	public function init()
	{
		$this->detectDocumentRoot();
		$this->includeRcIniset();
	}

	/**
	 *
	 * @return	void
	*/
	private function detectDocumentRoot()
	{
		$dir = dirname(__FILE__);
		$this->docRoot = str_replace('plugins/pop3fetcher/imscp', null, $dir);
		define('INSTALL_PATH', $this->docRoot);
	}

	/**
	 *
	 * @return	void
	*/
	private  function includeRcIniset()
	{
		if (file_exists($this->docRoot . $this->rcIniset)) {
			chdir($this->docRoot);
			require_once $this->docRoot . '/program/include/iniset.php';
		} else {
			die("Can't detect file path correctly! I got this as Roundcubes document root: " . $this->docRoot . "\n");
		}
	}

	/**
	 * Synchronize all available pop3fetcher accounts
	 *
	 * @return	void
	*/
	public function synchronize()
	{
		define('DISPLAY_XPM4_ERRORS', false); // display XPM4 errors
      	require_once $this->docRoot . '/plugins/pop3fetcher/XPM4/POP35.php';
      	require_once $this->docRoot . '/plugins/pop3fetcher/XPM4/FUNC5.php';
		
		$this->rcmail = rcmail::get_instance();
		$query = "
			SELECT
				`t1`.*, `t2`.`username`, `t2`.`mail_host`,
				`t3`.`mail_pass`
			FROM
				`{IMSCP-DATABASE}_roundcube`.`pop3fetcher_accounts` AS `t1`
			LEFT JOIN
				`{IMSCP-DATABASE}_roundcube`.`users` AS `t2` ON(`t2`.`user_id` = `t1`.`user_id`)
			LEFT JOIN
				`{IMSCP-DATABASE}`.`mail_users` AS `t3` ON(`t3`.`mail_addr` = `t2`.`username`)
			WHERE
				(`t3`.`mail_type` LIKE '%normal_mail%' OR `t3`.`mail_type` LIKE '%alias_mail%' OR `t3`.`mail_type` LIKE '%subdom_mail%')
				
			ORDER BY
				`t1`.`user_id` ASC
		";
		$ret = $this->rcmail->db->query($query);
		
		$accounts = array();
		
		while($account = $this->rcmail->db->fetch_assoc($ret))
		$accounts[] = $account;
		$temparr = array();
		
		foreach($accounts as $key => $val){
			$temparr[$key] = $val['pop3fetcher_email'];
			
			if($this->debug) write_log("pop3fetcher_cron.txt", "INTERCEPT: task mail, checkunread for " . $val['pop3fetcher_email']);
			
			$last_uidl = $val['last_uidl'];
			$c = POP35::connect($val['pop3fetcher_serveraddress'], $val['pop3fetcher_username'], $val['pop3fetcher_password'], intval($val['pop3fetcher_serverport']), $val['pop3fetcher_ssl'], 100);
			
			if($c){
				$s = POP35::pStat($c, false);
				if(is_array($s)) {
					list($Count, $b) = each($s);

					if($this->debug) write_log("pop3fetcher_cron.txt", "INTERCEPT: task mail, found new messages: $Count, $b");
					
					$msglist = POP35::puidl($c);
					if(is_array($msglist)) {
						$Count=count($msglist);
					} else {
						$Count=0;
					}
					
					if(sizeof($msglist) > 0) {
						$i = 0;					
						for ($j = 1; $j <= sizeof($msglist); $j++) {
							if($this->debug) write_log("pop3fetcher_cron.txt", "INTERCEPT: task mail, MSG UIDL: " . $msglist["$j"]);
							if($this->debug) write_log("pop3fetcher_cron.txt", "INTERCEPT: task mail, LAST UIDL: " . $last_uidl);
							if ($msglist["$j"] == $last_uidl) {
								$i = $j;
								break;
							}
						}
						
						if ($Count < $i) {
							POP35::disconnect($c);
						}
						if ($Count == 0) {
							POP35::disconnect($c);
						} else {
							$newmsgcount = $Count - $i;
							if($this->debug && $this->debug_console) echo "Login OK: Inbox contains [" . $newmsgcount . "] messages\n";
						}
						
						if($newmsgcount > 0) {
							// Start to login to the imap server
							$this->rcmail->kill_session();
							
							if($this->debug && $this->debug_console) echo "Start Login IMAP Server (" . $val['mail_host']. ") with account: " . $val['username'] . "\n";
							if($this->debug) write_log("pop3fetcher_cron.txt", "Start Login IMAP Server (" . $val['mail_host']. ") with account: " . $val['username']);
							
							if ($this->rcmail->login($val['username'], $val['mail_pass'], $auth['mail_host'], false)) {
								$this->rcmail->get_storage();
								$this->rcmail->storage_connect();
								
								$max_messages_downloaded_x_session=10;
								$max_bytes_downloaded_x_session=1000000;
								$cur_bytes_downloaded=0;
								$k=1;
								
								for (; $i < $Count; $i++) {
									if($k<=$max_messages_downloaded_x_session){
										$cur_msg_index=$i+1;
										$l = POP35::pList($c, $cur_msg_index);
										$last_uidl = $msglist["$cur_msg_index"];
										
										if($l["$cur_msg_index"] <= $this->max_forwarded_message_size || $this->max_forwarded_message_size == 0){
											$cur_bytes_downloaded=$cur_bytes_downloaded+$l["$i"];
											
											if($this->debug && $this->debug_console) echo "INTERCEPT: task mail, k=" . $k . " Downloading a message with UIDL " . $msglist["$cur_msg_index"].", SIZE: " . $l["$cur_msg_index"] . "\n";
											if($this->debug) write_log("pop3fetcher_cron.txt", "INTERCEPT: task mail, k=" . $k . " Downloading a message with UIDL " . $msglist["$cur_msg_index"].", SIZE: " . $l["$cur_msg_index"]);
											
											$Message = POP35::pRetr($c, $cur_msg_index) or die(print_r($_RESULT));
										
											if($this->debug && $this->debug_console) echo "INTERCEPT: task mail, k=" . $k . "  Downloaded a message with UIDL " . $msglist["$cur_msg_index"] . "\n";
											if($this->debug) write_log("pop3fetcher_cron.txt", "INTERCEPT: task mail, k=" . $k . "  Downloaded a message with UIDL " . $msglist["$cur_msg_index"]);
											
											$message_id = $this->rcmail->storage->save_message($val['default_folder'], $Message);
											
											if($this->debug && $this->debug_console) echo "INTERCEPT: task mail, k=" . $k . " Stored a message with UIDL ".$msglist["$cur_msg_index"] . "\n";
											if($this->debug) write_log("pop3fetcher_cron.txt", "INTERCEPT: task mail, k=" . $k . " Stored a message with UIDL ".$msglist["$cur_msg_index"]);
											
											$this->rcmail->storage->unset_flag($message_id, "SEEN", $val['default_folder']);
											if(!($val['pop3fetcher_leaveacopyonserver'])){
												if(POP35::pDele($c, $cur_msg_index)){
													if($this->debug && $this->debug_console) echo "INTERCEPT: DELETING MESSAGE " . $cur_msg_index . " " . $last_uidl . "\n";
													if($this->debug) write_log("pop3fetcher_cron.txt", "INTERCEPT: DELETING MESSAGE " . $cur_msg_index . " " . $last_uidl);
												} else {
													if($this->debug && $this->debug_console) echo "INTERCEPT: ERROR: CANNOT DELETE " .$last_uidl . "\n";
													if($this->debug) write_log("pop3fetcher_cron.txt", "INTERCEPT: ERROR: CANNOT DELETE " .$last_uidl);
												}
											} else {
												if($this->debug && $this->debug_console) echo "INTERCEPT: leaveacopyonserver IS SET\n";
												if($this->debug) write_log("pop3fetcher_cron.txt", "INTERCEPT: leaveacopyonserver IS SET");
											}
											
											if($cur_bytes_downloaded>$max_bytes_downloaded_x_session) $i=$Count+1;
										} else {
											if($this->debug && $this->debug_console) echo "INTERCEPT: Skipped message " .$last_uidl . "\n";
											if($this->debug) write_log("pop3fetcher_cron.txt", "INTERCEPT: Skipped message " .$last_uidl);
										}
										
										if($this->debug) write_log("pop3fetcher_cron.txt", "INTERCEPT: trying to update DB: " . $last_uidl . " " . $val['pop3fetcher_id']);
										
										$query = "UPDATE {IMSCP-DATABASE}_roundcube.pop3fetcher_accounts SET last_uidl = ? WHERE pop3fetcher_id = ?";
										$ret = $this->rcmail->db->query($query, $last_uidl, $val['pop3fetcher_id']);
										
										if($this->debug) write_log("pop3fetcher_cron.txt", "INTERCEPT: updated DB: " . $last_uidl . " " . $val['pop3fetcher_id']);
										$k=$k+1;
									} else {
										$i=$Count+1;
									}
								}
								
								$this->rcmail->logout_actions();
								$this->rcmail->kill_session();
							} else {
								if($this->debug && $this->debug_console) echo "ERROR: Can't login to IMAP Server (" . $val['mail_host']. ") with account: " . $val['username'] . "\n";
								if($this->debug) write_log("pop3fetcher_cron.txt", "ERROR: Can't login to IMAP Server (" . $val['mail_host']. ") with account: " . $val['username']);
							}
						} else {
							if($this->debug && $this->debug_console) echo "Nothing to do. Inbox contains [" . $newmsgcount . "] messages\n";
							if($this->debug) write_log("pop3fetcher_cron.txt", "Nothing to do. Inbox contains [" . $newmsgcount . "] messages");
						}
					}
					POP35::disconnect($c);
				} else {
					if($this->debug) write_log("pop3fetcher_cron.txt", "INTERCEPT: task mail, ERROR ON is_array FOR " . $val['pop3fetcher_email']);
				}
			} else {
				if($this->debug) write_log("pop3fetcher_cron.txt", "INTERCEPT: task mail, POP35::connectfailed for " . $val['pop3fetcher_email']);
			}
		}
	}
}

$cronjob = new fetchmail_cronjob();
$cronjob->synchronize();
