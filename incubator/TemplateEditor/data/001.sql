CREATE TABLE IF NOT EXISTS `template_editor_group` (
  `group_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `group_parent_id` int(10) unsigned DEFAULT NULL,
  `group_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `group_service_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`group_id`),
  UNIQUE KEY `group_name` (`group_name`),
  KEY `group_parent_id` (`group_parent_id`),
  KEY `group_service_name` (`group_service_name`),
  CONSTRAINT `group_parent_id` FOREIGN KEY (`group_parent_id`)
    REFERENCES `template_editor_group` (`group_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
