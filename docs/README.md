# CakePHP Tools Plugin Documentation

## Version notice

This cake3 branch only works for **CakePHP3.x** - please use the master branch for CakePHP 2.x!
**It is still dev** (not even alpha), please be careful with using it.

## Installation
* [Installation](Install.md)

## Upgrade Guide
* [Upgrade guide from 2.x to 3.x](Upgrade.md)

## Detailed Documentation - Quicklinks
* [Behavior/Passwordable](Behavior/Passwordable.md)
* [Behavior/Slugged](Behavior/Slugged.md)
* [Behavior/Jsonable](Behavior/Jsonable.md)
* ...

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

	public $components = array('Tools.Common', 'Tools.Flash');

	public $helpers = array('Tools.Common', 'Tools.Flash', 'Tools.Time', 'Tools.Number', 'Tools.Format');

}
```
Here we can also see some of the most useful components and helpers included right away.

The Common component for example will automatically provide:
- Auto-trim on POST (to make - not only notEmpty - validation working properly).

With the Flash component and it's message() method you can have colorful (success, warning, error, ...) flash messages.
They also can stack up (multiple messages per type) which the core currently still doesn't support.

The Tools plugin controller will allow you to:
- Disable cache also works for older IE versions.




### BC shims for easier migration from 2.x
The session component of the core is deprecated and will throw a warning as it will soon be removed.
Better use the plugin one right away. It is a 1:1 clone of it.
```php
namespace App\Controller;

use Tools\Controller\Controller;

class AppController extends Controller {

	public $components = array('Tools.Session');

}
```


## Testing
You can test using a local installation of phpunit or the phar version of it:

	cd Plugin/Tools
	composer update
	phpunit

To test a specific file:

	phpunit /path/to/class.php

