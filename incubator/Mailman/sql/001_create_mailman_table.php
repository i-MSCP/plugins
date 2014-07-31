<?php

return array(
	'up' => "
		CREATE TABLE IF NOT EXISTS mailman (
			mailman_id int(11) unsigned NOT NULL AUTO_INCREMENT,
			mailman_admin_id int(11) unsigned NOT NULL,
			mailman_admin_email varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			mailman_admin_password varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			mailman_list_name varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			mailman_status varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			PRIMARY KEY (mailman_id),
			UNIQUE KEY mailman_list_name (mailman_list_name),
			KEY mailman_admin_id (mailman_admin_id)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
	",
	'down' => '
		DROP TABLE IF EXISTS mailman
	'
);
