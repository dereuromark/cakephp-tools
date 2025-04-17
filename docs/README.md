# CakePHP Tools Plugin Documentation

## Installation
* [Installation](Install.md)

## Upgrade Guide
* [Upgrade guide from 4.x to 5.x](Upgrade.md)

## Detailed Documentation - Quicklinks

### Routing
* [Url](Url/Url.md) for useful tooling around URL generation.

### I18n
* [I18n](I18n/I18n.md) for language detection and switching

### ErrorHandler
* [ExceptionTrap](Error/ExceptionTrap.md) for improved error handling.

### Email
* [Email](Mailer/Email.md) for sending Emails

### Tokens
* [Tokens](Model/Tokens.md) for Token usage
* [Login Links](Authentication/LoginLink.md) For one time login link functionality

### Controller
* [Controller](Controller/Controller.md)

### Behaviors
* [AfterSave](Behavior/AfterSave.md)
* [Jsonable](Behavior/Jsonable.md)
* [Passwordable](Behavior/Passwordable.md)
* [Slugged](Behavior/Slugged.md)
* [Bitmasked](Behavior/Bitmasked.md)
* [Reset](Behavior/Reset.md)
* [String](Behavior/String.md)
* [Toggle](Behavior/Toggle.md)
* [Encryption](Behavior/Encryption.md)

### Components
* [Common](Component/Common.md)
* [Mobile](Component/Mobile.md)
* [RefererRedirect](Component/RefererRedirect.md)

### Helpers
* [Html](Helper/Html.md)
* [Form](Helper/Form.md)
* [Common](Helper/Common.md)
* [Format](Helper/Format.md)
* [Icon](Helper/Icon.md) [Deprecated, use Icon plugin instead]
* [Progress](Helper/Progress.md)
* [Meter](Helper/Meter.md)
* [Tree](Helper/Tree.md)
* [Typography](Helper/Typography.md)

### Widgets
* [Datalist](Widget/Datalist.md)

### Model/Entity
* [Enums](Entity/Enum.md) using native enums (NEW)
* [StaticEnums](Entity/StaticEnum.md) using static entity methods

Note: Using native enums is recommended since CakePHP 5.

### Utility
* [FileLog](Utility/FileLog.md) to log data into custom file(s) with one line

### Command
* [Inflect](Command/Inflect.md) to test inflection of words.

### Backend
* [Tools Backend](Backend.md) for useful backend tools.

## IDE compatibility improvements
For some methods you can find a IdeHelper task in [IdeHelperExtra plugin](https://github.com/dereuromark/cakephp-ide-helper-extra/):
- `IconHelper::render()` (deprecated)

Those will give you automcomplete for the input.

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

    protected $components = ['Tools.Common'];

    protected $helpers = ['Tools.Common', 'Tools.Time', 'Tools.Number', 'Tools.Format'];

}
```
Here we can also see some of the most useful components and helpers included right away.

The Common component for example will automatically provide:
- Auto-trim on POST (to make - not only notEmpty - validation working properly).

The Tools plugin controller will allow you to:
- Disable cache also works for older IE versions.


### BC shims for easier migration from 4.x
It contains many shims to provide 4.x functionality when upgrading apps to 5.0.
This eases migration as complete parts of the code, such as validation and other
model property settings can be reused immediately without refactoring them right away.

* See [Shims](Shims.md) for details.

## Contributing
Your help is greatly appreciated.

* See [Contributing](Contributing.md) for details.
