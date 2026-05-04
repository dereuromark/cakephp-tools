# Table

The table class provides additional validation you can use:

- `validateUniqueExt()` to allow validating with multiple scoped fields with NULL values.
- `validateIdentical()`
- `validateDateTime()` with before/after.
- `validateDate()` with before/after.
- `validateTime()` with before/after.
- `validateUrl()` with auto-complete/deep.

## Related values in use

For pagination and filtering you often want to display selects.
Those would often then contain all possible values from the related table.

But for better UI/UX you usually don't want to display the values that yield in no results.
Only the ones actually used in the current context should be selectable.

`getRelatedInUse()` is doing that:

```php
$authors = $this->Posts->getRelatedInUse('Authors', 'author_id', 'list')
    ->toArray();
```

For optional columns used for relations, make sure to set IS NOT NULL conditions here.
```php
    ->getRelatedInUse('Related', null, 'list', ['conditions' => ['optional_relation_id IS NOT' => null]])
    ->toArray();
```


## Truncate

`truncate()` is a convenience wrapper to truncate a table.
