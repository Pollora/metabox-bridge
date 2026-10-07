# Changelog

## 1.0.0 - Unreleased

### Added

- Migration methods `metaboxObject()` and `metaboxModel()`, creating the columns MB Custom Table expects.
- `CustomTableRow` and `ModelRecord` Eloquent base models, following the Meta Box conventions.
- `SerializedArray` cast for the values Meta Box stores serialized, never unserializing objects.
- MB Custom Table cache cleared when a row is saved or deleted with Eloquent.
- `HasCustomTable` trait, relating a model of posts, terms or users to its row.
