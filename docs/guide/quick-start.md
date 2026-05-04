# 5-min Quick Start

Get the most common Tools features wired up in five minutes.

## 1. Install

```bash
composer require dereuromark/cakephp-tools
bin/cake plugin load Tools
```

## 2. Use the Tools base classes

In every Table, extend the Tools `Table`:

```php
namespace App\Model\Table;

use Tools\Model\Table\Table;

class UsersTable extends Table {}
```

In every Entity, extend the Tools `Entity`:

```php
namespace App\Model\Entity;

use Tools\Model\Entity\Entity;

class User extends Entity {}
```

You now have access to the extra validation rules ([URL](/reference/url), email/phone formats, ranges), `tokens` integration, native [Enum](/model/enum) helpers — see [Table](/model/table) for the full list.

## 3. Auto-trim POSTs and load the helpers

In `AppController`:

```php
namespace App\Controller;

use Tools\Controller\Controller;

class AppController extends Controller {
    public function initialize(): void {
        parent::initialize();
        $this->loadComponent('Tools.Common');  // auto-trim POST data so notEmpty validation works
    }
}
```

In `AppView`:

```php
public function initialize(): void {
    parent::initialize();
    $this->loadHelper('Tools.Common');
    $this->loadHelper('Tools.Format');
}
```

That's the minimum — `Common` covers misc rendering, `Format` covers numbers/dates/badges/status pills.

## 4. Add a behavior to a Table

Every behavior is a one-liner in `initialize()`:

```php
class ArticlesTable extends Table {
    public function initialize(array $config): void {
        parent::initialize($config);
        $this->addBehavior('Tools.Slugged', ['field' => 'title']);
    }
}
```

Browse the [behaviors index](/behavior/) for the full list — each page has a minimal config example.

## 5. Where to next

- [Behaviors](/behavior/) — Slugged, Bitmasked, Passwordable, Jsonable, Reset, Toggle, AfterSave, String, Encryption, Typographic
- [Helpers](/helper/) — Common, Format, Form, Html, Tree, Progress, Meter, Typography, Icon
- [Components](/component/) — Common, Mobile, RefererRedirect
- [Live Sandbox](https://sandbox.dereuromark.de/sandbox/tools-examples) — runnable examples for most features
- [Plugin Ecosystem](/guide/ecosystem) — how Tools fits with Shim, Setup, IDE Helper, and friends
