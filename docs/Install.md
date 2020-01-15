# Installation

## How to include
Installing the Plugin is pretty much as with every other CakePHP Plugin.

```
composer require dereuromark/cakephp-tools
```

The following command can enable the plugin:
```
bin/cake plugin load Tools
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
