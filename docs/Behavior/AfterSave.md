# AfterSave Behavior

A CakePHP behavior to allow the entity to be available inside afterSave() callback.

## Introduction
It takes a clone of the entity from beforeSave(). This allows all the
info on it to be available in the afterSave() callback or from the outside without resetting (dirty, ...).

### Technical limitation
Make sure you do not further modify the entity in the table's beforeSave() then. As this would
not be part of the cloned and stored entity here.

## Usage
Attach it to your model's `Table` class in its `initialize()` method like so:
```php
$this->addBehavior('Tools.AfterSave', $options);
```

Then inside your table you can do:
```php
public function afterSave(Event $event, EntityInterface $entity, ArrayObject $options) {
    $entityBefore = $this->getEntityBeforeSave();
    // Now you can check isDirty() etc
}
```

The same call could also be made from the calling layer/object on the table:
```php
$table->saveOrFail();
$entityBefore = $table->getEntityBeforeSave();
```

If you are using save(), make sure you check the result and that the save was successful.
Only call this method after a successful save operation.
Otherwise, there will not be an entity stored and you would get an exception here.
