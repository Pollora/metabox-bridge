<?php

declare(strict_types=1);

namespace Pollora\MetaboxBridge\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Pollora\MetaboxBridge\Eloquent\Concerns\FlushesMetaboxCache;

/**
 * A row of a custom table storing the field values of a post, a term or a user
 * (MB Custom Table): its ID is the ID of the object.
 */
abstract class CustomTableRow extends Model
{
    use FlushesMetaboxCache;

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'ID';

    protected $keyType = 'int';
}
