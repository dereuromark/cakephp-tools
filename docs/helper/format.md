# Format Helper

A CakePHP helper to handle some common format topics.

## Setup
Include helper in your AppView class as
```php
$this->loadHelper('Tools.Format', [
    ...
]);
```

You can store default configs also in Configure key `'Format'`.

## Usage

### yesNo()

Displays yes/no symbol for e.g. boolean values as more user-friendly representation.

### ok()

Display a colored result based on the 2nd argument being true or false.
```php
echo $this->Format->ok($text, $bool, $optionalAttributes);
```

### disabledLink()

Display a disabled link with a default title.

### array2table()

Translate a result array into a HTML table.
