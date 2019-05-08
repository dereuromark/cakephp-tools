# EnumTrait

Enum support via trait and `enum()` method.

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


## Bake template support

Use the [Setup](https://github.com/dereuromark/cakephp-setup) plugin (`--theme Setup`) to 
get auto-support for your templates based on the existing enums you added.

The above form controls would be auto-added by this.

## Background

See [Static Enums](http://www.dereuromark.de/2010/06/24/static-enums-or-semihardcoded-attributes/).
