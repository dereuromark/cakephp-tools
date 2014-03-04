# CakePHP Tools Plugin

A CakePHP 2.x Plugin containing several useful tools that can be used in many projects.

Please note: New functionality has been tested against 2.x (current master and dev) only. Please upgrade if possible.

## How to include
Installing the Plugin is pretty much as with every other CakePHP Plugin.

* Put the files in `APP/Plugin/Tools`
* Make sure you have `CakePlugin::load('Tools')` or `CakePlugin::loadAll()` in your bootstrap

Tip: You can also use packagist now @ https://packagist.org/packages/dereuromark/tools-cakephp

That's it. It should be up and running.

## The basics

In case you want the Tools bootstrap file included (recommended), you can do that in your `APP/Config/bootstrap.php` with

```php
CakePlugin::load('Tools', array('bootstrap' => true));
```

This can also be done after using `CakePlugin::loadAll()` - without problems.


MyModel can be extended to use more powerful validation and other improvements:

```php
App::uses('MyModel', 'Tools.Model');

class AppModel extends MyModel {
}
```

MyController can be extended for DRY improvements and to fix some common bugs:

```php
App::uses('MyController', 'Tools.Controller');

class MyController extends MyController {
}
```

MyHelper can be extended and used this way:

```php
App::uses('MyHelper', 'Tools.View/Helper');

class AppHelper extends MyHelper {
}
```

The test suite improvements can be used via:

```php
App::uses('MyCakeTestCase', 'Tools.TestSuite');

class SomeClassTest extends MyCakeTestCase {
}
```

To run any of the console commands (replace [ShellName] and [command]!):

    cake Tools.[ShellName] [command]

The models, behaviors, helpers, libs and other classes are used the same way prefixing them with the plugin name:

```php
App::uses('GooglLib', 'Tools.Lib');
App::uses('TimeLib', 'Tools.Utility');
App::uses('GeocoderBehavior', 'Tools.Model/Behavior');
...
```

Tip: For how to use them, try to find some information in the test cases.
A quick reference or some larger modules: [USAGE.md](https://github.com/dereuromark/tools/blob/master/USAGE.md)

## The cool stuff

### Useful fixes

* Auto-trim on POST (to make validation working properly).
* Auto-aliasing for models' "order" properties.
* Disable cache also works for older IE versions.
* Redirect with additional encoding for Apache (if still using named params insteaf of query strings).
* Default settings for Paginator, ... can be set using Configure.
* RSS and Ajax Views for better responses (Ajax also comes with an optional component).
* Minor misc. tweaks.

### Additional classes and features

* Using the Common component's flashMessage() you can have colorful (success, warning, error, ...) flash messages.
  They also can stack up (multiple messages per type). This way no message is lost when redirecting twice etc.
* TinyAuth authorization adapter with single and multi-role support - extremely fast and easy to use.
* The Passwordable behavior allows easy to use functionality for frontend and backend.
* Geocodeable, WhoDidIt, Confirmable and Bitmasked behaviors.
* With MyModel you can build subqueries the Cake way.
* Tokens, Qlogin, TinyUrl, KeyValue functionality included.
* DiffLib, ZipLib, WeatherLib and many more lib functionality.
* The TextExt, Datetime, Numeric and FormExt helpers provide extended functionality if desired.
* Tree helper for working with (complex) trees.
* GoogleMapV3, Gravatar, Qrcode, Timeline, Typography, Ical, Hcard provide additional helper functionality.

and much more


## Disclaimer
Use at your own risk. Please provide any fixes or enhancements via issue or better pull request.
Some classes are still from 1.2 (and are merely upgraded to 2.x) and might still need some serious refactoring.
If you are able to help on that one, that would be awesome.

### Status
[![Build Status](https://api.travis-ci.org/dereuromark/tools.png?branch=master)](https://travis-ci.org/dereuromark/tools)
[![Latest Stable Version](https://poser.pugx.org/dereuromark/tools-cakephp/v/stable.png)](https://packagist.org/packages/dereuromark/tools-cakephp)
[![Coverage Status](https://coveralls.io/repos/dereuromark/tools/badge.png)](https://coveralls.io/r/dereuromark/tools)
[![License](https://poser.pugx.org/dereuromark/tools-cakephp/license.png)](https://packagist.org/packages/dereuromark/tools-cakephp)
[![Total Downloads](https://poser.pugx.org/dereuromark/tools-cakephp/d/total.png)](https://packagist.org/packages/dereuromark/tools-cakephp)

### Branching strategy
The master branch is the currently active and maintained one and works with the current 2.x stable version.
Older versions might be found in their respective branches (1.3, 2.0, 2.3, ...).
Please provide PRs mainly against master branch then.

For details on how to contribute please read the [CONTRIBUTING page](CONTRIBUTING.md).

### License
Licensed under [The MIT License](http://www.opensource.org/licenses/mit-license.php)
unless specified otherwise (in the classes).

### TODOs

* Better test coverage (once it's >= 75% I will declare the plugin stable 1.0)
* Use __d() and https://www.transifex.com/projects/p/tools/resources/ for translations

### Recent changes (possibly BC breaking)

* 2013-02 Removed PasswordChangeBehavior in favor of its new name Passwordable.
* 2013-02-25 Removed deprecated bootstrap functions and moved DebugTab functionality into Bootstrap namespace
  (you need to now manually include it if needed).
* 2014-01 Changed Garbige to Garbage in method names.
* 2014-02 Qlogin now uses Token model instead of deprecated CodeKey per default.
