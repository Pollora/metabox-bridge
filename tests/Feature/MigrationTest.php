<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Pollora\Metabox\Enums\ModelSupport;
use Pollora\MetaboxBridge\Database\MetaboxColumns;

function columns(string $table): array
{
    return collect(Schema::getColumns($table))->mapWithKeys(fn ($column) => [$column['name'] => $column])->all();
}

test('metaboxObject() adds the ID of the object as primary key', function () {
    Schema::create('events', function (Blueprint $table) {
        $table->metaboxObject();
        $table->string('venue')->nullable();
    });

    $columns = columns('events');

    expect(array_keys($columns))->toBe(['ID', 'venue'])
        ->and(Schema::getIndexes('events')[0])->toMatchArray(['columns' => ['ID'], 'primary' => true]);
});

test('metaboxObject() does not auto-increment the ID', function () {
    // SQLite reports every integer primary key as auto-incremented: check the definition sent to MySQL.
    $column = MetaboxColumns::object(new Blueprint(Schema::getConnection(), 'events'));

    expect($column->getAttributes())->toMatchArray(['type' => 'bigInteger', 'name' => 'ID', 'unsigned' => true, 'primary' => true])
        ->and($column->get('autoIncrement'))->toBeFalse();
});

test('metaboxModel() adds an auto-incremented ID and the support columns', function () {
    Schema::create('transactions', function (Blueprint $table) {
        $table->metaboxModel(ModelSupport::Author, 'published_date', ModelSupport::ModifiedDate, ModelSupport::Author);
        $table->decimal('amount', 10, 2)->nullable();
    });

    $columns = columns('transactions');

    expect(array_keys($columns))->toBe(['ID', 'author', 'published_date', 'modified_date', 'amount'])
        ->and($columns['ID']['auto_increment'])->toBeTrue()
        ->and($columns['author']['nullable'])->toBeTrue()
        ->and(collect(Schema::getIndexes('transactions'))->pluck('columns')->all())->toContain(['author']);
});

test('metaboxModel() without supports only adds the ID', function () {
    Schema::create('logs', fn (Blueprint $table) => $table->metaboxModel());

    expect(array_keys(columns('logs')))->toBe(['ID']);
});

test('metaboxModel() rejects unknown supports', function () {
    expect(fn () => Schema::create('bad', fn (Blueprint $table) => $table->metaboxModel('comments')))
        ->toThrow(InvalidArgumentException::class);
});

test('tables get the connection prefix, like the WordPress tables in Pollora', function () {
    Schema::create('events', fn (Blueprint $table) => $table->metaboxObject());

    expect(Schema::getConnection()->getTablePrefix().'events')->toBe('wp_events')
        ->and(Schema::hasTable('events'))->toBeTrue();
});
