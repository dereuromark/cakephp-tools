# Installation

## How to include
Installing the Plugin is pretty much as with every other CakePHP Plugin.

Include the plugin using Packagist/Composer:
```
"require": {
	"dereuromark/cakephp-tools": "dev-master"
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
Plugin::loadAll(...);
```

In case you want the Tools bootstrap file included (recommended), you can do that in your `ROOT/config/bootstrap.php` with

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

// In a View
$this->loadHelper('Tools.Foo'); // Adding FooHelper

// In a Controller (deprecated)
public $helpers = ['Tools.Foo']; // Adding FooHelper
```

### Class Alias Shortcuts

For Configure usage especially in view files, you can add this to the bootstrap:
```php
class_alias('Cake\Core\Configure', 'Configure');
```
This avoids having to add tons of `use` statements at the top of your view ctps.
