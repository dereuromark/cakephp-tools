# Static enums

For new backed enums, see [native enums](/model/enum). `EnumTrait` supports existing entity constants and returns labels, full option arrays, or selected subsets.

Add the trait to an entity, for example `src/Model/Entity/Notification.php`:

```php
namespace App\Model\Entity;

use Cake\ORM\Entity;
use Tools\Model\Entity\EnumTrait;

class Notification extends Entity {
    use EnumTrait;

    public const STATUS_PENDING = 0;
    public const STATUS_SUCCESS = 1;
    public const STATUS_FAILURE = 2;

    public static function statuses(int|array|null $value = null): array|string|null {
        return static::enum($value, [
            self::STATUS_PENDING => __('Pending'),
            self::STATUS_SUCCESS => __('Success'),
            self::STATUS_FAILURE => __('Failure'),
        ]);
    }
}
```

Use an integer column suitable for the range of values. Database-specific tinyint widths are not a portable schema requirement; avoid a type that your driver interprets as boolean.

## Form options

```php
use App\Model\Entity\Notification;

echo $this->Form->control('status', ['options' => Notification::statuses()]);
```

An array argument selects and orders options:

```php
$options = Notification::statuses([
    Notification::STATUS_FAILURE,
    Notification::STATUS_SUCCESS,
]);
echo $this->Form->control('status', ['options' => $options]);
```

A scalar argument returns one label, or `null` for an unknown value. A `null` argument returns all options, so handle nullable entity values explicitly:

```php
echo $entity->status !== null ? h(Notification::statuses($entity->status)) : '';
```

Use the constants in normal ORM conditions:

```php
$notifications = $this->fetchTable('Notifications')->find()
    ->where(['status' => Notification::STATUS_PENDING])
    ->all();
```

The optional `Tools\Model\Entity\Entity` base class already includes the trait. When using the trait directly, call `static::enum()`; the core entity parent does not provide that method.
