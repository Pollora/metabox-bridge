<?php

declare(strict_types=1);

namespace Pollora\MetaboxBridge\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Pollora\MetaboxBridge\Eloquent\Concerns\FlushesMetaboxCache;

/**
 * An item of a custom model (MB Custom Table), with an auto-incremented ID.
 *
 * When the model supports the published and modified dates, set $timestamps to
 * true: Eloquent fills them like Meta Box. With only one of them, set the other
 * constant to null.
 */
abstract class ModelRecord extends Model
{
    use FlushesMetaboxCache;

    public const CREATED_AT = 'published_date';

    public const UPDATED_AT = 'modified_date';

    public $timestamps = false;

    protected $primaryKey = 'ID';
}
