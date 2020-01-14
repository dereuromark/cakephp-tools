# CakePHP Tools Plugin Documentation

## Installation
* [Installation](Install.md)

## Upgrade Guide
* [Upgrade guide from 3.x to 4.x](Upgrade.md)

## Detailed Documentation - Quicklinks

Routing:
* [Url](Url/Url.md) for useful tooling around URL generation.

I18n:
* [I18n](I18n/I18n.md) for language detection and switching

ErrorHandler
* [ErrorHandler](Error/ErrorHandler.md) for improved error handling.

Auth
* [MultiColumnAuthenticate](Auth/MultiColumn.md) for log-in with e.g. "email or username"

Email
* [Email](Mailer/Email.md) for sending Emails

Controller:
* [Controller](Controller/Controller.md)

Behaviors:
* [AfterSave](Behavior/AfterSave.md)
* [Jsonable](Behavior/Jsonable.md)
* [Passwordable](Behavior/Passwordable.md)
* [Slugged](Behavior/Slugged.md)
* [Bitmasked](Behavior/Bitmasked.md)
* [Reset](Behavior/Reset.md)
* [String](Behavior/String.md)
* [Toggle](Behavior/Toggle.md)

Components:
* [Common](Component/Common.md)
* [Mobile](Component/Mobile.md)
* [RefererRedirect](Component/RefererRedirect.md)

Helpers:
* [Html](Helper/Html.md)
* [Form](Helper/Form.md)
* [Common](Helper/Common.md)
* [Format](Helper/Format.md)
* [Progress](Helper/Progress.md)
* [Meter](Helper/Meter.md)
* [Tree](Helper/Tree.md)
* [Typography](Helper/Typography.md)

Widgets:
* [Datalist](Widget/Datalist.md)

Entity:
* [Enum](Entity/Enum.md)

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

## Contributing
Your help is greatly appreciated.

* See [Contributing](Contributing.md) for details.
