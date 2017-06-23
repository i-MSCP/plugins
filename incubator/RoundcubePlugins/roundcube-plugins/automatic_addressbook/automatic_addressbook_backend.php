<?php

/**
 * Automatic addressbook backend 
 *
 * Minimal backend for Automatic Addressbook
 *
 * @version 0.4
 * @author Jocelyn Delalande (slightly modified by Roland 'rosali' Liebl)
 * @author Sebastien Blaisot <sebastien@blaisot.org>
 * @website https://github.com/sblaisot/automatic_addressbook
 * @licence http://www.gnu.org/licenses/gpl-3.0.html GNU GPLv3+
 */

class automatic_addressbook_backend extends rcube_contacts
{
    function __construct($dbconn, $user)
    {
        parent::__construct($dbconn, $user);
        $this->db_name = rcmail::get_instance()->db->table_name('collected_contacts');
    }
}
