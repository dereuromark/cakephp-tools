# Format Helper

A CakePHP helper to handle some common format topics.

## Setup
Include helper in your AppView class as
```php
$this->addHelper('Tools.Format', [
    ...
]);
```

You can store default configs also in Configure key `'Format'`.

## Usage

### icon()
Display font icons using the default namespace or an already prefixed one.
```php
echo $this->Html->link(
    $this->Format->icon('view'), 
    $url, 
    $attributes
);
```

Font Awesome v4 works out of the box.
For v5 you want to use a custom namespace and prefix (either in app.php or in AppView.php):
```php
$this->loadHelper('Tools.Format', [
    'iconNamespace' => 'fas',
    'autoPrefix' => 'fa',
]);
```

You can alias them via Configure for more usability:
```php
// In app.php
    'Format' => [
        'fontIcons' => [
            'login' => 'fa fa-sign-in',
            'logout' => 'fa fa-sign-out',
            'translate' => 'fa fa-language',
        ],
    ],
    
// in the template
echo $this->Format->icon('translate', ['title' => 'Translate this']);
```

### yesNo()

Displays yes/no symbol for e.g. boolean values as more user friendly representation.

### ok()

Display a colored result based on the 2nd argument being true or false.
```php
echo $this->Format->ok($text, $bool, $optionalAttributes);
```

### disabledLink()

Display a disabled link with a default title.

### array2table()

Translate a result array into a HTML table.
