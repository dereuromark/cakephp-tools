<?php

/**
 * Tools Example Configuration
 *
 * Merge the keys below into your application's config/app.php (or
 * config/app_local.php) — do not replace the whole file, since this snippet
 * only contains this plugin's configuration. When copying entries that
 * reference imported classes, use fully-qualified class names or move the
 * `use` imports to the top of the target file. Customize the values as needed.
 */
return [
	// Controller pagination
	'Paginator' => [],

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

	// Data trimming for CommonComponent::startup()
	'DataPreparation' => [
		// NOTE: The component reads the lowercase key `DataPreparation.notrim`.
		// This camelCase `noTrim` does NOT match the runtime read (suspected code bug, see maintainer note).
		'noTrim' => false, // Set true to disable auto-trimming of request data
	],

	// Behaviors
	'Passwordable' => [],
	'Reset' => [],
	'Slugged' => [],

	// Email + i18n (read by Tools\Mailer\Message, Tools\Mailer\Mailer, CommonHelper, ShuntRequestController)
	'Config' => [
		'systemEmail' => '', // Default from address for system mails
		'systemName' => '', // Default from name for system mails
		'adminEmail' => '', // Fallback admin from address
		'adminName' => '', // Fallback admin from name
		'xMailer' => '', // X-Mailer header value
		'live' => false, // false = use debug transport in Mailer when no transport given

		// SEO/meta defaults read by Tools\View\Helper\CommonHelper
		'robots' => null, // Default robots meta; 'public' => index,follow,noarchive; array of tokens; null => noindex,nofollow,noarchive
		'locale' => null, // Default lang attribute used for meta description/keywords (iso2: de, en-us, ...)
		'keywords' => null, // Default meta keywords (string or array of strings)

		// Language switching (Tools\Controller\ShuntRequestController) and locale detection (Tools\Mailer\Mailer)
		'allowedLanguages' => [
			// Each language is keyed by its iso2 code and holds at least a `locale` and `name`:
			// 'de' => ['locale' => 'de_DE', 'name' => 'Deutsch'],
			// 'en' => ['locale' => 'en_US', 'name' => 'English'],
		],
		'defaultLanguage' => null, // iso2 key into allowedLanguages used as primary language
		'defaultLocale' => null, // Explicit primary locale; overrides allowedLanguages-derived locale in Mailer
	],

	// Helpers
	'Format' => [
		'templates' => [],
	],
	'Google' => [],
	'Icon' => [
		'checkExistence' => false,
		'sets' => [],
		'map' => [],
	],

	// Tools\View\Helper\FormHelper defaults (merged into the helper config)
	'FormConfig' => [
		'novalidate' => false, // Set true to add novalidate to Form->create() by default
	],

	// Tools\View\Helper\TypographyHelper
	'Typography' => [
		'locale' => null, // null => derive from App.language; or hardwire 'default', 'low', or 'angle'
	],

	// Tools\View\Helper\ProgressHelper (text-based progress bar)
	'Progress' => [
		'empty' => '░', // Char for the empty portion
		'full' => '█', // Char for the filled portion
	],

	// Tools\View\Helper\MeterHelper (text-based meter/gauge)
	'Meter' => [
		'empty' => '░', // Char for the empty portion
		'full' => '█', // Char for the filled portion
		'precision' => 6, // Rounding precision for the value
	],
];
