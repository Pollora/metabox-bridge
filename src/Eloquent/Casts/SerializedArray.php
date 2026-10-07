<?php

declare(strict_types=1);

namespace Pollora\MetaboxBridge\Eloquent\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * An array stored serialized by MB Custom Table: the value of a cloneable or
 * multiple field, or of a group.
 *
 * @implements CastsAttributes<array|string|null, array|string|null>
 */
final class SerializedArray implements CastsAttributes
{
    /**
     * Unserialize the arrays stored by Meta Box. Objects are never unserialized.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array|string|null
    {
        if (! is_string($value) || ! str_starts_with($value, 'a:')) {
            return $value;
        }

        $array = unserialize($value, ['allowed_classes' => false]);

        return is_array($array) ? $array : $value;
    }

    /**
     * Serialize arrays, like Meta Box.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return is_array($value) ? serialize($value) : (string) $value;
    }
}
