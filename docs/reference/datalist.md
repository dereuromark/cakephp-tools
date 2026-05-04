# Datalist Widget 

Many of the HTML 5 new widgets are automatically supported by CakePHP.
Unfortunatelly [datalist](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/datalist) is not supported by default.

This widget adds support for basic datalist support using the values or keys of the options array.
If you need shimming for older browsers, add your JS snippet for this as polyfill yourself.

### Setup

Enable the following widget in your Form helper config:
```php
'datalist' => ['Tools\View\Widget\DatalistWidget'],
```

Add the following template to your Form helper templates:
```php
'datalist' => '<input type="text" list="datalist-{{id}}"{{inputAttrs}}><datalist id="datalist-{{id}}"{{datalistAttrs}}>{{content}}</datalist>',
```

Config:
 - keys: Use as true to use the keys of the select options instead of the values.
 - input: Attributes for input element

### Usage

```php
echo $this->Form->control('search', ['type' => 'datalist', 'options' => $options]);
```

It will generate the above input with a datalist element.

If you want to use the keys instead of the values:
```php
echo $this->Form->control('search', ['type' => 'datalist', 'options' => $options, 'keys' => true]);
```

You can pass input attributes using the `'input'` key, e.g. 
`'input' => ['placeholder' => 'My placeholder']`.

### Advanced usage
You could also auto-create new entries right away.
See [rrd108/cakephp-datalist](https://github.com/rrd108/cakephp-datalist) for this.

Credits for this widget go to him for discovering the basics here.
