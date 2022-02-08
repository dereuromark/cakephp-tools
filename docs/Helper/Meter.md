# Meter Helper

A CakePHP helper to handle gauge calculation and output as meter (bar) element.
By default it supports HTML5 meter element - and as alternative or fallback uses unicode chars to work completely text-based.

The main advantage of the meter helper over default calculation is that you can decide on the overflow of min/max boundaries.
By default the max/min borders are kept and the value just cut to this boundary value.

Use the meter element to display data within a given range (a gauge).
Examples: Disk usage, the relevance of a query result, etc. Fixed values basically.

Note: The `<meter>` tag should not be used to indicate progress (as in a progress bar). Use Progress helper here.

## Setup
Include helper in your AppView class as
```php
$this->addHelper('Tools.Meter', [
    ...
]);
```

You can store default configs also in Configure key `'Meter'`.
Mainly empty/full chars can be configured this way.

## Usage

### htmlMeterBar()
Displays HTML5 element.
This is best used with the textual fallback if you are not sure everyone is using a modern browser.
See [browser support](https://www.w3schools.com/tags/tag_meter.asp).

```php
echo $this->Meter->htmlMeterBar(
    $value,
    $max,
    $min,
    $options,
    $attributes
);
```

### meterBar()
Display a text-based progress bar with the progress in percentage as title.
```php
echo $this->Meter->meterBar(
    $value,
    $max,
    $min,
    $length, // Char length >= 3
    $options,
    $attributes
);
```

### draw()
Display a text-based progress bar as raw bar.
```php
echo $this->Meter->draw(
    $percentage, // Value 0...1
    $length // Char length >= 3
);
```
This can be used if you want to customize the usage.

## Tips

Consider using CSS `white-space: nowrap` for the span tag if wrapping could occur to the textual version based on smaller display sizes.
Wrapping would render such a text-based progress bar a bit hard to read.
