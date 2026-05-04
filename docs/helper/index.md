# Helpers

View helpers covering the most frequent rendering needs.

| Helper | Purpose |
| --- | --- |
| [Common](/helper/common) | Grab-bag of frequently-used view utilities. |
| [Format](/helper/format) | Data formatting (numbers, dates, badges, status indicators). |
| [Form](/helper/form) | Extensions on top of the core FormHelper. |
| [Html](/helper/html) | Extensions on top of the core HtmlHelper. |
| [Icon](/helper/icon) | Icon rendering (deprecated — see the dedicated [Icon plugin](https://github.com/dereuromark/cakephp-icon)). |
| [Meter](/helper/meter) | HTML5 `<meter>` rendering. |
| [Progress](/helper/progress) | Progress bars (text + HTML5 `<progress>`). |
| [Tree](/helper/tree) | Render nested tree structures. |
| [Typography](/helper/typography) | Typographic cleanup at render time. |

Load any helper from your `AppView`:

```php
public function initialize(): void {
    parent::initialize();
    $this->loadHelper('Tools.Common');
    $this->loadHelper('Tools.Format');
}
```
