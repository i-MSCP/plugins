CREATE TABLE IF NOT EXISTS `termplate_editor_template_admin` (
  `template_id` int(10) unsigned NOT NULL,
  `admin_id` int(10) unsigned NOT NULL,
  UNIQUE KEY `template_admin` (`template_id`,`admin_id`),
  CONSTRAINT `template_id` FOREIGN KEY (`template_id`)
   REFERENCES `template_editor_template` (`id`) ON DELETE CASCADE,
  CONSTRAINT `admin_id` FOREIGN KEY (`admin_id`)
    REFERENCES `admin` (`admin_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
