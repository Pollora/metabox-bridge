<?php

declare(strict_types=1);

use Pollora\Attributes\Taxonomy;
use Pollora\Discovery\Domain\Contracts\DiscoveryEngineInterface;
use Pollora\Discovery\Domain\Models\DirectoryLocation;
use Pollora\Metabox\Attributes\FieldGroup as FieldGroupAttribute;
use Pollora\Metabox\Attributes\Registrar;
use Pollora\Metabox\Block;
use Pollora\Metabox\FieldGroup;
use Pollora\Metabox\SettingsPage;
use Pollora\MetaboxBridge\Discovery\MetaboxDiscovery;
use Pollora\MetaboxBridge\Discovery\PolloraIntegration;
use Pollora\MetaboxBridge\Eloquent\Casts\SerializedArray;
use Pollora\MetaboxBridge\Eloquent\CustomTableRow;
use Pollora\MetaboxBridge\MetaboxBridgeServiceProvider;
use Pollora\MetaboxBridge\Tests\Fixtures\Cms\Blocks\Home\CallToAction;
use Spatie\StructureDiscoverer\Data\DiscoveredAttribute;
use Spatie\StructureDiscoverer\Data\DiscoveredClass;
use Spatie\StructureDiscoverer\Data\DiscoveredInterface;

#[Taxonomy]
final class Abcdefghijklmnopqrst {}

#[Taxonomy]
final class Abcdefghijklmnopqrstu {}

#[Taxonomy]
final class Abcdefghijklmnopqrstuvwxyz {}

#[Taxonomy]
final class VeryLongTaxonomyNameForTests {}

#[Taxonomy('explicit-slug')]
final class ExplicitSlugTaxonomy {}

#[FieldGroupAttribute('Abstract group')]
abstract class AbstractGroupFixture {}

class NoKeyRow extends CustomTableRow
{
    protected $table = 'events';
}

function integration(): PolloraIntegration
{
    return new PolloraIntegration(app(), app('config'));
}

function discoveredClass(bool $abstract, array $attributes, string $namespace = 'App\\Cms', string $name = 'Thing'): DiscoveredClass
{
    return new DiscoveredClass($name, '/tmp/Thing.php', $namespace, false, $abstract, false, null, [], array_map(fn (string $class) => new DiscoveredAttribute($class), $attributes));
}

function discovered(MetaboxDiscovery $discovery): array
{
    return iterator_to_array($discovery->getItems(), false);
}

beforeEach(function () {
    $GLOBALS['test_filters'] = [];
    $GLOBALS['wp_cache_deleted'] = [];
});

// --- PolloraIntegration::configure

test('a settings page does not get the field group priority', function () {
    $this->app['config']->set('metabox.field_groups.priority', 'high');
    $page = SettingsPage::make('Options', 'options');

    integration()->configure($page, 'App\\Options');

    expect($page)->toBeInstanceOf(SettingsPage::class);
});

test('a field group gets the configured priority, only when there is one', function () {
    $this->app['config']->set('metabox.field_groups.priority', 'low');
    $group = FieldGroup::make('Group', 'group');
    integration()->configure($group, 'App\\Group');

    $this->app['config']->set('metabox.field_groups.priority', null);
    $untouched = FieldGroup::make('Group', 'group');
    integration()->configure($untouched, 'App\\Group');

    expect($group->build()['priority'])->toBe('low')
        ->and($untouched->build())->not->toHaveKey('priority');
});

test('a block gets the configured category and mode, only when there are some', function () {
    $this->app['config']->set('metabox.blocks.category', 'theme/site');
    $this->app['config']->set('metabox.blocks.mode', 'preview');
    $this->app['config']->set('metabox.field_groups.priority', 'high');
    $block = Block::make('Block', 'block');
    integration()->configure($block, 'App\\Block');

    $this->app['config']->set('metabox.blocks.category', null);
    $this->app['config']->set('metabox.blocks.mode', null);
    $untouched = Block::make('Block', 'block');
    integration()->configure($untouched, 'App\\Block');

    $built = $block->build();

    expect($built['category'])->toBe('theme/site')
        ->and($built['mode'])->toBe('preview')
        ->and($built)->not->toHaveKey('priority')
        ->and($untouched->build())->toMatchArray(['category' => 'design', 'mode' => 'edit']);
});

