<?php

/*
 * rcguard plugin
 * Version HEAD
 *
 * Copyright (c) 2010-2012 Denny Lin. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 */

define('RCGUARD_RECAPTCHA_SUCCESS', 0);
define('RCGUARD_RECAPTCHA_FAILURE', 1);

class rcguard extends rcube_plugin
{
    function init()
    {
        $this->load_config();
        $this->add_hook('template_object_loginform', array($this, 'loginform'));
        $this->add_hook('authenticate', array($this, 'authenticate'));
        $this->add_hook('login_after', array($this, 'login_after'));
        $this->add_hook('login_failed', array($this, 'login_failed'));
    }

    function loginform($loginform)
    {
        $rcmail = rcmail::get_instance();
        $client_ip = $this->_get_client_ip();

        $query = $rcmail->db->query("SELECT " . $this->unixtimestamp('last') . " AS last, " . $this->unixtimestamp('NOW()') . " as time " .
                                    " FROM rcguard WHERE ip = ? AND hits >= ?",
                                    $client_ip, $rcmail->config->get('failed_attempts'));
        $result = $rcmail->db->fetch_assoc($query);

        if ((!$result || $this->delete_rcguard($result, $client_ip)) &&
            $rcmail->config->get('failed_attempts') != 0) {
            return $loginform;
        }

        return $this->show_recaptcha($loginform);
    }

    function authenticate($args)
    {
        $this->add_texts('localization/');
        $rcmail = rcmail::get_instance();
        $client_ip = $this->_get_client_ip();

        $query  = $rcmail->db->query("SELECT ip FROM rcguard WHERE ip = ? AND hits >= ?",
                                    $client_ip, $rcmail->config->get('failed_attempts'));
        $result = $rcmail->db->fetch_assoc($query);

        if (!$result && $rcmail->config->get('failed_attempts') != 0) {
            return $args;
        }

        if ($rcmail->config->get('pl_plugin')) {
            if (!empty($_COOKIE[$rcmail->config->get('pl_cookie_name')])) {
                $args = $this->pl_authenticate($args);
                return $args;
            }
        }

        if ($response = $_POST['g-recaptcha-response']) {
            if ($this->verify_recaptcha($client_ip, $response)) {
                $this->log_recaptcha(RCGUARD_RECAPTCHA_SUCCESS, $args['user']);
                return $args;
            }
            else {
                $this->log_recaptcha(RCGUARD_RECAPTCHA_FAILURE, $args['user']);

                $rcmail->output->show_message('rcguard.recaptchafailed', 'error');
                $rcmail->output->set_env('task', 'login');
                $rcmail->output->send('login');
            }
        }
        else {
            $this->log_recaptcha(RCGUARD_RECAPTCHA_FAILURE, $args['user']);

            $rcmail->output->show_message('rcguard.recaptchaempty', 'error');
            $rcmail->output->set_env('task', 'login');
            $rcmail->output->send('login');
        }

        return null;
    }

    function login_after($args)
    {
        $client_ip = $this->_get_client_ip();
        $this->delete_rcguard('', $client_ip, true);

        return $args;
    }

    function login_failed($args)
    {
        $rcmail = rcmail::get_instance();
        $client_ip = $this->_get_client_ip();

        $query  = $rcmail->db->query("SELECT hits FROM rcguard WHERE ip = ?", $client_ip);
        $result = $rcmail->db->fetch_assoc($query);

        if ($result) {
            $this->update_rcguard($result['hits'], $client_ip);
        }
        else {
            $this->insert_rcguard($client_ip);
        }
    }

    private function insert_rcguard($client_ip)
    {
        $rcmail = rcmail::get_instance();
        $query  = $rcmail->db->query("INSERT INTO rcguard (ip, first, last, hits) VALUES (?, NOW(), NOW(), ?)",
                                    $client_ip, 1);
    }

    private function update_rcguard($hits, $client_ip)
    {
        $rcmail = rcmail::get_instance();
        $query  = $rcmail->db->query("UPDATE rcguard SET last = NOW(), hits = ? WHERE ip = ?",
                                    $hits + 1, $client_ip);
    }

