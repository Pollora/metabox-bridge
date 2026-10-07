<?php

declare(strict_types=1);

namespace Pollora\MetaboxBridge;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\ServiceProvider;
use Pollora\Metabox\Enums\ModelSupport;
use Pollora\MetaboxBridge\Database\MetaboxColumns;

/**
 * Registers the migration helpers for MB Custom Table.
 */
class MetaboxBridgeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Blueprint::macro('metaboxObject', function (): ColumnDefinition {
            /** @var Blueprint $this */
            return MetaboxColumns::object($this);
        });

        Blueprint::macro('metaboxModel', function (string|ModelSupport ...$supports): ColumnDefinition {
            /** @var Blueprint $this */
            return MetaboxColumns::model($this, ...$supports);
        });
    }
}