test('the category and the mode of a block are set independently', function () {
    $this->app['config']->set('metabox.blocks.category', null);
    $this->app['config']->set('metabox.blocks.mode', 'preview');
    $onlyMode = Block::make('Block', 'block');
    integration()->configure($onlyMode, 'App\\Block');

    $this->app['config']->set('metabox.blocks.category', 'text');
    $this->app['config']->set('metabox.blocks.mode', null);
    $onlyCategory = Block::make('Block', 'block');
    integration()->configure($onlyCategory, 'App\\Block');

    expect($onlyMode->build())->toMatchArray(['mode' => 'preview', 'category' => 'design'])
        ->and($onlyCategory->build())->toMatchArray(['category' => 'text', 'mode' => 'edit']);
});

// --- PolloraIntegration::slug

test('a slug is cut to 20 characters, with the boundary kept', function (string $class, string $slug) {
    expect(integration()->slug($class, 'taxonomies'))->toBe($slug);
})->with([
    '20 characters' => [Abcdefghijklmnopqrst::class, 'abcdefghijklmnopqrst'],
    '21 characters' => [Abcdefghijklmnopqrstu::class, 'abcdefghijklmnopqrst'],
    'longer' => [Abcdefghijklmnopqrstuvwxyz::class, 'abcdefghijklmnopqrst'],
    'kebab case' => [VeryLongTaxonomyNameForTests::class, 'very-long-taxonomy-n'],
]);

test('an explicit slug is used as is', function () {
    expect(integration()->slug(ExplicitSlugTaxonomy::class, 'taxonomies'))->toBe('explicit-slug')
        ->and(integration()->slug(ExplicitSlugTaxonomy::class, 'other'))->toBeNull();
});

// --- PolloraIntegration::view

test('the view of a block follows the namespace and kebab-cases the folders', function () {
    $this->app['config']->set('metabox.blocks.namespace', 'App\\Cms\\Blocks');

    expect(integration()->view('App\\Cms\\Blocks\\LandingPages\\Hero\\Banner', 'banner'))->toBe('blocks.landing-pages.hero.banner')
        ->and(integration()->view('App\\Cms\\Blocks\\Banner', 'banner'))->toBe('blocks.banner');
});

test('the namespace is trimmed of its backslashes', function () {
    $this->app['config']->set('metabox.blocks.namespace', '\\App\\Cms\\Blocks\\');

    expect(integration()->view('App\\Cms\\Blocks\\Home\\Cta', 'cta'))->toBe('blocks.home.cta');
});

test('a class outside the namespace is rendered with the view named after its ID', function () {
    $this->app['config']->set('metabox.blocks.namespace', 'App\\Cms\\Blocks');

    expect(integration()->view('Vendor\\Plugin\\Blocks\\Home\\Cta', 'cta'))->toBe('blocks.cta');
});

test('a namespace which is only a prefix of a folder does not match', function () {
    $this->app['config']->set('metabox.blocks.namespace', 'App\\Cms');

    expect(integration()->view('App\\CmsOther\\Home\\Cta', 'cta'))->toBe('blocks.cta');
});

test('without a namespace, no folder is added', function (mixed $namespace) {
    $this->app['config']->set('metabox.blocks.namespace', $namespace);

    expect(integration()->view('App\\Cms\\Blocks\\Home\\Cta', 'cta'))->toBe('blocks.cta');
})->with(['empty' => [''], 'null' => [null], 'backslash' => ['\\']]);

// --- MetaboxDiscovery::discover

test('the classes declared with a Meta Box attribute are discovered with their full name', function (string $attribute) {
    $discovery = new MetaboxDiscovery;
    $location = new DirectoryLocation(__DIR__);

    $discovery->discover($location, discoveredClass(false, ['Other\\Attribute', $attribute], 'App\\Cms', 'Thing'));

    expect(discovered($discovery))->toBe(['App\\Cms\\Thing']);
})->with([
    'field group' => [FieldGroupAttribute::class],
    'block' => [Pollora\Metabox\Attributes\Block::class],
    'settings page' => [Pollora\Metabox\Attributes\SettingsPage::class],
]);

test('a class is added once, however many Meta Box attributes it has', function () {
    $discovery = new MetaboxDiscovery;

    $discovery->discover(new DirectoryLocation(__DIR__), discoveredClass(false, [FieldGroupAttribute::class, Pollora\Metabox\Attributes\Block::class]));

    expect(discovered($discovery))->toHaveCount(1);
});

