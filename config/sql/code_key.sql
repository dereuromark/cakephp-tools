-- code_keys
CREATE TABLE IF NOT EXISTS `{prefix}code_keys` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` char(36) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `type` varchar(10) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'e.g.:activate,reactivate',
  `key` varchar(60) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `content` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '' COMMENT 'can transport some information',
  `used` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;