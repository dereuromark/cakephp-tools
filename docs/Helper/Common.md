# Common Helper

A CakePHP helper to handle some common topics.

### Setup
Include helper in your AppView class as
```php
$this->addHelper('Tools.Common', [
    ...
]);
```

### Singular vs Plural
```php
echo $this->Common->sp('Singular', 'Plural', $count, true);
```
If using explicit translations or if no I18n translation is necessary, you don't need the 4th argument:

```php
echo $this->Common->sp(__('Singular'), __('Plural'), $count);
```

### Meta tags

Canonical URL:
```php
echo $this->Format->metaCanonical($url);
```

Alternate content URL:

```php
echo $this->Format->metaAlternate($url, $language);
```

RSS link:
```php
echo $this->Format->metaRss($url, $title);
```

Generic meta tags:

```php
echo $this->Format->metaEquiv($type, $value, $escape)
```
