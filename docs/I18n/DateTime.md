# DateTime and Date

This package ships with improved
- DateTime
- Date

extending the core value objects.

They can also work with select/dropdown date(time) form controls and provide other improvements.

## Validation

For improved validation, the Shim Table - that you can extend in your tables - ships with
- `validateDateTime()`
- `validateDate()`
- `validateTime()`

They provide additional configs to work with:
- `timeFormat`
- `after`/`before` (field name to validate against)
- `min`/`max`
