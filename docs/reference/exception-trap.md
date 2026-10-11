# Exception logging

Tools can separate selected HTTP exceptions into a `404` log scope. The default mappings include not-found and unauthorized exceptions; [the source](https://github.com/dereuromark/cakephp-tools/blob/master/src/Error/ErrorHandlerTrait.php) defines the complete list.

## Configure logging

In `config/bootstrap.php`, configure the scoped logger:

```php
use Cake\Log\Log;

Log::setConfig('404', [
    'className' => 'File',
    'path' => LOGS,
    'file' => '404',
    'levels' => ['error'],
    'scopes' => ['404'],
]);
```

Set `scopes` to `null` in the existing `Log.debug` and `Log.error` entries in `config/app.php` if those engines should accept only unscoped messages. Preserve their other settings. An empty list accepts all scopes and can duplicate these records.

## Replace the exception trap

In `config/bootstrap.php`, replace the core `ExceptionTrap` import with `Tools\Error\ExceptionTrap`. Keep CakePHP's `ErrorTrap`:

```php
use Cake\Core\Configure;
use Cake\Error\ErrorTrap;
use Tools\Error\ExceptionTrap;

(new ErrorTrap(Configure::read('Error')))->register();
(new ExceptionTrap(Configure::read('Error')))->register();
```

In `Application::middleware()`, replace the existing core error middleware with the Tools middleware. Import `Tools\Error\Middleware\ErrorHandlerMiddleware` instead of its core counterpart and preserve the rest of the middleware queue:

```php
$middlewareQueue->add(new ErrorHandlerMiddleware(Configure::read('Error')));
```

Requests referred from the application's own configured host remain in the normal error log when the exception trap receives that request context. This helps identify broken internal links. External requests matching the configured mappings use the `404` scope.

## Customize mappings

`Error.log404` replaces the default exception list. For example, add this setting to the existing `Error` configuration to scope only not-found exceptions:

```php
'log404' => [Cake\Http\Exception\NotFoundException::class],
```

The [DatabaseLog monitor](https://dereuromark.github.io/cakephp-databaselog/reference/monitoring) can report recorded errors when database logging is configured.
