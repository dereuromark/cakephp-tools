# Typography Helper

A CakePHP helper to handle typographic consistency.

The basic idea is to normalize all input into a standard typography (utf8 default).
So different quotes like `»` or `“` end up as `"` in the database.
See the [TypographicBehavior](/docs/Behavior/Typographic.md) docs for input modification.

Upon output one can the decide to re-apply localization here.

### Usage

#### Basic usage
Include helper in your AppView class as
```php
$this->addHelper('Tools.Typography', [
    ...
]);
```

Then you can use it in your templates as
```php
echo $this->Typography->autoTypography($myText);
```

### Configuration

It uses Configure key `'App.language'` by default to detect the output format.
So if you have `Configure::write('App.language', 'deu');` in your bootstrap, it will use German typography.
A string `"Interesting Quote"` will then become the German `„Interesting Quote‟`.
English (US) would be `“Interesting Quote”` and French, for example, `«Interesting Quote»`.
