# FileLog

`Tools\Utility\FileLog` writes debug messages to a named file under `LOGS`, without configuring a separate logger for each filename.

```php
use Tools\Utility\FileLog;

FileLog::write('Import started', 'imports');
FileLog::write(['user_id' => 42, 'result' => 'saved'], 'imports');
```

These calls append to `imports.log`. Arrays and objects are formatted with `print_r()`. Omitting the filename writes to `custom_log.log`. The method returns whether logging succeeded.

The utility temporarily replaces the `custom` logger and disables the configured `debug` logger while writing. Other engines that accept the `custom` scope may also receive the message; this utility does not guarantee exclusive delivery to one file.

For a persistent application logger, CakePHP's scoped logging is an alternative. In `config/bootstrap.php`:

```php
use Cake\Log\Log;

Log::setConfig('imports', [
    'className' => 'File',
    'path' => LOGS,
    'levels' => ['debug'],
    'scopes' => ['imports'],
    'file' => 'imports',
]);
Log::write('debug', 'Import started', ['scope' => 'imports']);
```

Set `scopes` to `null` on other application loggers that should accept only unscoped messages. An empty scope list accepts every scope.
