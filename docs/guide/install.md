---
description: Install Tools, load its library features, and configure optional dependencies.
---

# Installation

This branch uses unreleased CakePHP 6 packages. Set `"minimum-stability": "dev"`
and `"prefer-stable": true` in your application's `composer.json` before installing.

From your CakePHP application root:

```bash
composer require dereuromark/cakephp-tools:dev-cake6
bin/cake plugin load Tools --no-routes
```

Composer checks compatible PHP and CakePHP versions and installs the Shim dependency. See the [version map](https://github.com/dereuromark/cakephp-tools/wiki#cakephp-version-map) for other CakePHP branches.

`--no-routes` loads Tools with all plugin routes disabled, including the language-switch endpoint. Keep existing `config/plugins.php` entries; if Tools is already registered, update that entry instead of adding a duplicate. Continue with [Getting started](/guide/quick-start) to render a helper in an existing template.

To load manually in `Application::bootstrap()`, preserve other plugin registrations:

```php
$this->addPlugin('Tools', ['routes' => false]);
```

The [backend guide](/guide/backend) explains route loading and access controls when those pages are needed.

## Optional dependencies

| Feature | Additional application dependency |
|---|---|
| Mobile component | `mobiledetect/mobiledetectlib`, using its supported API |
| Login-link authentication | `cakephp/authentication` and a configured token table |
| Tools token-table migration | `cakephp/migrations` |

Development dependencies from this repository are not installed automatically in applications. For example, install the detector before loading the Mobile component:

```bash
composer require mobiledetect/mobiledetectlib:^4
```

Register a detector in `config/bootstrap.php` after installing MobileDetect:

```php
use Cake\Http\ServerRequest;
use Detection\MobileDetect;

ServerRequest::addDetector('mobile', static function (ServerRequest $request): bool {
    $detector = new MobileDetect();
    $detector->setUserAgent($request->getHeaderLine('User-Agent'));

    return $detector->isMobile();
});
```

Follow [Login links](/reference/login-link) for authentication and database setup. Legacy icon helpers are deprecated; use the [Templating plugin](https://dereuromark.github.io/cakephp-templating/) for new icon integration.

## Configuration and plugin names

`config/app.example.php` lists optional settings. Copy the settings you need into your application's configuration. The example file is not loaded automatically.

CakePHP's plugin notation identifies the feature to load:

```php
// Inside a Table initialize() method.
$this->addBehavior('Tools.Slugged');

// Inside an AppView initialize() method.
$this->loadHelper('Tools.Progress');

// Inside a controller initialize() method.
$this->loadComponent('Tools.Common');
```

For custom application classes, use the application's namespace, such as `App\Model\Behavior`, and import the Tools class being extended. Keep customizations in the application so Composer updates preserve them.
