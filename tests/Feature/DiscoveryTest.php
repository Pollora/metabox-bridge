<?php

declare(strict_types=1);

use Illuminate\Contracts\Debug\ExceptionHandler;
use Pollora\Application\Domain\Contracts\DebugDetectorInterface;
use Pollora\Attributes\PostType;
use Pollora\Attributes\Taxonomy;
use Pollora\Discovery\Domain\Contracts\DiscoveryEngineInterface;
use Pollora\Discovery\Domain\Models\DirectoryLocation;
use Pollora\Discovery\Infrastructure\Services\DiscoveryEngine;
use Pollora\Metabox\Attributes\Registrar;
use Pollora\MetaboxBridge\Discovery\MetaboxDiscovery;
use Pollora\MetaboxBridge\Discovery\PolloraIntegration;
use Pollora\MetaboxBridge\MetaboxBridgeServiceProvider;
use Pollora\MetaboxBridge\Tests\Fixtures\Cms\Blocks\Home\CallToAction;
use Pollora\MetaboxBridge\Tests\Fixtures\Cms\Fields\EventFields;
use Pollora\MetaboxBridge\Tests\Fixtures\Cms\PostTypes\ConferenceSession;
use Pollora\MetaboxBridge\Tests\Fixtures\Cms\PostTypes\Event;
use Pollora\MetaboxBridge\Tests\Fixtures\Cms\PostTypes\Topic;

beforeEach(function () {
    Registrar::reset();
    $GLOBALS['test_filters'] = [];

    $this->app['config']->set('metabox.blocks.namespace', 'Pollora\\MetaboxBridge\\Tests\\Fixtures\\Cms\\Blocks');
    $this->app['config']->set('metabox.blocks.category', 'theme/site');
    $this->app['config']->set('metabox.field_groups.priority', 'high');
    $this->app['view']->addLocation(__DIR__.'/../Fixtures/views');
    Registrar::integrate(new PolloraIntegration($this->app, $this->app['config']));
});

function engine(): DiscoveryEngine
{
    app()->singleton(DebugDetectorInterface::class, fn () => new class implements DebugDetectorInterface
    {
        public function isDebugMode(): bool
        {
            return true;
        }
    });
    app()->singleton(DiscoveryEngineInterface::class, fn ($app) => new DiscoveryEngine($app, $app->make(DebugDetectorInterface::class)));

    return app(DiscoveryEngineInterface::class);
}

function metaBoxes(): array
{
    return apply_filters('rwmb_meta_boxes', []);
}

test('the discovery is added to the Pollora engine when it is resolved', function () {
    expect(engine()->getDiscovery('metabox'))->toBeInstanceOf(MetaboxDiscovery::class);

    // Registering the provider again does not add it twice.
    (new MetaboxBridgeServiceProvider($this->app))->register();

    expect(engine()->getDiscoveries()->keys()->all())->toContain('metabox');
});

test('the declared classes are registered once, however many times Pollora applies the discoveries', function () {
    $engine = engine()->addLocation(new DirectoryLocation(__DIR__.'/../Fixtures/Cms'));

    $engine->discover()->apply();
    $engine->discover()->apply();

    expect(array_keys(Registrar::registered()))->toEqualCanonicalizing([CallToAction::class, EventFields::class])
        ->and(metaBoxes())->toHaveCount(2);
});

test('a field group gets the site defaults, its dependencies and the slugs of Pollora post types and taxonomies', function () {
    Registrar::register(EventFields::class);

    expect(metaBoxes()[0])->toMatchArray([
        'id' => 'event_fields',
        'priority' => 'high',
        'post_types' => ['event', 'session'],
        'taxonomies' => ['topic'],
    ])->and(metaBoxes()[0]['fields'][0]['name'])->toBe('Venue');
});

test('a block gets the site category and renders the view named after its namespace and ID', function () {
    Registrar::register(CallToAction::class);

    $block = metaBoxes()[0];

    ob_start();
    $block['render_callback'](['data' => ['label' => 'Join us']], true, 3);

    expect($block['category'])->toBe('theme/site')
        ->and(trim((string) ob_get_clean()))->toBe('<a>Join us</a> preview');
});

test('default views can be turned off', function () {
    $this->app['config']->set('metabox.blocks.views', null);
    Registrar::register(CallToAction::class);

    expect(metaBoxes()[0])->not->toHaveKey('render_callback');
});

test('an invalid declaration is reported without stopping the discovery', function () {
    $discovery = new MetaboxDiscovery;
    $location = new DirectoryLocation(__DIR__);
    $discovery->getItems()->add($location, 'App\\Missing');
    $discovery->getItems()->add($location, CallToAction::class);

    $reported = [];
    $this->app->make(ExceptionHandler::class)->reportable(function (Throwable $e) use (&$reported) {
        $reported[] = $e->getMessage();
    });

    $discovery->apply();

    expect($reported)->toBe(["The class 'App\\Missing' does not exist."])
        ->and(array_keys(Registrar::registered()))->toBe([CallToAction::class]);
});

test('the slugs match the ones Pollora registers', function () {
    $integration = new PolloraIntegration($this->app, $this->app['config']);

    foreach ([Event::class, ConferenceSession::class] as $class) {
        expect($integration->slug($class, 'post_types'))->toBe((new ReflectionClass($class))->getAttributes(PostType::class)[0]->newInstance()->resolveSlug($class));
    }

    expect($integration->slug(Topic::class, 'taxonomies'))->toBe((new Taxonomy)->resolveSlug(Topic::class))
        ->and($integration->slug(Topic::class, 'post_types'))->toBeNull();
})->skip(! method_exists(PostType::class, 'resolveSlug'), 'This Pollora version has no resolveSlug()');
