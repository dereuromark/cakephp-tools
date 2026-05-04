# Migration from 4.x to 5.x

## CommonComponent
- `setHelpers()` has been removed in favor of core usage directly.

## Auth
- MultiColumn authentication has fully been moved to [TinyAuth](https://github.com/dereuromark/cakephp-tinyauth) plugin.

## Utility
- `L10n`, `Mime` classes have been removed
- Mutable `Time` class has been removed, use immutable `DateTime` instead.
- `Number` class has been moved from Utility to I18n namespace.
- `DateTime` class has been moved from Utility to I18n namespace.

## NumberHelper
- Custom engine has been moved from Utility to I18n namespace.

## TimeHelper
- Custom engine has been moved from Utility to I18n namespace.
