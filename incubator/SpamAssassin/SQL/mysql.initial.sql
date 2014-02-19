--
-- Table structure for table `awl`
--

CREATE TABLE IF NOT EXISTS `awl` (
  `username` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `ip` varchar(40) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `count` int(11) NOT NULL DEFAULT '0',
  `totscore` float NOT NULL DEFAULT '0',
  `signedby` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`username`,`email`,`signedby`,`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bayes_expire`
--

CREATE TABLE IF NOT EXISTS `bayes_expire` (
  `id` int(11) NOT NULL DEFAULT '0',
  `runtime` int(11) NOT NULL DEFAULT '0',
  KEY `bayes_expire_idx1` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bayes_global_vars`
--

CREATE TABLE IF NOT EXISTS `bayes_global_vars` (
  `variable` varchar(30) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`variable`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `bayes_global_vars`
--

INSERT IGNORE INTO `bayes_global_vars` (`variable`, `value`) VALUES
('VERSION', '3');

-- --------------------------------------------------------

--
-- Table structure for table `bayes_seen`
--

CREATE TABLE IF NOT EXISTS `bayes_seen` (
  `id` int(11) NOT NULL DEFAULT '0',
  `msgid` varchar(200) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL DEFAULT '',
  `flag` char(1) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  PRIMARY KEY (`id`,`msgid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bayes_token`
--

CREATE TABLE IF NOT EXISTS `bayes_token` (
  `id` int(11) NOT NULL DEFAULT '0',
  `token` char(5) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `spam_count` int(11) NOT NULL DEFAULT '0',
  `ham_count` int(11) NOT NULL DEFAULT '0',
  `atime` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`,`token`),
  KEY `bayes_token_idx1` (`id`,`atime`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bayes_vars`
--

CREATE TABLE IF NOT EXISTS `bayes_vars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(200) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `spam_count` int(11) NOT NULL DEFAULT '0',
  `ham_count` int(11) NOT NULL DEFAULT '0',
  `token_count` int(11) NOT NULL DEFAULT '0',
  `last_expire` int(11) NOT NULL DEFAULT '0',
  `last_atime_delta` int(11) NOT NULL DEFAULT '0',
  `last_expire_reduce` int(11) NOT NULL DEFAULT '0',
  `oldest_token_age` int(11) NOT NULL DEFAULT '2147483647',
  `newest_token_age` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `bayes_vars_idx1` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `userpref`
--

CREATE TABLE IF NOT EXISTS `userpref` (
  `username` varchar(100) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `preference` varchar(50) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `value` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `prefid` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`prefid`),
  KEY `username` (`username`),
  UNIQUE KEY `user_pref` (`username`,`preference`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Dumping data for table `userpref`
--

INSERT IGNORE INTO `userpref` (`username`, `preference`, `value`) VALUES
('$GLOBAL', 'required_score', '5'),
('$GLOBAL', 'rewrite_header Subject', '*****SPAM*****'),
('$GLOBAL', 'report_safe', '1'),
('$GLOBAL', 'use_bayes', '1'),
('$GLOBAL', 'use_bayes_rules', '1'),
('$GLOBAL', 'bayes_auto_learn', '1'),
('$GLOBAL', 'bayes_auto_learn_threshold_nonspam', '0.1'),
('$GLOBAL', 'bayes_auto_learn_threshold_spam', '12.0'),
('$GLOBAL', 'use_auto_whitelist', '0'),
('$GLOBAL', 'skip_rbl_checks', '1'),
('$GLOBAL', 'use_razor2', '0'),
('$GLOBAL', 'use_pyzor', '0'),
('$GLOBAL', 'use_dcc', '0'),
('$GLOBAL', 'score USER_IN_BLACKLIST', '10'),
('$GLOBAL', 'score USER_IN_WHITELIST', '-6');
