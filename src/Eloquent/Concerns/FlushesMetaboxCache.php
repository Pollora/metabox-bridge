<?php

declare(strict_types=1);

namespace Pollora\MetaboxBridge\Eloquent\Concerns;

/**
 * Removes a row from the MB Custom Table cache when it is saved or deleted with
 * Eloquent, so that rwmb_meta() does not return the former values.
 *
 * Queries that bypass model events, such as Row::where(...)->update([...]), do not
 * clear the cache.
 */
trait FlushesMetaboxCache
{
    public static function bootFlushesMetaboxCache(): void
    {
        static::saved(fn (self $row) => $row->flushMetaboxCache());
        static::deleted(fn (self $row) => $row->flushMetaboxCache());
    }

    /**
     * Remove the row from the MB Custom Table cache.
     */
    public function flushMetaboxCache(): void
    {
        if (! function_exists('wp_cache_delete') || ! $this->getKey()) {
            return;
        }

        // MB Custom Table caches the rows by table name, with the WordPress prefix,
        // which is the connection prefix in Pollora.
        $table = $this->getConnection()->getTablePrefix().$this->getTable();

        wp_cache_delete((int) $this->getKey(), "rwmb_{$table}_table_data");
    }
}
