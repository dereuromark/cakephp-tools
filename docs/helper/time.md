# Time helper

Load the helper in `AppView::initialize()`:

```php
$this->loadHelper('Tools.Time', [
    'outputTimezone' => 'Europe/Berlin',
    'default' => 'Unavailable',
]);
```

## Date, date and time, or time

Choose the method for the output you need:

```php
echo $this->Time->date($post->created);
echo $this->Time->dateTime($post->created);
echo $this->Time->time($post->created);
```

With no format argument, `date()` uses the locale's medium date style,
`dateTime()` uses medium date and short time styles, and `time()` uses the short
time style. The locale comes from CakePHP's date configuration unless you supply
`locale` in the options.

Custom formats use **ICU patterns**. For example, use `yyyy-MM-dd`, not the PHP
`date()` pattern `Y-m-d`:

```php
$options = ['timezone' => 'Europe/Berlin', 'locale' => 'de_DE'];
echo $this->Time->date($post->created, 'dd.MM.yyyy', $options);
echo $this->Time->dateTime($post->created, 'dd.MM.yyyy HH:mm', $options);
echo $this->Time->time($post->created, 'HH:mm', $options);
```

All five formatting methods accept date objects, datetime objects, Unix timestamps,
strings accepted by CakePHP's datetime constructor, or null. Datetime strings are
parsed in PHP's default timezone unless they specify their own timezone. The
`timezone` option controls output, not parsing. To specify the input timezone,
pass an object:

```php
$value = new \Cake\I18n\DateTime('2026-01-15 12:00:00', 'UTC');
echo $this->Time->dateTime($value, 'yyyy-MM-dd HH:mm', [
    'timezone' => 'Europe/Berlin',
]);
// 2026-01-15 13:00
```

`Cake\I18n\Date` objects and exact `YYYY-MM-DD` strings represent calendar dates.
Their day stays unchanged across output timezones. If passed to `dateTime()` or
`time()`, they represent midnight in the output timezone. Datetime inputs are
converted to the output timezone before formatting; input objects stay unchanged.
If no output timezone is configured, datetime objects retain their own timezone.

Null returns the helper's `default` configuration, which defaults to `-----`.
Override it for a single call with `['default' => 'n/a']` or an empty string.
Pass an explicit `new \Cake\I18n\DateTime()` to show the current time.

## Markup

`dateMarkup()` and `dateTimeMarkup()` wrap the formatted text in a `<span>`.
They add `class="today"` when the value falls on the current day in the output
timezone. Null fallbacks get no `today` class. Both methods escape the formatted
text and fallback, so they can be echoed in a template:

```php
echo $this->Time->dateMarkup($post->created, 'dd.MM.yyyy');
echo $this->Time->dateTimeMarkup($post->created, 'dd.MM.yyyy HH:mm');
```

The plain methods return text without HTML escaping. Escape them with `h()` when
rendering patterns or fallbacks that contain user input.

## Existing formatters

`nice()`, `niceDate()` and `niceDateMarkup()` remain available for existing code.
`niceDate()` and `niceDateMarkup()` use PHP format patterns and can choose date
or datetime output from the input type. The explicit methods above use ICU patterns
and select output by method name. See the [migration guide](/guide/upgrade).
