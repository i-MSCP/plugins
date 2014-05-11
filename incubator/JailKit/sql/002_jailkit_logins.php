<?php

return array(
    'up' => "
      CREATE TABLE IF NOT EXISTS jailkit_ssh_logins (
        ssh_login_id int(10) unsigned NOT NULL AUTO_INCREMENT,
        jail_id int(10) unsigned NOT NULL,
        ssh_login_name varchar(16) collate utf8_unicode_ci default NULL,
        ssh_login_pass varchar(200) collate utf8_unicode_ci default NULL,
        ssh_login_locked tinyint(1) default '0',
        ssh_login_status varchar(255) COLLATE utf8_unicode_ci NOT NULL,
        PRIMARY KEY (ssh_login_id),
        UNIQUE KEY ssh_login_name (ssh_login_name),
        KEY ssh_login_status (ssh_login_status)
        CONSTRAINT jail_id FOREIGN KEY (jail_id) REFERENCES jailkit_jails (jail_id) ON DELETE CASCADE
      ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
    ",
    'down' => "
      DROP TABLE IF EXISTS jailkit_logins
    "
);
