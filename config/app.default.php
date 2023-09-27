<?php
$config = [
	// Controller pagination
	'Paginator' => [
	],

	// Error handling around 404s
	'Log' => [
		'debug' => [
			'scopes' => null,
		],
		'error' => [
			'scopes' => null,
		],
		'404' => [
			'file' => '404',
			'levels' => ['error'],
			'scopes' => ['404'],
		],
	],

	// Controller pagination
	'DataPreparation' => [
		'noTrim' => false,
	],

	// Behaviors
	'Passwordable' => [
	],
	'Reset' => [
	],
	'Slugged' => [
	],

	// Email
	'Config' => [
		'systemEmail' => '',
		'systemName' => '',
		'adminEmail' => '',
		'adminName' => '',
		'xMailer' => '',
		'live' => false,
	],

	// Helpers
	'Format' => [
		'templates' => [],
	],
	'Google' => [
	],
	'Icon' => [
		'sets' => [],
		'map' => [],
	],
];
