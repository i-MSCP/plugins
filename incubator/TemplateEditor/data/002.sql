CREATE TABLE IF NOT EXISTS `template_editor_template` (
  `template_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `group_id` int(10) unsigned NOT NULL,
  `service` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) NOT NULL,
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  `scope` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`template_id`),
  KEY `parent_id` (`parent_id`),
  UNIQUE KEY `service_name` (`service`,`name`),
  CONSTRAINT `parent_id` FOREIGN KEY (`parent_id`)
    REFERENCES `template_editor_template` (`template_id`) ON DELETE CASCADE,
  CONSTRAINT `group_id` FOREIGN KEY (`group_id`)
    REFERENCES `template_editor_group` (`group_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
