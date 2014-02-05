CREATE TABLE IF NOT EXISTS `template_editor_template` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) unsigned DEFAULT NULL,
  `service` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(255) NOT NULL,
  `content` text COLLATE utf8_unicode_ci NOT NULL,
  `scope` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  UNIQUE KEY `service_name` (`service`,`name`),
  CONSTRAINT `parent_id` FOREIGN KEY (`parent_id`)
    REFERENCES `template_editor_template` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
