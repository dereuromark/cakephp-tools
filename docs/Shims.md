# Migration from 2.x to 3.x: Shims
Shims ease migration as complete parts of the code, such as validation and other model property settings
can be reused immediately without refactoring them right away.

See the [Shim plugin](https://github.com/dereuromark/cakephp-shim) for details.

Note: It does not hurt to have them, if you don't use them. The overhead is minimal.

## Model
The following can be used in 3.x (mainly via Shim plugin support):

### Table
- $order property
- $validate property
- relations (hasX, belongsTo)
- $displayField
- $primaryKey
- Timestamp behavior added by default (if modified or created exists)

### Entity
- Enums via enum() are ported in entity, if you used them before.


## Component

### Session
The session component of the core is deprecated and will throw a warning as it will soon be removed.
Better use the plugin one right away. It is a 1:1 clone of it.
```php
namespace App\Controller;

use Tools\Controller\Controller;

class AppController extends Controller {

	public $components = ['Shim.Session'];

}
```
It also contains the new `consume()` method.


## Helper

### Session
The session helper of the core is deprecated and will throw a warning as it will soon be removed.
Better use the plugin one right away. It is a 1:1 clone of it.
```php
namespace App\Controller;

use Tools\Controller\Controller;

class AppController extends Controller {

	public $helpers = ['Shim.Session'];

}
```
It also contains the new `consume()` method.

### Configure

If you have a lot of `Configure::read()` calls in your layout and templates you can either manually include the use statement everywhere, put a class_alias() hack in your bootstrap or just quickly replace the calls with `$this->Configure->read()` as helper call.
Just make sure your controller (or AppView) loads the helper:
```php
// Controller way
public $helpers = ['Shim.Configure'];

// AppView way
$this->loadHelper('Shim.Configure');
```
