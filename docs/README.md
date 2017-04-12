# CakePHP Tools Plugin Documentation

## Version notice

## Installation
* [Installation](Install.md)

## Upgrade Guide
* [Upgrade guide from 2.x to 3.x](Upgrade.md)

## Detailed Documentation - Quicklinks

Routing:
* [Url](Url/Url.md)

I18n:
* [I18n](I18n/I18n.md) for language detection and switching

ErrorHandler
* [ErrorHandler](Error/ErrorHandler.md)

Testing
* [Testing](TestSuite/Testing.md)

Helpers:
* [Html](Helper/Html.md)
* [Form](Helper/Form.md)

Behaviors:
* [Jsonable](Behavior/Jsonable.md)
* [Passwordable](Behavior/Passwordable.md)
* [Slugged](Behavior/Slugged.md)
* [Bitmasked](Behavior/Bitmasked.md)
* [Reset](Behavior/Reset.md)
* [String](Behavior/String.md)
* [Toogle](Behavior/Toggle.md)

## Basic enhancements of the core

### Model
Extend the Tools plugin table and entity class to benefit from a few gotchas:
```php
<?php
namespace App\Model\Table;

use Tools\Model\Table\Table;

class UsersTable extends Table {}
```
and
```php
<?php
namespace App\Model\Entity;

use Tools\Model\Entity\Entity;

class User extends Entity {}
```
You can also make yourself your own AppTable and AppEntity class in your application and then
extend those for each of the individual files - which I recommend for most flexibility.

### Controller
```php
<?php
namespace App\Controller;

use Tools\Controller\Controller;

class AppController extends Controller {

	public $components = ['Tools.Common'];

	public $helpers = ['Tools.Common', 'Tools.Time', 'Tools.Number', 'Tools.Format'];

}
```
Here we can also see some of the most useful components and helpers included right away.

The Common component for example will automatically provide:
- Auto-trim on POST (to make - not only notEmpty - validation working properly).

The Tools plugin controller will allow you to:
- Disable cache also works for older IE versions.


### BC shims for easier migration from 2.x
It contains many shims to provide 2.x functionality when upgrading apps to 3.0.
This eases migration as complete parts of the code, such as validation and other model property settings
can be reused immediatelly without refactoring them right away.

* See [Shims](Shims.md) for details.

## Testing the Plugin
You can test using a local installation of phpunit or the phar version of it:

	cd plugins/Tools
	composer update // or: php composer.phar update
	phpunit // or: php phpunit.phar

To test a specific file:

	phpunit /path/to/class.php


## Contributing
Your help is greatly appreciated.

* See [Contributing](Contributing.md) for details.
