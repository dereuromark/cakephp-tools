# Icon Helper

A CakePHP helper to handle most common font icons. Contains convenience wrappers.

## Setup
Include helper in your AppView class as
```php
$this->loadHelper('Tools.Icon', [
    ...
]);
```

You can store default configs also in Configure key `'Icon'`.

Make sure to set up at least one icon set:
- **Bootstrap**: npm package `bootstrap-icons`
- **FontAwesome** v4/v5/v6: npm package `fontawesome-free` for v6
- **Material**: npm package `material-symbols`
- **Feather**: npm package `feather-icons`

or your custom Icon class.

E.g.
```php
'Icon' => [
    'sets' => [
        'bs' => \Tools\View\Icon\BootstrapIcon::class,
        ...
    ],
],
```

For some Icon classes, there is additional configuration available:
- `namespace`: Some fonts offer different traits (light, bold, round, ...)

In this case make sure to use an array instead of just the class string:
```php
'Icon' => [
    'sets' => [
        'material' => [
            'class' => \Tools\View\Icon\MaterialIcon::class,
            'namespace' => 'material-symbols-round',
        ],
        ...
    ],
],
```

You can also set a global attributes config that would be merged in with every icon:
```php
'Icon' => [
    'sets' => [
        'material' => [
            'attributes' => [
                'data-custom' => 'some-custom-default',
            ],
        ],
        ...
    ],
    'attributes' => [
        'data-default' => 'some-default',
        ...
    ],
],
```

Don't forget to also set up the necessary stylesheets (CSS files) and alike.

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
        'view' => 'bs:eye',
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

For this make sure to set up the path config to the icon meta files as per each collector.
E.g.:
```php
'Icon' => [
    // For being able to parse the available icons
    'sets' => [
        'fa' => [
            ...
            'path' => '/path/to/font-awesome/less/variables.less',
        ],
        'bs' => [
            ...
            'path' => '/path/to/bootstrap-icons/font/bootstrap-icons.json',
        ],
        'feather' => [
            ...
            'path' => '/path/to/feather-icons/dist/icons.json',
        ],
        'material' => [
            ...
            'path' => '/path/to/material-symbols/index.d.ts',
        ],
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

## Configuration

You can enable `checkExistence` to ensure each icon exists or otherwise throws a warning in logs:
```php
'Icon' => [
    'checkExistence' => true,
    ...
],
```

## Tips

Check out [animations](https://fontawesome.com/docs/web/style/animate) and
other cool things you can add for FontAwesome icons, which are by far the
most powerful and most used ones.

Use [IdeHelperExtra plugin](https://github.com/dereuromark/cakephp-ide-helper-extra/) to get full autocomplete for the icon names as input for `render($name)`.
This requires an IDE that can understand the meta data (e.g. PHPStorm).

## Demo
https://sandbox.dereuromark.de/sandbox/tools-examples/icons

## Writing your own class
You mainly need to set up your own template string and how it should render:
```php
class YourIcon extends AbstractIcon {

	public function __construct(array $config = []) {
		$config += [
			'template' => '<span class="{{class}}...></span>',
		];

		parent::__construct($config);
	}

	public function render(string $icon, array $options = [], array $attributes = []): string {
	    ...
	}

	public function names(): array {
		$path = $this->path();

		return YourCollector::collect($path);
	}

}
```
Now you can hook it into your config and enjoy!
