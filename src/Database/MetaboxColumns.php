<?php

declare(strict_types=1);

namespace Pollora\MetaboxBridge\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use InvalidArgumentException;
use Pollora\Metabox\Enums\ModelSupport;

/**
 * The columns MB Custom Table expects in a table, available in migrations as
 * $table->metaboxObject() and $table->metaboxModel().
 */
final class MetaboxColumns
{
    /**
     * The ID column of a table storing the field values of posts, terms or users:
     * the ID of the object, not auto-incremented.
     */
    public static function object(Blueprint $table): ColumnDefinition
    {
        return $table->unsignedBigInteger('ID')->primary();
    }

    /**
     * The auto-incremented ID column of a custom model, and the columns of its supports,
     * as MB Custom Table creates them.
     */
    public static function model(Blueprint $table, string|ModelSupport ...$supports): ColumnDefinition
    {
        $id = $table->bigIncrements('ID');

        foreach (array_unique(array_map(self::support(...), $supports), SORT_REGULAR) as $support) {
            match ($support) {
                ModelSupport::Author => $table->unsignedBigInteger('author')->nullable()->index(),
                ModelSupport::PublishedDate => $table->dateTime('published_date')->nullable(),
                ModelSupport::ModifiedDate => $table->dateTime('modified_date')->nullable(),
            };
        }

        return $id;
    }

    private static function support(string|ModelSupport $support): ModelSupport
    {
        return $support instanceof ModelSupport ? $support : (ModelSupport::tryFrom($support)
            ?? throw new InvalidArgumentException("Unknown model support '{$support}': use author, published_date or modified_date."));
    }
}
