# Url component and helper 

There is both a component and helper that help to work around some URL issues.

### Defaults
If you need to merge in defaults to your URLs, you can get the information from the `defaults()` method:

```php
// From inside a plugin controller
$$this->redirect(['controller' => 'Main', 'action' => 'index'] + $this->Url->defaults());
```
It will basically add in `'prefix' => false, 'plugin' => false`.

### Reset
You can in that case also just use the convenience method:
```php
$url = $this->Url->reset(['controller' => 'Main', 'action' => 'overview']);
```

Inside `/admin/plugin-name/example/action` the URL to redirect to would normally become `/admin/plugin-name/main/overview`.
With the reset() method it will become the desired: `/main/overview`.

### Complete
In both cases, however, the query strings are not passed on. If you want that, you can use the other convenience method:
```php
$url = $this->Url->complete(['controller' => 'Main', 'action' => 'overview']);
```
Now if there was a query string `?q=x` on the current action, it would also be passed along as `/main/overview?q=x`.


### Generating links
For generating links for those cases please see [Html helper](/docs/Helper/Html.md).
