# Improved version of ErrorHandler

The main goal of the error.log is to notify about internal errors of the system.
By default there would also be a lot of noise in there.

Most 404 logs should not be part of your error log, for example. 
You can either completely ignore them, or better yet put them into their own space:
```php
Log::config('404', [
	'className' => 'DatabaseLog.Database',
	'type' => '404',
	'levels' => ['error'],
	'scopes' => ['404'],
]);
```

Make sure your other log configs are scope-deactivated then to prevent them being logged twice:
```php
	'Log' => [
		'debug' => [
			'scopes' => false,
			...
		],
		'error' => [
			'scopes' => false,
			...
		],
	],
```

In your bootstrap, the following snippet just needs to include the ErrorHandler of this plugin:
```php
// Switch Cake\Error\ErrorHandler to
use Tools\Error\ErrorHandler;

if ($isCli) {
	(new ConsoleErrorHandler(Configure::read('Error')))->register();
} else {
	(new ErrorHandler(Configure::read('Error')))->register();
}
```

### Tips
You should also set up a monitor to check for internally caused 404s (referrer is a page on the own site) and alert (via email or alike).
In that case you are having invalid links in your pages somewhere, which should be fixed.
All other 404s are caused from the outside (often times crawlers and bots) and are usually not too relevant.
