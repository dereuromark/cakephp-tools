# CakePHP Tools Plugin

A CakePHP 3.x Plugin containing several useful tools that can be used in many projects.


## Version notice

This cake3 branch only works for **cake3** - please use the master branch for CakePHP 2.x!


## How to include
Installing the Plugin is pretty much as with every other CakePHP Plugin.

Put the files in `APP/Plugin/Tools`, using packagist/composer:
```
"require": {
	"dereuromark/tools-cakephp": "dev-cake3"
}
```
and

	composer update

Details @ https://packagist.org/packages/dereuromark/tools-cakephp

That's it. It should be up and running.

## The basics

In case you want the Tools bootstrap file included (recommended), you can do that in your `APP/Config/bootstrap.php` with

```php
CakePlugin::load('Tools', array('bootstrap' => true));
```

For `CakePlugin::loadAll()` it's

```php
CakePlugin::loadAll(array(
		'Tools' => array('bootstrap' => true
));
```

## Namespacing
Using Cake3 and namespaces, don't forget to add "Dereuromark\Tools" as namespace to new files.
Also don't forget the `use` statements.

So for a new behavior "MySlugged" that extends "Slugged" it is:
```php
namespace App\Model\Behavior;

use Dereuromark\Tools\Model\Behavior\SluggedBehavior;

class MySluggedBehavior extends SluggedBehavior {
}
```
Note that use statements should be in alphabetical order.
See CakePHP coding standards for details.

### Shortened namespacing
As long as you don't have a "Tools" namespace already in use and if you want to save
yourself some namespace typing, you can configure it the way that it does not need the
the vendor name:

```php
CakePlugin::load('Tools', array('namespace' => 'Dereuromark\\Tools'));
```

For `CakePlugin::loadAll()` it's

```php
CakePlugin::loadAll(array(
		'Tools' => array('namespace' => 'Dereuromark\\Tools'
));
```
Personally, this is my default configuration for all plugins.

So for a new behavior "MySlugged" that extends "Slugged" it is now:
```php
namespace App\Model\Behavior;

use Tools\Model\Behavior\SluggedBehavior;

class MySluggedBehavior extends SluggedBehavior {
}
```

## Testing
You can test using a local installation of phpunit or the phar version of it:

	cd Plugin/Tools
	phpunit --stderr

To test a specific file:

	phpunit --stderr /path/to/class.php

### License
Licensed under [The MIT License](http://www.opensource.org/licenses/mit-license.php)
unless specified otherwise (in the classes).

### TODOs

* Move more 2.x stuff to 3.x
