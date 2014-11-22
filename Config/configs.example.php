<?php

$config['App'] = array(
	'warnAboutNamedParams' => false,
	'disableMobileDetection' => false
);

$config['DataPreparation'] = array(
	'noTrim' => false,
	'disableMobileDetection' => false
);

$config['Passwordable'] = array(
	'authType' => ''
);

$config['Reset'] = array(
);

$config['Qlogin'] = array(
);

$config['Mobile'] = array(
);

$config['Weather'] = array(
);

$config['UrlCache'] = array(
);

$config['Mail'] = array(
	'debug' => 0,	# 0=no,1=flashMessageAfterwards,2=fullDebug(noMailSent)
	'log' => 1,
	'useSmtp' => 1,
	'smtpPort' => 25,
	'smtpTimeout' => 20,
	'smtpHost' => '',
	'smtpUsername' => '',
	'smtpPassword' => '',
);

$config['Google'] = array(
	'key' => '',
	'api' => '2.x',
	'zoom' => 6,
	'lat' => 51,
	'lng' => 11,
	'type' => 'G_NORMAL_MAP',
 	'static_size' => '500x500'
);

$config['Config'] = array(
	'language' => 'en',
	'adminName' => 'Site Owner',
	'adminEmail' => 'test@test.de',
	'noReplyEmail' => 'noreply@test.de',
	'noReplyEmailname' => '',
	'keywords' => '',
	'description' => '',
	'pwd' => ''
);

$config['Paginator'] = array(
	'paramType' => 'querystring'
);

$config['Common'] = array(
	'messages' => true
);

$config['Typography'] = array(
	'locale' => ''
);

$config['Currency'] = array(
	'code' => 'USD',
	'symbolLeft' => '',
	'symbolRight' => '$',
	'places' => '2',
	'thousands' => ',',
	'decimals' => '.',
);

$config['Localization'] = array(
	'addressFormat' => 'en',
	'thousands' => ',',
	'decimals' => '.',
);

$config['LocalizationPattern'] = array(
);


$config['AutoLogin'] = array(
);

$config['Validation'] = array(
	'browserAutoRequire' => false,
);

$config['Country'] = array(
	'imagePath' => 'Data./img/country_flags/',
);

$config['Select'] = array(
	'defaultBefore' => ' -[ ',
	'defaultAfter' => ' ]- ',
	'naBefore' => ' -- ',
	'naAfter' => ' -- '
);

$config['Cli'] = array(
	'dos2unixPath' => ''
);