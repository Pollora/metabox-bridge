<?php

declare(strict_types=1);

namespace Pollora\MetaboxBridge\Tests\Fixtures\Cms\Fields;

/**
 * A dependency injected by the container.
 */
final class Greeter
{
    public function label(): string
    {
        return 'Venue';
    }
}
