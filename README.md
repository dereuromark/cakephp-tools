# CakePHP Tools Plugin
[![Build Status](https://api.travis-ci.org/dereuromark/cakephp-tools.png?branch=cake3)](https://travis-ci.org/dereuromark/cakephp-tools)
[![License](https://poser.pugx.org/dereuromark/cakephp-tools/license.png)](https://packagist.org/packages/dereuromark/cakephp-tools)
[![Total Downloads](https://poser.pugx.org/dereuromark/tools-cakephp/d/total.png)](https://packagist.org/packages/dereuromark/cakephp-tools)

A CakePHP 3.x Plugin containing several useful tools that can be used in many projects.


## Version notice

This cake3 branch only works for **CakePHP3.x** - please use the master branch for CakePHP 2.x!


## How to include
Installing the Plugin is pretty much as with every other CakePHP Plugin.

Put the files in `APP/Plugin/Tools`, using Packagist/Composer:
```
"require": {
	"dereuromark/cakephp-tools": "dev-cake3"
}
```
and

	composer update

Details @ https://packagist.org/packages/dereuromark/cakephp-tools

That's it. It should be up and running.

## The basics
This will load the plugin:
```php
Plugin::load('Tools');
```
or
```php
Plugin::loadAll();
```

In case you want the Tools bootstrap file included (recommended), you can do that in your `APP/Config/bootstrap.php` with

```php
Plugin::load('Tools', array('bootstrap' => true));
```

or

```php
Plugin::loadAll(array(
		'Tools' => array('bootstrap' => true
));
```

## Namespacing
Using Cake3 and namespaces, don't forget to add "Tools" as namespace to new files.
Also don't forget the `use` statements.

If you create a new behavior in the plugin, it might look like this:
```php
namespace Tools\Model\Behavior;

use Cake\ORM\Behavior;

class CoolBehavior extends Behavior {
}
```

For a new APP behavior "MySlugged" that extends "Tools.Slugged" it is:
```php
namespace App\Model\Behavior;

use Tools\Model\Behavior\SluggedBehavior;

class MySluggedBehavior extends SluggedBehavior {
}
```
Note that use statements should be in alphabetical order.
See CakePHP coding standards for details.

### Internal handling via plugin dot notation
Internally (method access), you don't use the namespace declaration. The plugin name suffices:
```php
// In a Table
$this->addBehavior('Tools.Slugged'); // Adding SluggedBehavior

// In a Controller
public $helpers = array('Tools.Foo'); // Adding FooHelper
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
