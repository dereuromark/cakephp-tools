# Guide

The Tools plugin extends CakePHP with the small-to-medium utilities you keep writing across projects: model & entity base classes, view helpers, behaviors, components, and a set of shims to ease 4.x → 5.x migrations.

## Setup

- [Installation](/guide/install) — composer require, plugin loading, optional config.
- [Upgrade Guide](/guide/upgrade) — moving from 4.x to 5.x.
- [Shims](/guide/shims) — BC shims for 4.x model properties (validation, etc.).
- [Tools Backend](/guide/backend) — opt-in admin backend for inspecting parts of the toolbox.

## Basic enhancements

Extend the Tools plugin Table and Entity base classes to pick up a few defaults:

```php
namespace App\Model\Table;

use Tools\Model\Table\Table;

class UsersTable extends Table {}
```

```php
namespace App\Model\Entity;

use Tools\Model\Entity\Entity;

class User extends Entity {}
```

In your `AppController`, load the Common component:

```php
use Tools\Controller\Controller;

class AppController extends Controller {
    public function initialize(): void {
        parent::initialize();
        $this->loadComponent('Tools.Common');
    }
}
```

In your `AppView`, the most useful helpers in one place:

```php
public function initialize(): void {
    parent::initialize();
    $this->loadHelper('Tools.Common');
    $this->loadHelper('Tools.Format');
}
```

The Common component auto-trims POST data so `notEmpty` validation behaves predictably. The Tools controller adds a few small fixes (e.g. cache disabling that also covers older browsers).

## Where to next

Pick a section from the sidebar:

- [Behaviors](/behavior/) — 10 ORM behaviors.
- [Helpers](/helper/) — 9 view helpers.
- [Components](/component/) — 3 controller components.
- [Model](/model/) — Table base, Tokens, Enum, StaticEnum.
- [Reference](/reference/) — Authentication, Mailer, Controller, Command, I18n, URL, Error, Utility, Widget.
