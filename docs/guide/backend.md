---
description: Enable Tools developer routes and protect them using application access controls.
---

# Tools backend

Tools registers a frontend plugin scope and an `Admin` scope when its route hook is enabled. The admin dashboard is available at `/admin/tools`. The library installation disables routes; enable them in the existing plugin registration only when the backend is needed:

```php
'Tools' => ['routes' => true, 'onlyDebug' => true],
```

This is an entry inside the array returned by `config/plugins.php`; preserve other entries. Debug-only loading also disables Tools library features when debug is off. If the application needs Tools libraries in production, keep Tools loaded with routes disabled and register the required admin routes separately in the application's routing configuration.

For manual admin routes, import `Cake\Routing\RouteBuilder` and add this inside the existing returned closure in `config/routes.php`:

```php
$routes->prefix('Admin', function (RouteBuilder $admin): void {
    $admin->plugin('Tools', function (RouteBuilder $tools): void {
        $tools->connect('/', ['controller' => 'Tools', 'action' => 'index']);
        $tools->fallbacks();
    });
});
```

The admin controllers extend the application's `AppController`, so its callbacks and authentication or authorization setup apply. Configure those controls for the developer pages. Enabling routes does not add a login system.

## Available pages

- Pages overview: list application pages and links generated through routing.
- String analyzer: inspect characters and hidden UTF-8 data.
- Bitmask tools: generate SQL snippets for bitmask updates.

The bitmask utility displays SQL for review; it does not execute those updates. Protect the developer pages using the application access controls.
