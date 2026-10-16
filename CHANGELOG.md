# Changelog

## 1.1.0 - Unreleased

### Added

- Discovery of the classes declared with the pollora/metabox attributes (`#[FieldGroup]`, `#[Block]`, `#[SettingsPage]`), registered once each however many times Pollora applies its discoveries. Invalid declarations are reported without stopping the other classes.
- Pollora integration: classes created by the container, Pollora post types and taxonomies usable as targets, and blocks rendered with Blade views named after their namespace and ID.
- `config/metabox.php`, publishable with the `metabox-config` tag: default block category, mode and views, and default field group priority.

### Changed

- Requires pollora/metabox 3.1.

## 1.0.1 - 2026-10-16

### Changed

- Supports pollora/metabox 3.0, where the `Metabox` class is renamed `FieldGroup`. The README examples use `FieldGroup`.

## 1.0.0 - 2026-10-07

### Added

- Migration methods `metaboxObject()` and `metaboxModel()`, creating the columns MB Custom Table expects.
- `CustomTableRow` and `ModelRecord` Eloquent base models, following the Meta Box conventions.
- `SerializedArray` cast for the values Meta Box stores serialized, never unserializing objects.
- MB Custom Table cache cleared when a row is saved or deleted with Eloquent.
- `HasCustomTable` trait, relating a model of posts, terms or users to its row.
