# Controller

The optional `Tools\Controller\Controller` base class reads pagination defaults from `Configure::read('Paginator')`. Existing controller pagination settings take precedence.

Add this entry to the array returned by `config/app.php`:

```php
'Paginator' => [
    'limit' => 25,
],
```

To use the extension, make the application's `AppController` extend `Tools\Controller\Controller`. Keep its existing callbacks and application access controls. This base-class change is optional and is not required to load individual Tools components.
