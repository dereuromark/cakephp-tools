# Mailer

Use `Tools\Mailer\Mailer` to extend CakePHP's mailer with attachment helpers and a per-mailer locale. The former `Tools\Mailer\Email` class is not available.

## Basic usage

Use an existing email profile and transport configured in the application's `config/app.php`:

```php
use Tools\Mailer\Mailer;

$mailer = new Mailer('default');
$mailer->setFrom('sender@example.com', 'Application');
$mailer->setTo('recipient@example.com');
$mailer->setSubject('Import complete');
$result = $mailer->deliver('Your import has finished.');
```

`deliver()` and `send()` return CakePHP's result array. Delivery exceptions propagate; there is no boolean success result or `getError()` method. Use `send()` for a configured mailer action and `deliver()` for message content.

## Defaults and test delivery

The message uses `Config.systemEmail` and `Config.systemName` as its default sender, falling back to `Config.adminEmail` and `Config.adminName`. Explicit `setFrom()` overrides these defaults. `Config.xMailer` adds an `X-Mailer` header.

When constructing a mailer without a profile and `Config.live` is exactly `false`, Tools selects the `test` profile. Configure that profile with a debug transport before using this mode. Passing a profile explicitly bypasses this selection. Tools does not automatically replace recipient addresses in debug mode.

## Attachments

```php
$mailer->addAttachment('/absolute/path/report.pdf');
$mailer->addBlobAttachment($pngBytes, 'chart.png', 'image/png');
$contentId = $mailer->addEmbeddedAttachment('/absolute/path/logo.png');
```

Use the returned content ID in an HTML email template, for example `src="cid:..."`. The message determines missing MIME types and handles embedded attachment metadata.

## Locale and diagnostics

`setLocale()` selects a locale while running `send()`, then restores the previous locale, including when sending throws. `getDebug()` returns the last result from `deliver()`. Use CakePHP's mailer logging and exception handling for failures.
