# Bitmasked Behavior

A CakePHP behavior to allow quick row-level filtering of models via bitmasks.

## Introduction
Basically it encodes the array of bit flags into a single bitmask on save and vice versa on find.
I created it as an extension of my pretty well working Enum stuff. It can use this type of enum declaration for our bitmask, as well.
It uses constants as this is the cleanest approach to define model based field values that need to be hardcoded in your application.

### Technical limitation
The theoretical limit for a 64-bit integer [SQL: BIGINT unsigned] would be 64 bits (2^64).
Don’t use bitmasks if you seem to need more than a hand full, though.
Then you obviously do something wrong and should better use a join table.
I highly recommend using `tinyint(3) unsigned` which can hold up to 8 bits – more than enough. It still only needs 1 byte.


## Usage
Attach it to your model's `Table` class in its `initialize()` method like so:
```php
$this->addBehavior('Tools.Bitmasked', $options);
```

If you want to alias the field for output:
```php
$this->addBehavior('Tools.Bitmasked', ['mappedField' => 'statuses', 'field' => 'status']);
```

The `mappedField` param is quite handy if you want more control over your bitmask.
It stores the array under this alias and does not override the bitmask key.
So in our case status will always contain the integer bitmask and statuses the verbose array of it.

Note: If you use an alias, make sure that either also that alias is whitelisted for patching,
or you don't use `beforeMarshal` event here.

### Defining the selectable values
We first define values and make sure they follow the bitmask scheme:
```
1, 2, 4, 8, 16, 32, 64, 128, ...
```

I recommend using a DRY [enum approach](https://www.dereuromark.de/2010/06/24/static-enums-or-semihardcoded-attributes/), using your entity:
```php
// A bunch of bool values
public const STATUS_ACTIVE = 1;
public const STATUS_FEATURED = 2;
public const STATUS_APPROVED = 4;
public const STATUS_FLAGGED = 8;

...

public static function statuses($value = null) {
    $options = [
        self::STATUS_ACTIVE => __('Active'),
        self::STATUS_FEATURED => __('Featured'),
        self::STATUS_APPROVED => __('Approved'),
        self::STATUS_FLAGGED => __('Flagged'),
    ];
    return parent::enum($value, $options);
}
```

Please note that you need to define Entity::enum() by extending my Tools Entity base class or by putting it into your own base class manually. You don’t have to use the enum approach, though.

Of course, it only makes sense to use bitmasks, if those values can co-exist, if you can select multiple at once. Otherwise you would want to store them separately anyway.
Obviously you could also just use four or more boolean fields to achieve the same thing.

So now, in the add/edit form we can:
```php
echo $this->Form->control('statuses', ['options' => Comment::statuses(), 'multiple' => 'checkbox']);
```

Tip: Usually, you have passed down the current entity for the form building anyway, then you don't need static access:
```php
echo $this->Form->create($comment);
echo $this->Form->control('statuses', ['options' => $comment->statuses(), 'multiple' => 'checkbox']);
...
```

It will save the final bitmask to the database field `status` as integer. For example "active and approved" would become `9`.

Note: When using `mappedField`, one needs to manually include error handling for the actual field:
```php
echo $this->Form->control('statuses', ['type' => 'select', 'multiple' => 'checkbox']);
echo $this->Form->error('status');
```
Alternatively, you can map the error over before the entity gets passed to the view layer.

### Custom finder
You can use the built in custom finder `findBitmasked`:
```php
$statuses = [Comment::STATUS_ACTIVE, Comment::STATUS_FEATURED];
$comments = $this->Comments->find('bits', ['bits' => $statuses])->toArray();
```

#### Using Search plugin
If you use [Search](https://github.com/FriendsOfCake/search/) plugin, you can easily make a filter as multi-checkbox (or any multi-select type):
```php
echo $this->Form->control('status', ['options' => Comment::statuses(), 'multiple' => 'checkbox', 'empty' => ' - no filter - ']);
```

And in your Table searchManager() setup:
```php
$searchManager
    // We need to map the posted "status" key to the finder required "bits" key
    ->finder('status', ['finder' => 'bits', 'map' => ['bits' => 'status']])
```

This way the array of checkboxes selected will be turned into the integer bitmask needed for the query to work.

When using select dropdows, you usually want to use type `contain` instead of `exact` matching:
```php
$this->Comments->find('bits', ['bits' => $statuses, 'type' => 'contain])->toArray();
```

#### Custom usage

If you build more complex finders or queries for your data, you might find the following info useful:

"contains" (looking for any that contains this type) is translated to `field & {type} = {type}` in the ORM, e.g. `status & 1 = 1`.
Once you are looking for a combination of types, it will be an `OR` of those elements, e.g. `status & 1 = 1 OR status & 2 = 2`.

Using the finder you do not have to care about the SQL details here, as it will translate to this automatically.

"exact" (default) only looks at the values exclusively. If you are looking for multiple ones at once that are exclusive, this will need to be translated to `IN (...)` using only the type integers directly (not the bitmasked combinations), e.g. `IN (1, 2, 4`).


### Configuration

The default `onMarshal` expects you to require validation (not empty, ...) on this field.
If you don't need that, and it is nullable, you can also set the event to e.g. `afterMarshal`.

If you use `fields` config to whitelist the fields for patching, you should also whitelist
the alias field if you defined one and if you are using `onMarshal`.

### Outview

You can read more about how it began in [my blog post](https://www.dereuromark.de/2012/02/26/bitmasked-using-bitmasks-in-cakephp/).

If you want to use a more DB or Config driven approach towards enums, you can also look into other plugins and CakePHP resources available, e.g. [this](https://github.com/CakeDC/Enum) implementation.
