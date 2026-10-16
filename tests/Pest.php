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

/*
 * WordPress hooks, running the callbacks added to a filter.
 */
function add_filter(string $hookName, callable $callback, int $priority = 10, int $acceptedArgs = 1): bool
{
    $GLOBALS['test_filters'][$hookName][] = $callback;

    return true;
}

function add_action(string $hookName, callable $callback, int $priority = 10, int $acceptedArgs = 1): bool
{
    return true;
}

function apply_filters(string $hookName, mixed $value): mixed
{
    $GLOBALS['test_doing_filters'][] = $hookName;

    try {
        foreach ($GLOBALS['test_filters'][$hookName] ?? [] as $callback) {
            $value = $callback($value);
        }
    } finally {
        array_pop($GLOBALS['test_doing_filters']);
    }

    return $value;
}

function doing_filter(?string $hookName = null): bool
{
    return in_array($hookName, $GLOBALS['test_doing_filters'] ?? [], true);
}
