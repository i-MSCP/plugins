CREATE TABLE IF NOT EXISTS `template_editor_template` (
  `template_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `template_group_id` int(10) unsigned NOT NULL,
  `template_name` varchar(255) NOT NULL,
  `template_content` text COLLATE utf8_unicode_ci NOT NULL,
  `template_scope` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`template_id`),
  UNIQUE KEY `template_group_name` (`template_group_id`,`template_name`),
  KEY `template_group_id` (`template_group_id`),
  KEY `template_name` (`template_name`),
  KEY `template_scope` (`template_scope`),
  CONSTRAINT `template_group_id` FOREIGN KEY (`template_group_id`)
    REFERENCES `template_editor_group` (`group_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
