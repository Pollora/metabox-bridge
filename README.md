<p align="center">
  <a href="https://pollora.dev">
    <img src="https://raw.githubusercontent.com/Pollora/.github/main/brand/banners/metabox-bridge.png" width="100%" alt="Metabox Bridge: Meta Box custom tables with migrations and Eloquent">
  </a>
</p>

<p align="center">
  <a href="https://packagist.org/packages/pollora/metabox-bridge"><img src="https://img.shields.io/packagist/v/pollora/metabox-bridge" alt="Latest version"></a>
  <a href="https://packagist.org/packages/pollora/metabox-bridge"><img src="https://img.shields.io/packagist/dt/pollora/metabox-bridge" alt="Total downloads"></a>
  <a href="https://github.com/Pollora/metabox-bridge/actions/workflows/tests.yml"><img src="https://github.com/Pollora/metabox-bridge/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
  <a href="LICENSE"><img src="https://img.shields.io/github/license/Pollora/metabox-bridge" alt="License"></a>
</p>

Metabox Bridge connects a [Pollora](https://pollora.dev) application to the data of [Meta Box](https://metabox.io). Field values that [MB Custom Table](https://docs.metabox.io/extensions/mb-custom-table/) stores in custom tables become plain Laravel: the tables are created by migrations, and the rows are read and written with Eloquent models that follow the Meta Box conventions.

It completes [pollora/metabox](https://github.com/Pollora/metabox), which declares the meta boxes, the fields and the custom models.

> Part of [Pollora](https://pollora.dev), the Laravel framework for WordPress. It requires Pollora: in a WordPress project without Pollora, use [pollora/metabox](https://github.com/Pollora/metabox) alone.

## Installation

```bash
composer require pollora/metabox-bridge
```

Requires PHP 8.3+, Pollora 13, and the [Meta Box](https://wordpress.org/plugins/meta-box/) plugin with MB Custom Table (included in Meta Box AIO). The service provider is discovered automatically.

## Quick start

Store the details of the `event` post type in a custom table:

```php
// database/migrations/2026_10_08_000000_create_events_table.php
Schema::create('events', function (Blueprint $table) {
    $table->metaboxObject();                       // ID: the ID of the post
    $table->string('venue', 100)->nullable()->index();
    $table->decimal('price', 10, 2)->nullable();
    $table->text('speakers')->nullable();          // a cloneable field, stored serialized
});
```

```php
// The fields, with pollora/metabox
Metabox::make('Event details', 'event_details')
    ->location(Location::postTypes('event'))
    ->customTable(EventDetails::class)
    ->fields([
        Text::make('Venue', 'venue'),
        Number::make('Price', 'price')->step(0.01),
        Text::make('Speakers', 'speakers')->cloneable(),
    ]);
```

```php
// The rows, with Eloquent
use Pollora\MetaboxBridge\Eloquent\Casts\SerializedArray;
use Pollora\MetaboxBridge\Eloquent\CustomTableRow;

class EventDetails extends CustomTableRow
{
    protected $table = 'events';

    protected $fillable = ['venue', 'price', 'speakers'];

    protected $casts = ['speakers' => SerializedArray::class];
}

$details = EventDetails::find($post->ID);
$details->speakers;                   // ['Ada', 'Grace']
$details->update(['price' => 49]);    // rwmb_meta() returns the new value
```

Table names have no prefix: the Laravel connection adds it, and Pollora gives WordPress the same prefix, so `events` is the `wp_events` table that `customTable('events')` or `customTable(EventDetails::class)` uses.

## Migrations

The service provider adds two methods to the `Blueprint` of migrations, which create the columns MB Custom Table expects:

- **`$table->metaboxObject()`**: For a table holding the field values of posts, terms or users. Adds the `ID` column, the ID of the object, as primary key.
- **`$table->metaboxModel(string|ModelSupport ...$supports)`**: For the table of a [custom model](https://github.com/Pollora/metabox/blob/main/docs/custom-tables.md#custom-models). Adds an auto-incremented `ID` column, and the columns of the supports: `author` (indexed), `published_date` and `modified_date`. An unknown support throws an `InvalidArgumentException`.

The other columns are named after the field IDs. Make them nullable: a field without value may be missing from a row. Cloneable fields, multiple fields and groups are stored serialized: use a `text` column.

```php
Schema::create('transactions', function (Blueprint $table) {
    $table->metaboxModel(ModelSupport::Author, ModelSupport::PublishedDate, ModelSupport::ModifiedDate);
    $table->decimal('amount', 10, 2)->nullable()->index();
    $table->string('status', 20)->nullable()->index();
});
```

## Eloquent models

### Rows of posts, terms and users

`CustomTableRow` is the base model of a table holding the field values of posts, terms or users: its primary key is `ID`, the ID of the object, which is not auto-incremented, and it has no timestamps.

To reach the row from the post, use the `HasCustomTable` trait on your Pollora model:

```php
use Pollora\Models\Post;
use Pollora\MetaboxBridge\Eloquent\Concerns\HasCustomTable;

class Event extends Post
{
    use HasCustomTable;

    public function details(): HasOne
    {
        return $this->hasCustomTable(EventDetails::class);
    }
}

$event->details->venue;
Event::with('details')->get();
```

- **`hasCustomTable(string $row)`**: Returns the `HasOne` relation to the row whose `ID` is the key of the model. It works on any model of posts, terms or users.

### Items of custom models

`ModelRecord` is the base model of a custom model: its primary key is the auto-incremented `ID`.

```php
use Pollora\MetaboxBridge\Eloquent\ModelRecord;

class Transaction extends ModelRecord
{
    public $timestamps = true;   // the model supports published_date and modified_date

    protected $table = 'transactions';
}

MetaboxModel::make('transaction')
    ->table(Transaction::class)
    ->labels(plural: 'Transactions', singular: 'Transaction')
    ->supports(ModelSupport::Author, ModelSupport::PublishedDate, ModelSupport::ModifiedDate);
```

Its timestamps are the `published_date` and `modified_date` columns of Meta Box, disabled by default. Enable them with `$timestamps = true` when the model supports both dates; with a single date, also set the other constant, `CREATED_AT` or `UPDATED_AT`, to `null`. The `author` column is filled by Meta Box in the admin only: set it yourself when creating items with Eloquent.

### Serialized values

Meta Box stores the value of cloneable fields, multiple fields and groups as a serialized array. Cast these columns with `SerializedArray`: it unserializes them when reading, and serializes arrays when writing. It never unserializes objects.

```php
protected $casts = ['speakers' => SerializedArray::class];
```

### Meta Box cache

MB Custom Table caches the rows in the WordPress object cache. Both base models remove a row from that cache when it is saved or deleted, so that `rwmb_meta()` returns the new values. Queries that do not fire model events, such as `EventDetails::where(...)->update([...])`, do not clear it: call `flushMetaboxCache()` on each row, or flush the cache group.

- **`flushMetaboxCache()`**: Removes the row from the MB Custom Table cache.

## Multisite

The Laravel connection prefix does not change with the current site, while Meta Box uses the prefix of the current site (`wp_2_`). On a multisite network, the tables created by migrations are those of the main site.

## License

MIT. See [LICENSE](LICENSE).
