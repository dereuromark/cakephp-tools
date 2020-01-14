# Migration from 2.x to 3.x: Shims
Shims ease migration as complete parts of the code, such as validation and other model property settings
can be reused immediately without refactoring them right away.

See the [Shim plugin](https://github.com/dereuromark/cakephp-shim) for details.

Note: It does not hurt to have them, if you don't use them. The overhead is minimal.

### Entity
- Enums via enum() are ported in entity, if you used them before.
