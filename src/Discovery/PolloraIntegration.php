<?php

declare(strict_types=1);

namespace Pollora\MetaboxBridge\Discovery;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\View\Factory;
use Illuminate\Support\Str;
use Pollora\Attributes\PostType;
use Pollora\Attributes\Taxonomy;
use Pollora\Metabox\Attributes\Contracts\Integration;
use Pollora\Metabox\Block;
use Pollora\Metabox\FieldGroup;
use Pollora\Metabox\SettingsPage;
use ReflectionClass;

/**
 * Connects the declared classes to Pollora: the container creates them, config/metabox.php
 * gives the defaults, Pollora post types and taxonomies are targets, and blocks render Blade views.
 */
final readonly class PolloraIntegration implements Integration
{
    public function __construct(
        private Container $container,
        private Repository $config,
    ) {}

    public function instance(string $class): object
    {
        return $this->container->make($class);
    }

    public function configure(FieldGroup|SettingsPage $builder, string $class): void
    {
        if ($builder instanceof Block) {
            if (($category = $this->config->get('metabox.blocks.category')) !== null) {
                $builder->category($category);
            }

            if (($mode = $this->config->get('metabox.blocks.mode')) !== null) {
                $builder->mode($mode);
            }

            return;
        }

        if ($builder instanceof FieldGroup && ($priority = $this->config->get('metabox.field_groups.priority')) !== null) {
            $builder->priority($priority);
        }
    }

    public function slug(string $class, string $key): ?string
    {
        $attribute = match ($key) {
            'post_types' => PostType::class,
            'taxonomies' => Taxonomy::class,
            default => null,
        };

        $declaration = $attribute === null ? null : ((new ReflectionClass($class))->getAttributes($attribute)[0] ?? null)?->newInstance();

        if ($declaration === null) {
            return null;
        }

        // The slug Pollora registers: the attribute one, or the class name in kebab case, cut to 20 characters.
        return $declaration->slug ?? substr(Str::kebab(class_basename($class)), 0, 20);
    }

    public function view(string $class, string $blockId): ?string
    {
        $views = $this->config->get('metabox.blocks.views');

        if ($views === null) {
            return null;
        }

        $namespace = trim((string) $this->config->get('metabox.blocks.namespace'), '\\').'\\';
        $folders = [];

        if ($namespace !== '\\' && str_starts_with($class, $namespace)) {
            $folders = array_map(Str::kebab(...), array_slice(explode('\\', substr($class, strlen($namespace))), 0, -1));
        }

        return implode('.', [$views, ...$folders, $blockId]);
    }

    public function render(string $view, array $data): string
    {
        return $this->container->make(Factory::class)->make($view, $data)->render();
    }
}
