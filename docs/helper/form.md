# Form helper

The Tools form helper reads global defaults from `FormConfig`, including `novalidate` and widget templates.

Load it in the existing `AppView::initialize()` method:

```php
$this->loadHelper('Tools.Form');
```

Preserve other helper registrations. Controller `$helpers` properties are not the setup API for current CakePHP applications.

To disable browser validation while retaining server-side validation, add this entry to the array returned by `config/app.php`:

```php
'FormConfig' => [
    'novalidate' => true,
],
```

A `templates` option supplies CakePHP form templates. Use template names supported by the configured widgets; the former combined `dateWidget` example is not a current core widget template.
