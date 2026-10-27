<?php

declare(strict_types=1);

namespace Pollora\MetaboxBridge\Discovery;

use Pollora\Discovery\Domain\Contracts\DiscoveryInterface;
use Pollora\Discovery\Domain\Contracts\DiscoveryLocationInterface;
use Pollora\Discovery\Domain\Contracts\ReflectionCacheInterface;
use Pollora\Discovery\Domain\Services\IsDiscovery;
use Pollora\Metabox\Attributes\Block;
use Pollora\Metabox\Attributes\FieldGroup;
use Pollora\Metabox\Attributes\Registrar;
use Pollora\Metabox\Attributes\SettingsPage;
use Spatie\StructureDiscoverer\Data\DiscoveredClass;
use Spatie\StructureDiscoverer\Data\DiscoveredStructure;
use Throwable;

/**
 * Finds the classes declared with #[FieldGroup], #[Block] or #[SettingsPage] and registers them.
 *
 * Pollora applies the discoveries again for every module and theme: the Registrar
 * registers each class once.
 */
final class MetaboxDiscovery implements DiscoveryInterface
{
    use IsDiscovery;

    private const array ATTRIBUTES = [FieldGroup::class, Block::class, SettingsPage::class];

    public function discover(DiscoveryLocationInterface $location, DiscoveredStructure $structure, ?ReflectionCacheInterface $reflectionCache = null): void
    {
        if (! $structure instanceof DiscoveredClass || $structure->isAbstract) {
            return;
        }

        foreach ($structure->attributes as $attribute) {
            if (in_array($attribute->class, self::ATTRIBUTES, true)) {
                $this->getItems()->add($location, $structure->namespace.'\\'.$structure->name);

                return;
            }
        }
    }

    /**
     * Register the discovered classes. An invalid declaration is reported without
     * preventing the other classes, and the other discoveries, from being registered.
     */
    public function apply(): void
    {
        foreach ($this->getItems() as $class) {
            try {
                Registrar::register($class);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }
}
