<?php

declare(strict_types=1);

use Pollora\MetaboxBridge\Tests\TestCase;

uses(TestCase::class)->in('Feature');

/*
 * WordPress object cache, recording the deleted keys.
 */
function wp_cache_delete(int|string $key, string $group = ''): bool
{
    $GLOBALS['wp_cache_deleted'][] = [$key, $group];

    return true;
}
