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
Using Cake3 and namespaces, don't forget to add "Tools" as namespace to new files.
Also don't forget the `use` statements. So for a new behavior "Extendable":
```php
namespace Tools\Model\Behavior;

use Cake\ORM\Behavior;

class SluggedBehavior extends Behavior {
}
```
Note that use statements should be in alphabetical order.
See CakePHP coding standards for details.

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
