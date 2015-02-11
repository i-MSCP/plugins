<?php

/**
 * This plugin displays an icon showing the status
 * of dkim verification of the message
 *
 * @version 0.8.3
 * @author Julien vehent
 * @mail julien@linuxwall.info
 *
 * original plugin from Vladimir Mach - wladik@gmail.com
 * http://www.wladik.net
 *
 * Changelog:
 *  20140219 - Dutch translation by Filip Vervloesem
 *  20140105 - from Savinov Artem: Add small fix if dkim and domainkey signature exists
 *  20120915 - Portuguese – Brazil translation by Brivaldo Jr
 *             Russian translation, by Подшивалов Антон
 *             Fix header match to include `d` and process only one regex
 *  20120825 - Rename imap_init hook into storage_init (Daniel Hahler)
 *  20110912 - Added X-Spam-Status for spamassassin (thanks Ashish Shukla for the patch)
 *  20110619 - Added License information for GPLv2
 *  20110406 - added italian translation from Roberto Puzzanghera
 *  20110128 - updated german translation by Simon
 *  20101118 - japanese translation from Taka
 *  20100811 - from Sergio Cambra: SPF fix, image function and spanish translation
 *  20100202 - fix for amavis and add cz translation
 *  20100201 - add control of header.i and header.from to detect third party signature, change icons
 *  20100115 - add 'no information' status with image using x-dkim-authentication-results
 *  20090920 - fixed space in matching status (thanks Pim Pronk for suggestion)
 */
class dkimstatus extends rcube_plugin
{
    public $task = 'mail';
    function init()
    {
        $rcmail = rcmail::get_instance();
        if ($rcmail->action == 'show' || $rcmail->action == 'preview') {
            $this->add_hook('storage_init', array($this, 'storage_init'));
            $this->add_hook('message_headers_output', array($this, 'message_headers'));
        } else if ($rcmail->action == '') {
            // with enabled_caching we're fetching additional headers before show/preview
            $this->add_hook('storage_init', array($this, 'storage_init'));
        }
    }

    function storage_init($p)
    {
        $rcmail = rcmail::get_instance();
        $p['fetch_headers'] = trim($p['fetch_headers'].' ' . strtoupper('Authentication-Results').' '. strtoupper('X-DKIM-Authentication-Results').' ' .strtoupper('X-Spam-Status'));
        return $p;
    }

    function image($image, $alt, $title)
    {
        return '<img src="plugins/dkimstatus/images/'.$image.'" alt="'.$this->gettext($alt).'" title="'.$this->gettext($alt).htmlentities($title).'" /> ';
    }

    function message_headers($p)
    {
        $this->add_texts('localization');

        /* First, if dkimproxy did not find a signature, stop here
        */
        if($p['headers']->others['x-dkim-authentication-results'] || $p['headers']->others['authentication-results'] || $p['headers']->others['x-spam-status']){

            $results = $p['headers']->others['x-dkim-authentication-results'];

            if(preg_match("/none/", $results)) {
                $image = 'nosiginfo.png';
                $alt = 'nosignature';
            } else {
                /* Second, check the authentication-results header
                */
                if($p['headers']->others['authentication-results']) {

                    $results = $p['headers']->others['authentication-results'];

                    if (is_array($results)) {
                        foreach ($results as $result) {
                            if(preg_match("/dkim=([a-zA-Z0-9]*)/", $result, $m)) {
                                $status = ($m[1]);
                                $res=$result;
                                break;
                            }
                            if(preg_match("/domainkeys=([a-zA-Z0-9]*)/", $result, $m)) {
                                $status = ($m[1]);
                                $res=$result;
                            }
                        }
                        $results=$res;
                    } else {
                        if(preg_match("/dkim=([a-zA-Z0-9]*)/", $results, $m)) {
                            $status = ($m[1]);
                        }
                        if(preg_match("/domainkeys=([a-zA-Z0-9]*)/", $results, $m)) {
                            $status = ($m[1]);
                        }
                    }

                    if($status == 'pass') {

                        /* Verify if its an author's domain signature or a third party
                        */

                        if(preg_match("/[@]([a-zA-Z0-9_-]+([.][a-zA-Z0-9_-]+)?\.[a-zA-Z]{2,4})/", $p['headers']->from, $m)) {
                            $authordomain = $m[1];
                            if(preg_match("/header\.(d|i|from)=(([a-zA-Z0-9]+[_\.\-]?)+)?($authordomain)/", $results)) {
                                $image = 'authorsign.png';
                                $alt = 'verifiedsender';
                                $title = $results;
                            } else {
                                $image = 'thirdpty.png';
                                $alt = 'thirdpartysig';
                                $title = $results;
                            }
                        }

                    }
                    /* If signature proves invalid, show appropriate warning
                    */
                    else if ($status) {
                        $image = 'invalidsig.png';
                        $alt = 'invalidsignature';
                        $title = $results;
                    }
                    /* If no status it can be a spf verification
                    */
                    else {
                        $image = 'nosiginfo.png';
                        $alt = 'nosignature';
                    }

                /* Third, check for spamassassin's X-Spam-Status
                */
                } else if ($p['headers']->others['x-spam-status']) {

                    $image = 'nosiginfo.png';
                    $alt = 'nosignature';

                    /* DKIM_* are defined at: http://search.cpan.org/~kmcgrail/Mail-SpamAssassin-3.3.2/lib/Mail/SpamAssassin/Plugin/DKIM.pm */
                    $results = $p['headers']->others['x-spam-status'];
                    if(preg_match_all('/DKIM_[^,]+/', $results, $m)) {
                        if(array_search('DKIM_SIGNED', $m[0]) !== FALSE) {
                            if(array_search('DKIM_VALID', $m[0]) !== FALSE) {
                                if(array_search('DKIM_VALID_AU', $m[0])) {
                                    $image = 'authorsign.png';
                                    $alt = 'verifiedsender';
                                    $title = 'DKIM_SIGNED, DKIM_VALID, DKIM_VALID_AU';
                                } else {
                                    $image = 'thirdpty.png';
                                    $alt = 'thirdpartysig';
                                    $title = 'DKIM_SIGNED, DKIM_VALID';
                                }
                            } else {
                                $image = 'invalidsig.png';
                                $alt = 'invalidsignature';
                                $title = 'DKIM_SIGNED';
                            }
                        }
                    }
                }
            }
        } else {
            $image = 'nosiginfo.png';
            $alt = 'nosignature';
        }
        if ($image && $alt) {
            $p['output']['from']['value'] = $this->image($image, $alt, $title) . $p['output']['from']['value'];
        }
        return $p;
    }
}
