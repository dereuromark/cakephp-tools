<?php

$config['App'] = [
	'warnAboutNamedParams' => false,
	'disableMobileDetection' => false
];

$config['DataPreparation'] = [
	'noTrim' => false,
	'disableMobileDetection' => false
];

$config['Passwordable'] = [
	'authType' => ''
];

$config['Reset'] = [
];

$config['Qlogin'] = [
];

$config['Mobile'] = [
];

$config['Weather'] = [
];

$config['UrlCache'] = [
];

$config['Mail'] = [
	'debug' => 0,	# 0=no,1=flashMessageAfterwards,2=fullDebug(noMailSent)
	'log' => 1,
	'useSmtp' => 1,
	'smtpPort' => 25,
	'smtpTimeout' => 20,
	'smtpHost' => '',
	'smtpUsername' => '',
	'smtpPassword' => '',
];

$config['Google'] = [
	'key' => '',
	'api' => '2.x',
	'zoom' => 6,
	'lat' => 51,
	'lng' => 11,
	'type' => 'G_NORMAL_MAP',
 	'static_size' => '500x500'
];

$config['Config'] = [
	'language' => 'en',
	'adminName' => 'Site Owner',
	'adminEmail' => 'test@test.de',
	'noReplyEmail' => 'noreply@test.de',
	'noReplyEmailname' => '',
	'keywords' => '',
	'description' => '',
	'pwd' => ''
];

$config['Paginator'] = [
	'paramType' => 'querystring'
];

$config['Common'] = [
	'messages' => true
];

$config['Typography'] = [
	'locale' => ''
];

$config['Currency'] = [
	'code' => 'USD',
	'symbolLeft' => '',
	'symbolRight' => '$',
	'places' => '2',
	'thousands' => ',',
	'decimals' => '.',
];

$config['Localization'] = [
	'addressFormat' => 'en',
	'thousands' => ',',
	'decimals' => '.',
];

$config['LocalizationPattern'] = [
];

$config['AutoLogin'] = [
];

$config['Validation'] = [
	'browserAutoRequire' => false,
];

$config['Country'] = [
	'imagePath' => 'Data./img/country_flags/',
];

$config['Select'] = [
	'defaultBefore' => ' -[ ',
	'defaultAfter' => ' ]- ',
	'naBefore' => ' -- ',
	'naAfter' => ' -- '
];

$config['Cli'] = [
	'dos2unixPath' => ''
];
