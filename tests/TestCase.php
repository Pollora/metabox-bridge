<?php

declare(strict_types=1);

namespace Pollora\MetaboxBridge\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Pollora\MetaboxBridge\MetaboxBridgeServiceProvider;

abstract class TestCase extends BaseTestCase
{
    /**
     * Only load this package: the Pollora providers need WordPress.
     */
    public function ignorePackageDiscoveriesFrom(): array
    {
        return ['*'];
    }

    protected function getPackageProviders($app): array
    {
        return [MetaboxBridgeServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => 'wp_',
        ]);
    }
}
