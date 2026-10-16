<?php

declare(strict_types=1);

namespace Pollora\MetaboxBridge\Tests\Fixtures\Cms\Blocks\Home;

use Pollora\Metabox\Attributes\Block;
use Pollora\Metabox\Fields\Text;

#[Block('Call to action', 'cta')]
final class CallToAction
{
    public function fields(): array
    {
        return [Text::make('Label', 'label')];
    }
}
