# Migration from 2.x to 3.x: Shims

## Session
The session component of the core is deprecated and will throw a warning as it will soon be removed.
Better use the plugin one right away. It is a 1:1 clone of it.
```php
namespace App\Controller;

use Tools\Controller\Controller;

class AppController extends Controller {

	public $components = array('Tools.Session');

}
```
It also contains the new consume() method.

## Model
The following can be used in 3.x via shim support:

### Used to be on models => now on tables
- $order property
- $validate property
- relations (hasX, belongsTo)
- $displayField
- $primaryKey
- Timestamp behavior added by default (if modified or created exists)