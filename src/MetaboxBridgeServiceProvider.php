<?php

declare(strict_types=1);

namespace Pollora\MetaboxBridge;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\ColumnDefinition;
use Illuminate\Support\ServiceProvider;
use Pollora\Discovery\Domain\Contracts\DiscoveryEngineInterface;
use Pollora\Metabox\Attributes\Registrar;
use Pollora\Metabox\Enums\ModelSupport;
use Pollora\MetaboxBridge\Database\MetaboxColumns;
use Pollora\MetaboxBridge\Discovery\MetaboxDiscovery;
use Pollora\MetaboxBridge\Discovery\PolloraIntegration;

/**
 * Registers the migration helpers for MB Custom Table, and discovers the classes
 * declared with the pollora/metabox attributes.
 */
class MetaboxBridgeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/metabox.php', 'metabox');

        // Add the discovery as soon as the engine exists: Pollora runs it while booting.
        $this->app->afterResolving(DiscoveryEngineInterface::class, $this->addDiscovery(...));

        if ($this->app->resolved(DiscoveryEngineInterface::class)) {
            $this->addDiscovery($this->app->make(DiscoveryEngineInterface::class));
        }
    }

    public function boot(): void
    {
        $this->publishes([__DIR__.'/../config/metabox.php' => $this->app->configPath('metabox.php')], 'metabox-config');

        Registrar::integrate(new PolloraIntegration($this->app, $this->app->make('config')));

        Blueprint::macro('metaboxObject', function (): ColumnDefinition {
            /** @var Blueprint $this */
            return MetaboxColumns::object($this);
        });

        Blueprint::macro('metaboxModel', function (string|ModelSupport ...$supports): ColumnDefinition {
            /** @var Blueprint $this */
            return MetaboxColumns::model($this, ...$supports);
        });
    }

    private function addDiscovery(DiscoveryEngineInterface $engine): void
    {
        if (! $engine->getDiscoveries()->has('metabox')) {
            $engine->addDiscovery('metabox', new MetaboxDiscovery);
        }
    }
}
