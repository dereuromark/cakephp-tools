---
description: Load one Tools helper and render a progress bar in an existing CakePHP template.
---

# Getting started

Complete [Installation](/guide/install), then try one helper in your application. Tools helpers, components, and behaviors can be loaded individually.

## Render a progress bar

In `src/View/AppView.php`, add the helper to your existing `initialize()` method. Keep other helper registrations:

```php
public function initialize(): void {
    parent::initialize();
    $this->loadHelper('Tools.Progress');
}
```

Add this to an existing template, such as `templates/Pages/home.php`:

```php
<label for="import-progress">Import progress</label>
<?php echo $this->Progress->htmlProgressBar(0.25, [], ['id' => 'import-progress']); ?>
```

Open the page using that template. It should display a progress bar at 25%. Values passed to the helper run from `0` to `1`; the rendered HTML element uses `value="25"` and `max="100"`. The [Progress helper](/helper/progress) also supports text bars and percentage calculations.

## Add a behavior where it is needed

For an existing `ArticlesTable` with `title` and `slug` columns, add this inside its `initialize()` method:

```php
$this->addBehavior('Tools.Slugged', [
    'label' => 'title',
    'field' => 'slug',
]);
```

`label` selects the input field; `field` selects the output column. This configuration keeps the title and writes a generated slug when the entity is saved. By default, existing slugs are preserved and uniqueness checks are disabled. Read [Slugged](/behavior/slugged) before using slugs for record lookup, changing existing slugs, or adding a uniqueness constraint.

## Choose the next tool

- [Common component](/component/common): trim request data or choose a local redirect target.
- [Behaviors](/behavior/): attach model operations to selected tables.
- [Helpers](/helper/): load rendering helpers in selected views.
- [Table and Entity extensions](/model/): optional base classes and enum utilities.
- [Tools backend](/guide/backend): enable developer pages when needed.
