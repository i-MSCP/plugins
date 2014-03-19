<?php

return array(
	'up' => '
		CREATE TABLE IF NOT EXISTS server_provisioning_keys (
			admin_id int(10) unsigned NOT NULL,
			access_key_id varchar (64) NOT NULL,
			private_key varchar (64) NOT NULL,
			UNIQUE KEY keys_admin_id (admin_id),
			KEY access_key_id (access_key_id),
			CONSTRAINT keys_admin_id FOREIGN KEY (admin_id)
				REFERENCES admin (admin_id) ON DELETE CASCADE
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
	',
	'down' => '
		DROP TABLE IF EXISTS server_provisioning_keys
	'
);
