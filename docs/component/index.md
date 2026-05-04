# Components

Three controller components for boilerplate you would otherwise re-implement.

| Component | Purpose |
| --- | --- |
| [Common](/component/common) | Auto-trim POST data, common lifecycle helpers. |
| [Mobile](/component/mobile) | User-agent based mobile detection. |
| [RefererRedirect](/component/referer-redirect) | Safe `redirect-to-referer` flow with allow-listing. |

Load from `AppController`:

```php
public function initialize(): void {
    parent::initialize();
    $this->loadComponent('Tools.Common');
}
```
