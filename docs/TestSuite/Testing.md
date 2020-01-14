# Useful TestSuite additions

Let's you test even faster.

## IntegrationTestCase

You can globally set
```php
    protected $disableErrorHandlerMiddleware = true;
```

This is a quick way to disable all error handler middlewares on this integration test case.
