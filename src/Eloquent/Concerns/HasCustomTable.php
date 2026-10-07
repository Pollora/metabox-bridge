<?php

declare(strict_types=1);

namespace Pollora\MetaboxBridge\Eloquent\Concerns;

use Illuminate\Database\Eloquent\Relations\HasOne;
use Pollora\MetaboxBridge\Eloquent\CustomTableRow;

/**
 * For a model of posts, terms or users, such as Pollora\Models\Post: the row of
 * a custom table holding its field values.
 */
trait HasCustomTable
{
    /**
     * The row of the custom table holding the field values of this object.
     *
     * @template TRow of CustomTableRow
     *
     * @param  class-string<TRow>  $row
     * @return HasOne<TRow, $this>
     */
    protected function hasCustomTable(string $row): HasOne
    {
        return $this->hasOne($row, 'ID', $this->getKeyName());
    }
}
