# DateTime and Date

This package ships with improved
- DateTime
- Date

extending the core value objects.

They can also work with select/dropdown date(time) form controls and provide other improvements.

## Validation

For improved validation, the Tools Table - that you can extend in your tables - ships with
- `validateDateTime()`
- `validateDate()`
- `validateTime()`

They provide additional configs to work with:
- `timeFormat`
- `after`/`before` (field name to validate against)
- `min`/`max`

## Relative length of time

`DateTime::relLengthOfTime()` renders a date relative to now (or a custom reference) in
human readable units, e.g. `4 Days, 5 Hours ago` or `In 2 Weeks`. The difference is
calendar-exact (based on the actual dates), so months and years respect calendar lengths
and leap years.

```php
use Tools\I18n\DateTime;

DateTime::relLengthOfTime($post->created);
// => '11 Years, 3 Months ago'

DateTime::relLengthOfTime($post->created, null, ['accuracy' => 1]);
// => '11 Years ago'

DateTime::relLengthOfTime($date, null, ['from' => $referenceDate]);
// relative to a custom reference instead of now
```

Options:
- `accuracy` (int, default `2`): maximum number of units displayed. Units scale
  automatically: seconds, minutes, hours, days, weeks, months, years.
- `from` (datetime, default now): reference point to compare against.
- `past`/`future` (string with `%s`, or `false`): wrapping templates, default `%s ago`/`In %s`.
- `verbose`: string returned for a zero difference (default `justNow` translation).
- `context` (string, default `relative`): translation msgctxt for the unit words. This
  allows grammatically correct wording depending on position, e.g. German dative
  `Vor 4 Tagen` via a `relative`-context translation of `Days` => `Tagen`, while the
  plain `Days` => `Tage` stays untouched for standalone durations.
- `separator`, `zero`, `default`: formatting details.

The same engine drives `DateTime::lengthOfTime()` for plain durations in seconds
(approximating months as 30 and years as 365 days):

```php
DateTime::lengthOfTime(10 * 86400);                        // => '1 Week, 3 Days'
DateTime::lengthOfTime(400 * 86400, null, ['accuracy' => 1]); // => '1 Year'
DateTime::lengthOfTime(4 * 86400 + 3600, null, ['verbose' => false]); // => '4d, 1h'
```

Both are also available through the Time helper: `$this->Time->relLengthOfTime(...)`.

## SQL date ranges

SQL builders live in `Tools\Utility\DateTimeSql`, separate from the date value
objects:

```php
use Tools\Utility\DateTimeSql;

$sql = DateTimeSql::dayAsSql('2026-01-15', 'Posts.created');
// (Posts.created >= '2026-01-15 00:00:00') AND (Posts.created <= '2026-01-15 23:59:59')

$sql = DateTimeSql::daysAsSql('2026-01-15', '2026-01-20', 'Posts.created');
$sql = DateTimeSql::periodAsSql('2026-01', 'Posts.created');
```

These methods return SQL fragments. Supply a trusted database field name from
application code. They preserve the previous behavior: the timezone argument
controls parsing, and SQL calendar boundaries are rendered in PHP's default
timezone. They do not convert ranges to a separate database timezone.

For localized view output, use the [Time helper](/helper/time).
