# Form Helper

An enhanced FormHelper
- Allow configuration via Configure `FormConfig`
- Allow easy enabling/disabling of `novalidate` this way globally throughout all forms

## Configs
- 'novalidate' => false, // Set to true to disable HTML5 browser validation
- 'templates' => [...] // Define your own custom default templates for all widgets

## Usage
Attach it to your controllers like so:
```php
public $helpers = ['Tools.Form'];
```

Alternatively, you can enable it in your AppView class.

### Basic Example
```php
// Inside your app.php config:
$config = [
    'debug' => true,
    ...
    'FormConfig' => array(
        'novalidate' => true,
        'templates' => array(
            'dateWidget' => '{{day}}{{month}}{{year}}{{hour}}{{minute}}{{second}}{{meridian}}',
        )
    )
];
```
