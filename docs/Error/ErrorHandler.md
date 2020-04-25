# Improved version of ErrorHandler

The main goal of the error.log is to notify about internal errors of the system.
By default there would also be a lot of noise in there.

Most 404 logs should not be part of your error log, for example.
You can either completely ignore them, or better yet put them into their own space:
```php
Log::config('404', [
    'className' => '...', // e.g. 'File' or 'DatabaseLog.Database'
    'file' => '404',
    'levels' => ['error'],
    'scopes' => ['404'],
]);
```

Make sure your other log configs are scope-deactivated then to prevent them being 
logged twice (`config/app.php`):
```php
    'Log' => [
        'debug' => [
            'scopes' => false,
            ...
        ],
        'error' => [
            'scopes' => false,
            ...
        ],
    ],
```

In your `config/bootstrap.php`, the following snippet just needs to include the 
ErrorHandler of this plugin:
```php
// Switch Cake\Error\ErrorHandler to
use Tools\Error\ErrorHandler;

if ($isCli) {
    (new ConsoleErrorHandler(Configure::read('Error')))->register();
} else {
    (new ErrorHandler(Configure::read('Error')))->register();
}
```

Also, make sure to switch out the middleware:
```php
use Cake\Http\BaseApplication;
// Switch Cake\Error\Middleware\ErrorHandlerMiddleware to
use Tools\Error\Middleware\ErrorHandlerMiddleware;

class Application extends BaseApplication {

    /**
     * @param \Cake\Http\MiddlewareQueue $middlewareQueue Middleware queue.
     *
     * @return \Cake\Http\MiddlewareQueue The updated middleware queue.
     */
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue {
        $middlewareQueue
            // Replace the core one
            ->add(new ErrorHandlerMiddleware())
            ...

        return $middlewareQueue;
    }
```

Note that internally caused 404s (referrer is a page on the own site) are not transferred into the 404 log.
In that case you are having invalid links in your pages somewhere, which should be fixed.
So those are considered actual errors here.

### Adding more exceptions

In case you need custom 404 mappings for some additional custom exceptions, 
make use of `log404` option in your `app.php`.
It will overwrite the current defaults completely.
```php
    'Error' => [
        'log404' => [
            ... // List of FQCN class names
        ],
    ],
```

### Tips

You can also set up a monitor to check for the internal 404s and alert (via email or alike).
Those should also be fixed rather soon because more and more people click on those and end up on the error page.
All other 404s are caused from the outside (often times crawlers and bots) and are usually not too relevant.

One possibility is [this](https://github.com/dereuromark/CakePHP-DatabaseLog/tree/master/docs#monitor) if you use database logging.
