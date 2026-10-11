# Mobile component

The component combines request detection with a session preference. It requires a `mobile` request detector, such as the optional [MobileDetect setup](/guide/install#optional-dependencies), or a callable `engine` option.

In the existing controller initialization:

```php
$this->loadComponent('Tools.Mobile');
```

In an action:

```php
$isMobile = $this->Mobile->isMobile();
```

`isMobile()` returns the detection result. `setMobile()` applies the session preference and stores the resulting choice in `User.setMobile` configuration.

The `mobile` query parameter stores an override in the session: `1` selects mobile, `0` selects desktop, and `-1` clears the preference. The session key is `User.mobile`.

## Theme selection

With `auto` enabled, the component calls `setMobile()` during its configured callback. A mobile choice selects the `Mobile` theme. Provide and load that theme plugin yourself; the component does not create templates or a mobile layout. It preserves the configured view class.

```php
$this->loadComponent('Tools.Mobile', ['auto' => true]);
```

With the default `auto => false`, call `setMobile()` explicitly if theme selection is desired. It exposes `mobileUrl` or `desktopUrl` to the view for switching the preference.

## Configuration

- `on`: `beforeFilter` by default; `initialize` runs detection during component initialization.
- `engine`: `null` uses `$request->is('mobile')`; a callable must return a detection result.
- `auto`: `false` by default.
- `themed`: retained as a legacy option; it does not change current theme selection.

For a responsive site, detection alone may be enough; CSS can handle the layout without selecting a separate theme.
