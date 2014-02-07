CREATE TABLE IF NOT EXISTS `termplate_editor_group_admin` (
  `group_id` int(10) unsigned NOT NULL,
  `admin_id` int(10) unsigned NOT NULL,
  UNIQUE KEY `group_admin` (`group_id`,`admin_id`),
  CONSTRAINT `group_id` FOREIGN KEY (`group_id`)
    REFERENCES `template_editor_group` (`group_id`) ON DELETE CASCADE,
  CONSTRAINT `admin_id` FOREIGN KEY (`admin_id`)
    REFERENCES `admin` (`admin_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
