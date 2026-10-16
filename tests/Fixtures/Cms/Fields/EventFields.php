<?php

declare(strict_types=1);

namespace Pollora\MetaboxBridge\Tests\Fixtures\Cms\Fields;

use Pollora\Metabox\Attributes\FieldGroup;
use Pollora\Metabox\Attributes\FieldGroup\PostTypes;
use Pollora\Metabox\Attributes\FieldGroup\Taxonomies;
use Pollora\Metabox\Fields\Text;
use Pollora\MetaboxBridge\Tests\Fixtures\Cms\PostTypes\ConferenceSession;
use Pollora\MetaboxBridge\Tests\Fixtures\Cms\PostTypes\Event;
use Pollora\MetaboxBridge\Tests\Fixtures\Cms\PostTypes\Topic;

#[FieldGroup('Event details')]
#[PostTypes(Event::class, ConferenceSession::class)]
#[Taxonomies(Topic::class)]
final class EventFields
{
    public function __construct(public readonly Greeter $greeter) {}

    public function fields(): array
    {
        return [Text::make($this->greeter->label(), 'venue')];
    }
}
