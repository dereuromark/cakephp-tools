# Mobile component

The mobile component can hold the information of whether to serve a mobile layout based on session preference and browser headers.
```php
// In your controller (action)
$isMobile = $this->Mobile->isMobile();
```

You can provide a form/button in the bottom of the layout that can switch a session variable to overwrite the browser detection:
Just store the user's choice in the `'User.mobile'` session key.

## Configuration

    'on' => 'beforeFilter', // initialize (prior to controller's beforeRender) or startup
    'engine' => null, // CakePHP internal if null
    'themed' => false, // If false uses subfolders instead of themes: /View/.../mobile/
    'auto' => false, // auto set mobile views
    
