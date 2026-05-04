# Behaviors

Ten ORM behaviors covering the patterns that appear across most CakePHP applications.

| Behavior | Purpose |
| --- | --- |
| [AfterSave](/behavior/after-save) | Hook for queueing follow-up work after an entity save. |
| [Bitmasked](/behavior/bitmasked) | Store a set of flags / enum values as a single bitmask integer column. |
| [Encryption](/behavior/encryption) | Transparent column-level encryption / decryption on save & find. |
| [Jsonable](/behavior/jsonable) | Automatic JSON encode/decode for fields stored as JSON. |
| [Passwordable](/behavior/passwordable) | Password change flow with confirm + current-password validation. |
| [Reset](/behavior/reset) | Reset / recompute denormalized fields across rows. |
| [Slugged](/behavior/slugged) | Auto-generate URL slugs from a source field. |
| [String](/behavior/string) | String-related field manipulation (whitespace, casing, etc.). |
| [Toggle](/behavior/toggle) | Boolean-flag toggling with constraints (e.g. only one active). |
| [Typographic](/behavior/typographic) | Typographic cleanup of text fields (smart quotes, dashes, etc.). |

To use any behavior in a Table:

```php
public function initialize(array $config): void {
    parent::initialize($config);
    $this->addBehavior('Tools.Bitmasked');
}
```
