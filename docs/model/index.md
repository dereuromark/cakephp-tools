# Model & Entity

Base classes and traits for the ORM layer.

| Topic | Purpose |
| --- | --- |
| [Table](/model/table) | Table base class with extra validation rules and quality-of-life additions. |
| [Tokens](/model/tokens) | One-time token storage (used by Login Links and similar flows). |
| [Enum](/model/enum) | Native PHP enum integration with entity properties. |
| [StaticEnum](/model/static-enum) | Static-method-based enum-like values on entities. |

> Native PHP enums are recommended on CakePHP 5+. Use [StaticEnum](/model/static-enum) only when interop with older code prevents enums.

Extend the Table and Entity base classes:

```php
use Tools\Model\Table\Table;

class UsersTable extends Table {}
```

```php
use Tools\Model\Entity\Entity;

class User extends Entity {}
```