test('abstract classes, other attributes and other structures are ignored', function () {
    $discovery = new MetaboxDiscovery;
    $location = new DirectoryLocation(__DIR__);

    $discovery->discover($location, discoveredClass(true, [FieldGroupAttribute::class]));
    $discovery->discover($location, discoveredClass(false, ['Other\\Attribute']));
    $discovery->discover($location, discoveredClass(false, []));
    $discovery->discover($location, new DiscoveredInterface('Thing', '/tmp/Thing.php', 'App', [], [new DiscoveredAttribute(FieldGroupAttribute::class)]));

    expect(discovered($discovery))->toBe([]);
});

test('the attribute is compared strictly', function () {
    $discovery = new MetaboxDiscovery;

    $discovery->discover(new DirectoryLocation(__DIR__), discoveredClass(false, ['pollora\\metabox\\attributes\\fieldgroup', ltrim(FieldGroupAttribute::class, '\\').'Extra']));

    expect(discovered($discovery))->toBe([]);
});

// --- SerializedArray

test('scalars are stored as strings and null stays null', function () {
    $cast = new SerializedArray;
    $model = new NoKeyRow;

    expect($cast->set($model, 'speakers', 5, []))->toBe('5')
        ->and($cast->set($model, 'speakers', 1.5, []))->toBe('1.5')
        ->and($cast->set($model, 'speakers', 'text', []))->toBe('text')
        ->and($cast->set($model, 'speakers', null, []))->toBeNull()
        ->and($cast->set($model, 'speakers', [], []))->toBe('a:0:{}');
});

test('values which are not serialized arrays are returned unchanged', function () {
    $cast = new SerializedArray;
    $model = new NoKeyRow;

    expect($cast->get($model, 'speakers', null, []))->toBeNull()
        ->and($cast->get($model, 'speakers', 's:1:"a";', []))->toBe('s:1:"a";')
        ->and($cast->get($model, 'speakers', 'xa:0:{}', []))->toBe('xa:0:{}');
});

test('a column the database returns as a number is read as text', function () {
    $cast = new SerializedArray;
    $model = new NoKeyRow;

    expect($cast->get($model, 'speakers', 5, []))->toBe('5')
        ->and($cast->get($model, 'speakers', 1.5, []))->toBe('1.5');
});

// --- FlushesMetaboxCache

test('a row without a key does not touch the cache', function () {
    (new NoKeyRow)->flushMetaboxCache();

    expect($GLOBALS['wp_cache_deleted'])->toBe([]);
});

test('the key sent to the cache is an integer, in the group named after the prefixed table', function () {
    $row = new NoKeyRow;
    $row->forceFill(['ID' => '7']);
    $row->flushMetaboxCache();

    expect($GLOBALS['wp_cache_deleted'])->toBe([[7, 'rwmb_wp_events_table_data']]);
});

// --- MetaboxBridgeServiceProvider

test('the discovery is added when the engine is already resolved', function () {
    $engine = Mockery::mock(DiscoveryEngineInterface::class);
    $engine->shouldReceive('getDiscoveries')->andReturn(collect());
    $engine->shouldReceive('addDiscovery')->once()->with('metabox', Mockery::type(MetaboxDiscovery::class));

    $this->app->instance(DiscoveryEngineInterface::class, $engine);

    (new MetaboxBridgeServiceProvider($this->app))->register();
});

test('the configuration is published as config/metabox.php under the metabox-config tag', function () {
    $paths = MetaboxBridgeServiceProvider::pathsToPublish(MetaboxBridgeServiceProvider::class, 'metabox-config');

    expect($paths)->toHaveCount(1)
        ->and(realpath((string) array_key_first($paths)))->toBe(realpath(__DIR__.'/../../config/metabox.php'))
        ->and(reset($paths))->toBe($this->app->configPath('metabox.php'));
});

test('booting connects the Pollora integration to the declarations', function () {
    Registrar::reset();
    $this->app['config']->set('metabox.blocks.category', 'theme/site');
    $this->app['config']->set('metabox.blocks.namespace', 'Pollora\\MetaboxBridge\\Tests\\Fixtures\\Cms\\Blocks');

    (new MetaboxBridgeServiceProvider($this->app))->boot();
    Registrar::register(CallToAction::class);

    expect(apply_filters('rwmb_meta_boxes', [])[0]['category'])->toBe('theme/site');
});
