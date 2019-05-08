# Typographic Behavior

A CakePHP behavior to handle typographic consistency.

The basic idea is to normalize all input into a standard typography (utf8 default).
So different quotes like `»` or `“` end up as `"` in the database.
Upon output one can the decide to re-apply localization here.

See the [TypographyHelper](/docs/Helper/Typography.md) docs for output modification.

### Usage

#### Basic usage
Include behavior in your Table class as
```php
$this->addBehavior('Tools.Typographic', [
    'fields' => ['content'], 
    'mergeQuotes' => false,
]);
```

Set the `fields` to your table fields you want to normalize.

### Configuration

With `mergeQuotes` option you can define if both `"` and `'` should be merged into one of them.
Defaults to `false` as they might be used nested for default input.
