# Toggle Behavior

A CakePHP behavior to handle unique toggles.

An implementation of a unique field toggle per table or scope.
This will ensure that on a set of records only one can be a "toggled" one, setting the others to false then.
On delete it will give the toggle status to another record if applicable.

### Usage

#### Basic usage
Include behavior in your Table class as
```php
$this->addBehavior('Tools.Toggle', [
    'field' => 'primary', 
    'scopeFields' => ['user_id'],
    'scope' => [],
]);
```

Set the `field` to your table field. Optionally, you can also set scope fields and a manual scope. 
Those will always be taken into consideration as scope conditions.

### Configuration

The `'findOrder'` can be used to further customize the find result when looking for a new toggle record.
By default it will try to use the `modified` field as `DESC`. But maybe you would want it to sort by another field.