    private function delete_rcguard($result, $client_ip, $force = false)
    {
        $rcmail = rcmail::get_instance();

        if ($force) {
            $query = $rcmail->db->query("DELETE FROM rcguard WHERE ip = ?", $client_ip);
            $this->flush_rcguard();
            return true;
        }

        $last = $result['last'];
        $time = $result['time'];

        if ($last + $rcmail->config->get('expire_time') * 60 < $time) {
            $this->flush_rcguard();
            return true;
        }

        return false;
    }

    private function flush_rcguard()
    {
        $rcmail = rcmail::get_instance();

        $query = $rcmail->db->query("DELETE FROM rcguard " .
                                    " WHERE " . $this->unixtimestamp('last') . " + ? < " . $this->unixtimestamp('NOW()'),
                                    $rcmail->config->get('expire_time') * 60);
    }

    private function show_recaptcha($loginform)
    {
        $rcmail = rcmail::get_instance();

        $skin_path = $this->local_skin_path();
        if (!file_exists(INSTALL_PATH . '/plugins/rcguard/'.$skin_path)) { $skin_path = 'skins/larry'; }
        $this->include_stylesheet($skin_path . '/rcguard.css');
        $this->include_script('rcguard.js');

        $recaptcha_api = ($rcmail->config->get('recaptcha_https') || $_SERVER['HTTPS']) ?
            $rcmail->config->get('recaptcha_api_secure') : $rcmail->config->get('recaptcha_api');

        $src = sprintf("%s?hl=%s", $recaptcha_api, $rcmail->user->language);
        $script = html::tag('script', array('type' => "text/javascript", 'src' => $src));
        $this->include_script($src);

        $tmp = $loginform['content'];
        $tmp = str_ireplace('</tbody>',
                            '<tr><td class="title" colspan="2"><div class="g-recaptcha" data-sitekey="'.$rcmail->config->get('recaptcha_publickey').'"></div></td>
</tr>
</tbody>', $tmp);
        $loginform['content'] = $tmp;

        return $loginform;
    }

    private function verify_recaptcha($client_ip, $response)
    {
        $rcmail = rcmail::get_instance();

        $privatekey = $rcmail->config->get('recaptcha_privatekey');
        require_once($this->home . '/lib/recaptchalib.php');

        $reCaptcha = new ReCaptcha($privatekey);
        $resp = $reCaptcha->verify($response, $client_ip);

        return ($resp != null && $resp->success);
    }

    private function log_recaptcha($log_type, $username)
    {
        $rcmail = rcmail::get_instance();
        $client_ip = $this->_get_client_ip();
        $username = (empty($username)) ? 'empty username' : $username;

        if (!$rcmail->config->get('recaptcha_log')) {
            return;
        }

        switch ($log_type) {
        case RCGUARD_RECAPTCHA_SUCCESS:
            $log_entry = $rcmail->config->get('recaptcha_log_success');
            break;
        case RCGUARD_RECAPTCHA_FAILURE:
            $log_entry = $rcmail->config->get('recaptcha_log_failure');
            break;
        default:
            $log_entry = $rcmail->config->get('recaptcha_log_unknown');
        }

        if (!empty($log_entry)) {
            $log_entry = str_replace(array('%r', '%u'), array($client_ip, $username), $log_entry);
            rcube::write_log('rcguard', $log_entry);
        }
    }

    private function unixtimestamp($field)
    {
        $rcmail = rcmail::get_instance();
        $ts = '';

        switch ($rcmail->db->db_provider) {
        case 'pgsql':
        case 'postgres':
            $ts = "EXTRACT (EPOCH FROM $field)";
            break;
        default:
            $ts = "UNIX_TIMESTAMP($field)";
        }

        return $ts;
    }

    private function pl_authenticate($args)
    {
        $rcmail = rcmail::get_instance();

        // Code from persistent login plugin
        $plain_token = $rcmail->decrypt($_COOKIE[$this->cookie_name]);
        $token_parts = explode('|', $plain_token);

        if (!empty($token_parts) && is_array($token_parts) && count($token_parts == 5)) {
            if (time() <= $token_parts[4]) {
                $args['user'] = $token_parts[1];
                $args['pass'] = $rcmail->decrypt($token_parts[2]);
                $args['host'] = $token_parts[3];
                $args['cookiecheck'] = false;
                $args['valid'] = true;
            }
        }

        return $args;
    }

    private function _get_client_ip()
    {
        return rcube_utils::remote_addr();
    }
}

?>
