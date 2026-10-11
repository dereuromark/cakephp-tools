# Plugin Ecosystem

Tools sits at the center of a small family of CakePHP plugins by the same maintainer. Each one has a specific job: Tools pulls in the few it depends on, and the others are opt-in.

## What Tools depends on (auto-installed)

| Plugin | Why Tools needs it |
| --- | --- |
| [cakephp-shim](https://github.com/dereuromark/cakephp-shim) | 4.x → 5.x BC shims (validation, model property style, etc.). Letting you migrate apps gradually instead of rewriting. |

## Frequently paired with Tools

These are not dependencies, but they're commonly installed alongside Tools because they extend the same areas.

| Plugin | What it adds | When to add it |
| --- | --- | --- |
| [cakephp-setup](https://github.com/dereuromark/cakephp-setup) | Setup commands and optional development and maintenance tooling. | When you want a head start on baking and an opinionated admin shell. |
| [cakephp-ide-helper](https://github.com/dereuromark/cakephp-ide-helper) | PHPStorm meta files for CakePHP: autocomplete on association calls, `fetchTable()`, etc. | When editor completion or generated annotations are needed. |
| [cakephp-templating](https://github.com/dereuromark/cakephp-templating) | Icon helper (Font Awesome / Bootstrap / custom collections). | The dedicated replacement for the deprecated [`Tools\View\Helper\IconHelper`](/helper/icon). |

## Other plugins from the same author

Worth knowing about, even if Tools doesn't depend on them.

- **[cakephp-queue](https://github.com/dereuromark/cakephp-queue)**: background jobs.
- **[cakephp-tinyauth](https://github.com/dereuromark/cakephp-tinyauth)**: INI-based ACL.
- **[cakephp-geo](https://github.com/dereuromark/cakephp-geo)**: Geocoder behavior, GoogleMapsV3 helper.
- **[cakephp-dto](https://github.com/dereuromark/cakephp-dto)**: generated DTO classes from XML.
- **[cakephp-templating](https://github.com/dereuromark/cakephp-templating)**: extra template helpers and view utilities.

Browse the full list at <https://github.com/dereuromark?tab=repositories&q=cakephp>.

For factory-based test fixtures, see the separately maintained [Fixture Factories plugin](https://github.com/vierge-noire/cakephp-fixture-factories).
