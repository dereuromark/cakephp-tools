-- tokens
CREATE TABLE IF NOT EXISTS `{prefix}tokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) COLLATE utf8_unicode_ci DEFAULT NULL,
  `type` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'e.g.:activate,reactivate',
  `key` varchar(60) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `content` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'can transport some information',
  `used` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `unlimited` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'used will never be set to 1',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM ;
