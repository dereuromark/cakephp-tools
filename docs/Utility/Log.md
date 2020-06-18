# Log

Log class let's you write logs into custom log files.

#### With default `Cake\Log\Log` class:

To write log data into custom file with default CakePHP `Cake\Log\Log` class feels like repeating ourselves.

1. Configure FileLog Adapter:
```php
use Cake\Log\Log;

Log::config('custom_file', [
    'className' => 'File',
    'path' => LOGS,
    'levels' => ['debug'],
    'file' => 'my_file.log',
]);
```

2. Write logs into file:
```php
Log::write('debug', "Something didn't work!");
```

With above approach, we have multiple issues:
- It surely logs data into `custom_file.log` file but also it will log into level specific `$level.log` file too, so we end up duplicating the log data.
- When we have to write more logs into same file from different parts of the project, we have to copy-paste this code every time.

Or you hack it with doing something like setting configurations in `bootstrap.php` and use scope to log data. But each time you start new project you have to remember to copy paste that config and use in your project in order to write data into custom log files.

#### With `Tools\Utility\Log` class:

You can directly pass data to log and filename to write the data into.

##### Usage:

```php
use Tools\Utility\Log;

Log::write("Something didn't work!", 'my_file');

// Somewhere else in any file
Log::write([
    'user' => [
        'id' => '1',
        'name' => 'John',
        'email' => 'john@example.com',
    ]
], 'user_data');
```

That's it! Above will create two separate files in `log/` directory named `my_file.log` and `user_data.log` store data into which we passed in first argument.

You can write string, array, objects, etc into log files. It will pretty print your array/object so it's more readable. Also, it will not duplicate records into `$level.log` file.
