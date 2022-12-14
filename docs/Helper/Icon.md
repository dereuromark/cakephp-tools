# Icon Helper

A CakePHP helper to handle most common font icons. Contains convenience wrappers.

## Setup
Include helper in your AppView class as
```php
$this->addHelper('Tools.Icon', [
    ...
]);
```

You can store default configs also in Configure key `'Icon'`.

Make sure to set up at least one icon set:
- Bootstrap
- FontAwesome v4/v5/v6
- Material
- Feather

or your custom Icon class.

E.g.
```php
'Icon' => [
    'sets' => [
        'bs' => \Tools\View\Icon\BoostrapIcon::class,
        ...
    ],
],
```

## Usage

### render()
Display font icons using the default namespace or an already prefixed one.
```php
echo $this->Html->link(
    $this->Icon->render('view', $options, $attributes),
    $url,
);
```

Especially if you have multiple icon sets defined, any icon set after the first one would require prefixing:
```php
echo $this->Html->link(
    $this->Icon->render('bs:view', $options, $attributes),
    $url,
);
```

You can alias them via Configure for more usability:
```php
// In app.php
    'Icon' => [
        'map' => [
            'login' => 'bs:login',
            'logout' => 'bs:logout',
            'translate' => 'bs:translate',
            ...
        ],
    ],

// in the template
echo $this->Icon->render('translate', [], ['title' => 'Translate this']);
```
This way you can also rename icons (and map them in any custom way).

### names()
You can get a nested list of all configured and available icons.

For this make sure to set up the paths to the icon files as per each collector.
E.g.
```php
    'Icon' => [
        // For being able to parse the available icons
        'paths' => [
            'fa' => '/path/to/font-awesome/less/variables.less',
            'bs' => '/path/to/bootstrap-icons/font/bootstrap-icons.json',
            'feather' => '/path/to/feather-icons/dist/icons.json',
            'material' => '/path/to/material-symbols/index.d.ts',
            ...
        ],
    ],
```

You can then use this to iterate over all of them for display:
```php
$icons = $this->Icon->names();
foreach ($icons as $iconSet => $list) {
    foreach ($list as $icon) {
        ...
    }
}
```

## Tips

Use [IdeHelperExtra plugin](https://github.com/dereuromark/cakephp-ide-helper-extra/) to get full autocomplete for the icon names as input for `render($name)`.
This requires an IDE that can understand the meta data (e.g. PHPStorm).

## Demo
https://sandbox.dereuromark.de/sandbox/tools-examples/icons
