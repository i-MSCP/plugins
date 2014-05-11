<?php

return array(
    'up' => "
      CREATE TABLE IF NOT EXISTS jailkit_jails (
        jail_id int(10) unsigned NOT NULL AUTO_INCREMENT,
        jail_owner_id int(10) unsigned NOT NULL,
        jail_max_logins int(10) DEFAULT NULL,
        jail_status varchar(255) COLLATE utf8_unicode_ci NOT NULL,
        PRIMARY KEY (jail_id),
        UNIQUE KEY jail_owner_id (jail_owner_id),
        KEY jail_status (jail_status),
        CONSTRAINT jail_owner_id FOREIGN KEY (jail_owner_id) REFERENCES admin (admin_id) ON DELETE CASCADE
      ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
    ",
    'down' => "
      DROP TABLE IF EXISTS jailkit_jails
    "
);
