# Migration from 4.x to 5.x

## CommonComponent
- `setHelpers()` has been removed in favor of core usage directly.

## Auth
- Login-link `urlChecker` now takes effect. The legacy `Authentication.CakeRouter` name maps to `Authentication.Default`; use `Authentication.Default` in new configuration.
- MultiColumn authentication has fully been moved to [TinyAuth](https://github.com/dereuromark/cakephp-tinyauth) plugin.

## Utility
- `L10n` was removed. `Tools\Utility\Mime` remains a deprecated compatibility class; use `Cake\Http\MimeType` on CakePHP 5.2 or later.
- Mutable `Time` class has been removed, use immutable `DateTime` instead.
- `Number` now lives in the I18n namespace. The Utility alias remains deprecated.
- `DateTime` now lives in the I18n namespace. The Utility alias remains deprecated.

## NumberHelper
- Custom engine has been moved from Utility to I18n namespace.

## TimeHelper
- Custom engine has been moved from Utility to I18n namespace.

## CakePHP 6: deprecated date formatting

`Tools\I18n\DateTime::localDate()` and `formatLocalized()` and `Tools\View\Helper\TimeHelper::localDateMarkup()` are removed. The obsolete `FORMAT_LOCAL_*` constants are also removed.

For localized output, use CakePHP date objects with `i18nFormat()`. Its format strings use ICU patterns, rather than PHP `date()` or `strftime()` patterns:

```php
$date = new \Cake\I18n\DateTime($value);
echo $date->i18nFormat('dd.MM.yyyy, HH:mm', 'Europe/Berlin', 'de_DE');
```

For Tools formatting with PHP `date()` patterns and a configured output timezone, use `DateTime::niceDate()` or `TimeHelper::niceDateMarkup()`. `niceDate()` returns its configured default for a null value; it does not treat null as the current time. Pass an explicit date object when you want the current time.

`localDate()` interpreted date strings in the supplied output timezone. `niceDate()` parses strings in PHP's default timezone and then converts them. To preserve an input wall-clock value, construct the date object with its timezone explicitly, for example `new DateTime($value, 'Europe/Berlin')`.

The `oclock` option in `niceDate()` appends text only for `FORMAT_NICE_YMDHM`, `FORMAT_NICE_YMDHMS`, `FORMAT_NICE_HM` and `FORMAT_NICE_HMS`. It does not append text for other formats containing `H:i`.
