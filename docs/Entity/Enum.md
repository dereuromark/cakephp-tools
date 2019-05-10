# Static Enums

Enum support via trait and `enum()` method.

## Intro

There are many cases where an additional model + table + relation would be total overhead. Like those little "status", "level", "type", "color", "category" attributes.
Often those attributes are implemented as "enums" in SQL - but cake doesn't support them natively. And it should not IMO. You might also want to read [this](http://komlenic.com/244/8-reasons-why-mysqls-enum-data-type-is-evil/) ;)

If there are only a few values to choose from and if they don't change very often, you might want to consider the following approach.
It is very efficient and easily expandable on code level.

Further advantages
- can be used from anywhere (model, controller, view, behavior, component, ...)
- reorder them dynamically per form by just changing the order of the array keys when you call the method.
- auto-translated right away (i18n without any translation tables - very fast)
- create multiple static functions for different views ("optionsForAdmins", "optionsForUsers" etc). the overhead is minimal.


## Setup

Add tinyint(2) unsigned field called for example "status" (singular).
"tinyint(2) unsigned" covers 0...127 / 0...255 - which should always be enough for enums. 
if you need more, you SHOULD make an extra relation as real table. 

Do not use tinyint(1) as CakePHP interprets this as a (boolean) toggle field, which we don't want!


## Usage

Add the trait to your entity:
```php
use Tools\Model\Entity\EnumTrait;

class MyEntity extends Entity {

    use EnumTrait;
```

Then add your enums like so:

```php
    /**
     * @param int|array|null $value
     *
     * @return array|string
     */
    public static function statuses($value = null) {
        $options = [
            static::STATUS_PENDING => __('Pending'),
            static::STATUS_SUCCESS => __('Success'),
            static::STATUS_FAILURE => __('Failure'),
        ];
        return parent::enum($value, $options);
    }

    public const STATUS_PENDING = 0;
    public const STATUS_SUCCESS = 1;
    public const STATUS_FAILURE = 2;    
```

You can now use it in the forms in your templates:
```php
<?= $this->Form->create($entity) ?>
...
<?= $this->Form->control('status', ['options' => $entity::statuses()]) ?>
```

And in your index or view:
```php
echo $entity::statuses($entity->status);
```

Make sure the property is not null (or it would return an array). Best to check for it before or combine 
it with Shim plugin GetTrait and `$entity->getStatusOrFail()`:

```php
// Allowed to be empty
echo $entity->status !== null ? $entity::statuses($entity->status) : $default;

// Required or throw exception
echo $entity::statuses($entity->getStatusOrFail());
```

You can also use it anywhere else for filtering, or comparison:
```php
use App\Model\Entity\Notification;

$unreadNotifications = $this->Notifications->find()
    ->where(['user_id' => $uid, 'status' => Notification::STATUS_UNREAD)])
    ->all();
```

### Subset or custom order
You can reorder the choices per form by passing a list of keys in the order you want.
With this, you can also filter the options you want to allow:
```php
<?= $this->Form->control('status', [
    'options' => $entity::statuses([$entity::STATUS_FAILURE, $entity::STATUS_SUCCESS]),
]) ?>
```



## Bake template support

Use the [Setup](https://github.com/dereuromark/cakephp-setup) plugin (`--theme Setup`) to 
get auto-support for your templates based on the existing enums you added.

The above form controls would be auto-added by this.

## Background

See [Static Enums](http://www.dereuromark.de/2010/06/24/static-enums-or-semihardcoded-attributes/).

## Bitmasks

If you are looking for combining several booleans into a single database field check out my [Bitmasked Behavior](http://www.dereuromark.de/2012/02/26/bitmasked-using-bitmasks-in-cakephp/).
