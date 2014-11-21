# CakePHP Tools Plugin
[![Build Status](https://api.travis-ci.org/dereuromark/cakephp-tools.png?branch=cake3)](https://travis-ci.org/dereuromark/cakephp-tools)
[![License](https://poser.pugx.org/dereuromark/cakephp-tools/license.png)](https://packagist.org/packages/dereuromark/cakephp-tools)
[![Total Downloads](https://poser.pugx.org/dereuromark/tools-cakephp/d/total.png)](https://packagist.org/packages/dereuromark/cakephp-tools)

A CakePHP 3.x Plugin containing several useful tools that can be used in many projects.


## Version notice

This cake3 branch only works for **CakePHP3.x** - please use the master branch for CakePHP 2.x!
**It is still dev** (not even alpha), please be careful with using it.

### Planned Release Cycle:
Dev (currently), Alpha, Beta, RC, 1.0 stable (incl. tagged release then).

## What is this plugin for?

### Enhancing the core
- Auto-trim on POST (to make - not only notEmpty - validation working properly).
- Disable cache also works for older IE versions.
- With flashMessage() you can have colorful (success, warning, error, ...) flash messages.
  They also can stack up (multiple messages per type) which the core currently doesn't support.
- Provide enum support as "static enums"
- Default settings for Paginator, ... can be set using Configure.
- Provided a less error-prone inArray() method when using Utility class.

### Additional features
- The Passwordable behavior allows easy to use password functionality for frontend and backend.
- Tree helper for working with (complex) trees and their output.
- RSS and Ajax Views for better responses (Ajax also comes with an optional component).
- Slugged and Reset behavior
- The Text, Time, Number libs and helpers etc provide extended functionality if desired.
- GoogleMapV3, Timeline, Typography, etc provide additional helper functionality.
- Email as a wrapper for core's Email adding some more usefulness and making debugging/testing easier.

### Providing 2.x shims
This plugin for the Cake 3 version also contains some 2.x shims to ease migration of existing applications from 2.x to 3.x:
- find('first') and find('count')
- Model::$validate, Model::$primaryKey, Model::$displayField and Model relations as properties
- Set/Multibyte class, Session component and a cut down version of JsHelper

## How to include
Installing the Plugin is pretty much as with every other CakePHP Plugin.

Put the files in `ROOT/plugins/Tools`, using Packagist/Composer:
```
"require": {
	"dereuromark/cakephp-tools": "dev-cake3"
}
```
and

	composer update

Details @ https://packagist.org/packages/dereuromark/cakephp-tools

This will load the plugin (within your boostrap file):
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
		'Tools' => array('bootstrap' => true)
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
	composer update
	phpunit

To test a specific file:

	phpunit /path/to/class.php

### TODOs

* Move more 2.x stuff to 3.x
