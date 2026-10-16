<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Blocks
    |--------------------------------------------------------------------------
    |
    | Defaults of the blocks declared with #[Block]. Their attributes override them.
    |
    | A block is rendered with the view named after its ID, under `views`: the
    | block 'advanced-quote' with blocks.advanced-quote. The sub-namespaces of
    | the class below `namespace` are added in kebab case: the block 'cta' of
    | App\Cms\Blocks\Home\Cta is rendered with blocks.home.cta. Set `views` to
    | null to render only the blocks with a #[View] attribute.
    |
    */

    'blocks' => [
        'category' => null,
        'mode' => null,
        'views' => 'blocks',
        'namespace' => 'App\\Cms\\Blocks',
    ],

    /*
    |--------------------------------------------------------------------------
    | Field groups
    |--------------------------------------------------------------------------
    |
    | Defaults of the field groups declared with #[FieldGroup]: 'high' displays
    | them above the other boxes.
    |
    */

    'field_groups' => [
        'priority' => null,
    ],

];
